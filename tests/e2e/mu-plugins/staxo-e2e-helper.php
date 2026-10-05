<?php
/**
 * Plugin Name: STR e2e test helper
 * Description: Used only by the Playwright tests: REST routes to set up Simple Taxonomy Refreshed data, and a switch to the classic editor (staxo_e2e_classic=1). Mounted as a must-use plugin; never shipped.
 *
 * @package simple-taxonomy-refreshed
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'staxo_e2e_register_routes' );

/*
 * Classic editor on request: add staxo_e2e_classic=1 to a post.php or post-new.php URL
 * to edit that post in the classic editor, as the Classic Editor plugin would.
 */
add_filter(
	'use_block_editor_for_post',
	static function ( $use_block_editor ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- test switch only, read-only.
		return isset( $_GET['staxo_e2e_classic'] ) ? false : $use_block_editor;
	}
);

// After a save from the classic editor, return to the classic editor (WordPress
// redirects to post.php without the switch, which would open the block editor).
add_filter(
	'redirect_post_location',
	static function ( $location ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- test switch only; WordPress has checked the post form nonce.
		$referer = isset( $_POST['_wp_http_referer'] ) ? (string) wp_unslash( $_POST['_wp_http_referer'] ) : '';
		return ( str_contains( $referer, 'staxo_e2e_classic' ) ? add_query_arg( 'staxo_e2e_classic', '1', $location ) : $location );
	}
);

/**
 * Register the helper routes (namespace staxo-e2e/v1). Administrators only.
 *
 * POST /reset  Delete the STR settings and the terms of the taxonomies they define.
 * POST /config Load a configuration fixture from tests/files (body: { "file": "staxo-config-suite.json" }).
 *
 * @return void
 */
function staxo_e2e_register_routes() {
	$permission = static function () {
		return current_user_can( 'manage_options' );
	};

	register_rest_route(
		'staxo-e2e/v1',
		'/reset',
		array(
			'methods'             => 'POST',
			'callback'            => 'staxo_e2e_reset',
			'permission_callback' => $permission,
		)
	);

	register_rest_route(
		'staxo-e2e/v1',
		'/config',
		array(
			'methods'             => 'POST',
			'callback'            => 'staxo_e2e_load_config',
			'permission_callback' => $permission,
			'args'                => array(
				'file' => array(
					'type'     => 'string',
					'required' => true,
					'pattern'  => '^[a-z0-9-]+\.json$',
				),
			),
		)
	);
}

/**
 * Clear the plugin's caches and transients, as a configuration import does.
 *
 * @param array $options STR settings whose per-taxonomy caches are cleared.
 * @return void
 */
function staxo_e2e_clear_caches( $options ) {
	if ( isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
		foreach ( array_keys( $options['taxonomies'] ) as $taxonomy ) {
			wp_cache_delete( 'staxo_sel_' . $taxonomy );
			delete_transient( 'staxo_sel_' . $taxonomy );
		}
	}
	foreach ( array( 'staxo_own_taxos', 'staxo_orderings', 'staxo_terms', 'staxo_taxonomies' ) as $key ) {
		wp_cache_delete( $key );
	}
	delete_transient( 'staxo_cntl_post_types' );
}

/**
 * Delete the STR settings, and the terms and default-term options of the taxonomies they define.
 *
 * @return WP_REST_Response
 */
function staxo_e2e_reset() {
	$options = get_option( OPTION_STAXO );
	$deleted = array();

	if ( is_array( $options ) && isset( $options['taxonomies'] ) && is_array( $options['taxonomies'] ) ) {
		foreach ( array_keys( $options['taxonomies'] ) as $taxonomy ) {
			delete_option( 'default_term_' . $taxonomy );
			if ( taxonomy_exists( $taxonomy ) ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
						'fields'     => 'ids',
					)
				);
				if ( is_array( $terms ) ) {
					foreach ( $terms as $term_id ) {
						wp_delete_term( (int) $term_id, $taxonomy );
					}
				}
			}
			$deleted[] = $taxonomy;
		}
	}

	delete_option( OPTION_STAXO );
	staxo_e2e_clear_caches( is_array( $options ) ? $options : array() );

	return rest_ensure_response( array( 'deleted' => $deleted ) );
}

/**
 * Load a configuration fixture (import file format) as the STR settings.
 *
 * The taxonomies are registered on the next request, as after an import in wp-admin.
 *
 * @param WP_REST_Request $request Request with the fixture file name.
 * @return WP_REST_Response|WP_Error
 */
function staxo_e2e_load_config( $request ) {
	$file = WP_PLUGIN_DIR . '/simple-taxonomy-refreshed/tests/files/' . $request['file'];
	if ( ! is_readable( $file ) ) {
		return new WP_Error( 'staxo_e2e_no_file', 'Fixture file not found.', array( 'status' => 404 ) );
	}

	$content = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local fixture file.
	$prefix  = 'SIMPLETAXONOMYREFRESHED';
	$config  = ( str_starts_with( $content, $prefix ) ? json_decode( substr( $content, strlen( $prefix ) ), true ) : null );
	if ( ! is_array( $config ) ) {
		return new WP_Error( 'staxo_e2e_bad_file', 'Not a configuration file.', array( 'status' => 400 ) );
	}

	staxo_e2e_clear_caches( (array) get_option( OPTION_STAXO, array() ) );
	update_option( OPTION_STAXO, $config, true );
	staxo_e2e_clear_caches( $config );
	set_transient( 'simple_taxonomy_refreshed_rewrite', true, 0 );

	return rest_ensure_response( array( 'taxonomies' => array_keys( (array) ( $config['taxonomies'] ?? array() ) ) ) );
}
