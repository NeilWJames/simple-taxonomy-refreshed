<?php
/**
 * Simple Taxonomy Admin class file.
 *
 * @package simple-taxonomy-refreshed
 * @author Neil James
 */

/**
 * Simple Taxonomy Admin class.
 *
 * Manages the Taxonomy Screen and writing of the options.
 *
 * @author Neil James
 */
class SimpleTaxonomyRefreshed_Admin {
	const ADMIN_SLUG = 'staxo_settings';
	const ADD_SLUG   = 'staxo_settings&action=add';

	/**
	 * Admin URL variable.
	 *
	 * @var string
	 */
	private $admin_url = '';

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
			self::$instance = new SimpleTaxonomyRefreshed_Admin();
		}
		return self::$instance;
	}

	/**
	 * Use of block editor.
	 *
	 * @var bool $use_block_editor.
	 */
	public static $use_block_editor = null;

	/**
	 * Variable to indicate if placeholder enqueued.
	 *
	 * @var boolean
	 */
	private static $placeholder = false;

	/**
	 * Call to enqueue the placeholder.
	 *
	 * @return void
	 */
	public function enqueue_placeholder() {
		if ( false === self::$placeholder ) {
			// just a placeholder so version irrelevant.
			// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_script(
				'staxo_placeholder',
				plugins_url( '/js/placeholder.js', __DIR__ ),
				array( 'wp-data' ),
				null,
				array( 'strategy' => 'defer' ),
			);
			// phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion
			self::$placeholder = true;
		}

		$screen = get_current_screen();

		if ( 'toplevel_page_staxo_settings' === $screen->id ) {
			// Add admin js/css.
			self::enqueue_admin_libs();
			return;
		}
		// Quick-edit option may be used.
		if ( 'edit' === $screen->base ) {
			// search in taxonomies as may have been changed from parameters.
			$taxos = get_object_taxonomies( $screen->post_type, 'objects' );
			foreach ( $taxos as $taxonomy ) {
				if ( $taxonomy->show_in_quick_edit ) {
					// Add admin js/css.
					self::enqueue_admin_libs();
					// Only once.
					return;
				}
			}
		}
	}

	/**
	 * Call to enqueue the admin js/css.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	public function enqueue_admin_libs() {
		// enqueue on staxo-settings page only.
		$dir      = dirname( __DIR__ );
		$suffix   = ( WP_DEBUG ) ? '.dev' : '';
		$index_js = 'js/staxo-admin' . $suffix . '.js';
		wp_enqueue_script(
			'staxo_admin',
			plugins_url( $index_js, __DIR__ ),
			array(),
			filemtime( "$dir/$index_js" ),
			array( 'strategy' => 'defer' ),
		);

		$index_css = 'css/staxo-admin-style' . $suffix . '.css';
		wp_enqueue_style(
			'staxo-admin-style',
			plugins_url( $index_css, __DIR__ ),
			array(),
			filemtime( "$dir/$index_css" ),
		);
	}

	/**
	 * Variable to indicate if client js enqueued.
	 *
	 * @var boolean
	 */
	private static $enqueue_client = false;

	/**
	 * Call to enqueue the client js.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	private static function enqueue_client_libs() {
		if ( self::$enqueue_client ) {
			return;
		}
		self::$enqueue_client = true;
		// enqueue on client pages only.
		$dir      = dirname( __DIR__ );
		$suffix   = ( WP_DEBUG ) ? '.dev' : '';
		$index_js = 'js/staxo-client' . $suffix . '.js';
		wp_enqueue_script(
			'staxo_client',
			plugins_url( $index_js, __DIR__ ),
			array( 'wp-block-editor', 'wp-blocks', 'wp-core-data', 'wp-data', 'wp-dom', 'wp-dom-ready', 'wp-edit-post', 'wp-editor' ),
			filemtime( "$dir/$index_js" ),
			false,
		);
	}

	/**
	 * Protected Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	final protected function __construct() {
		// Register hooks.
		add_action( 'activity_box_end', array( __CLASS__, 'activity_box_end' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		// Queue up JS.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_placeholder' ) );

		// check existing posts outside limits.
		add_action( 'all_admin_notices', array( __CLASS__, 'check_posts_outside_limits' ) );

		// called if block editor to render screen.
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'block_editor_active' ) );

		// help text.
		add_action( 'load-toplevel_page_' . self::ADMIN_SLUG, array( __CLASS__, 'add_help_tab' ) );
	}

	/**
	 * Add custom taxo on dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function activity_box_end() {
		$options = get_option( OPTION_STAXO );
		if ( ( ! isset( $options['taxonomies'] ) ) || ! is_array( $options['taxonomies'] ) ) {
			return;
		}
		?>
		<div id="dashboard-custom-taxo">
			<table>
				<tbody>
					<?php
					foreach ( (array) $options['taxonomies'] as $taxonomy ) {
						$taxo = get_taxonomy( $taxonomy['name'] );
						if ( false === $taxo || is_wp_error( $taxo ) ) {
							continue;
						}
						?>
						<tr>
							<td class="first b b-<?php echo esc_attr( $taxo->name ); ?>"><a href="edit-tags.php?taxonomy=<?php echo esc_attr( $taxo->name ); ?>"><?php echo esc_html( wp_count_terms( $taxo->name ) ); ?></a></td>
							<td class="t <?php echo esc_attr( $taxo->name ); ?>"><a href="edit-tags.php?taxonomy=<?php echo esc_attr( $taxo->name ); ?>"><?php echo esc_attr( $taxo->labels->name ); ?></a></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<script type="text/javascript">
			jQuery(".table_content table tbody").append( jQuery("#dashboard-custom-taxo table tbody").html() );
			jQuery("#dashboard-custom-taxo").remove();
		</script>
		<?php
	}
	/**
	 * Add settings init page
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public static function admin_init() {
		self::check_merge_taxonomy();
		self::check_delete_taxonomy();
		self::check_export_taxonomy();

		register_setting( 'simple-taxonomy-refreshed', 'settings_updated' );

		global $strc;
		$cntl_post_types = $strc::refresh_term_cntl_cache( false );
		// if terms control wanted, invoke the code.
		if ( isset( $cntl_post_types ) && ! empty( $cntl_post_types ) ) {
			// filters the post to implement the taxonomy controls.
			add_filter( 'wp_insert_post_empty_content', array( __CLASS__, 'check_taxonomy_value_set' ), 10, 2 );

			// make sure that there is no taxonomy error to report.
			add_action( 'admin_notices', array( __CLASS__, 'admin_error_check' ), 1 );
		}
	}

	/**
	 * Add settings menu page
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public static function admin_menu() {
		add_menu_page( __( 'Custom Taxonomies', 'simple-taxonomy-refreshed' ), __( 'Taxonomies', 'simple-taxonomy-refreshed' ), 'manage_options', self::ADMIN_SLUG, array( __CLASS__, 'page_manage' ), 'dashicons-category', 40 );
		add_submenu_page( self::ADMIN_SLUG, __( 'All Taxonomies', 'simple-taxonomy-refreshed' ), __( 'All Taxonomies', 'simple-taxonomy-refreshed' ), 'manage_options', self::ADMIN_SLUG, array( __CLASS__, 'page_manage' ) );
		add_submenu_page( self::ADMIN_SLUG, __( 'Add Taxonomy', 'simple-taxonomy-refreshed' ), __( 'Add Taxonomy', 'simple-taxonomy-refreshed' ), 'manage_options', self::ADD_SLUG, array( __CLASS__, 'page_form' ) );
	}

	/**
	 * Adds help tabs to help tab API.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public static function add_help_tab() {
		$screen = get_current_screen();

		// On settings page.
		if ( 'toplevel_page_' . self::ADMIN_SLUG !== $screen->id ) {
			return;
		}

		// parent key is the id of the current screen
		// child key is the title of the tab
		// value is the help text (as HTML).
		$help = array(
			__( 'Taxonomy List', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'This displays all the Taxonomies that are managed by this plugin.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'It shows two blocks: Custom and External.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Custom Taxonomies are the taxonomies that are defined and created using this plugin.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'It shows all properties of the taxonomy - and allows you to extract the code that defines the taxonomy entities.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You can add a new taxonomy or modify an existing an existing taxonomy with a new window.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'External Taxonomies are taxonomies that have been created by standard WordPress itself or other plugins that you have installed.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Whilst you are unable to change their definition with this plugin, you can add the additional functionality with this plugin to these external taxonomies.', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Taxonomy Definition', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'This applies only to Custom Taxonomies.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'There are very many parameters to control the operation of the Taxonomy and seven tabs have been provided to enter them all. Since a major objective of the plugin is to avoid the user having to write code, all these parameters have been made available.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'As these parameters are for underlying WordPress functionality, you should read the WordPress documentation to understand their purpose and action.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You need to enter the Name (slug), whether it is Hierarchical or not and the post type(s) it will be linked to on the Main Options tab and the Name (label) on the Labels tab. Most of the others can be left as their default value - expect possibly the labels.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'WP 5.5 brings the possibility to add a default term for all objects defined from the taxonomy. This may be entered on the Other tab. The slug and description can also be entered here and used to create the term.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'By default the terms do not appear with the post. The options at the bottom of the Main Options screen allow you to output the attached terms with the post content and/or excerpt information.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'For greater control on positioning, you can also display these terms with a shortcode <code>staxo_post_terms</code> or plugin block <code>post_terms</code>.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'When done, simply click <code>Add Taxonomy</code> or <code>Update Taxonomy</code> at the bottom to save your changes', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'WPGraphQL', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'Extra taxonomy-related functionality.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'These parameters are for sites that have implemented WPGraphQL; and are not relevant if the plugin is not installed.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'They are provided in the spirit of supporting no-coding. There is no requirement for this plugin.', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Admin List Filter', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'Extra taxonomy-related functionality.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'The admin screen for post types can optionally have a dropdown filter to select posts using the taxonomy.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'This tab allows the taxonomy parameters to be entered appropriately for your use.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'This provides appropriate subset of parmeters the standard WordPress functionality (wp_dropdown_categories).', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Term Count', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'Extra taxonomy-related functionality.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Standard WordPress functionality provides a count of the posts using a term on the Taxonomy Terms page - and this count is for only Published posts.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You may want this count to include posts with other statuses. You can set this behaviour on this tab.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'If you have additional non-standard post_statuses that you wish to be included, this can be done, but it will require coding and use of the filter \'staxo_term_count_statuses\'.', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Term Control', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'Extra taxonomy-related functionality.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Standard WordPress functionality provides no limits on the number of terms that can be attached to a post.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'This functionality provides this control. Using this tab, upper and lower bounds may be set for either Published posts or all posts.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'The tests will be applied at save post time (soft);  or also when the terms are added or removed (hard).', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Thus a user who can edit a post may not be able to add or remove terms but can be notified of the issue and make other updates.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'By default term controls will be applied to all corresponding post types, but just a sub-set of post types can be chosen to be controlled.', 'simple-taxonomy-refreshed' ) . '</p>',
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

		// add sidebar.
		self::add_help_sidebar();
	}

	/**
	 * Adds help sidebar to help tab API.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function add_help_sidebar() {
		$screen = get_current_screen();

		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'simple-taxonomy-refreshed' ) . '</strong></p>' .
			'<p><a href="https://github.com/NeilWJames/simple-taxonomy-refreshed/" target="_blank">' . __( 'Github Project page', 'simple-taxonomy-refreshed' ) . '</a></p>' .
			'<p><a href="https://wordpress.org/support/plugin/simple-taxonomy-refreshed/" target="_blank">' . __( 'WP Support Forum', 'simple-taxonomy-refreshed' ) . '</a></p>'
		);
	}

	/**
	 * Output any error when checking that taxonomy term bounds missed..
	 *
	 * Invoked in response to a requery process (so no need to check how created here).
	 */
	public static function admin_error_check() {
		if ( ( ! isset( $_GET['staxo_terms'] ) ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['staxo_terms'] ) ), 'terms' ) ) {
			// not valid.
			return;
		}

		if ( array_key_exists( 'staxo_error', $_GET ) && array_key_exists( 'staxo_tax', $_GET ) ) {
			if ( isset( $_GET['message'] ) ) {
				// This will over-ride any message.
				unset( $_GET['message'] );
			}

			$taxonomy = sanitize_text_field( wp_unslash( $_GET['staxo_tax'] ) );
			$label    = get_taxonomy( $taxonomy )->labels->name;

			?>
			<div><p>&nbsp;</p></div>
			<div class="error">
				<p>
				<?php
				switch ( $_GET['staxo_error'] ) {
					case 'min':
						// translators: %s is the taxonomy label name.
						echo esc_html( sprintf( __( 'Your post needs to have more terms for the taxonomy - %s entered.', 'simple-taxonomy-refreshed' ), $label ) );
						break;
					case 'max':
						// translators: %s is the taxonomy label name.
						echo esc_html( sprintf( __( 'Your post needs to have less terms for taxonomy - %s.', 'simple-taxonomy-refreshed' ), $label ) );
						break;
					case 'minmax':
						// translators: %s is the taxonomy label name.
						echo esc_html( sprintf( __( 'Your post needs to have more terms for the taxonomy - %s entered.', 'simple-taxonomy-refreshed' ), $label ) );
						echo '</p><p>';
						// translators: %s is the taxonomy label name.
						echo esc_html( sprintf( __( 'Your post needs to have less terms for taxonomy - %s.', 'simple-taxonomy-refreshed' ), $label ) );
						break;
					default:
						null;
				}
				?>
				</p>
				<p><?php esc_html_e( 'Your update has been cancelled and stored data remains unchanged.', 'simple-taxonomy-refreshed' ); ?></p>
				<p><?php esc_html_e( 'Your browser may hold an updated version, but it is currently invalid.', 'simple-taxonomy-refreshed' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Allow to display only form.
	 *
	 * @param array   $taxonomy  taxonomy name.
	 * @param boolean $custom    taxonomy is custom.
	 * @return void
	 */
	public static function page_form( $taxonomy = null, $custom = true ) {
		if ( isset( $taxonomy ) ) {
			if ( $custom ) {
				// translators: %s is the taxonomy name.
				$title = sprintf( __( 'Custom Taxonomy : %s', 'simple-taxonomy-refreshed' ), stripslashes( $taxonomy['labels']['name'] ) );
			} else {
				// translators: %s is the taxonomy name.
				$title = sprintf( __( 'External Taxonomy : %s', 'simple-taxonomy-refreshed' ), stripslashes( $taxonomy['labels']['name'] ) );
			}
		} else {
			$title = __( 'Add Custom Taxonomy', 'simple-taxonomy-refreshed' );
		}
		?>
		<div class="wrap">
			<h1 class="wp-heading-_"><?php echo esc_html( $title ); ?></h1>

			<hr class="wp-header-end">

			<h2 class="screen-reader-text"><?php esc_html_e( 'Add/Modify taxonomy', 'simple-taxonomy-refreshed' ); ?></h2>

			<div class="form-wrap">
				<?php self::form_merge_custom_type( $taxonomy, $custom ); ?>
			</div>
		</div>
		<?php
	}


	/**
	 * Helper function to display a True/False option on admin
	 *
	 * @param array  $taxonomy taxonomy array.
	 * @param string $name     target option name.
	 * @param string $label    display label.
	 * @param string $descr    sanitzed description field.
	 * @return void
	 */
	private static function option_yes_no( &$taxonomy, $name, $label, $descr ) {
		// Expect to sanitize the data before calling.
		// phpcs:disable  WordPress.Security.EscapeOutput
		?>
		<tr>
			<th scope="row"><label for="<?php echo $name; ?>"><?php echo $label; ?></label></th>
			<td>
				<select name="<?php echo $name; ?>" id="<?php echo $name; ?>">
					<?php
					foreach ( self::get_true_false() as $type_key => $type_name ) {
						echo '<option ' . selected( (int) $taxonomy[ $name ], $type_key, false ) . ' value="' . esc_attr( $type_key ) . '">' . esc_html( $type_name ) . '</option>' . "\n";
					}
					?>
				</select>
				<span class="description"><?php echo $descr; ?></span>
			</td>
		</tr>
		<?php
		// phpcs:enable  WordPress.Security.EscapeOutput
	}


	/**
	 * Helper function to display a text option on admin
	 *
	 * @param array  $taxonomy taxonomy array.
	 * @param string $name     target option name.
	 * @param string $label    display label.
	 * @param string $descr    sanitized description field.
	 * @return void
	 */
	private static function option_text( &$taxonomy, $name, $label, $descr ) {
		// Sanitize the data before calling.
		// phpcs:disable  WordPress.Security.EscapeOutput
		?>
		<tr>
			<th scope="row"><label for="<?php echo $name; ?>"><?php echo $label; ?></label></th>
			<td>
				<input name="<?php echo $name; ?>" type="text" id="<?php echo $name; ?>" value="<?php echo esc_attr( $taxonomy[ $name ] ); ?>" class="regular-text" />
				<?php
				if ( '' !== $descr ) {
					echo '<br /><span class="description">' . $descr . '</span>';
				}
				?>
			</td>
		</tr>
		<?php
		// phpcs:enable  WordPress.Security.EscapeOutput
	}


	/**
	 * Helper function to display a Label option on admin
	 *
	 * @param array  $taxonomy taxonomy array.
	 * @param string $name     target option name.
	 * @param string $label    display label.
	 * @param string $descr    sanitized description field.
	 * @return void
	 */
	private static function option_label( &$taxonomy, $name, $label, $descr ) {
		// Expect to sanitize the data before calling.
		// phpcs:disable  WordPress.Security.EscapeOutput
		?>
		<tr>
			<th scope="row"><label for="labels-<?php echo $name; ?>"><?php echo $label; ?></label></th>
			<td>
				<input name="labels[<?php echo $name; ?>]" type="text" id="labels-<?php echo $name; ?>" value="<?php echo esc_attr( $taxonomy['labels'][ $name ] ); ?>" class="regular-text" />
				<?php
				if ( '' !== $descr ) {
					echo '<br /><span class="description">' . $descr . '</span>';
				}
				?>
			</td>
		</tr>
		<?php
		// phpcs:enable  WordPress.Security.EscapeOutput
	}

	/**
	 * Helper function to display a Capability option on admin
	 *
	 * @param array  $taxonomy taxonomy array.
	 * @param string $name     target option name.
	 * @param string $label    display label.
	 * @param string $descr    sanitized description field.
	 * @return void
	 */
	private static function option_cap( &$taxonomy, $name, $label, $descr ) {
		// Sanitize the data before calling.
		// phpcs:disable  WordPress.Security.EscapeOutput
		?>
		<tr>
			<th scope="row"><label for="<?php echo $name; ?>"><?php echo $label; ?></label></th>
			<td>
				<input name="capabilities[<?php echo $name; ?>]" type="text" id="<?php echo $name; ?>" value="<?php echo esc_attr( $taxonomy['capabilities'][ $name ] ); ?>" class="regular-text" />
				<?php
				if ( '' !== $descr ) {
					echo '<br /><span class="description">' . $descr . '</span>';
				}
				?>
			</td>
		</tr>
		<?php
		// phpcs:enable  WordPress.Security.EscapeOutput
	}


	/**
	 * Helper function to display the post status checkboxes.
	 *
	 * @param string $status_name  post_status parameter name.
	 * @param bool   $status_sel   is status selected.
	 * @param string $status_label label of the status.
	 * @return void
	 */
	private static function option_check_status( $status_name, $status_sel, $status_label ) {
		echo '<label class="inline">';
		echo '<input type="checkbox" id="' . esc_attr( $status_name ) . '" name="' . esc_attr( $status_name );
		echo '" role="checkbox" aria-checked="' . ( $status_sel ? 'true' : 'false' ) . '" tabindex="0" ' . checked( true, $status_sel, false );
		echo ' onclick="ariaChk(event)" />';
		echo esc_html( $status_label ) . '</label><br/>';
	}


	/**
	 * Display options on admin
	 *
	 * @return void
	 */
	public static function page_manage() {
		// Admin URL.
		$admin_url = admin_url( 'admin.php?page=' . self::ADMIN_SLUG );

		// Get current options.
		$current_options = get_option( OPTION_STAXO );
		// Check get for message.
		// phpcs:disable  WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['message'] ) && isset( $_GET['staxo'] ) ) {
			$staxo = sanitize_text_field( wp_unslash( $_GET['staxo'] ) );
			switch ( $_GET['message'] ) { // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
				case 'flush-deleted':
					// translators: %1$s is the taxonomy slug name.
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', sprintf( __( 'Taxonomy "%1$s" and relations deleted successfully !', 'simple-taxonomy-refreshed' ), $staxo ), 'updated' );
					break;
				case 'deleted':
					// translators: %1$s is the taxonomy slug name.
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', sprintf( __( 'Taxonomy "%1$s" deleted successfully !', 'simple-taxonomy-refreshed' ), $staxo ), 'updated' );
					break;
				case 'added':
					// translators: %1$s is the taxonomy slug name.
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', sprintf( __( 'Taxonomy "%1$s" added successfully !', 'simple-taxonomy-refreshed' ), $staxo ), 'updated' );
					break;
				case 'updated':
					// translators: %1$s is the taxonomy slug name.
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', sprintf( __( 'Taxonomy "%1$s" updated successfully !', 'simple-taxonomy-refreshed' ), $staxo ), 'updated' );
					break;
			}
		}

		// Display message.
		settings_errors( 'simple-taxonomy-refreshed' );

		// edit custom taxonomy.
		if ( isset( $_GET['action'] ) && isset( $_GET['taxonomy_name'] ) && 'edit' === $_GET['action'] && isset( $current_options['taxonomies'][ $_GET['taxonomy_name'] ] ) ) {
			self::page_form( $current_options['taxonomies'][ sanitize_text_field( wp_unslash( $_GET['taxonomy_name'] ) ) ] );  // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
			return;
		}

		// add option invoked.
		if ( isset( $_GET['action'] ) && 'add' === $_GET['action'] ) {
			self::page_form();
			return;
		}

		// edit other taxonomy.
		if ( isset( $_GET['action'] ) && isset( $_GET['taxonomy_name'] ) && 'edit' === $_GET['action'] ) {
			$tax_name = sanitize_text_field( wp_unslash( $_GET['taxonomy_name'] ) ); // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
			// Get existing options if exist.
			$options = get_option( OPTION_STAXO );
			if ( isset( $options['externals'] ) && is_array( $options['externals'] && array_key_exists( $tax_name, $options['externals'] ) ) ) {
				$taxonomy = $options['externals'][ $tax_name ];
			} else {
				// set defaults.
				$taxonomy = array(
					'name'               => $tax_name,
					'st_show_in_graphql' => 0,
					'st_graphql_single'  => '',
					'st_graphql_plural'  => '',
					'st_adm_types'       => array(),
					'st_adm_hier'        => 0,
					'st_adm_depth'       => 0,
					'st_adm_count'       => 0,
					'st_adm_h_e'         => 0,
					'st_adm_h_i_e'       => 0,
					'st_cb_type'         => 0,
					'st_cb_pub'          => 0,
					'st_cb_fut'          => 0,
					'st_cb_dft'          => 0,
					'st_cb_pnd'          => 0,
					'st_cb_prv'          => 0,
					'st_cb_tsh'          => 0,
					'st_cc_type'         => 0,
					'st_cc_types'        => array(),
					'st_cc_hard'         => 0,
					'st_cc_umin'         => 0,
					'st_cc_umax'         => 0,
					'st_cc_min'          => 0,
					'st_cc_max'          => 0,
					'st_feed'            => 0,
				);
				// add data from taxonomy . Not stored.
				$tax_obj                              = get_taxonomy( $tax_name );
				$taxonomy['labels']                   = (array) $tax_obj->labels;
				$taxonomy['objects']                  = (array) $tax_obj->object_type;
				$taxonomy['hierarchical']             = $tax_obj->hierarchical;
				$taxonomy['st_update_count_callback'] = $tax_obj->update_count_callback;
			}
			self::page_form( $taxonomy, false );
			return;
		}
		// phpcs:enable  WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Custom Taxonomies', 'simple-taxonomy-refreshed' ); ?></h1>

			<a href="<?php echo esc_url( $admin_url ); ?>&action=add" class="page-title-action"><?php esc_html_e( 'Add New', 'simple-taxonomy-refreshed' ); ?></a>

			<hr class="wp-header-end">

			<h2 class="screen-reader-text"><?php esc_html_e( 'Custom taxonomy list', 'simple-taxonomy-refreshed' ); ?></h2>

			<div class="message updated">
				<p>
				<?php
				// phpcs:ignore  WordPress.Security.EscapeOutput
				echo wp_kses( __( '<strong>Warning :</strong> Flush & Delete a taxonomy will also delete all terms of the taxonomy and all object relations.', 'simple-taxonomy-refreshed' ), array( 'strong' ) );
				?>
				</p>
			</div>

			<div id="col-container-custom">
				<table class="wp-list-table widefat tag fixed striped table-view-list" cellspacing="0">
					<thead>
						<tr>
							<th scope="col" id="labell" class="manage-column column-name"><?php esc_html_e( 'Label', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" id="nameh"  class="manage-column column-slug"><?php esc_html_e( 'Name', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" id="labelt" class="manage-column column-name"><?php esc_html_e( 'Post Types', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" id="labelh" class="manage-column column-name"><?php esc_html_e( 'Hierarchical', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" id="labelr" class="manage-column column-name"><?php esc_html_e( 'Rewrite', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" id="labelp" class="manage-column column-name"><?php esc_html_e( 'Public', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" id="labelb" class="manage-column column-name"><?php esc_html_e( 'Block Editor', 'simple-taxonomy-refreshed' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Label', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" class="manage-column column-slug"><?php esc_html_e( 'Name', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Post Types', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Hierarchical', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Rewrite', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Public', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Block Editor', 'simple-taxonomy-refreshed' ); ?></th>
						</tr>
					</tfoot>

					<tbody id="the-list-custom" class="list:taxonomies">
						<?php
						if ( false === $current_options || empty( $current_options['taxonomies'] ) ) {
							echo '<tr><td colspan="3">' . esc_html__( 'No custom taxonomy.', 'simple-taxonomy-refreshed' ) . '</td></tr>';
						} else {
							$class = 'alternate';
							$i     = 0;
							foreach ( (array) $current_options['taxonomies'] as $_t_name => $_t ) {
								++$i;
								$class = ( 'alternate' === $class ) ? '' : 'alternate';
								$lname = $_t['labels']['name'];
								// phpcs:disable  WordPress.Security.EscapeOutput
								// translators: %s is the taxonomy name.
								$edit_msg = esc_html( sprintf( __( "Edit the taxonomy '%s'", 'simple-taxonomy-refreshed' ), $lname ) );
								// translators: %s is the taxonomy name.
								$del_msg = esc_js( sprintf( __( "You are about to delete this taxonomy '%s'\n  'Cancel' to stop, 'OK' to delete.", 'simple-taxonomy-refreshed' ), $lname ) );
								// translators: %s is the taxonomy name.
								$dfl_msg = esc_js( sprintf( __( "You are about to delete and flush this taxonomy '%s' and all relations.\n  'Cancel' to stop, 'OK' to delete.", 'simple-taxonomy-refreshed' ), $lname ) );
								// phpcs:enable  WordPress.Security.EscapeOutput
								?>
								<tr id="taxonomy-<?php echo esc_attr( $i ); ?>" class="<?php esc_attr( $class ); ?>">
									<td class="name column-name has-row-actions column-primary page-title">
										<strong><a class="row-title" href="<?php echo esc_url( $admin_url ); ?>&amp;action=edit&amp;taxonomy_name=<?php echo esc_attr( $_t_name ); ?>" title="<?php esc_attr( $edit_msg ); ?>" aria-label="<?php esc_html_e( 'Modify', 'simple-taxonomy-refreshed' ); ?> - <?php echo esc_html( stripslashes( $lname ) ); ?>"><?php echo esc_html( stripslashes( $lname ) ); ?></a></strong>
										<br />
										<div class="row-actions">
											<span class="edit"><a href="<?php echo esc_url( $admin_url ); ?>&amp;action=edit&amp;taxonomy_name=<?php echo esc_attr( $_t_name ); ?>" aria-label="<?php esc_html_e( 'Modify', 'simple-taxonomy-refreshed' ); ?> - <?php echo esc_html( stripslashes( $lname ) ); ?>"><?php esc_html_e( 'Modify', 'simple-taxonomy-refreshed' ); ?></a> | </span>
											<span class="export"><a class="export_php-taxonomy" href="<?php echo esc_url( wp_nonce_url( esc_url( $admin_url ) . '&amp;action=export_php&amp;taxonomy_name=' . esc_attr( $_t_name ), 'staxo_export_php-' . $_t_name ) ); ?>" aria-label="<?php esc_html_e( 'Export PHP', 'simple-taxonomy-refreshed' ); ?> - <?php echo esc_html( stripslashes( $lname ) ); ?>"><?php esc_html_e( 'Export PHP', 'simple-taxonomy-refreshed' ); ?></a> | </span>
											<span class="delete"><a class="delete-taxonomy" href="<?php echo esc_url( wp_nonce_url( esc_url( $admin_url ) . '&amp;action=delete&amp;taxonomy_name=' . $_t_name, 'staxo_delete_' . esc_attr( $_t_name ) ) ); ?>" onclick="if ( confirm( '<?php echo esc_html( $del_msg ); ?>' ) ) { return true;}return false;" aria-label="<?php esc_html_e( 'Delete', 'simple-taxonomy-refreshed' ); ?> - <?php echo esc_html( stripslashes( $lname ) ); ?>"><?php esc_html_e( 'Delete', 'simple-taxonomy-refreshed' ); ?></a> | </span>
											<span class="delete"><a class="flush-delete-taxonomy" href="<?php echo esc_url( wp_nonce_url( esc_url( $admin_url ) . '&amp;action=flush-delete&amp;taxonomy_name=' . $_t_name, 'staxo_flush_delete-' . esc_attr( $_t_name ) ) ); ?>" onclick="if ( confirm( '<?php echo esc_html( $dfl_msg ); ?>' ) ) { return true;}return false;" aria-label="<?php esc_html_e( 'Flush & Delete', 'simple-taxonomy-refreshed' ); ?> - <?php echo esc_html( stripslashes( $lname ) ); ?>"><?php esc_html_e( 'Flush & Delete', 'simple-taxonomy-refreshed' ); ?></a></span>
										</div>
									</td>
									<td><?php echo esc_html( $_t['name'] ); ?></td>
									<td>
										<?php
										if ( is_array( $_t['objects'] ) && ! empty( $_t['objects'] ) ) {
											foreach ( $_t['objects'] as $k => $post_type ) {
												$cpt = get_post_type_object( $post_type );
												if ( null === $cpt ) {
													unset( $_t['objects'][ $k ] );
												} else {
													$_t['objects'][ $k ] = $cpt->labels->name;
												}
											}
											echo esc_html( implode( ', ', (array) $_t['objects'] ) );
										} else {
											echo '-';
										}
										?>
									</td>
									<td><?php echo esc_html( self::get_true_false( $_t['hierarchical'], 0 ) ); ?></td>
									<td><?php echo esc_html( self::get_true_false( $_t['rewrite'] ) ); ?></td>
									<td><?php echo esc_html( self::get_true_false( $_t['public'] ) ); ?></td>
									<td><?php echo esc_html( self::get_true_false( ( isset( $_t['show_in_rest'] ) ? $_t['show_in_rest'] : 1 ) ) ); ?></td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>

				<br class="clear" />

			</div><!-- /col-container -->
		</div>

		<div class="wrap">
			<h2><?php esc_html_e( 'External Taxonomies', 'simple-taxonomy-refreshed' ); ?></h2>

			<h2 class="screen-reader-text"><?php esc_html_e( 'External Taxonomies list', 'simple-taxonomy-refreshed' ); ?></h2>

			<div id="col-container-external">
				<table class="wp-list-table widefat tag fixed striped table-view-list" cellspacing="0">
					<thead>
						<tr>
							<th scope="col" id="labell" class="manage-column column-name"><?php esc_html_e( 'Label', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" id="nameh"  class="manage-column column-slug"><?php esc_html_e( 'Name', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" id="labelt" class="manage-column column-name"><?php esc_html_e( 'Post Types', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" id="labelh" class="manage-column column-name"><?php esc_html_e( 'Hierarchical', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" id="labelb" class="manage-column column-name"><?php esc_html_e( 'Block Editor', 'simple-taxonomy-refreshed' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Label', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" class="manage-column column-slug"><?php esc_html_e( 'Name', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Post Types', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Hierarchical', 'simple-taxonomy-refreshed' ); ?></th>
							<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Block Editor', 'simple-taxonomy-refreshed' ); ?></th>
						</tr>
					</tfoot>

					<tbody id="the-list-external" class="list:taxonomies">
						<?php
						global $wp_taxonomies;

						$use_all = ( false === $current_options ) || empty( $current_options['taxonomies'] );
						$others  = array();
						foreach ( $wp_taxonomies as $key => $wp_tax ) {
							if ( $wp_tax->public && ( $use_all || ! isset( $current_options['taxonomies'][ $key ] ) ) ) {
								$others [ $key ] = $wp_tax;
							}
						}
						if ( empty( $others ) ) {
							echo '<tr><td colspan="3">' . esc_html__( 'No external taxonomy.', 'simple-taxonomy-refreshed' ) . '</td></tr>';
						} else {
							$class = 'alternate';
							$i     = 0;
							foreach ( (array) $others as $_t_name => $_t ) {
								++$i;
								$class = ( 'alternate' === $class ) ? '' : 'alternate';
								$lname = $_t->labels->name;
								// phpcs:disable  WordPress.Security.EscapeOutput
								// translators: %s is the taxonomy name.
								$edit_msg = esc_html( sprintf( __( "Edit the taxonomy '%s'", 'simple-taxonomy-refreshed' ), $lname ) );
								// phpcs:enable  WordPress.Security.EscapeOutput
								?>
								<tr id="external-<?php echo esc_attr( $i ); ?>" class="<?php esc_attr( $class ); ?>">
									<td class="name column-name has-row-actions column-primary page-title">
										<strong><a class="row-title" href="<?php echo esc_url( $admin_url ); ?>&amp;action=edit&amp;taxonomy_name=<?php echo esc_attr( $_t_name ); ?>" title="<?php esc_attr( $edit_msg ); ?>" aria-label="<?php esc_html_e( 'Extra Functions', 'simple-taxonomy-refreshed' ); ?> - <?php echo esc_html( stripslashes( $lname ) ); ?>"><?php echo esc_html( stripslashes( $lname ) ); ?></a></strong>
										<br />
										<div class="row-actions">
											<span class="edit"><a href="<?php echo esc_url( $admin_url ); ?>&amp;action=edit&amp;taxonomy_name=<?php echo esc_attr( $_t_name ); ?>" aria-label="<?php esc_html_e( 'Extra Functions', 'simple-taxonomy-refreshed' ); ?> - <?php echo esc_html( stripslashes( $lname ) ); ?>"><?php esc_html_e( 'Extra Functions', 'simple-taxonomy-refreshed' ); ?></a></span>
											<?php if ( isset( $current_options['externals'] ) && array_key_exists( $_t_name, $current_options['externals'] ) ) { ?>
											<span class="delete"> | <a class="delete-taxonomy" href="<?php echo esc_url( wp_nonce_url( esc_url( $admin_url ) . '&amp;action=delete&amp;taxonomy_name=' . $_t_name, 'staxo_delete_' . esc_attr( $_t_name ) ) ); ?>" onclick="if ( confirm( '<?php echo esc_html( $del_msg ); ?>' ) ) { return true;}return false;" aria-label="<?php esc_html_e( 'Delete Extra Functions', 'simple-taxonomy-refreshed' ); ?> - <?php echo esc_html( stripslashes( $lname ) ); ?>"><?php esc_html_e( 'Delete Extra Functions', 'simple-taxonomy-refreshed' ); ?></a></span>
											<?php } ?>
										</div>
									</td>
									<td><?php echo esc_html( $_t->name ); ?></td>
									<td>
										<?php
										if ( is_array( $_t->object_type ) && ! empty( $_t->object_type ) ) {
											$pt = array();
											foreach ( $_t->object_type as $k => $post_type ) {
												$cpt = get_post_type_object( $post_type );
												if ( null !== $cpt ) {
													$pt[] = $cpt->labels->name;
												}
											}
											echo esc_html( implode( ', ', $pt ) );
										} else {
											echo '-';
										}
										?>
									</td>
									<td><?php echo esc_html( self::get_true_false( (int) $_t->hierarchical, 0 ) ); ?></td>
									<td><?php echo esc_html( self::get_true_false( (int) $_t->show_in_rest ) ); ?></td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>

				<br class="clear" />

			</div><!-- /col-container -->
		</div>
		<?php
	}

	/**
	 * Build HTML for form custom taxonomy, add with list on right column
	 *
	 * @param array   $taxonomy  taxonomy name.
	 * @param boolean $custom    taxonomy is custom.
	 * @return void
	 */
	private static function form_merge_custom_type( $taxonomy, $custom ) {
		// Admin URL.
		$admin_url = admin_url( 'admin.php?page=' . self::ADMIN_SLUG );

		if ( null === $taxonomy ) {
			$taxonomy                 = SimpleTaxonomyRefreshed_Client::get_taxonomy_default_fields();
			$taxonomy['labels']       = SimpleTaxonomyRefreshed_Client::get_taxonomy_default_labels();
			$taxonomy['capabilities'] = SimpleTaxonomyRefreshed_Client::get_taxonomy_default_capabilities();
			$edit                     = false;
			$_action                  = 'add-taxonomy';
			$submit_val               = __( 'Add taxonomy', 'simple-taxonomy-refreshed' );
			$nonce_field              = 'staxo_add_taxo';
		} else {
			$edit = true;
			if ( $custom ) {
				$_action = 'merge-taxonomy';
			} else {
				$_action = 'merge-external';
			}
			$submit_val  = __( 'Update taxonomy', 'simple-taxonomy-refreshed' );
			$nonce_field = 'staxo_edit_taxo';
		}

		// set up the taxonomy parameter array. Note. $taxonomy has two different structure.
		if ( $custom ) {
			// Get default values if need.
			$taxonomy = wp_parse_args( SimpleTaxonomyRefreshed_Client::prepare_args( $taxonomy ), $taxonomy );

			// Migration case - Not updated yet.
			if ( ! array_key_exists( 'st_before', $taxonomy ) ) {
				$taxonomy['st_before'] = '';
				$taxonomy['st_after']  = '';
			}

			// Added 1.0.
			if ( ! array_key_exists( 'st_slug', $taxonomy ) ) {
				$taxonomy['st_slug']                  = '';
				$taxonomy['st_with_front']            = 1;
				$taxonomy['st_hierarchical']          = 1;
				$taxonomy['st_ep_mask']               = '';
				$taxonomy['st_update_count_callback'] = '';
				$taxonomy['st_meta_box_cb']           = '';
				$taxonomy['st_meta_box_sanitize_cb']  = '';
				$taxonomy['st_args']                  = '';
			}

			// Added 1.1.
			if ( ! array_key_exists( 'st_adm_types', $taxonomy ) ) {
				$taxonomy['st_adm_types'] = array();
				$taxonomy['st_adm_hier']  = 0;
				$taxonomy['st_adm_depth'] = 0;
				$taxonomy['st_adm_count'] = 0;
				$taxonomy['st_adm_h_e']   = 0;
				$taxonomy['st_adm_h_i_e'] = 0;
				$taxonomy['st_cb_type']   = 0;
				$taxonomy['st_cb_pub']    = 0;
				$taxonomy['st_cb_fut']    = 0;
				$taxonomy['st_cb_dft']    = 0;
				$taxonomy['st_cb_pnd']    = 0;
				$taxonomy['st_cb_prv']    = 0;
				$taxonomy['st_cb_tsh']    = 0;
			}

			// Added 1.2.
			if ( ! array_key_exists( 'st_cc_type', $taxonomy ) ) {
				$taxonomy['st_cc_type']  = 0;
				$taxonomy['st_cc_hard']  = 0;
				$taxonomy['st_cc_umin']  = 0;
				$taxonomy['st_cc_umax']  = 0;
				$taxonomy['st_cc_min']   = 0;
				$taxonomy['st_cc_max']   = 0;
				$taxonomy['st_dft_name'] = '';
				$taxonomy['st_dft_slug'] = '';
				$taxonomy['st_dft_desc'] = '';
			}

			// Added 2.0.
			if ( ! array_key_exists( 'st_feed', $taxonomy ) ) {
				$taxonomy['st_feed']        = 0;
				$taxonomy['rest_namespace'] = '';
			}

			// Added 2.4.
			if ( ! array_key_exists( 'st_sep', $taxonomy ) ) {
				$taxonomy['st_sep'] = '';
			}

			// Added 3.1.
			if ( ! array_key_exists( 'st_cc_types', $taxonomy ) ) {
				$taxonomy['st_cc_types'] = array();
			}

			// Label menu_name needs to exist to edit (it is removed for registering).
			if ( ! array_key_exists( 'menu_name', $taxonomy['labels'] ) ) {
				$taxonomy['labels']['menu_name'] = '';
			}

			// If rewrite is true, then set st_ variables from rewrite (may have passed through a filter).
			if ( $taxonomy['rewrite'] && isset( $taxonomy['rewrite']['slug'] ) ) {
				$taxonomy['st_slug']         = $taxonomy['rewrite']['slug'];
				$taxonomy['st_with_front']   = (int) $taxonomy['rewrite']['with_front'];
				$taxonomy['st_hierarchical'] = (int) $taxonomy['rewrite']['hierarchical'];
				$taxonomy['st_ep_mask']      = $taxonomy['rewrite']['ep_mask'];
			}
			if ( isset( $taxonomy['default_term'] ) && isset( $taxonomy['default_term']['name'] ) ) {
				$taxonomy['st_dft_name'] = $taxonomy['default_term']['name'];
				if ( isset( $taxonomy['default_term']['slug'] ) ) {
					$taxonomy['st_dft_slug'] = $taxonomy['default_term']['slug'];
				}
				if ( isset( $taxonomy['default_term']['desc'] ) ) {
					$taxonomy['st_dft_desc'] = $taxonomy['default_term']['desc'];
				}
			}

			// set output if user data has existing value.
			if ( ! isset( $taxonomy['update_count_callback'] ) && isset( $taxonomy['st_update_count_callback'] ) ) {
				$taxonomy['update_count_callback'] = $taxonomy['st_update_count_callback'];
			}
			if ( ! isset( $taxonomy['meta_box_cb'] ) && isset( $taxonomy['st_meta_box_cb'] ) ) {
				$taxonomy['meta_box_cb'] = ( 'false' === $taxonomy['st_meta_box_cb'] ? false : $taxonomy['st_meta_box_cb'] );
			}
			if ( ! isset( $taxonomy['meta_box_sanitize_cb'] ) && isset( $taxonomy['st_meta_box_sanitize_cb'] ) ) {
				$taxonomy['meta_box_sanitize_cb'] = $taxonomy['st_meta_box_sanitize_cb'];
			}
			if ( ! isset( $taxonomy['args'] ) && isset( $taxonomy['st_args'] ) ) {
				$taxonomy['args'] = $taxonomy['st_args'];
			}
		} else {
			// Taxonomy array prepared before calling.
			null;
		}
		// If show_in_graphql is true, then set st_ variables from values (may have passed through a filter).
		$taxonomy['st_show_in_graphql'] = ( isset( $taxonomy['show_in_graphql'] ) ? +$taxonomy['show_in_graphql'] : 0 );
		$taxonomy['st_graphql_single']  = ( isset( $taxonomy['graphql_single'] ) ? $taxonomy['graphql_single'] : '' );
		$taxonomy['st_graphql_plural']  = ( isset( $taxonomy['graphql_plural'] ) ? $taxonomy['graphql_plural'] : '' );
		?>

		<form id="addtag" method="post" action="<?php echo esc_url( $admin_url ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_html( $_action ); ?>" />
			<?php wp_nonce_field( $nonce_field ); ?>

			<p><?php esc_html_e( 'Click on the tabs to see all the options and facilities available.', 'simple-taxonomy-refreshed' ); ?></p>

			<?php if ( $custom ) { ?>
				<p><?php esc_html_e( 'The taxonomy definition options are spread across 7 tabs. The remaining 4 are for integrating the taxonomy.', 'simple-taxonomy-refreshed' ); ?></p>
			<?php } else { ?>
				<p><?php esc_html_e( 'The taxonomy is defined outside this plugin. These inputs are for integrating the taxonomy.', 'simple-taxonomy-refreshed' ); ?></p>
				<p><strong><?php esc_html_e( 'Note that these functionalities may already be defined for the taxonomy. Please check before defining then here.', 'simple-taxonomy-refreshed' ); ?></strong></p>
			<?php } ?>

			<div id="poststuff" class="metabox-holder">
				<div id="post-body-content">
					<div role="tablist">
						<?php if ( $custom ) { ?>
							<button type="button" role="tab" aria-controls="mainopts" aria-selected="true"><?php esc_html_e( 'Main Options', 'simple-taxonomy-refreshed' ); ?></button>
							<button type="button" role="tab" aria-controls="visibility"><?php esc_html_e( 'Visibility', 'simple-taxonomy-refreshed' ); ?></button>
							<button type="button" role="tab" aria-controls="labels"><?php esc_html_e( 'Labels', 'simple-taxonomy-refreshed' ); ?></button>
							<button type="button" role="tab" aria-controls="rewriteURL"><?php esc_html_e( 'Rewrite URL', 'simple-taxonomy-refreshed' ); ?></button>
							<button type="button" role="tab" aria-controls="permissions"><?php esc_html_e( 'Permissions', 'simple-taxonomy-refreshed' ); ?></button>
							<button type="button" role="tab" aria-controls="rest"><?php esc_html_e( 'REST', 'simple-taxonomy-refreshed' ); ?></button>
							<button type="button" role="tab" aria-controls="other"><?php esc_html_e( 'Other', 'simple-taxonomy-refreshed' ); ?></button>
							<button type="button" role="tab" aria-controls="wpgraphql"><?php esc_html_e( 'WPGraphQL', 'simple-taxonomy-refreshed' ); ?></button>
						<?php } else { ?>
							<button type="button" role="tab" aria-controls="wpgraphql" aria-selected="true"><?php esc_html_e( 'WPGraphQL', 'simple-taxonomy-refreshed' ); ?></button>
						<?php } ?>
						<button type="button" role="tab" aria-controls="adm_filter"><?php esc_html_e( 'Admin List Filter', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" role="tab" aria-controls="callback"><?php esc_html_e( 'Term Count', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" role="tab" aria-controls="countt"><?php esc_html_e( 'Term Control', 'simple-taxonomy-refreshed' ); ?></button>
					</div>

					<?php if ( $custom ) { ?>
					<div id="mainopts" class="meta-box-sortabless" role="tabpanel">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Main Options', 'simple-taxonomy-refreshed' ); ?></span></h3>

							<div class="inside">
								<table class="form-table" style="clear:none;">
									<tr>
										<th scope="row"><label for="name"><?php esc_html_e( 'Name (slug)', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
											<input name="name" type="text" id="name" onchange="checkNameSet(event)" value="<?php echo esc_attr( $taxonomy['name'] ); ?>" class="regular-text"
											<?php
											if ( true === $edit ) {
												echo ' readonly="readonly"';
											}
											?>
											/>
											<span class="description">
											<?php
											// phpcs:ignore  WordPress.Security.EscapeOutput
											_e( '<strong>Name</strong> is used on DB and to register taxonomy. (Lowercase alphanumeric and _ characters only)', 'simple-taxonomy-refreshed' );
											?>
											</span>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="hierarchical"><?php esc_html_e( 'Hierarchical ?', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
											<select name="hierarchical" id="hierarchical">
												<?php
												foreach ( self::get_true_false() as $type_key => $type_name ) {
													echo '<option ' . selected( (int) $taxonomy['hierarchical'], $type_key, false );
													echo ' onclick="linkH(event, ' . esc_attr( $type_key ) . ')"';
													echo ' value="' . esc_attr( $type_key ) . '">' . esc_html( $type_name ) . '</option>' . "\n";
												}
												?>
											</select>
											<span class="description"><?php esc_html_e( "The default hierarchical in WordPress are categories. Default post tags WP aren't hierarchical.", 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
									<tr>
										<th scope="row"><label id="post_types"><?php esc_html_e( 'Post types', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td><div role="group" aria-labelledby="post_types" aria-describedby="post_types_sel">
											<?php
											if ( true === $edit && is_array( $taxonomy['objects'] ) ) {
												$objects = $taxonomy['objects'];
											} else {
												$objects = array();
											}
											$i = 0;
											foreach ( self::get_object_types() as $type ) {
												$show = in_array( $type->name, $objects, true );
												echo '<label class="inline">';
												echo '<input type="checkbox" role="checkbox" aria-checked="' . ( $show ? 'true' : 'false' );
												echo '" tabindex="0" ' . checked( true, $show, false );
												echo ' onclick="linkAdm(event, ' . esc_attr( $i ) . ')"';
												echo ' name="objects[]" value="' . esc_attr( $type->name ) . '" />';
												echo esc_attr( $type->label ) . '</label>' . "\n";
												++$i;
											}
											?>
											</div><span class="description" id="post_types_sel"><?php esc_html_e( 'Select which builtin or custom post types will use this taxonomy.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="auto"><?php esc_html_e( 'Display Terms with Posts', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
											<select name="auto" id="auto">
												<?php
												foreach ( self::get_auto_content_types() as $type_key => $type_name ) {
													echo '<option ' . esc_attr( selected( $taxonomy['auto'], $type_key, false ) ) . ' value="' . esc_attr( $type_key ) . '">' . esc_html( $type_name ) . '</option>' . "\n";
												}
												?>
											</select>
											<span class="description"><?php esc_html_e( 'Option to display the terms on the Post page with associated data', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
								</table>
								<span class="description"><p><?php esc_html_e( 'The following three parameters are only needed if taxonomy is displayed', 'simple-taxonomy-refreshed' ); ?></p>
								<p><?php esc_html_e( 'They can take the form of either simple text or as html data such as list tags. But all three need to be consistent types', 'simple-taxonomy-refreshed' ); ?></p></span>
								<table class="form-table" style="clear:none;">
									<?php
										self::option_text(
											$taxonomy,
											'st_before',
											esc_html__( 'Display Terms Before text', 'simple-taxonomy-refreshed' ),
											esc_html__( 'This text will be used before the Post terms display list', 'simple-taxonomy-refreshed' ) . '<br/>' .
											esc_html__( 'Simple text will be trimmed and a single space output after this.', 'simple-taxonomy-refreshed' ) . '<br/>' .
											esc_html__( 'Will also be used with the shortcode and post_terms block.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_sep',
											esc_html__( 'Display Terms Separator text', 'simple-taxonomy-refreshed' ),
											esc_html__( 'This text will be used as the separator between the Post terms in the display list', 'simple-taxonomy-refreshed' ) . '<br/>' .
											esc_html__( 'By default, it will consist of a comma followed by a space.', 'simple-taxonomy-refreshed' ) . '<br/>' .
											esc_html__( 'Will also be used with the shortcode and post_terms block.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_after',
											esc_html__( 'Display Terms After text', 'simple-taxonomy-refreshed' ),
											esc_html__( 'This text will be used after the Post terms display list', 'simple-taxonomy-refreshed' ) . '<br/>' .
											esc_html__( 'Simple text will be trimmed and a single space output before this.', 'simple-taxonomy-refreshed' ) . '<br/>' .
											esc_html__( 'Will also be used with the shortcode and post_terms block.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'st_feed',
											esc_html__( 'Show in feeds ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Whether taxonomy terms will be shown in post feeds', 'simple-taxonomy-refreshed' )
										);
									?>
								</table>
							</div>
						</div>
					</div>

					<div id="visibility" class="meta-box-sortabless is-hidden" role="tabpanel">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Visibility', 'simple-taxonomy-refreshed' ); ?></span></h3>
							<div class="inside">
								<table class="form-table" style="clear:none;">
									<?php
										self::option_yes_no(
											$taxonomy,
											'public',
											esc_html__( 'Public ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Whether taxonomy queries can be performed from the front page.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'publicly_queryable',
											esc_html__( 'Publicly Queryable ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Whether the taxonomy is publicly queryable.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'show_ui',
											esc_html__( 'Display on admin ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Whether to generate and allow a UI for managing terms in this taxonomy in the admin.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'show_in_menu',
											esc_html__( 'Show in Menu ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Whether to show the taxonomy in the admin menu. If true, the taxonomy is shown as a submenu of the object type menu. If false, no menu is shown.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'show_in_nav_menus',
											esc_html__( 'Show in nav menu ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Makes this taxonomy available for selection in navigation menus.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'show_tagcloud',
											esc_html__( 'Show in tag cloud widget ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Put this setting to true for display this taxonomy on settings of tag cloud widget.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'show_in_quick_edit',
											esc_html__( 'Display in Quick Edit panel ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Whether to show the taxonomy in the quick/bulk edit panel.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'show_admin_column',
											esc_html__( 'Display a column on admin lists ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Whether to display a column for the taxonomy on its post type admin listing screens.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'show_in_rest',
											esc_html__( 'Show in REST ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Whether to include the taxonomy in the REST API and Block Editor.', 'simple-taxonomy-refreshed' )
										);
									?>
								</table>
								<p style="text-indent: 15px;"><?php esc_html_e( 'Show in REST needs to be set to TRUE for the taxonomy to appear in the Block Editor.', 'simple-taxonomy-refreshed' ); ?></p>
							</div>
						</div>
					</div>

					<div id="labels" class="meta-box-sortabless is-hidden" role="tabpanel">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Labels Wording', 'simple-taxonomy-refreshed' ); ?></span></h3>

							<div class="inside">
								<table class="form-table" style="clear:none;">
									<?php
										self::option_label(
											$taxonomy,
											'name',
											esc_html__( 'Name (label)', 'simple-taxonomy-refreshed' ),
											esc_html__( 'This will be used as the taxonomy label.', 'simple-taxonomy-refreshed' )
										);
										self::option_label(
											$taxonomy,
											'name_field_description',
											esc_html__( 'Name Field Description', 'simple-taxonomy-refreshed' ),
											esc_html__( 'The description of the name field.', 'simple-taxonomy-refreshed' )
										);
										self::option_label(
											$taxonomy,
											'menu_name',
											esc_html__( 'Menu Name', 'simple-taxonomy-refreshed' ),
											esc_html__( 'If not set this will default to the taxonomy label.', 'simple-taxonomy-refreshed' )
										);
										self::option_label(
											$taxonomy,
											'singular_name',
											esc_html__( 'Singular Name', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'search_items',
											esc_html__( 'Search Terms', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'popular_items',
											esc_html__( 'Popular Terms', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'all_items',
											esc_html__( 'All Terms', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'parent_item',
											esc_html__( 'Parent Term', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'parent_item_colon',
											esc_html__( 'Parent Term:', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Parent Term with colon', 'simple-taxonomy-refreshed' )
										);
										self::option_label(
											$taxonomy,
											'parent_field_description',
											esc_html__( 'Parent Term Field Description', 'simple-taxonomy-refreshed' ),
											esc_html__( 'These Description texts will appear in the term item entry screen.', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'slug_field_description',
											esc_html__( 'Slug Field Description', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'desc_field_description',
											esc_html__( 'Descriptiom Field Description', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'edit_item',
											esc_html__( 'Edit Term', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'view_item',
											esc_html__( 'View Term', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'update_item',
											esc_html__( 'Update Term', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'add_new_item',
											esc_html__( 'Add New Term', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'new_item_name',
											esc_html__( 'New Term Name', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'separate_items_with_commas',
											esc_html__( 'Separate terms with commas', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'add_or_remove_items',
											esc_html__( 'Add or remove terms', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'choose_from_most_used',
											esc_html__( 'Choose from the most used terms', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'not_found',
											esc_html__( 'No terms found', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'no_terms',
											esc_html__( 'No terms', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'items_list_navigation',
											esc_html__( 'Items list navigation', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'items_list',
											esc_html__( 'Items list', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'most_used',
											esc_html__( 'Most used', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'back_to_items',
											esc_html__( 'Label displayed after a term has been updated', 'simple-taxonomy-refreshed' ),
											''
										);
										self::option_label(
											$taxonomy,
											'filter_by_item',
											esc_html__( 'Related filter', 'simple-taxonomy-refreshed' ),
											esc_html__( 'The related filter is displayed at the top of list tables, but only for hierarchical taxonomies', 'simple-taxonomy-refreshed' )
										);
										self::option_label(
											$taxonomy,
											'item_link',
											esc_html__( 'Title for a navigation link block variation.', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Used in the block editor.', 'simple-taxonomy-refreshed' )
										);
										self::option_label(
											$taxonomy,
											'item_link_description',
											esc_html__( 'Description for a navigation link block', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Used in the block editor.', 'simple-taxonomy-refreshed' )
										);
										self::option_label(
											$taxonomy,
											'no_term',
											esc_html__( 'No term', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Used by the plugin to select no term from this taxonomy should be used (where a checkbox has been changed to a radio button).', 'simple-taxonomy-refreshed' )
										);
									?>
								</table>
							</div>
						</div>
					</div>

					<div id="rewriteURL" class="meta-box-sortabless is-hidden" role="tabpanel">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Rewrite URL', 'simple-taxonomy-refreshed' ); ?></span></h3>
							<div class="inside">
								<table class="form-table" style="clear:none;">
									<?php
										self::option_yes_no(
											$taxonomy,
											'rewrite',
											esc_html__( 'Rewrite ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Rewriting allows to build nice URL for your new custom taxonomy.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_slug',
											esc_html__( 'Rewrite Slug', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Customize the permastruct slug.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'st_with_front',
											esc_html__( 'With Front ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Should the permastruct be prepended with WP_Rewrite::$front.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'st_hierarchical',
											esc_html__( 'Hierarchical ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Either hierarchical rewrite tag or not.', 'simple-taxonomy-refreshed' )
										);
									?>
									<tr>
										<th scope="row"><label for="st_ep_mask_s"><?php esc_html_e( 'EP_MASK', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
											<select name="st_ep_mask_s[]" id="st_ep_mask_s" multiple size="6">
												<?php
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'], 0, false ) ) . ' value="0">' . esc_html__( 'EP_NONE', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 1, 1, false ) ) . ' value="1">' . esc_html__( 'EP_PERMALINK', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 2, 2, false ) ) . ' value="2">' . esc_html__( 'EP_ATTACHMENT', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 4, 4, false ) ) . ' value="4">' . esc_html__( 'EP_DATE', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 8, 8, false ) ) . ' value="8">' . esc_html__( 'EP_YEAR', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 16, 16, false ) ) . ' value="16">' . esc_html__( 'EP_MONTH', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 32, 32, false ) ) . ' value="32">' . esc_html__( 'EP_DAY', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 64, 64, false ) ) . ' value="64">' . esc_html__( 'EP_ROOT', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 128, 128, false ) ) . ' value="128">' . esc_html__( 'EP_COMMENTS', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 256, 256, false ) ) . ' value="256">' . esc_html__( 'EP_SEARCH', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 512, 512, false ) ) . ' value="512">' . esc_html__( 'EP_CATEGORIES', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 1024, 1024, false ) ) . ' value="1024">' . esc_html__( 'EP_TAGS', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 2048, 2048, false ) ) . ' value="2048">' . esc_html__( 'EP_AUTHORS', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												echo '<option ' . esc_attr( selected( (int) $taxonomy['st_ep_mask'] & 4096, 4096, false ) ) . ' value="4096">' . esc_html__( 'EP_PAGES', 'simple-taxonomy-refreshed' ) . '</option>' . "\n";
												?>
											</select>
											<span class="description"><?php esc_html_e( 'Assign an endpoint mask.', 'simple-taxonomy-refreshed' ); ?></span>
											<span class="description"><?php esc_html_e( 'N.B. Use CTRL or Mac Cmd to select multiple entries.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>

					<div id="permissions" class="meta-box-sortabless is-hidden" role="tabpanel">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Permissions', 'simple-taxonomy-refreshed' ); ?></span></h3>
							<div class="inside">
								<table class="form-table" style="clear:none;">
									<?php
										self::option_cap(
											$taxonomy,
											'manage_terms',
											esc_html__( 'Manage terms', 'simple-taxonomy-refreshed' ),
											esc_html__( "Ability to view terms in the administration. Defaults to 'manage_categories'.", 'simple-taxonomy-refreshed' )
										);
										self::option_cap(
											$taxonomy,
											'edit_terms',
											esc_html__( 'Edit terms', 'simple-taxonomy-refreshed' ),
											esc_html__( "Grants the ability to edit and create terms. Defaults to 'manage_categories'", 'simple-taxonomy-refreshed' )
										);
										self::option_cap(
											$taxonomy,
											'delete_terms',
											esc_html__( 'Delete terms', 'simple-taxonomy-refreshed' ),
											esc_html__( "Gives permission to delete terms from the taxonomy. Defaults to 'manage_categories'.", 'simple-taxonomy-refreshed' )
										);
										self::option_cap(
											$taxonomy,
											'assign_terms',
											esc_html__( 'Assign terms', 'simple-taxonomy-refreshed' ),
											esc_html__( "Capability for assigning terms in the new/edit post screen. Defaults to 'edit_terms'.", 'simple-taxonomy-refreshed' )
										);
									?>
								</table>
							</div>
						</div>
					</div>

					<div id="rest" class="meta-box-sortabless is-hidden" role="tabpanel">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'REST Functionality', 'simple-taxonomy-refreshed' ); ?></span></h3>
							<div class="inside">
								<p class="description"><?php esc_html_e( 'These values should only be set when specific processing is required.', 'simple-taxonomy-refreshed' ); ?></p>
								<table class="form-table" style="clear:none;">
									<?php
										self::option_text(
											$taxonomy,
											'rest_base',
											esc_html__( 'REST Base', 'simple-taxonomy-refreshed' ),
											esc_html__( 'To change the base url of REST API route.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'rest_namespace',
											esc_html__( 'REST Namespace', 'simple-taxonomy-refreshed' ),
											esc_html__( "Defaults to '/wp/v2'.", 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'rest_controller_class',
											esc_html__( 'REST Controller Class', 'simple-taxonomy-refreshed' ),
											esc_html__( "REST API Controller class name. Default is 'WP_REST_Terms_Controller'.", 'simple-taxonomy-refreshed' )
										);
									?>
								</table>
							</div>
						</div>
					</div>

					<div id="other" class="meta-box-sortabless is-hidden" role="tabpanel">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Other Options', 'simple-taxonomy-refreshed' ); ?></span></h3>
							<div class="inside">
								<table class="form-table" style="clear:none;">
									<?php
										self::option_text(
											$taxonomy,
											'query_var',
											esc_html__( 'Query var', 'simple-taxonomy-refreshed' ),
											__( '<strong>Query var</strong> is used for build URLs of taxonomy. If this value is empty, WordPress will use the taxonomy slug for build URL.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_update_count_callback',
											esc_html__( 'Update Count Callback', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Works much like a hook, in that it will be called when the count is updated.', 'simple-taxonomy-refreshed' ) . '<br/>' .
											esc_html__( 'Set to the text false to not display the metabox.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_meta_box_cb',
											esc_html__( 'Meta Box Callback', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Provide a callback function for the meta box display.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_meta_box_sanitize_cb',
											esc_html__( 'Meta Box Sanitize Callback', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Callback function for sanitizing taxonomy data saved from a meta box.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'sort',
											esc_html__( 'Sort ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Whether this taxonomy should remember the order in which terms are added to objects.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_args',
											esc_html__( 'Args - Taxonomy Items Sort Query', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Array giving query to order the taxonomy items attached to objects.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_dft_name',
											esc_html__( 'Default Term Name', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Default term name to apply to attach to objects.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_dft_slug',
											esc_html__( 'Default Term Slug', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Default term slug for the default term.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_dft_desc',
											esc_html__( 'Default Term Description', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Default term description for the default term.', 'simple-taxonomy-refreshed' )
										);
									?>
								</table>
							</div>
						</div>
					</div>

					<div id="wpgraphql" class="meta-box-sortabless is-hidden" role="tabpanel">
					<?php } else { ?>
					<div id="wpgraphql" class="meta-box-sortabless" role="tabpanel">
					<input type="hidden" id="name" name="name" value="<?php echo esc_attr( $taxonomy['name'] ); ?>" />
					<input type="hidden" id="hierarchical" name="hierarchical" value="<?php echo esc_attr( (int) $taxonomy['hierarchical'] ); ?>" />
					<input type="hidden" id="st_update_count_callback" name="st_update_count_callback" value="<?php echo esc_attr( $taxonomy['st_update_count_callback'] ); ?>" />
					<?php } ?>
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'WPGraphQL Support', 'simple-taxonomy-refreshed' ); ?></span></h3>
							<div class="inside">
								<table class="form-table" style="clear:none;">
									<?php
										self::option_yes_no(
											$taxonomy,
											'st_show_in_graphql',
											esc_html__( 'Show in WPGraphQL ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Whether this taxonomy is registered for WPGraphQL usage.', 'simple-taxonomy-refreshed' )
										);
									?>
								</table>
								<p class="description"><?php esc_html_e( 'Following parameters are only needed if taxonomy is shown in WPGraphQL', 'simple-taxonomy-refreshed' ); ?></p>
								<table class="form-table" style="clear:none;">
									<?php
										self::option_text(
											$taxonomy,
											'st_graphql_single',
											esc_html__( 'Graph QL Single Name', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Taxonomy Singular Name for WPGraphQL in camel case with no punctuation or spaces.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_graphql_plural',
											esc_html__( 'Graph QL Plural Name', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Taxonomy Plural Name for WPGraphQL in camel case with no punctuation or spaces.', 'simple-taxonomy-refreshed' )
										);
									?>
								</table>
							</div>
						</div>
					</div>

					<div id="adm_filter" class="meta-box-sortabless is-hidden" role="tabpanel">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Admin List Filter', 'simple-taxonomy-refreshed' ); ?></span></h3>

							<div class="inside">
								<table class="form-table" style="clear:none;">
									<p><?php esc_html_e( 'Taxonomy needs to be allocated to Post Types and Publicly Queryable.', 'simple-taxonomy-refreshed' ); ?></p>
									<p><?php esc_html_e( 'Advisable to have a column on the admin list screen.', 'simple-taxonomy-refreshed' ); ?></p>
									<?php if ( $custom ) { ?>
										<p><?php esc_html_e( '(See Visibility tab for these parameters.)', 'simple-taxonomy-refreshed' ); ?></p>
									<?php } else { ?>
										<p><strong><?php esc_html_e( 'Attention. You can use this function to define a duplicate filter if it already exists', 'simple-taxonomy-refreshed' ); ?></strong></p>
									<?php } ?>
									<tr>
										<th scope="row"><label id="pt_adm"><?php esc_html_e( 'Post types', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td><div role="group" aria-labelledby="pt_adm" aria-describedby="post_types_adm">
											<?php
											if ( true === $edit ) {
												$objects = $taxonomy['st_adm_types'];
											} else {
												$objects = array();
											}
											// External taxonomies types should only show defined post types.
											$i = 0;
											foreach ( self::get_object_types() as $type ) {
												if ( ! $custom && ! in_array( $type->name, (array) $taxonomy['objects'], true ) ) {
													continue;
												}
												$show = in_array( $type->name, (array) $objects, true );
												echo '<label class="inline">';
												echo '<input id="admlist' . esc_attr( $i ) . '" type="checkbox" role="checkbox" aria-checked="' . ( $show ? 'true' : 'false' );
												echo '" tabindex="0" onclick="ariaChk(event)" ' . checked( true, $show, false );
												if ( ! in_array( $type->name, (array) $taxonomy['objects'], true ) ) {
													echo ' disabled';
												}
												echo ' name="st_adm_types[]" value="' . esc_attr( $type->name ) . '" />';
												echo esc_html( $type->label ) . '</label>' . "\n";
												++$i;
											}
											?>
											<span class="description" id="post_types_adm"><?php esc_html_e( 'You can add this taxonomy as a filter field on the admin list screen for selected builtin or custom post types linked to this taxonomy.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
									<?php
										self::option_yes_no(
											$taxonomy,
											'st_adm_hier',
											esc_html__( 'Hierarchical ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Hierarchical dropdown. Only relevant for hierarchical taxonomies.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_adm_depth',
											esc_html__( 'Hierarchy Depth', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Hierarchical dropdown depth. Only relevant for hierarchical taxonomies.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'st_adm_count',
											esc_html__( 'Show Count ?', 'simple-taxonomy-refreshed' ),
											esc_html__( 'Whether to include post counts. Default false', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'st_adm_h_e',
											esc_html__( 'Hide Empty', 'simple-taxonomy-refreshed' ),
											esc_html__( 'True to hide element if Term count is zero.', 'simple-taxonomy-refreshed' )
										);
										self::option_yes_no(
											$taxonomy,
											'st_adm_h_i_e',
											esc_html__( 'Hide if Empty', 'simple-taxonomy-refreshed' ),
											esc_html__( 'True to skip generating markup if no terms are found. Default false', 'simple-taxonomy-refreshed' )
										);
									?>
								</table>
							</div>
						</div>
					</div>

					<div id="callback" class="meta-box-sortabless is-hidden" role="tabpanel">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Term Count', 'simple-taxonomy-refreshed' ); ?></span></h3>

							<div class="inside">
								<span id="count_tab_0" <?php echo ( empty( $taxonomy['st_update_count_callback'] ) ? 'class="is-hidden"' : '' ); ?>>
									<p><?php esc_html_e( 'A function has been defined for calculating term counts. This function is therefore not available.', 'simple-taxonomy-refreshed' ); ?></p>
								<table  class="form-table" style="clear:none;">
								</table>
								</span>
								<span id="count_tab_1" <?php echo ( empty( $taxonomy['st_update_count_callback'] ) ? '' : 'class="is-hidden"' ); ?>>
								<table class="form-table" style="clear:none;">
									<p id="cb_descr"><?php esc_html_e( 'Term counts are normally based on Published posts. This option provides some no-coding configuration.', 'simple-taxonomy-refreshed' ); ?></p>
									<tr>
										<th scope="row"><label id="cb_label"><?php esc_html_e( 'Count Options', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td><div id="cb_type" role="radiogroup" aria-labelledby="cb_label" aria-describedby="cb_descr">
											<fieldset>
											<label><input type="radio" id="cb_std" name="st_cb_type" <?php checked( 0, $taxonomy['st_cb_type'], true ); ?> value="0" onclick="hideSel(event, 0)"><?php esc_html_e( 'Standard (Publish)', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<label><input type="radio" id="cb_any" name="st_cb_type" <?php checked( 1, $taxonomy['st_cb_type'], true ); ?> value="1" onclick="hideSel(event, 1)"><?php esc_html_e( 'Any (Except Trash)', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<label><input type="radio" id="cb_sel" name="st_cb_type" <?php checked( 2, $taxonomy['st_cb_type'], true ); ?> value="2" onclick="hideSel(event, 2)"><?php esc_html_e( 'Selection', 'simple-taxonomy-refreshed' ); ?></label><br/>
											</fieldset>
										</div></td>
									</tr>
								</table>
								<span id="count_sel_0" <?php echo ( 2 === (int) $taxonomy['st_cb_type'] ? 'class="is-hidden"' : '' ); ?>">
									<p><?php esc_html_e( 'Additional parameters not shown as they are not applicable.', 'simple-taxonomy-refreshed' ); ?></p>
								</span>
								<span id="count_sel_1" <?php echo ( 2 === (int) $taxonomy['st_cb_type'] ? '' : 'class="is-hidden"' ); ?>">
								<table class="form-table" style="clear:none;">
									<tr>
										<th scope="row"><label id="post_status"><?php esc_html_e( 'Status Selection', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
											<div role="group" aria-labelledby="post_status" aria-describedby="post_status_sel">
											<fieldset>
											<?php
											// Use WordPress translations for statuses.
											// phpcs:disable WordPress.WP.I18n.MissingArgDomain
											self::option_check_status( 'st_cb_pub', (bool) $taxonomy['st_cb_pub'], __( 'Publish' ) );
											self::option_check_status( 'st_cb_fut', (bool) $taxonomy['st_cb_fut'], __( 'Future' ) );
											self::option_check_status( 'st_cb_dft', (bool) $taxonomy['st_cb_dft'], __( 'Draft' ) );
											self::option_check_status( 'st_cb_pnd', (bool) $taxonomy['st_cb_pnd'], __( 'Pending' ) );
											self::option_check_status( 'st_cb_prv', (bool) $taxonomy['st_cb_prv'], __( 'Private' ) );
											self::option_check_status( 'st_cb_tsh', (bool) $taxonomy['st_cb_tsh'], __( 'Trash' ) );
											// phpcs:enable WordPress.WP.I18n.MissingArgDomain
											?>
											</fieldset>
											</div>
											<span class="description" id="post_status_sel"><?php esc_html_e( 'Choose the set of Statuses to be included in Term counts.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
								</table>
								</span>
								</span>
							</div>
						</div>
					</div>

					<div id="countt" class="meta-box-sortabless is-hidden" role="tabpanel">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Term Control', 'simple-taxonomy-refreshed' ); ?></span></h3>

							<div class="inside">
								<table class="form-table" style="clear:none;">
									<p id="cc_descr"><?php esc_html_e( 'Term controls are to be applied on posts. This option provides some no-coding configuration.', 'simple-taxonomy-refreshed' ); ?></p>
									<tr>
										<th scope="row"><label id="cc_label"><?php esc_html_e( 'Post status', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td><fieldset><div id="cc_type" role="radiogroup" aria-labelledby="cc_label" aria-describedby="cc_descr">
											<label><input type="radio" id="cc_off" name="st_cc_type" role="radio" <?php checked( 0, $taxonomy['st_cc_type'], true ); ?> value="0" onclick="ccSel(event, 0)"><?php esc_html_e( 'No control applied', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<label><input type="radio" id="cc_pub" name="st_cc_type" role="radio" <?php checked( 1, $taxonomy['st_cc_type'], true ); ?> value="1" onclick="ccSel(event, 1)"><?php esc_html_e( 'Published only', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<label><input type="radio" id="cc_any" name="st_cc_type" role="radio" <?php checked( 2, $taxonomy['st_cc_type'], true ); ?> value="2" onclick="ccSel(event, 2)"><?php esc_html_e( 'Any (Except Trash)', 'simple-taxonomy-refreshed' ); ?></label><br/>
											</div></fieldset>
											<span class="description"><?php esc_html_e( 'Choose  the statuses of posts to apply the control.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
								</table>
								<span id="control_tab_0" <?php echo ( empty( $taxonomy['st_cc_type'] ) ? '' : 'class="is-hidden"' ); ?>>
								<p><?php esc_html_e( 'Additional parameters not shown as they are not applicable.', 'simple-taxonomy-refreshed' ); ?></p>
								</span>
								<span id="control_tab_1" <?php echo ( empty( $taxonomy['st_cc_type'] ) ? 'class="is-hidden"' : '' ); ?>>
								<table class="form-table" style="clear:none;">
									<tr>
										<th scope="row"><label id="pt_cc"><?php esc_html_e( 'Post types', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td><div role="group" aria-labelledby="pt_cc" aria-describedby="post_types_cc">
											<?php
											if ( true === $edit ) {
												$objects = $taxonomy['st_cc_types'];
											} else {
												$objects = array();
											}
											// External taxonomies types should only show defined post types.
											$i = 0;
											foreach ( self::get_object_types() as $type ) {
												if ( ! $custom && ! in_array( $type->name, (array) $taxonomy['objects'], true ) ) {
													continue;
												}
												$show = in_array( $type->name, (array) $objects, true );
												echo '<label class="inline">';
												echo '<input id="cclist' . esc_attr( $i ) . '" type="checkbox" role="checkbox" aria-checked="' . ( $show ? 'true' : 'false' );
												echo '" tabindex="0" onclick="ariaChk(event)" ' . checked( true, $show, false );
												if ( ! in_array( $type->name, (array) $taxonomy['objects'], true ) ) {
													echo ' disabled';
												}
												echo ' name="st_cc_types[]" value="' . esc_attr( $type->name ) . '" />';
												echo esc_html( $type->label ) . '</label>' . "\n";
												++$i;
											}
											?>
											<span class="description" id="post_types_cc"><?php esc_html_e( 'Select the Post Types to which the controls should apply. If none are selected then all eligible ones will be.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
									<tr>
										<th scope="row"><label id="cch_label"><?php esc_html_e( 'How Control is applied', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td><fieldset><div id="cc_hard" role="radiogroup" aria-labelledby="cch_label" aria-describedby="cch_descr">
											<label><input type="radio" id="cc_pos" name="st_cc_hard" role="radio" <?php checked( 0, $taxonomy['st_cc_hard'], true ); ?> value="0" onclick="cchSel(event, 0)"><?php esc_html_e( 'When user cannot change terms give notification message but allow changes (notification at start of edit)', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<label><input type="radio" id="cc_sft" name="st_cc_hard" role="radio" <?php checked( 1, $taxonomy['st_cc_hard'], true ); ?> value="1" onclick="cchSel(event, 1)"><?php esc_html_e( 'When the post is saved', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<label><input type="radio" id="cc_hrd" name="st_cc_hard" role="radio" <?php checked( 2, $taxonomy['st_cc_hard'], true ); ?> value="2" onclick="cchSel(event, 2)"><?php esc_html_e( 'As terms are changed and when the post is saved', 'simple-taxonomy-refreshed' ); ?></label><br/>
											</div></fieldset><span id="cch_descr" class="description">
											<p><?php esc_html_e( 'Choose the control level to be applied.', 'simple-taxonomy-refreshed' ); ?></p>
											<p><?php esc_html_e( 'Notification option allows a user who can edit the post but cannot change the terms attached to make other updates.', 'simple-taxonomy-refreshed' ); ?></p>
											<p><?php esc_html_e( 'Other options will block the user from making updates if the number of terms are not within required limits.', 'simple-taxonomy-refreshed' ); ?></p></p>
											<p><?php esc_html_e( 'NOTE. The option to apply the control as terms are entered is a Work in Progress.', 'simple-taxonomy-refreshed' ); ?></p>
											<p><?php esc_html_e( 'Specifically issues may occur with Block Editor post types and their notifications.', 'simple-taxonomy-refreshed' ); ?></p></span>
										</td>
									</tr>
									<tr>
										<th scope="row"><label><?php esc_html_e( 'Minimum Control', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
										<?php
											self::option_yes_no(
												$taxonomy,
												'st_cc_umin',
												esc_html__( 'Use minimum number of terms', 'simple-taxonomy-refreshed' ),
												esc_html__( 'Is there to be a control on the minimum number of terms.', 'simple-taxonomy-refreshed' )
											);
										?>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="st_cc_min"><?php esc_html_e( 'Minimum number of Terms', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
										<input name="st_cc_min" type="number" id="st_cc_min" onchange="checkMinMax(event)" value="<?php echo esc_attr( $taxonomy['st_cc_min'] ); ?>" class="regular-number" min="0" />
										<span class="description"><?php esc_html_e( 'Select the minimum number of terms that can be attached to a post.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
									<tr>
										<th scope="row"><label><?php esc_html_e( 'Maximum Control', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
										<?php
											self::option_yes_no(
												$taxonomy,
												'st_cc_umax',
												esc_html__( 'Use maximum number of terms', 'simple-taxonomy-refreshed' ),
												esc_html__( 'Is there to be a control on the maximum number of terms.', 'simple-taxonomy-refreshed' )
											);
										?>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="st_cc_max"><?php esc_html_e( 'Maximum number of Terms', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
										<input name="st_cc_max" type="number" id="st_cc_max" onchange="checkMinMax(event)" value="<?php echo esc_attr( $taxonomy['st_cc_max'] ); ?>" class="regular-number"  min="0" />
										<span class="description"><?php esc_html_e( 'Select the maximum number of terms that can be attached to a post.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
								</table>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<p class="submit" style="padding:0 0 1.5em;">
				<button type="submit" class="button-primary" name="submit" id="submit" role="button" tabindex="0"
				<?php
				if ( false === $edit ) {
					echo ' disabled';
				}
				echo '>' . esc_attr( $submit_val );
				?>
				</button>
			</p>
		</form>
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function(evt) {
				str_admin_init();
				hideSel(evt, <?php echo esc_attr( $taxonomy['st_cb_type'] ); ?>)
				ccSel(evt, <?php echo esc_attr( $taxonomy['st_cc_type'] ); ?>)
				cchSel(evt, <?php echo esc_attr( $taxonomy['st_cc_hard'] ); ?>)
				switchMinMax(evt);
				document.getElementById("st_cc_umin").addEventListener('change', event => {
					switchMinMax(evt);
				});
				document.getElementById("st_cc_umax").addEventListener('change', event => {
					switchMinMax(evt);
				});
				document.getElementById("st_update_count_callback").addEventListener('change', event => {
					hideCnt(evt);
				});
				document.getElementById("st_update_count_callback").addEventListener("keydown",function(e){
					if(e.keyCode == 32){
						e.preventDefault();
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Check $_POST datas for add/merge taxonomy
	 *
	 * @return boolean
	 */
	private static function check_merge_taxonomy() {
		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( isset( $_POST['action'] ) && in_array( wp_unslash( $_POST['action'] ), array( 'add-taxonomy', 'merge-taxonomy', 'merge-external' ), true ) ) {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You cannot edit the Simple Taxonomy Refreshed options.', 'simple-taxonomy-refreshed' ) );
			}

			// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
			$action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
			// Clean values from _POST.
			$simple   = true;
			$taxonomy = array();
			foreach ( SimpleTaxonomyRefreshed_Client::get_taxonomy_default_fields() as $field => $default_value ) {
				if ( 'merge-external' === $action && ! array_key_exists( $field, $_POST ) ) {
					// Don't create non-existing fields for external taxonomies.
					continue;
				}
				// phpcs:ignore  WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput
				$post_field = ( array_key_exists( $field, $_POST ) ? wp_unslash( $_POST[ $field ] ) : '' );
				if ( isset( $post_field ) ) {
					$taxonomy[ $field ] = '';
					if ( is_string( $post_field ) ) {// String ?
						if ( in_array( $field, array( 'st_before', 'st_sep', 'st_after' ), true ) ) {
							// can contain html.
							$taxonomy[ $field ] = wp_kses_post( $post_field );
							$simple             = false;
						} else {
							$taxonomy[ $field ] = sanitize_text_field( trim( stripslashes( $post_field ) ) );
						}
					} elseif ( is_array( $post_field ) ) {
						$taxonomy[ $field ] = array();
						foreach ( $post_field as $k => $_v ) {
							$taxonomy[ $field ][ sanitize_text_field( $k ) ] = sanitize_text_field( $_v );
						}
					} else {
						$taxonomy[ $field ] = sanitize_text_field( $post_field );
					}
				}
			}

			// Retrive st_ep_mask value. User cannot set the input value.
			$taxonomy['st_ep_mask'] = 0;
			// phpcs:ignore  WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput
			$post_field = ( array_key_exists( 'st_ep_mask_s', $_POST ) ? wp_unslash( $_POST['st_ep_mask_s'] ) : array() );
			foreach ( $post_field as $k => $_v ) {
				$taxonomy['st_ep_mask'] = $taxonomy['st_ep_mask'] + $_v;
			}

			if ( 'merge-taxonomy' === $action && empty( $taxonomy['name'] ) ) {
				wp_die( esc_html__( 'You are trying to edit a taxonomy without name. Impossible !', 'simple-taxonomy-refreshed' ) );
			}

			if ( ! empty( $taxonomy['name'] ) ) { // Label exist ?
				// Values exist ? or build it from label ?
				$taxonomy['name'] = ( empty( $taxonomy['name'] ) ) ? $taxonomy['labels']['name'] : $taxonomy['name'];

				// Clean sanitize value.
				$taxonomy['name'] = sanitize_title( $taxonomy['name'] );

				// Allow plugin to filter data...
				/**
				 *
				 * Filter to modify the taxonomy option data structure.
				 *
				 * This is just before it is written to the options data table.
				 *
				 * @param array $taxonomy Taxonomy data structure
				 */
				$taxonomy = apply_filters( 'staxo_check_merge', $taxonomy );

				// N.B. add_taxonomy and update_taxonomy functions do not return, so put any terminating logic in them.
				if ( 'add-taxonomy' === $action ) {
					check_admin_referer( 'staxo_add_taxo' );
					if ( taxonomy_exists( $taxonomy['name'] ) ) { // Default Taxo already exist ?
						wp_die( esc_html__( 'You are trying to add a taxonomy with a name already used by another taxonomy.', 'simple-taxonomy-refreshed' ) );
					}
					self::add_taxonomy( $taxonomy );
				} elseif ( 'merge-taxonomy' === $action ) {
					check_admin_referer( 'staxo_edit_taxo' );
					self::update_taxonomy( $taxonomy );
				} else {
					check_admin_referer( 'staxo_edit_taxo' );
					self::update_external( $taxonomy );
				}

				// Won't actually get here.
				return true;
			} else {
				add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'Impossible to add your taxonomy... You must enter a taxonomy name.', 'simple-taxonomy-refreshed' ), 'error' );
			}
		}

		return false;
	}

	/**
	 * Allow to export registration CPT with PHP
	 *
	 * @since 1.0.0
	 */
	private static function check_export_taxonomy() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && isset( $_GET['taxonomy_name'] ) && 'export_php' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			// Get proper taxo name.
			$taxonomy_name = sanitize_text_field( wp_unslash( $_GET['taxonomy_name'] ) );
			$taxo_name     = stripslashes( $taxonomy_name );

			// check nonce.
			check_admin_referer( 'staxo_export_php-' . $taxonomy_name );

			// Get taxo data.
			$current_options = get_option( OPTION_STAXO );
			if ( ! isset( $current_options['taxonomies'][ $taxo_name ] ) ) { // Taxo not exist ?
				wp_die( esc_html__( "You are trying to output a taxonomy that doesn't exist...", 'simple-taxonomy-refreshed' ) );
				return false;
			}

			$taxo_data = $current_options['taxonomies'][ $taxo_name ];

			// Get proper args.
			$args = SimpleTaxonomyRefreshed_Client::prepare_args( $taxo_data );

			// Get args to code.
			if ( is_array( $taxo_data['objects'] ) && ! empty( $taxo_data['objects'] ) ) {
				// phpcs:ignore
				$taxo_cpt = var_export( $taxo_data['objects'], true );
			} else {
				$taxo_cpt = 'null';
			}
			// phpcs:ignore
			$code = $taxo_cpt . ",\n  " . var_export( $args, true ) . ' );';

			$output = implode(
				"\n",
				array(
					'<?php',
					'/*',
					'Plugin Name: XXX - %TAXO_LABEL%',
					'Version: x.y.z',
					'Plugin URI: http://www.example.com',
					'Description: XXX - Taxonomy %TAXO_LABEL%',
					'Author: XXX - Simple Taxonomy Refreshed Generator',
					'Author URI: http://www.example.com',
					'',
					'----',
					'',
					'Copyright %TAXO_YEAR% - XXX-Author',
					'',
					'This program is free software; you can redistribute it and/or modify',
					'it under the terms of the GNU General Public License as published by',
					'the Free Software Foundation; either version 3 of the License, or',
					'(at your option) any later version.',
					'',
					'This program is distributed in the hope that it will be useful,',
					'but WITHOUT ANY WARRANTY; without even the implied warranty of',
					'MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the',
					'GNU General Public License for more details.',
					'',
					'You should have received a copy of the GNU General Public License',
					'along with this program; if not, write to the Free Software',
					'Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA',
					'*/',
					'',
					"add_action( 'init', 'register_staxo_%TAXO_NAME%', 10 );",
					'',
					'function register_staxo_%TAXO_NAME%() {',
					'register_taxonomy( "' . $taxo_data['name'] . '", ',
					'  %TAXO_CODE%',
					'}',
				)
			);

			// Replace marker by variables ($code has line feeds).
			$output = str_replace( '%TAXO_YEAR%', gmdate( 'Y' ), $output );
			$output = str_replace( '%TAXO_LABEL%', $args['labels']['name'], $output );
			$output = str_replace( '%TAXO_NAME%', str_replace( '-', '_', $taxo_name ), $output );
			$output = str_replace( '%TAXO_CODE%', $code, $output );

			// Set display as comment as not in parameters.
			if ( ! array_key_exists( 'st_before', $taxo_data ) ) {
				$taxo_data['st_before'] = '';
			}
			if ( ! array_key_exists( 'st_sep', $taxo_data ) ) {
				$taxo_data['st_sep'] = ', ';
			}
			if ( ! array_key_exists( 'st_after', $taxo_data ) ) {
				$taxo_data['st_after'] = '';
			}
			$display  = "\n" . '// ' . esc_html__( 'Display Terms with Posts', 'simple-taxonomy-refreshed' ) . ': ';
			$display .= ( 'both' === $taxo_data['auto'] ? 'content, excerpt' : $taxo_data['auto'] );
			$display .= "\n" . '// ' . esc_html__( 'Display Terms Before text', 'simple-taxonomy-refreshed' ) . ': ' . $taxo_data['st_before'];
			$display .= "\n" . '// ' . esc_html__( 'Display Terms Separator', 'simple-taxonomy-refreshed' ) . ': ' . $taxo_data['st_sep'];
			$display .= "\n" . '// ' . esc_html__( 'Display Terms After text', 'simple-taxonomy-refreshed' ) . ': ' . $taxo_data['st_after'];
			$display .= "\n" . '// ' . esc_html__( 'Show Terms in Feeds', 'simple-taxonomy-refreshed' ) . ': ' . $taxo_data['st_feed'];

			$output .= $display . "\n";

			if ( array_key_exists( 'st_adm_types', $taxo_data ) && ! empty( $taxo_data['st_adm_types'] ) ) {
				// output admin filter parameters.
				$display = "\n" . '/**' . esc_html__( 'Admin List screens for these post type(s) will have a filter list dropdown:', 'simple-taxonomy-refreshed' );
				foreach ( $taxo_data['st_adm_types'] as $post_type ) {
					$display .= "\n  " . $post_type;
				}
				$display .= "\n" . esc_html__( 'with wp_dropdown_categories parameters:', 'simple-taxonomy-refreshed' );

				// phpcs:ignore
				$taxo_filter = SimpleTaxonomyRefreshed_Client::prepare_filter_args( $taxo_data );
				// modify selected back to text version; not value.
				$taxo_filter['selected'] = 'filter_input( INPUT_GET, \'' . $taxo_data['query_var'] . '\', FILTER_SANITIZE_STRING )';
				// phpcs:ignore
				$exp = var_export( $taxo_filter, true );
				$exp = str_replace( "'filter_input", 'filter_input', $exp );
				$exp = str_replace( "_STRING )'", '_STRING )', $exp );

				$output .= "\n" . $display . "\n" . $exp . "\n" . '**/' . "\n";
			}

			if ( array_key_exists( 'st_cb_type', $taxo_data ) && ! empty( $taxo_data['st_cb_type'] ) ) {
				$display = "\n" . '/**' . esc_html__( 'Term count callback modified.', 'simple-taxonomy-refreshed' ) . "\n";
				if ( ! empty( $args['update_count_callback'] ) ) {
					$display .= esc_html__( 'N.B. Callback parameter update_count_callback set, so will be ineffective', 'simple-taxonomy-refreshed' ) . "\n";
				}
				$display .= esc_html__( 'Applies to posts with status: ', 'simple-taxonomy-refreshed' );
				if ( 1 === (int) $taxo_data['st_cb_type'] ) {
					$display .= esc_html__( 'All except trash', 'simple-taxonomy-refreshed' );
				} else {
					$display .= ( true === (bool) $taxo_data['st_cb_pub'] ? "\n " . esc_html__( 'Published', 'simple-taxonomy-refreshed' ) : '' );
					$display .= ( true === (bool) $taxo_data['st_cb_fut'] ? "\n " . esc_html__( 'Future', 'simple-taxonomy-refreshed' ) : '' );
					$display .= ( true === (bool) $taxo_data['st_cb_dft'] ? "\n " . esc_html__( 'Default', 'simple-taxonomy-refreshed' ) : '' );
					$display .= ( true === (bool) $taxo_data['st_cb_pnd'] ? "\n " . esc_html__( 'Pending', 'simple-taxonomy-refreshed' ) : '' );
					$display .= ( true === (bool) $taxo_data['st_cb_prv'] ? "\n " . esc_html__( 'Private', 'simple-taxonomy-refreshed' ) : '' );
					$display .= ( true === (bool) $taxo_data['st_cb_tsh'] ? "\n " . esc_html__( 'Trash', 'simple-taxonomy-refreshed' ) : '' );
				}
				$output .= $display . "\n" . '**/' . "\n";
			}

			if ( array_key_exists( 'st_cc_type', $taxo_data ) && ! empty( $taxo_data['st_cc_type'] ) ) {
				$display = "\n" . '/**' . esc_html__( 'Terms control parameters set.', 'simple-taxonomy-refreshed' ) . "\n";
				// output all post types parameters.
				if ( ! array_key_exists( 'st_cc_types', $taxo_data ) || empty( $taxo_data['st_cc_types'] ) ) {
					$display .= esc_html__( 'Applies to all valid post type(s)', 'simple-taxonomy-refreshed' );
				} else {
					$display .= esc_html__( 'Applies to these post type(s)', 'simple-taxonomy-refreshed' );
					foreach ( $taxo_data['st_cc_types'] as $post_type ) {
						$display .= "\n  " . $post_type;
					}
				}
				$display .= "\n" . esc_html__( 'Applies to posts with status:', 'simple-taxonomy-refreshed' );
				if ( 1 === (int) $taxo_data['st_cc_type'] ) {
					$display .= '  ' . esc_html__( 'Published and Future only', 'simple-taxonomy-refreshed' );
				} else {
					$display .= '  ' . esc_html__( 'All statuses except Trash.', 'simple-taxonomy-refreshed' );
				}

				if ( 0 === (int) $taxo_data['st_cc_hard'] ) {
					$hard = esc_html__( 'Notifications only if outside bounds will be given and user cannot change terms.', 'simple-taxonomy-refreshed' );
				} elseif ( 1 === (int) $taxo_data['st_cc_hard'] ) {
					$hard = esc_html__( 'Hard tests. I.e. Controls will apply during Form Editing and on saving.', 'simple-taxonomy-refreshed' );
				} else {
					$hard = esc_html__( 'Soft tests. I.e. Controls will apply on saving.', 'simple-taxonomy-refreshed' );
				}

				if ( true === (bool) $taxo_data['st_cc_umin'] ) {
					// translators: %d is the minimum number of terms.
					$min = esc_html( sprintf( __( 'Minimum number of terms set to %d.', 'simple-taxonomy-refreshed' ), $taxo_data['st_cc_min'] ) );
				} else {
					$min = esc_html__( 'No minimum number of terms.', 'simple-taxonomy-refreshed' );
				}

				if ( true === (bool) $taxo_data['st_cc_umax'] ) {
					// translators: %d is the minimum number of terms.
					$max = esc_html( sprintf( __( 'Maximum number of terms set to %d.', 'simple-taxonomy-refreshed' ), $taxo_data['st_cc_max'] ) );
				} else {
					$max = esc_html__( 'No maximum number of terms.', 'simple-taxonomy-refreshed' );
				}

				$output .= $display . "\n\n" . $hard . "\n\n" . $min . "\n" . $max . "\n" . '**/' . "\n";
			}

			// No cache.
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + ( 24 * 60 * 60 ) ) . ' GMT' );
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
			header( 'Cache-Control: no-store, no-cache, must-revalidate' );
			header( 'Cache-Control: post-check=0, pre-check=0', false );
			header( 'Pragma: no-cache' );

			// Force download.
			header( 'Content-Disposition: attachment; filename=' . $taxo_name . '.php' );
			header( 'Content-Type: application/force-download' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Type: application/download' );
			header( 'Content-Description: File Transfer' );
			flush(); // this doesn't really matter.

			// phpcs:ignore  WordPress.Security.EscapeOutput
			die( $output . "\n" );
		}

		return false;
	}

	/**
	 * Check $_GET datas for delete a taxonomy
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	private static function check_delete_taxonomy() {
		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && isset( $_GET['taxonomy_name'] ) && 'delete' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			$taxonomy_name = sanitize_text_field( wp_unslash( $_GET['taxonomy_name'] ) );

			check_admin_referer( 'staxo_delete_' . $taxonomy_name );

			$taxonomy         = array();
			$taxonomy['name'] = stripslashes( $taxonomy_name );
			self::delete_taxonomy( $taxonomy, false );

			// Flush rewriting rules !
			flush_rewrite_rules( false );

			return true;
		} elseif ( isset( $_GET['action'] ) && isset( $_GET['taxonomy_name'] ) && 'flush-delete' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			$taxonomy_name = sanitize_text_field( wp_unslash( $_GET['taxonomy_name'] ) );
			check_admin_referer( 'staxo_flush_delete-' . $taxonomy_name );

			$taxonomy         = array();
			$taxonomy['name'] = stripslashes( $taxonomy_name );
			self::delete_taxonomy( $taxonomy, true );

			return true;
		}

		return false;
	}

	/**
	 * Add taxonomy in options
	 *
	 * @since 1.0.0
	 *
	 * @param array $taxonomy  taxonomy name.
	 * @return void
	 */
	private static function add_taxonomy( $taxonomy ) {
		$current_options = get_option( OPTION_STAXO );
		$staxo           = $taxonomy['name'];

		if ( isset( $current_options['taxonomies'][ $staxo ] ) ) { // User taxo already exist ?
			wp_die( esc_html__( 'You are trying to add a taxonomy with a name already used by an another taxonomy.', 'simple-taxonomy-refreshed' ) );
		}
		// Before saving, remove taxonomy labels that are default.
		global $strc;
		foreach ( $taxonomy['labels'] as $key => $label ) {
			if ( array_key_exists( $key, $strc::$wp_decoded_labels[0] ) && $strc::$wp_decoded_labels[0][ $key ] === $label ) {
				unset( $taxonomy['labels'][ $key ] );
			} elseif ( array_key_exists( $key, $strc::$wp_decoded_labels[1] ) && $strc::$wp_decoded_labels[1][ $key ] === $label ) {
				unset( $taxonomy['labels'][ $key ] );
			}
		}

		$current_options['taxonomies'][ $staxo ] = $taxonomy;

		update_option( OPTION_STAXO, $current_options, true );

		// Force cache refresh.
		wp_cache_delete( 'staxo_own_taxos', '' );
		wp_cache_delete( 'staxo_orderings' );

		global $strc;
		$cntl_post_types = $strc::refresh_term_cntl_cache();

		if ( (bool) $taxonomy['rewrite'] ) {
			// Unfortunately we cannot register the new taxonomy and refresh rules here, so create transient data.
			set_transient( 'simple_taxonomy_refreshed_rewrite', true, 0 );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::ADMIN_SLUG ) . '&message=added&staxo=' . $staxo );
		exit();
	}

	/**
	 * Update taxonomy in options.
	 *
	 * @since 1.0.0
	 *
	 * @param array $taxonomy  taxonomy name.
	 * @return void
	 */
	private static function update_taxonomy( $taxonomy ) {
		$current_options = get_option( OPTION_STAXO );
		$staxo           = $taxonomy['name'];

		if ( ! isset( $current_options['taxonomies'][ $staxo ] ) ) { // Taxo not exist ?
			wp_die( esc_html__( 'You are trying to edit a taxonomy with a name different as original. Use the Rename Slug function for this.', 'simple-taxonomy-refreshed' ) );
		}

		// Is there a change of rewrite rules involved in this update?
		$old_tax = $current_options['taxonomies'][ $staxo ];
		if ( (bool) $old_tax['rewrite'] ) {
			// this plugin has a specific element, old one uses query_var.
			$old_slug = ( array_key_exists( 'st_slug', $old_tax ) ? $old_tax['st_slug'] : $old_tax['query_var'] );
		} else {
			$old_slug = '!impossible!';
		}
		if ( (bool) $taxonomy['rewrite'] ) {
			$new_slug = ( empty( $taxonomy['st_slug'] ) ? $staxo : $taxonomy['st_slug'] );
		} else {
			$new_slug = '!impossible!';
		}

		// Before saving, remove taxonomy labels that are default.
		global $strc;
		foreach ( $taxonomy['labels'] as $key => $label ) {
			if ( array_key_exists( $key, $strc::$wp_decoded_labels[0] ) && $strc::$wp_decoded_labels[0][ $key ] === $label ) {
				unset( $taxonomy['labels'][ $key ] );
			} elseif ( array_key_exists( $key, $strc::$wp_decoded_labels[1] ) && $strc::$wp_decoded_labels[1][ $key ] === $label ) {
				unset( $taxonomy['labels'][ $key ] );
			}
		}

		// remove some possible inconsistencies.
		if ( 2 !== (int) $taxonomy['st_cb_type'] ) {
			$taxonomy['st_cb_pub'] = 0;
			$taxonomy['st_cb_fut'] = 0;
			$taxonomy['st_cb_dft'] = 0;
			$taxonomy['st_cb_pnd'] = 0;
			$taxonomy['st_cb_prv'] = 0;
			$taxonomy['st_cb_tsh'] = 0;
		}

		$current_options['taxonomies'][ $staxo ] = $taxonomy;

		update_option( OPTION_STAXO, $current_options, true );

		// Force cache refresh.
		wp_cache_delete( 'staxo_own_taxos', '' );
		wp_cache_delete( 'staxo_orderings' );

		global $strc;
		$cntl_post_types = $strc::refresh_term_cntl_cache();

		// Clear cache if there.
		delete_transient( 'staxo_sel_' . $staxo );

		if ( $new_slug !== $old_slug ) {
			// Change in rewrite rules - Flush !
			// Ensure taxonomy entries updated before any flush.
			unregister_taxonomy( $staxo );
			if ( '!impossible!' !== $old_slug ) {
				// remove old slug from rewrite rules.
				flush_rewrite_rules( false );
			}
			if ( '!impossible!' !== $new_slug ) {
				// Unfortunately we cannot register the new taxonomy and refresh rules here, so create transient data.
				set_transient( 'simple_taxonomy_refreshed_rewrite', true, 0 );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::ADMIN_SLUG ) . '&message=updated&staxo=' . $staxo );
		exit();
	}

	/**
	 * Update external taxonomy in options.
	 *
	 * @since 2.0.0
	 *
	 * @param array $taxonomy  taxonomy name.
	 * @return void
	 */
	private static function update_external( $taxonomy ) {
		$current_options = get_option( OPTION_STAXO );
		$staxo           = $taxonomy['name'];

		// Remove added detail from external taxonomy definition.
		unset( $taxonomy['labels'] );
		unset( $taxonomy['objects'] );
		unset( $taxonomy['st_ep_mask'] );
		unset( $taxonomy['st_update_count_callback'] );

		// remove some possible inconsistencies.
		if ( 2 !== (int) $taxonomy['st_cb_type'] ) {
			$taxonomy['st_cb_pub'] = 0;
			$taxonomy['st_cb_fut'] = 0;
			$taxonomy['st_cb_dft'] = 0;
			$taxonomy['st_cb_pnd'] = 0;
			$taxonomy['st_cb_prv'] = 0;
			$taxonomy['st_cb_tsh'] = 0;
		}

		$current_options['externals'][ $staxo ] = $taxonomy;

		update_option( OPTION_STAXO, $current_options, true );

		// Force cache refresh.
		wp_cache_delete( 'staxo_own_taxos', '' );
		wp_cache_delete( 'staxo_orderings' );

		global $strc;
		$cntl_post_types = $strc::refresh_term_cntl_cache();

		// Clear cache if there.
		delete_transient( 'staxo_sel_' . $staxo );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::ADMIN_SLUG ) . '&message=updated&staxo=' . $staxo );
		exit();
	}

	/**
	 * Delete a taxonomy, and optionally flush contents.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $taxonomy        taxonomy name.
	 * @param boolean $flush_relations whether to delete the object relations/terms.
	 * @return void
	 */
	private static function delete_taxonomy( $taxonomy, $flush_relations = false ) {
		$current_options = get_option( OPTION_STAXO );
		$staxo           = $taxonomy['name'];

		if ( isset( $current_options['taxonomies'][ $staxo ] ) ) {
			// custom taxonomy.
			unset( $current_options['taxonomies'][ $staxo ] ); // Delete from options.

			$opt = 'deleted';
			if ( true === $flush_relations ) {
				// Delete object relations/terms.
				self::delete_objects_taxonomy( $staxo );
				$opt = 'flush-deleted';
			}

			// Flush rewriting rules !
			flush_rewrite_rules( false );

			// remove from any orderings.
			if ( isset( $current_options['list_order'] ) && is_array( $current_options['list_order'] ) ) {
				$lists = $current_options['list_order'];
				foreach ( $lists as $pt => $list ) {
					// remove the taxonomy if found.
					if ( in_array( $staxo, $lists[ $pt ], true ) ) {
						unset( $lists[ $pt ][ $staxo ] );
					}
					// is a list still needed.
					if ( count( $list ) < 2 ) {
							unset( $lists[ $pt ] );
					}
				}
				$current_options['list_order'] = $lists;
			}
		} elseif ( isset( $current_options['externals'][ $staxo ] ) ) {
			// external taxonomy.
			$opt = 'deleted';
			unset( $current_options['externals'][ $staxo ] ); // Delete from options.
		} else {
			// Taxo not exist ?
			wp_die( esc_html__( 'You are trying to delete a taxonomy that does not exist.', 'simple-taxonomy-refreshed' ) );
			exit();
		}

		update_option( OPTION_STAXO, $current_options, true );

		// Force cache refresh.
		wp_cache_delete( 'staxo_own_taxos', '' );
		wp_cache_delete( 'staxo_orderings' );

		global $strc;
		$cntl_post_types = $strc::refresh_term_cntl_cache();

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::ADMIN_SLUG ) . '&message=' . $opt . '&staxo=' . $staxo );
		exit();
	}

	/**
	 * Delete all relationship between objects and terms for a specific taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxo_name taxonomy name.
	 * @return boolean
	 */
	private static function delete_objects_taxonomy( $taxo_name = '' ) {
		if ( empty( $taxo_name ) ) {
			return false;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxo_name,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		if ( false === $terms || is_wp_error( $terms ) ) {
			return false;
		}

		foreach ( (array) $terms as $term ) {
			wp_delete_term( $term, $taxo_name );
		}

		return true;
	}

	/**
	 * Check if an existing post has too few or too many taxonomy entries.
	 *
	 * Also possibly inject change to checkbox to radio.
	 *
	 * @since 1.2.0
	 */
	public static function check_posts_outside_limits() {
		global $post, $current_screen;

		// make sure that we're looking at a post.
		if ( isset( $post->ID ) && 'post' === $current_screen->base ) {
			self::check_posts_outside_limits_post();
			return;
		}

		// process edit list screen (may or may not be a specific post).
		if ( 'edit' === $current_screen->base ) {
			self::check_posts_outside_limits_edit();
			return;
		}
	}

	/**
	 * Check the edit screen for too few or too many taxonomy entries.
	 *
	 * Also possibly inject change to checkbox to radio.
	 *
	 * @since 3.4.0
	 */
	private static function check_posts_outside_limits_edit() {
		global $post, $current_screen;

		// get the post type.
		$post_type = $current_screen->post_type;

		// find out which checks are needed. Note no specific post available here.
		global $strc;
		$cntl_post_types = $strc::refresh_term_cntl_cache( false );
		if ( isset( $cntl_post_types[ $post_type ] ) ) {
			// there are controls for this post_type.
			foreach ( $cntl_post_types[ $post_type ] as $tax => $cntl ) {
				// get user capabilities.
				$tax_obj     = get_taxonomy( $tax );
				$user_manage = current_user_can( $tax_obj->cap->manage_terms );
				$user_change = current_user_can( $tax_obj->cap->assign_terms );

				// is it available on quick edit.
				if ( ! $tax_obj->show_in_quick_edit ) {
					continue;
				}

				// get the terms and count them.
				$label = $tax_obj->labels->name;

				// get min/max flags.
				$vmn = (bool) $cntl['st_cc_umin'];
				$min = ( $vmn ? (int) $cntl['st_cc_min'] : null );
				$vmx = (bool) $cntl['st_cc_umax'];
				$max = ( $vmx ? (int) $cntl['st_cc_max'] : null );

				// error on which post statuses.
				$pstat = (int) $cntl['st_cc_type'];

				// should we change checkbox to a radio button.
				if ( (bool) $tax_obj->hierarchical && 1 === $max ) {
					$min_r = ( is_null( $min ) ? 0 : $min );
					self::script_radio_edit( $tax, $label, $pstat, $min_r, (bool) $tax_obj->hierarchical, $cntl['no_term'] );
					// if set to radio then next test not needed.
					continue;
				}

				// should hard limits apply.
				if ( 2 === (int) $cntl['st_cc_hard'] && $user_change ) {
					self::hard_term_limits_edit( $tax, $label, $pstat, $min, $max, (bool) $tax_obj->hierarchical, $cntl['no_term'] );
				}
			}
		}
	}

	/**
	 * Check if an existing post has too few or too many taxonomy entries.
	 *
	 * Also possibly inject change to checkbox to radio.
	 *
	 * @since 3.4.0
	 */
	private static function check_posts_outside_limits_post() {
		global $post, $current_screen;

		// get the post type.
		$post_type = $post->post_type;

		// set control for (current) statuses.
		if ( in_array( $post->post_status, array( 'new', 'auto-draft', 'trash' ), true ) ) {
			$status = 0;
		} elseif ( in_array( $post->post_status, array( 'publish', 'future' ), true ) ) {
			$status = 1;
		} else {
			$status = 2;
		}

		// find out which checks are needed.
		global $strc;
		$cntl_post_types = $strc::refresh_term_cntl_cache( false );
		if ( isset( $cntl_post_types[ $post_type ] ) ) {
			// there are controls for this post_type.
			foreach ( $cntl_post_types[ $post_type ] as $tax => $cntl ) {
				// get user capabilities.
				$tax_obj     = get_taxonomy( $tax );
				$user_manage = current_user_can( $tax_obj->cap->manage_terms );
				$user_change = current_user_can( $tax_obj->cap->assign_terms );

				// determine type of error to report going into process.
				$stat = (int) $cntl['st_cc_type'];
				if ( ( 2 === $stat && $status > 0 ) || ( 1 === $stat && 1 === $status ) ) {
					$err_msg = 'error';
				} else {
					$err_msg = 'warning';
				}

				// get the terms and count them.
				$label = $tax_obj->labels->name;
				$terms = get_the_terms( $post->ID, $tax );
				if ( false === $terms ) {
					$num_terms = 0;
				} elseif ( $terms instanceof WP_Error ) {
					$num_terms = 0;
				} else {
					$num_terms = count( $terms );
				}
				// set min/max flags.
				$vmn = (bool) $cntl['st_cc_umin'];
				$min = ( $vmn ? (int) $cntl['st_cc_min'] : null );
				$vmx = (bool) $cntl['st_cc_umax'];
				$max = ( $vmx ? (int) $cntl['st_cc_max'] : null );

				// Put out saving error message as current may be erroneous.
				if ( $status > 0 ) {
					$err_notice = false;
					// check minimum if test is needed.
					if ( $vmn && $num_terms < $min ) {
						if ( 'add' === $current_screen->action ) {
							// New document.
							$err = $err_msg;
							// translators: %1$s is the taxonomy label name; %2$d is the required minimum number of terms.
							$text_1 = sprintf( __( 'The number of terms for taxonomy (%1$s) needs to be at least %2$d.', 'simple-taxonomy-refreshed' ), $label, $min );
							$text_3 = '';
							if ( $user_change ) {
								$text_2 = __( 'Please review and add additional terms.', 'simple-taxonomy-refreshed' );
							} else {
								$err    = 'warning';
								$text_2 = __( 'For information as you cannot add them.', 'simple-taxonomy-refreshed' );
								if ( $cntl['st_cc_hard'] > 0 ) {
									$text_3 = __( 'N.B. You will not be able to save any changes!', 'simple-taxonomy-refreshed' );
								}
							}
						} else {
							$err = $err_msg;
							// translators: %1$s is the taxonomy label name; %2$d is the required minimum number of terms.
							$text_1 = sprintf( __( 'The number of terms for taxonomy (%1$s) is less than the required minimum number %2$d.', 'simple-taxonomy-refreshed' ), $label, $min );
							$text_3 = '';
							if ( $user_change ) {
								$text_2 = __( 'Please review and add additional terms before trying to save.', 'simple-taxonomy-refreshed' );
							} else {
								$err    = 'warning';
								$text_2 = __( 'For information as you cannot add them.', 'simple-taxonomy-refreshed' );
								if ( $cntl['st_cc_hard'] > 0 ) {
									$text_3 = __( 'N.B. You will not be able to save any changes!', 'simple-taxonomy-refreshed' );
								}
							}
						}
						if ( self::is_block_editor() ) {
							// Show in rest needed and hard error will put out message later.
							if ( $cntl['show_in_rest'] && $cntl['st_cc_hard'] < 2 ) {
								// phpcs:disable Squiz.Strings.DoubleQuoteUsage
								$script =
									"( function( wp ) { " . PHP_EOL .
									"	wp.data.dispatch( 'core/notices' ).createNotice(" . PHP_EOL .
									"  '" . $err . "'," . PHP_EOL .
									"  '" . $text_1 . '  ' . $text_2 . '  ' . $text_3 . "'," . PHP_EOL .
									"  { isDismissible: true, id: 'str_notice_{$tax}' }" . PHP_EOL .
									" );" . PHP_EOL .
									"} )( window.wp );" . PHP_EOL .
									"window.onload = function() {" . PHP_EOL .
									" var sub = document.getElementsByClassName('edit-post-header__settings');" . PHP_EOL .
									" if (sub.length > 0) {" . PHP_EOL .
									"  sub[0].addEventListener('click', event => {" . PHP_EOL .
									"	  wp.data.dispatch( 'core/notices' ).removeNotice( 'str_notice_{$tax}' );" . PHP_EOL .
									"  });" . PHP_EOL .
									" };" . PHP_EOL .
									"};";
								// phpcs:enable Squiz.Strings.DoubleQuoteUsage
								wp_add_inline_script( 'staxo_placeholder', $script, 'after' );
							}
						} else {
							$err_notice = true;
							?>
							<div><p>&nbsp;</p></div>
							<div class="notice notice-<?php echo esc_html( $err ); ?>" id="err-<?php echo esc_html( $tax ); ?>"><p>
							<?php
							echo esc_html( $text_1 . '  ' . $text_2 . '  ' . $text_3 );
							?>
							</div>
							<?php
						}
					}

					// check maximum if test is needed.
					if ( $vmx && $num_terms > $max ) {
						$err = $err_msg;
						// translators: %1$s is the taxonomy label; %2$d is the required maximum number of terms.
						$text_1 = sprintf( __( 'The number of terms for taxonomy (%1$s) is greater than the required maximum number %2$d.', 'simple-taxonomy-refreshed' ), $label, $max );
						$text_3 = '';
						if ( $user_change ) {
							$text_2 = __( 'Please review and remove terms before trying to save.', 'simple-taxonomy-refreshed' );
						} else {
							$err    = 'warning';
							$text_2 = __( 'For information as you cannot remove them.', 'simple-taxonomy-refreshed' );
							if ( $cntl['st_cc_hard'] > 0 ) {
								$text_3 = __( 'N.B. You will not be able to save any changes!', 'simple-taxonomy-refreshed' );
							}
						}
						if ( self::is_block_editor() ) {
							// Show in rest needed and hard error will put out message later.
							if ( $cntl['show_in_rest'] && $cntl['st_cc_hard'] < 2 ) {
								// phpcs:disable Squiz.Strings.DoubleQuoteUsage
								$script =
									'( function( wp ) { ' . PHP_EOL .
									"	wp.data.dispatch( 'core/notices' ).createNotice(" . PHP_EOL .
									"  '" . $err . "'," . PHP_EOL .
									"  '" . $text_1 . '  ' . $text_2 . '  ' . $text_3 . "'," . PHP_EOL .
									"  { isDismissible: true, id: 'str_notice_{$tax}' }" . PHP_EOL .
									" );" . PHP_EOL .
									"} )( window.wp );" . PHP_EOL .
									"window.onload = function() {" . PHP_EOL .
									" var sub = document.getElementsByClassName('edit-post-header__settings');" . PHP_EOL .
									" if (sub.length > 0) {" . PHP_EOL .
									"  sub[0].addEventListener('click', event => {" . PHP_EOL .
									"	  wp.data.dispatch( 'core/notices' ).removeNotice( 'str_notice_{$tax}' );" . PHP_EOL .
									"  });" . PHP_EOL .
									" };" . PHP_EOL .
									"};";
								// phpcs:enable Squiz.Strings.DoubleQuoteUsage
								wp_add_inline_script( 'staxo_placeholder', $script, 'after' );
							}
						} else {
							$err_notice = true;
							?>
							<div class="notice notice-<?php echo esc_html( $err ); ?>" id="err-<?php echo esc_html( $tax ); ?>"><p>
							<?php
							echo esc_html( $text_1 . '  ' . $text_2 . '  ' . $text_3 );
							?>
							</p></div>
							<?php
						}
					}
					// put out hidden error notice in case of error being created.
					if ( ! $err_notice && ! self::is_block_editor() ) {
						?>
						<div class="notice notice-error hidden" id="err-<?php echo esc_html( $tax ); ?>">
						<p></p></div>
						<?php
					}
				}

				// post status control type.
				$pstat = (int) $cntl['st_cc_type'];
				// should we change checkbox to a radio button.
				// (Not over limit, hierarchical, min and max limits exist and set to 1).
				if ( $num_terms < 2 && (bool) $tax_obj->hierarchical && 1 === $max ) {
					$cntl_type = (int) $cntl['st_cc_hard'];
					// for radio, force min to be 0 or 1.
					$min_r = ( is_null( $min ) ? 0 : $min );
					self::script_radio( $tax, $label, $pstat, $min_r, true, $cntl['no_term'] );
					// if we converted to radio and there is already one term, then it always is in limits (non-block only).
					if ( 1 === $num_terms && ! self::is_block_editor() ) {
						continue;
					}
				}
				// should hard limits apply.
				if ( 2 === (int) $cntl['st_cc_hard'] && $user_change ) {
					global $post;
					$stat = $post->post_status;
					$parm = self::term_limits_push( $tax, $label, $pstat, $min, $max, true, $cntl['no_term'], $stat );
					self::enqueue_client_libs();
					if ( self::is_block_editor() ) {
						// Block editor is the same. N.B. This should be called elsewhere.
						$funct = 'block_limit( window.wp, ';
					} elseif ( (bool) $tax_obj->hierarchical ) {
						$funct = 'dom_hier_cntl_check(';
					} else {
						$funct = 'dom_tag_cntl_check(';
					}
					wp_add_inline_script(
						'staxo_client',
						$parm . 'document.addEventListener("DOMContentLoaded", ' . $funct . ' "' . $tax . '" ));'
					);
				}
			}
		}
	}

	/**
	 * Output the javascript to change the taxonomy display to use radio buttons on quick edit screens.
	 *
	 * @since 3.4.0
	 *
	 * @param string $tax_name  The taxonomy name.
	 * @param string $tax_label The taxonomy label name.
	 * @param int    $pstat     Post status control type.
	 * @param int    $min_bound minimum number of terms (null if no minimum).
	 * @param bool   $hier      taxonomy is hierarchical.
	 * @param string $nt_label  The taxonomy label name for No term.
	 */
	private static function script_radio_edit( $tax_name, $tax_label, $pstat, $min_bound, $hier, $nt_label ) {
		global $post;
		if ( is_null( $post ) || ! isset( $post->post_status ) ) {
			return;
		}
		$stat = $post->post_status;
		// for radio, force min to be 0 or 1.
		$min_r = ( is_null( $min_bound ) ? 0 : $min_bound );
		$text  = self::term_limits_push( $tax_name, $tax_label, $pstat, $min_r, 1, $hier, $nt_label, $stat );
		self::enqueue_client_libs();
		wp_add_inline_script(
			'staxo_client',
			$text . 'document.addEventListener("DOMContentLoaded", dom_qe_radio_client( "' . $tax_name . '" ));'
		);
	}

	/**
	 * Output the javascript to change the taxonomy display to use radio buttons on post screens.
	 *
	 * @since 1.2.0
	 *
	 * @param string $tax_name  The taxonomy slug.
	 * @param string $tax_label The taxonomy label.
	 * @param int    $pstat     Post status control type.
	 * @param int    $min_bound minimum number of terms (0 or 1).
	 * @param bool   $hier      Whether taxonomy is hierarchical.
	 * @param string $nt_label  The taxonomy label name for No term.
	 */
	private static function script_radio( $tax_name, $tax_label, $pstat, $min_bound, $hier, $nt_label ) {
		// Logic is that there are two tabs for the taxonomy - all and popular.
		// Only one category must be selected, so radio is appropriate.
		// All will contain all options; popular may be available, but may be incomplete.
		// List of all will be changed to radio. Popular will be converted (but for compatability).
		// If any item in either list is clicked, then the corresponding entry in other list is set.
		// Every other value is unset.
		// Generally we're not bothered about the status with radio buttons.
		// But if there are existing multiple ones, then we need to leave as checkboxes.

		if ( self::is_block_editor() ) {
			// not yet supported.
			// look for stuff within editor-post-taxonomies__hierarchical-terms-list with aria-label of the Taxonomy Name.
			null;
		} else {
			global $post;
			if ( is_null( $post ) || ! isset( $post->post_status ) ) {
				return;
			}
			$stat = $post->post_status;
			$text = self::term_limits_push( $tax_name, $tax_label, $pstat, $min_bound, 1, $hier, $nt_label, $stat );

			self::enqueue_client_libs();
			wp_add_inline_script(
				'staxo_client',
				$text . 'document.addEventListener("DOMContentLoaded", dom_radio_client( "' . $tax_name . '" ))  ;'
			);
		}
	}

	/**
	 * Output the scripting to check the taxonomy limits for taxonomies for quick edit screen as they are being entered.
	 *
	 * @since 3.4.0
	 *
	 * @param string $tax_name  taxonomy name.
	 * @param string $tax_label taxonomy label name.
	 * @param int    $pstat     post status control type.
	 * @param int    $min_bound minimum number of terms (null if no minimum).
	 * @param int    $max_bound maximum number of terms (null if no maximum).
	 * @param bool   $hier      Whether taxonomy is hierarchical.
	 * @param string $nt_label  The taxonomy label name for No term.
	 */
	private static function hard_term_limits_edit( $tax_name, $tax_label, $pstat, $min_bound, $max_bound, $hier, $nt_label ) {
		$text = self::term_limits_push( $tax_name, $tax_label, $pstat, $min_bound, $max_bound, $hier, $nt_label );
		self::enqueue_client_libs();
		wp_add_inline_script(
			'staxo_client',
			$text . 'document.addEventListener("DOMContentLoaded", dom_qe_cntl_check( "' . $tax_name . '", ' . (int) $hier . ' ));'
		);
	}

	/**
	 * Create the tax_cntl push javascript text for term limits.
	 *
	 * @since 3.0.0
	 *
	 * @param string $tax_name  Taxonomy name.
	 * @param string $tax_label Taxonomy label name.
	 * @param int    $pstat     post status control type.
	 * @param int    $min_bound minimum number of terms (null if no minimum).
	 * @param int    $max_bound maximum number of terms (null if no maximum).
	 * @param bool   $hier      Whether taxonomy is hierarchical.
	 * @param string $nt_label  The taxonomy label name for No term.
	 * @param string $status    post status.
	 */
	private static function term_limits_push( $tax_name, $tax_label, $pstat, $min_bound, $max_bound, $hier, $nt_label, $status = '' ) {
		$lock = ( 1 === $pstat ? esc_html__( 'Publishing is blocked.', 'simple-taxonomy-refreshed' ) : esc_html__( 'Saving is blocked.', 'simple-taxonomy-refreshed' ) );
		if ( is_null( $min_bound ) ) {
			$mib  = 'null';
			$less = '';
		} else {
			// can be set to zero when the intent that it is set as 1 (i.e. Show No terms.).
			$mib = esc_html( $min_bound );
			// translators: %1$s is the taxonomy label name; %2$d is the required minimum number of terms.
			$less  = esc_html( sprintf( __( 'The number of terms for taxonomy (%1$s) is less than the required minimum number %2$d.', 'simple-taxonomy-refreshed' ), $tax_label, max( $min_bound, 1 ) ) );
			$less .= ' ' . $lock;
		}
		if ( is_null( $max_bound ) ) {
			$mab  = 'null';
			$more = '';
		} else {
			$mab = esc_html( $max_bound );
			// translators: %1$s is the taxonomy label name; %2$d is the required maximum number of terms.
			$more  = esc_html( sprintf( __( 'The number of terms for taxonomy (%1$s) is greater than the required maximum number %2$d.', 'simple-taxonomy-refreshed' ), $tax_label, $max_bound ) );
			$more .= ' ' . $lock;
		}
		$no_terms = ( isset( $nt_label ) ? $nt_label : __( 'No term', 'simple-taxonomy-refreshed' ) );
		return 'tax_cntl.push( [ "' . $tax_name . '", ' . $pstat . ', ' . $mib . ', "' . $less . '", ' . $mab . ', "' . $more . '", ' . (int) $hier . ', "' . $nt_label . '", "' . $status . '" ] );' . "\n";
	}

	/**
	 * Check that the necessary taxonomy term(s) is entered for the post.
	 *
	 * Invoked *before* post is inserted/updated.
	 *
	 * @since 1.2.0
	 *
	 * @param bool  $maybe_empty Whether the post should be considered "empty".
	 * @param array $postarr     Array of post data.
	 */
	public static function check_taxonomy_value_set( $maybe_empty, $postarr ) {
		// ignore whilst doing autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $maybe_empty;
		}

		// status checks. Ignore new, auto-draft and trash, and when the title is empty.
		if ( in_array( $postarr['post_status'], array( 'new', 'auto-draft', 'trash' ), true ) ) {
			return $maybe_empty;
		}
		if ( empty( $postarr['post_title'] ) && post_type_supports( $postarr['post_type'], 'title' ) ) {
			return $maybe_empty;
		}
		$post_status = ( 'inherit' === $postarr['post_status'] ? get_post_status( $postarr['post_parent'] ) : $postarr['post_status'] );
		// No post object available, use the arrays.
		// find out which checks are needed.
		global $strc;
		$cntl_post_types = $strc::refresh_term_cntl_cache( false );
		if ( isset( $cntl_post_types[ $postarr['post_type'] ] ) ) {
			// there are controls for this post_type.
			foreach ( $cntl_post_types[ $postarr['post_type'] ] as $tax => $cntl ) {
				// check the post_status (trash already excluded so all cc_type 2 need processing).
				if ( 1 === $cntl['st_cc_type'] && ! in_array( $postarr['post_status'], array( 'publish', 'future' ), true ) ) {
					continue;
				}

				$error_type = '';
				// count the number of terms.
				$terms_count = 0;
				if ( isset( $postarr['tax_input'][ $tax ] ) ) {
					$terms = $postarr['tax_input'][ $tax ];
					// ignore the 0 element (hierarchical).
					if ( 0 === $terms[0] ) {
						unset( $terms[0] );
					}
					// remove the No Terms value. (Ensure at beginning).
					if ( isset( $terms[1] ) && -1 === $terms[1] ) {
						unset( $terms[1] );
					}
					$terms_count = ( empty( $terms ) ? 0 : count( $terms ) );
				}

				// check the minimum bound.
				if ( true === (bool) $cntl['st_cc_umin'] && $terms_count < $cntl['st_cc_min'] ) {
					$error_type = 'min';
				}
				// check the maximum bound.
				if ( true === (bool) $cntl['st_cc_umax'] && $terms_count > $cntl['st_cc_max'] ) {
					$error_type .= 'max';
				}

				// commen error path.
				if ( '' !== $error_type ) {
					if ( isset( $postarr['_inline_edit'] ) ) {
						// Quickedit.
						$tax_label = get_taxonomy( $tax )->labels->name;
						if ( 'min' === $error_type ) {
							// translators: %1$s is the taxonomy label name; %2$d is the required minimum number of terms.
							echo esc_html( sprintf( __( 'The number of terms for taxonomy (%1$s) is less than the required minimum number %2$d.', 'simple-taxonomy-refreshed' ), $tax_label, $cntl['st_cc_min'] ) );
						} else {
							// translators: %1$s is the taxonomy label name; %2$d is the required maximum number of terms.
							echo esc_html( sprintf( __( 'The number of terms for taxonomy (%1$s) is greater than the required maximum number %2$d.', 'simple-taxonomy-refreshed' ), $tax_label, $cntl['st_cc_max'] ) );
						}
						wp_die();
					}
					$referer = ( isset( $postarr['_wp_http_referer'] ) ? $postarr['_wp_http_referer'] : '' );
					if ( empty( $referer ) && isset( $_SERVER['REQUEST_URI'] ) ) {
						$referer = esc_attr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
					}
					$url = add_query_arg(
						array(
							'post'        => $postarr['ID'],
							'action'      => 'edit',
							'staxo_tax'   => $tax,
							'staxo_error' => $error_type,
							'staxo_terms' => wp_create_nonce( 'terms' ),
						),
						get_home_url( null, $referer )
					);

					if ( wp_safe_redirect( $url ) ) {
						exit;
					}
				}
			}
		}

		return $maybe_empty;
	}

	/**
	 * Filters a post before it is inserted via the REST API to check terms control.
	 *
	 * @since 1.3.0
	 *
	 * @param stdClass        $prepared_post An object representing a single post prepared
	 *                                       for inserting or updating the database.
	 * @param WP_REST_Request $request       Request object.
	 */
	public function check_taxonomy_value_rest( $prepared_post, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// previous filter has invalidated it.
		if ( is_wp_error( $prepared_post ) ) {
			return $prepared_post;
		}

		// determine post_status.
		if ( isset( $prepared_post->post_status ) ) {
			$post_status = $prepared_post->post_status;
		} else {
			$post = get_post( $prepared_post->ID );
			if ( false === $post ) {
				$post_status = 'new';
			} else {
				$post_status = $post->post_status;
			}
		}

		// status checks. Ignore new, auto-draft and trash.
		if ( in_array( $post_status, array( 'new', 'auto-draft', 'trash' ), true ) ) {
			return $prepared_post;
		}

		// it would be wonderful to be able to read $request, but object is entirely protected.
		$json = file_get_contents( 'php://input' );
		$data = json_decode( $json, true );

		// find out which checks are needed.
		global $strc;
		$cntl_post_types = $strc::refresh_term_cntl_cache( false );
		if ( isset( $cntl_post_types[ $prepared_post->post_type ] ) ) {
			// there are controls for this post_type.
			foreach ( $cntl_post_types[ $prepared_post->post_type ] as $tax => $cntl ) {
				// check the post_status (trash already excluded so all cc_type 2 need processing).
				if ( 1 === $cntl['st_cc_type'] && ! in_array( $post_status, array( 'publish', 'future' ), true ) ) {
					continue;
				}

				if ( ! $cntl['show_in_rest'] ) {
					continue;
				}

				// count the number of terms.
				if ( isset( $data[ $cntl['rest_base'] ] ) ) {
					$terms_count = ( empty( $data[ $cntl['rest_base'] ] ) ? 0 : count( $data[ $cntl['rest_base'] ] ) );
				} else {
					$terms_count = 0;
				}

				// check the minimum bound.
				if ( true === (bool) $cntl['st_cc_umin'] && $terms_count < $cntl['st_cc_min'] ) {
					return new WP_Error(
						'rest_minimum_terms',
						// translators: %s is the taxonomy label name.
						sprintf( __( 'Not enough terms entered for Taxonomy "%s"', 'simple-taxonomy-refreshed' ), $cntl['label_name'] ),
						array( 'status' => 403 )
					);
				}
				// check the maximum bound.
				if ( true === (bool) $cntl['st_cc_umax'] && $terms_count > $cntl['st_cc_max'] ) {
					return new WP_Error(
						'rest_maximum_terms',
						// translators: %s is the taxonomy label name.
						sprintf( __( 'Too many terms entered for Taxonomy "%s"', 'simple-taxonomy-refreshed' ), $cntl['label_name'] ),
						array( 'status' => 403 )
					);
				}
			}
		}

		return $prepared_post;
	}

	/**
	 * Function to determine whether the page is being rendered by Block editor.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function block_editor_active() {
		self::$use_block_editor = true;
	}

	/**
	 * Function to return whether the page is being rendered by Block editor.
	 *
	 * @since 2.0.0
	 *
	 * @return boolean
	 */
	public static function is_block_editor() {
		if ( is_null( self::$use_block_editor ) ) {
			$screen = get_current_screen();
			if ( ( ! is_null( $screen ) ) && method_exists( $screen, 'is_block_editor' ) ) {
				self::$use_block_editor = $screen->is_block_editor();
			} elseif ( function_exists( 'use_block_editor_for_post' ) ) {
				global $post;
				self::$use_block_editor = use_block_editor_for_post( $post );
			} elseif ( function_exists( 'is_gutenberg_page' ) ) {
				self::$use_block_editor = is_gutenberg_page();
			} else {
				self::$use_block_editor = false;
			}
		}
		return self::$use_block_editor;
	}

	/**
	 * Use for build admin taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key  index into object types.
	 * @return array
	 */
	private static function get_object_types( $key = '' ) {

		// Get all post types registered.
		$object_types = get_post_types( array( 'public' => true ), 'objects' );

		/*
		 *
		 * Filters the list of Public Post Types to assign the Taxonomies.
		 *
		 * @param array  $object_types Public post types.
		 * @param string $key          Object selector.
		 */
		$object_types = apply_filters( 'staxo_object_types', $object_types, $key );
		if ( isset( $object_types[ $key ] ) ) {
			return $object_types[ $key ];
		}

		return $object_types;
	}

	/**
	 * Use for build selector - convert number to string.
	 *
	 * @param string $key    index into true/false type.
	 * @param string $dfault optional default value.
	 * @return string/array
	 */
	private static function get_true_false( $key = '', $dfault = null ) {
		$types = array(
			'0' => __( 'False', 'simple-taxonomy-refreshed' ),
			'1' => __( 'True', 'simple-taxonomy-refreshed' ),
		);

		if ( isset( $types[ $key ] ) ) {
			return $types[ $key ];
		}

		if ( is_null( $dfault ) ) {
			return $types;
		}
		return $types[ $dfault ];
	}

	/**
	 * Use for build selector auto terms.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key  index into content type.
	 * @return array|string
	 */
	private static function get_auto_content_types( $key = '' ) {
		$content_types = array(
			'none'    => __( 'None', 'simple-taxonomy-refreshed' ),
			'content' => __( 'Content', 'simple-taxonomy-refreshed' ),
			'excerpt' => __( 'Excerpt', 'simple-taxonomy-refreshed' ),
			'both'    => __( 'Content and excerpt', 'simple-taxonomy-refreshed' ),
		);

		/*
		 *
		 * Filters the list of auto-extract options.
		 *
		 * @param array  $content_types Default auto-extract options
		 * @param string $key           Content selector.
		 */
		$content_types = apply_filters( 'staxo_auto_content_types', $content_types, $key );
		if ( isset( $content_types[ $key ] ) ) {
			return $content_types[ $key ];
		}

		return $content_types;
	}
}

