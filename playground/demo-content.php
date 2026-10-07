<?php
/**
 * Demo content for the Simple Taxonomy Refreshed playground.
 *
 * Loaded by playground/blueprint.json (and blueprint-local.json) once the plugin is active:
 * it defines five taxonomies, sets extra functions on Tags, creates their terms and a set of
 * posts and a page that show what the plugin does. The documentation in docs/ describes this site.
 *
 * The taxonomies follow the plugin's test suite (tests/files/staxo-config-suite.json and the
 * end-to-end radio fixture), with names for a music and arts magazine:
 *
 * | Taxonomy  | Based on  | Shows                                                         |
 * |-----------|-----------|---------------------------------------------------------------|
 * | genre     | test_hier | Hierarchical; terms shown after the post content; rewrite URL |
 * | colour    | test_flat | Flat, posts and pages; REST base; default term; content+excerpt |
 * | audience  | test_cntl | Term Control: 1 or 2 terms on every saved post                |
 * | topic     | test_count| Term Count of published and draft posts; admin list filter    |
 * | format    | e2e_kind  | At most one term: radio buttons with "No term"               |
 *
 * @package simple-taxonomy-refreshed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings of a taxonomy defined by the plugin, from the plugin's defaults.
 *
 * @param string $name     taxonomy name (slug).
 * @param string $singular singular label.
 * @param string $plural   plural label.
 * @param array  $settings settings that differ from the defaults.
 * @return array
 */
function staxo_demo_taxonomy( $name, $singular, $plural, $settings ) {
	$hier   = ! empty( $settings['hierarchical'] );
	$labels = SimpleTaxonomyRefreshed_Client::get_taxonomy_default_labels( $hier ? 1 : 0 );

	$labels['name']          = $plural;
	$labels['singular_name'] = $singular;

	return array_merge(
		SimpleTaxonomyRefreshed_Client::get_taxonomy_default_fields(),
		array(
			'name'         => $name,
			'labels'       => $labels,
			'capabilities' => SimpleTaxonomyRefreshed_Client::get_taxonomy_default_capabilities(),
			'query_var'    => $name,
		),
		$settings
	);
}

/**
 * Create a tree of terms.
 *
 * @param string $taxonomy taxonomy name.
 * @param array  $tree     term name => child tree (array) for each term.
 * @param int    $parent   parent term id.
 * @return void
 */
function staxo_demo_terms( $taxonomy, $tree, $parent = 0 ) {
	foreach ( $tree as $name => $children ) {
		$term = wp_insert_term( $name, $taxonomy, array( 'parent' => $parent ) );
		if ( is_array( $term ) && ! empty( $children ) ) {
			staxo_demo_terms( $taxonomy, $children, (int) $term['term_id'] );
		}
	}
}

/**
 * Attach terms (by name) to a post.
 *
 * @param int   $post_id post id.
 * @param array $terms   taxonomy => term names.
 * @return void
 */
function staxo_demo_set_terms( $post_id, $terms ) {
	foreach ( $terms as $taxonomy => $names ) {
		$ids = array();
		foreach ( $names as $name ) {
			$term = get_term_by( 'name', $name, $taxonomy );
			if ( $term ) {
				$ids[] = (int) $term->term_id;
			}
		}
		wp_set_object_terms( $post_id, $ids, $taxonomy );
	}
}

/**
 * Load the demo: settings, terms, posts and the demo page.
 *
 * @return void
 */
function staxo_demo_load() {
	// Work as the administrator so that block markup in the content is kept as written.
	wp_set_current_user( 1 );
	kses_remove_filters();

	SimpleTaxonomyRefreshed_Client::get_wp_default_labels();

	$taxonomies = array(
		'genre'    => staxo_demo_taxonomy(
			'genre',
			'Genre',
			'Genres',
			array(
				'hierarchical' => 1,
				'objects'      => array( 'post' ),
				'auto'         => 'content',
				'st_before'    => 'Genres:',
				'rewrite'      => 1,
				'st_slug'      => 'genre',
			)
		),
		'colour'   => staxo_demo_taxonomy(
			'colour',
			'Colour',
			'Colours',
			array(
				'hierarchical' => 0,
				'objects'      => array( 'post', 'page' ),
				'auto'         => 'both',
				'st_before'    => 'Colours:',
				'rest_base'    => 'colours',
				'st_dft_name'  => 'Unsorted',
				'st_dft_slug'  => 'unsorted',
			)
		),
		'audience' => staxo_demo_taxonomy(
			'audience',
			'Audience',
			'Audiences',
			array(
				'hierarchical' => 0,
				'objects'      => array( 'post' ),
				'st_cc_type'   => 2,
				'st_cc_hard'   => 1,
				'st_cc_umin'   => 1,
				'st_cc_min'    => 1,
				'st_cc_umax'   => 1,
				'st_cc_max'    => 2,
			)
		),
		'topic'    => staxo_demo_taxonomy(
			'topic',
			'Topic',
			'Topics',
			array(
				'hierarchical' => 1,
				'objects'      => array( 'post' ),
				'st_cb_type'   => 2,
				'st_cb_pub'    => 1,
				'st_cb_dft'    => 1,
				'st_adm_types' => array( 'post' ),
				'st_adm_hier'  => 1,
				'st_adm_count' => 1,
			)
		),
		'format'   => staxo_demo_taxonomy(
			'format',
			'Format',
			'Formats',
			array(
				'hierarchical' => 1,
				'objects'      => array( 'post' ),
				'st_cc_type'   => 2,
				'st_cc_hard'   => 0,
				'st_cc_umin'   => 0,
				'st_cc_min'    => 0,
				'st_cc_umax'   => 1,
				'st_cc_max'    => 1,
			)
		),
	);

	// Extra functions on WordPress's own Tags: an admin list filter, and counts of all posts except trash.
	$externals = array(
		'post_tag' => array(
			'name'         => 'post_tag',
			'hierarchical' => 0,
			'show_ui'      => 1,
			'st_adm_types' => array( 'post' ),
			'st_adm_count' => 1,
			'st_cb_type'   => 1,
		),
	);

	update_option(
		OPTION_STAXO,
		array(
			'taxonomies' => $taxonomies,
			'externals'  => $externals,
			'list_order' => array( 'post' => array( 'genre', 'topic', 'format', 'audience', 'colour', 'category', 'post_tag' ) ),
		),
		true
	);

	// Register the taxonomies now (normally done on init) so that terms and posts can use them.
	SimpleTaxonomyRefreshed_Client::init();
	SimpleTaxonomyRefreshed_Client::init_2();
	SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache();

	// Terms: the test suite's trees and lists.
	staxo_demo_terms(
		'genre',
		array(
			'Music' => array(
				'Jazz' => array(
					'Bebop'    => array(),
					'Big Band' => array(),
				),
				'Rock' => array( 'Punk' => array() ),
			),
			'Art'   => array(
				'Painting'  => array(),
				'Sculpture' => array(),
			),
			'Misc'  => array(),
		)
	);
	staxo_demo_terms(
		'topic',
		array(
			'Science'    => array(
				'Physics' => array(
					'Astronomy' => array(),
					'Quantum'   => array(),
				),
				'Biology' => array( 'Botany' => array() ),
			),
			'Humanities' => array(
				'History'    => array(),
				'Philosophy' => array(),
			),
			'Misc'       => array(),
		)
	);
	staxo_demo_terms( 'colour', array_fill_keys( array( 'Red', 'Green', 'Blue', 'Yellow', 'Cyan' ), array() ) );
	staxo_demo_terms( 'audience', array_fill_keys( array( 'Beginners', 'Experts', 'Students', 'Teachers', 'Everyone' ), array() ) );
	staxo_demo_terms( 'format', array_fill_keys( array( 'Article', 'Review', 'Interview' ), array() ) );

	// Posts: the test suite's post matrix, one post for each status.
	$posts = array(
		array( 'A night of jazz', 'publish', array( 'Jazz' ), array( 'Red', 'Green' ), array( 'Beginners' ), array( 'Physics' ), array( 'Article' ), array( 'live' ) ),
		array( 'Bebop basics', 'publish', array( 'Bebop' ), array( 'Green' ), array( 'Experts' ), array( 'Astronomy' ), array( 'Review' ), array( 'study' ) ),
		array( 'From jazz to bebop', 'publish', array( 'Jazz', 'Bebop' ), array( 'Blue' ), array( 'Beginners', 'Students' ), array( 'Physics' ), array( 'Article' ), array( 'study', 'live' ) ),
		array( 'The big band sound', 'publish', array( 'Big Band' ), array( 'Red', 'Blue' ), array( 'Students' ), array(), array( 'Interview' ), array() ),
		array( 'Rock notes (draft)', 'draft', array( 'Rock' ), array( 'Yellow' ), array(), array( 'Biology' ), array(), array() ),
		array( 'Punk review (pending)', 'pending', array( 'Punk' ), array( 'Yellow' ), array(), array( 'Botany' ), array(), array() ),
		array( 'Painting diary (private)', 'private', array( 'Painting' ), array( 'Cyan' ), array(), array( 'History' ), array(), array() ),
		array( 'Sculpture next year (scheduled)', 'future', array( 'Sculpture' ), array(), array(), array( 'Philosophy' ), array(), array() ),
		array( 'Old jazz listings (trash)', 'trash', array( 'Jazz' ), array( 'Red' ), array(), array( 'Physics' ), array(), array() ),
	);
	foreach ( $posts as $post ) {
		$args = array(
			'post_title'   => $post[0],
			'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $post[0] ) . ': a sample post for Simple Taxonomy Refreshed.</p><!-- /wp:paragraph -->',
			'post_excerpt' => 'A sample post.',
			'post_status'  => ( 'trash' === $post[1] ? 'publish' : $post[1] ),
		);
		if ( 'future' === $post[1] ) {
			$args['post_date'] = gmdate( 'Y-m-d H:i:s', time() + YEAR_IN_SECONDS );
		}
		$post_id = wp_insert_post( $args );
		staxo_demo_set_terms(
			$post_id,
			array(
				'genre'    => $post[2],
				'colour'   => $post[3],
				'audience' => $post[4],
				'topic'    => $post[5],
				'format'   => $post[6],
				'post_tag' => $post[7],
			)
		);
		if ( 'trash' === $post[1] ) {
			wp_trash_post( $post_id );
		}
	}

	// A page with the plugin's blocks and shortcode.
	$page_id = wp_insert_post(
		array(
			'post_title'   => 'Taxonomy blocks',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => implode(
				"\n\n",
				array(
					'<!-- wp:paragraph --><p>This page shows the blocks and shortcode of Simple Taxonomy Refreshed.</p><!-- /wp:paragraph -->',
					'<!-- wp:heading --><h2 class="wp-block-heading">Display Post Terms block</h2><!-- /wp:heading -->',
					'<!-- wp:simple-taxonomy-refreshed/staxo-terms {"tax":"colour"} /-->',
					'<!-- wp:heading --><h2 class="wp-block-heading">Taxonomy Cloud block: cloud</h2><!-- /wp:heading -->',
					'<!-- wp:simple-taxonomy-refreshed/cloud-widget {"title":"Genres","taxonomy":"genre","disptype":"cloud"} /-->',
					'<!-- wp:heading --><h2 class="wp-block-heading">Taxonomy Cloud block: list</h2><!-- /wp:heading -->',
					'<!-- wp:simple-taxonomy-refreshed/cloud-widget {"title":"Topics","taxonomy":"topic","disptype":"list","showcount":true} /-->',
					'<!-- wp:heading --><h2 class="wp-block-heading">Shortcode</h2><!-- /wp:heading -->',
					'<!-- wp:shortcode -->[staxo_post_terms tax="colour"]<!-- /wp:shortcode -->',
				)
			),
		)
	);
	staxo_demo_set_terms( $page_id, array( 'colour' => array( 'Red' ) ) );

	// New rewrite rules for the Genres URL.
	set_transient( 'simple_taxonomy_refreshed_rewrite', true, 0 );
	flush_rewrite_rules( false );
}
