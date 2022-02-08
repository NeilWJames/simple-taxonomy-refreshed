<?php
/**
 * Simple Taxonomy Admin Conversion class file.
 *
 * @package simple-taxonomy-refreshed
 * @author Neil James
 */

/**
 * Simple Taxonomy Admin Conversion class.
 *
 * @package simple-taxonomy-refreshed
 */
class SimpleTaxonomyRefreshed_Admin_Conversion {
	const CONVERT_SLUG = 'staxo_convert';

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
			self::$instance = new SimpleTaxonomyRefreshed_Admin_Conversion();
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
		add_action( 'wp_ajax_staxo_convert', array( __CLASS__, 'staxo_convert' ) );
	}

	/**
	 * Add settings menu page.
	 **/
	public static function add_menu() {
		add_submenu_page( SimpleTaxonomyRefreshed_Admin::ADMIN_SLUG, __( 'Terms Migration', 'simple-taxonomy-refreshed' ), __( 'Terms Migrate', 'simple-taxonomy-refreshed' ), 'manage_options', self::CONVERT_SLUG, array( __CLASS__, 'page_conversion' ) );

		// help text.
		add_action( 'load-taxonomies_page_' . self::CONVERT_SLUG, array( __CLASS__, 'add_help_tab' ) );
	}

	/**
	 * Server-side AJAX function to get list of terms to load.
	 *
	 * @return void
	 */
	public static function staxo_convert() {
		// phpcs:ignore  WordPress.Security.NonceVerification
		if ( isset( $_POST['action'] ) && self::CONVERT_SLUG === $_POST['action'] ) {
			check_admin_referer( self::CONVERT_SLUG );

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
				$hier_text   = __( 'Hierarchy uses space for levels', 'simple-taxonomy-refreshed' );
			} else {
				$taxo_list   = self::list_std_taxo_terms( $source_taxo->name );
				$hier_option = 'no';
				$hier_text   = __( 'No hierarchy', 'simple-taxonomy-refreshed' );
			}

			// invoke import.
			// phpcs:disable  WordPress.Security.NonceVerification.Recommended
			$_POST['taxonomy']       = $destination_taxo->name;
			$_POST['hierarchy']      = $hier_option;
			$_POST['import_content'] = $taxo_list;
			// phpcs:enable  WordPress.Security.NonceVerification.Recommended

			ob_start();
			settings_errors( 'simple-taxonomy-refreshed' );
			?>
			<h1><?php esc_html_e( 'Terms import', 'simple-taxonomy-refreshed' ); ?></h1>
			<p><?php esc_html_e( 'Import a list of words as terms of a taxonomy using this page.', 'simple-taxonomy-refreshed' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin.php?page=' . SimpleTaxonomyRefreshed_Admin_Import::IMPORT_SLUG ) ); ?>" method="post">
				<p>
					<label for="taxonomy"><?php esc_html_e( 'Choose a taxonomy', 'simple-taxonomy-refreshed' ); ?></label>
					<br />
					<select name="taxonomy" id="taxonomy">
					<option value="<?php echo esc_attr( $destination_taxo->name ); ?>" selected><?php echo esc_html( $destination_taxo->label . ' (' . $destination_taxo->name . ')' ); ?></option>
					</select>
				</p>

				<p>
					<label for="hierarchy"><?php esc_html_e( 'Import uses a hierarchy ?', 'simple-taxonomy-refreshed' ); ?></label>
					<br />
					<select name="hierarchy" id="hierarchy">
					<option value="<?php echo esc_html( $hier_option ); ?>" selected><?php echo esc_attr( $hier_text ); ?></option>
					</select>
				</p>

				<p>
					<label for="import_content"><?php echo esc_html( __( 'Terms to import', 'simple-taxonomy-refreshed' ) . ' (' . $source_taxo->label . ')' ); ?></label>
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
					<?php wp_nonce_field( SimpleTaxonomyRefreshed_Admin_Import::IMPORT_SLUG ); ?>
					<input type="submit" name="<?php echo esc_html( SimpleTaxonomyRefreshed_Admin_Import::IMPORT_SLUG ); ?>" value="<?php esc_html_e( 'Import these words as terms', 'simple-taxonomy-refreshed' ); ?>" class="button-primary" />
				</p>

				<p>
				<?php
					esc_html_e( 'Term slugs will be generated by WordPress from the Term name.', 'simple-taxonomy-refreshed' );
					echo ' ';
					esc_html_e( 'Use the Taxonomy Term form after import if changes are needed.', 'simple-taxonomy-refreshed' );
				?>
				</p>
				<p><?php esc_html_e( "A term won't be recreated if it already exists but it can be used to add items into the hierarchy.", 'simple-taxonomy-refreshed' ); ?></p>
			</form>
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput
			echo ob_get_clean();
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
		return ob_get_clean();
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

		settings_errors( 'simple-taxonomy-refreshed' );
		?>
		<div class="wrap">
			<h1 class="title"><?php esc_html_e( 'Taxonomy Values Migrator', 'simple-taxonomy-refreshed' ); ?></h1>
			<form id="copyto" action="" method="post">
				<p id="descCopyFrom"><?php esc_html_e( 'The first column contains a "Copy From" checkbox; one for each taxonomy source. Select one of them to be the source of terms.', 'simple-taxonomy-refreshed' ); ?></p>
				<p id="descCopyTo"><?php esc_html_e( 'The second column contains a "Copy To" checkbox; one for each taxonomy destination. Select one of them to be where the terms are to be created.', 'simple-taxonomy-refreshed' ); ?></p>
				<p><?php esc_html_e( 'Once one of each has been selected the Copy Terms button will become active and this should be selected.', 'simple-taxonomy-refreshed' ); ?></p>
				<p><?php esc_html_e( 'It will simply prepare a list of terms for you to select for import.', 'simple-taxonomy-refreshed' ); ?></p>
				<p><?php esc_html_e( 'See Help above for more detailed information on usage.', 'simple-taxonomy-refreshed' ); ?></p>
				<div id="col-container">
					<table class="widefat" cellspacing="0">
						<thead>
							<tr>
								<th scope="col" id="labelFrom" class="manage-column column-name"><?php esc_html_e( 'Copy From', 'simple-taxonomy-refreshed' ); ?></th>
								<th scope="col" id="labelTo" class="manage-column column-name"><?php esc_html_e( 'Copy To', 'simple-taxonomy-refreshed' ); ?></th>
								<th scope="col" id="name"  class="manage-column column-name"><?php esc_html_e( 'Label', 'simple-taxonomy-refreshed' ); ?></th>
								<th scope="col" id="slug"  class="manage-column column-slug"><?php esc_html_e( 'Slug', 'simple-taxonomy-refreshed' ); ?></th>
								<th scope="col" id="labelHier" class="manage-column column-name"><?php esc_html_e( 'Hierarchical', 'simple-taxonomy-refreshed' ); ?></th>
							</tr>
						</thead>

						<tbody id="the-list" class="list:taxonomies">
							<?php
							/*
							 * Modifies the default taxonomy selectors (for displaying which taxonomy to import/convert).
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
							$i         = 0;
							foreach ( get_taxonomies(
								$selectors,
								'object'
							) as $taxonomy ) {
								?>
								<tr id="taxonomy-<?php echo esc_attr( $i ); ?>">
									<td><input type="checkbox" onclick="copy()" class="copy" id="copy[<?php echo esc_attr( $i ); ?>]" name="copy[<?php echo esc_attr( $i ); ?>]"
									aria-describedby="descCopyFrom" aria-labelledby="<?php echo esc_html( $taxonomy->name ); ?>"
									title="<?php esc_html_e( 'Copy From', 'simple-taxonomy-refreshed' ); ?> checkbox <?php echo esc_html( $taxonomy->label ); ?>"></td>
									<td>
									<?php
									// Can it be copied to?
									if ( current_user_can( $taxonomy->cap->manage_terms ) ) {
										?>
										<input type="checkbox" onclick="oput()" class="oput" id="oput[<?php echo esc_attr( $i ); ?>]" name="oput[<?php echo esc_attr( $i ); ?>]"
										aria-describedby="descCopyTo" aria-labelledby="<?php echo esc_html( $taxonomy->name ); ?>"
										title="<?php esc_html_e( 'Copy To', 'simple-taxonomy-refreshed' ); ?> checkbox <?php echo esc_html( $taxonomy->label ); ?>">
										<?php
									} else {
										echo '<br/>';
									}
									?>
									</td>
									<td class="name column-name" id="<?php echo esc_html( $taxonomy->name ); ?>"><?php echo esc_html( $taxonomy->label ); ?></td>
									<td class="name column-name"><?php echo esc_html( $taxonomy->name ); ?>
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
					<input type="hidden" name="action" value="<?php echo esc_html( self::CONVERT_SLUG ); ?>" />
					<?php wp_nonce_field( self::CONVERT_SLUG ); ?>
					<input type="submit" id="<?php echo esc_html( self::CONVERT_SLUG ); ?>" name="<?php echo esc_html( self::CONVERT_SLUG ); ?>" value="<?php esc_html_e( 'Copy Terms', 'simple-taxonomy-refreshed' ); ?>" class="button-primary" disabled />
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
						document.getElementById("<?php echo esc_html( self::CONVERT_SLUG ); ?>").disabled = false;
					}
				} else {
					if ( ! is_checked(othgrp) ) {
						p.getElementsByClassName(othgrp)[0].disabled = false;
					}
					document.getElementById("<?php echo esc_html( self::CONVERT_SLUG ); ?>").disabled = true;
				}
			}
			function copy() {
				copyop( "copy", "oput" );
			}
			function oput() {
				copyop( "oput", "copy" );
			}
			document.addEventListener('DOMContentLoaded', function() {
				var $=jQuery.noConflict();
				document.getElementById('<?php echo esc_html( self::CONVERT_SLUG ); ?>').addEventListener('click', event => {
					// no double click
					document.getElementById('<?php echo esc_html( self::CONVERT_SLUG ); ?>').disabled = true;
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
			__( 'Overview', 'simple-taxonomy-refreshed' )  =>
				'<p>' . __( 'This tool allows you to copy terms from one taxonomy to another one. It does not affect existing terms or their links to posts.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'It works in two phases. This screen operates the first phase only and prepares the data for you to operate the second phase - the creation of the term data.', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Phase One', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'By default, you are presented with a list of all publicly available taxonomies, not just those defined by this plugin.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You identify the source taxonomy and the destination one and click on Copy Terms. This does not copy the terms, but prepares the data for the next phase - the Terms import tool.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'If either taxonomy is non-hierarchical, it will extract all terms as a simple alphabetical list, otherwise it will prepare a hierarchical list.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'The data extracted are then presented back to you in the form of a pre-filled input form.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'It is done in two stages to allow you the maximum flexibility to choose which terms you want to be imported (or not).', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Phase Two', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'This is actually the Terms Import tool pre-filled with all the terms of the source taxonomy.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'Once you have all the terms of the souurce taxonomy available in your browser you can edit this data into the set of terms to be imported.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'As it is a set of text lines, you can also copy/paste them into an editor for local manipulation before the actual import process is carried out.', 'simple-taxonomy-refreshed' ) . '</p>',
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
