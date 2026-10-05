<?php
/**
 * Tests for external taxonomies: STR settings on taxonomies that another plugin (or WordPress) registers.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Saving the external form (update_external()), the control cache, the REST check,
 * term counts, WPGraphQL settings and the external form itself.
 *
 * Two taxonomies are registered here as another plugin would, with no update count callback:
 * ext_topic (flat, posts, REST base "topics") and ext_area (hierarchical, posts).
 *
 * @group externals
 */
class Test_STaxo_Externals extends STaxo_Test_Case {

	/**
	 * Register the external taxonomies and log in an administrator.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		register_taxonomy(
			'ext_topic',
			'post',
			array(
				'label'        => 'Topics',
				'labels'       => array(
					'name'          => 'Topics',
					'singular_name' => 'Topic',
				),
				'public'       => true,
				'show_ui'      => true,
				'show_in_rest' => true,
				'rest_base'    => 'topics',
			)
		);
		register_taxonomy(
			'ext_area',
			'post',
			array(
				'labels'       => array(
					'name'          => 'Areas',
					'singular_name' => 'Area',
				),
				'hierarchical' => true,
				'public'       => true,
				'show_ui'      => true,
				'show_in_rest' => true,
			)
		);
		$this->login( 'administrator' );
		SimpleTaxonomyRefreshed_Client::get_wp_default_labels();
	}

	/**
	 * Remove the external taxonomies and their STR caches.
	 *
	 * @return void
	 */
	public function tear_down() {
		foreach ( array( 'ext_topic', 'ext_area', 'ext_generic' ) as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				unregister_taxonomy( $taxonomy );
			}
			delete_transient( 'staxo_sel_' . $taxonomy );
		}
		delete_transient( 'staxo_sel_category' );
		remove_filter( 'update_post_term_count_statuses', array( 'SimpleTaxonomyRefreshed_Client', 'review_count_statuses' ), 30 );
		$_GET = array();
		parent::tear_down();
	}

	/**
	 * Fields the external form sends: the hidden name and hierarchical, and the integration tabs.
	 *
	 * @param string $name   taxonomy name.
	 * @param array  $fields fields to add or replace.
	 * @return array
	 */
	private function external_form( $name, $fields = array() ) {
		$tax_obj = get_taxonomy( $name );

		return array_merge(
			array(
				'name'               => $name,
				'hierarchical'       => (string) (int) $tax_obj->hierarchical,
				'st_show_in_graphql' => '0',
				'st_graphql_single'  => '',
				'st_graphql_plural'  => '',
				'st_adm_hier'        => '0',
				'st_adm_depth'       => '0',
				'st_adm_count'       => '0',
				'st_adm_h_e'         => '0',
				'st_adm_h_i_e'       => '0',
				'st_cb_type'         => '0',
				'st_cc_type'         => '0',
				'st_cc_hard'         => '0',
				'st_cc_umin'         => '0',
				'st_cc_umax'         => '0',
				'st_cc_min'          => '0',
				'st_cc_max'          => '0',
			),
			$fields
		);
	}

	/**
	 * Submit the external form through admin_init (action merge-external).
	 *
	 * @param array $fields form fields.
	 * @return string redirect location.
	 */
	private function submit_external( $fields ) {
		$request  = array_merge(
			$fields,
			array(
				'action'   => 'merge-external',
				'_wpnonce' => wp_create_nonce( 'staxo_edit_taxo' ),
			)
		);
		$_POST    = $request;
		$_REQUEST = $request;

		$this->expect_redirect();
		try {
			SimpleTaxonomyRefreshed_Admin::admin_init();
		} catch ( STaxo_Redirect_Exception $e ) {
			return $e->getMessage();
		}
		$this->fail( 'Expected a redirect' );
	}

	/**
	 * Terms control on ext_topic: any saved post, checked when saved, 1 or 2 terms.
	 *
	 * @param array $fields fields to add or replace.
	 * @return string redirect location.
	 */
	private function save_topic_control( $fields = array() ) {
		return $this->submit_external(
			$this->external_form(
				'ext_topic',
				array_merge(
					array(
						'st_cc_type' => '2',
						'st_cc_hard' => '1',
						'st_cc_umin' => '1',
						'st_cc_min'  => '1',
						'st_cc_umax' => '1',
						'st_cc_max'  => '2',
					),
					$fields
				)
			)
		);
	}

	/**
	 * Saving the external form stores the integration settings only, under externals, and redirects with a message.
	 */
	public function test_save_external() {
		$location = $this->save_topic_control(
			array(
				'st_show_in_graphql' => '1',
				'st_graphql_single'  => 'topic',
				'st_graphql_plural'  => 'topics',
			)
		);

		$this->assertStringContainsString( 'message=updated&staxo=ext_topic', $location );
		$options = get_option( OPTION_STAXO );
		$this->assertArrayNotHasKey( 'taxonomies', $options, 'An external taxonomy is not stored as an STR taxonomy' );
		$saved = $options['externals']['ext_topic'];
		$this->assertSame( 'ext_topic', $saved['name'] );
		$this->assertSame( '2', $saved['st_cc_type'] );
		$this->assertSame( '1', $saved['st_cc_hard'] );
		$this->assertSame( '2', $saved['st_cc_max'] );
		$this->assertSame( 'topics', $saved['st_graphql_plural'] );
		// Details of the taxonomy itself are not stored: they belong to whoever registers it.
		foreach ( array( 'labels', 'objects', 'st_ep_mask', 'st_update_count_callback', 'public', 'rewrite' ) as $key ) {
			$this->assertArrayNotHasKey( $key, $saved, "$key should not be stored for an external taxonomy" );
		}
	}

	/**
	 * Status choices for Term Count are kept only for the "Selection" count type.
	 */
	public function test_save_external_count_statuses() {
		$this->submit_external(
			$this->external_form(
				'ext_area',
				array(
					'st_cb_type' => '1',
					'st_cb_pub'  => '1',
					'st_cb_dft'  => '1',
				)
			)
		);
		$saved = get_option( OPTION_STAXO )['externals']['ext_area'];
		$this->assertSame( 0, $saved['st_cb_pub'] );
		$this->assertSame( 0, $saved['st_cb_dft'] );

		$this->submit_external(
			$this->external_form(
				'ext_area',
				array(
					'st_cb_type' => '2',
					'st_cb_pub'  => '1',
					'st_cb_dft'  => '1',
				)
			)
		);
		$saved = get_option( OPTION_STAXO )['externals']['ext_area'];
		$this->assertSame( '1', $saved['st_cb_pub'] );
		$this->assertSame( '1', $saved['st_cb_dft'] );
	}

	/**
	 * Only administrators may save external settings.
	 */
	public function test_save_external_needs_manage_options() {
		$this->login( 'editor' );

		$this->expectException( 'WPDieException' );
		$this->submit_external( $this->external_form( 'ext_topic' ) );
	}

	/**
	 * Saving builds the control cache entry from the registered taxonomy.
	 */
	public function test_external_control_cache() {
		$this->save_topic_control();

		$cache = SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache( false );
		$this->assertArrayHasKey( 'post', $cache );
		$cntl = $cache['post']['ext_topic'];
		$this->assertSame( 2, $cntl['st_cc_type'] );
		$this->assertSame( 1, $cntl['st_cc_hard'] );
		$this->assertSame( 1, $cntl['st_cc_min'] );
		$this->assertSame( 2, $cntl['st_cc_max'] );
		$this->assertSame( 'topics', $cntl['rest_base'] );
		$this->assertSame( 'Topics', $cntl['label_name'] );
		$this->assertFalse( (bool) $cntl['hierarchical'] );
	}

	/**
	 * No control is cached for other post types, or for a taxonomy that is not registered.
	 */
	public function test_external_control_cache_exclusions() {
		$this->save_topic_control( array( 'st_cc_types' => array( 'page' ) ) );
		$this->assertSame( array(), SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache() );

		$options = get_option( OPTION_STAXO );
		$topic   = array_merge( $options['externals']['ext_topic'], array( 'st_cc_types' => '' ) );
		$gone    = array_merge( $topic, array( 'name' => 'ext_gone' ) );

		$options['externals']['ext_topic'] = $topic;
		$options['externals']['ext_gone']  = $gone;
		update_option( OPTION_STAXO, $options );

		$cache = SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache();
		$this->assertArrayHasKey( 'ext_topic', $cache['post'] );
		$this->assertArrayNotHasKey( 'ext_gone', $cache['post'], 'An external taxonomy that is not registered has nothing to control' );
	}

	/**
	 * The REST check applies the external control, using the taxonomy's REST base.
	 */
	public function test_external_rest_check() {
		$this->save_topic_control();
		SimpleTaxonomyRefreshed_Client::rest_api_init( rest_get_server() );
		$term = self::factory()->term->create(
			array(
				'taxonomy' => 'ext_topic',
				'name'     => 'Weather',
			)
		);

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$request->set_param( 'title', 'External control' );
		$request->set_param( 'status', 'publish' );
		$request->set_param( 'topics', array() );
		$response = rest_do_request( $request );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_minimum_terms', $response->get_data()['code'] );

		$request->set_param( 'topics', array( $term ) );
		$this->assertSame( 201, rest_do_request( $request )->get_status() );
	}

	/**
	 * Term Count "Selection" on an external taxonomy counts the chosen statuses.
	 */
	public function test_external_term_count() {
		$this->submit_external(
			$this->external_form(
				'ext_area',
				array(
					'st_cb_type' => '2',
					'st_cb_pub'  => '1',
					'st_cb_dft'  => '1',
				)
			)
		);
		SimpleTaxonomyRefreshed_Client::init();
		$this->assertNotFalse( has_filter( 'update_post_term_count_statuses', array( 'SimpleTaxonomyRefreshed_Client', 'review_count_statuses' ) ) );
		$this->assertSame( array( 'publish', 'draft' ), SimpleTaxonomyRefreshed_Client::term_count_sel_cache( 'ext_area' )['statuses'] );

		$term = self::factory()->term->create(
			array(
				'taxonomy' => 'ext_area',
				'name'     => 'North',
			)
		);
		foreach ( array( 'publish', 'draft', 'pending' ) as $status ) {
			$post = self::factory()->post->create( array( 'post_status' => $status ) );
			wp_set_object_terms( $post, array( $term ), 'ext_area' );
		}
		wp_update_term_count_now( array( $term ), 'ext_area' );
		clean_term_cache( $term, 'ext_area' );

		$this->assertSame( 2, (int) get_term( $term, 'ext_area' )->count, 'Published and draft posts are counted, pending is not' );
	}

	/**
	 * WPGraphQL settings for an external taxonomy are set on the registered taxonomy object.
	 */
	public function test_external_graphql() {
		$this->save_topic_control(
			array(
				'st_show_in_graphql' => '1',
				'st_graphql_single'  => 'topic',
				'st_graphql_plural'  => 'topics',
			)
		);

		SimpleTaxonomyRefreshed_Client::registered_taxonomy( 'ext_topic', 'post', array() );

		$tax_obj = get_taxonomy( 'ext_topic' );
		$this->assertTrue( $tax_obj->show_in_graphql );
		$this->assertSame( 'topic', $tax_obj->graphql_single );
		$this->assertSame( 'topics', $tax_obj->graphql_plural );

		// A taxonomy without the setting is left alone.
		SimpleTaxonomyRefreshed_Client::registered_taxonomy( 'ext_area', 'post', array() );
		$this->assertFalse( isset( get_taxonomy( 'ext_area' )->show_in_graphql ) && get_taxonomy( 'ext_area' )->show_in_graphql );
	}

	/**
	 * The form for an external taxonomy offers only the integration tabs and posts merge-external.
	 */
	public function test_external_form() {
		$this->save_topic_control();
		$_GET = array(
			'action'        => 'edit',
			'taxonomy_name' => 'ext_topic',
		);

		ob_start();
		SimpleTaxonomyRefreshed_Admin::page_manage();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'External Taxonomy : Topics', $html );
		$this->assertStringContainsString( 'name="action" value="merge-external"', $html );
		$this->assertStringContainsString( 'type="hidden" id="name" name="name" value="ext_topic"', $html );
		$this->assertStringNotContainsString( 'aria-controls="mainopts"', $html, 'No Main Options tab for an external taxonomy' );
		$this->assertStringContainsString( 'aria-controls="countt"', $html );
		// The saved control is shown: "Any (Except Trash)" chosen.
		$this->assertMatchesRegularExpression( '/id="cc_any"[^>]*checked/', $html );
	}

	/**
	 * Editing a taxonomy that does not exist stops with a message.
	 */
	public function test_external_form_unknown() {
		$_GET = array(
			'action'        => 'edit',
			'taxonomy_name' => 'ext_none',
		);

		$this->expectException( 'WPDieException' );
		ob_start();
		try {
			SimpleTaxonomyRefreshed_Admin::page_manage();
		} finally {
			ob_end_clean();
		}
	}

	/**
	 * Categories use WordPress's own _update_post_term_count(), which applies the status filter,
	 * so Term Count "Selection" works for them too, and the form offers the options.
	 */
	public function test_external_term_count_category() {
		$this->assertTrue( SimpleTaxonomyRefreshed_Client::counts_by_post_status( get_taxonomy( 'category' )->update_count_callback ) );

		$this->submit_external(
			$this->external_form(
				'category',
				array(
					'st_cb_type' => '2',
					'st_cb_pub'  => '1',
					'st_cb_dft'  => '1',
				)
			)
		);
		SimpleTaxonomyRefreshed_Client::init();
		$this->assertSame( array( 'publish', 'draft' ), SimpleTaxonomyRefreshed_Client::term_count_sel_cache( 'category' )['statuses'] );

		$term = self::factory()->category->create( array( 'name' => 'Drafted' ) );
		foreach ( array( 'publish', 'draft', 'pending' ) as $status ) {
			$post = self::factory()->post->create( array( 'post_status' => $status ) );
			wp_set_object_terms( $post, array( $term ), 'category' );
		}
		wp_update_term_count_now( array( $term ), 'category' );
		clean_term_cache( $term, 'category' );
		$this->assertSame( 2, (int) get_term( $term, 'category' )->count, 'Published and draft posts are counted, pending is not' );

		// The form shows the Term Count options, not the "function defined" message.
		$_GET = array(
			'action'        => 'edit',
			'taxonomy_name' => 'category',
		);
		ob_start();
		SimpleTaxonomyRefreshed_Admin::page_manage();
		$html = ob_get_clean();
		$this->assertMatchesRegularExpression( '/<span id="count_tab_0" class="is-hidden">/', $html );
		$this->assertMatchesRegularExpression( '/<span id="count_tab_1" >/', $html );
	}

	/**
	 * Register ext_generic, a taxonomy with a count function of its own.
	 *
	 * @return void
	 */
	private function register_own_count_taxonomy() {
		register_taxonomy(
			'ext_generic',
			'post',
			array(
				'labels'                => array( 'name' => 'Generics' ),
				'public'                => true,
				'show_ui'               => true,
				'update_count_callback' => '_update_generic_term_count',
			)
		);
	}

	/**
	 * The external form for a taxonomy with its own count function.
	 *
	 * @return string HTML.
	 */
	private function own_count_form() {
		$_GET = array(
			'action'        => 'edit',
			'taxonomy_name' => 'ext_generic',
		);
		ob_start();
		SimpleTaxonomyRefreshed_Admin::page_manage();
		return ob_get_clean();
	}

	/**
	 * A taxonomy with a count function of its own keeps it unless the user chooses otherwise:
	 * no statuses are applied, and the form warns and offers the choice.
	 */
	public function test_external_term_count_own_callback() {
		$this->register_own_count_taxonomy();
		$this->assertFalse( SimpleTaxonomyRefreshed_Client::counts_by_post_status( '_update_generic_term_count' ) );

		$this->submit_external(
			$this->external_form(
				'ext_generic',
				array(
					'st_cb_type'     => '2',
					'st_cb_pub'      => '1',
					'st_cb_dft'      => '1',
					'st_cb_override' => '0',
				)
			)
		);
		SimpleTaxonomyRefreshed_Client::init_2();
		$this->assertSame( '_update_generic_term_count', get_taxonomy( 'ext_generic' )->update_count_callback, 'The own count function is kept' );
		$this->assertSame( array(), SimpleTaxonomyRefreshed_Client::term_count_sel_cache( 'ext_generic' )['statuses'] );

		$html = $this->own_count_form();
		$this->assertStringContainsString( 'This taxonomy has its own function for counting terms (_update_generic_term_count).', $html );
		$this->assertMatchesRegularExpression( '/<span id="count_tab_0" >/', $html );
		$this->assertMatchesRegularExpression( '/<span id="count_tab_1" >/', $html, 'The options are shown with the warning' );
		$this->assertMatchesRegularExpression( '/<input type="checkbox" id="st_cb_override" name="st_cb_override" value="1"\s*\/>/', $html, 'Not ticked' );
	}

	/**
	 * With "use these options" ticked, the taxonomy is counted by WordPress's standard function
	 * with the statuses chosen; the form still names its own function and shows the box ticked.
	 */
	public function test_external_term_count_override() {
		$this->register_own_count_taxonomy();
		$this->submit_external(
			$this->external_form(
				'ext_generic',
				array(
					'st_cb_type'     => '2',
					'st_cb_pub'      => '1',
					'st_cb_dft'      => '1',
					'st_cb_override' => '1',
				)
			)
		);
		$this->assertSame( '1', get_option( OPTION_STAXO )['externals']['ext_generic']['st_cb_override'] );

		SimpleTaxonomyRefreshed_Client::init();
		SimpleTaxonomyRefreshed_Client::init_2();
		$this->assertSame( '_update_post_term_count', get_taxonomy( 'ext_generic' )->update_count_callback );
		$this->assertSame( '_update_generic_term_count', SimpleTaxonomyRefreshed_Client::original_count_callback( 'ext_generic' ) );
		$this->assertSame( array( 'publish', 'draft' ), SimpleTaxonomyRefreshed_Client::term_count_sel_cache( 'ext_generic' )['statuses'] );

		$term = self::factory()->term->create(
			array(
				'taxonomy' => 'ext_generic',
				'name'     => 'Counted',
			)
		);
		foreach ( array( 'publish', 'draft', 'pending' ) as $status ) {
			$post = self::factory()->post->create( array( 'post_status' => $status ) );
			wp_set_object_terms( $post, array( $term ), 'ext_generic' );
		}
		wp_update_term_count_now( array( $term ), 'ext_generic' );
		clean_term_cache( $term, 'ext_generic' );
		$this->assertSame( 2, (int) get_term( $term, 'ext_generic' )->count, 'Published and draft posts are counted, pending is not' );

		$html = $this->own_count_form();
		$this->assertStringContainsString( '(_update_generic_term_count)', $html );
		$this->assertMatchesRegularExpression( "/id=\"st_cb_override\"[^>]*checked='checked'/", $html );
	}
}
