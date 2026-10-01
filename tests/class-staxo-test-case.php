<?php
/**
 * Base class for Simple Taxonomy Refreshed tests.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Test case with the STR fixtures.
 */
abstract class STaxo_Test_Case extends WP_UnitTestCase {

	use STaxo_Fixtures;

	/**
	 * Load the admin classes and clear STR state.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->staxo_set_up();
	}

	/**
	 * Clear STR state and the request data.
	 *
	 * @return void
	 */
	public function tear_down() {
		$this->staxo_tear_down();
		parent::tear_down();
	}
}
