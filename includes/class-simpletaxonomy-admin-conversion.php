<?php
/**
 * Simple Taxonomy Admin Conversion class file.
 *
 * @package simple-taxonomy-2
 * @author Neil James
 */

/**
 * Simple Taxonomy Admin Conversion class.
 *
 * @package simple-taxonomy-2
 */
class SimpleTaxonomy_Admin_Conversion {
	const CONVERT_SLUG = 'staxo-convert';

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
			self::$instance = new SimpleTaxonomy_Admin_Conversion();
		}
		return self::$instance;
	}

	/**
	 * Protected Constructor
	 *
	 * @return void
	 */
	final protected function __construct() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'wp_ajax_staxo_convert', array( __CLASS__, 'staxo_convert' ) );
	}

	/**
	 * Add settings menu page.
	 **/
	public static function add_menu() {
		add_management_page( __( 'Terms migration', 'simple-taxonomy-2' ), __( 'Terms migrate', 'simple-taxonomy-2' ), 'manage_options', self::CONVERT_SLUG, array( __CLASS__, 'page_conversion' ) );
	}

	/**
	 * Server-side AJAX function to get list of terms to load.
	 *
	 * @return void
	 */
	public static function staxo_convert() {
		if ( isset( $_POST['action'] ) && 'staxo_convert' === $_POST['action'] ) {
			check_admin_referer( 'staxo-convert' );

			// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
			$names = wp_unslash( $_POST['name'] );

			// Find how many elements.
			$num = count( $names );
			// See which are set.
			$copy = '';
			$oput = '';
			for ( $i = 0; $i < $num; $i++ ) {
				if ( isset( $_POST['copy'] ) && key_exists( $i, $_POST['copy'] ) ) {
					$copy = $names[ $i ];
				}
				if ( isset( $_POST['oput'] ) && key_exists( $i, $_POST['oput'] ) ) {
					$oput = $names[ $i ];
				}
			}

			// Source taxo.
			$source_taxo = get_taxonomy( $copy );

			// Destination taxo.
			$destination_taxo = get_taxonomy( $oput );

			// Hierarchical or not? (Both need to be).
			$hierarchical = (bool) $source_taxo->hierarchical && (bool) $destination_taxo->hierarchical;

			if ( $hierarchical ) {
				$taxo_list   = self::list_hier_taxo_terms( $source_taxo->name );
				$hier_option = 'space';
				$hier_text   = __( 'Hierarchy uses space for levels', 'simple-taxonomy-2' );
			} else {
				$taxo_list   = self::list_std_taxo_terms( $source_taxo->name );
				$hier_option = 'no';
				$hier_text   = __( 'No hierarchy', 'simple-taxonomy-2' );
			}

			// invoke import.
			// phpcs:disable  WordPress.Security.NonceVerification.Recommended
			$_POST['taxonomy']       = $destination_taxo->name;
			$_POST['hierarchy']      = $hier_option;
			$_POST['import_content'] = $taxo_list;
			// phpcs:enable  WordPress.Security.NonceVerification.Recommended

			ob_start();
			settings_errors( 'simple-taxonomy-2' );
			?>
			<h2><?php esc_html_e( 'Terms import', 'simple-taxonomy-2' ); ?></h2>
			<p><?php esc_html_e( 'Import a list of words as terms of a taxonomy using this page.', 'simple-taxonomy-2' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'tools.php?page=' . SimpleTaxonomy_Admin_Import::IMPORT_SLUG ) ); ?>" method="post">
				<p>
					<label for="taxonomy"><?php esc_html_e( 'Choose a taxonomy', 'simple-taxonomy-2' ); ?></label>
					<br />
					<select name="taxonomy" id="taxonomy">
					<option value="<?php echo esc_attr( $destination_taxo->name ); ?>" selected><?php echo esc_html( $destination_taxo->label . ' (' . $destination_taxo->name . ')' ); ?></option>
					</select>
				</p>

				<p>
					<label for="hierarchy"><?php esc_html_e( 'Import uses a hierarchy ?', 'simple-taxonomy-2' ); ?></label>
					<br />
					<select name="hierarchy" id="hierarchy">
					<option value="<?php echo esc_html( $hier_option ); ?>" selected><?php echo esc_attr( $hier_text ); ?></option>
					</select>
				</p>

				<p>
					<label for="import_content"><?php echo esc_html( __( 'Terms to import', 'simple-taxonomy-2' ) . ' (' . $source_taxo->label . ')' ); ?></label>
					<br />
					<?php
					// Output the tag with PHP to avoid these leading format tabs being output in the textarea.
					echo '<textarea name="import_content" id="import_content" rows="20" style="width:100%">';
					// phpcs:ignore  WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput
					echo esc_html( stripslashes( wp_unslash( $_POST['import_content'] ) ) );
					echo '</textarea>';
					?>
				</p>

				<p class="submit">
					<?php wp_nonce_field( 'staxo-import' ); ?>
					<input type="submit" name="staxo-import" value="<?php esc_html_e( 'Import these words as terms', 'simple-taxonomy-2' ); ?>" class="button-primary" />
				</p>

				<p>
				<?php
					esc_html_e( 'Term slugs will be generated by WordPress from the Term name.', 'simple-taxonomy-2' );
					echo ' ';
					esc_html_e( 'Use the Taxonomy Term form after import if changes are needed.', 'simple-taxonomy-2' );
				?>
				</p>
				<p><?php esc_html_e( "A term won't be recreated if it already exista but it can be used to add items into the hierarchy.", 'simple-taxonomy-2' ); ?></p>
			</form>
			<?php
			$result = ob_get_contents();
			ob_end_clean();
			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $result;
		}
		wp_die(); // this is required to terminate immediately and return a proper response.
	}

	/**
	 * List the taxonomy children for a give parent term (Hierarchical).
	 *
	 * @param string  $taxonomy  taxonomy name.
	 * @param integer $parent    parent term.
	 * @param integer $level     level (indent) of parent term.
	 * @return void
	 */
	private static function list_taxonomy_children( $taxonomy, $parent, $level ) {
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
				$parent
			),
			ARRAY_A
		);

		$indent = str_repeat( ' ', $level );
		foreach ( $children as $p => $row ) {
			echo esc_html( $indent . $row['name'] . '&#013;' );
			self::list_taxonomy_children( $taxonomy, $row['term_id'], $level + 1 );
		}
	}

	/**
	 * List all the taxonomy terms (Hierarchical).
	 *
	 * @param string $taxonomy taxonomy name.
	 * @return string $output   list of terms (one per line indented)
	 */
	private static function list_hier_taxo_terms( $taxonomy ) {
		ob_start();
		self::list_taxonomy_children( $taxonomy, 0, 0 );
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	/**
	 * List all the taxonomy terms (Non-Hierarchical).
	 *
	 * @param string $taxonomy taxonomy name.
	 * @return string $output   list of terms (one per line)
	 */
	private static function list_std_taxo_terms( $taxonomy ) {
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

		foreach ( $all_terms as $p => $row ) {
			$output .= $row['name'] . '&#013;';
		}

		return $output;
	}

	/**
	 * Display page to allow conversion of custom taxonomies.
	 */
	public static function page_conversion() {

		settings_errors( 'simple-taxonomy-2' );
		?>
		<div class="wrap">
			<h2 class="title"><?php esc_html_e( 'Taxonomy Values Migrator', 'simple-taxonomy-2' ); ?></h2>
			<form id="copyto" action="" method="post">
				<p><?php esc_html_e( 'Select one "Copy From" taxonomy source and one "To" taxonomy destination', 'simple-taxonomy-2' ); ?></p>
				<div id="col-container">
					<table class="widefat" cellspacing="0">
						<thead>
							<tr>
								<th scope="col" id="label" class="manage-column column-name"><?php esc_html_e( 'Copy From', 'simple-taxonomy-2' ); ?></th>
								<th scope="col" id="label" class="manage-column column-name"><?php esc_html_e( 'To', 'simple-taxonomy-2' ); ?></th>
								<th scope="col" id="name"  class="manage-column column-slug"><?php esc_html_e( 'Label', 'simple-taxonomy-2' ); ?></th>
								<th scope="col" id="label" class="manage-column column-name"><?php esc_html_e( 'Hierarchical', 'simple-taxonomy-2' ); ?></th>
							</tr>
						</thead>

						<tbody id="the-list" class="list:taxonomies">
							<?php

								/*
								 *
								 * @param array array default list of taxonomy selection criteria
								 */
							$selectors = apply_filters(
								'staxo_taxo_import_convert_select',
								array(
									'show_ui' => true,
									'public'  => true,
								)
							);
							$i = 0;
							foreach ( get_taxonomies(
								$selectors,
								'object'
							) as $taxonomy ) {
								?>
								<tr id="taxonomy-<?php echo esc_attr( $i ); ?>">
									<td><input type="checkbox" onclick="copy()" class="copy" id="copy[<?php echo esc_attr( $i ); ?>]" name="copy[<?php echo esc_attr( $i ); ?>]"></td>
									<td>
									<?php
									// Can it be copied to?
									if ( current_user_can( $taxonomy->cap->manage_terms ) ) {
										?>
										<input type="checkbox" onclick="oput()" class="oput" id="oput[<?php echo esc_attr( $i ); ?>]" name="oput[<?php echo esc_attr( $i ); ?>]">
										<?php
									} else {
										echo '<br/>';
									}
									?>
									</td>
									<td class="name column-name"><?php echo esc_html( $taxonomy->label . ' [' . $taxonomy->name . ']' ); ?>
									<input type="hidden" id="name[<?php echo esc_attr( $i ); ?>]" name="name[<?php echo esc_attr( $i ); ?>]" value="<?php echo esc_html( $taxonomy->name ); ?>" /></td>
									<td><?php echo esc_html( self::get_true_false( $taxonomy->hierarchical ) ); ?></td>
								</tr>
								<?php
								$i++;
							}
							?>
						</tbody>
					</table>
				</div>

				<p class="submit">
					<input type="hidden" name="action" value="staxo_convert" />
					<?php wp_nonce_field( 'staxo-convert' ); ?>
					<input type="submit" id="staxo-convert" name="staxo-convert" value="<?php esc_html_e( 'Copy Terms', 'simple-taxonomy-2' ); ?>" class="button-primary" disabled />
				</p>
			</form>
		</div>
		<script type="text/javascript">
			function is_checked( classname ) {
				var members = document.getElementsByClassName( classname );
				for (var i = 0; i < members.length; i++) {
					if ( members[i].checked ) {
						return true;
					}
				}
				return false;
			}
			function copyop( thisgrp, othgrp ) {
				var x = document.getElementById(event.srcElement.id).checked;
				var p = document.getElementById(event.srcElement.id).parentElement.parentElement;
				var members = document.getElementsByClassName(thisgrp);
				for (var i = 0; i < members.length; i++) {
					members[i].disabled = x;
				}
				if (x) {
					document.getElementById(event.srcElement.id).disabled = false;
					p.getElementsByClassName(othgrp)[0].disabled = true;
					if ( is_checked(othgrp) ) {
						document.getElementById("staxo-convert").disabled = false;
					}
				} else {
					if ( ! is_checked(othgrp) ) {
						p.getElementsByClassName(othgrp)[0].disabled = false;
					}
					document.getElementById("staxo-convert").disabled = true;
				}
			}
			function copy() {
				copyop( "copy", "oput" );
			}
			function oput() {
				copyop( "oput", "copy" );
			}
			( function( $ ) {
				$( document ).ready( function() {
				$( '#staxo-convert' ).click( function( event ) {
					// no double click
					$( '#staxo-convert' ).disabled = true;
					var data = $("#copyto").serializeArray();

					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					$.post(ajaxurl, data, function(response) {
						// Replace form with load version
						$('.wrap').html(response);
					});

					// Don't submit form
					return false;
				});
			});
			} )( jQuery );
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
			'0' => __( 'False', 'simple-taxonomy-2' ),
			'1' => __( 'True', 'simple-taxonomy-2' ),
		);

		if ( isset( $types[ $key ] ) ) {
			return $types[ $key ];
		}

		return $types;
	}

}
