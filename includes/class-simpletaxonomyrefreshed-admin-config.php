<?php
/**
 * Simple Taxonomy Admin Configuration Export/Import class file.
 *
 * @package simple-taxonomy-refreshed
 * @author Neil James
 */

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
	 * @var int
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

			// remove any spurious buffered output.
			while ( ob_get_level() ) {
				ob_end_clean();
			}

			// is a reordering of taxionomies wanted?
			$options = get_option( OPTION_STAXO );
			if ( isset( $_POST['taxo_list_arr'] ) && ! empty( $_POST['taxo_list_arr'] ) ) {
				$taxos = json_decode( sanitize_text_field( wp_unslash( $_POST['taxo_list_arr'] ) ), true );
				$ntaxo = array();
				foreach ( $taxos as $tax ) {
					$ntaxo[ $tax ] = $options['taxonomies'][ $tax ];
				}
				$options['taxonomies'] = $ntaxo;
			}

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
			die( 'SIMPLETAXONOMYREFRESHED' . wp_json_encode( $options ) );
		}

		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( isset( $_POST[ self::IMP_FILE_SLUG ] ) && isset( $_FILES['config_file'] ) ) {
			check_admin_referer( self::IMP_FILE_SLUG );

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
					// looks as though it is OK so load it.
					// clear caches (need to do first as values could be different).
					$options = get_option( OPTION_STAXO );
					if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
						foreach ( (array) $options['taxonomies'] as $taxonomy => $tax_data ) {
							wp_cache_delete( 'staxo_sel_' . $taxonomy );
						}
						wp_cache_delete( 'staxo_own_taxos' );
					}
					wp_cache_delete( 'staxo_taxonomies' );
					wp_cache_delete( 'staxo_orderings' );
					update_option( OPTION_STAXO, $config_file );
					SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache();
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'OK. Configuration is restored.', 'simple-taxonomy-refreshed' ), 'updated' );
					// Change of file may provoke a change of rewrite rules, so trigger it via transient data.
					set_transient( 'simple_taxonomy_refreshed_rewrite', true, 0 );
				} else {
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'This is really a config file for Simple Taxonomy ? Probably corrupt :(', 'simple-taxonomy-refreshed' ), 'error' );
				}
			}
		}
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
