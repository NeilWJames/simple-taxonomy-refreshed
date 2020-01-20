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
	}

	/**
	 * Register all custom taxonomies to WordPress process.
	 *
	 * @return void
	 */
	public static function init() {
		$options = get_option( OPTION_STAXO );
		if ( is_array( $options['taxonomies'] ) ) {
			foreach ( (array) $options['taxonomies'] as $taxonomy ) {
				register_taxonomy( $taxonomy['name'], $taxonomy['objects'], self::prepare_args( $taxonomy ) );
			};
			// need to refresh the rewrite rules once registered.
			if ( get_transient( 'simple_taxonomy_refreshed_rewrite' ) ) {
				delete_transient( 'simple_taxonomy_refreshed_rewrite' );
				flush_rewrite_rules( false );
			}
		}
	}

	/**
	 * Prepare ARGS from DB to function API.
	 *
	 * @param array $taxonomy  taxonomy structure.
	 * @return array
	 */
	public static function prepare_args( $taxonomy ) {
		// Empty query_var ? use name.
		if ( empty( $taxonomy['query_var'] ) ) {
			$taxonomy['query_var'] = $taxonomy['name'];
		} else {
			$taxonomy['query_var'] = trim( $taxonomy['query_var'] );
		}

		// Ensure complete.
		$taxonomy                 = wp_parse_args( $taxonomy, self::get_taxonomy_default_fields() );
		$taxonomy['labels']       = wp_parse_args( $taxonomy['labels'], self::get_taxonomy_default_labels() );
		$taxonomy['capabilities'] = wp_parse_args( $taxonomy['capabilities'], self::get_taxonomy_default_capabilities() );

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
			'show_in_rest'          => (bool) $taxonomy['show_in_rest'],
			'rest_base'             => $taxonomy['rest_base'],
			'rest_controller_class' => $taxonomy['rest_controller_class'],
			'sort'                  => (bool) $taxonomy['sort'],
		);

		// code-related fields. Can't assume null is valid.
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

		/*
		 * Filter to set the taxonomy arguments to store.
		 *
		 * @param  array  $tax_out  input set of existing register_taxonomy fields (plus name).
		 * @param  array  $taxonomy taxonomy parameters from screen or DB.
		 * @return array  output set of register_taxonomy fields (plus name).
		 */
		return apply_filters( 'staxo_prepare_args', $tax_out, $taxonomy );
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

		$output = '';

		$options = get_option( OPTION_STAXO );
		foreach ( (array) $options['taxonomies'] as $taxonomy ) {

			$filter = false;
			if ( isset( $taxonomy['auto'] ) && 'both' === $taxonomy['auto'] ) {
				$filter = true;
			} elseif ( isset( $taxonomy['auto'] ) && $type === $taxonomy['auto'] ) {
				$filter = true;
			}

			if ( true === $filter ) {
				$terms = get_the_term_list( $post->ID, $taxonomy['name'], $taxonomy['labels']['name'] . ': ', ', ', '' );
				if ( ! empty( $terms ) ) {
					$output .= "\t" . '<div class="taxonomy-' . $taxonomy['name'] . '">' . $terms . "</div>\n";
				} else {
						// On migration and before update, no value in 'not_found'.
						$notfound = ( isset( $taxonomy['labels']['not_found'] ) ? $taxonomy['labels']['not_found'] : 'No Terms found' );
					$output      .= "\t" . '<!-- Taxonomy : ' . $taxonomy['name'] . ' : ' . $notfound . ' -->' . "\n";
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
			'metabox'                  => 'default',  // compatibility.
		);
	}

	/**
	 * Get array fields for CPT object.
	 *
	 * @return array
	 */
	private static function get_taxonomy_default_labels() {
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
	private static function get_taxonomy_default_capabilities() {
		return array(
			'manage_terms' => 'manage_categories',
			'edit_terms'   => 'manage_categories',
			'delete_terms' => 'manage_categories',
			'assign_terms' => 'edit_posts',
		);
	}
}
