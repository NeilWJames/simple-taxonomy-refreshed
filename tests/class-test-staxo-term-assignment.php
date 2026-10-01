<?php
/**
 * Tests for adding and removing terms on posts, and term counts.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Term assignment, deletion and the STR count rules, on the shared fixture.
 *
 * @group assignment
 */
class Test_STaxo_Term_Assignment extends STaxo_Test_Case {

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
	 * Change test_count's count settings and recount its terms.
	 *
	 * @param array $settings STR settings to change (st_cb_*).
	 * @return void
	 */
	private function set_count_rules( $settings ) {
		$options = get_option( OPTION_STAXO );
		foreach ( $settings as $key => $value ) {
			$options['taxonomies']['test_count'][ $key ] = $value;
		}
		update_option( OPTION_STAXO, $options );
		delete_transient( 'staxo_sel_test_count' );

		$this->recount( 'test_count' );
	}

	/**
	 * Recount every term of a taxonomy.
	 *
	 * @param string $taxonomy taxonomy name.
	 * @return void
	 */
	private function recount( $taxonomy ) {
		$tt_ids = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'tt_ids',
			)
		);
		wp_update_term_count_now( $tt_ids, $taxonomy );
	}

	/**
	 * The fixture is as documented in STaxo_Fixtures::make_posts().
	 */
	public function test_fixture_matrix() {
		$this->assertSame( array( 'Bebop', 'Jazz' ), $this->post_terms( 'P3', 'test_hier' ) );
		$this->assertSame( array( 'blue', 'red' ), $this->post_terms( 'P3', 'test_cntl' ) );
		$this->assertSame( array( 'Unsorted' ), $this->post_terms( 'P8', 'test_flat' ), 'P8 should have the default term' );
		$this->assertSame( array( 'red' ), $this->post_terms( 'PG1', 'test_flat' ) );
		$this->assertSame( array(), $this->post_terms( 'P4', 'test_count' ) );
		$this->assertSame( 'trash', get_post_status( $this->posts['P9'] ) );
		$this->assertSame( 'future', get_post_status( $this->posts['P8'] ) );
	}

	/**
	 * Adding a term to a published post raises its count.
	 */
	public function test_add_term() {
		$this->assertSame( 0, $this->term_count( 'test_hier', 'Misc' ) );

		wp_add_object_terms( $this->posts['P1'], $this->term( 'test_hier', 'Misc' )->term_id, 'test_hier' );

		$this->assertSame( array( 'Jazz', 'Misc' ), $this->post_terms( 'P1', 'test_hier' ) );
		$this->assertSame( 1, $this->term_count( 'test_hier', 'Misc' ) );
	}

	/**
	 * Adding a term to a draft does not raise the default count.
	 */
	public function test_add_term_to_draft() {
		wp_add_object_terms( $this->posts['P5'], $this->term( 'test_hier', 'Misc' )->term_id, 'test_hier' );

		$this->assertSame( array( 'Misc', 'Rock' ), $this->post_terms( 'P5', 'test_hier' ) );
		$this->assertSame( 0, $this->term_count( 'test_hier', 'Misc' ) );
	}

	/**
	 * Removing a term lowers its count.
	 */
	public function test_remove_term() {
		$this->assertSame( 2, $this->term_count( 'test_hier', 'Jazz' ), 'P1 and P3; P9 is in the trash' );

		wp_remove_object_terms( $this->posts['P1'], $this->term( 'test_hier', 'Jazz' )->term_id, 'test_hier' );

		$this->assertSame( array(), $this->post_terms( 'P1', 'test_hier' ) );
		$this->assertSame( 1, $this->term_count( 'test_hier', 'Jazz' ) );
	}

	/**
	 * Removing the last flat term leaves the post with none (the default term only applies on insert).
	 */
	public function test_remove_last_term() {
		wp_remove_object_terms( $this->posts['P5'], $this->term( 'test_flat', 'yellow' )->term_id, 'test_flat' );

		$this->assertSame( array(), $this->post_terms( 'P5', 'test_flat' ) );
		$this->assertSame( 0, $this->term_count( 'test_flat', 'yellow' ), 'P6 is pending, so not counted' );
	}

	/**
	 * Deleting a term removes it from posts and moves its children up.
	 */
	public function test_delete_term() {
		$jazz = $this->term( 'test_hier', 'Jazz' );

		$this->assertTrue( wp_delete_term( $jazz->term_id, 'test_hier' ) );

		$this->assertSame( array(), $this->post_terms( 'P1', 'test_hier' ) );
		$this->assertSame( array( 'Bebop' ), $this->post_terms( 'P3', 'test_hier' ) );
		$this->assertSame( array(), $this->post_terms( 'P9', 'test_hier' ) );

		$tree = $this->term_tree( 'test_hier' );
		$this->assertSame( 'Music', $tree['Bebop'] );
		$this->assertSame( 'Music', $tree['Big Band'] );
	}

	/**
	 * The default term of test_flat cannot be deleted.
	 */
	public function test_default_term_not_deleted() {
		$unsorted = $this->term( 'test_flat', 'Unsorted' );

		$this->assertSame( 0, wp_delete_term( $unsorted->term_id, 'test_flat' ) );
		$this->assertTrue( $this->term_exists_by_name( 'test_flat', 'Unsorted' ) );
	}

	/**
	 * A new post without test_flat terms gets the default term.
	 */
	public function test_default_term_on_new_post() {
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		$this->assertSame( array( 'Unsorted' ), $this->post_terms( $post_id, 'test_flat' ) );
	}

	/**
	 * Default count rules: published posts only.
	 */
	public function test_default_counts() {
		$expected = array(
			'Jazz'      => 2, // P1, P3 (P9 in trash).
			'Bebop'     => 2, // P2, P3.
			'Big Band'  => 1, // P4.
			'Rock'      => 0, // P5 draft.
			'Punk'      => 0, // P6 pending.
			'Painting'  => 0, // P7 private.
			'Sculpture' => 0, // P8 future.
		);
		foreach ( $expected as $name => $count ) {
			$this->assertSame( $count, $this->term_count( 'test_hier', $name ), "test_hier $name" );
		}
		$this->assertSame( 3, $this->term_count( 'test_flat', 'red' ), 'P1, P4 and page PG1; P9 in trash not counted' );
	}

	/**
	 * STR count rules (st_cb_type 2): test_count counts publish and draft.
	 */
	public function test_count_publish_and_draft() {
		$this->assertSame( 2, $this->term_count( 'test_count', 'Jazz' ), 'P1, P3; P9 trash' );
		$this->assertSame( 1, $this->term_count( 'test_count', 'Rock' ), 'P5 draft; P6 pending not counted' );
		$this->assertSame( 0, $this->term_count( 'test_count', 'Painting' ), 'P7 private' );
		$this->assertSame( 0, $this->term_count( 'test_count', 'Sculpture' ), 'P8 future' );
	}

	/**
	 * Counts follow post status changes.
	 */
	public function test_count_follows_status_changes() {
		wp_update_post(
			array(
				'ID'          => $this->posts['P6'],
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 2, $this->term_count( 'test_count', 'Rock' ), 'P5 draft and P6 now published' );

		wp_trash_post( $this->posts['P5'] );
		$this->assertSame( 1, $this->term_count( 'test_count', 'Rock' ), 'P5 trashed' );
	}

	/**
	 * STR count rules (st_cb_type 1): every status except trash, inherit and auto-draft.
	 */
	public function test_count_all_statuses() {
		$this->set_count_rules( array( 'st_cb_type' => '1' ) );

		$this->assertSame( 2, $this->term_count( 'test_count', 'Jazz' ), 'P1, P3; P9 trash' );
		$this->assertSame( 2, $this->term_count( 'test_count', 'Rock' ), 'P5 draft, P6 pending' );
		$this->assertSame( 1, $this->term_count( 'test_count', 'Painting' ), 'P7 private' );
		$this->assertSame( 1, $this->term_count( 'test_count', 'Sculpture' ), 'P8 future' );
	}

	/**
	 * STR count rules (st_cb_type 2) with trash ticked count trashed posts.
	 */
	public function test_count_with_trash() {
		$this->set_count_rules( array( 'st_cb_tsh' => 1 ) );

		$this->assertSame( 3, $this->term_count( 'test_count', 'Jazz' ), 'P1, P3 and P9 in trash' );
	}

	/**
	 * The staxo_term_count_statuses filter can add a status.
	 */
	public function test_count_statuses_filter() {
		$add_pending = static function ( $statuses, $taxonomy ) {
			if ( 'test_count' === $taxonomy ) {
				$statuses[] = 'pending';
			}
			return $statuses;
		};
		add_filter( 'staxo_term_count_statuses', $add_pending, 10, 2 );
		delete_transient( 'staxo_sel_test_count' );
		$this->recount( 'test_count' );

		$this->assertSame( 2, $this->term_count( 'test_count', 'Rock' ), 'P5 draft and P6 pending' );
	}
}
