<?php
/**
 * Simple Taxonomy Admin Term Merge class file.
 *
 * @package simple-taxonomy-refreshed
 * @author Neil James
 */

/**
 * Simple Taxonomy Admin Term Merge class.
 *
 * @package simple-taxonomy-refreshed
 */
class SimpleTaxonomyRefreshed_Admin_Merge {
	const MERGE_SLUG = 'staxo_merge';

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
			self::$instance = new SimpleTaxonomyRefreshed_Admin_Merge();
		}
		return self::$instance;
	}

	/**
	 * Protected Constructor
	 *
	 * @return void
	 */
	final protected function __construct() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 20 );
		// add ajax action.
		add_action( 'wp_ajax_staxo_merge', array( __CLASS__, 'staxo_merge' ) );
	}

	/**
	 * Add settings menu page.
	 **/
	public static function add_menu() {
		add_submenu_page( SimpleTaxonomyRefreshed_Admin::ADMIN_SLUG, __( 'Terms Merge', 'simple-taxonomy-refreshed' ), __( 'Terms Merge', 'simple-taxonomy-refreshed' ), 'manage_options', self::MERGE_SLUG, array( __CLASS__, 'page_merge' ) );

		// help text.
		add_action( 'load-taxonomies_page_' . self::MERGE_SLUG, array( __CLASS__, 'add_help_tab' ) );
	}

	/**
	 * Server-side AJAX function to get list of terms to load.
	 *
	 * @return void
	 */
	public static function staxo_merge() {
		// phpcs:ignore  WordPress.Security.NonceVerification
		if ( isset( $_POST['action'] ) && self::MERGE_SLUG === $_POST['action'] ) {
			check_admin_referer( self::MERGE_SLUG );

			// Taxonomy chosen - output the terms as a radio group.
			if ( isset( $_POST['phase'] ) && 'one' === $_POST['phase'] ) {
				// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
				$taxonomy = wp_unslash( $_POST['taxonomy'] );

				// Selected taxonomy.
				$tax_obj = get_taxonomy( $taxonomy );

				// is terms control configured for this taxonomy with a minimum.
				$terms_control = false;
				if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
					if ( in_array( $taxonomy, $options['taxonomies'], true ) ) {
						$parms = $options['taxonomies'][ $taxonomy ];
						if ( isset( $parms['st_cc_type'] ) && $parms['st_cc_type'] > 0 ) {
							$terms_control = (bool) $parms['st_cc_umin'] && $parms['st_cc_min'] > 0;
						}
					}
				} elseif ( isset( $options['externals'] ) && is_array( $options['externals'] ) ) {
					if ( in_array( $taxonomy, $options['externals'], true ) ) {
						$parms = $options['externals'][ $taxonomy ];
						if ( isset( $parms['st_cc_type'] ) && $parms['st_cc_type'] > 0 ) {
							$terms_control = (bool) $parms['st_cc_umin'] && $parms['st_cc_min'] > 0;
						}
					}
				}

				ob_start();

				// translators: %s is the taxonomy name.
				echo '<p>' . esc_html( sprintf( __( 'Selected Taxonomy : %s', 'simple-taxonomy-refreshed' ), $tax_obj->labels->name ) ) . '</p>';
				if ( $terms_control ) {
					echo '<p>' . esc_html__( 'This taxonomy has Terms Control implemented with a minimum number of terms.', 'simple-taxonomy-refreshed' ) .
						' ' . esc_html__( 'This process may resulst in posts having less than the this minimum number.', 'simple-taxonomy-refreshed' ) . '</p>';
					echo '<input type="hidden" name="control" id="control" value="' . esc_attr( $parms['st_cc_type'] ) . '/' . esc_attr( $parms['st_cc_min'] ) . '" />';
				} else {
					echo '<input type="hidden" name="control" id="control" value="0/0" />';
				}
				echo '<p>' . esc_html__( 'Choose the Destination Term and click on Select Destination Term.', 'simple-taxonomy-refreshed' ) . '</p>';
				echo '<span onclick="str_r()">';

				// Hierarchical or not?
				if ( $tax_obj->hierarchical ) {
				// phpcs:ignore WordPress.Security.EscapeOutput
					echo self::list_hier_taxo_terms( $taxonomy, 'radio' );
				} else {
				// phpcs:ignore WordPress.Security.EscapeOutput
					echo self::list_std_taxo_terms( $taxonomy, 'radio' );
				}
				echo '</span>';
				echo '<input type="hidden" name="taxonomy" id="taxonomy" value="' . esc_attr( $taxonomy ) . '" />';
				echo '<input type="hidden" name="phase" id="phase" value="two" />';
				// phpcs:ignore WordPress.Security.EscapeOutput
				echo ob_get_clean();
			}

			// Taxonomy chosen; Destination chosen - output the terms as checkboxes. Allow multiple.
			if ( isset( $_POST['phase'] ) && 'two' === $_POST['phase'] ) {
				// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
				$taxonomy = wp_unslash( $_POST['taxonomy'] );

				// Selected taxonomy.
				$tax_obj = get_taxonomy( $taxonomy );

				// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
				$destination = (int) wp_unslash( $_POST['term'] );

				ob_start();

				// translators: %s is the taxonomy name.
				echo '<p>' . esc_html( sprintf( __( 'Selected Taxonomy : %s', 'simple-taxonomy-refreshed' ), $tax_obj->labels->name ) ) . '</p>';
				?>
				<p><?php echo esc_html__( 'Choose one or more Source Terms that you want to Merge into the Destination Term and click Select', 'simple-taxonomy-refreshed' ); ?></p>
				<span onclick="str_r()">
				<?php
				// Hierarchical or not?
				if ( $tax_obj->hierarchical ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::list_hier_taxo_terms( $taxonomy, 'checkbox', $destination );
				} else {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::list_std_taxo_terms( $taxonomy, 'checkbox', $destination );
				}
				echo '</span>';
				echo '<input type="hidden" name="taxonomy" id="taxonomy" value="' . esc_attr( $taxonomy ) . '" />';
				echo '<input type="hidden" name="phase" id="phase" value="three" />';
				echo '<input type="hidden" name="destination" id="destination" value="' . esc_attr( $destination ) . '" />';
				// phpcs:ignore WordPress.Security.EscapeOutput
				echo ob_get_clean();
			}

			// All input selected - Confirm.
			if ( isset( $_POST['phase'] ) && 'three' === $_POST['phase'] ) {
				// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
				$taxonomy = wp_unslash( $_POST['taxonomy'] );

				// Selected taxonomy.
				$tax_obj = get_taxonomy( $taxonomy );

				// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
				$destination = wp_unslash( $_POST['destination'] );
				$dest_obj    = get_term( $destination );

				if ( isset( $_POST['term'] ) ) {
					// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
					$terms = wp_unslash( $_POST['term'] );
					if ( is_array( $terms ) ) {
						$sources = array();
						$tt_ids  = array();
						foreach ( $terms as $var => $val ) {
							$sources[ $var ] = $val;
							$tt_ids[ $var ]  = get_term( $sources[ $var ] )->term_taxonomy_id;
						}
					} else {
						$sources = array( $terms );
						$tt_ids  = array( get_term( $sources[0] )->term_taxonomy_id );
					}
				}
				ob_start();
				// translators: %s is the taxonomy name.
				echo '<p>' . esc_html( sprintf( __( 'Selected Taxonomy : %s', 'simple-taxonomy-refreshed' ), $tax_obj->labels->name ) ) . '</p>';
				// translators: %s is the desstination term name.
				echo '<p>' . esc_html( sprintf( __( 'Destination Term  : %s', 'simple-taxonomy-refreshed' ), $dest_obj->name ) ) . '</p>';
				echo '<p>' . esc_html__( 'Source Term(s)    : ', 'simple-taxonomy-refreshed' ) . '</p>';
				foreach ( $sources as $source ) {
					$name = get_term( $source )->name;
					echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . esc_attr( $name ) . '<br/>';
				}
				echo '<p><strong>' . esc_html__( 'This will change all posts to link to the destination term and delete the source term(s).', 'simple-taxonomy-refreshed' ) . '</strong></p>';
				echo '<p><strong>' . esc_html__( 'Any source term metadata will be deleted.', 'simple-taxonomy-refreshed' ) . '</strong></p>';
				echo '<input type="hidden" name="taxonomy" id="taxonomy" value="' . esc_attr( $taxonomy ) . '" />';
				echo '<input type="hidden" name="phase" id="phase" value="four" />';
				echo '<input type="hidden" name="destination" id="destination" value="' . esc_attr( $destination ) . '" />';
				echo '<input type="hidden" name="sources" id="sources" value="' . esc_attr( implode( ',', $sources ) ) . '" />';
				echo '<input type="hidden" name="stt_ids" id="stt_ids" value="' . esc_attr( implode( ',', $tt_ids ) ) . '" />';
				// phpcs:ignore WordPress.Security.EscapeOutput
				echo ob_get_clean();
			}

			// All input selected - go for it.
			if ( isset( $_POST['phase'] ) && 'four' === $_POST['phase'] ) {
				// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
				$taxonomy = wp_unslash( $_POST['taxonomy'] );

				// Selected taxonomy.
				$tax_obj = get_taxonomy( $taxonomy );

				// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
				$destination = wp_unslash( $_POST['destination'] );
				$dest_obj    = get_term( $destination );

				// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
				// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
				$sources = array( wp_unslash( $_POST['sources'] ) );
				// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
				$stt_ids = array( wp_unslash( $_POST['stt_ids'] ) );

				ob_start();

				// update the taxonomy terms.
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$tt_ids = $wpdb->get_results(
					$wpdb->prepare(
						"
							SELECT `{$wpdb->prefix}term_relationships`.`object_id`, `{$wpdb->prefix}term_relationships`.`term_taxonomy_id`
				 			FROM `{$wpdb->prefix}term_relationships` 
							WHERE `{$wpdb->prefix}term_relationships`.`term_taxonomy_id` IN ( %s )",
						$stt_ids
					),
					ARRAY_A
				);
				// if the object is already linked to destination, the update will fail with duplicate index.
				$wpdb->suppress_errors( true );
				$i      = 0;
				$failed = array();
				foreach ( $tt_ids as $p => $row ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$updated = $wpdb->query(
						$wpdb->prepare(
							"UPDATE `{$wpdb->prefix}term_relationships`
							 SET `{$wpdb->prefix}term_relationships`.`term_taxonomy_id` = %d
							 WHERE `{$wpdb->prefix}term_relationships`.`object_id` = %d
							 AND   `{$wpdb->prefix}term_relationships`.`term_taxonomy_id` = %d",
							$dest_obj->term_taxonomy_id,
							$row['object_id'],
							$row['term_taxonomy_id']
						)
					);
					if ( false === $updated ) {
						// failed. term count for post will be reduced. Could be terms control issues.
						$failed[ $row['object_id'] ] = $row['object_id'];
					} else {
						++$i;
					}
				}
				$wpdb->suppress_errors( false );
				// translators: %d is the count of source terms used on objects.
				echo '<p>' . esc_html( sprintf( __( 'Source terms used on objects : %d', 'simple-taxonomy-refreshed' ), count( $tt_ids ) ) ) . '</p>';
				// translators: %d is the count of source terms used on objects updated.
				echo '<p>' . esc_html( sprintf( __( 'Number updated : %d', 'simple-taxonomy-refreshed' ), $i ) ) . '</p>';
				if ( $i < count( $tt_ids ) ) {
					// Some updates may have failed because there was already the destination term linked to the object.
					echo '<p><strong>' . esc_html__( 'Some posts were already linked to the destination term.', 'simple-taxonomy-refreshed' ) . '</strong></p>';
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->query(
						$wpdb->prepare(
							"	DELETE FROM `{$wpdb->prefix}term_relationships`
							WHERE `{$wpdb->prefix}term_relationships`.`term_taxonomy_id` IN ( %s )",
							$stt_ids
						)
					);
				}

				// Update Destination count. Uses the taxonomy counting method.
				wp_update_term_count( $dest_obj->term_taxonomy_id, $taxonomy );

				// Delete source terms. There are no objects using them.
				foreach ( $sources as $source ) {
					// use the standard function. This will delete metadata, clean cache and call standard hooks.
					if ( ! wp_delete_term( $source, $taxonomy ) ) {
						// should not happen, but...
						echo '<p>' . esc_html__( 'Problem to delete Source Term(s) with id: ', 'simple-taxonomy-refreshed' ) . esc_attr( $source ) . '</p>';
					}
				}

				// rebuild cache - including the _children option.
				clean_taxonomy_cache( $taxonomy );

				// Output result.
				$dest_obj = get_term( $destination );
				echo '<p>' . esc_html__( 'All objects updated, destination term count is now : ', 'simple-taxonomy-refreshed' ) . esc_attr( $dest_obj->count ) . '</p>';
				echo '<input type="hidden" name="phase" id="phase" value="five" />';
				// phpcs:ignore WordPress.Security.EscapeOutput
				echo ob_get_clean();
			}
			wp_die(); // this is required to terminate immediately and return a proper response.
		}
	}

	/**
	 * List the taxonomy children for a give parent term (Hierarchical).
	 *
	 * @param string    $taxonomy  taxonomy name.
	 * @param integer   $par_term  parent term.
	 * @param integer   $level     level (indent) of parent term.
	 * @param string    $type      input type (radio or checkbox).
	 * @param integer[] $term_ids  term_ids to disable.
	 * @return void
	 */
	private static function list_taxonomy_children( $taxonomy, $par_term, $level, $type, $term_ids ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$children = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `{$wpdb->prefix}term_taxonomy`.`term_id`, `{$wpdb->prefix}terms`.`name`, `{$wpdb->prefix}terms`.`slug`
				 FROM `{$wpdb->prefix}term_taxonomy` INNER JOIN `{$wpdb->prefix}terms`
				 ON `{$wpdb->prefix}term_taxonomy`.`term_id` = `{$wpdb->prefix}terms`.`term_id`
				 WHERE `{$wpdb->prefix}term_taxonomy`.`taxonomy` = %s
				 AND `{$wpdb->prefix}term_taxonomy`.`parent` = %d
				 ORDER BY `{$wpdb->prefix}terms`.`name`",
				$taxonomy,
				$par_term
			),
			ARRAY_A
		);

		$indent = str_repeat( '&nbsp;&nbsp; ', $level );
		$arr    = ( 'checkbox' === $type ? '[]' : '' );
		foreach ( $children as $p => $row ) {
			$dis = ( in_array( (int) $row['term_id'], $term_ids, true ) ? 'disabled' : '' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $indent . '<span class="components-checkbox-control__input-container"><input type="' . $type . '" role="' . $type . '" name="term' . $arr . '" id="tax' . esc_attr( $row['term_id'] ) . '" value="' . esc_attr( $row['term_id'] ) . '" ' . $dis . '/>';
			echo '<label for="tax_' . esc_attr( $row['term_id'] ) . '" >' . esc_html( $row['name'] ) . '</label></span><br />';
			self::list_taxonomy_children( $taxonomy, $row['term_id'], $level + 1, $type, $term_ids );
		}
	}

	/**
	 * List all the taxonomy terms (Hierarchical).
	 *
	 * @param string  $taxonomy taxonomy name.
	 * @param string  $type     input type (radio or checkbox).
	 * @param integer $term_id  term_id to disable.
	 * @return string $output   list of terms (one per line indented)
	 */
	private static function list_hier_taxo_terms( $taxonomy, $type, $term_id = null ) {
		// find the term and its parents.
		$term_ids = array( (int) $term_id );
		$term_idp = (int) $term_id;
		while ( $term_idp > 0 ) {
			$term_ids[] = (int) $term_idp;
			$term_obj   = get_term( $term_idp );
			$term_idp   = ( $term_obj instanceof WP_Term ? $term_obj->parent : 0 );
		}
		ob_start();
		if ( 'checkbox' === $type ) {
			$output = '<div role="group" >';
		} else {
			$output = '<div role="radiogroup" >';
		}
		self::list_taxonomy_children( $taxonomy, 0, 0, $type, $term_ids );
		$output .= '</div>';
		return ob_get_clean();
	}

	/**
	 * List all the taxonomy terms (Non-Hierarchical).
	 *
	 * @param string  $taxonomy taxonomy name.
	 * @param string  $type     input type (radio or checkbox).
	 * @param integer $term_id  term_id to disable.
	 * @return string $output   list of terms (one per line)
	 */
	private static function list_std_taxo_terms( $taxonomy, $type, $term_id = null ) {
		$output = '';
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$all_terms = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `{$wpdb->prefix}term_taxonomy`.`term_id`, `{$wpdb->prefix}terms`.`name`, `{$wpdb->prefix}terms`.`slug`
				 FROM `{$wpdb->prefix}term_taxonomy` INNER JOIN `{$wpdb->prefix}terms`
				 ON `{$wpdb->prefix}term_taxonomy`.`term_id` = `{$wpdb->prefix}terms`.`term_id`
				 WHERE `{$wpdb->prefix}term_taxonomy`.`taxonomy` = %s
				 ORDER BY `{$wpdb->prefix}terms`.`name`",
				$taxonomy
			),
			ARRAY_A
		);

		if ( 'checkbox' === $type ) {
			$arr     = '[]';
			$output .= '<div role="group" >';
		} else {
			$arr     = '';
			$output .= '<div role="radiogroup" >';
		}

		$i = 0;
		foreach ( $all_terms as $p => $row ) {
			$dis = ( (int) $row['term_id'] === $term_id ? 'disabled' : '' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$output .= '<span class="components-checkbox-control__input-container"><input type="' . $type . '" role="' . $type . '" name="term' . $arr . '" id="tax_' . $row['term_id'] . '" value="' . $row['term_id'] . '" ' . $dis . '/> ';
			$output .= '<label for="tax_' . $row['term_id'] . '" >' . esc_html( $row['name'] ) . '</label></span><br />';
			++$i;
		}
		if ( 0 === $i ) {
			// user has no taxonomies possible to change.
			wp_die( esc_html__( 'Sorry. There are no terms in this taxonomy.', 'simple-taxonomy-refreshed' ) );
		}
		$output .= '</div>';

		return $output;
	}

	/**
	 * Display page to allow term mergw .
	 */
	public static function page_merge() {

		settings_errors( 'simple-taxonomy-refreshed' );
		?>
		<div class="wrap">
			<h1 class="title"><?php esc_html_e( 'Terms Merge', 'simple-taxonomy-refreshed' ); ?></h1>
			<p id="p1" style="font-weight:bold"><?php esc_html_e( 'First select the taxonomy whose terms you wish to merge.', 'simple-taxonomy-refreshed' ); ?></p>
			<p id="p2"><?php esc_html_e( 'It will retrieve all its terms and you first select the Destination term.', 'simple-taxonomy-refreshed' ); ?></p>
			<p id="p3"><?php esc_html_e( 'Then you select one or more Source terms to be merged into the Destination term.', 'simple-taxonomy-refreshed' ); ?></p>
			<p id="p4"><?php esc_html_e( 'The options selected are presented and you confirm the processing to be carried out, with all posts using the Source Term(s) will be linked to the Destination term and the Source Term(s) will be deleted.', 'simple-taxonomy-refreshed' ); ?></p>
			<p id="p5"><?php esc_html_e( 'The results of the processing are presented and standard WordPress caches are cleared.', 'simple-taxonomy-refreshed' ); ?></p>
			<p><?php esc_html_e( 'See Help above for more detailed information on usage.', 'simple-taxonomy-refreshed' ); ?></p>
			<form id = "merge" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MERGE_SLUG ) ); ?>" method="post">
				<div id="tax_terms">
				<p><?php esc_html_e( 'Choose a taxonomy', 'simple-taxonomy-refreshed' ); ?></p>
				<fieldset>
					<div role="radiogroup">
					<?php
					// build a list of taxonomies that can be processed.
					$taxos = array();
					global $wp_taxonomies;
					foreach ( $wp_taxonomies as $taxo ) {
						if ( $taxo->public && ( current_user_can( 'manage_options' ) || current_user_can( $taxo->capabilities->manage_terms ) ) ) {
							$taxos[ $taxo->labels->name ] = $taxo->name;
						}
						if ( empty( $taxos ) ) {
							// user has no taxonomies possible to change.
							wp_die( esc_html__( 'Sorry. You do not have the necessary permissions to change any taxonomies.', 'simple-taxonomy-refreshed' ) );
						}
					}
					// sort the list and output.
					ksort( $taxos );
					foreach ( $taxos as $taxo => $value ) {
							// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '<input type="radio" role="radio" name="taxonomy" class="taxonomy" id="' . $value . '" value="' . $value . '" onclick="str_t(\'' . $value . '\')" >';
							echo '<label for="' . $value . '" >' . esc_html( $taxo ) . '</label><br />';
							// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
					</div>
				</fieldset>
				</p>
				<p><?php esc_html_e( 'Note: Standard WordPress Term caches will be cleared during the Rename process. However other caches may exist and cause some confusion until timed out.', 'simple-taxonomy-refreshed' ); ?></p>
				<input type="hidden" name="phase" id="phase" value="one" />
				</div>
				<div id="submit_button">
				<p class="submit">
					<input type="hidden" name="action" id="action" value="<?php echo esc_html( self::MERGE_SLUG ); ?>" />
					<?php wp_nonce_field( self::MERGE_SLUG ); ?>
					<input type="submit" name="<?php echo esc_html( self::MERGE_SLUG ); ?>" id="<?php echo esc_html( self::MERGE_SLUG ); ?>" value="<?php esc_html_e( 'Select Taxonomy', 'simple-taxonomy-refreshed' ); ?>" class="button-primary" disabled />
				</p>
				</div>
			</form>
		</div>
		<script type="text/javascript">
			var tax = '';
			function str_t(val) {
				document.getElementById("<?php echo esc_html( self::MERGE_SLUG ); ?>").disabled = false;
				tax = val;
			}
			function str_r() {
				document.getElementById("<?php echo esc_html( self::MERGE_SLUG ); ?>").disabled = false;
			}
			document.addEventListener('DOMContentLoaded', function() {
				var $=jQuery.noConflict();
				document.getElementById('<?php echo esc_html( self::MERGE_SLUG ); ?>').addEventListener('click', event => {
					// no double click
					document.getElementById('<?php echo esc_html( self::MERGE_SLUG ); ?>').disabled = true;
					var data = $("#merge").serializeArray();

					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					$.post(ajaxurl, data, function(response) {
						// Replace form with load version
						$('#tax_terms').html(response);
						document.getElementById('<?php echo esc_html( self::MERGE_SLUG ); ?>').disabled = true;
						var phase = document.getElementById('phase').value;
						if ( "two" == phase ) {
							document.getElementById('<?php echo esc_html( self::MERGE_SLUG ); ?>').value = "<?php esc_html_e( 'Select Destination Term', 'simple-taxonomy-refreshed' ); ?>";
							document.getElementById('p1').removeAttribute('style');
							document.getElementById('p2').setAttribute('style', "font-weight:bold");
						}
						if ( "three" == phase ) {
							document.getElementById('<?php echo esc_html( self::MERGE_SLUG ); ?>').value = "<?php esc_html_e( 'Select Source Term(s)', 'simple-taxonomy-refreshed' ); ?>";
							document.getElementById('p2').removeAttribute('style');
							document.getElementById('p3').setAttribute('style', "font-weight:bold");
						}
						if ( "four" == phase ) {
							document.getElementById('<?php echo esc_html( self::MERGE_SLUG ); ?>').value = "<?php esc_html_e( 'Confirm Action', 'simple-taxonomy-refreshed' ); ?>";
							document.getElementById('<?php echo esc_html( self::MERGE_SLUG ); ?>').disabled = false;
							document.getElementById('p3').removeAttribute('style');
							document.getElementById('p4').setAttribute('style', "font-weight:bold");
						}
						if ( "five" == phase ) {
							$('#submit_button').html("");
							document.getElementById('p4').removeAttribute('style');
							document.getElementById('p5').setAttribute('style', "font-weight:bold");
						}
						// Don't submit form
						return false;
					});
				});
			});
		</script>
		<?php
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
				'<p>' . __( 'This tool allows you to merge several terms within a taxonomy into a single term. All existing post links will be migrated to the merged term.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You are presented with a list of all publicly available taxonomies, not just those defined by this plugin.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'It works in five phases. After selecting the taxonomy, the destination term is selected.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Then one or more source terms are selected.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'The choices made are output and the user asked for confirmation.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Once confirmed.the processing is done. That is, the assignment of source terms to posts are updated to be the destination term.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Once done, the source terms are deleted and finally a report is output.', 'simple-taxonomy-refreshed' ) . '</p>',
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
