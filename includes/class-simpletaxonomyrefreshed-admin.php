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
	const ADMIN_SLUG = 'simple-taxonomy-settings';

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
	 * Protected Constructor
	 *
	 * @return void
	 */
	final protected function __construct() {
		// Register hooks.
		add_action( 'activity_box_end', array( __CLASS__, 'activity_box_end' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		// check existing posts outside limits.
		add_action( 'admin_notices', array( __CLASS__, 'check_posts_outside_limits' ) );

		// called if block editor to render screen.
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'block_editor_active' ) );

		// help text.
		add_action( 'load-settings_page_simple-taxonomy-settings', array( __CLASS__, 'add_help_tab' ) );

		// called at admin_init, load all check functions.
		self::check_merge_taxonomy();
		self::check_delete_taxonomy();
		self::check_export_taxonomy();
		self::check_importexport();
	}

	/**
	 * Add custom taxo on dashboard.
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
					foreach ( (array) $options['taxonomies'] as $taxonomy ) :
						$taxo = get_taxonomy( $taxonomy['name'] );
						if ( false === $taxo || is_wp_error( $taxo ) ) {
							continue;
						}
						?>
						<tr>
							<td class="first b b-<?php echo esc_attr( $taxo->name ); ?>"><a href="edit-tags.php?taxonomy=<?php echo esc_attr( $taxo->name ); ?>"><?php echo esc_html( wp_count_terms( $taxo->name ) ); ?></a></td>
							<td class="t <?php echo esc_attr( $taxo->name ); ?>"><a href="edit-tags.php?taxonomy=<?php echo esc_attr( $taxo->name ); ?>"><?php echo esc_attr( $taxo->labels->name ); ?></a></td>
						</tr>
					<?php endforeach; ?>
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
	 * Add settings menu page
	 *
	 * @return void
	 */
	public static function admin_menu() {
		add_options_page( __( 'Simple Taxonomy : Custom Taxonomies', 'simple-taxonomy-refreshed' ), __( 'Custom Taxonomies', 'simple-taxonomy-refreshed' ), 'manage_options', self::ADMIN_SLUG, array( __CLASS__, 'page_manage' ) );
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
		if ( 'settings_page_simple-taxonomy-settings' !== $screen->id ) {
			return;
		}

		// parent key is the id of the current screen
		// child key is the title of the tab
		// value is the help text (as HTML).
		$help = array(
			__( 'Taxonomy List', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'This displays all the Taxonomies managed by this plugin.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'It shows some major properties of the taxonomy - and allows you to extract the code that defines the taxonomy enties.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You can add a new taxonomy in the screen here. Modifying an existing taxonomy will open a new window - with the same fields available.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You can also export all the parameters for all taxonomies or reload them.', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Taxonomy Definition', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'There are very many parameters to control the operation of the Taxonomy and the first seven tabs have been provided to enter them all. Since a major objective of the plugin is to avoid the user having to write code, all these parameters have been made available.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'As these parameters are for underlying WordPress functionality, you should read the WordPress documentation to understand their purpose and action.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You need to enter the Name (slug) and whether it is Hierarchical or not and the post type(s) it will be linked to on the Main Options tab and the Name (label) on the Labels tab. Most of the others can be left as their default value - expect possibly the labels.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'WP 5.5 brings the possibility to add a default term for all objects defined from the taxonomy. This may be entered on the Other tab. The slug and description can also be entered here and used to create the term.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'By default the terms do not appear with the post. The options at the bottom of the Main Options screen allow you to output the attached terms with the post content and/or excerpt information.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'When done, simply click <code>Add Taxonomy</code> or <code>Update Taxonomy</code> to save your changes', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'WPGraphQL', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'Added taxonomy-related functionality.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'These parameters are for sites that have implemented WPGraphQL; and are not relevant if the plugin is not installed.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'They are provided in the spirit of supporting no-coding. There is no requirement for this plugin.', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Admin List Filter', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'Added taxonomy-related functionality.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'The admin screen for post types allows provide an option for these posts to be filtered by various fields. This tab allows these screens to have a filter on this taxonomy simply by ticking a box.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Some taxonomy parameters should be set appropriately for this to be useful. They are noted on the tab.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'This invokes the standard WordPress functionality.', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Term Count', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'Added taxonomy-related functionality.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Standard WordPress functionality provides a count of the posts using a term on the Taxonomy Terms page - and this count is for only Published posts.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You may want this count to include posts with other statuses. You can set this  behaviour on this tab.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'If you have additional non-standard post_statuses that you wish to be included, this can be done, but it will require coding and use of the filter \'staxo_term_count_statuses\'.', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Term Control', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'Added taxonomy-related functionality.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Standard WordPress functionality provides no limits on the number of terms that can be attached to a post.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'This functionality provides this control. Using this tab, upper and lower bounds may be set for either Published posts or all posts.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'The tests will be applied at save post time (soft);  or also when the terms are added or removed (hard).', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Thus a user who can edit a post may not be able to add or remove terms but can be notified of the issue and make other updates.', 'simple-taxonomy-refreshed' ) . '</p>',
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

		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
			'<p><a href="https://github.com/NeilWJames/simple-taxonomy-refreshed/" target="_blank">' . __( 'Github Project page', 'simple-taxonomy-refreshed' ) . '</a></p>' .
			'<p><a href="https://wordpress.org/support/plugin/simple-taxonomy-refreshed/" target="_blank">' . __( 'WP Support Forum', 'simple-taxonomy-refreshed' ) . '</a></p>'
		);
	}

	/**
	 * Allow to display only form.
	 *
	 * @param array $taxonomy  taxonomy name.
	 * @return void
	 */
	public static function page_form( $taxonomy ) {
		?>
		<div class="wrap">
			<h2>
			<?php
			// translators: %s is the taxonomy name.
			echo esc_html( sprintf( __( 'Custom Taxonomy : %s', 'simple-taxonomy-refreshed' ), stripslashes( $taxonomy['labels']['name'] ) ) );
			?>
			</h2>

			<div class="form-wrap">
				<?php self::form_merge_custom_type( $taxonomy ); ?>
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
		<tr valign="top">
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
	 * @param string $descr    sanitzed description field.
	 * @return void
	 */
	private static function option_text( &$taxonomy, $name, $label, $descr ) {
		// Sanitize the data before calling.
		// phpcs:disable  WordPress.Security.EscapeOutput
		?>
		<tr valign="top">
			<th scope="row"><label for="<?php echo $name; ?>"><?php echo $label; ?></label></th>
			<td>
				<input name="<?php echo $name; ?>" type="text" id="<?php echo $name; ?>" value="<?php echo esc_attr( $taxonomy[ $name ] ); ?>" class="regular-text" />
				<span class="description"><?php echo $descr; ?></span>
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
	 * @param string $descr    sanitzed description field.
	 * @return void
	 */
	private static function option_label( &$taxonomy, $name, $label, $descr ) {
		// Expect to sanitize the data before calling.
		// phpcs:disable  WordPress.Security.EscapeOutput
		?>
		<tr valign="top">
			<th scope="row"><label for="labels-<?php echo $name; ?>"><?php echo $label; ?></label></th>
			<td>
				<input name="labels[<?php echo $name; ?>]" type="text" id="labels-<?php echo $name; ?>" value="<?php echo esc_attr( $taxonomy['labels'][ $name ] ); ?>" class="regular-text" />
				<?php
				if ( '' !== $descr ) {
					echo '<span class="description">' . $descr . '</span>';
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
	 * @param string $descr    sanitzed description field.
	 * @return void
	 */
	private static function option_cap( &$taxonomy, $name, $label, $descr ) {
		// Sanitize the data before calling.
		// phpcs:disable  WordPress.Security.EscapeOutput
		?>
		<tr valign="top">
			<th scope="row"><label for="<?php echo $name; ?>"><?php echo $label; ?></label></th>
			<td>
				<input name="capabilities[<?php echo $name; ?>]" type="text" id="<?php echo $name; ?>" value="<?php echo esc_attr( $taxonomy['capabilities'][ $name ] ); ?>" class="regular-text" />
				<span class="description"><?php echo $descr; ?></span>;
			</td>
		</tr>
		<?php
		// phpcs:enable  WordPress.Security.EscapeOutput
	}


	/**
	 * Display options on admin
	 *
	 * @return void
	 */
	public static function page_manage() {
		// Admin URL.
		$admin_url = admin_url( 'options-general.php?page=' . self::ADMIN_SLUG );

		// Get current options.
		$current_options = get_option( OPTION_STAXO );
		// Check get for message.
		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['message'] ) ) {
			switch ( $_GET['message'] ) { // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
				case 'flush-deleted':
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'Taxonomy and relations deleted with success !', 'simple-taxonomy-refreshed' ), 'updated' );
					break;
				case 'deleted':
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'Taxonomy deleted with success !', 'simple-taxonomy-refreshed' ), 'updated' );
					break;
				case 'added':
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'Taxonomy added with success !', 'simple-taxonomy-refreshed' ), 'updated' );
					break;
				case 'updated':
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'Taxonomy updated with success !', 'simple-taxonomy-refreshed' ), 'updated' );
					break;
			}
		}

		// Display message.
		settings_errors( 'simple-taxonomy-refreshed' );

		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && isset( $_GET['taxonomy_name'] ) && 'edit' === $_GET['action'] && isset( $current_options['taxonomies'][ $_GET['taxonomy_name'] ] ) ) {
			self::page_form( $current_options['taxonomies'][ sanitize_text_field( wp_unslash( $_GET['taxonomy_name'] ) ) ] );  // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
			return;
		}
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Simple Taxonomy : Custom Taxonomies', 'simple-taxonomy-refreshed' ); ?></h2>

			<div class="message updated">
				<p>
				<?php
				// phpcs:ignore  WordPress.Security.EscapeOutput
				wp_kses( _e( '<strong>Warning :</strong> Flush & Delete a taxonomy will also delete all terms of the taxonomy and all object relations.', 'simple-taxonomy-refreshed' ), array( 'strong' ) );
				?>
				</p>
			</div>

			<div id="col-container">
				<table class="widefat tag fixed" cellspacing="0">
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

					<tbody id="the-list" class="list:taxonomies">
						<?php
						if ( false === $current_options || empty( $current_options['taxonomies'] ) ) {
							echo '<tr><td colspan="3">' . esc_html__( 'No custom taxonomy.', 'simple-taxonomy-refreshed' ) . '</td></tr>';
						} else {
							$class = 'alternate';
							$i     = 0;
							foreach ( (array) $current_options['taxonomies'] as $_t_name => $_t ) :
								$i++;
								$class = ( 'alternate' === $class ) ? '' : 'alternate';
								// phpcs:disable  WordPress.Security.EscapeOutput
								// translators: %s is the taxonomy name.
								$edit_msg = esc_html( sprintf( __( "Edit the taxonomy '%s'", 'simple-taxonomy-refreshed' ), $_t['labels']['name'] ) );
								// translators: %s is the taxonomy name.
								$del_msg = esc_js( sprintf( __( "You are about to delete this taxonomy '%s'\n  'Cancel' to stop, 'OK' to delete.", 'simple-taxonomy-refreshed' ), $_t['labels']['name'] ) );
								// translators: %s is the taxonomy name.
								$dfl_msg = esc_js( sprintf( __( "You are about to delete and flush this taxonomy '%s' and all relations.\n  'Cancel' to stop, 'OK' to delete.", 'simple-taxonomy-refreshed' ), $_t['labels']['name'] ) );
								// phpcs:enable  WordPress.Security.EscapeOutput
								?>
								<tr id="taxonomy-<?php echo esc_attr( $i ); ?>" class="<?php esc_attr( $class ); ?>">
									<td class="name column-name">
										<strong><a class="row-title" href="<?php echo esc_url( $admin_url ); ?>&amp;action=edit&amp;taxonomy_name=<?php echo esc_attr( $_t_name ); ?>" title="<?php esc_attr( $edit_msg ); ?>"><?php echo esc_html( stripslashes( $_t['labels']['name'] ) ); ?></a></strong>
										<br />
										<div class="row-actions">
											<span class="edit"><a href="<?php echo esc_url( $admin_url ); ?>&amp;action=edit&amp;taxonomy_name=<?php echo esc_attr( $_t_name ); ?>"><?php esc_html_e( 'Modify', 'simple-taxonomy-refreshed' ); ?></a> | </span>
											<span class="export"><a class="export_php-taxonomy" href="<?php echo esc_url( wp_nonce_url( esc_url( $admin_url ) . '&amp;action=export_php&amp;taxonomy_name=' . esc_attr( $_t_name ), 'staxo-export_php-' . $_t_name ) ); ?>"><?php esc_html_e( 'Export PHP', 'simple-taxonomy-refreshed' ); ?></a> | </span>
											<span class="delete"><a class="delete-taxonomy" href="<?php echo esc_url( wp_nonce_url( esc_url( $admin_url ) . '&amp;action=delete&amp;taxonomy_name=' . $_t_name, 'staxo-delete-' . esc_attr( $_t_name ) ) ); ?>" onclick="if ( confirm( '<?php echo esc_html( $del_msg ); ?>' ) ) { return true;}return false;"><?php esc_html_e( 'Delete', 'simple-taxonomy-refreshed' ); ?></a> | </span>
											<span class="delete"><a class="flush-delete-taxonomy" href="<?php echo esc_url( wp_nonce_url( esc_url( $admin_url ) . '&amp;action=flush-delete&amp;taxonomy_name=' . $_t_name, 'staxo-flush-delete-' . esc_attr( $_t_name ) ) ); ?>" onclick="if ( confirm( '<?php echo esc_html( $dfl_msg ); ?>' ) ) { return true;}return false;"><?php esc_html_e( 'Flush & Delete', 'simple-taxonomy-refreshed' ); ?></a></span>
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
									<td><?php echo esc_html( self::get_true_false( $_t['hierarchical'] ) ); ?></td>
									<td><?php echo esc_html( self::get_true_false( $_t['rewrite'] ) ); ?></td>
									<td><?php echo esc_html( self::get_true_false( $_t['public'] ) ); ?></td>
									<td><?php echo esc_html( self::get_true_false( ( isset( $_t['show_in_rest'] ) ? $_t['show_in_rest'] : 1 ) ) ); ?></td>
								</tr>
								<?php
							endforeach;
						}
						?>
					</tbody>
				</table>

				<br class="clear" />

				<div class="form-wrap">
					<h3><?php esc_html_e( 'Add a new taxonomy', 'simple-taxonomy-refreshed' ); ?></h3>
					<?php self::form_merge_custom_type(); ?>
				</div>
			</div><!-- /col-container -->
		</div>

		<div class="wrap">
			<h2><?php esc_html_e( 'Simple Taxonomy : Export/Import', 'simple-taxonomy-refreshed' ); ?></h2>

			<a class="button" href="<?php echo esc_url( wp_nonce_url( $admin_url . '&amp;action=staxo-export-config', 'staxo-export-config' ) ); ?>"><?php esc_html_e( 'Export config file', 'simple-taxonomy-refreshed' ); ?></a>
			<a class="button" href="#" id="toggle-import_form"><?php esc_html_e( 'Import config file', 'simple-taxonomy-refreshed' ); ?></a>
			<script type="text/javascript">
				jQuery( "#toggle-import_form" ).click(function(event) {
					event.preventDefault();
					jQuery( '#import_form' ).removeClass('hide-if-js');
				});
			</script>
			<div id="import_form" class="hide-if-js">
				<form action="<?php echo esc_url( $admin_url ); ?>" method="post" enctype="multipart/form-data">
					<p>
						<label><?php esc_html_e( 'Config file', 'simple-taxonomy-refreshed' ); ?></label>
						<input type="file" name="config_file" />
					</p>
					<p class="submit">
						<?php wp_nonce_field( 'staxo-import-config-file' ); ?>
						<input class="button-primary" type="submit" name="staxo-import-config-file" value="<?php esc_html_e( 'I want import a config from a previous backup, this action will REPLACE current configuration', 'simple-taxonomy-refreshed' ); ?>" />
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Build HTML for form custom taxonomy, add with list on right column
	 *
	 * @param array $taxonomy  taxonomy name.
	 * @return void
	 */
	private static function form_merge_custom_type( $taxonomy = null ) {
		// Admin URL.
		$admin_url = admin_url( 'options-general.php?page=' . self::ADMIN_SLUG );

		if ( null === $taxonomy ) {
			$taxonomy                 = SimpleTaxonomyRefreshed_Client::get_taxonomy_default_fields();
			$taxonomy['labels']       = SimpleTaxonomyRefreshed_Client::get_taxonomy_default_labels();
			$taxonomy['capabilities'] = SimpleTaxonomyRefreshed_Client::get_taxonomy_default_capabilities();
			$edit                     = false;
			$_action                  = 'add-taxonomy';
			$submit_val               = __( 'Add taxonomy', 'simple-taxonomy-refreshed' );
			$nonce_field              = 'staxo-add-taxo';
		} else {
			$edit        = true;
			$_action     = 'merge-taxonomy';
			$submit_val  = __( 'Update taxonomy', 'simple-taxonomy-refreshed' );
			$nonce_field = 'staxo-edit-taxo';
		}

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

		// If show_in_graphql is true, then set st_ variables from values (may have passed through a filter).
		$taxonomy['st_show_in_graphql'] = ( isset( $taxonomy['show_in_graphql'] ) ? +$taxonomy['show_in_graphql'] : 0 );
		$taxonomy['st_graphql_single']  = ( isset( $taxonomy['graphql_single'] ) ? $taxonomy['graphql_single'] : '' );
		$taxonomy['st_graphql_plural']  = ( isset( $taxonomy['graphql_plural'] ) ? $taxonomy['graphql_plural'] : '' );
		?>

		<style>
		/* Style the tab */
		.tab {
			overflow: hidden;
			border: 1px solid #ccc;
			background-color: #f1f1f1;
		}

		/* Style the buttons inside the tab */
		.tab button {
			background-color: inherit;
			float: left;
			border: none;
			outline: none;
			cursor: pointer;
			padding: 12px 16px;
			transition: 0.3s;
			font-size: 15px;
		}

		/* Change background color of buttons on hover */
		.tab button:hover {
			background-color: #ccc;
		}

		/* Create an active/current tablink class */
		.tab button.active {
			background-color: #ddd;
		}

		/* Style the tab content */
		.tabcontent {
			display: none;
			padding: 6px 12px;
			border: 1px solid #ccc;
			border-top: none;
		}
		</style>

		<form id="addtag" method="post" action="<?php echo esc_url( $admin_url ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_html( $_action ); ?>" />
			<?php wp_nonce_field( $nonce_field ); ?>

			<p><?php esc_html_e( 'Click on the tabs to see all the options and facilities available.', 'simple-taxonomy-refreshed' ); ?></p>

			<p><?php esc_html_e( 'The taxonomy definition options are spread across 7 tabs. The remaining 4 are for integrating the taxonomy.', 'simple-taxonomy-refreshed' ); ?></p>

			<div id="poststuff" class="metabox-holder">
				<div id="post-body-content">
					<div class="tab">
						<button type="button" class="tablinks active" onclick="openTab(event, 'mainopts')"><?php esc_html_e( 'Main Options', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" class="tablinks" onclick="openTab(event, 'visibility')"><?php esc_html_e( 'Visibility', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" class="tablinks" onclick="openTab(event, 'labels')"><?php esc_html_e( 'Labels', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" class="tablinks" onclick="openTab(event, 'rewrite')"><?php esc_html_e( 'Rewrite URL', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" class="tablinks" onclick="openTab(event, 'permissions')"><?php esc_html_e( 'Permissions', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" class="tablinks" onclick="openTab(event, 'rest')"><?php esc_html_e( 'REST', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" class="tablinks" onclick="openTab(event, 'other')"><?php esc_html_e( 'Other', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" class="tablinks" onclick="openTab(event, 'wpgraphql')"><?php esc_html_e( 'WPGraphQL', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" class="tablinks" onclick="openTab(event, 'adm_filter')"><?php esc_html_e( 'Admin List Filter', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" class="tablinks" onclick="openTab(event, 'callback')"><?php esc_html_e( 'Term Count', 'simple-taxonomy-refreshed' ); ?></button>
						<button type="button" class="tablinks" onclick="openTab(event, 'countt')"><?php esc_html_e( 'Term Control', 'simple-taxonomy-refreshed' ); ?></button>
					</div>

					<div id="mainopts" class="meta-box-sortabless tabcontent" style="display: block;">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Main Options', 'simple-taxonomy-refreshed' ); ?></span></h3>

							<div class="inside">
								<table class="form-table" style="clear:none;">
									<tr valign="top">
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
									<tr valign="top">
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
											<span class="description"><?php esc_html_e( "Default <strong>hierarchical</strong> in WordPress are categories. Default post tags WP aren't hierarchical.", 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row"><label><?php esc_html_e( 'Post types', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
											<?php
											if ( true === $edit && is_array( $taxonomy['objects'] ) ) {
												$objects = $taxonomy['objects'];
											} else {
												$objects = array();
											}
											$i = 0;
											foreach ( self::get_object_types() as $type ) {
												echo '<label class="inline">';
												echo '<input type="checkbox" ' . checked( true, in_array( $type->name, $objects, true ), false );
												echo ' onclick="linkAdm(event, ' . esc_attr( $i ) . ')"';
												echo ' name="objects[]" value="' . esc_attr( $type->name ) . '" />';
												echo esc_attr( $type->label ) . '</label>' . "\n";
												$i++;
											}
											?>
											<span class="description"><?php esc_html_e( 'You can add this taxonomy to builtin or custom post types.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
									<tr valign="top">
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
									<?php
										self::option_text(
											$taxonomy,
											'st_before',
											esc_html__( 'Display Terms Before text', 'simple-taxonomy-refreshed' ),
											esc_html__( 'This text will be used before the Post terms display list', 'simple-taxonomy-refreshed' ) . '<br/>' .
											esc_html__( 'The text will be trimmed and a single space output after this.', 'simple-taxonomy-refreshed' )
										);
										self::option_text(
											$taxonomy,
											'st_after',
											esc_html__( 'Display Terms After text', 'simple-taxonomy-refreshed' ),
											esc_html__( 'This text will be used after the Post terms display list', 'simple-taxonomy-refreshed' ) . '<br/>' .
											esc_html__( 'The text will be trimmed and a single space output before this.', 'simple-taxonomy-refreshed' )
										);
									?>
								</table>
							</div>
						</div>
					</div>

					<div id="visibility" class="meta-box-sortabless tabcontent">
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

					<div id="labels" class="meta-box-sortabless tabcontent">
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
									?>
								</table>
							</div>
						</div>
					</div>

					<div id="rewrite" class="meta-box-sortabless tabcontent">
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
									<tr valign="top">
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

					<div id="permissions" class="meta-box-sortabless tabcontent">
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

					<div id="rest" class="meta-box-sortabless tabcontent">
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
											'rest_controller_class',
											esc_html__( 'REST Controller Class', 'simple-taxonomy-refreshed' ),
											esc_html__( "REST API Controller class name. Default is 'WP_REST_Terms_Controller'.", 'simple-taxonomy-refreshed' )
										);
									?>
								</table>
							</div>
						</div>
					</div>

					<div id="other" class="meta-box-sortabless tabcontent">
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

					<div id="wpgraphql" class="meta-box-sortabless tabcontent">
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

					<div id="adm_filter" class="meta-box-sortabless tabcontent">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Admin List Filter', 'simple-taxonomy-refreshed' ); ?></span></h3>

							<div class="inside">
								<table class="form-table" style="clear:none;">
									<p><?php esc_html_e( 'Taxonomy needs to be allocated to Post Types and Publicly Queryable.', 'simple-taxonomy-refreshed' ); ?></p>
									<p><?php esc_html_e( 'Advisable to have a column on the admin list screen.', 'simple-taxonomy-refreshed' ); ?></p>
									<p><?php esc_html_e( '(See Visibility tab for these parameters.)', 'simple-taxonomy-refreshed' ); ?></p>
									<tr valign="top">
										<th scope="row"><label><?php esc_html_e( 'Post types', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
											<?php
											if ( true === $edit ) {
												$objects = $taxonomy['st_adm_types'];
											} else {
												$objects = array();
											}
											$i = 0;
											foreach ( self::get_object_types() as $type ) {
												echo '<label class="inline">';
												echo '<input id="admlist' . esc_attr( $i ) . '" type="checkbox" ' . checked( true, in_array( $type->name, (array) $objects, true ), false );
												if ( ! in_array( $type->name, (array) $taxonomy['objects'], true ) ) {
													echo ' disabled';
												}
												echo ' name="st_adm_types[]" value="' . esc_attr( $type->name ) . '" />';
												echo esc_html( $type->label ) . '</label>' . "\n";
												$i++;
											}
											?>
											<span class="description"><?php esc_html_e( 'You can add this taxonomy as a filter field on the admin list screen for builtin or custom post types linked to this taxonomy.', 'simple-taxonomy-refreshed' ); ?></span>
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

					<div id="callback" class="meta-box-sortabless tabcontent">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Term Count', 'simple-taxonomy-refreshed' ); ?></span></h3>

							<div class="inside">
								<table class="form-table" style="clear:none;">
									<p><?php esc_html_e( 'Term counts are normally based on Published posts. This option provides some no-coding configuration.', 'simple-taxonomy-refreshed' ); ?></p>
									<tr valign="top">
										<th scope="row"><label><?php esc_html_e( 'Count Options', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
											<fieldset>
											<input type="radio" id="cb_std" name="st_cb_type" <?php checked( 0, $taxonomy['st_cb_type'], true ); ?> value="0" onclick="hideSel(event, 0)"><label for="cb_std"><?php esc_html_e( 'Standard (Publish)', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<input type="radio" id="cb_any" name="st_cb_type" <?php checked( 1, $taxonomy['st_cb_type'], true ); ?> value="1" onclick="hideSel(event, 1)"><label for="cb_any"><?php esc_html_e( 'Any (Except Trash)', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<input type="radio" id="cb_sel" name="st_cb_type" <?php checked( 2, $taxonomy['st_cb_type'], true ); ?> value="2" onclick="hideSel(event, 2)"><label for="cb_sel"><?php esc_html_e( 'Selection', 'simple-taxonomy-refreshed' ); ?></label><br/>
											</fieldset>
											<span class="description"><?php esc_html_e( 'Setting Any or Selection needs Update Count Callback NOT to be set.', 'simple-taxonomy-refreshed' ); ?></span>
											<p><?php esc_html_e( '(See Other tab for this parameter.)', 'simple-taxonomy-refreshed' ); ?></p>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row"><label><?php esc_html_e( 'Status Selection', 'simple-taxonomy-refreshed' ); ?></label></th>
											<td>
											<div id="count_sel" style="display: <?php echo ( 2 === (int) $taxonomy['st_cb_type'] ? 'block;' : 'none;' ); ?>">
											<fieldset>
											<input type="checkbox" id="st_cb_pub" name="st_cb_pub" <?php checked( true, (bool) $taxonomy['st_cb_pub'], true ); ?>><label for="st_cb_pub"><?php esc_html_e( 'Publish', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<input type="checkbox" id="st_cb_fut" name="st_cb_fut" <?php checked( true, (bool) $taxonomy['st_cb_fut'], true ); ?>><label for="st_cb_fut"><?php esc_html_e( 'Future', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<input type="checkbox" id="st_cb_dft" name="st_cb_dft" <?php checked( true, (bool) $taxonomy['st_cb_dft'], true ); ?>><label for="st_cb_dft"><?php esc_html_e( 'Draft', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<input type="checkbox" id="st_cb_pnd" name="st_cb_pnd" <?php checked( true, (bool) $taxonomy['st_cb_pnd'], true ); ?>><label for="st_cb_pnd"><?php esc_html_e( 'Pending', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<input type="checkbox" id="st_cb_prv" name="st_cb_prv" <?php checked( true, (bool) $taxonomy['st_cb_prv'], true ); ?>><label for="st_cb_prv"><?php esc_html_e( 'Private', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<input type="checkbox" id="st_cb_tsh" name="st_cb_tsh" <?php checked( true, (bool) $taxonomy['st_cb_tsh'], true ); ?>><label for="st_cb_tsh"><?php esc_html_e( 'Trash', 'simple-taxonomy-refreshed' ); ?></label><br/>
											</fieldset>
											<span class="description"><?php esc_html_e( 'Choose the set of Statuses to be included in Term counts.', 'simple-taxonomy-refreshed' ); ?></span>
											</div>
											</td>
									</tr>
								</table>
							</div>
						</div>
					</div>

					<div id="countt" class="meta-box-sortabless tabcontent">
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Term Control', 'simple-taxonomy-refreshed' ); ?></span></h3>

							<div class="inside">
								<table class="form-table" style="clear:none;">
									<p><?php esc_html_e( 'Term controls are to be applied on posts. This option provides some no-coding configuration.', 'simple-taxonomy-refreshed' ); ?></p>
									<tr valign="top">
										<th scope="row"><label><?php esc_html_e( 'Post status', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
											<fieldset>
											<input type="radio" id="cc_off" name="st_cc_type" <?php checked( 0, $taxonomy['st_cc_type'], true ); ?> value="0" ><label for="cc_off"><?php esc_html_e( 'No control applied', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<input type="radio" id="cc_pub" name="st_cc_type" <?php checked( 1, $taxonomy['st_cc_type'], true ); ?> value="1" ><label for="cc_pub"><?php esc_html_e( 'Published only', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<input type="radio" id="cc_any" name="st_cc_type" <?php checked( 2, $taxonomy['st_cc_type'], true ); ?> value="2" ><label for="cc_any"><?php esc_html_e( 'Any (Except Trash)', 'simple-taxonomy-refreshed' ); ?></label><br/>
											</fieldset>
											<span class="description"><?php esc_html_e( 'Choose  the statuses of posts to apply the control.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row"><label><?php esc_html_e( 'How Control is applied', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
											<fieldset>
											<input type="radio" id="cc_pos" name="st_cc_hard" <?php checked( 0, $taxonomy['st_cc_hard'], true ); ?> value="0" ><label for="cc_pos"><?php esc_html_e( 'When user cannot change terms give notification message but allow changes (notification at start of edit)', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<input type="radio" id="cc_sft" name="st_cc_hard" <?php checked( 1, $taxonomy['st_cc_hard'], true ); ?> value="1" ><label for="cc_sft"><?php esc_html_e( 'When post is saved', 'simple-taxonomy-refreshed' ); ?></label><br/>
											<input type="radio" id="cc_hrd" name="st_cc_hard" <?php checked( 2, $taxonomy['st_cc_hard'], true ); ?> value="2" ><label for="cc_hrd"><?php esc_html_e( 'As terms are changed and when is post saved', 'simple-taxonomy-refreshed' ); ?></label><br/>
											</fieldset>
											<span class="description">
											<p><?php esc_html_e( 'Choose the control level to be applied.', 'simple-taxonomy-refreshed' ); ?></p>
											<p><?php esc_html_e( 'Notification option allows a user who can edit the post but cannot change the terms attached to make other updates.', 'simple-taxonomy-refreshed' ); ?></p>
											<p><?php esc_html_e( 'Other options will block this user from making updates if the number of terms are not within required limits.', 'simple-taxonomy-refreshed' ); ?></p></p>
											<p><?php esc_html_e( 'NOTE. The option to apply the control as terms are entered is incomplete; notably does not currently apply for Gutenberg posts.', 'simple-taxonomy-refreshed' ); ?></p></span>
										</td>
									</tr>
									<tr valign="top">
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
									<tr valign="top">
										<th scope="row"><label for="st_cc_min"><?php esc_html_e( 'Minimum number of Terms', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
										<input name="st_cc_min" type="number" id="st_cc_min" onchange="checkMinMax(event)" value="<?php echo esc_attr( $taxonomy['st_cc_min'] ); ?>" class="regular-number" min="0" />
										<span class="description"><?php esc_html_e( 'Select the minimum number of terms that can be attached to a post.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
									<tr valign="top">
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
									<tr valign="top">
										<th scope="row"><label for="st_cc_max"><?php esc_html_e( 'Maximum number of Terms', 'simple-taxonomy-refreshed' ); ?></label></th>
										<td>
										<input name="st_cc_max" type="number" id="st_cc_max" onchange="checkMinMax(event)" value="<?php echo esc_attr( $taxonomy['st_cc_max'] ); ?>" class="regular-number"  min="0" />
										<span class="description"><?php esc_html_e( 'Select the maximum number of terms that can be attached to a post.', 'simple-taxonomy-refreshed' ); ?></span>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

			<p class="submit" style="padding:0 0 1.5em;">
				<input type="submit" class="button-primary" name="submit" id="submit" value="<?php echo esc_attr( $submit_val ); ?>"
				<?php
				if ( false === $edit ) {
					echo ' disabled';
				}
				?>
				/>
			</p>
		</form>

		<script type="text/javascript">
		function openTab(evt, tabName) {
			var i, tabcontent, tablinks;
			tabcontent = document.getElementsByClassName("tabcontent");
			for (i = 0; i < tabcontent.length; i++) {
				tabcontent[i].style.display = "none";
			}
			tablinks = document.getElementsByClassName("tablinks");
			for (i = 0; i < tablinks.length; i++) {
				tablinks[i].className = tablinks[i].className.replace(" active", "");
			}
			document.getElementById(tabName).style.display = "block";
			if (tabName == "adm_filter") {
				var i = document.getElementById("hierarchical").value;
				document.getElementById("st_adm_hier").disabled = ( i == 0 );
				document.getElementById("st_adm_depth").disabled = ( i == 0 );
			}
			evt.currentTarget.className += " active";
			evt.stopPropagation();
		}
		function checkNameSet(evt) {
			document.getElementById("submit").disabled = ( evt.currentTarget.value.length === 0 );
			evt.stopPropagation();
		}
		function linkAdm(evt, objNo) {
			document.getElementById("admlist" + objNo).disabled = ( evt.currentTarget.checked === false );
			if (evt.currentTarget.checked === false) {
				document.getElementById("admlist" + objNo).checked = false;
			}
			evt.stopPropagation();
		}
		function linkH(evt, objNo) {
			document.getElementById("st_adm_hier").disabled = (objNo === 0);
			document.getElementById("st_adm_depth").disabled = (objNo === 0);
			if (objNo === 0) {
				document.getElementById("st_adm_hier").value = 0;
				document.getElementById("st_adm_depth").value = 0;
			}
			evt.stopPropagation();
		}
		function hideSel(evt, objNo) {
			if (objNo === 2) {
				document.getElementById("count_sel").style.display = "block";
			} else {
				document.getElementById("count_sel").style.display = "none";
			}
			evt.stopPropagation();
		}
		function switchMinMax(evt) {
			var umin = (document.getElementById("st_cc_umin").value == 0);
			var umax = (document.getElementById("st_cc_umax").value == 0);
			document.getElementById("st_cc_min").disabled = umin;
			document.getElementById("st_cc_max").disabled = umax;
			evt.stopPropagation();
		}
		function checkMinMax(evt) {
			var minv = document.getElementById("st_cc_min").value;
			var maxv = document.getElementById("st_cc_max").value;
			if (minv > maxv && evt.currentTarget.id === "st_cc_min") {
				document.getElementById("st_cc_max").value = minv;
			}
			if (minv > maxv && evt.currentTarget.id === "st_cc_max") {
				document.getElementById("st_cc_min").value = maxv;
			}
			evt.stopPropagation();
		}
		document.addEventListener('DOMContentLoaded', function(evt) {
			switchMinMax(evt);
			document.getElementById("st_cc_umin").addEventListener('change', event => {
				switchMinMax(evt);
			});
			document.getElementById("st_cc_umax").addEventListener('change', event => {
				switchMinMax(evt);
			});
		});
		</script>
		<?php
	}

	/**
	 * Check $_GET/$_POST/$_FILES for Export/Import
	 *
	 * @return void
	 */
	private static function check_importexport() {
		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && 'staxo-export-config' === $_GET['action'] ) {
			check_admin_referer( 'staxo-export-config' );

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
			die( 'SIMPLETAXONOMYREFRESHED' . wp_json_encode( get_option( OPTION_STAXO ) ) );
		} elseif ( isset( $_POST['staxo-import-config-file'] ) && isset( $_FILES['config_file'] ) ) {  // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
			check_admin_referer( 'staxo-import-config-file' );

			// phpcs:ignore
			if ( $_FILES['config_file']['error'] > 0 ) {
				add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'An error occured during the config file upload. Please fix your server configuration and retry.', 'simple-taxonomy-refreshed' ), 'error' );
			} else {
				// phpcs:ignore
				$config_file = file_get_contents( $_FILES['config_file']['tmp_name'] );
				if ( 'SIMPLETAXONOMYREFRESHED' !== substr( $config_file, 0, strlen( 'SIMPLETAXONOMYREFRESHED' ) ) ) {
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'This is really a config file for Simple Taxonomy ? Probably corrupt :(', 'simple-taxonomy-refreshed' ), 'error' );
				} else {
					$config_file = json_decode( substr( $config_file, strlen( 'SIMPLETAXONOMYREFRESHED' ) ), true );
					if ( ! is_array( $config_file ) ) {
						add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'This is really a config file for Simple Taxonomy ? Probably corrupt :(', 'simple-taxonomy-refreshed' ), 'error' );
					} else {
						// clear cache (need to do first as values could be different).
						$options = get_option( OPTION_STAXO );
						if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
							foreach ( (array) $options['taxonomies'] as $taxonomy => $tax_data ) {
								wp_cache_delete( 'staxo_sel_' . $taxonomy );
							}
						}
						update_option( OPTION_STAXO, $config_file );
						add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', __( 'OK. Configuration is restored.', 'simple-taxonomy-refreshed' ), 'updated' );
						// Change of file may provoke a change of rewrite rules, so trigger it via transient data.
						set_transient( 'simple_taxonomy_refreshed_rewrite', true, 0 );
					}
				}
			}
		}
	}

	/**
	 * Check $_POST datas for add/merge taxonomy
	 *
	 * @return boolean
	 */
	private static function check_merge_taxonomy() {
		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( isset( $_POST['action'] ) && in_array( wp_unslash( $_POST['action'] ), array( 'add-taxonomy', 'merge-taxonomy' ), true ) ) {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You cannot edit the Simple Taxonomy options.', 'simple-taxonomy-refreshed' ) );
			}

			// Clean values from _POST.
			$taxonomy = array();
			foreach ( SimpleTaxonomyRefreshed_Client::get_taxonomy_default_fields() as $field => $default_value ) {
				// phpcs:ignore  WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput
				$post_field = ( array_key_exists( $field, $_POST ) ? wp_unslash( $_POST[ $field ] ) : '' );
				if ( isset( $post_field ) && is_string( $post_field ) ) {// String ?
					$taxonomy[ $field ] = sanitize_text_field( trim( stripslashes( $post_field ) ) );
				} elseif ( isset( $post_field ) ) {
					if ( is_array( $post_field ) ) {
						$taxonomy[ $field ] = array();
						foreach ( $post_field as $k => $_v ) {
							$taxonomy[ $field ][ sanitize_text_field( $k ) ] = sanitize_text_field( $_v );
						}
					} else {
						$taxonomy[ $field ] = sanitize_text_field( $post_field );
					}
				} else {
					$taxonomy[ $field ] = '';
				}
			}

			// Retrive st_ep_mask value. User cannot set the input value.
			$taxonomy['st_ep_mask'] = 0;
			// phpcs:ignore  WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput
			$post_field = ( array_key_exists( 'st_ep_mask_s', $_POST ) ? wp_unslash( $_POST['st_ep_mask_s'] ) : array() );
			foreach ( $post_field as $k => $_v ) {
				$taxonomy['st_ep_mask'] = $taxonomy['st_ep_mask'] + $_v;
			}

			// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
			if ( 'merge-taxonomy' === $_POST['action'] && empty( $taxonomy['name'] ) ) {
				wp_die( esc_html__( 'Cheating ? You try to edit a taxonomy without name. Impossible !', 'simple-taxonomy-refreshed' ) );
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
				// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
				if ( 'add-taxonomy' === $_POST['action'] ) {
					check_admin_referer( 'staxo-add-taxo' );
					if ( taxonomy_exists( $taxonomy['name'] ) ) { // Default Taxo already exist ?
						wp_die( esc_html__( 'Cheating ? You try to add a taxonomy with a name already used by another taxonomy.', 'simple-taxonomy-refreshed' ) );
					}
					self::add_taxonomy( $taxonomy );
				} else {
					check_admin_referer( 'staxo-edit-taxo' );
					self::update_taxonomy( $taxonomy );
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
	 */
	private static function check_export_taxonomy() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && isset( $_GET['taxonomy_name'] ) && 'export_php' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			// Get proper taxo name.
			$taxonomy_name = sanitize_text_field( wp_unslash( $_GET['taxonomy_name'] ) );
			$taxo_name     = stripslashes( $taxonomy_name );

			// check nonce.
			check_admin_referer( 'staxo-export_php-' . $taxonomy_name );

			// Get taxo data.
			$current_options = get_option( OPTION_STAXO );
			if ( ! isset( $current_options['taxonomies'][ $taxo_name ] ) ) { // Taxo not exist ?
				wp_die( esc_html__( "Cheating, uh ? You try to output a taxonomy that doesn't exist...", 'simple-taxonomy-refreshed' ) );
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
			if ( ! array_key_exists( 'st_after', $taxo_data ) ) {
				$taxo_data['st_after'] = '';
			}
			$display  = "\n" . '// ' . esc_html__( 'Display Terms with Posts', 'simple-taxonomy-refreshed' ) . ': ';
			$display .= ( 'both' === $taxo_data['auto'] ? 'content, excerpt' : $taxo_data['auto'] );
			$display .= "\n" . '// ' . esc_html__( 'Display Terms Before text', 'simple-taxonomy-refreshed' ) . ': ' . $taxo_data['st_before'];
			$display .= "\n" . '// ' . esc_html__( 'Display Terms After text', 'simple-taxonomy-refreshed' ) . ': ' . $taxo_data['st_after'];

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
				$display  = "\n" . '/**' . esc_html__( 'Terms control parameters set.', 'simple-taxonomy-refreshed' ) . "\n";
				$display .= esc_html__( 'Applies to posts with status:', 'simple-taxonomy-refreshed' );
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
	 * @return boolean
	 */
	private static function check_delete_taxonomy() {
		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && isset( $_GET['taxonomy_name'] ) && 'delete' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			$taxonomy_name = sanitize_text_field( wp_unslash( $_GET['taxonomy_name'] ) );

			check_admin_referer( 'staxo-delete-' . $taxonomy_name );

			$taxonomy         = array();
			$taxonomy['name'] = stripslashes( $taxonomy_name );
			self::delete_taxonomy( $taxonomy, false );

			// Flush rewriting rules !
			flush_rewrite_rules( false );

			return true;
		} elseif ( isset( $_GET['action'] ) && isset( $_GET['taxonomy_name'] ) && 'flush-delete' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			$taxonomy_name = sanitize_text_field( wp_unslash( $_GET['taxonomy_name'] ) );
			check_admin_referer( 'staxo-flush-delete-' . $taxonomy_name );

			$taxonomy         = array();
			$taxonomy['name'] = stripslashes( $taxonomy_name );
			self::delete_taxonomy( $taxonomy, true );

			// Flush rewriting rules !
			flush_rewrite_rules( false );

			return true;
		}

		return false;
	}

	/**
	 * Add taxonomy in options
	 *
	 * @param array $taxonomy  taxonomy name.
	 * @return void
	 */
	private static function add_taxonomy( $taxonomy ) {
		$current_options = get_option( OPTION_STAXO );

		if ( isset( $current_options['taxonomies'][ $taxonomy['name'] ] ) ) { // User taxo already exist ?
			wp_die( esc_html__( 'Cheating, uh ? You try to add a taxonomy with a name already used by an another taxonomy.', 'simple-taxonomy-refreshed' ) );
		}
		$current_options['taxonomies'][ $taxonomy['name'] ] = $taxonomy;

		update_option( OPTION_STAXO, $current_options );

		if ( (bool) $taxonomy['rewrite'] ) {
			// Unfortunately we cannot register the new taxonomy and refresh rules here, so create transient data.
			set_transient( 'simple_taxonomy_refreshed_rewrite', true, 0 );
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::ADMIN_SLUG ) . '&message=added' );
		exit();
	}

	/**
	 * Update taxonomy in options.
	 *
	 * @param array $taxonomy  taxonomy name.
	 * @return void
	 */
	private static function update_taxonomy( $taxonomy ) {
		$current_options = get_option( OPTION_STAXO );

		if ( ! isset( $current_options['taxonomies'][ $taxonomy['name'] ] ) ) { // Taxo not exist ?
			wp_die( esc_html__( 'Cheating, uh ? You try to edit a taxonomy with a name different as original. Simple Taxonomy dont allow update the name. Propose a patch ;)', 'simple-taxonomy-refreshed' ) );
		}

		// Is there a change of rewrite rules involved in this update?
		$old_tax = $current_options['taxonomies'][ $taxonomy['name'] ];
		if ( (bool) $old_tax['rewrite'] ) {
			// this plugin has a specific element, old one uses query_var.
			$old_slug = ( array_key_exists( 'st_slug', $old_tax ) ? $old_tax['st_slug'] : $old_tax['query_var'] );
		} else {
			$old_slug = '!impossible!';
		}
		if ( (bool) $taxonomy['rewrite'] ) {
			$new_slug = ( empty( $taxonomy['st_slug'] ) ? $taxonomy['name'] : $taxonomy['st_slug'] );
		} else {
			$new_slug = '!impossible!';
		}

		$current_options['taxonomies'][ $taxonomy['name'] ] = $taxonomy;

		update_option( OPTION_STAXO, $current_options );

		// Clear cache if there.
		wp_cache_delete( 'staxo_sel_' . $taxonomy['name'] );

		if ( $new_slug !== $old_slug ) {
			// Change in rewrite rules - Flush !
			// Ensure taxonomy entries updated before any flush.
			unregister_taxonomy( $taxonomy['name'] );
			if ( '!impossible!' !== $old_slug ) {
				// remove old slug from rewrite rules.
				flush_rewrite_rules( false );
			}
			if ( '!impossible!' !== $new_slug ) {
				// Unfortunately we cannot register the new taxonomy and refresh rules here, so create transient data.
				set_transient( 'simple_taxonomy_refreshed_rewrite', true, 0 );
			}
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::ADMIN_SLUG ) . '&message=updated' );
		exit();
	}

	/**
	 * Delete a taxonomy, and optionally flush contents.
	 *
	 * @param string  $taxonomy        taxonomy name.
	 * @param boolean $flush_relations whether to delete the object relations/terms.
	 * @return boolean|void
	 */
	private static function delete_taxonomy( $taxonomy, $flush_relations = false ) {
		$current_options = get_option( OPTION_STAXO );

		if ( ! isset( $current_options['taxonomies'][ $taxonomy['name'] ] ) ) { // Taxo not exist ?
			wp_die( esc_html__( 'Cheating, uh ? You try to delete a taxonomy who not exist...', 'simple-taxonomy-refreshed' ) );
			return false;
		}

		unset( $current_options['taxonomies'][ $taxonomy['name'] ] ); // Delete from options.

		if ( true === $flush_relations ) {
			self::delete_objects_taxonomy( $taxonomy['name'] ); // Delete object relations/terms.
		}

		update_option( OPTION_STAXO, $current_options );

		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::ADMIN_SLUG ) . '&message=deleted' );
		exit();
	}

	/**
	 * Delete all relationship between objects and terms for a specific taxonomy.
	 *
	 * @param string $taxo_name taxonomy name.
	 * @return boolean
	 */
	private static function delete_objects_taxonomy( $taxo_name = '' ) {
		if ( empty( $taxo_name ) ) {
			return false;
		}

		$terms = get_terms( $taxo_name, 'hide_empty=0&fields=ids' );
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
		if ( ! isset( $post->ID ) || 'post' !== $current_screen->base ) {
			return;
		}

		if ( is_null( self::$use_block_editor ) ) {
			// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
			self::$use_block_editor = method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor()
			 || function_exists( 'is_gutenberg_page' ) && is_gutenberg_page()
			 || false;
			// phpcs:enable  WordPress.WhiteSpace.PrecisionAlignment.Found
		}

		// get the post type.
		$post_type = $post->post_type;

		// dont control some statuses or with not title.
		$no_cntl = in_array( $post->post_status, array( 'new', 'auto-draft', 'trash' ), true ) || empty( $post->post_title ) && post_type_supports( $post_type, 'title' );

		$options = get_option( OPTION_STAXO );
		if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
			foreach ( (array) $options['taxonomies'] as $taxonomy ) {
				// is this taxonomy implicated.
				if ( empty( $taxonomy['objects'] ) || ! in_array( $post_type, $taxonomy['objects'], true ) ) {
					continue;
				}

				// is control set up.
				if ( ( ! isset( $taxonomy['st_cc_type'] ) ) || empty( $taxonomy['st_cc_type'] ) ) {
					continue;
				}

				// control required. check the status.
				$test = false;
				$cntl = (int) $taxonomy['st_cc_type'];
				if ( ( 1 === $cntl && in_array( $post->post_status, array( 'publish', 'future' ), true ) ) || ( 2 === $cntl && ! $no_cntl ) ) {
					$test = true;
				}

				// get user capabilities.
				$user_manage = current_user_can( $taxonomy['capabilities']['manage_terms'] );
				$user_change = current_user_can( $taxonomy['capabilities']['assign_terms'] );

				// get the terms and count them.
				$tax   = $taxonomy['name'];
				$label = $taxonomy['labels']['name'];
				$terms = get_the_terms( $post->ID, $tax );
				if ( false === $terms ) {
					$num_terms = 0;
				} elseif ( $terms instanceof WP_Error ) {
					continue;
				} else {
					$num_terms = count( $terms );
				}

				// check minimum if test is needed.
				$chg = true;
				$min = (int) $taxonomy['st_cc_min'];
				$vmn = (bool) $taxonomy['st_cc_umin'];
				if ( $test && $vmn && $num_terms < $min ) {
					?>
					<div><p>&nbsp;</p></div>
					<div class="notice notice-error" id="err-<?php echo esc_html( $tax ); ?>-min"><p>
					<?php
					// translators: %1$s is the taxonomy label name; %2$d is the required minimum number of terms.
					echo esc_html( sprintf( __( 'The number of terms for taxonomy "%1$s" is less than the required minimum number %2$d.', 'simple-taxonomy-refreshed' ), $label, $min ) );
					echo '</p><p>';
					if ( $user_change ) {
						esc_html_e( 'Please review and add additional terms.', 'simple-taxonomy-refreshed' );
					} else {
						esc_html_e( 'For information as you cannot add them.', 'simple-taxonomy-refreshed' );
						if ( $taxonomy['st_cc_hard'] > 0 ) {
							echo '</p><p>' . esc_html__( 'N.B. You will not be able to save any changes!', 'simple-taxonomy-refreshed' );
						}
					}
					?>
					</p></div>
					<?php
				}

				// check maximum if test is needed.
				$max = (int) $taxonomy['st_cc_max'];
				$vmx = (bool) $taxonomy['st_cc_umax'];
				if ( $test && $vmx && $num_terms > $max ) {
					$chg = false;
					?>
					<div class="notice notice-error" id="err-<?php echo esc_html( $tax ); ?>-max"><p>
					<?php
					// translators: %1$s is the taxonomy label; %2$d is the required maximum number of terms.
					echo esc_html( sprintf( __( 'The number of terms for taxonomy "%1$s" is greater than the required maximum number %2$d.', 'simple-taxonomy-refreshed' ), $label, $max ) );
					echo '</p><p>';
					if ( $user_change ) {
						esc_html_e( 'Please review and remove terms.', 'simple-taxonomy-refreshed' );
					} else {
						esc_html_e( 'For information as you cannot remove them.', 'simple-taxonomy-refreshed' );
						if ( $taxonomy['st_cc_hard'] > 0 ) {
							echo '</p><p>' . esc_html__( 'N.B. You will not be able to save any changes!', 'simple-taxonomy-refreshed' );
						}
					}
					?>
					</p></div>
					<?php
				}

				// should we change checkbox to a radio button.
				// (Not over limit, hierarchical, min and max limits exist and set to 1).
				if ( $chg && (bool) $taxonomy['hierarchical'] && $vmn && 1 === $min && $vmx && 1 === $max ) {
					self::script_radio( $tax );
					// if we converted to radio and there is already one term, then it always is in limits.
					if ( 1 === $num_terms ) {
						continue;
					}
				}

				// should hard limits apply.
				if ( 2 === (int) $taxonomy['st_cc_hard'] && $user_change ) {
					if ( (bool) $taxonomy['hierarchical'] ) {
						self::hard_term_limits_hier( $tax, $label, $num_terms, $cntl, ( $vmn ? $min : null ), ( $vmx ? $max : null ) );
					} else {
						self::hard_term_limits_tag( $tax, $label, $num_terms, $cntl, ( $vmn ? $min : null ), ( $vmx ? $max : null ) );
					}
				}
			}
		}
	}

	/**
	 * Output the javascript to change the taxonomy display to use radio buttons.
	 *
	 * @since 1.2.0
	 *
	 * @param string $tax_name The taxonomy slug.
	 */
	public static function script_radio( $tax_name ) {
		// Logic is that there are two tabs for the taxonomy - all and popular.
		// Only one category must be selected, so radio is appropriate.
		// All will contain all options; popular may be available, but may be incomplete.
		// List of all will be changed to radio. Popular will be converted (but for compatability).
		// If any item in either list is clicked, then the corresponding entry in other list is set.
		// Every other value is unset.
		$taxn = esc_html( $tax_name );
		$taxf = esc_html( str_replace( '-', '_', $tax_name ) );

		if ( self::$use_block_editor ) {
			// not yet supported.
		} else {
			// All output has passed through esc_html so switch off checking.
			// phpcs:disable  WordPress.Security.EscapeOutput
			?>
			<script type="text/javascript">
			function radio_<?php echo $taxf; ?>() {
				var inp = document.getElementsByName("tax_input[<?php echo $taxn; ?>][]");
				inp.forEach(item => {
					// avoid updating hidden one.
					if ( item.value > 0 && item.type !== "radio" ) {
						item.type = "radio";
						item.addEventListener('click', event => {
							adj_<?php echo $taxf; ?>(item.value);
						});
						item.addEventListener('keypress', event => {
							adj_<?php echo $taxf; ?>(item.value);
						});
					}
				});
			}

			function adj_<?php echo $taxf; ?>(val) {
				var inp = document.getElementsByName("tax_input[<?php echo $taxn; ?>][]");
				var i, attr, id;
				for ( i in inp ) {
					inp[i].checked = false;
					if ( inp[i].value === val ) {
						inp[i].checked = true;
					}
				}
				var pan = document.getElementById("<?php echo $taxn; ?>-pop");
				inp = pan.getElementsByTagName("input");
				for ( i in inp ) {
					inp[i].checked = false;
					if ( inp[i].value === val ) {
						inp[i].checked = true;
					}
				}
			}

			document.addEventListener('DOMContentLoaded', function() {
				var i, attr, tag, stag, val;
				radio_<?php echo $taxf; ?>();

				var pop = document.getElementById("<?php echo $taxn; ?>-pop");
				inp = pop.getElementsByTagName("input");
				for (const item of inp) {
					// avoid updating hidden one.
					if ( item.value > 0 ) {
						item.type = "radio";
						tag  = item.id;
						stag = tag.replace("popular-", "");
						attr = document.getElementById(stag);
						item.checked = attr.checked;
						item.addEventListener('click', event => {
							adj_<?php echo $taxf; ?>(item.value);
						});
						item.addEventListener('keypress', event => {
							adj_<?php echo $taxf; ?>(item.value);
						});
					}
				}

				var sub = document.getElementById("<?php echo $taxn; ?>-add-submit");
				sub.addEventListener('click', event => {
						adj_<?php echo $taxf; ?>(-1);
				});
				sub.addEventListener('keypress', event => {
					adj_<?php echo $taxf; ?>(-1);
				});

				// Select the node that will be observed for mutations
				const targetNode = document.getElementById("<?php echo $taxn; ?>-all");

				// Options for the observer (which mutations to observe)
				const config = { childList: true, subtree: true };

				// Callback function to execute when mutations are observed
				const callback = function(mutationsList, observer) {
					// Use traditional 'for loops' for IE 11
					for (const mutation of mutationsList) {
						if (mutation.type === 'childList') {
							radio_<?php echo $taxf; ?>();
						}
					}
				};

				// Create an observer instance linked to the callback function
				const observer = new MutationObserver(callback);

				// Start observing the target node for configured mutations
				observer.observe(targetNode, config);

			}, false);
			</script>
			<?php
			// phpcs:enable  WordPress.Security.EscapeOutput
		}
	}

	/**
	 * Output the scripting to check the taxonomy limits for hierarchical taxonomies as they are being entered.
	 *
	 * @since 1.2.0
	 *
	 * @param string $tax_name     taxonomy name.
	 * @param string $tax_label    taxonomy label name.
	 * @param int    $num_terms    current number of terms on post.
	 * @param string $cntl         control type.
	 * @param int    $min_bound    minimum number of terms (null if no minimum).
	 * @param int    $max_bound    maximum number of terms (null if no maximum).
	 */
	public static function hard_term_limits_hier( $tax_name, $tax_label, $num_terms, $cntl, $min_bound, $max_bound ) {
		if ( ! is_null( $min_bound ) ) {
			$mib = esc_html( $min_bound );
			// translators: %1$s is the taxonomy label name; %2$d is the required minimum number of terms.
			$less = esc_html( sprintf( __( 'The number of terms for taxonomy (%1$s) is less than the required minimum number %2$d.', 'simple-taxonomy-refreshed' ), $tax_label, $min_bound ) );
		}
		if ( ! is_null( $max_bound ) ) {
			$mab = esc_html( $max_bound );
			// translators: %1$s is the taxonomy label name; %2$d is the required maximum number of terms.
			$more = esc_html( sprintf( __( 'The number of terms for taxonomy (%1$s) is greater than the required maximum number %2$d.', 'simple-taxonomy-refreshed' ), $tax_label, $max_bound ) );
		}
		$taxn = esc_html( $tax_name );
		$taxf = esc_html( str_replace( '-', '_', $tax_name ) );
		if ( self::$use_block_editor ) {
			// not yet supported.
		} else {
			// All output has passed through esc_html so switch off checking.
			// phpcs:disable  WordPress.Security.EscapeOutput
			?>
			<script type="text/javascript">
			function count_<?php echo $taxf; ?>() {
				var inp = document.getElementsByName("tax_input[<?php echo $taxn; ?>][]");
				var i, v, arr = [];
				for ( i in inp ) {
					if ( inp[i].checked ) {
						v = inp[i].value;
						if ( v > 0 && ! arr.includes( v )) {
							arr.splice(0, 0, v);
						}
					}
				}
				return arr.length;
			}

			function check_<?php echo $taxf; ?>(bail = false) {
				// check post_status.
				var stat = document.getElementById("post_status").value;
				if ( "new" === stat || "auto-draft" === stat || "trash" === stat ) {
					return;
				}
				<?php
				if ( 1 === $cntl ) {
					// check status.
					echo 'if ( "publish" !== stat && "future" !== stat ) { return; }' . "\n";
				}
				?>
				var cnt = count_<?php echo $taxf; ?>();
				var err = false;

				<?php
				if ( ! is_null( $min_bound ) ) {
					echo 'if ( cnt < ' . $mib . ' ) { alert( "' . $less . '" ); err = true; }' . "\n";
				}

				if ( ! is_null( $max_bound ) ) {
					echo 'if ( cnt > ' . $mab . ' ) { alert( "' . $more . '" ); err = true; }' . "\n";
				}
				?>
				if (err && bail) {
					event.stopPropagation();
					event.preventDefault();
				}
			}

			document.addEventListener('DOMContentLoaded', function() {
				var inp = document.getElementsByName("tax_input[<?php echo $taxn; ?>][]");
				inp.forEach(item => {
					item.addEventListener('click', event => {
						check_<?php echo $taxf; ?>();
					});
					item.addEventListener('keypress', event => {
						check_<?php echo $taxf; ?>();
					});
				})
				document.getElementById("publish").addEventListener('click', event => {
					check_<?php echo $taxf; ?>(true);
				});
				document.getElementById("publish").addEventListener('keypress', event => {
					check_<?php echo $taxf; ?>(true);
				});
				var sp = document.getElementById("save-post");
				if (sp) {
					sp.addEventListener('click', event => {
						check_<?php echo $taxf; ?>(true);
					});
					sp.addEventListener('keypress', event => {
						check_<?php echo $taxf; ?>(true);
					});
				}
			}, false);
			</script>
			<?php
			// phpcs:enable  WordPress.Security.EscapeOutput
		}
	}

	/**
	 * Output the scripting to check the taxonomy limits for tag taxonomies as they are being entered.
	 *
	 * @since 1.2.0
	 *
	 * @param string $tax_name     taxonomy name.
	 * @param string $tax_label    taxonomy label name.
	 * @param int    $num_terms    current number of terms on post.
	 * @param string $cntl         control type.
	 * @param int    $min_bound    minimum number of terms (null if no minimum).
	 * @param int    $max_bound    maximum number of terms (null if no maximum).
	 */
	public static function hard_term_limits_tag( $tax_name, $tax_label, $num_terms, $cntl, $min_bound, $max_bound ) {
		if ( ! is_null( $min_bound ) ) {
			$mib = esc_html( $min_bound );
			// translators: %1$s is the taxonomy label name; %2$d is the required minimum number of terms.
			$less = esc_html( sprintf( __( 'The number of terms for taxonomy (%1$s) is less than the required minimum number %2$d.', 'simple-taxonomy-refreshed' ), $tax_label, $min_bound ) );
		}
		if ( ! is_null( $max_bound ) ) {
			$mab = esc_html( $max_bound );
			// translators: %1$s is the taxonomy label name; %2$d is the required maximum number of terms.
			$more = esc_html( sprintf( __( 'The number of terms for taxonomy (%1$s) is greater than the required maximum number %2$d.', 'simple-taxonomy-refreshed' ), $tax_label, $max_bound ) );
		}
		$taxn  = esc_html( $tax_name );
		$taxf  = esc_html( str_replace( '-', '_', $tax_name ) );
		$terms = esc_html( $num_terms );
		if ( self::$use_block_editor ) {
			// not yet supported.
		} else {
			// All output has passed through esc_html so switch off checking.
			// phpcs:disable  WordPress.Security.EscapeOutput
			?>
			<script type="text/javascript">
			var cnt1st = true;
			function count_<?php echo $taxf; ?>() {
				if (cnt1st) {
					return <?php echo $terms; ?>;
				}
				// tags rendered.
				var i = 0;
				while ( true ) {
					inp = document.getElementById("<?php echo $taxn; ?>-check-num-"+i);
					if (inp) {
						i++;
					} else {
						return i;
					}
				}
			}

			function check_<?php echo $taxf; ?>(bail = false) {
				// Ensure tage add readonly attribute remove, unless explicitly wanted.
				document.getElementById("new-tag-<?php echo $taxn; ?>").removeAttribute("readonly");
				document.getElementById("link-<?php echo $taxn; ?>").removeAttribute("disabled");
				// check post_status.
				var stat = document.getElementById("post_status").value;
				if ( "new" === stat || "auto-draft" === stat || "trash" === stat ) {
					return;
				}
				<?php
				if ( 1 === $cntl ) {
					// check status.
					echo 'if ( "publish" !== stat && "future" !== stat ) { return; }' . "\n";
				}
				?>

				var cnt = count_<?php echo $taxf; ?>();
				var err = false;
				<?php
				if ( ! is_null( $min_bound ) ) {
					echo 'if ( cnt < ' . $mib . ' ) { alert( "' . $less . '" ); err = true; }' . "\n";
				}

				if ( ! is_null( $max_bound ) ) {
					?>
					if ( cnt > <?php echo $mab; ?> ) { 
						alert( "<?php echo $more; ?>" );
						err = true;
					}
					if ( cnt >= <?php echo $mab; ?> ) { 
						document.getElementById("new-tag-<?php echo $taxn; ?>").setAttribute("readonly", true);
						document.getElementById("link-<?php echo $taxn; ?>").setAttribute("disabled", true);
					}
					<?php
				}
				?>
				if (err && bail) {
					event.stopPropagation();
					event.preventDefault();
				}
			}

			document.addEventListener('DOMContentLoaded', function() {
				check_<?php echo $taxf; ?>();
				cnt1st = false;
				var tag = document.getElementById("<?php echo $taxn; ?>");
				var taglist = tag.getElementsByTagName('ul');
				taglist[0].addEventListener('click', event => {
					check_<?php echo $taxf; ?>();
				});
				document.getElementById("new-tag-<?php echo $taxn; ?>").addEventListener('click', event => {
					check_<?php echo $taxf; ?>();
				});
				document.getElementById("new-tag-<?php echo $taxn; ?>").addEventListener('keypress', event => {
					check_<?php echo $taxf; ?>();
				});
				document.getElementById("publish").addEventListener('click', event => {
					check_<?php echo $taxf; ?>(true);
				});
				document.getElementById("publish").addEventListener('keypress', event => {
					check_<?php echo $taxf; ?>(true);
				});
				document.getElementById("save-post").addEventListener('click', event => {
					check_<?php echo $taxf; ?>(true);
				});
				document.getElementById("save-post").addEventListener('keypress', event => {
					check_<?php echo $taxf; ?>(true);
				});
			}, false);
			</script>
			<?php
			// phpcs:enable  WordPress.Security.EscapeOutput
		}
	}

	/**
	 * Function to determine whether the page is being rendered by Block editor.
	 *
	 * @return void
	 */
	public static function block_editor_active() {
		self::$use_block_editor = true;
	}

	/**
	 * Use for build admin taxonomy.
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
	 * @param string $key  index into true/false type.
	 * @return string/array
	 */
	private static function get_true_false( $key = '' ) {
		$types = array(
			'0' => __( 'False', 'simple-taxonomy-refreshed' ),
			'1' => __( 'True', 'simple-taxonomy-refreshed' ),
		);

		if ( isset( $types[ $key ] ) ) {
			return $types[ $key ];
		}

		return $types;
	}

	/**
	 * Use for build selector auto terms.
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
