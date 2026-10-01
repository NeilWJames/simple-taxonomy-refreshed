<?php
/**
 * Tests for creating, updating, deleting and exporting taxonomies from the admin screens.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Add / update (form), delete / flush-delete and Export PHP.
 *
 * @group taxonomy
 */
class Test_STaxo_Taxonomy_Admin extends STaxo_Test_Case {

	/**
	 * Log in an administrator and load the WordPress default labels (used when saving).
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->login( 'administrator' );
		SimpleTaxonomyRefreshed_Client::get_wp_default_labels();
	}

	/**
	 * Clear the request data used by the delete and export handlers.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_GET = array();
		parent::tear_down();
	}

	/**
	 * Form fields for a new taxonomy.
	 *
	 * @param string $name   taxonomy name.
	 * @param array  $fields fields to add or replace.
	 * @return array
	 */
	private function taxonomy_form( $name, $fields = array() ) {
		return array_merge(
			array(
				'name'               => $name,
				'labels'             => array(
					'name'          => 'Genres',
					'singular_name' => 'Genre',
				),
				'objects'            => array( 'post' ),
				'hierarchical'       => '1',
				'public'             => '1',
				'publicly_queryable' => '1',
				'show_ui'            => '1',
				'show_in_menu'       => '1',
				'show_in_rest'       => '1',
				'rewrite'            => '0',
				'query_var'          => '',
				'auto'               => 'none',
				'st_cb_type'         => '0',
				'st_cc_type'         => '0',
			),
			$fields
		);
	}

	/**
	 * Submit the taxonomy form through admin_init.
	 *
	 * @param string $action       'add-taxonomy' or 'merge-taxonomy'.
	 * @param array  $fields       form fields.
	 * @param string $nonce_action nonce action (default matches the action).
	 * @return string redirect location.
	 */
	private function submit_form( $action, $fields, $nonce_action = null ) {
		if ( is_null( $nonce_action ) ) {
			$nonce_action = ( 'add-taxonomy' === $action ? 'staxo_add_taxo' : 'staxo_edit_taxo' );
		}
		$request  = array_merge(
			$fields,
			array(
				'action'   => $action,
				'_wpnonce' => wp_create_nonce( $nonce_action ),
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
	 * Run a delete or flush-delete through admin_init.
	 *
	 * @param string $action   'delete' or 'flush-delete'.
	 * @param string $taxonomy taxonomy name.
	 * @param string $nonce    nonce to send; default a valid one.
	 * @return string redirect location.
	 */
	private function delete( $action, $taxonomy, $nonce = null ) {
		if ( is_null( $nonce ) ) {
			$nonce = wp_create_nonce( ( 'delete' === $action ? 'staxo_delete_' : 'staxo_flush_delete-' ) . $taxonomy );
		}
		$request  = array(
			'action'        => $action,
			'taxonomy_name' => $taxonomy,
			'_wpnonce'      => $nonce,
		);
		$_POST    = array();
		$_GET     = $request;
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
	 * Number of term_taxonomy rows for a taxonomy.
	 *
	 * @param string $taxonomy taxonomy name.
	 * @return int
	 */
	private function term_rows( $taxonomy ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE taxonomy = %s", $taxonomy ) );
	}

	/**
	 * Names of T_STRING tokens in PHP code (function names called or defined, constants ...).
	 *
	 * Parses with TOKEN_PARSE, so invalid PHP throws a ParseError.
	 *
	 * @param string $code PHP code.
	 * @return string[]
	 */
	private function code_identifiers( $code ) {
		$names = array();
		foreach ( token_get_all( $code, TOKEN_PARSE ) as $token ) {
			if ( is_array( $token ) && T_STRING === $token[0] ) {
				$names[] = $token[1];
			}
		}

		return $names;
	}

	/**
	 * Adding a taxonomy stores it and it is registered at the next init.
	 */
	public function test_add_taxonomy() {
		$location = $this->submit_form( 'add-taxonomy', $this->taxonomy_form( 'test_genre' ) );

		$this->assertStringContainsString( 'message=added&staxo=test_genre', $location );
		$options = get_option( OPTION_STAXO );
		$this->assertArrayHasKey( 'test_genre', $options['taxonomies'] );

		$this->register_taxonomies();
		$this->assertTrue( taxonomy_exists( 'test_genre' ) );
		$this->assertTrue( is_taxonomy_hierarchical( 'test_genre' ) );
		$this->assertSame( 'Genres', get_taxonomy( 'test_genre' )->labels->name );
		$this->assertContains( 'post', get_taxonomy( 'test_genre' )->object_type );

		unregister_taxonomy( 'test_genre' );
	}

	/**
	 * Form values are sanitised.
	 */
	public function test_add_taxonomy_sanitises() {
		$this->submit_form(
			'add-taxonomy',
			$this->taxonomy_form(
				'Test Genre!',
				array(
					'labels'    => array(
						'name'          => '<b>Genres</b>',
						'singular_name' => 'Genre',
					),
					'st_before' => '<script>alert(1)</script><em>Genres:</em>',
				)
			)
		);

		$options = get_option( OPTION_STAXO );
		$this->assertArrayHasKey( 'test-genre', $options['taxonomies'], 'Name not sanitised with sanitize_title()' );
		$taxo = $options['taxonomies']['test-genre'];
		$this->assertSame( 'Genres', $taxo['labels']['name'] );
		$this->assertStringNotContainsString( '<script', $taxo['st_before'] );
		$this->assertStringContainsString( '<em>Genres:</em>', $taxo['st_before'] );
	}

	/**
	 * Labels equal to the WordPress defaults are not stored.
	 */
	public function test_default_labels_not_stored() {
		$default = SimpleTaxonomyRefreshed_Client::$wp_decoded_labels[1]['search_items'];
		$this->assertNotEmpty( $default );

		$form                           = $this->taxonomy_form( 'test_genre' );
		$form['labels']['search_items'] = $default;
		$this->submit_form( 'add-taxonomy', $form );

		$labels = get_option( OPTION_STAXO )['taxonomies']['test_genre']['labels'];
		$this->assertArrayNotHasKey( 'search_items', $labels );
		$this->assertSame( 'Genres', $labels['name'] );
	}

	/**
	 * A taxonomy name already registered (core or STR) cannot be added.
	 */
	public function test_add_existing_name_refused() {
		$this->import_config( 'staxo-config-suite.json' );

		foreach ( array( 'category', 'test_hier' ) as $name ) {
			try {
				$this->submit_form( 'add-taxonomy', $this->taxonomy_form( $name ) );
				$this->fail( "Adding $name should be refused" );
			} catch ( WPDieException $e ) {
				$this->assertStringContainsString( 'already used', $e->getMessage() );
			}
		}
		$this->assertSame( self::$staxo_taxonomies, array_keys( get_option( OPTION_STAXO )['taxonomies'] ) );
	}

	/**
	 * Updating a taxonomy stores the new settings.
	 */
	public function test_update_taxonomy() {
		$this->submit_form( 'add-taxonomy', $this->taxonomy_form( 'test_genre' ) );

		$form                   = $this->taxonomy_form( 'test_genre' );
		$form['labels']['name'] = 'Styles';
		$form['st_cb_type']     = '1';
		$form['st_cb_pub']      = '1';
		$location               = $this->submit_form( 'merge-taxonomy', $form );

		$this->assertStringContainsString( 'message=updated&staxo=test_genre', $location );
		$taxo = get_option( OPTION_STAXO )['taxonomies']['test_genre'];
		$this->assertSame( 'Styles', $taxo['labels']['name'] );
		$this->assertSame( '1', $taxo['st_cb_type'] );
		$this->assertSame( 0, $taxo['st_cb_pub'], 'Status choices only kept for count type 2' );
	}

	/**
	 * Updating a taxonomy that this plugin does not hold is refused.
	 */
	public function test_update_unknown_refused() {
		$this->expectException( 'WPDieException' );
		$this->submit_form( 'merge-taxonomy', $this->taxonomy_form( 'test_unknown' ) );
	}

	/**
	 * Callback fields are not changed by users who may not edit them.
	 */
	public function test_update_keeps_callbacks_when_not_allowed() {
		$this->submit_form( 'add-taxonomy', $this->taxonomy_form( 'test_genre' ) );

		add_filter( 'staxo_can_edit_callbacks', '__return_false' );
		$this->submit_form( 'merge-taxonomy', $this->taxonomy_form( 'test_genre', array( 'update_count_callback' => 'phpinfo' ) ) );
		remove_filter( 'staxo_can_edit_callbacks', '__return_false' );

		$this->assertSame( '', get_option( OPTION_STAXO )['taxonomies']['test_genre']['update_count_callback'] );
	}

	/**
	 * An editor cannot add a taxonomy.
	 */
	public function test_add_needs_manage_options() {
		$this->login( 'editor' );

		$this->expectException( 'WPDieException' );
		$this->submit_form( 'add-taxonomy', $this->taxonomy_form( 'test_genre' ) );
	}

	/**
	 * A bad nonce is refused and nothing is stored.
	 */
	public function test_add_bad_nonce_refused() {
		try {
			$this->submit_form( 'add-taxonomy', $this->taxonomy_form( 'test_genre' ), 'wrong_action' );
			$this->fail( 'Expected WPDieException' );
		} catch ( WPDieException $e ) {
			unset( $e );
		}
		$this->assertFalse( get_option( OPTION_STAXO ) );
	}

	/**
	 * Delete removes the definition but leaves the terms and their posts in the database.
	 */
	public function test_delete_keeps_terms() {
		$this->load_fixture();
		$this->make_posts();

		$location = $this->delete( 'delete', 'test_hier' );

		$this->assertStringContainsString( 'message=deleted&staxo=test_hier', $location );
		$this->assertArrayNotHasKey( 'test_hier', get_option( OPTION_STAXO )['taxonomies'] );
		$this->assertSame( 10, $this->term_rows( 'test_hier' ), 'Terms should stay in the database' );
		$this->assertSame( array( 'Jazz' ), $this->post_terms( 'P1', 'test_hier' ) );
	}

	/**
	 * Flush-delete removes the definition, the terms, their relationships and meta.
	 */
	public function test_flush_delete_removes_terms() {
		$this->load_fixture();
		$this->make_posts();
		$bebop = $this->term( 'test_hier', 'Bebop' );

		$location = $this->delete( 'flush-delete', 'test_hier' );

		$this->assertStringContainsString( 'message=flush-deleted&staxo=test_hier', $location );
		$this->assertArrayNotHasKey( 'test_hier', get_option( OPTION_STAXO )['taxonomies'] );
		$this->assertSame( 0, $this->term_rows( 'test_hier' ) );
		$this->assertSame( array(), $this->post_terms( 'P1', 'test_hier' ) );
		$this->assertSame( '', get_term_meta( $bebop->term_id, 'staxo_test_meta', true ) );

		// Other taxonomies are untouched.
		$this->assertSame( array( 'Jazz' ), $this->post_terms( 'P1', 'test_count' ) );
	}

	/**
	 * Flush-delete cannot remove a taxonomy's default term (WordPress refuses to delete it).
	 */
	public function test_flush_delete_leaves_default_term() {
		$this->load_fixture();

		$this->delete( 'flush-delete', 'test_flat' );

		$this->assertSame( 1, $this->term_rows( 'test_flat' ) );
		$this->assertTrue( $this->term_exists_by_name( 'test_flat', 'Unsorted' ) );
	}

	/**
	 * Delete removes the taxonomy from the admin list orderings.
	 */
	public function test_delete_updates_list_order() {
		$this->import_config( 'staxo-config-suite.json' );
		$options               = get_option( OPTION_STAXO );
		$options['list_order'] = array(
			'post' => array( 'test_flat', 'test_hier', 'category' ),
			'page' => array( 'test_hier', 'test_flat' ),
		);
		update_option( OPTION_STAXO, $options );

		$this->delete( 'delete', 'test_hier' );

		$order = get_option( OPTION_STAXO )['list_order'];
		$this->assertSame( array( 'test_flat', 'category' ), $order['post'] );
		$this->assertArrayNotHasKey( 'page', $order, 'A list with one taxonomy left is not needed' );
	}

	/**
	 * Deleting a taxonomy this plugin does not hold is refused.
	 */
	public function test_delete_unknown_refused() {
		$this->expectException( 'WPDieException' );
		$this->delete( 'delete', 'category' );
	}

	/**
	 * Delete needs manage_options and a valid nonce.
	 */
	public function test_delete_refused_without_rights_or_nonce() {
		$this->import_config( 'staxo-config-suite.json' );

		$this->login( 'editor' );
		foreach ( array( 'delete', 'flush-delete' ) as $action ) {
			try {
				$this->delete( $action, 'test_hier' );
				$this->fail( "$action by an editor should be refused" );
			} catch ( WPDieException $e ) {
				unset( $e );
			}
		}

		$this->login( 'administrator' );
		try {
			$this->delete( 'delete', 'test_hier', 'bad' );
			$this->fail( 'Delete with a bad nonce should be refused' );
		} catch ( WPDieException $e ) {
			unset( $e );
		}

		$this->assertArrayHasKey( 'test_hier', get_option( OPTION_STAXO )['taxonomies'] );
	}

	/**
	 * Export PHP produces valid PHP for each fixture taxonomy, including the terms-control and count notes.
	 */
	public function test_export_php_is_valid() {
		$this->import_config( 'staxo-config-suite.json' );
		$options = get_option( OPTION_STAXO );

		foreach ( self::$staxo_taxonomies as $name ) {
			$code  = SimpleTaxonomyRefreshed_Admin::build_php_export( $options['taxonomies'][ $name ] );
			$names = $this->code_identifiers( $code );

			$this->assertContains( 'register_taxonomy', $names, "$name: no register_taxonomy call" );
			$this->assertContains( 'register_staxo_' . $name, $names, "$name: no registration function" );
			$this->assertStringContainsString( "register_taxonomy( '" . $name . "',", $code );
		}

		$cntl = SimpleTaxonomyRefreshed_Admin::build_php_export( $options['taxonomies']['test_cntl'] );
		$this->assertStringContainsString( 'Minimum number of terms set to 1.', $cntl );
		$count = SimpleTaxonomyRefreshed_Admin::build_php_export( $options['taxonomies']['test_count'] );
		$this->assertStringContainsString( 'Term count callback modified.', $count );
	}

	/**
	 * Values written into comments cannot break out of them (review finding L3).
	 */
	public function test_export_php_comments_are_safe() {
		$this->import_config( 'staxo-config-suite.json' );
		$taxo = get_option( OPTION_STAXO )['taxonomies']['test_cntl'];

		$taxo['st_before']      = "Before\nstaxo_injected_before();";
		$taxo['st_after']       = "After\r\nstaxo_injected_after();";
		$taxo['labels']['name'] = 'Evil */ staxo_injected_label(); /*';
		$taxo['st_cc_types']    = array( 'post */ staxo_injected_type(); /*' );

		$names = $this->code_identifiers( SimpleTaxonomyRefreshed_Admin::build_php_export( $taxo ) );

		foreach ( array( 'staxo_injected_before', 'staxo_injected_after', 'staxo_injected_label', 'staxo_injected_type' ) as $injected ) {
			$this->assertNotContains( $injected, $names, "$injected became code" );
		}
	}

	/**
	 * The taxonomy name is exported as a string literal and a safe function name.
	 */
	public function test_export_php_name_is_safe() {
		$this->import_config( 'staxo-config-suite.json' );
		$taxo         = get_option( OPTION_STAXO )['taxonomies']['test_hier'];
		$taxo['name'] = 'bad-name", staxo_injected_name(), "';

		$names = $this->code_identifiers( SimpleTaxonomyRefreshed_Admin::build_php_export( $taxo ) );

		$this->assertNotContains( 'staxo_injected_name', $names );
		$this->assertContains( 'register_staxo_bad_name___staxo_injected_name_____', $names );
	}

	/**
	 * Export PHP needs manage_options, and the taxonomy must be one of this plugin's.
	 */
	public function test_export_php_refusals() {
		$this->import_config( 'staxo-config-suite.json' );

		$cases = array(
			array( 'editor', 'test_hier', 'You do not have the necessary permissions.' ),
			array( 'administrator', 'category', 'You are trying to output a taxonomy' ),
		);
		foreach ( $cases as $case ) {
			$this->login( $case[0] );
			$request  = array(
				'action'        => 'export_php',
				'taxonomy_name' => $case[1],
				'_wpnonce'      => wp_create_nonce( 'staxo_export_php-' . $case[1] ),
			);
			$_POST    = array();
			$_GET     = $request;
			$_REQUEST = $request;
			try {
				SimpleTaxonomyRefreshed_Admin::admin_init();
				$this->fail( "Export of {$case[1]} by {$case[0]} should be refused" );
			} catch ( WPDieException $e ) {
				$this->assertStringContainsString( $case[2], $e->getMessage() );
			}
		}
	}
}
