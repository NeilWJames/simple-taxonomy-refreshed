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
	 * Default WP taxonomy labels.
	 *
	 * @var array
	 */
	public static $wp_default_labels = array();

	/**
	 * Default WP taxonomy labels but html decoded.
	 *
	 * @var array
	 */
	public static $wp_decoded_labels = array();

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( __CLASS__, 'rest_api_init' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'init' ), 1 );
		add_action( 'init', array( __CLASS__, 'init' ), 1 );
		add_action( 'init', array( __CLASS__, 'init_2' ), 99999 );
		add_action( 'init', array( __CLASS__, 'staxo_terms_block' ), 99999 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );

		add_filter( 'the_excerpt', array( __CLASS__, 'the_excerpt' ), 10, 1 );
		add_filter( 'the_content', array( __CLASS__, 'the_content' ), 10, 1 );
		add_shortcode( 'staxo_post_terms', array( __CLASS__, 'the_terms' ), 10, 1 );
		add_filter( 'the_category_rss', array( __CLASS__, 'the_category_feed' ), 10, 2 );

		add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
		add_filter( 'wp_title', array( __CLASS__, 'wp_title' ), 10, 2 );

		add_action( 'restrict_manage_posts', array( __CLASS__, 'manage_filters' ) );

		// WPGraphQL for external taxonomies.
		$options = get_option( OPTION_STAXO );
		if ( isset( $options['externals'] ) && is_array( $options['externals'] ) ) {
			add_action( 'registered_taxonomy', array( __CLASS__, 'registered_taxonomy' ), 10, 3 );
		}
	}


	/**
	 * Add rest api init process.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Server $wp_rest_server Server object.
	 * @return void
	 */
	public static function rest_api_init( $wp_rest_server ) {
		$cntl_post_types = self::refresh_term_cntl_cache( false );
		// if terms control wanted, invoke the code.
		if ( isset( $cntl_post_types ) && ! empty( $cntl_post_types ) ) {
			global $stra;
			if ( ! $stra ) {
				// we are in the includes directory.
				require_once __DIR__ . '/class-simpletaxonomyrefreshed-admin.php';
				$stra = SimpleTaxonomyRefreshed_Admin::get_instance();
			}

			// register rest filters for any post types.
			foreach ( $cntl_post_types as $post_type => $tax ) {
				add_filter( "rest_pre_insert_{$post_type}", array( $stra, 'check_taxonomy_value_rest' ), 10, 2 );
			}
		}
	}

	/**
	 * Register all custom taxonomies to WordPress process.
	 *
	 * @return void
	 */
	public static function init() {
		// determine whether to invoke old or new count method (Change with WP 5.7 #38843).
		global $wp_version;
		$vers = strpos( $wp_version, '-' );
		$vers = $vers ? substr( $wp_version, 0, $vers ) : $wp_version;
		if ( version_compare( $vers, '5.7' ) >= 0 ) {
			// core method introduced with version 5.7.
			$count_method = 'new';
		} else {
			$count_method = 'old';
		}
		// get default taxonomy labels.
		self::get_wp_default_labels();
		$options = get_option( OPTION_STAXO );
		// is terms count implemented anywhere with new rules.
		$terms_count = false;
		if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
			foreach ( (array) $options['taxonomies'] as $taxonomy ) {
				$args = self::prepare_args( $taxonomy );

				// Update callback if term count callback wanted.
				if ( '' === $args['update_count_callback'] && isset( $taxonomy['st_cb_type'] ) && $taxonomy['st_cb_type'] > 0 ) {
					if ( 'new' === $count_method ) {
						$terms_count = true;
					} else {
						$args['update_count_callback'] = array( __CLASS__, 'term_count_cb_sel' );
					}
				}

				register_taxonomy( $taxonomy['name'], $taxonomy['objects'], $args );

				// avoid side effects (see https://developer.wordpress.org/reference/functions/register_taxonomy/).
				if ( ! empty( $taxonomy['objects'] ) ) {
					foreach ( $taxonomy['objects'] as $cpt ) {
						register_taxonomy_for_object_type( $taxonomy['name'], $cpt );
					}
				}
			};
		}

		// Repeat for Externals. Note, that the external taxonomy may not yet be registered.
		if ( isset( $options['externals'] ) && is_array( $options['externals'] ) ) {
			foreach ( (array) $options['externals'] as $key => $args ) {
				if ( isset( $args['st_cb_type'] ) && $args['st_cb_type'] > 0 ) {
					$taxonomy = get_taxonomy( $key );

					// Update callback if term count callback wanted.
					// If not yet registered, go via init_2 to update.
					if ( ! isset( $taxonomy ) || '' === $taxonomy->update_count_callback ) {
						if ( 'new' === $count_method ) {
							$terms_count = true;
						}
					}
				}
			};
		}

		// need to refresh the rewrite rules once registered.
		if ( get_transient( 'simple_taxonomy_refreshed_rewrite' ) ) {
			delete_transient( 'simple_taxonomy_refreshed_rewrite' );
			flush_rewrite_rules( false );
		}
		// if terms count wanted and new taxonomy, set up the code.
		if ( $terms_count ) {
			// filters the post statuses to implement the taxonomy counts.
			add_filter( 'update_post_term_count_statuses', array( __CLASS__, 'review_count_statuses' ), 30, 2 );
		}
	}

	/**
	 * Process count for old moethod for external plugin taxonomies.
	 *
	 * @return void
	 */
	public static function init_2() {
		// determine whether to invoke old or new count method.
		global $wp_version;
		$vers = strpos( $wp_version, '-' );
		$vers = $vers ? substr( $wp_version, 0, $vers ) : $wp_version;
		if ( version_compare( $vers, '5.7' ) >= 0 ) {
			// core method introduced with version 5.7.
			return;
		}

		// Only concerned for old method, i.e. version < 5.7.
		$options = get_option( OPTION_STAXO );

		// Set update method if using default.
		if ( isset( $options['externals'] ) && is_array( $options['externals'] ) ) {
			foreach ( (array) $options['externals'] as $key => $args ) {
				if ( isset( $args['st_cb_type'] ) && $args['st_cb_type'] > 0 ) {
					$taxonomy = get_taxonomy( $key );

					// Update callback if term count callback wanted.
					if ( '' === $taxonomy->update_count_callback ) {
						global $wp_taxonomies;
						$wp_taxonomies[ $key ]->update_count_callback = array( __CLASS__, 'term_count_cb_sel' );
					}
				}
			};
		}
	}

	/**
	 * Create filters for taxonomy order on screens if set.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function admin_init() {
		$options = get_option( OPTION_STAXO );
		if ( isset( $options['list_order'] ) && is_array( $options['list_order'] ) ) {
			foreach ( $options['list_order'] as $post_type => $taxos ) {
				add_action( "manage_taxonomies_for_{$post_type}_columns", array( __CLASS__, 'reorder_admin_list' ), 10, 2 );
			}
		}
	}

	/**
	 * Modify external taxonomies for WPGraphql if set.
	 *
	 * @since 2.0.0
	 *
	 * @param string       $taxonomy        Taxonomy slug.
	 * @param array|string $object_type     Object type or array of object types.
	 * @param array        $taxonomy_object Array of taxonomy registration arguments.
	 */
	public static function registered_taxonomy( $taxonomy, $object_type, $taxonomy_object ) {
		$options = get_option( OPTION_STAXO );
		if ( isset( $options['externals'] ) && is_array( $options['externals'] ) ) {
			$externals = $options['externals'];
			if ( isset( $externals[ $taxonomy ] ) && (bool) $externals[ $taxonomy ]['st_show_in_graphql'] ) {
				global $wp_taxonomies;
				// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
				$wp_taxonomies[ $taxonomy ]['show_in_graphql'] = true;
				$wp_taxonomies[ $taxonomy ]['graphql_single']  = $externals[ $taxonomy ]['st_graphql_single'];
				$wp_taxonomies[ $taxonomy ]['graphql_plural']  = $externals[ $taxonomy ]['st_graphql_plural'];
				// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}
	}

	/**
	 * Change taxonomy order on admin screens if set.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $taxonomies  taxonomy name list.
	 * @param string $post_type   post type.
	 * @return string[].
	 */
	public static function reorder_admin_list( $taxonomies, $post_type ) {
		$options = get_option( OPTION_STAXO );
		return $options['list_order'][ $post_type ];
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
		$hier                     = ( isset( $taxonomy['hierarchical'] ) ? $taxonomy['hierarchical'] : 1 );
		$taxonomy['labels']       = wp_parse_args( $taxonomy['labels'], self::get_taxonomy_default_labels( $hier ) );
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
		if ( ! empty( $taxonomy['rest_base'] ) ) {
			$tax_out['rest_base'] = $taxonomy['rest_base'];
		}
		if ( ! empty( $taxonomy['rest_namespace'] ) ) {
			$tax_out['rest_namespace'] = $taxonomy['rest_namespace'];
		}
		if ( ! empty( $taxonomy['rest_controller_class'] ) ) {
			$tax_out['rest_controller_class'] = $taxonomy['rest_controller_class'];
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param string $content content of the content or excerpt.
	 * @param string $type    content, excerpt or arbitrary selector.
	 * @param string $tax     taxonomy slug for arbitrary call (optional).
	 * @return string
	 */
	public static function taxonomy_filter( $content, $type, $tax = '' ) {
		global $post;

		if ( ! isset( $post->post_type ) ) {
			return $content;
		}

		$options = get_option( OPTION_STAXO );
		if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
			null; // Drop through.
		} else {
			return $content;
		}

		$output = '';

		foreach ( (array) $options['taxonomies'] as $taxonomy ) {
			// bypass if we want only a specific taxonomy and this isn't it.
			if ( '' !== $tax && $tax !== $taxonomy['name'] ) {
				continue;
			}
			// Does the post_type uses this taxonomy.
			if ( ( 'arbitrary' === $type || isset( $taxonomy['auto'] ) ) && ( ! empty( $taxonomy['objects'] ) ) && in_array( $post->post_type, $taxonomy['objects'], true ) ) {
				if ( 'both' === $taxonomy['auto'] || $type === $taxonomy['auto'] || 'arbitrary' === $type ) {
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param string $content content of the_excerpt.
	 * @return string
	 */
	public static function the_excerpt( $content = '' ) {
		return self::taxonomy_filter( $content, 'excerpt' );
	}

	/**
	 * Meta function for call filter taxonomy with arbitrary context.
	 *
	 * @since 2.2.0
	 *
	 * @param array $atts shortcode attributes (not currently used).
	 * @return string
	 */
	public static function the_terms( $atts ) {
		$tax = shortcode_atts(
			array(
				'tax' => '',
			),
			$atts
		);
		return self::taxonomy_filter( '', 'arbitrary', $tax['tax'] );
	}


	/**
	 * Get taxonomy names for selection (use cache).
	 *
	 * @return Array Taxonomy names for documents
	 * @since 2.2.0
	 */
	public static function get_taxonomies() {
		$taxonomies = wp_cache_get( 'staxo_own_taxos', '' );

		if ( false === $taxonomies ) {
			$taxonomies = array();
			// build and create cache entry.
			$options = get_option( OPTION_STAXO );
			if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
				foreach ( (array) $options['taxonomies'] as $taxonomy ) {
					if ( (bool) $taxonomy['public'] ) {
						$taxonomies[ $taxonomy['name'] ] = ( empty( $taxonomy['labels']['name'] ) ? $taxonomy['name'] : $taxonomy['labels']['name'] );
					}
				}

				asort( $taxonomies );
			}

			wp_cache_set( 'staxo_own_taxos', $taxonomies );
		}

		return $taxonomies;
	}

	/**
	 * Register terms block.
	 *
	 * @since 2.2.0
	 */
	public static function staxo_terms_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active, e.g. Old WP version installed.
			return;
		}

		$dir      = dirname( __DIR__ );
		$suffix   = ( WP_DEBUG ) ? '.dev' : '';
		$index_js = 'js/staxo-terms' . $suffix . '.js';
		wp_register_script(
			'staxo-terms-editor',
			plugins_url( $index_js, __DIR__ ),
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-server-side-render',
				'wp-i18n',
			),
			filemtime( "$dir/$index_js" ),
			true
		);

		// Add supplementary script for additional information.
		// Ensure taxonomies are set.

		$taxonomies = self::get_taxonomies();
		wp_add_inline_script( 'staxo-terms-editor', 'const staxo_own_data = ' . wp_json_encode( $taxonomies ), 'before' );

		register_block_type(
			'simple-taxonomy-refreshed/post-terms',
			array(
				'editor_script'   => 'staxo-terms-editor',
				'render_callback' => array( __CLASS__, 'the_terms' ),
				'attributes'      => array(
					'tax'             => array(
						'type' => 'string',
					),
					'align'           => array(
						'type' => 'string',
					),
					'backgroundColor' => array(
						'type' => 'string',
					),
					'linkColor'       => array(
						'type' => 'string',
					),
					'textColor'       => array(
						'type' => 'string',
					),
					'gradient'        => array(
						'type' => 'string',
					),
					'fontSize'        => array(
						'type' => 'string',
					),
					'style'           => array(
						'type' => 'object',
					),
				),
			)
		);

		// set translations.
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'staxo-terms-editor', 'simple_taxonomy-refreshed' );
		}
	}

	/**
	 * Meta function for call filter taxonomy.
	 *
	 * @param string $the_list All of the RSS post categories.
	 * @param string $type     Type of feed. Possible values include 'rss2', 'atom'.
	 *                         Default 'rss2'.
	 * @return string
	 */
	public static function the_category_feed( $the_list, $type ) {
		global $post;

		if ( ! isset( $post->post_type ) ) {
			return $the_list;
		}

		$options = get_option( OPTION_STAXO );
		if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
			null; // Drop through.
		} else {
			return $the_list;
		}

		$new_list = '';
		$filter   = 'rss';
		if ( 'atom' === $type ) {
			$filter = 'raw';
		}

		foreach ( (array) $options['taxonomies'] as $taxonomy ) {
			// Does the post_type uses this taxonomy.
			if ( isset( $taxonomy['st_feed'] ) && ( ! empty( $taxonomy['objects'] ) ) && in_array( $post->post_type, $taxonomy['objects'], true ) ) {

				$terms      = get_the_terms( $post->ID, $taxonomy['name'] );
				$term_names = array();

				if ( ! empty( $terms ) ) {
					foreach ( (array) $terms as $term ) {
						$term_names[] = sanitize_term_field( 'name', $term->name, $term->term_id, $taxonomy['name'], $filter );
					}
				}

				$term_names = array_unique( $term_names );

				foreach ( $term_names as $term_name ) {
					if ( 'rdf' === $type ) {
						$new_list .= "\t\t<dc:subject><![CDATA[$term_name]]></dc:subject>\n";
					} elseif ( 'atom' === $type ) {
						$new_list .= sprintf( '<%1$s scheme="%2$s" term="%3$s" />', esc_attr( $taxonomy['name'] ), esc_attr( get_bloginfo_rss( 'url' ) ), esc_attr( $term_name ) );
					} else {
						// phpcs:ignore Squiz.Strings.DoubleQuoteUsage
						$new_list .= "\t\t<" . esc_attr( $taxonomy['name'] ) . "><![CDATA[" . html_entity_decode( $term_name, ENT_COMPAT, get_option( 'blog_charset' ) ) . "]]></" . esc_attr( $taxonomy['name'] ) . ">\n";
					}
				}
			}
		}

		return $the_list . $new_list;
	}

	/**
	 * Prepare the dropdown filter args.
	 *
	 * @since 1.3.0
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
			'name'            => $query_var,
			'value_field'     => 'slug',
		);
		return $args;
	}

	/**
	 * Manage the dropdown filters.
	 *
	 * @since 1.3.0
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
		// External taxonomies.
		if ( isset( $options['externals'] ) && is_array( $options['externals'] ) ) {
			foreach ( (array) $options['externals'] as $taxonomy ) {
				// Have we upgraded the taxonomy?
				if ( ! array_key_exists( 'st_adm_types', $taxonomy ) ) {
					continue;
				}
				// Does the post_type use this taxonomy and we want it.
				if ( ! in_array( $post_type, (array) $taxonomy['st_adm_types'], true ) ) {
					continue;
				}
				// Add fields from the defined taxonomy.
				$tax_obj                         = get_taxonomy( $taxonomy['name'] );
				$taxonomy['query_var']           = $tax_obj->query_var;
				$taxonomy['labels']['all_items'] = $tax_obj->labels->all_items;
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
		$tax_details = get_transient( 'staxo_sel_' . $taxonomy );

		if ( false === $tax_details ) {
			$options = get_option( OPTION_STAXO );
			if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) && isset( $options['taxonomies'][ $taxonomy ] ) ) {
				$source = 'taxonomies'; // Drop through.
			} elseif ( isset( $options['taxonomies'] ) && is_array( $options['externals'] ) && isset( $options['externals'][ $taxonomy ] ) ) {
				$source = 'externals'; // Drop through.
			} else {
				$tax_details = array(
					'statuses'  => array(),  // empty means pass though values.
					'in_string' => '',
				);
				set_transient( 'staxo_sel_' . $taxonomy, $tax_details, '', ( WP_DEBUG ? 10 : HOUR_IN_SECONDS ) );

				return $tax_details;
			}

			$taxo     = $options[ $source ][ $taxonomy ];
			$callback = get_taxonomy( $taxonomy )->update_count_callback;
			$statuses = array();
			if ( '' === $callback && isset( $taxo['st_cb_type'] ) ) {
				switch ( $taxo['st_cb_type'] ) {
					case '1':
						$statuses = get_post_stati();
						// trash, inherit and auto-draft to be excluded.
						unset( $statuses['trash'] );
						unset( $statuses['inherit'] );
						unset( $statuses['auto-draft'] );
						break;
					case '2':
						if ( (bool) $taxo['st_cb_pub'] ) {
							$statuses[] = 'publish';
						}
						if ( (bool) $taxo['st_cb_fut'] ) {
							$statuses[] = 'future';
						}
						if ( (bool) $taxo['st_cb_dft'] ) {
							$statuses[] = 'draft';
						}
						if ( (bool) $taxo['st_cb_pnd'] ) {
							$statuses[] = 'pending';
						}
						if ( (bool) $taxo['st_cb_prv'] ) {
							$statuses[] = 'private';
						}
						if ( (bool) $taxo['st_cb_tsh'] ) {
							$statuses[] = 'trash';
						}
						break;
					default:
						null; // leave empty.
				}

				/**
				 * Filter to manage additional post_statuses for Terms Control entered on the screen.
				 *
				 * @param  array  $statuses standard post_statuses entered via screen.
				 * @param  array  $taxonomy taxonomy name.
				 * @return array  $types    post_status slugs to be controlled.
				 */
				$statuses = apply_filters( 'staxo_term_count_statuses', $statuses, $taxonomy );
			}

			// create the string.
			$in_string = "IN ('" . implode( "','", $statuses ) . "') ";

			$tax_details = array(
				'statuses'  => $statuses,
				'in_string' => $in_string,
			);

			set_transient( 'staxo_sel_' . $taxonomy, $tax_details, '', ( WP_DEBUG ? 10 : HOUR_IN_SECONDS ) );
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
	 * @param string $query the query object.
	 * @return string the modified query
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

		if ( empty( $filter['statuses'] ) ) {
			return $query;
		}

		return str_replace( "= 'publish'", $filter['in_string'], $query );
	}


	/**
	 * Modifies the term count query looking at different list of post statuses.
	 * See generally, #17548, and #38843
	 *
	 * @since 1.2.0
	 * @param string[]    $statuses  List of post statuses to include in the count. Default is 'publish'.
	 * @param WP_Taxonomy $taxonomy  Current taxonomy object.
	 * @return array
	 */
	public static function review_count_statuses( $statuses, $taxonomy ) {
		$filter = self::term_count_sel_cache( $taxonomy->name );
		if ( empty( $filter['statuses'] ) ) {
			return $statuses;
		}

		return $filter['statuses'];
	}

	/**
	 * Ensure term control cache is current..
	 *
	 * @since 2.0.0
	 *
	 * @param bool $force_refresh whether to force a refresh.
	 * @return mixed array of post_types and the term control requirements.
	 */
	public static function refresh_term_cntl_cache( $force_refresh = true ) {
		$cache_key       = 'staxo_cntl_post_types';
		$cntl_post_types = get_transient( $cache_key );

		// does cache exist.
		if ( false !== $cntl_post_types ) {
			// yes, but do we to force a refresh.
			if ( ! $force_refresh ) {
				return $cntl_post_types;
			}
			// transient exists but going to update it.
			delete_transient( $cache_key );
		}
		// reset as post_type list as empty.
		$cntl_post_types = array();
		$options         = get_option( OPTION_STAXO );
		if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
			foreach ( (array) $options['taxonomies'] as $taxonomy ) {

				// Identify if term count control limits wanted.
				if ( isset( $taxonomy['st_cc_type'] ) && 0 < $taxonomy['st_cc_type'] ) {
					if ( isset( $taxonomy['st_cc_hard'] ) && ! empty( $taxonomy['st_cc_hard'] ) ) {
						// add to post types list.
						if ( ! empty( $taxonomy['objects'] ) ) {
							foreach ( $taxonomy['objects'] as $post_type ) {
								$cntl_post_types[ $post_type ][ $taxonomy['name'] ] = array(
									'st_cc_type'   => $taxonomy['st_cc_type'],
									'st_cc_hard'   => $taxonomy['st_cc_hard'],
									'st_cc_umin'   => $taxonomy['st_cc_umin'],
									'st_cc_min'    => $taxonomy['st_cc_min'],
									'st_cc_umax'   => $taxonomy['st_cc_umax'],
									'st_cc_max'    => $taxonomy['st_cc_max'],
									'show_in_rest' => $taxonomy['show_in_rest'],
									'rest_base'    => ( empty( $taxonomy['rest_base'] ) ? $taxonomy['name'] : $taxonomy['rest_base'] ),
									'label_name'   => $taxonomy['labels']['name'],
								);
							}
						}
					}
				}
			}
		}

		if ( isset( $options['externals'] ) && is_array( $options['externals'] ) ) {
			foreach ( (array) $options['externals'] as $key => $taxonomy ) {

				// Identify if term count control limits wanted.
				if ( isset( $taxonomy['st_cc_type'] ) && 0 < $taxonomy['st_cc_type'] ) {
					if ( isset( $taxonomy['st_cc_hard'] ) && ! empty( $taxonomy['st_cc_hard'] ) ) {
						// add to post types list.
						if ( ! empty( $taxonomy['objects'] ) ) {
							// need to get some properties from external taxonomy.
							$tax_obj = get_taxonomy( $taxonomy );
							foreach ( $taxonomy['objects'] as $post_type ) {
								$cntl_post_types[ $post_type ][ $taxonomy['name'] ] = array(
									'st_cc_type'   => $taxonomy['st_cc_type'],
									'st_cc_hard'   => $taxonomy['st_cc_hard'],
									'st_cc_umin'   => $taxonomy['st_cc_umin'],
									'st_cc_min'    => $taxonomy['st_cc_min'],
									'st_cc_umax'   => $taxonomy['st_cc_umax'],
									'st_cc_max'    => $taxonomy['st_cc_max'],
									'show_in_rest' => $tax_obj->show_in_rest,
									'rest_base'    => ( empty( $tax_obj->rest_base ) ? $tax_obj->name : $tax_obj->rest_base ),
									'label_name'   => $taxonomy['labels']['name'],
								);
							}
						}
					}
				}
			}
		}

		set_transient( $cache_key, $cntl_post_types, WEEK_IN_SECONDS );

		return $cntl_post_types;
	}

	/**
	 * Get array fields for CPT object.
	 *
	 * @since 1.0.0
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
			'rest_namespace'           => '',
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
			'st_feed'                  => 0,
			'st_dft_name'              => '',
			'st_dft_slug'              => '',
			'st_dft_desc'              => '',
			'metabox'                  => 'default',  // compatibility.
		);
	}

	/**
	 * Get array label fields for CPT object.
	 *
	 * @since 1.0.0
	 *
	 * @param int $hier set of default parameters wanted.
	 * @return array
	 */
	public static function get_taxonomy_default_labels( $hier = 1 ) {
		// These labels us the default WP name domains so that translations are automatically done.
		return self::$wp_default_labels[ $hier ];
	}

	/**
	 * Get array capability fields for CPT object.
	 *
	 * @since 1.0.0
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

	/**
	 * Get WP default labels for either hierarchical or non-hierarhical.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $hier set of default parameters wanted.
	 * @return void
	 */
	private static function get_wp_default_labels_set( $hier ) {
		// get set of labels.
		$set = new WP_Taxonomy(
			'set_dflt',
			array(),
			array(
				'hierarchical' => $hier,
			)
		);
		get_taxonomy_labels( $set );
		// empty labels based on the specific taxonomy slug.
		$labels                   = (array) $set->labels;
		$labels['name']           = '';
		$labels['menu_name']      = '';
		$labels['name_admin_bar'] = '';

		// Add defaults for 5.8 and 5.9 if running earlier versions..
		if ( ! isset( $labels['item_link'] ) ) {
			$labels['item_link']             = __( 'Category Link' );
			$labels['item_link_description'] = __( 'A link to a category' );
		}
		if ( ! isset( $labels['name_field_description'] ) ) {
			$labels['name_field_description'] = __( 'The name is how it appears on your site.' );
			if ( $hier ) {
				$labels['parent_field_description'] = __( 'Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.' );
			} else {
				$labels['parent_field_description'] = null;
			}
			$labels['slug_field_description'] = __( 'The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' );
			$labels['desc_field_description'] = __( 'The description is not prominent by default; however, some themes may show it.' );
		}

		self::$wp_default_labels[ (int) $hier ] = (array) $labels;
		self::$wp_decoded_labels[ (int) $hier ] = array_map( 'html_entity_decode', (array) $labels );
	}

	/**
	 * Get WP default labels.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public static function get_wp_default_labels() {
		// get tag (non_hierarchical) labels.
		self::get_wp_default_labels_set( false );

		// get category (hierarchical) labels.
		self::get_wp_default_labels_set( true );
	}

}
