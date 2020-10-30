<?php
/**
 * Simple Taxonomy Client class file.
 *
 * @package simple-taxonomy-refreshed
 * @author Neil James
 */

/**
 * Simple Taxonomy Client class.
 *
 * @package simple-taxonomy-refreshed
 */
class SimpleTaxonomyRefreshed_Client {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'init' ), 1 );

		add_filter( 'the_excerpt', array( __CLASS__, 'the_excerpt' ), 10, 1 );
		add_filter( 'the_content', array( __CLASS__, 'the_content' ), 10, 1 );

		add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
		add_filter( 'wp_title', array( __CLASS__, 'wp_title' ), 10, 2 );

		add_action( 'restrict_manage_posts', array( __CLASS__, 'manage_filters' ) );

		// terms control invokes additional processing (see init().
	}

	/**
	 * Register all custom taxonomies to WordPress process.
	 *
	 * @return void
	 */
	public static function init() {
		$options = get_option( OPTION_STAXO );
		if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
			// get WP version.
			global $wp_version;

			// check whether to invoke old or new method (Change will need #51517).
			// if ( version_compare( $wp_version, '??' ) >= 0 ) {
				// core method introduced with version ??.
			// $count_method = 'new';
			// } else {  .
				$count_method = 'old';
			// }
			// is terms count implemented anywhere with new rules.
			$terms_count = false;
			// is terms control implemented anywhere.
			$terms_control = false;
			foreach ( (array) $options['taxonomies'] as $taxonomy ) {
				$args = self::prepare_args( $taxonomy );

				// Update callback if term count callback wanted.
				if ( '' === $args['update_count_callback'] && isset( $taxonomy['st_cb_type'] ) ) {
					if ( 'new' === $count_method ) {
						$terms_count = true;
					} else {
						$args['update_count_callback'] = array( __CLASS__, 'term_count_cb_sel' );
					}
				}

				// Identify if term count control limits wanted.
				if ( isset( $taxonomy['st_cc_type'] ) && ! empty( $taxonomy['st_cc_type'] ) ) {
					if ( isset( $taxonomy['st_cc_hard'] ) && ! empty( $taxonomy['st_cc_hard'] ) ) {
						$terms_control = true;
					}
				}

				register_taxonomy( $taxonomy['name'], $taxonomy['objects'], $args );
			};

			// need to refresh the rewrite rules once registered.
			if ( get_transient( 'simple_taxonomy_refreshed_rewrite' ) ) {
				delete_transient( 'simple_taxonomy_refreshed_rewrite' );
				flush_rewrite_rules( false );
			}

			// if terms count wanted and new taxonomy, set up the code.
			if ( $terms_count ) {
				// filters the post statuses to implement the taxonomy counts.
				add_filter( 'countable_status', array( __CLASS__, 'review_statuses' ), 10, 2 );
			}

			// if terms control wanted, invoke the code.
			if ( $terms_control ) {
				// filters the post to implement the taxonomy controls.
				add_filter( 'wp_insert_post_empty_content', array( __CLASS__, 'check_taxonomy_value_set' ), 10, 2 );

				// make sure that the taxonomy is defined for each published document.
				add_action( 'admin_notices', array( __CLASS__, 'admin_error_check' ), 1 );
			}
		}
	}

	/**
	 * Prepare ARGS from DB to function API.
	 *
	 * @param array $taxonomy  taxonomy saved parameters.
	 * @return array
	 */
	public static function prepare_args( $taxonomy ) {
		// Ensure complete.
		$taxonomy                 = wp_parse_args( $taxonomy, self::get_taxonomy_default_fields() );
		$taxonomy['labels']       = wp_parse_args( $taxonomy['labels'], self::get_taxonomy_default_labels() );
		$taxonomy['capabilities'] = wp_parse_args( $taxonomy['capabilities'], self::get_taxonomy_default_capabilities() );

		// Empty query_var ? use name.
		if ( ! empty( $taxonomy['query_var'] ) ) {
			$taxonomy['query_var'] = trim( $taxonomy['query_var'] );
		}

		// Empty slug ? use name.
		if ( empty( $taxonomy['st_slug'] ) ) {
			$taxonomy['st_slug'] = $taxonomy['name'];
		} else {
			$taxonomy['st_slug'] = trim( $taxonomy['st_slug'] );
		}
		if ( empty( $taxonomy['st_ep_mask'] ) ) {
			$taxonomy['st_ep_mask'] = 0;
		}

		// Rewrite.
		$taxonomy['rewrite'] = (bool) $taxonomy['rewrite'];
		if ( true === $taxonomy['rewrite'] ) {
			$taxonomy['rewrite'] = array(
				'slug'         => $taxonomy['st_slug'],
				'with_front'   => (bool) $taxonomy['st_with_front'],
				'hierarchical' => (bool) $taxonomy['st_hierarchical'],
				'ep_mask'      => $taxonomy['st_ep_mask'],
			);
		}

		// Default fields.
		if ( ! empty( $taxonomy['st_dft_name'] ) ) {
			$taxonomy['default_term'] = array(
				'name' => $taxonomy['st_dft_name'],
			);
			if ( ! empty( $taxonomy['st_dft_slug'] ) ) {
				$taxonomy['default_term']['slug'] = $taxonomy['st_dft_slug'];
			}
			if ( ! empty( $taxonomy['st_dft_desc'] ) ) {
				$taxonomy['default_term']['description'] = $taxonomy['st_dft_desc'];
			}
		}

		// Clean labels.
		foreach ( $taxonomy['labels'] as $k => $v ) {
			$taxonomy['labels'][ $k ] = stripslashes( $v );
		}
		if ( '' === $taxonomy['labels']['menu_name'] ) {
			unset( $taxonomy['labels']['menu_name'] );
		}

		// Output Fields.
		$tax_out = array(
			'name'                  => $taxonomy['name'],
			'description'           => $taxonomy['description'],
			'labels'                => $taxonomy['labels'],
			'public'                => (bool) $taxonomy['public'],
			'publicly_queryable'    => (bool) $taxonomy['publicly_queryable'],
			'hierarchical'          => (bool) $taxonomy['hierarchical'],
			'show_ui'               => (bool) $taxonomy['show_ui'],
			'show_in_menu'          => (bool) $taxonomy['show_in_menu'],
			'show_in_nav_menus'     => (bool) $taxonomy['show_in_nav_menus'],
			'show_tagcloud'         => (bool) $taxonomy['show_tagcloud'],
			'show_in_quick_edit'    => (bool) $taxonomy['show_in_quick_edit'],
			'show_admin_column'     => (bool) $taxonomy['show_admin_column'],
			'capabilities'          => $taxonomy['capabilities'],
			'rewrite'               => $taxonomy['rewrite'],
			'query_var'             => $taxonomy['query_var'],
			'update_count_callback' => $taxonomy['update_count_callback'],
			'show_in_rest'          => (bool) $taxonomy['show_in_rest'],
			'rest_base'             => $taxonomy['rest_base'],
			'rest_controller_class' => $taxonomy['rest_controller_class'],
			'sort'                  => (bool) $taxonomy['sort'],
		);

		// code-related fields. Can't assume null is valid.
		if ( empty( $taxonomy['query_var'] ) ) {
			$tax_out['query_var'] = $taxonomy['name'];
		}
		if ( ! empty( $taxonomy['st_args'] ) ) {
			$tax_out['args'] = $taxonomy['st_args'];
		}
		if ( ! empty( $taxonomy['st_update_count_callback'] ) ) {
			$tax_out['update_count_callback'] = $taxonomy['st_update_count_callback'];
		}
		if ( ! empty( $taxonomy['st_meta_box_cb'] ) ) {
			$tax_out['meta_box_cb'] = ( 'false' === $taxonomy['st_meta_box_cb'] ? false : $taxonomy['st_meta_box_cb'] );
		}
		if ( ! empty( $taxonomy['st_meta_box_sanitize_cb'] ) ) {
			$tax_out['meta_box_sanitize_cb'] = $taxonomy['st_meta_box_sanitize_cb'];
		}

		if ( (bool) $taxonomy['st_show_in_graphql'] ) {
			$tax_out['show_in_graphql'] = true;
			$tax_out['graphql_single']  = $taxonomy['st_graphql_single'];
			$tax_out['graphql_plural']  = $taxonomy['st_graphql_plural'];
		};

		if ( ! empty( $taxonomy['default_term'] ) ) {
			$tax_out['default_term'] = $taxonomy['default_term'];
		}

		/*
		 * Filter to set the taxonomy arguments to store.
		 *
		 * @param  array  $tax_out  input set of existing register_taxonomy fields (plus name).
		 * @param  array  $taxonomy taxonomy parameters from screen or DB.
		 * @return array  output set of register_taxonomy fields (plus name).
		 */
		$tax_out = apply_filters( 'staxo_prepare_args', $tax_out, $taxonomy );

		return $tax_out;
	}

	/**
	 * Allow to display the taxonomy template, even if the term is empty.
	 *
	 * @return void
	 */
	public static function template_redirect() {
		global $wp_query;

		if ( isset( $wp_query->query_vars['term'] ) && isset( $wp_query->query_vars['taxonomy'] ) && isset( $wp_query->query_vars[ $wp_query->query_vars['taxonomy'] ] ) ) {
			$wp_query->is_404 = false;
			$wp_query->is_tax = true;
		}
	}

	/**
	 * Allow to build a correct page title for empty term. Otherwise, the term is null.
	 *
	 * @param string $title page title.
	 * @param string $sep   title separator.
	 * @return string
	 */
	public static function wp_title( $title = '', $sep = '' ) {
		global $wp_query;

		// If there's a taxonomy.
		if ( is_tax() && null === $wp_query->get_queried_object() ) {
			// Taxo.
			$taxonomy = get_query_var( 'taxonomy' );
			$tax      = get_taxonomy( $taxonomy );

			// Build unique key.
			$key = 'current-term' . get_query_var( 'term' ) . $tax->name;

			// Terms.
			$term = wp_cache_get( $key, 'terms' );
			if ( false === $term || null === $term ) {
				$term = get_term_by( 'slug', get_query_var( 'term' ), $tax->name, OBJECT, 'display' );
				wp_cache_set( $key, $term, 'terms' );
			}

			// Format Output.
			$title = $tax->label . " $sep " . $term->name;
		}

		return $title;
	}

	/**
	 * Build an xHTML list of terms when the post have custom taxonomy.
	 *
	 * @param string $content content of the content or excerpt.
	 * @param string $type    content or excerpt selector.
	 * @return string
	 */
	public static function taxonomy_filter( $content, $type ) {
		global $post;

		if ( ! isset( $post->post_type ) ) {
			return $content;
		}

		$output = '';

		$options = get_option( OPTION_STAXO );
		if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
			null; // Drop through.
		} else {
			return $content;
		}

		foreach ( (array) $options['taxonomies'] as $taxonomy ) {
			// Does the post_type uses this taxonomy.
			if ( isset( $taxonomy['auto'] ) && in_array( $post->post_type, $taxonomy['objects'], true ) ) {
				if ( 'both' === $taxonomy['auto'] || $type === $taxonomy['auto'] ) {
					// Migration case - Not updated yet.
					if ( ! array_key_exists( 'st_before', $taxonomy ) ) {
						$taxonomy['st_before'] = '';
						$taxonomy['st_after']  = '';
					}
					if ( ! empty( $taxonomy['st_before'] ) && ' ' !== substr( $taxonomy['st_before'], -1 ) ) {
						$prefix = $taxonomy['st_before'] . ' ';
					} else {
						$prefix = $taxonomy['st_before'];
					}
					if ( ! empty( $taxonomy['st_after'] ) && ' ' !== substr( $taxonomy['st_after'], 1 ) ) {
						$suffix = ' ' . $taxonomy['st_after'];
					} else {
						$suffix = $taxonomy['st_after'];
					}
					$terms = get_the_term_list( $post->ID, $taxonomy['name'], $prefix, ', ', $suffix );
					if ( ! empty( $terms ) ) {
						$output .= "\t" . '<div class="taxonomy-' . $taxonomy['name'] . '">' . $terms . "</div>\n";
					} else {
						// On migration and before update, no value in 'not_found'.
						$notfound = ( isset( $taxonomy['labels']['not_found'] ) ? $taxonomy['labels']['not_found'] : __( 'No Terms found', 'simple-taxonomy-refreshed' ) );
						$output  .= "\t" . '<!-- Taxonomy : ' . $taxonomy['name'] . ' : ' . $notfound . ' -->' . "\n";
					}
				}
			}
		}

		if ( ! empty( $output ) ) {
			$content .= '<div class="simple-taxonomy">' . "\n" . $output . "\n" . '</div>' . "\n";
		}

		return $content;
	}

	/**
	 * Meta function for call filter taxonomy with the context "content".
	 *
	 * @param string $content content of the_content.
	 * @return string
	 */
	public static function the_content( $content = '' ) {
		return self::taxonomy_filter( $content, 'content' );
	}

	/**
	 * Meta function for call filter taxonomy with the context "excerpt".
	 *
	 * @param string $content content of the_excerpt.
	 * @return string
	 */
	public static function the_excerpt( $content = '' ) {
		return self::taxonomy_filter( $content, 'excerpt' );
	}

	/**
	 * Prepare the dropdown filter args.
	 *
	 * @param array $taxonomy The current taxonomy structure.
	 */
	public static function prepare_filter_args( &$taxonomy ) {
		$query_var = ( empty( $taxonomy['query_var'] ) ? $taxonomy['name'] : $taxonomy['query_var'] );
		$args      = array(
			'taxonomy'        => $taxonomy['name'],
			'show_option_all' => $taxonomy['labels']['all_items'],
			'orderby'         => 'name',
			'order'           => 'ASC',
			'show_count'      => (bool) $taxonomy['st_adm_count'],
			'hide_empty'      => (bool) $taxonomy['st_adm_h_e'],
			'hide_if_empty'   => (bool) $taxonomy['st_adm_h_i_e'],
			'selected'        => filter_input( INPUT_GET, $query_var, FILTER_SANITIZE_STRING ),
			'hierarchical'    => (bool) $taxonomy['st_adm_hier'],
			'name'            => $taxonomy['query_var'],
			'value_field'     => 'slug',
		);
		return $args;
	}

	/**
	 * Manage the dropdown filters.
	 *
	 * @param string $post_type The current post type.
	 */
	public static function manage_filters( $post_type ) {
		$options = get_option( OPTION_STAXO );
		if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
			foreach ( (array) $options['taxonomies'] as $taxonomy ) {
				// Have we upgraded the taxonomy?
				if ( ! array_key_exists( 'st_adm_types', $taxonomy ) ) {
					continue;
				}
				// Does the post_type use this taxonomy and we want it.
				if ( ! in_array( $post_type, (array) $taxonomy['st_adm_types'], true ) ) {
					continue;
				}
				// Add the filter.
				wp_dropdown_categories( self::prepare_filter_args( $taxonomy ) );
			}
		}
	}

	/**
	 * Create custom filter for taxonomy and put in cache.
	 *
	 * @since 1.1
	 * @param string $taxonomy the taxonomy slug.
	 * @return array the query modification
	 */
	public static function term_count_sel_cache( $taxonomy ) {
		$tax_details = wp_cache_get( 'staxo_sel_' . $taxonomy );

		if ( false === $tax_details ) {
			$options = get_option( OPTION_STAXO );
			if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
				null; // Drop through.
			} else {
				return array(
					'types'     => array(),
					'in_string' => 'IN ("publish")',
				);
			}

			$taxo  = $options['taxonomies'][ $taxonomy ];
			$types = array();
			if ( '' === $taxo['update_count_callback'] && isset( $taxo['st_cb_type'] ) ) {
				switch ( $taxo['st_cb_type'] ) {
					case '1':
						$types = get_post_stati();
						// trash, inherit and auto-draft to be excluded.
						unset( $types['trash'] );
						unset( $types['inherit'] );
						unset( $types['auto-draft'] );
						break;
					case '2':
						if ( (bool) $taxo['st_cb_pub'] ) {
							$types[] = 'public';
						}
						if ( (bool) $taxo['st_cb_fut'] ) {
							$types[] = 'future';
						}
						if ( (bool) $taxo['st_cb_dft'] ) {
							$types[] = 'draft';
						}
						if ( (bool) $taxo['st_cb_pnd'] ) {
							$types[] = 'pending';
						}
						if ( (bool) $taxo['st_cb_prv'] ) {
							$types[] = 'private';
						}
						if ( (bool) $taxo['st_cb_tsh'] ) {
							$types[] = 'trash';
						}
						break;
					default:
						null;
				}

				/**
				 * Filter to manage additional post_statuses for Terms Control entered on the screen.
				 *
				 * @param  array  $types    standard post_statuses entered via screen.
				 * @param  array  $taxonomy taxonomy name.
				 * @return array  $types    post_status slugs to be controlled.
				 */
				$types = apply_filters( 'staxo_term_count_statuses', $types, $taxonomy );
			}

			// create the string.
			$in_string = "IN ('" . implode( "','", $types ) . "') ";

			$tax_details = array(
				'types'     => $types,
				'in_string' => $in_string,
			);

			wp_cache_set( 'staxo_sel_' . $taxonomy, $tax_details, '', ( WP_DEBUG ? 10 : 600 ) );
		}

		return $tax_details;
	}

	/**
	 * Term Count Callback that applies custom filter
	 * Allows taxonomy term counts to include user-specified post statuses.
	 *
	 * @since 1.1
	 * @param Array  $terms    the terms to filter.
	 * @param Object $taxonomy the taxonomy slug.
	 */
	public static function term_count_cb_sel( $terms, $taxonomy ) {
		add_filter( 'query', array( __CLASS__, 'term_count_query_filter_sel' ) );
		_update_post_term_count( $terms, $taxonomy );
		remove_filter( 'query', array( __CLASS__, 'term_count_query_filter_sel' ) );
	}

	/**
	 * Alters term count query to include selected list of post statuses.
	 * See generally, #17548
	 *
	 * @since 1.1
	 * @param Object $query the query object.
	 * @return String the modified query
	 */
	public static function term_count_query_filter_sel( $query ) {
		if ( 'SELECT COUNT(*)' !== substr( $query, 0, 15 ) ) {
			return $query;
		}
		global $wpdb;

		// Target string. Find taxonomy via taxonomy term.
		$term_tax_id = (int) substr( $query, strpos( $query, 'term_taxonomy_id = ' ) + 19 );
		// phpcs:ignore
		$taxonomy = $wpdb->get_var( $wpdb->prepare( "SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", $term_tax_id ) );
		$filter   = self::term_count_sel_cache( $taxonomy );

		return str_replace( "= 'publish'", $filter['in_string'], $query );
	}


	/**
	 * Modifies the term count query looking at different list of post statuses.
	 * See generally, #17548, and #40351
	 *
	 * @since 1.2
	 * @param array  $status   array of post statuses to count.
	 * @param string $taxonomy the taxonomy name.
	 * @return array
	 */
	public static function review_statuses( $status, $taxonomy ) {
		$filter = self::term_count_sel_cache( $taxonomy );
		if ( empty( $filter['types'] ) ) {
			return $status;
		}

		return $filter['types'];
	}

	/**
	 * Check that one (and only one) taxonomy term is entered for published documents.
	 *
	 * Invoked *before* post is inserted/updated.
	 *
	 * @since 1.2.0
	 *
	 * @param bool  $maybe_empty Whether the post should be considered "empty".
	 * @param array $postarr     Array of post data.
	 */
	public static function check_taxonomy_value_set( $maybe_empty, $postarr ) {
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
		$options = get_option( OPTION_STAXO );
		if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
			foreach ( (array) $options['taxonomies'] as $taxonomy ) {
				// Is this taxonomy handled by post.
				if ( ! in_array( $postarr['post_type'], $taxonomy['objects'], true ) ) {
					continue;
				}
				// is terms control defined and non-zero.
				if ( ( ! isset( $taxonomy['st_cc_type'] ) ) || empty( $taxonomy['st_cc_type'] ) ) {
					continue;
				}
				if ( ( ! isset( $taxonomy['st_cc_hard'] ) ) || empty( $taxonomy['st_cc_hard'] ) ) {
					continue;
				}
				// check the post_status (trash already excluded so all cc_type 2 need processing).
				if ( 1 === $taxonomy['st_cc_type'] && ! in_array( $post_status, array( 'publish', 'future' ), true ) ) {
					continue;
				}

				$error_type = '';
				// count the number of terms.
				$terms_count = 0;
				if ( isset( $postarr['tax_input'][ $taxonomy['name'] ] ) ) {
					$terms = $postarr['tax_input'][ $taxonomy['name'] ];
					// ignore the 0 element.
					unset( $terms[0] );
					$terms_count = ( empty( $terms ) ? 0 : count( $terms ) );
				}

				// check the minimum bound.
				if ( true === (bool) $taxonomy['st_cc_umin'] && $terms_count < $taxonomy['st_cc_min'] ) {
					$error_type = 'min';
				}
				// check the minimum bound.
				if ( true === (bool) $taxonomy['st_cc_umax'] && $terms_count > $taxonomy['st_cc_max'] ) {
					$error_type .= 'max';
				}

				// commen error path.
				if ( '' !== $error_type ) {
					$referer = ( array_key_exists( '_wp_http_referer', $postarr ) ? $postarr['_wp_http_referer'] : esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
					$url = add_query_arg(
						array(
							'post'        => $postarr['ID'],
							'action'      => 'edit',
							'staxo_tax'   => $taxonomy['name'],
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
	 * Output any error when checking that taxonomy term bounds missed..
	 *
	 * Invoked on publish of a document (so no need to check).
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
						// translators: %s is the taxonomy name.
						echo esc_html( sprintf( __( 'Your post needs to have more terms for the taxonomy - %s entered.', 'wp-document-revisions' ), $label ) );
						break;
					case 'max':
						// translators: %s is the taxonomy name.
						echo esc_html( sprintf( __( 'Your post needs to have less terms for taxonomy - %s.', 'wp-document-revisions' ), $label ) );
						break;
					case 'minmax':
						// translators: %s is the taxonomy name.
						echo esc_html( sprintf( __( 'Your post needs to have more terms for the taxonomy - %s entered.', 'wp-document-revisions' ), $label ) );
						echo '</p><p>';
						// translators: %s is the taxonomy name.
						echo esc_html( sprintf( __( 'Your post needs to have less terms for taxonomy - %s.', 'wp-document-revisions' ), $label ) );
						break;
					default:
						null;
				};
				?>
				</p>
				<p><?php esc_html_e( 'Your update has been cancelled and stored data remains unchanged.', 'wp-document-revisions' ); ?></p>
				<p><?php esc_html_e( 'Your browser may hold an updated version, but it is currently invalid.', 'wp-document-revisions' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Get array fields for CPT object.
	 *
	 * @return array
	 */
	public static function get_taxonomy_default_fields() {
		return array(
			'name'                     => '',
			'description'              => '',
			'labels'                   => array(),
			'public'                   => 1,
			'publicly_queryable'       => 1,
			'hierarchical'             => 1,
			'show_ui'                  => 1,
			'show_in_menu'             => 1,
			'show_in_nav_menus'        => 1,
			'show_tagcloud'            => 1,
			'show_in_quick_edit'       => 1,
			'show_admin_column'        => 1,
			'capabilities'             => array(),
			'rewrite'                  => 0,
			'query_var'                => '',
			'update_count_callback'    => '',
			'show_in_rest'             => 1,
			'rest_base'                => '',
			'rest_controller_class'    => '',
			'sort'                     => 0,
			// Specific to plugin.
			'objects'                  => array(),
			'auto'                     => 'none',
			'st_before'                => '',
			'st_after'                 => '',
			'st_slug'                  => '',
			'st_with_front'            => 1,
			'st_hierarchical'          => 1,
			'st_ep_mask'               => '',
			'st_show_in_graphql'       => 0,
			'st_graphql_single'        => '',
			'st_graphql_plural'        => '',
			'st_update_count_callback' => '',
			'st_meta_box_cb'           => '',
			'st_meta_box_sanitize_cb'  => '',
			'st_args'                  => '',
			'st_adm_types'             => array(),
			'st_adm_hier'              => 0,
			'st_adm_depth'             => 0,
			'st_adm_count'             => 0,
			'st_adm_h_e'               => 0,
			'st_adm_h_i_e'             => 0,
			'st_cb_type'               => 0,
			'st_cb_pub'                => 0,
			'st_cb_fut'                => 0,
			'st_cb_dft'                => 0,
			'st_cb_pnd'                => 0,
			'st_cb_prv'                => 0,
			'st_cb_tsh'                => 0,
			'st_cc_type'               => 0,
			'st_cc_hard'               => 0,
			'st_cc_umin'               => 0,
			'st_cc_umax'               => 0,
			'st_cc_min'                => 0,
			'st_cc_max'                => 0,
			'st_dft_name'              => '',
			'st_dft_slug'              => '',
			'st_dft_desc'              => '',
			'metabox'                  => 'default',  // compatibility.
		);
	}

	/**
	 * Get array fields for CPT object.
	 *
	 * @return array
	 */
	public static function get_taxonomy_default_labels() {
		return array(
			'name'                       => _x( 'Post Terms', 'taxonomy general name', 'simple-taxonomy-refreshed' ),
			'menu_name'                  => '',
			'singular_name'              => _x( 'Post Term', 'taxonomy singular name', 'simple-taxonomy-refreshed' ),
			'search_items'               => __( 'Search Terms', 'simple-taxonomy-refreshed' ),
			'popular_items'              => __( 'Popular Terms', 'simple-taxonomy-refreshed' ),
			'all_items'                  => __( 'All Terms', 'simple-taxonomy-refreshed' ),
			'parent_item'                => __( 'Parent Term', 'simple-taxonomy-refreshed' ),
			'parent_item_colon'          => __( 'Parent Term:', 'simple-taxonomy-refreshed' ),
			'edit_item'                  => __( 'Edit Term', 'simple-taxonomy-refreshed' ),
			'view_item'                  => __( 'View Term', 'simple-taxonomy-refreshed' ),
			'update_item'                => __( 'Update Term', 'simple-taxonomy-refreshed' ),
			'add_new_item'               => __( 'Add New Term', 'simple-taxonomy-refreshed' ),
			'new_item_name'              => __( 'New Term Name', 'simple-taxonomy-refreshed' ),
			'separate_items_with_commas' => __( 'Separate terms with commas', 'simple-taxonomy-refreshed' ),
			'add_or_remove_items'        => __( 'Add or remove terms', 'simple-taxonomy-refreshed' ),
			'choose_from_most_used'      => __( 'Choose from the most used terms', 'simple-taxonomy-refreshed' ),
			'not_found'                  => __( 'No Terms found', 'simple-taxonomy-refreshed' ),
			'no_terms'                   => __( 'No Terms', 'simple-taxonomy-refreshed' ),
			'items_list_navigation'      => __( 'Terms list navigation', 'simple-taxonomy-refreshed' ),
			'items_list'                 => __( 'Terms list', 'simple-taxonomy-refreshed' ),
			/* translators: Tab heading when selecting from the most used terms. */
			'most_used'                  => _x( 'Most Used', 'simple-taxonomy-refreshed' ),
			'back_to_items'              => __( '&#8592; Back to Terms', 'simple-taxonomy-refreshed' ),
		);
	}

	/**
	 * Get array fields for CPT object.
	 *
	 * @return array
	 */
	public static function get_taxonomy_default_capabilities() {
		return array(
			'manage_terms' => 'manage_categories',
			'edit_terms'   => 'manage_categories',
			'delete_terms' => 'manage_categories',
			'assign_terms' => 'edit_posts',
		);
	}
}
