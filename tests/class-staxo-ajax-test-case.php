<?php
/**
 * Base class for Simple Taxonomy Refreshed AJAX tests.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * AJAX test case with the STR fixtures, for Terms Merge and Terms Conversion.
 */
abstract class STaxo_Ajax_Test_Case extends WP_Ajax_UnitTestCase {

	use STaxo_Fixtures;

	/**
	 * Load the admin classes, clear STR state and hook the AJAX handlers.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->staxo_set_up();

		// The plugin only hooks these when is_admin() is true.
		add_action( 'wp_ajax_' . SimpleTaxonomyRefreshed_Admin_Merge::MERGE_SLUG, array( 'SimpleTaxonomyRefreshed_Admin_Merge', 'staxo_merge' ) );
		add_action( 'wp_ajax_' . SimpleTaxonomyRefreshed_Admin_Conversion::CONVERT_SLUG, array( 'SimpleTaxonomyRefreshed_Admin_Conversion', 'staxo_convert' ) );
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

	/**
	 * Call an AJAX action with a valid nonce and return its output.
	 *
	 * A wp_die() after output (the normal end of these handlers) is caught here.
	 * A wp_die() with no output (a rejection) throws WPAjaxDieStopException to the test.
	 *
	 * @param string $action action (and nonce) name.
	 * @param array  $args   other $_POST fields.
	 * @param string $nonce  nonce to send; default a valid one.
	 * @return string response.
	 */
	protected function ajax( $action, $args, $nonce = null ) {
		$_POST = array_merge(
			array(
				'_wpnonce' => ( is_null( $nonce ) ? wp_create_nonce( $action ) : $nonce ),
			),
			$args
		);

		$this->_last_response = '';
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return $this->_last_response;
	}

	/**
	 * Call one phase of Terms Merge.
	 *
	 * @param string $phase    'one' to 'four'.
	 * @param string $taxonomy taxonomy name.
	 * @param array  $args     other $_POST fields.
	 * @return string response.
	 */
	protected function merge_phase( $phase, $taxonomy, $args = array() ) {
		return $this->ajax(
			SimpleTaxonomyRefreshed_Admin_Merge::MERGE_SLUG,
			array_merge(
				array(
					'phase'    => $phase,
					'taxonomy' => $taxonomy,
				),
				$args
			)
		);
	}

	/**
	 * Run the final phase of Terms Merge.
	 *
	 * @param string   $taxonomy    taxonomy name.
	 * @param string   $destination destination term name.
	 * @param string[] $sources     source term names.
	 * @return string response.
	 */
	protected function merge( $taxonomy, $destination, $sources ) {
		$ids = array();
		foreach ( $sources as $source ) {
			$ids[] = $this->term( $taxonomy, $source )->term_id;
		}

		return $this->merge_phase(
			'four',
			$taxonomy,
			array(
				'destination' => $this->term( $taxonomy, $destination )->term_id,
				'sources'     => implode( ',', $ids ),
			)
		);
	}
}
