<?php
/**
 * Tests for the Taxonomy List Order (admin list column order).
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Saving and applying the order of taxonomy columns on the admin post lists.
 *
 * @group order
 */
class Test_STaxo_Order extends STaxo_Test_Case {

	/**
	 * Log in an administrator and register the fixture taxonomies.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->login( 'administrator' );
		$this->import_config( 'staxo-config-suite.json' );
	}

	/**
	 * The default column order for a post type: its taxonomies shown in the admin list, in registration order.
	 *
	 * @param string $post_type post type.
	 * @return string[]
	 */
	private function default_order( $post_type ) {
		$taxonomies = wp_filter_object_list( get_object_taxonomies( $post_type, 'objects' ), array( 'show_admin_column' => true ) );

		return array_values( wp_list_pluck( $taxonomies, 'name' ) );
	}

	/**
	 * Save orders from the Taxonomy List Order page.
	 *
	 * @param array  $orders post type => list (array, encoded as JSON) or raw string.
	 * @param string $nonce  nonce to send; default a valid one.
	 * @return array settings errors raised.
	 */
	private function save_order( $orders, $nonce = null ) {
		$this->clear_settings_errors();

		$request = array();
		foreach ( $orders as $post_type => $order ) {
			$request[ $post_type . '_arr' ] = ( is_array( $order ) ? wp_json_encode( $order ) : $order );
		}
		$_POST    = $request;
		$_REQUEST = array( '_wpnonce' => ( is_null( $nonce ) ? wp_create_nonce( SimpleTaxonomyRefreshed_Admin_Order::ORDER_SLUG ) : $nonce ) );

		$_POST[ SimpleTaxonomyRefreshed_Admin_Order::ORDER_SLUG ] = '1';

		SimpleTaxonomyRefreshed_Admin_Order::check_admin_order();

		return get_settings_errors( 'simple-taxonomy-refreshed' );
	}

	/**
	 * The stored order for a post type, or null.
	 *
	 * @param string $post_type post type.
	 * @return string[]|null
	 */
	private function stored_order( $post_type ) {
		$options = get_option( OPTION_STAXO );

		return ( isset( $options['list_order'][ $post_type ] ) ? $options['list_order'][ $post_type ] : null );
	}

	/**
	 * The fixture gives posts several admin-column taxonomies.
	 */
	public function test_fixture_columns() {
		$default = $this->default_order( 'post' );

		foreach ( self::$staxo_taxonomies as $taxonomy ) {
			$this->assertContains( $taxonomy, $default );
		}
		$this->assertSame( array( 'test_flat' ), $this->default_order( 'page' ) );
	}

	/**
	 * A new order for one post type is saved (it was dropped when only one post type had its own order).
	 */
	public function test_save_order() {
		$order  = array_reverse( $this->default_order( 'post' ) );
		$errors = $this->save_order( array( 'post' => $order ) );

		$this->assertSame( $order, $this->stored_order( 'post' ) );
		$this->assertContains( '"Posts" admin list taxonomies updated.', $this->messages( $errors, 'updated' ) );
	}

	/**
	 * The saved order is applied to the admin list columns.
	 */
	public function test_order_applied() {
		$default = $this->default_order( 'post' );
		$order   = array_reverse( $default );
		$this->save_order( array( 'post' => $order ) );

		SimpleTaxonomyRefreshed_Client::admin_init();

		$this->assertSame( $order, apply_filters( 'manage_taxonomies_for_post_columns', $default, 'post' ) );
		$this->assertSame( array( 'test_flat' ), apply_filters( 'manage_taxonomies_for_page_columns', array( 'test_flat' ), 'page' ), 'Pages unchanged' );
	}

	/**
	 * Saving the default order stores nothing.
	 */
	public function test_default_order_not_stored() {
		$this->save_order( array( 'post' => array_reverse( $this->default_order( 'post' ) ) ) );
		$this->save_order( array( 'post' => $this->default_order( 'post' ) ) );

		$this->assertArrayNotHasKey( 'list_order', get_option( OPTION_STAXO ) );
	}

	/**
	 * Unknown names are dropped, duplicates removed and missing taxonomies added at the end.
	 */
	public function test_order_cleaned() {
		$default = $this->default_order( 'post' );

		$this->save_order( array( 'post' => array( 'test_count', 'no_such_taxo', '<b>x</b>', 'test_count', array( 'nested' ), 'test_hier' ) ) );

		$expected = array_merge( array( 'test_count', 'test_hier' ), array_values( array_diff( $default, array( 'test_count', 'test_hier' ) ) ) );
		$this->assertSame( $expected, $this->stored_order( 'post' ) );
	}

	/**
	 * A value that is not a list is ignored.
	 */
	public function test_order_not_a_list() {
		$errors = $this->save_order( array( 'post' => '"test_hier"' ) );

		$this->assertNull( $this->stored_order( 'post' ) );
		$this->assertEmpty( $this->messages( $errors, 'updated' ) );
	}

	/**
	 * Taxonomies no longer shown are left out of the columns; new ones are added.
	 */
	public function test_order_applied_after_changes() {
		$options               = get_option( OPTION_STAXO );
		$options['list_order'] = array( 'post' => array( 'gone_taxo', 'test_count', 'test_hier' ) );
		update_option( OPTION_STAXO, $options );

		$default = $this->default_order( 'post' );
		$columns = SimpleTaxonomyRefreshed_Client::reorder_admin_list( $default, 'post' );

		$this->assertNotContains( 'gone_taxo', $columns );
		$this->assertSame( array( 'test_count', 'test_hier' ), array_slice( $columns, 0, 2 ) );
		$this->assertEqualSets( $default, $columns );
		$this->assertSame( $default, SimpleTaxonomyRefreshed_Client::reorder_admin_list( $default, 'page' ), 'No saved order for pages' );
	}

	/**
	 * An editor, or a bad nonce, cannot change the order.
	 */
	public function test_order_refusals() {
		$order = array_reverse( $this->default_order( 'post' ) );

		$this->login( 'editor' );
		try {
			$this->save_order( array( 'post' => $order ) );
			$this->fail( 'An editor should be refused' );
		} catch ( WPDieException $e ) {
			$this->assertStringContainsString( 'necessary permissions', $e->getMessage() );
		}

		$this->login( 'administrator' );
		try {
			$this->save_order( array( 'post' => $order ), 'bad' );
			$this->fail( 'A bad nonce should be refused' );
		} catch ( WPDieException $e ) {
			unset( $e );
		}

		$this->assertNull( $this->stored_order( 'post' ) );
	}

	/**
	 * The page lists each post type's taxonomies with Move up / Move down buttons (keyboard alternative to dragging).
	 */
	public function test_page_sortable_lists() {
		$default = $this->default_order( 'post' );

		ob_start();
		SimpleTaxonomyRefreshed_Admin_Order::page_admin_order();
		$page = ob_get_clean();

		$this->assertStringContainsString( '<ul id="post_list" class="staxo-sortable" data-moved="%1$s moved to position %2$s of %3$s.">', $page );
		foreach ( $default as $taxonomy ) {
			$this->assertStringContainsString( '<li class="sort-li" data-name="' . $taxonomy . '">', $page );
			$this->assertStringContainsString( 'data-move="-1" aria-label="Move up: ' . $taxonomy . '">Move up</button>', $page );
			$this->assertStringContainsString( 'data-move="1" aria-label="Move down: ' . $taxonomy . '">Move down</button>', $page );
		}
		$this->assertStringNotContainsString( '<li class="sort-li" tabindex', $page, 'Items are not focus stops; their buttons are' );
		// Tabs (review A6): the selected tab is the only one in the Tab order, and names its panel.
		$this->assertStringContainsString( '<div role="tablist" aria-label="Post types"><button type="button" role="tab" id="tab-post_pnl" aria-controls="post_pnl" aria-selected="true" tabindex="0">Posts</button>', $page );
		$this->assertStringContainsString( '<div id="post_pnl" class="meta-box-sortabless" role="tabpanel" aria-labelledby="tab-post_pnl" tabindex="0">', $page );
		$this->assertStringContainsString( 'staxo_sortable( "post_list", "post_arr", "' . SimpleTaxonomyRefreshed_Admin_Order::ORDER_SLUG . '" );', $page );
		$this->assertStringNotContainsString( 'staxo_sortable( "page_list"', $page, 'Pages have one taxonomy: nothing to order' );
	}
}
