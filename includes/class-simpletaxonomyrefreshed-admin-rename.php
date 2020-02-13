<?php
/**
 * Simple Taxonomy Admin Rename class file.
 *
 * @package simple-taxonomy-refreshed
 * @author Neil James
 */

/**
 * Simple Taxonomy Admin Rename class.
 *
 * @package simple-taxonomy-refreshed
 */
class SimpleTaxonomyRefreshed_Admin_Rename {
	const RENAME_SLUG = 'staxo-rename';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'check_admin_post' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
	}

	/**
	 * Meta function for load all check functions.
	 */
	public static function check_admin_post() {
		self::check_rename();
	}

	/**
	 * Add settings menu page.
	 **/
	public static function add_menu() {
		add_management_page( __( 'Rename Taxonomy slug', 'simple-taxonomy-refreshed' ), __( 'Rename Taxonomy slug', 'simple-taxonomy-refreshed' ), 'manage_options', self::RENAME_SLUG, array( __CLASS__, 'page_rename' ) );
	}

	/**
	 * Check POST datas for rename slug.
	 *
	 * @return void
	 */
	private static function check_rename() {
		if ( isset( $_POST['staxo-rename'] ) && isset( $_POST['taxonomy'] ) && ! empty( $_POST['taxonomy'] ) ) {
			// check nonce for form submit.
			check_admin_referer( 'staxo-rename' );

			$taxonomy = sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) );
			if ( ! taxonomy_exists( $taxonomy ) ) {
				wp_die( esc_html__( 'Cheating ? You are trying to rename the slug on a taxonomy that does not exist.', 'simple-taxonomy-refreshed' ) );
			}

			$new_slug     = ( isset( $_POST['new_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['new_slug'] ) ) : '' );
			$new_query    = ( isset( $_POST['new_query'] ) ? sanitize_text_field( wp_unslash( $_POST['new_query'] ) ) : '' );
			$new_rewrite  = ( isset( $_POST['new_rewrite'] ) ? sanitize_text_field( wp_unslash( $_POST['new_rewrite'] ) ) : '' );
			$taxonomy_obj = (array) get_taxonomy( $taxonomy );
			if ( ! ( current_user_can( 'manage_options' ) || current_user_can( $taxonomy_obj->cap->manage_terms ) ) ) {
				wp_die( esc_html__( 'Cheating ? You do not have the necessary permissions.', 'simple-taxonomy-refreshed' ) );
			}

			// Modify the taxonomy settings.
			$current_options      = get_option( OPTION_STAXO );
			$new_taxonomy         = $current_options['taxonomies'][ $taxonomy ];
			$new_taxonomy['name'] = $new_slug;

			// deal with query_var.
			if ( $taxonomy === $new_taxonomy['query_var'] ) {
				// default value - query_var == name - reset old.
				$new_query = '';
			}
			if ( '' !== $new_query && $new_query === $new_slug ) {
				// default value - query_var == name - reset new.
				$new_query = '';
			}
			$new_taxonomy['query_var'] = $new_query;

			// A change in rewrite slug requires a flush of the rewrite rules.
			if ( (bool) $taxonomy_obj['rewrite'] ) {
				if ( '' !== $new_rewrite && $new_rewrite !== $new_taxonomy['st_slug'] ) {
					// update options.
					$new_taxonomy['st_slug'] = $new_rewrite;
					// need a flush of the rewrite rules at next init.
					set_transient( 'simple_taxonomy_refreshed_rewrite', true, 0 );
				}
			}

			// Modify the taxonomy settings.
			// Remove old entry.
			unset( $current_options['taxonomies'][ $taxonomy ] );
			$current_options['taxonomies'][ $new_taxonomy['name'] ] = $new_taxonomy;
			update_option( OPTION_STAXO, $current_options );

			// Appears to be some taxonomy structure data held in the options table.
			global $wpdb;
			$post_table = "{$wpdb->prefix}options";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$children = $wpdb->update(
				$post_table,
				array(
					'option_name' => $new_slug . '_children',
				),
				array(
					'option_name' => $taxonomy . '_children',
				)
			);

			// clean taxonomy cache.
			// Do not use clean_taxonomy_cache as it rebuilds the hierarchy - which we already have.
			// However the new taxonomy does not yet exist so terms will not be found.
			// Can delete any terms cached.
			wp_cache_delete( 'all_ids', $taxonomy );
			wp_cache_delete( 'get', $taxonomy );

			// Update the Taxonomy table.
			$post_table = "{$wpdb->prefix}term_taxonomy";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$updated = $wpdb->update(
				$post_table,
				array(
					'taxonomy' => $new_slug,
				),
				array(
					'taxonomy' => $taxonomy,
				)
			);

			add_settings_error( 'simple-taxonomy-refreshed', 'terms_updated', esc_html__( 'Taxonomy slug changed.', 'simple-taxonomy-refreshed' ), 'updated' );
			if ( 0 === $updated ) {
				add_settings_error( 'simple-taxonomy-refreshed', 'terms_updated', esc_html__( 'Done, no terms were migrated.', 'simple-taxonomy-refreshed' ), 'updated' );
			} else {
				// translators: %d is the count of terms that were successfully migrated.
				add_settings_error( 'simple-taxonomy-refreshed', 'terms_updated', esc_html( sprintf( __( 'Done, %d terms were migrated.', 'simple-taxonomy-refreshed' ), $updated ) ), 'updated' );
			}
			if ( 0 === $children ) {
				add_settings_error( 'simple-taxonomy-refreshed', 'terms_updated', esc_html__( 'Done, Children record in options table not migrated.', 'simple-taxonomy-refreshed' ), 'updated' );
			} else {
				add_settings_error( 'simple-taxonomy-refreshed', 'terms_updated', esc_html__( 'Done, Children record in options table migrated.', 'simple-taxonomy-refreshed' ), 'updated' );
			}
		}
	}

	/**
	 * Display page to allow rename of custom taxonomies.
	 */
	public static function page_rename() {
		global $wp_post_types;

		settings_errors( 'simple-taxonomy-refreshed' );

		$options = get_option( OPTION_STAXO );
		$taxos   = array();
		$i       = 1;
		if ( is_array( $options['taxonomies'] ) ) {
			$options = $options['taxonomies'];
			ksort( $options );
			foreach ( (array) $options as $taxonomy ) {
				if ( ! ( current_user_can( 'manage_options' ) || current_user_can( $taxonomy['capabilities']['manage_terms'] ) ) ) {
					continue;
				}
				$args = SimpleTaxonomyRefreshed_Client::prepare_args( $taxonomy );
				$objs = array();
				foreach ( $taxonomy['objects'] as $obj ) {
					$objs[] = $wp_post_types[ $obj ]->label;
				}
				$taxo = array(
					'n'         => $i,
					'label'     => $taxonomy['labels']['name'],
					'name'      => esc_attr( $taxonomy['name'] ),
					'objects'   => '"' . implode( ', ', $objs ) . '"',
					'slug'      => ( ( false === (bool) $args['rewrite'] ) ? '""' : '"' . $args['rewrite']['slug'] . '"' ),
					'query_var' => '"' . $taxonomy['query_var'] . '"',
				);
				$i++;
				$taxos[ $taxo['label'] ] = $taxo;
			};
		}
		if ( 1 === $i ) {
			// user has no taxonomies possible to change.
			wp_die( esc_html__( 'Sorry. You do not have the necessary permissions to change any taxonomies.', 'simple-taxonomy-refreshed' ) );
		}
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Rename Taxonomy slug', 'simple-taxonomy-refreshed' ); ?></h2>
			<p><?php esc_html_e( 'Rename a Taxonomy slug and its terms.', 'simple-taxonomy-refreshed' ); ?></p>
			<p><?php esc_html_e( 'All usages of these terms will be updated as well.', 'simple-taxonomy-refreshed' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'tools.php?page=' . self::RENAME_SLUG ) ); ?>" method="post">
				<p>
					<label for="taxonomy"><?php esc_html_e( 'Choose a taxonomy', 'simple-taxonomy-refreshed' ); ?></label>
					<br />
					<fieldset>
						<?php
						foreach ( $taxos as $taxo ) {
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '<input type="radio" name="taxonomy" class="taxonomy" value="' . $taxo['name'] . '" onclick="c' . $taxo['n'] . '()" > ' . esc_html( $taxo['label'] ) . '<br />';
						}
						?>
					</fieldset>
				</p>
				<p><?php esc_html_e( 'Note: Existing terms may be cached. They may appear until timed out.', 'simple-taxonomy-refreshed' ); ?></p>

				<p class="submit">
					<input type="hidden" name="action" value="staxo_rename" />
					<?php wp_nonce_field( 'staxo-rename' ); ?>
					<input type="submit" name="staxo-rename" id="staxo-rename"value="<?php esc_html_e( 'Rename Taxonomy slug', 'simple-taxonomy-refreshed' ); ?>" class="button-primary" disabled />
				</p>
				<h3><?php esc_html_e( 'Taxonomy applies to Post Types', 'simple-taxonomy-refreshed' ); ?></h3>
				<p id="curr_objects">&nbsp;</p>
				<h3><?php esc_html_e( 'Update Taxonomy slug', 'simple-taxonomy-refreshed' ); ?></h3>
				<p><?php esc_html_e( 'Existing value: ', 'simple-taxonomy-refreshed' ); ?><span id="curr_slug">&nbsp;</span></p>
				<p><input type="text" name="new_slug" id="new_slug" onchange="staxo_check()"></p>
				<p><?php esc_html_e( 'Ensure that you have entered a new value for the slug to enable the Rename.', 'simple-taxonomy-refreshed' ); ?><span id="curr_slug">&nbsp;</span></p>
				<h3><?php esc_html_e( 'Update Query_var', 'simple-taxonomy-refreshed' ); ?></h3>
				<p><?php esc_html_e( 'Existing value: ', 'simple-taxonomy-refreshed' ); ?><span id="curr_query">&nbsp;</span></p>
				<p><input type="text" name="new_query" id="new_query"></p>
				<p><?php esc_html_e( 'If empty then the new Taxonomy slug will be used.', 'simple-taxonomy-refreshed' ); ?></p>
				<div id="rewrite_block">
				<h3><?php esc_html_e( 'Update Rewrite slug', 'simple-taxonomy-refreshed' ); ?></h3>
				<p><?php esc_html_e( 'Existing value: ', 'simple-taxonomy-refreshed' ); ?><span id="curr_rewrite">&nbsp;</span></p>
				<p><input type="text" name="new_rewrite" id="new_rewrite"></p>
				</div>
			</form>
		</div>
		<script type="text/javascript">
			var $=jQuery.noConflict();
			<?php
			foreach ( $taxos as $taxo ) {
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				echo 'function c' . $taxo['n'] . '() {' . "\n";
				echo ' document.getElementById("curr_objects").innerHTML = ' . $taxo['objects'] . ";\n";
				echo ' document.getElementById("curr_slug").innerHTML = "' . $taxo['name'] . '";' . "\n";
				echo ' document.getElementById("new_slug").value = "";' . "\n";
				echo ' document.getElementById("curr_query").innerHTML = ' . $taxo['query_var'] . ";\n";
				echo ' document.getElementById("new_query").value = "";' . "\n";
				echo ' document.getElementById("curr_rewrite").innerHTML = ' . $taxo['slug'] . ";\n";
				echo ' document.getElementById("new_rewrite").value = "";' . "\n";
				// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ' document.getElementById("staxo-rename").disabled = true;' . "\n";
				if ( '""' === $taxo['slug'] ) {
					// Hide rewrite section.
					echo ' document.getElementById("rewrite_block").style.display = "none";' . "\n";
				} else {
					echo ' document.getElementById("rewrite_block").style.display = "block";' . "\n";
				}
				echo "}\n";
			}
			?>
			function staxo_check() {
				// need extra here.
				document.getElementById("staxo-rename").disabled = false;
			}
			$( document ).ready( function() {
				// no taxonomy value clicked at start.
				$( '.taxonomy' ).prop("checked", false);
				$( '.new_slug').val("");
			});
		</script>
		<?php
	}
}
