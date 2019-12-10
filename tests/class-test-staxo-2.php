<?php
/**
 * Verifies basic CRUD operations of Simple Taxonomy 2
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package simple-taxonomy-2
 */

/**
 * Main Simple Taxonomy 2 tests
 */
class Test_STaxo_2 extends WP_UnitTestCase {

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

		$this->assertTrue( class_exists( 'SimpleTaxonomy_Client' ), 'SimpleTaxonomy_Client class not defined' );

	}


}
