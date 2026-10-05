<?php
/**
 * Tests for Terms Control (minimum and maximum number of terms) when posts are saved.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Terms Control on the shared fixture: test_cntl has a hard control, type 2, minimum 1, maximum 2.
 *
 * @group control
 */
class Test_STaxo_Terms_Control extends STaxo_Test_Case {

	/**
	 * Log in an administrator, load the fixture and register the REST check.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->login( 'administrator' );
		$this->load_fixture();
		$this->make_posts();

		// Register the REST filters for the controlled post types (normally on rest_api_init).
		SimpleTaxonomyRefreshed_Client::rest_api_init( rest_get_server() );
	}

	/**
	 * Clear the request data used by the notice tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_GET = array();
		parent::tear_down();
	}

	/**
	 * Change the terms control settings of test_cntl and rebuild the cache.
	 *
	 * @param array $settings STR settings to change (st_cc_*, show_in_rest ...).
	 * @return void
	 */
	private function set_control( $settings ) {
		$options = get_option( OPTION_STAXO );
		foreach ( $settings as $key => $value ) {
			$options['taxonomies']['test_cntl'][ $key ] = $value;
		}
		update_option( OPTION_STAXO, $options );
		SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache();
	}

	/**
	 * Term ids of test_cntl by name.
	 *
	 * @param string[] $names term names.
	 * @return int[]
	 */
	private function cntl_ids( $names ) {
		$ids = array();
		foreach ( $names as $name ) {
			$ids[] = $this->term( 'test_cntl', $name )->term_id;
		}

		return $ids;
	}

	/**
	 * Post data as the classic editor sends it to wp_insert_post().
	 *
	 * @param string $status post status.
	 * @param mixed  $terms  test_cntl tax_input value (null: not sent).
	 * @param array  $extra  other fields.
	 * @return array
	 */
	private function postarr( $status, $terms, $extra = array() ) {
		$postarr = array_merge(
			array(
				'ID'          => $this->posts['P1'],
				'post_title'  => 'P1',
				'post_type'   => 'post',
				'post_status' => $status,
				'post_parent' => 0,
			),
			$extra
		);
		if ( ! is_null( $terms ) ) {
			$postarr['tax_input'] = array( 'test_cntl' => $terms );
		}

		return $postarr;
	}

	/**
	 * Run the classic-editor check.
	 *
	 * @param array $postarr post data.
	 * @return string|null redirect location, or null if the save may go ahead.
	 */
	private function classic_check( $postarr ) {
		$this->expect_redirect();
		try {
			$result = SimpleTaxonomyRefreshed_Admin::check_taxonomy_value_set( false, $postarr );
		} catch ( STaxo_Redirect_Exception $e ) {
			return $e->getMessage();
		}
		$this->assertFalse( $result, 'The post should not be reported as empty' );

		return null;
	}

	/**
	 * Send a REST request to the posts endpoint.
	 *
	 * @param string $method 'POST' or 'PUT'.
	 * @param string $route  route, e.g. /wp/v2/posts or /wp/v2/posts/123.
	 * @param array  $params body parameters.
	 * @return WP_REST_Response
	 */
	private function rest( $method, $route, $params ) {
		$request = new WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_do_request( $request );
	}

	/**
	 * The error code of a REST response, or '' when it succeeded.
	 *
	 * @param WP_REST_Response $response response.
	 * @return string
	 */
	private function rest_error( $response ) {
		$data = $response->get_data();

		return ( $response->is_error() && isset( $data['code'] ) ? $data['code'] : '' );
	}

	/**
	 * The control cache holds the test_cntl settings, as integers, for posts only.
	 */
	public function test_control_cache() {
		$cache = SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache();

		$this->assertArrayHasKey( 'post', $cache );
		$this->assertArrayNotHasKey( 'page', $cache, 'test_cntl is not used on pages' );
		$cntl = $cache['post']['test_cntl'];
		$this->assertSame( 2, $cntl['st_cc_type'] );
		$this->assertSame( 1, $cntl['st_cc_hard'] );
		$this->assertSame( 1, $cntl['st_cc_min'] );
		$this->assertSame( 2, $cntl['st_cc_max'] );
		$this->assertSame( 'test_cntl', $cntl['rest_base'] );
		$this->assertSame( 'Control Terms', $cntl['label_name'] );
	}

	/**
	 * No control is cached when there is no control, or the post type is not selected.
	 */
	public function test_control_cache_exclusions() {
		$this->set_control( array( 'st_cc_types' => array( 'page' ) ) );
		$this->assertSame( array(), SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache() );

		$this->set_control(
			array(
				'st_cc_types' => '',
				'st_cc_type'  => '0',
			)
		);
		$this->assertSame( array(), SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache() );
	}

	/**
	 * Notification only (control level 0) is cached, so the post screens can show radio buttons and
	 * notices, but saving is not checked: neither the classic editor nor the REST API refuses the post.
	 */
	public function test_notification_only() {
		$this->set_control( array( 'st_cc_hard' => '0' ) );

		$cache = SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache();
		$this->assertSame( 0, $cache['post']['test_cntl']['st_cc_hard'] );

		$this->assertNull( $this->classic_check( $this->postarr( 'publish', array() ) ), 'Too few terms: saved' );
		$this->assertNull( $this->classic_check( $this->postarr( 'publish', $this->cntl_ids( array( 'red', 'green', 'blue' ) ) ) ), 'Too many terms: saved' );

		$response = $this->rest(
			'POST',
			'/wp/v2/posts',
			array(
				'title'     => 'REST post',
				'status'    => 'publish',
				'test_cntl' => array(),
			)
		);
		$this->assertSame( 201, $response->get_status() );
	}

	/**
	 * Classic editor: too few terms on a published post redirects back with an error.
	 */
	public function test_classic_minimum() {
		$location = $this->classic_check( $this->postarr( 'publish', array() ) );

		$this->assertNotNull( $location, 'Expected a redirect' );
		$this->assertStringContainsString( 'staxo_error=min', $location );
		$this->assertStringContainsString( 'staxo_tax=test_cntl', $location );
		$this->assertStringContainsString( 'post=' . $this->posts['P1'], $location );
	}

	/**
	 * Classic editor: too many terms redirects with the max error.
	 */
	public function test_classic_maximum() {
		$location = $this->classic_check( $this->postarr( 'publish', $this->cntl_ids( array( 'red', 'green', 'blue' ) ) ) );

		$this->assertStringContainsString( 'staxo_error=max', (string) $location );
	}

	/**
	 * Classic editor: one or two terms are accepted.
	 */
	public function test_classic_within_limits() {
		$this->assertNull( $this->classic_check( $this->postarr( 'publish', $this->cntl_ids( array( 'red' ) ) ) ) );
		$this->assertNull( $this->classic_check( $this->postarr( 'publish', $this->cntl_ids( array( 'red', 'green' ) ) ) ) );
	}

	/**
	 * Classic editor: the hidden 0 and the "No term" -1 are not counted as terms, and a comma list is counted.
	 */
	public function test_classic_form_values() {
		$red = $this->cntl_ids( array( 'red' ) )[0];

		$this->assertNull( $this->classic_check( $this->postarr( 'publish', array( 0, $red ) ) ) );
		$this->assertStringContainsString( 'staxo_error=min', (string) $this->classic_check( $this->postarr( 'publish', array( '0', '-1' ) ) ) );
		$this->assertStringContainsString( 'staxo_error=max', (string) $this->classic_check( $this->postarr( 'publish', 'red, green, blue' ) ) );
	}

	/**
	 * Type 2 controls drafts; type 1 only published and scheduled posts.
	 */
	public function test_classic_type_and_status() {
		$this->assertNotNull( $this->classic_check( $this->postarr( 'draft', array() ) ), 'Type 2 checks drafts' );

		$this->set_control( array( 'st_cc_type' => '1' ) );
		$this->assertNull( $this->classic_check( $this->postarr( 'draft', array() ) ), 'Type 1 does not check drafts' );
		$this->assertNotNull( $this->classic_check( $this->postarr( 'publish', array() ) ), 'Type 1 checks published posts' );
		$this->assertNotNull( $this->classic_check( $this->postarr( 'future', array() ) ), 'Type 1 checks scheduled posts' );
	}

	/**
	 * No check for new, auto-draft or trashed posts, posts without a title, or other post types.
	 */
	public function test_classic_not_checked() {
		foreach ( array( 'new', 'auto-draft', 'trash' ) as $status ) {
			$this->assertNull( $this->classic_check( $this->postarr( $status, array() ) ), "Status $status" );
		}
		$this->assertNull( $this->classic_check( $this->postarr( 'publish', array(), array( 'post_title' => '' ) ) ), 'Empty title' );
		$this->assertNull( $this->classic_check( $this->postarr( 'publish', array(), array( 'post_type' => 'page' ) ) ), 'Page' );
	}

	/**
	 * Quick edit: the error is output and the request ends.
	 */
	public function test_quick_edit() {
		ob_start();
		try {
			SimpleTaxonomyRefreshed_Admin::check_taxonomy_value_set( false, $this->postarr( 'publish', array(), array( '_inline_edit' => '1' ) ) );
			$this->fail( 'Expected WPDieException' );
		} catch ( WPDieException $e ) {
			unset( $e );
		}
		$output = ob_get_clean();

		$this->assertStringContainsString( 'The number of terms for taxonomy (Control Terms) is less than the required minimum number 1.', $output );
	}

	/**
	 * Through wp_update_post(): the update is stopped and the stored terms are unchanged.
	 */
	public function test_classic_save_is_stopped() {
		SimpleTaxonomyRefreshed_Admin::admin_init();
		$this->expect_redirect();

		try {
			wp_update_post(
				array(
					'ID'        => $this->posts['P1'],
					'tax_input' => array( 'test_cntl' => array() ),
				)
			);
			$this->fail( 'Expected a redirect' );
		} catch ( STaxo_Redirect_Exception $e ) {
			$this->assertStringContainsString( 'staxo_error=min', $e->getMessage() );
		}
		$this->assertSame( array( 'red' ), $this->post_terms( 'P1', 'test_cntl' ) );
	}

	/**
	 * REST: creating a published post with too few or too many terms is refused.
	 */
	public function test_rest_create_limits() {
		$base = array(
			'title'  => 'REST post',
			'status' => 'publish',
		);

		$response = $this->rest( 'POST', '/wp/v2/posts', array_merge( $base, array( 'test_cntl' => array() ) ) );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_minimum_terms', $this->rest_error( $response ) );

		$response = $this->rest( 'POST', '/wp/v2/posts', array_merge( $base, array( 'test_cntl' => $this->cntl_ids( array( 'red', 'green', 'blue' ) ) ) ) );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_maximum_terms', $this->rest_error( $response ) );

		$response = $this->rest( 'POST', '/wp/v2/posts', array_merge( $base, array( 'test_cntl' => $this->cntl_ids( array( 'red' ) ) ) ) );
		$this->assertSame( 201, $response->get_status() );
	}

	/**
	 * REST: an update that does not send the taxonomy uses the post's current terms (review finding M7).
	 */
	public function test_rest_update_without_terms() {
		$response = $this->rest( 'POST', '/wp/v2/posts/' . $this->posts['P1'], array( 'title' => 'P1 renamed' ) );
		$this->assertSame( 200, $response->get_status() );

		// P5 is a draft with no test_cntl terms: publishing it without sending terms is refused.
		$response = $this->rest( 'POST', '/wp/v2/posts/' . $this->posts['P5'], array( 'status' => 'publish' ) );
		$this->assertSame( 'rest_minimum_terms', $this->rest_error( $response ) );
	}

	/**
	 * REST: removing all terms from a published post is refused and nothing changes.
	 */
	public function test_rest_update_remove_terms() {
		$response = $this->rest( 'POST', '/wp/v2/posts/' . $this->posts['P1'], array( 'test_cntl' => array() ) );

		$this->assertSame( 'rest_minimum_terms', $this->rest_error( $response ) );
		$this->assertSame( array( 'red' ), $this->post_terms( 'P1', 'test_cntl' ) );
	}

	/**
	 * REST: type 2 checks drafts; type 1 does not.
	 */
	public function test_rest_type_and_status() {
		$params = array(
			'title'     => 'REST draft',
			'status'    => 'draft',
			'test_cntl' => array(),
		);

		$this->assertSame( 'rest_minimum_terms', $this->rest_error( $this->rest( 'POST', '/wp/v2/posts', $params ) ) );

		$this->set_control( array( 'st_cc_type' => '1' ) );
		$this->assertSame( 201, $this->rest( 'POST', '/wp/v2/posts', $params )->get_status() );
	}

	/**
	 * REST batch requests are checked the same way.
	 */
	public function test_rest_batch() {
		$request = new WP_REST_Request( 'POST', '/batch/v1' );
		$request->set_param(
			'requests',
			array(
				array(
					'method' => 'POST',
					'path'   => '/wp/v2/posts/' . $this->posts['P1'],
					'body'   => array( 'title' => 'P1 batch' ),
				),
				array(
					'method' => 'POST',
					'path'   => '/wp/v2/posts/' . $this->posts['P2'],
					'body'   => array( 'test_cntl' => array() ),
				),
			)
		);
		$data = rest_do_request( $request )->get_data();

		$this->assertSame( 200, $data['responses'][0]['status'] );
		$this->assertSame( 403, $data['responses'][1]['status'] );
		$this->assertSame( 'rest_minimum_terms', $data['responses'][1]['body']['code'] );
		$this->assertSame( array( 'green' ), $this->post_terms( 'P2', 'test_cntl' ) );
	}

	/**
	 * The notice after a refused classic save names the taxonomy.
	 */
	public function test_error_notice() {
		$_GET = array(
			'staxo_terms' => wp_create_nonce( 'terms' ),
			'staxo_error' => 'minmax',
			'staxo_tax'   => 'test_cntl',
		);

		ob_start();
		SimpleTaxonomyRefreshed_Admin::admin_error_check();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Your post needs to have more terms for the taxonomy - Control Terms entered.', $output );
		$this->assertStringContainsString( 'Your post needs to have less terms for taxonomy - Control Terms.', $output );
		$this->assertStringContainsString( 'Your update has been cancelled', $output );
	}

	/**
	 * No notice without a valid nonce.
	 */
	public function test_error_notice_needs_nonce() {
		$_GET = array(
			'staxo_terms' => 'bad',
			'staxo_error' => 'min',
			'staxo_tax'   => 'test_cntl',
		);

		ob_start();
		SimpleTaxonomyRefreshed_Admin::admin_error_check();
		$this->assertSame( '', ob_get_clean() );
	}
}
