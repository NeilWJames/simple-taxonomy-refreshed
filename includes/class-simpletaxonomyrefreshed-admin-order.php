<?php
/**
 * Simple Taxonomy Admin List Taxonomy Order class file.
 *
 * @package simple-taxonomy-refreshed
 * @author Neil James
 */

/**
 * Simple Taxonomy Admin List Taxonomy Order  class.
 *
 * @package simple-taxonomy-refreshed
 */
class SimpleTaxonomyRefreshed_Admin_Order {
	const ORDER_SLUG = 'staxo_order';

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
			self::$instance = new SimpleTaxonomyRefreshed_Admin_Order();
		}
		return self::$instance;
	}

	/**
	 * Protected Constructor
	 *
	 * @return void
	 */
	final protected function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'check_admin_order' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 20 );
	}

	/**
	 * Add settings menu page.
	 **/
	public static function add_menu() {
		// get data from cache. Cache because we expect to receive an update.
		$orderings = self::ordering_cache( true );
		if ( $orderings['multiple'] ) {
			add_submenu_page( SimpleTaxonomyRefreshed_Admin::ADMIN_SLUG, __( 'Taxonomy List Order', 'simple-taxonomy-refreshed' ), __( 'Taxonomy List Order', 'simple-taxonomy-refreshed' ), 'manage_options', self::ORDER_SLUG, array( __CLASS__, 'page_admin_order' ) );

			$hook_suffix = 'taxonomies_page_' . self::ORDER_SLUG;
			// help text.
			add_action( 'load-' . $hook_suffix, array( __CLASS__, 'add_help_tab' ) );

			// ensure sortable libraries.
			add_action( 'admin_print_scripts-' . $hook_suffix, array( __CLASS__, 'add_js_libs' ) );
		}
	}

	/**
	 * Check POST data for parameters.
	 *
	 * @return void
	 */
	public static function check_admin_order() {
		// phpcs:ignore  WordPress.Security.NonceVerification
		if ( isset( $_POST[ self::ORDER_SLUG ] ) ) {
			// check nonce for form submit.
			check_admin_referer( self::ORDER_SLUG );
			if ( ! ( current_user_can( 'manage_options' ) ) ) {
				wp_die( esc_html__( 'You do not have the necessary permissions.', 'simple-taxonomy-refreshed' ) );
			}

			// get data from cache.
			$orderings      = self::ordering_cache( true );
			$deflt_ordering = $orderings['default'];
			$saved_ordering = $orderings['saved'];
			$displ_ordering = $orderings['display'];

			// update display with changes made.
			global $wp_post_types;

			foreach ( $deflt_ordering as $post_type => $tax ) {
				$post_key = $post_type . '_arr';
				if ( isset( $_POST[ $post_key ] ) && ! empty( $_POST[ $post_key ] ) ) {
					$taxos                        = json_decode( sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ), true );
					$displ_ordering[ $post_type ] = $taxos;
					// translators: %1$s is the post type name.
					add_settings_error( 'simple-taxonomy-refreshed', 'settings_updated', sprintf( __( '"%1$s" admin list taxonomies updated.', 'simple-taxonomy-refreshed' ), $wp_post_types[ $post_type ]->labels->name ), 'updated' );
				}
				if ( $displ_ordering[ $post_type ] === $deflt_ordering[ $post_type ] ) {
					// no need to store the default ordering.
					unset( $displ_ordering[ $post_type ] );
				}
			}
			// if any changes to default order, store them.
			$options = get_option( OPTION_STAXO );
			// if all default, then remove.
			if ( empty( $displ_ordering ) ) {
				unset( $options['list_order'] );
			} else {
				$options['list_order'] = $displ_ordering;
			}
			update_option( OPTION_STAXO, $options );
			wp_cache_delete( 'staxo_orderings' );
		}
	}

	/**
	 * Display page to allow import in custom taxonomies.
	 */
	public static function page_admin_order() {
		global $wp_post_types;

		// get data from cache. Cache because we expect to receive an update.
		$orderings      = self::ordering_cache( true );
		$displ_ordering = $orderings['display'];

		settings_errors( 'simple-taxonomy-refreshed' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Taxonomy List Ordering', 'simple-taxonomy-refreshed' ); ?></h1>
			<p><?php esc_html_e( 'Set the order of different Taxonomies within Posts Admin lists', 'simple-taxonomy-refreshed' ); ?></p>
			<p><?php esc_html_e( 'See Help above for more detailed information on usage.', 'simple-taxonomy-refreshed' ); ?></p>
			<?php
			$single   = false;
			$multiple = false;
			foreach ( $displ_ordering as $post_type => $tax ) {
				if ( count( $tax ) > 1 ) {
					$multiple = true;
				} else {
					if ( ! $single ) {
						echo '<p>' . esc_html__( 'Ordering is not relevant for these Post Type(s) as they contain only one taxonomy. .', 'simple-taxonomy-refreshed' ) . '</p>';
						echo '<ul style="text-indent: 20px">';
						$single = true;
					}
					echo '<li>' . esc_html( $wp_post_types[ $post_type ]->labels->name ) . '</li>';
				}
			}
			if ( $single ) {
				echo '</ul>';
			}
			if ( ! $multiple ) {
				// Nothing to do.
				echo '<strong?<p>' . esc_html__( 'There is no Post Type with more than one taxonomy.element. Nothing to do.', 'simple-taxonomy-refreshed' ) . '</p></strong></div>';
				return;
			}
			?>
			<div id="poststuff" class="metabox-holder">
			<div id="post-body-content">
				<?php
				// create tabs for each post type.
				echo '<div role="tablist">';
				$active = 'true';
				foreach ( $displ_ordering as $post_type => $tax ) {
					if ( count( $tax ) > 1 ) {
						echo '<button type="button" role="tab" aria-controls="' . esc_html( $post_type ) . '_pnl" aria-selected="' . esc_html( $active ) . '">' . esc_html( $wp_post_types[ $post_type ]->labels->name ) . '</button>';
						$active = 'false';
					}
				}
				echo '</div>';

				// create tab panel for each post type.
				$hidden = '';
				foreach ( $displ_ordering as $post_type => $tax ) {
					if ( count( $tax ) > 1 ) {
						// phpcs:ignore
						echo '<div id="' . esc_html( $post_type ) . '_pnl" class="meta-box-sortabless' . $hidden . '" role="tabpanel">';
						$hidden = ' is-hidden';
						echo '<div class="postbox">';
						echo '<div class="inside"><ul id="' . esc_html( $post_type ) . '_list">';
						foreach ( $tax as $li ) {
							echo '<li class="sort-li" tabindex="0">' . esc_html( $li ) . '</li>';
						}
						echo '</ul></div></div></div>';
					}
				}
				?>
				</div>
			</div>

			<form action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::ORDER_SLUG ) ); ?>" method="post">
				<p class="submit">
					<input type="hidden" name="action" value="<?php echo esc_html( self::ORDER_SLUG ); ?>" />
					<?php wp_nonce_field( self::ORDER_SLUG ); ?>
					<input type="submit" name="<?php echo esc_html( self::ORDER_SLUG ); ?>" id="<?php echo esc_html( self::ORDER_SLUG ); ?>" value="<?php esc_html_e( 'Save these orderings', 'simple-taxonomy-refreshed' ); ?>" class="button-primary" disabled />
				</p>
				<?php
				foreach ( $displ_ordering as $post_type => $tax ) {
					echo '<input type="hidden" name="' . esc_html( $post_type ) . '_arr" id="' . esc_html( $post_type ) . '_arr" value="" />';
				}
				?>
			</form>
		</div>
		<script type="text/javascript">
		function setSortable(grp) {
			jQuery( "#"+grp+"_list" ).sortable({
				placeholder : "ui-sortable-placeholder",
				update : function(event, ui) {
					set_array = new Array();
					var set = document.getElementById(grp+"_list").getElementsByClassName("sort-li");
					for (const elt of set)  {
						set_array.push(elt.innerText);
					};
					document.getElementById( grp+"_arr" ).value = JSON.stringify(set_array);
					document.getElementById("<?php echo esc_html( self::ORDER_SLUG ); ?>").disabled = false;
				}
			});
		}

		document.addEventListener('DOMContentLoaded', function(evt) {	
			str_admin_init();
			<?php
			foreach ( $displ_ordering as $post_type => $tax ) {
				echo 'setSortable("' . esc_html( $post_type ) . '");';
			}
			?>
		});
		</script>
		<?php
	}

	/**
	 * Caches the posts and taxonomies.
	 *
	 * @since 2.0.0
	 *
	 * @param boolean $refresh Whether to refresh the cache.
	 * @return Array
	 */
	private static function ordering_cache( $refresh = false ) {
		$orderings = ( $refresh ? false : wp_cache_get( 'staxo_orderings' ) );

		if ( false === $orderings ) {
			// find the posts types being used and their taxonomies.
			global $wp_post_types;
			$post_tax = array();
			foreach ( $wp_post_types as $wp_post_type ) {
				$post_type  = $wp_post_type->name;
				$taxonomies = get_object_taxonomies( $post_type, 'objects' );
				$taxonomies = wp_filter_object_list( $taxonomies, array( 'show_admin_column' => true ), 'and', 'name' );
				if ( ! empty( $taxonomies ) ) {
					$post_tax[ $post_type ] = array_keys( $taxonomies );
				}
			}

			// get any saved parameters and formulate .
			$options = get_option( OPTION_STAXO );
			if ( isset( $options['list_order'] ) && is_array( $options['list_order'] ) ) {
				$saved_ordering = $options['list_order'];
				$displ_ordering = array();
				foreach ( $post_tax as $post_type => $tax ) {
					// is there a modified list.
					if ( array_key_exists( $post_type, $saved_ordering ) ) {
						$order_exist = array_intersect( $saved_ordering[ $post_type ], $tax );
						$new_taxos   = array_diff( $tax, $saved_ordering[ $post_type ] );
						// go through list building the new one. May be additions or deletions.
						// add any remaining, i.e. new ones.
						$displ_ordering[ $post_type ] = array_merge( $order_exist, $new_taxos );
					} else {
						// no change, use standard ordering.
						$displ_ordering[ $post_type ] = $tax;
					}
				}
			} else {
				$saved_ordering = array();
				$displ_ordering = $post_tax;
			}

			// is there a post_type with multiple?
			$multiple = false;
			foreach ( $post_tax as $post_type => $tax ) {
				if ( count( $tax ) > 1 ) {
					$multiple = true;
					break;
				}
			}

			$orderings = array(
				'default'  => $post_tax,
				'saved'    => $saved_ordering,
				'display'  => $displ_ordering,
				'multiple' => $multiple,
			);

			wp_cache_set( 'staxo_orderings', $orderings, 1200 );
		}
		return $orderings;
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
				'<p>' . __( 'This tool allows you to set the order of taxonomies displayed within post admin list pages.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You will presented with a list of all publicly available taxonomies, not just those defined by this plugin.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Drag the taxonomy names into the desired order for each post type.', 'simple-taxonomy-refreshed' ) . '</p><p>',
			__( 'List Ordering', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'When there are multiple taxonomies for a given post type, they are displayed in the order that they were defined.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You may want them to be displayed in a different order.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Simply drag the taxonomies into the required order and save them.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Note that the placing of the taxonomy columns relative to any other columns displayed cannot be changed.', 'simple-taxonomy-refreshed' ) . '</p>',
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
}
