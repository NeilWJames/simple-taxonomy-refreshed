<?php
/**
 * Verifies basic CRUD operations of Simple Taxonomy Refreshed
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Main Simple Taxonomy 2 tests
 */
class Test_STaxo_Refreshed_Main extends WP_UnitTestCase {

	/**
	 * Setup Initial Testing Environment
	 *
	 * Called for every defined test
	 *
	 * @return void
	 */
	public function setUp() {

		parent::setUp();

		wp_cache_flush();

	}

	/**
	 * Output message to log.
	 *
	 * @param string $text text to output.
	 */
	public function consoleLog( $text ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			fwrite( STDERR, $text . "\n" );
	}

	/**
	 * Make sure multisite is as expected.
	 */
	public function test_log_multisite() {

		$this->consoleLog( 'Test_STaxo_Refreshed_Main - Start' );

		$env = (bool) getenv( 'WP_MULTISITE' );

		$this->assertTrue( ( is_multisite() === $env ), 'Multisite not as expected' );

	}

	/**
	 * Make sure plugin is activated.
	 */
	public function test_activated() {

		$this->consoleLog( 'Test_STaxo_Refreshed_Main - Activated' );

		$this->assertTrue( class_exists( 'SimpleTaxonomyRefreshed_Client' ), 'SimpleTaxonomy_Client class not defined' );

	}


}
