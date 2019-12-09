<?php
/**
 * Simple Taxonomy Admin Import class file.
 *
 * @package simple-taxonomy-2
 * @author Neil James
 */

/**
 * Simple Taxonomy Admin Import class.
 *
 * @package simple-taxonomy-2
 */
class SimpleTaxonomy_Admin_Import {
	const IMPORT_SLUG = 'staxo-import';

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
		self::check_importation();
	}

	/**
	 * Add settings menu page.
	 **/
	public static function add_menu() {
		add_management_page( __( 'Terms import', 'simple-taxonomy-2' ), __( 'Terms import', 'simple-taxonomy-2' ), 'manage_options', self::IMPORT_SLUG, array( __CLASS__, 'page_importation' ) );
	}

	/**
	 * Check POST datas for bulk importation.
	 *
	 * @return void
	 */
	private static function check_importation() {
		if ( isset( $_POST['staxo-import'] ) && isset( $_POST['import_content'] ) && ! empty( $_POST['import_content'] ) ) {
			// check nonce for form submit.
			check_admin_referer( 'staxo-import' );

			// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
			$taxonomy = sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) );
			if ( ! taxonomy_exists( $taxonomy ) ) {
				wp_die( esc_html__( 'Cheating ? You are trying to import terms on a taxonomy that does not exist.', 'simple-taxonomy-2' ) );
			}

			$taxonomy_obj = get_taxonomy( $taxonomy );
			if ( ! ( current_user_can( 'manage_options' ) || current_user_can( $taxonomy_obj->cap->manage_terms ) ) ) {
				wp_die( esc_html__( 'Cheating ? You do not have the necessary permissions.', 'simple-taxonomy-2' ) );
			}

			$prev_ids = array();
			// standard sanitizing will remove the newline and tab characters - do it for each term.
			// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
			$terms = explode( "\n", str_replace( array( "\r\n", "\n\r", "\r" ), "\n", $_POST['import_content'] ) );
			// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
			$hierarchy = ( isset( $_POST['hierarchy'] ) ? sanitize_text_field( wp_unslash( $_POST['hierarchy'] ) ) : 'no' );
			$termlines = 0;
			$added     = 0;
			foreach ( $terms as $term_line ) {
				if ( 'no' !== $hierarchy ) {
					if ( 'space' === $hierarchy ) {
						$sep = ' ';
					} else {
						$sep = "\t";
					}

					$level = strlen( $term_line ) - strlen( ltrim( $term_line, $sep ) );

					if ( 0 === $termlines ) {
						$term = self::create_term( $taxonomy, $term_line, 0 );
						if ( false !== $term ) {
							$prev_ids[0] = $term[0];
							$added      += (int) $term[1];
							$termlines++;
						}
					} else {
						if ( ( $level - 1 ) < 0 ) {
							$parent = 0;
						} else {
							$parent = $prev_ids[ $level - 1 ];
						}

						$term = self::create_term( $taxonomy, $term_line, $parent );
						if ( false !== $term ) {
							$prev_ids[ $level ] = $term[0];
							$added             += (int) $term[1];
							$termlines++;
						}
					}
				} else {
					$term = self::create_term( $taxonomy, $term_line, 0 );
					if ( false !== $term ) {
						$added += (int) $term[1];
						$termlines++;
					}
				}				
			}

			if ( 0 === $termlines ) {
				add_settings_error( 'simple-taxonomy-2', 'terms_updated', esc_html__( 'Done, but you have not imported any term.', 'simple-taxonomy-2' ), 'error' );
			} elseif ( 1 === $termlines ) {
				add_settings_error( 'simple-taxonomy-2', 'terms_updated', esc_html__( 'Done, 1 term lines processed successfully !', 'simple-taxonomy-2' ), 'updated' );
			} else {
				// translators: %d is the count of terms that were successfully processed.
				add_settings_error( 'simple-taxonomy-2', 'terms_updated', esc_html( sprintf( __( 'Done, %d term lines processed successfully !', 'simple-taxonomy-2' ), $termlines ) ), 'updated' );
			}
			if ( 1 === $added ) {
				add_settings_error( 'simple-taxonomy-2', 'terms_updated', esc_html__( '1 new term was created.', 'simple-taxonomy-2' ), 'updated' );
			} elseif ( $added > 1 ) {
				// translators: %d is the count of terms that were created.
				add_settings_error( 'simple-taxonomy-2', 'terms_updated', esc_html( sprintf( __( ' %d new terms were created.', 'simple-taxonomy-2' ), $added ) ), 'updated' );
			}
		}
	}

	/**
	 * Create term on a taxonomy if necessary.
	 *
	 * @param string  $taxonomy  taxonomy name.
	 * @param string  $term_name term name.
	 * @param integer $parent    term parent.
	 * @return boolean|array of term_id and whether already existed
	 */
	private static function create_term( $taxonomy = '', $term_name = '', $parent = 0 ) {
		$term_name = trim( sanitize_text_field( $term_name ) );
		if ( empty( $term_name ) ) {
			return false;
		}

		$id = term_exists( $term_name, $taxonomy, $parent );
		if ( is_array( $id ) ) {
			$id = (int) $id['term_id'];
		}

		if ( 0 !== (int) $id ) {
			return array( $id, false );
		}

		// Insert on DB.
		$term = wp_insert_term( $term_name, $taxonomy, array( 'parent' => $parent ) );

		// Cache.
		clean_term_cache( $parent, $taxonomy );
		clean_term_cache( $term['term_id'], $taxonomy );

		return array( $term['term_id'], true );
	}

	/**
	 * Display page to allow import in custom taxonomies.
	 */
	public static function page_importation() {
		// phpcs:disable  WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['import_content'] ) ) {
			$_POST['import_content'] = '';
		}
		if ( ! isset( $_POST['taxonomy'] ) ) {
			$_POST['taxonomy'] = '';
		}
		if ( ! isset( $_POST['hierarchy'] ) ) {
			$_POST['hierarchy'] = '';
		}
		// phpcs:enable  WordPress.Security.NonceVerification.Missing

		settings_errors( 'simple-taxonomy-2' );
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Terms import', 'simple-taxonomy-2' ); ?></h2>
			<p><?php esc_html_e( 'Import a list of words as terms of a taxonomy using this page.', 'simple-taxonomy-2' ); ?></p>
			<ul style="margin-left:1em; list-style-type:disc"><li><?php esc_html_e( 'Enter one term per line.', 'simple-taxonomy-2' ); ?></li>
			<li><?php esc_html_e( 'Existing terms can be entered using either the Term Name or its Slug.', 'simple-taxonomy-2' ); ?></li>
			<li><?php esc_html_e( 'Use leading spaces or tabs to denote the level of the Term in the hierarchy relative to its parent.', 'simple-taxonomy-2' ); ?></li></ul>
			<form action="<?php echo esc_url( admin_url( 'tools.php?page=' . self::IMPORT_SLUG ) ); ?>" method="post">
				<p>
					<label for="taxonomy"><?php esc_html_e( 'Choose a taxonomy', 'simple-taxonomy-2' ); ?></label>
					<br />
					<select name="taxonomy" id="taxonomy">
						<?php
						foreach ( get_taxonomies(
							/**
							 *
							 * Filters the default get_taxonomies selector.
							 *
							 * @param array array default list of taxonomy selection criteria
							 */
							apply_filters(
								'staxo_taxo_import_convert_select',
								array(
									'show_ui' => true,
									'public'  => true,
								)
							),
							'object'
						) as $taxonomy ) {
							// phpcs:ignore WordPress.Security.NonceVerification.Missing
							echo '<option value="' . esc_attr( $taxonomy->name ) . '" ' . selected( sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ), $taxonomy->name, false ) . '> ' . esc_html( $taxonomy->label ) . ' (' . esc_html( $taxonomy->name ) . ')</option>' . "\n";
						}
						?>
					</select>
				</p>

				<?php
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$hierarchy = sanitize_text_field( wp_unslash( $_POST['hierarchy'] ) );
				?>
				<p>
					<label for="hierarchy"><?php esc_html_e( 'Import uses a hierarchy ?', 'simple-taxonomy-2' ); ?></label>
					<br />
					<select name="hierarchy" id="hierarchy">
						<option value="no" <?php selected( $hierarchy, 'no' ); ?>><?php esc_html_e( 'No hierarchy', 'simple-taxonomy-2' ); ?></option>
						<option value="space" <?php selected( $hierarchy, 'space' ); ?>><?php esc_html_e( 'Hierarchy uses space for levels', 'simple-taxonomy-2' ); ?></option>
						<option value="tab" <?php selected( $hierarchy, 'tab' ); ?>><?php esc_html_e( 'Hierarchy uses tab for levels', 'simple-taxonomy-2' ); ?></option>
					</select>
				</p>

				<p>
					<label for="import_content"><?php esc_html_e( 'Terms to import', 'simple-taxonomy-2' ); ?></label>
					<br />
					<?php
					// Output the tag with PHP to avoid these leading format tabs being output in the textarea.
					echo '<textarea name="import_content" id="import_content" rows="20" style="width:100%" onkeydown="insertTab(this, event);">';
					// phpcs:ignore  WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput
					echo esc_html( stripslashes( wp_unslash( $_POST['import_content'] ) ) );
					echo '</textarea>';
					?>
				</p>

				<p class="submit">
					<?php wp_nonce_field( 'staxo-import' ); ?>
					<input type="submit" name="staxo-import" value="<?php esc_html_e( 'Import these words as terms', 'simple-taxonomy-2' ); ?>" class="button-primary" />
				</p>
			</form>
		</div>
		<script type="text/javascript">
			function insertTab(o, e)
			{
				var kC = e.keyCode ? e.keyCode : e.charCode ? e.charCode : e.which;
				if (kC == 9 && !e.shiftKey && !e.ctrlKey && !e.altKey)
				{
					var oS = o.scrollTop;
					if (o.setSelectionRange)
					{
						var sS = o.selectionStart;
						var sE = o.selectionEnd;
						o.value = o.value.substring(0, sS) + "\t" + o.value.substr(sE);
						o.setSelectionRange(sS + 1, sS + 1);
						o.focus();
					}
					else if (o.createTextRange)
					{
						document.selection.createRange().text = "\t";
						e.returnValue = false;
					}
					o.scrollTop = oS;
					if (e.preventDefault)
					{
						e.preventDefault();
					}
					return false;
				}
				return true;
			}
		</script>
		<?php
	}
}
