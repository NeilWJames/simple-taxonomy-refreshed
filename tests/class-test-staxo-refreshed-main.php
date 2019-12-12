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
	 */
	public function setUp() {

		parent::setUp();

		wp_cache_flush();

	}

	/**
	 * Make sure plugin is activated.
	 */
	public function test_activated() {

		$this->assertTrue( class_exists( 'SimpleTaxonomyRefreshed_Client' ), 'SimpleTaxonomy_Client class not defined' );

	}


}
