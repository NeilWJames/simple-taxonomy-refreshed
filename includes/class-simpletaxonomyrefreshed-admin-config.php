<?php
/**
 * Simple Taxonomy Admin Configuration Export/Import class file.
 *
 * @package simple-taxonomy-refreshed
 * @author Neil James
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Silence is golden.' );
}

/**
 * Simple Taxonomy Admin Configuration Export/Import class.
 *
 * @package simple-taxonomy-refreshed
 */
class SimpleTaxonomyRefreshed_Admin_Config {
	const CONFIG_SLUG   = 'staxo_config_file';
	const EXP_FILE_SLUG = 'staxo_export_config_file';
	const IMP_FILE_SLUG = 'staxo_import_config_file';

	/**
	 * Instance variable to ensure singleton.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Call to construct the singleton instance.
	 *
	 * @return object
	 */
	final public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new SimpleTaxonomyRefreshed_Admin_Config();
		}
		return self::$instance;
	}

	/**
	 * Protected Constructor
	 *
	 * @return void
	 */
	final protected function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'check_importexport' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 20 );
	}

	/**
	 * Add settings menu page.
	 **/
	public static function add_menu() {
		add_submenu_page( SimpleTaxonomyRefreshed_Admin::ADMIN_SLUG, __( 'Configuration Export/Import', 'simple-taxonomy-refreshed' ), __( 'Configuration Export/Import', 'simple-taxonomy-refreshed' ), 'manage_options', self::CONFIG_SLUG, array( __CLASS__, 'page_config' ) );

		$hook_suffix = 'taxonomies_page_' . self::CONFIG_SLUG;
		// help text.
		add_action( 'load-' . $hook_suffix, array( __CLASS__, 'add_help_tab' ) );

		// ensure sortable libraries.
		add_action( 'admin_print_scripts-' . $hook_suffix, array( __CLASS__, 'add_js_libs' ) );
	}

	/**
	 * Check $_GET/$_POST/$_FILES for Export/Import
	 *
	 * @return void
	 */
	public static function check_importexport() {
		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( isset( $_POST['action'] ) && self::EXP_FILE_SLUG === $_POST['action'] ) {
			check_admin_referer( self::EXP_FILE_SLUG );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have the necessary permissions.', 'simple-taxonomy-refreshed' ), '', array( 'response' => 403 ) );
			}

			// remove any spurious buffered output.
			while ( ob_get_level() ) {
				ob_end_clean();
			}

			// is a reordering of taxonomies wanted?
			$order = array();
			if ( isset( $_POST['taxo_list_arr'] ) && ! empty( $_POST['taxo_list_arr'] ) ) {
				$order = json_decode( sanitize_text_field( wp_unslash( $_POST['taxo_list_arr'] ) ), true );
			}
			$export = self::build_config_export( get_option( OPTION_STAXO ), $order );

			// No cache.
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + ( 24 * 60 * 60 ) ) . ' GMT' );
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
			header( 'Cache-Control: no-store, no-cache, must-revalidate' );
			header( 'Cache-Control: post-check=0, pre-check=0', false );
			header( 'Pragma: no-cache' );

			// Force download dialog.
			header( 'Content-Type: application/force-download' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Type: application/download' );

			// use the Content-Disposition header to supply a recommended filename.
			// and force the browser to display the save dialog.
			// As a local file, we want it in the user timezone.
			// phpcs:ignore
			header( 'Content-Disposition: attachment; filename=staxo-config-' . date( 'ymdHisT' ) . '.json;' );
			// phpcs:ignore  WordPress.Security.EscapeOutput
			die( $export );
		}

		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( isset( $_POST[ self::IMP_FILE_SLUG ] ) && isset( $_FILES['config_file'] ) ) {
			check_admin_referer( self::IMP_FILE_SLUG );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have the necessary permissions.', 'simple-taxonomy-refreshed' ), '', array( 'response' => 403 ) );
			}

			// phpcs:ignore
			if ( $_FILES['config_file']['error'] > 0 ) {
				add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'An error occured during the config file upload. Please fix your server configuration and retry.', 'simple-taxonomy-refreshed' ), 'error' );
				return;
			}

			// phpcs:ignore
			$config_file = file_get_contents( $_FILES['config_file']['tmp_name'] );
			if ( 'SIMPLETAXONOMYREFRESHED' !== substr( $config_file, 0, strlen( 'SIMPLETAXONOMYREFRESHED' ) ) ) {
				add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'This is really a config file for Simple Taxonomy ? Probably corrupt :(', 'simple-taxonomy-refreshed' ), 'error' );
			} else {
				$config_file = json_decode( substr( $config_file, strlen( 'SIMPLETAXONOMYREFRESHED' ) ), true );
				if ( ! is_array( $config_file ) ) {
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'This is really a config file for Simple Taxonomy ? Probably corrupt :(', 'simple-taxonomy-refreshed' ), 'error' );
				} elseif ( isset( $config_file['taxonomies'] ) || isset( $config_file['list_order'] ) || isset( $config_file['externals'] ) ) {
					// Check and sanitise every entry as the admin form does.
					$options = get_option( OPTION_STAXO );
					$import  = self::prepare_import( $config_file, $options );
					if ( empty( $import['config'] ) ) {
						add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'The config file holds no valid settings, so nothing has been imported.', 'simple-taxonomy-refreshed' ), 'error' );
						self::import_notices( $import );
						return;
					}

					// clear caches (need to do first as values could be different).
					if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
						foreach ( (array) $options['taxonomies'] as $taxonomy => $tax_data ) {
							wp_cache_delete( 'staxo_sel_' . $taxonomy );
						}
						wp_cache_delete( 'staxo_own_taxos' );
					}
					wp_cache_delete( 'staxo_taxonomies' );
					wp_cache_delete( 'staxo_orderings' );
					update_option( OPTION_STAXO, $import['config'], true );
					SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache();
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'OK. Configuration is restored.', 'simple-taxonomy-refreshed' ), 'updated' );
					self::import_notices( $import );
					// Change of file may provoke a change of rewrite rules, so trigger it via transient data.
					set_transient( 'simple_taxonomy_refreshed_rewrite', true, 0 );
				} else {
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'This is really a config file for Simple Taxonomy ? Probably corrupt :(', 'simple-taxonomy-refreshed' ), 'error' );
				}
			}
		}
	}

	/**
	 * Check and sanitise an imported configuration, entry by entry, as the admin form does.
	 *
	 * A taxonomy is skipped when its name is not valid, WordPress or another plugin already uses
	 * the name, or any setting the plugin uses has a value the admin form would not store (out of
	 * range, wrong type, or changed by sanitising). Settings the plugin does not use are dropped.
	 * Callback fields follow the staxo_can_edit_callbacks rule.
	 *
	 * @since 4.0.0
	 *
	 * @param array $config  decoded configuration file.
	 * @param mixed $options current plugin options.
	 * @return array {
	 *     @type array    $config    the configuration to store.
	 *     @type string[] $skipped   one line per taxonomy skipped (plain text).
	 *     @type int      $ignored   number of settings dropped because the plugin does not use them.
	 * }
	 */
	public static function prepare_import( $config, $options ) {
		$options = ( is_array( $options ) ? $options : array() );
		$clean   = array();
		$skipped = array();
		$ignored = count( array_diff( array_keys( $config ), array( 'taxonomies', 'externals', 'list_order' ) ) );
		$own     = ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ? array_map( 'strval', array_keys( $options['taxonomies'] ) ) : array() );

		foreach ( array( 'taxonomies', 'externals' ) as $group ) {
			if ( ! isset( $config[ $group ] ) ) {
				continue;
			}
			if ( ! is_array( $config[ $group ] ) ) {
				++$ignored;
				continue;
			}
			foreach ( $config[ $group ] as $key => $data ) {
				$name   = (string) $key;
				$reason = self::import_entry_problem( $group, $name, $data, $own );
				if ( '' !== $reason ) {
					// translators: %1$s is the taxonomy name in the file; %2$s is why it was not imported.
					$skipped[] = sprintf( __( '"%1$s": %2$s', 'simple-taxonomy-refreshed' ), $name, $reason );
					continue;
				}
				if ( 'externals' === $group ) {
					// Only the settings for integrating the taxonomy; the rest belongs to whoever registers it.
					$defaults = SimpleTaxonomyRefreshed_Admin::external_defaults();
					$taxonomy = SimpleTaxonomyRefreshed_Admin::clean_taxonomy_fields( $data, 'skip' );
					$taxonomy = array_merge( $defaults, array_intersect_key( $taxonomy, $defaults + array_flip( array( 'hierarchical', 'show_ui' ) ) ) );
				} else {
					$taxonomy = SimpleTaxonomyRefreshed_Admin::clean_taxonomy_fields( $data, 'default' );
				}
				$taxonomy['name'] = $name;
				$taxonomy         = self::import_value_ranges( $taxonomy );

				// A setting the plugin uses must be stored as it is in the file; otherwise skip the taxonomy.
				$invalid = self::import_invalid_field( $data, $taxonomy );
				if ( '' !== $invalid ) {
					// translators: %1$s is the taxonomy name in the file; %2$s is the name of the setting.
					$skipped[] = sprintf( __( '"%1$s": the setting "%2$s" is not valid.', 'simple-taxonomy-refreshed' ), $name, $invalid );
					continue;
				}
				// Settings the plugin does not use are dropped.
				$ignored += count( array_diff_key( $data, $taxonomy ) );

				$stored                   = ( isset( $options[ $group ][ $name ] ) ? (array) $options[ $group ][ $name ] : array() );
				$clean[ $group ][ $name ] = SimpleTaxonomyRefreshed_Admin::protect_callback_fields( $taxonomy, $stored, $name );
			}
		}

		if ( isset( $config['list_order'] ) ) {
			if ( is_array( $config['list_order'] ) ) {
				foreach ( $config['list_order'] as $post_type => $taxonomies ) {
					$list = ( is_array( $taxonomies ) ? array_values( array_filter( array_map( 'sanitize_key', array_filter( $taxonomies, 'is_string' ) ) ) ) : array() );
					if ( ! is_array( $taxonomies ) || array_values( $taxonomies ) !== $list || sanitize_key( $post_type ) !== (string) $post_type ) {
						++$ignored;
					}
					if ( ! empty( $list ) ) {
						$clean['list_order'][ sanitize_key( $post_type ) ] = $list;
					}
				}
			} else {
				++$ignored;
			}
		}

		return array(
			'config'  => $clean,
			'skipped' => $skipped,
			'ignored' => $ignored,
		);
	}

	/**
	 * Why an entry of an imported configuration cannot be imported.
	 *
	 * @since 4.0.0
	 *
	 * @param string   $group 'taxonomies' or 'externals'.
	 * @param string   $name  taxonomy name (the entry key).
	 * @param mixed    $data  its settings.
	 * @param string[] $own   taxonomies currently defined by this plugin.
	 * @return string reason, or '' if it can be imported.
	 */
	private static function import_entry_problem( $group, $name, $data, $own ) {
		if ( ! is_array( $data ) ) {
			return __( 'its settings are not a list.', 'simple-taxonomy-refreshed' );
		}
		if ( '' === $name || strlen( $name ) > 32 ) {
			return __( 'the name must have 1 to 32 characters.', 'simple-taxonomy-refreshed' );
		}
		if ( sanitize_title( $name ) !== $name ) {
			return __( 'the name may only contain lowercase letters, numbers, hyphens and underscores.', 'simple-taxonomy-refreshed' );
		}
		if ( isset( $data['name'] ) && (string) $data['name'] !== $name ) {
			return __( 'the name in its settings is different.', 'simple-taxonomy-refreshed' );
		}
		if ( 'taxonomies' === $group && taxonomy_exists( $name ) && ! in_array( $name, $own, true ) && ! SimpleTaxonomyRefreshed_Client::registered_by_plugin( $name ) ) {
			return __( 'WordPress or another plugin already has a taxonomy with this name.', 'simple-taxonomy-refreshed' );
		}
		if ( 'taxonomies' === $group ) {
			// The taxonomies defined now are replaced by the import, so they cannot clash.
			$conflict = SimpleTaxonomyRefreshed_Admin::rest_base_conflict( array_merge( $data, array( 'name' => $name ) ), $own );
			if ( '' !== $conflict ) {
				// translators: %s is the taxonomy's REST name (its REST Base, or its name).
				return sprintf( __( 'its REST name "%s" is already used in the REST API data of posts, so the block editor could not set its terms. Set a different REST Base.', 'simple-taxonomy-refreshed' ), $conflict );
			}
		}
		return '';
	}

	/**
	 * Normalise imported values; values out of range are changed, so the taxonomy is skipped.
	 *
	 * @since 4.0.0
	 *
	 * @param array $taxonomy sanitised taxonomy settings.
	 * @return array
	 */
	private static function import_value_ranges( $taxonomy ) {
		// Choices 0, 1 or 2 (empty, as in settings from older versions, counts as 0).
		foreach ( array( 'st_cb_type', 'st_cc_type', 'st_cc_hard' ) as $field ) {
			if ( isset( $taxonomy[ $field ] ) && ! in_array( (string) $taxonomy[ $field ], array( '', '0', '1', '2' ), true ) ) {
				$taxonomy[ $field ] = 0;
			}
		}
		// Whole numbers (may be left empty).
		foreach ( array( 'st_cc_min', 'st_cc_max', 'st_adm_depth' ) as $field ) {
			if ( isset( $taxonomy[ $field ] ) && '' !== $taxonomy[ $field ] ) {
				$taxonomy[ $field ] = (string) absint( $taxonomy[ $field ] );
			}
		}
		if ( isset( $taxonomy['st_ep_mask'] ) ) {
			$taxonomy['st_ep_mask'] = absint( $taxonomy['st_ep_mask'] );
		}
		return $taxonomy;
	}

	/**
	 * The first setting of an imported entry whose value would be stored differently.
	 *
	 * @since 4.0.0
	 *
	 * @param array $raw   settings in the file.
	 * @param array $clean settings sanitised and checked.
	 * @return string setting name, or '' if all are valid.
	 */
	private static function import_invalid_field( $raw, $clean ) {
		foreach ( $raw as $key => $value ) {
			if ( array_key_exists( $key, $clean ) && ! self::same_value( $value, $clean[ $key ] ) ) {
				return (string) $key;
			}
		}
		return '';
	}

	/**
	 * Whether two setting values are the same once stored (numbers and numeric strings match).
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $a value.
	 * @param mixed $b value.
	 * @return bool
	 */
	private static function same_value( $a, $b ) {
		if ( is_array( $a ) || is_array( $b ) ) {
			if ( ! is_array( $a ) || ! is_array( $b ) || count( $a ) !== count( $b ) ) {
				return false;
			}
			foreach ( $a as $key => $value ) {
				if ( ! array_key_exists( $key, $b ) || ! self::same_value( $value, $b[ $key ] ) ) {
					return false;
				}
			}
			return true;
		}
		if ( is_object( $a ) || is_object( $b ) ) {
			return false;
		}
		return (string) $a === (string) $b;
	}

	/**
	 * Report the taxonomies skipped and the settings ignored by an import.
	 *
	 * @since 4.0.0
	 *
	 * @param array $import result of prepare_import().
	 * @return void
	 */
	private static function import_notices( $import ) {
		if ( ! empty( $import['skipped'] ) ) {
			add_settings_error(
				'simple-taxonomy-refreshed',
				'config_skipped',
				esc_html__( 'These taxonomies in the file were not imported:', 'simple-taxonomy-refreshed' ) . '<br />' . implode( '<br />', array_map( 'esc_html', $import['skipped'] ) ),
				'warning'
			);
		}
		if ( $import['ignored'] > 0 ) {
			add_settings_error(
				'simple-taxonomy-refreshed',
				'config_ignored',
				// translators: %d is the number of settings ignored.
				sprintf( _n( '%d setting in the file is not used by the plugin and has been ignored.', '%d settings in the file are not used by the plugin and have been ignored.', $import['ignored'], 'simple-taxonomy-refreshed' ), $import['ignored'] ),
				'warning'
			);
		}
	}

	/**
	 * Build the configuration export file content.
	 *
	 * Taxonomies named in $order come first, in that order; any others follow in their
	 * stored order. Names that are not stored taxonomies are ignored.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $options stored plugin options.
	 * @param mixed $order   taxonomy names in the order wanted (from the Export screen).
	 * @return string the file content: the marker followed by the options as JSON.
	 */
	public static function build_config_export( $options, $order = array() ) {
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		if ( is_array( $order ) && ! empty( $order ) && isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
			$ntaxo = array();
			foreach ( $order as $tax ) {
				if ( is_string( $tax ) && isset( $options['taxonomies'][ $tax ] ) ) {
					$ntaxo[ $tax ] = $options['taxonomies'][ $tax ];
				}
			}
			// Taxonomies missing from the list are kept, after the listed ones.
			$options['taxonomies'] = $ntaxo + $options['taxonomies'];
		}

		return 'SIMPLETAXONOMYREFRESHED' . wp_json_encode( $options );
	}

	/**
	 * Display page to export or import the configuration.
	 */
	public static function page_config() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Export/Import Configuration', 'simple-taxonomy-refreshed' ); ?></h1>
			<p>These options allow you to export or import the entire configuration file.</p>
			<h2><?php esc_html_e( 'Export Configuration', 'simple-taxonomy-refreshed' ); ?></h2>
			<?php
			$options = get_option( OPTION_STAXO );
			// No export if no configuration.
			if ( false === $options || empty( $options ) ) {
				echo '<p>' . esc_html__( 'No configuration exists to export.', 'simple-taxonomy-refreshed' ) . '</p>';
			} else {
				?>
				<form action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::CONFIG_SLUG ) ); ?>" method="post">
				<?php self::process_taxos( $options ); ?>
				<p class="submit">
					<input type="hidden" name="action" value="<?php echo esc_html( self::EXP_FILE_SLUG ); ?>" />
					<?php wp_nonce_field( self::EXP_FILE_SLUG ); ?>
					<input type="submit" name="<?php echo esc_html( self::EXP_FILE_SLUG ); ?>" id="<?php echo esc_html( self::EXP_FILE_SLUG ); ?>" value="<?php esc_html_e( 'Export config file', 'simple-taxonomy-refreshed' ); ?>" class="button-primary" />
				</p>
				</form>
				<?php
			}
			?>
			<h2><?php esc_html_e( 'Import Configuration', 'simple-taxonomy-refreshed' ); ?></h2>
			<p>&nbsp;</p>
			<a class="button" href="#" id="toggle-import_form"><?php esc_html_e( 'Import config file', 'simple-taxonomy-refreshed' ); ?></a>
			<script type="text/javascript">
				jQuery( "#toggle-import_form" ).click(function(event) {
					event.preventDefault();
					jQuery( '#import_form' ).removeClass('hide-if-js');
				});
			</script>
			<div id="import_form" class="hide-if-js">
				<form action="<?php echo esc_url( 'admin.php?page=' . self::CONFIG_SLUG ); ?>" method="post" enctype="multipart/form-data">
					<p>
						<label><?php esc_html_e( 'Config file', 'simple-taxonomy-refreshed' ); ?></label>
						<input type="file" name="config_file" />
					</p>
					<p class="submit">
						<?php wp_nonce_field( esc_html( self::IMP_FILE_SLUG ) ); ?>
						<input class="button-primary" type="submit" name="<?php echo esc_html( self::IMP_FILE_SLUG ); ?>" value="<?php esc_html_e( 'I want to import a config from a previous backup. This action will REPLACE current configuration', 'simple-taxonomy-refreshed' ); ?>" />
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Adds custom taxonomies sort list.
	 *
	 * @since 2.3
	 * @param array $options taxonomy option.
	 * @return void
	 */
	private static function process_taxos( $options ) {
		if ( ! isset( $options['taxonomies'] ) ) {
			echo '<p>' . esc_html__( 'No custom taxonomies to export.', 'simple-taxonomy-refreshed' ) . '</p>';
			return;
		}
		if ( 1 === count( $options['taxonomies'] ) ) {
			echo '<p>' . esc_html__( 'Only one custom taxonomy defined. No reorder possible on export.', 'simple-taxonomy-refreshed' ) . '</p>';
			return;
		}
		echo '<p>' . esc_html__( 'You can reorder the custom taxonomies defined on export.', 'simple-taxonomy-refreshed' ) . '</p>';
		echo '<p>' . esc_html__( 'On re-importing, they will be displayed in the order of the exported file.', 'simple-taxonomy-refreshed' ) . '</p>';
		echo '<p>' . esc_html__( 'Drag the entries to create the required order.', 'simple-taxonomy-refreshed' ) . '</p>';
		echo '<div id="taxo_list_box" class="meta-box-sortabless">';
		echo '<div class="postbox">';
		echo '<div class="inside"><ul id="taxo_list">';
		foreach ( $options['taxonomies'] as $li ) {
			echo '<li class="sort-li" tabindex="0">' . esc_html( $li['name'] ) . '</li>';
		}
		echo '</ul></div></div></div>';
		?>
		<input type="hidden" name="taxo_list_arr" id="taxo_list_arr" value="" />
		<script type="text/javascript">
		function setSortable() {
			jQuery( "#taxo_list" ).sortable({
				placeholder : "ui-sortable-placeholder",
				update : function(event, ui) {
					set_array = new Array();
					var set = document.getElementById("taxo_list").getElementsByClassName("sort-li");
					for (const elt of set)  {
						set_array.push(elt.innerText);
					};
					document.getElementById( "taxo_list_arr" ).value = JSON.stringify(set_array);
				}
			});
		}

		document.addEventListener('DOMContentLoaded', function(evt) {
			setSortable();
		});
		</script>
		<?php
	}

	/**
	 * Adds js libraries for sorting.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function add_js_libs() {
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
		wp_enqueue_script( 'jquery-ui-sortable', '', array( 'jquery-ui-core', 'jquery' ), false, true );

		// enqueue admin js/css.
		global $stra;
		$stra->enqueue_admin_libs();
	}
	/**
	 * Adds help tabs to help tab API.
	 *
	 * @since 1.2
	 * @return void
	 */
	public static function add_help_tab() {
		$screen = get_current_screen();

		// parent key is the id of the current screen
		// child key is the title of the tab
		// value is the help text (as HTML).
		$help = array(
			__( 'Overview', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'This tool allows you to exort or import the entire configuration file.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'The files are in JSON format.', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Export config file', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'On clicking the Export button a JSON file is prepared for downloading via the broswer.', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Import config file', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'On clicking the Import button, you are invited to identify the file to load with a new button that will load the file. It contains a warning.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'The file is checked for its basic content before loading.', 'simple-taxonomy-refreshed' ) . '</p>',
		);

		// loop through each tab in the help array and add.
		foreach ( $help as $title => $content ) {
			$screen->add_help_tab(
				array(
					'title'   => $title,
					'id'      => str_replace( ' ', '_', $title ),
					'content' => $content,
				)
			);
		}

		// add help sidebar.
		SimpleTaxonomyRefreshed_Admin::add_help_sidebar();
	}
}
