<?php
/**
 * Bootstrap the PHPUnit test environment.
 *
 * @package simple-taxonomy-refreshed
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load Simple Taxonomy Refreshed before the other plugins.
 *
 * @return void
 */
function _staxo_manually_load_plugin() {
	require dirname( __DIR__ ) . '/simple-taxonomy-refreshed.php';
}
tests_add_filter( 'muplugins_loaded', '_staxo_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

// Shared fixtures and base classes (need the WP test classes loaded above).
require_once __DIR__ . '/class-staxo-redirect-exception.php';
require_once __DIR__ . '/trait-staxo-fixtures.php';
require_once __DIR__ . '/class-staxo-test-case.php';
require_once __DIR__ . '/class-staxo-ajax-test-case.php';
