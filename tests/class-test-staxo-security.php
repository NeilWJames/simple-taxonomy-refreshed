<?php
/**
 * Security sweep: every state-changing handler checks the user's capability and the nonce.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Capability and nonce checks for all admin handlers, in one place (review finding M4).
 *
 * Each handler is called as the request from its screen would call it. Users without the
 * capability, and requests with a bad nonce, must be refused and must change nothing.
 *
 * @group security
 */
class Test_STaxo_Security extends STaxo_Test_Case {

	/**
	 * Log in an administrator and load taxonomies, terms and posts.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->login( 'administrator' );
		$this->load_fixture();
		$this->make_posts();
	}

	/**
	 * Clear the request data.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_GET = array();
		parent::tear_down();
	}

	/**
	 * A snapshot of everything the handlers can change.
	 *
	 * @return string
	 */
	private function snapshot() {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$terms = $wpdb->get_results( "SELECT term_taxonomy_id, term_id, taxonomy, parent FROM $wpdb->term_taxonomy WHERE taxonomy LIKE 'test\\_%' ORDER BY term_taxonomy_id", ARRAY_A );
		$links = $wpdb->get_results( "SELECT object_id, term_taxonomy_id FROM $wpdb->term_relationships ORDER BY object_id, term_taxonomy_id", ARRAY_A );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		return md5( wp_json_encode( array( get_option( OPTION_STAXO ), $terms, $links ) ) );
	}

	/**
	 * Set the request for a handler.
	 *
	 * @param array  $post  $_POST fields.
	 * @param array  $get   $_GET fields.
	 * @param string $nonce nonce value.
	 * @param string $flag  name of a $_POST field set to '1' (the form's submit button).
	 * @return void
	 */
	private function request( $post, $get, $nonce, $flag = '' ) {
		$this->clear_settings_errors();
		if ( '' !== $flag ) {
			// The form's submit button name, which the handler looks for.
			$post[ $flag ] = '1';
		}
		$_POST    = $post;
		$_GET     = $get;
		$_REQUEST = array_merge( $post, $get, array( '_wpnonce' => $nonce ) );
	}

	/**
	 * The state-changing handlers.
	 *
	 * Each entry: name => array( minimum role, callable taking a "valid nonce" flag ).
	 * The callable sets the request as the screen would, then calls the handler.
	 *
	 * @return array
	 */
	private function handlers() {
		$id = function ( $taxonomy, $name ) {
			return $this->term( $taxonomy, $name )->term_id;
		};

		$nonce = static function ( $action, $valid ) {
			return ( $valid ? wp_create_nonce( $action ) : 'bad' );
		};

		$taxonomy_form = array(
			'name'    => 'test_hier',
			'labels'  => array( 'name' => 'Changed' ),
			'objects' => array( 'post' ),
		);

		return array(
			'config import'       => array(
				'administrator',
				function ( $valid ) use ( $nonce ) {
					$this->request( array(), array(), $nonce( SimpleTaxonomyRefreshed_Admin_Config::IMP_FILE_SLUG, $valid ), SimpleTaxonomyRefreshed_Admin_Config::IMP_FILE_SLUG );
					$_FILES['config_file'] = array(
						'error'    => 0,
						'tmp_name' => __DIR__ . '/files/staxo-config-test-hier.json',
					);
					SimpleTaxonomyRefreshed_Admin_Config::check_importexport();
				},
			),
			'config export'       => array(
				'administrator',
				function ( $valid ) use ( $nonce ) {
					$this->request( array( 'action' => SimpleTaxonomyRefreshed_Admin_Config::EXP_FILE_SLUG ), array(), $nonce( SimpleTaxonomyRefreshed_Admin_Config::EXP_FILE_SLUG, $valid ) );
					SimpleTaxonomyRefreshed_Admin_Config::check_importexport();
				},
			),
			'add taxonomy'        => array(
				'administrator',
				function ( $valid ) use ( $nonce, $taxonomy_form ) {
					$form         = $taxonomy_form;
					$form['name'] = 'test_new';
					$this->request( array_merge( $form, array( 'action' => 'add-taxonomy' ) ), array(), $nonce( 'staxo_add_taxo', $valid ) );
					SimpleTaxonomyRefreshed_Admin::admin_init();
				},
			),
			'update taxonomy'     => array(
				'administrator',
				function ( $valid ) use ( $nonce, $taxonomy_form ) {
					$this->request( array_merge( $taxonomy_form, array( 'action' => 'merge-taxonomy' ) ), array(), $nonce( 'staxo_edit_taxo', $valid ) );
					SimpleTaxonomyRefreshed_Admin::admin_init();
				},
			),
			'update external'     => array(
				'administrator',
				function ( $valid ) use ( $nonce ) {
					$this->request(
						array(
							'action'     => 'merge-external',
							'name'       => 'category',
							'st_cb_type' => '1',
						),
						array(),
						$nonce( 'staxo_edit_taxo', $valid )
					);
					SimpleTaxonomyRefreshed_Admin::admin_init();
				},
			),
			'delete taxonomy'     => array(
				'administrator',
				function ( $valid ) use ( $nonce ) {
					$this->request(
						array(),
						array(
							'action'        => 'delete',
							'taxonomy_name' => 'test_hier',
						),
						$nonce( 'staxo_delete_test_hier', $valid )
					);
					SimpleTaxonomyRefreshed_Admin::admin_init();
				},
			),
			'flush-delete'        => array(
				'administrator',
				function ( $valid ) use ( $nonce ) {
					$this->request(
						array(),
						array(
							'action'        => 'flush-delete',
							'taxonomy_name' => 'test_hier',
						),
						$nonce( 'staxo_flush_delete-test_hier', $valid )
					);
					SimpleTaxonomyRefreshed_Admin::admin_init();
				},
			),
			'export PHP'          => array(
				'administrator',
				function ( $valid ) use ( $nonce ) {
					$this->request(
						array(),
						array(
							'action'        => 'export_php',
							'taxonomy_name' => 'test_hier',
						),
						$nonce( 'staxo_export_php-test_hier', $valid )
					);
					SimpleTaxonomyRefreshed_Admin::admin_init();
				},
			),
			'terms import'        => array(
				'editor',
				function ( $valid ) use ( $nonce ) {
					$this->request(
						array(
							'taxonomy'       => 'test_cntl',
							'hierarchy'      => 'no',
							'import_content' => 'mauve',
						),
						array(),
						$nonce( SimpleTaxonomyRefreshed_Admin_Import::IMPORT_SLUG, $valid ),
						SimpleTaxonomyRefreshed_Admin_Import::IMPORT_SLUG
					);
					SimpleTaxonomyRefreshed_Admin_Import::check_importation();
				},
			),
			'terms merge'         => array(
				'editor',
				function ( $valid ) use ( $nonce, $id ) {
					$this->request(
						array(
							'action'      => SimpleTaxonomyRefreshed_Admin_Merge::MERGE_SLUG,
							'phase'       => 'four',
							'taxonomy'    => 'test_cntl',
							'destination' => $id( 'test_cntl', 'green' ),
							'sources'     => (string) $id( 'test_cntl', 'red' ),
						),
						array(),
						$nonce( SimpleTaxonomyRefreshed_Admin_Merge::MERGE_SLUG, $valid )
					);
					SimpleTaxonomyRefreshed_Admin_Merge::staxo_merge();
				},
			),
			'terms conversion'    => array(
				'administrator',
				function ( $valid ) use ( $nonce ) {
					$this->request(
						array(
							'action' => SimpleTaxonomyRefreshed_Admin_Conversion::CONVERT_SLUG,
							'name'   => array( 'test_hier', 'test_count' ),
							'copy'   => array( 0 => '1' ),
							'oput'   => array( 1 => '1' ),
						),
						array(),
						$nonce( SimpleTaxonomyRefreshed_Admin_Conversion::CONVERT_SLUG, $valid )
					);
					SimpleTaxonomyRefreshed_Admin_Conversion::staxo_convert();
				},
			),
			'rename slug'         => array(
				'administrator',
				function ( $valid ) use ( $nonce ) {
					$this->request(
						array(
							'taxonomy' => 'test_hier',
							'new_slug' => 'test_genre',
						),
						array(),
						$nonce( SimpleTaxonomyRefreshed_Admin_Rename::RENAME_SLUG, $valid ),
						SimpleTaxonomyRefreshed_Admin_Rename::RENAME_SLUG
					);
					SimpleTaxonomyRefreshed_Admin_Rename::check_admin_post();
				},
			),
			'taxonomy list order' => array(
				'administrator',
				function ( $valid ) use ( $nonce ) {
					$this->request(
						array( 'post_arr' => wp_json_encode( array( 'test_count', 'test_hier' ) ) ),
						array(),
						$nonce( SimpleTaxonomyRefreshed_Admin_Order::ORDER_SLUG, $valid ),
						SimpleTaxonomyRefreshed_Admin_Order::ORDER_SLUG
					);
					SimpleTaxonomyRefreshed_Admin_Order::check_admin_order();
				},
			),
		);
	}

	/**
	 * Call a handler and require that it is refused and changes nothing.
	 *
	 * @param string   $label   description for failure messages.
	 * @param callable $handler handler from handlers().
	 * @param bool     $valid   whether to send a valid nonce.
	 * @param string   $reason  regular expression the refusal message must match.
	 * @return void
	 */
	private function assert_refused( $label, $handler, $valid, $reason ) {
		$before = $this->snapshot();
		$level  = ob_get_level();
		ob_start();
		try {
			call_user_func( $handler, $valid );
			$this->fail( "$label: not refused" );
		} catch ( WPDieException $e ) {
			$this->assertMatchesRegularExpression( $reason, $e->getMessage(), "$label: unexpected refusal message" );
		} finally {
			while ( ob_get_level() > $level ) {
				ob_end_clean();
			}
		}
		$this->assertSame( $before, $this->snapshot(), "$label: data changed" );
	}

	/**
	 * Roles below each handler's minimum role are refused.
	 */
	public function test_roles_refused() {
		$roles = array( 'subscriber', 'contributor', 'author', 'editor' );

		foreach ( $this->handlers() as $label => $handler ) {
			$below = array_slice( $roles, 0, array_search( $handler[0], array_merge( $roles, array( 'administrator' ) ), true ) );
			foreach ( $below as $role ) {
				$this->login( $role );
				$this->assert_refused( "$label as $role", $handler[1], true, '/permission|cannot edit/i' );
			}
		}
	}

	/**
	 * A visitor who is not logged in is refused.
	 */
	public function test_logged_out_refused() {
		foreach ( $this->handlers() as $label => $handler ) {
			wp_set_current_user( 0 );
			$this->assert_refused( "$label logged out", $handler[1], true, '/permission|cannot edit/i' );
		}
	}

	/**
	 * An administrator with a bad nonce is refused.
	 */
	public function test_bad_nonce_refused() {
		foreach ( $this->handlers() as $label => $handler ) {
			$this->login( 'administrator' );
			$this->assert_refused( "$label with a bad nonce", $handler[1], false, '/link you followed has expired/i' );
		}
	}

	/**
	 * The plugin offers nothing to visitors who are not logged in: no nopriv AJAX actions and no REST routes.
	 */
	public function test_no_public_endpoints() {
		// The admin classes hook their AJAX actions when created (normally only in wp-admin).
		SimpleTaxonomyRefreshed_Admin_Merge::get_instance();
		SimpleTaxonomyRefreshed_Admin_Conversion::get_instance();

		global $wp_filter;
		foreach ( array_keys( $wp_filter ) as $hook ) {
			$this->assertFalse( 0 === strpos( $hook, 'wp_ajax_nopriv_staxo' ), "Public AJAX action $hook" );
		}

		foreach ( array_keys( rest_get_server()->get_routes() ) as $route ) {
			$this->assertStringNotContainsString( 'simple-taxonomy', $route );
			$this->assertStringNotContainsString( 'staxo', $route );
		}
	}
}
