<?php
/**
 * Tests for renaming a taxonomy slug.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Rename Slug on the shared fixture.
 *
 * @group rename
 */
class Test_STaxo_Rename extends STaxo_Test_Case {

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
	 * Unregister the renamed taxonomies.
	 *
	 * @return void
	 */
	public function tear_down() {
		foreach ( array( 'test_genre', 'test_tint', 'test_mood' ) as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				unregister_taxonomy( $taxonomy );
			}
		}
		parent::tear_down();
	}

	/**
	 * Run the rename handler.
	 *
	 * @param string $taxonomy taxonomy to rename.
	 * @param string $new_slug new taxonomy name.
	 * @param array  $fields   other form fields (new_query, new_rewrite).
	 * @param string $nonce    nonce to send; default a valid one.
	 * @return array settings errors raised.
	 */
	private function rename( $taxonomy, $new_slug, $fields = array(), $nonce = null ) {
		$this->clear_settings_errors();

		$_POST    = array_merge(
			array(
				'taxonomy'    => $taxonomy,
				'new_slug'    => $new_slug,
				'new_query'   => '',
				'new_rewrite' => '',
			),
			$fields
		);
		$_REQUEST = array( '_wpnonce' => ( is_null( $nonce ) ? wp_create_nonce( SimpleTaxonomyRefreshed_Admin_Rename::RENAME_SLUG ) : $nonce ) );

		$_POST[ SimpleTaxonomyRefreshed_Admin_Rename::RENAME_SLUG ] = '1';

		SimpleTaxonomyRefreshed_Admin_Rename::check_admin_post();

		return get_settings_errors( 'simple-taxonomy-refreshed' );
	}

	/**
	 * Make the renamed taxonomy the registered one, as on the next page load.
	 *
	 * @param string $old_name old taxonomy name.
	 * @return void
	 */
	private function reload( $old_name ) {
		unregister_taxonomy( $old_name );
		$this->register_taxonomies();
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
	 * A refused rename changes nothing.
	 *
	 * @param string $taxonomy taxonomy to rename.
	 * @param string $new_slug new taxonomy name.
	 * @param string $message  part of the expected wp_die() message.
	 * @return void
	 */
	private function assert_rename_refused( $taxonomy, $new_slug, $message ) {
		$before = get_option( OPTION_STAXO );
		try {
			$this->rename( $taxonomy, $new_slug );
			$this->fail( "Rename of $taxonomy to '$new_slug' should be refused" );
		} catch ( WPDieException $e ) {
			$this->assertStringContainsString( $message, $e->getMessage() );
		}
		$this->assertSame( $before, get_option( OPTION_STAXO ), 'Option changed' );
		$this->assertSame( 10, $this->term_rows( 'test_hier' ), 'Terms moved' );
	}

	/**
	 * Rename moves the definition, the terms and the posts' terms.
	 */
	public function test_rename() {
		$errors = $this->rename( 'test_hier', 'test_genre' );

		$options = get_option( OPTION_STAXO );
		$this->assertArrayNotHasKey( 'test_hier', $options['taxonomies'] );
		$this->assertArrayHasKey( 'test_genre', $options['taxonomies'] );
		$this->assertSame( 'test_genre', $options['taxonomies']['test_genre']['name'] );
		$this->assertSame( 0, $this->term_rows( 'test_hier' ) );
		$this->assertSame( 10, $this->term_rows( 'test_genre' ) );

		$messages = $this->messages( $errors, 'updated' );
		$this->assertContains( 'Taxonomy slug changed.', $messages );
		$this->assertContains( 'Done, 10 terms were migrated.', $messages, 'Count of migrated terms' );

		$this->reload( 'test_hier' );
		$this->assertTrue( taxonomy_exists( 'test_genre' ) );
		$this->assertSame( array( 'Jazz' ), $this->post_terms( 'P1', 'test_genre' ) );
		$this->assertSame( array( 'Bebop', 'Jazz' ), $this->post_terms( 'P3', 'test_genre' ) );
		$this->assertSame( 'Jazz', $this->term_tree( 'test_genre' )['Bebop'], 'Hierarchy kept' );
	}

	/**
	 * Query_var: empty or equal to the new slug means the default; any other value is stored.
	 */
	public function test_rename_query_var() {
		$this->rename( 'test_hier', 'test_genre' );
		$this->assertSame( '', get_option( OPTION_STAXO )['taxonomies']['test_genre']['query_var'], 'Empty: default' );

		// The old query_var was the default (the old name); a new value must still be used.
		$this->rename( 'test_count', 'test_tint', array( 'new_query' => 'tint' ) );
		$this->assertSame( 'tint', get_option( OPTION_STAXO )['taxonomies']['test_tint']['query_var'] );

		$this->rename( 'test_cntl', 'test_mood', array( 'new_query' => 'test_mood' ) );
		$this->assertSame( '', get_option( OPTION_STAXO )['taxonomies']['test_mood']['query_var'], 'Equal to new slug: default' );
	}

	/**
	 * The stored term hierarchy (<taxonomy>_children option) follows the rename.
	 */
	public function test_rename_moves_children_option() {
		_get_term_hierarchy( 'test_hier' );
		$children = get_option( 'test_hier_children' );
		$this->assertNotEmpty( $children );

		$errors = $this->rename( 'test_hier', 'test_genre' );

		$this->assertSame( $children, get_option( 'test_genre_children' ) );
		$this->assertFalse( get_option( 'test_hier_children' ) );
		$this->assertContains( 'Done, Children record in options table migrated.', $this->messages( $errors, 'updated' ) );
	}

	/**
	 * The default term setting (default_term_<taxonomy>) follows the rename.
	 */
	public function test_rename_moves_default_term() {
		$default = get_option( 'default_term_test_flat' );
		$this->assertNotEmpty( $default );

		$this->rename( 'test_flat', 'test_tint' );

		$this->assertSame( $default, get_option( 'default_term_test_tint' ) );
		$this->assertFalse( get_option( 'default_term_test_flat' ) );
	}

	/**
	 * Admin list orderings use the new name.
	 */
	public function test_rename_updates_list_order() {
		$options               = get_option( OPTION_STAXO );
		$options['list_order'] = array( 'post' => array( 'test_flat', 'test_hier', 'test_cntl' ) );
		update_option( OPTION_STAXO, $options );

		$this->rename( 'test_hier', 'test_genre' );

		$this->assertSame( array( 'test_flat', 'test_genre', 'test_cntl' ), get_option( OPTION_STAXO )['list_order']['post'] );
	}

	/**
	 * A new rewrite slug is stored and the rewrite rules are flushed at the next init.
	 */
	public function test_rename_rewrite_slug() {
		$options = get_option( OPTION_STAXO );

		$options['taxonomies']['test_hier']['rewrite'] = '1';
		update_option( OPTION_STAXO, $options );
		$this->register_taxonomies();
		$this->assertFalse( get_transient( 'simple_taxonomy_refreshed_rewrite' ) );

		$this->rename( 'test_hier', 'test_genre', array( 'new_rewrite' => 'Music/Genres' ) );

		$this->assertSame( 'music/genres', get_option( OPTION_STAXO )['taxonomies']['test_genre']['st_slug'] );
		$this->assertNotFalse( get_transient( 'simple_taxonomy_refreshed_rewrite' ) );
	}

	/**
	 * Cached term objects of the old taxonomy are cleared.
	 */
	public function test_rename_clears_term_cache() {
		$jazz = $this->term( 'test_hier', 'Jazz' )->term_id;
		get_term( $jazz, 'test_hier' );
		$this->assertNotFalse( wp_cache_get( $jazz, 'terms' ), 'Term should be cached before the rename' );

		$this->rename( 'test_hier', 'test_genre' );

		$this->assertFalse( wp_cache_get( $jazz, 'terms' ) );
	}

	/**
	 * Invalid new slugs are refused (review finding M6).
	 */
	public function test_invalid_new_slug_refused() {
		$this->assert_rename_refused( 'test_hier', '', 'must be different, non-empty' );
		$this->assert_rename_refused( 'test_hier', str_repeat( 'a', 33 ), 'must be different, non-empty' );
		$this->assert_rename_refused( 'test_hier', 'test_hier', 'must be different, non-empty' );
		$this->assert_rename_refused( 'test_hier', 'category', 'already exists' );
		$this->assert_rename_refused( 'test_hier', 'test_flat', 'already exists' );
	}

	/**
	 * Only this plugin's taxonomies can be renamed.
	 */
	public function test_other_taxonomies_refused() {
		$this->assert_rename_refused( 'category', 'test_cats', 'Only taxonomies created by Simple Taxonomy Refreshed' );
		$this->assert_rename_refused( 'no_such_taxo', 'test_genre', 'does not exist' );
	}

	/**
	 * A subscriber cannot rename.
	 */
	public function test_subscriber_refused() {
		$this->login( 'subscriber' );

		$this->assert_rename_refused( 'test_hier', 'test_genre', 'You do not have the necessary permissions.' );
	}

	/**
	 * A bad nonce is refused.
	 */
	public function test_bad_nonce_refused() {
		$this->expectException( 'WPDieException' );
		$this->rename( 'test_hier', 'test_genre', array(), 'bad' );
	}
}
