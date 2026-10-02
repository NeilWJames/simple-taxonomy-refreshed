<?php
/**
 * Tests for Terms Merge.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Terms Merge over AJAX, phase by phase, on the shared fixture.
 *
 * @group merge
 */
class Test_STaxo_Merge extends STaxo_Ajax_Test_Case {

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
	 * Number of term_relationships rows for a term.
	 *
	 * @param int $tt_id term_taxonomy_id.
	 * @return int
	 */
	private function relationship_rows( $tt_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $tt_id ) );
	}

	/**
	 * Phase one lists the taxonomy's terms as radio buttons.
	 */
	public function test_phase_one_lists_terms() {
		$response = $this->merge_phase( 'one', 'test_hier' );

		foreach ( array( 'Music', 'Jazz', 'Bebop', 'Big Band', 'Rock', 'Punk', 'Art', 'Painting', 'Sculpture', 'Misc' ) as $name ) {
			$this->assertStringContainsString( '>' . $name . '<', $response, "$name not listed" );
		}
		$this->assertStringContainsString( 'type="radio"', $response );
		$this->assertStringContainsString( 'id="phase" value="two"', $response );
		$this->assertStringContainsString( 'id="control" value="0/0"', $response, 'No terms control on test_hier' );
	}

	/**
	 * Phase one warns when the taxonomy has a terms control minimum.
	 */
	public function test_phase_one_terms_control_warning() {
		$response = $this->merge_phase( 'one', 'test_cntl' );

		$this->assertStringContainsString( 'Terms Control implemented with a minimum', $response );
		$this->assertStringContainsString( 'id="control" value="2/1"', $response );
	}

	/**
	 * Phase two offers checkboxes and disables the destination.
	 */
	public function test_phase_two_disables_destination() {
		$red      = $this->term( 'test_flat', 'red' )->term_id;
		$response = $this->merge_phase( 'two', 'test_flat', array( 'term' => $red ) );

		$this->assertStringContainsString( 'type="checkbox"', $response );
		$this->assertStringContainsString( 'id="tax_' . $red . '" value="' . $red . '" disabled', $response );
		$this->assertStringContainsString( 'id="phase" value="three"', $response );
		$this->assertStringContainsString( 'id="destination" value="' . $red . '"', $response );
	}

	/**
	 * Hierarchical phase two disables the destination and its ancestors, and each label matches its input.
	 */
	public function test_phase_two_hierarchical() {
		$ids = array();
		foreach ( array( 'Music', 'Jazz', 'Bebop', 'Rock' ) as $name ) {
			$ids[ $name ] = $this->term( 'test_hier', $name )->term_id;
		}
		$response = $this->merge_phase( 'two', 'test_hier', array( 'term' => $ids['Bebop'] ) );

		foreach ( array( 'Music', 'Jazz', 'Bebop' ) as $name ) {
			$this->assertStringContainsString( 'id="tax_' . $ids[ $name ] . '" value="' . $ids[ $name ] . '" disabled', $response, "$name should be disabled" );
		}
		$this->assertStringContainsString( 'id="tax_' . $ids['Rock'] . '" value="' . $ids['Rock'] . '" /', $response, 'Rock should be enabled' );
		$this->assertStringContainsString( '<label for="tax_' . $ids['Rock'] . '" >Rock</label>', $response );
	}

	/**
	 * Phase three keeps only valid sources: not the destination, not unknown ids, not terms of other taxonomies.
	 */
	public function test_phase_three_filters_sources() {
		$jazz  = $this->term( 'test_hier', 'Jazz' )->term_id;
		$bebop = $this->term( 'test_hier', 'Bebop' )->term_id;
		$red   = $this->term( 'test_flat', 'red' )->term_id;

		$response = $this->merge_phase(
			'three',
			'test_hier',
			array(
				'destination' => $jazz,
				'term'        => array( $bebop, $jazz, 999999, $red ),
			)
		);

		$this->assertStringContainsString( 'id="sources" value="' . $bebop . '"', $response );
		$this->assertStringContainsString( 'id="phase" value="four"', $response );
		$this->assertStringNotContainsString( 'Child terms', $response, 'Bebop has no children, so no choice' );
	}

	/**
	 * Merge one source; one post already has both terms.
	 */
	public function test_merge_single_source() {
		$bebop    = $this->term( 'test_hier', 'Bebop' );
		$response = $this->merge( 'test_hier', 'Jazz', array( 'Bebop' ) );

		$this->assertSame( array( 'Jazz' ), $this->post_terms( 'P1', 'test_hier' ) );
		$this->assertSame( array( 'Jazz' ), $this->post_terms( 'P2', 'test_hier' ), 'P2 not moved to Jazz' );
		$this->assertSame( array( 'Jazz' ), $this->post_terms( 'P3', 'test_hier' ), 'P3 should have Jazz once' );

		$this->assertFalse( $this->term_exists_by_name( 'test_hier', 'Bebop' ), 'Source not deleted' );
		$this->assertSame( 0, $this->relationship_rows( $bebop->term_taxonomy_id ), 'Source relationships left' );
		$this->assertSame( '', get_term_meta( $bebop->term_id, 'staxo_test_meta', true ), 'Source meta not deleted' );

		// P1, P2, P3 published; P9 is in the trash.
		$this->assertSame( 3, $this->term_count( 'test_hier', 'Jazz' ) );
		$this->assertStringContainsString( 'Some posts were already linked to the destination term.', $response );
		$this->assertStringContainsString( 'destination term count is now : 3', $response );
	}

	/**
	 * Merge several sources at once (review finding H1).
	 */
	public function test_merge_several_sources() {
		$sources = array();
		foreach ( array( 'Bebop', 'Big Band', 'Rock' ) as $name ) {
			$sources[ $name ] = $this->term( 'test_hier', $name );
		}

		$this->merge( 'test_hier', 'Jazz', array_keys( $sources ) );

		foreach ( $sources as $name => $term ) {
			$this->assertFalse( $this->term_exists_by_name( 'test_hier', $name ), "$name not deleted" );
			$this->assertSame( 0, $this->relationship_rows( $term->term_taxonomy_id ), "$name relationships left" );
		}
		foreach ( array( 'P2', 'P3', 'P4', 'P5' ) as $post ) {
			$this->assertSame( array( 'Jazz' ), $this->post_terms( $post, 'test_hier' ), "$post not moved to Jazz" );
		}

		// Punk (child of Rock) moves under the destination.
		$this->assertSame( 'Jazz', $this->term_tree( 'test_hier' )['Punk'] );

		// P1-P4 published; P5 draft and P9 trash not counted.
		$this->assertSame( 4, $this->term_count( 'test_hier', 'Jazz' ) );
	}

	/**
	 * Merging a parent term: its children move under the destination and keep their posts.
	 */
	public function test_merge_parent_term() {
		$this->merge( 'test_hier', 'Art', array( 'Music' ) );

		$tree = $this->term_tree( 'test_hier' );
		$this->assertArrayNotHasKey( 'Music', $tree );
		$this->assertSame( 'Art', $tree['Jazz'], 'Jazz should move under Art' );
		$this->assertSame( 'Art', $tree['Rock'], 'Rock should move under Art' );
		$this->assertSame( 'Jazz', $tree['Bebop'], 'Grandchildren keep their parent' );
		$this->assertSame( 'Rock', $tree['Punk'] );
		$this->assertSame( array( 'Jazz' ), $this->post_terms( 'P1', 'test_hier' ) );
		$this->assertSame( 0, $this->term_count( 'test_hier', 'Art' ) );
	}

	/**
	 * Phase three offers a choice for child terms when a source has children (default: under the destination).
	 */
	public function test_phase_three_child_terms_choice() {
		$response = $this->merge_phase(
			'three',
			'test_hier',
			array(
				'destination' => $this->term( 'test_hier', 'Art' )->term_id,
				'term'        => array( $this->term( 'test_hier', 'Music' )->term_id ),
			)
		);

		$this->assertStringContainsString( 'Child terms of the source term(s):', $response );
		$this->assertStringContainsString( 'name="children" id="children_under" value="under" checked', $response );
		$this->assertStringContainsString( 'name="children" id="children_up" value="up" />', $response );
	}

	/**
	 * With "up a level" the children of a merged parent move to its parent, as before 4.0.0.
	 */
	public function test_merge_children_up() {
		$response = $this->merge_phase(
			'four',
			'test_hier',
			array(
				'destination' => $this->term( 'test_hier', 'Art' )->term_id,
				'sources'     => (string) $this->term( 'test_hier', 'Music' )->term_id,
				'children'    => 'up',
			)
		);

		$tree = $this->term_tree( 'test_hier' );
		$this->assertArrayNotHasKey( 'Music', $tree );
		$this->assertSame( '', $tree['Jazz'], 'Jazz should move to the top level' );
		$this->assertSame( '', $tree['Rock'], 'Rock should move to the top level' );
		$this->assertSame( 'Jazz', $tree['Bebop'], 'Grandchildren keep their parent' );
		$this->assertSame( array( 'Jazz' ), $this->post_terms( 'P1', 'test_hier' ) );
		$this->assertStringContainsString( 'Child terms of the source term(s) have been moved up a level.', $response );
	}

	/**
	 * With "up a level" a destination below a source is left where it is (it only moves up with its parent).
	 */
	public function test_merge_children_up_destination_below_source() {
		$response = $this->merge_phase(
			'four',
			'test_hier',
			array(
				'destination' => $this->term( 'test_hier', 'Bebop' )->term_id,
				'sources'     => (string) $this->term( 'test_hier', 'Music' )->term_id,
				'children'    => 'up',
			)
		);

		$tree = $this->term_tree( 'test_hier' );
		$this->assertArrayNotHasKey( 'Music', $tree );
		$this->assertSame( '', $tree['Jazz'] );
		$this->assertSame( 'Jazz', $tree['Bebop'], 'Bebop stays under Jazz' );
		$this->assertStringNotContainsString( 'The destination term was below a source term', $response );
	}

	/**
	 * Merging a parent and one of its children: all remaining children end up under the destination.
	 */
	public function test_merge_parent_and_child() {
		$this->merge( 'test_hier', 'Art', array( 'Music', 'Jazz' ) );

		$tree = $this->term_tree( 'test_hier' );
		$this->assertArrayNotHasKey( 'Music', $tree );
		$this->assertArrayNotHasKey( 'Jazz', $tree );
		foreach ( array( 'Rock', 'Bebop', 'Big Band', 'Painting', 'Sculpture' ) as $name ) {
			$this->assertSame( 'Art', $tree[ $name ], "$name should be under Art" );
		}
		$this->assertSame( 'Rock', $tree['Punk'] );
		$this->assertSame( array( 'Art' ), $this->post_terms( 'P1', 'test_hier' ), 'P1 moved from Jazz to Art' );
	}

	/**
	 * A destination below a source (not offered by the UI) is moved up first, so no loop is created.
	 */
	public function test_merge_destination_below_source() {
		$response = $this->merge( 'test_hier', 'Bebop', array( 'Music' ) );

		$tree = $this->term_tree( 'test_hier' );
		$this->assertArrayNotHasKey( 'Music', $tree );
		$this->assertSame( '', $tree['Bebop'], 'Bebop should move to the top level' );
		$this->assertSame( 'Bebop', $tree['Jazz'] );
		$this->assertSame( 'Bebop', $tree['Rock'] );
		$this->assertSame( 'Jazz', $tree['Big Band'] );
		$this->assertStringContainsString( 'moved up a level', $response );
	}

	/**
	 * Flat taxonomies have no children to move.
	 */
	public function test_flat_merge_has_no_children_notice() {
		$red      = $this->term( 'test_flat', 'red' )->term_id;
		$response = $this->merge_phase(
			'three',
			'test_flat',
			array(
				'destination' => $red,
				'term'        => array( $this->term( 'test_flat', 'blue' )->term_id ),
			)
		);

		$this->assertStringNotContainsString( 'Child terms', $response );
	}

	/**
	 * Flat taxonomy on posts and pages; the object term cache is cleared.
	 */
	public function test_merge_flat_across_object_types() {
		// Prime the object term cache for P2.
		$this->assertSame( array( 'green' ), wp_list_pluck( get_the_terms( $this->posts['P2'], 'test_flat' ), 'name' ) );

		$blue = $this->term( 'test_flat', 'blue' );
		$this->merge( 'test_flat', 'red', array( 'green', 'blue' ) );

		// No manual cache clear here: the merge must have done it.
		$this->assertSame( array( 'red' ), wp_list_pluck( get_the_terms( $this->posts['P2'], 'test_flat' ), 'name' ), 'Stale object term cache' );

		foreach ( array( 'P1', 'P2', 'P3', 'P4', 'P9', 'PG1' ) as $post ) {
			$this->assertSame( array( 'red' ), $this->post_terms( $post, 'test_flat' ), "$post should have only red" );
		}
		$this->assertSame( '', get_term_meta( $blue->term_id, 'staxo_test_meta', true ), 'Source meta not deleted' );

		// P1-P4 and the page PG1 are published; P9 is in the trash.
		$this->assertSame( 5, $this->term_count( 'test_flat', 'red' ) );
	}

	/**
	 * The destination count follows the taxonomy's count rules (test_count counts publish and draft).
	 */
	public function test_merge_respects_count_rules() {
		$this->assertSame( 1, $this->term_count( 'test_count', 'Rock' ), 'P5 draft counted, P6 pending not' );

		// P7 is private and P8 future: neither is counted.
		$this->merge( 'test_count', 'Rock', array( 'Painting', 'Sculpture' ) );

		$this->assertSame( array( 'Rock' ), $this->post_terms( 'P7', 'test_count' ) );
		$this->assertSame( array( 'Rock' ), $this->post_terms( 'P8', 'test_count' ) );
		$this->assertSame( 1, $this->term_count( 'test_count', 'Rock' ) );
	}

	/**
	 * Merge in a taxonomy with terms control; posts keep at least the minimum of one term.
	 */
	public function test_merge_with_terms_control() {
		$this->merge( 'test_cntl', 'green', array( 'red', 'blue' ) );

		foreach ( array( 'P1', 'P2', 'P3', 'P4' ) as $post ) {
			$this->assertSame( array( 'green' ), $this->post_terms( $post, 'test_cntl' ), "$post should have only green" );
		}
	}

	/**
	 * Change the terms control settings of test_cntl.
	 *
	 * @param array $settings STR settings to change (st_cc_*).
	 * @return void
	 */
	private function set_control( $settings ) {
		$options = get_option( OPTION_STAXO );
		foreach ( $settings as $key => $value ) {
			$options['taxonomies']['test_cntl'][ $key ] = $value;
		}
		update_option( OPTION_STAXO, $options );
	}

	/**
	 * Phase three for merging red and blue into green in test_cntl.
	 *
	 * @return string response.
	 */
	private function cntl_phase_three() {
		return $this->merge_phase(
			'three',
			'test_cntl',
			array(
				'destination' => $this->term( 'test_cntl', 'green' )->term_id,
				'term'        => array( $this->term( 'test_cntl', 'red' )->term_id, $this->term( 'test_cntl', 'blue' )->term_id ),
			)
		);
	}

	/**
	 * Phase three lists the posts the merge would take below the minimum, and offers the choice.
	 */
	public function test_phase_three_lists_posts_below_minimum() {
		$this->set_control( array( 'st_cc_min' => '2' ) );

		$response = $this->cntl_phase_three();

		// P3 goes from red + blue to green only. P1, P2 and P4 keep one term, so they are not listed.
		$this->assertStringContainsString( '1 post would have fewer than the minimum of 2 terms after this merge:', $response );
		$this->assertStringContainsString( '>P3</a>', $response );
		$this->assertStringContainsString( '2 terms before, 1 after', $response );
		foreach ( array( 'P1', 'P2', 'P4' ) as $post ) {
			$this->assertStringNotContainsString( '>' . $post . '</a>', $response, "$post should not be listed" );
		}
		$this->assertStringContainsString( 'name="below_min" id="below_min_stop" value="stop" checked', $response );
		$this->assertStringContainsString( 'name="below_min" id="below_min_proceed" value="proceed"', $response );
	}

	/**
	 * Phase three says so when no post falls below the minimum.
	 */
	public function test_phase_three_none_below_minimum() {
		$response = $this->cntl_phase_three();

		$this->assertStringContainsString( 'No posts will have fewer than the minimum number of terms after this merge.', $response );
		$this->assertStringNotContainsString( 'below_min', $response );
	}

	/**
	 * Terms control type 1 only applies to published and scheduled posts.
	 */
	public function test_below_minimum_type_one_ignores_drafts() {
		$this->set_control(
			array(
				'st_cc_type' => '1',
				'st_cc_min'  => '2',
			)
		);
		wp_update_post(
			array(
				'ID'          => $this->posts['P3'],
				'post_status' => 'draft',
			)
		);

		$response = $this->cntl_phase_three();

		$this->assertStringNotContainsString( '>P3</a>', $response );
		$this->assertStringContainsString( 'No posts will have fewer than the minimum', $response );
	}

	/**
	 * By default the merge is not done when posts would fall below the minimum.
	 */
	public function test_below_minimum_stops_by_default() {
		$this->set_control( array( 'st_cc_min' => '2' ) );

		$response = $this->merge( 'test_cntl', 'green', array( 'red', 'blue' ) );

		$this->assertStringContainsString( 'Merge not done: 1 post would have fewer than the minimum of 2 terms.', $response );
		$this->assertStringContainsString( '>P3</a>', $response );
		$this->assertStringContainsString( 'id="phase" value="five"', $response );
		$this->assertTrue( $this->term_exists_by_name( 'test_cntl', 'red' ), 'red should not be deleted' );
		$this->assertTrue( $this->term_exists_by_name( 'test_cntl', 'blue' ), 'blue should not be deleted' );
		$this->assertSame( array( 'blue', 'red' ), $this->post_terms( 'P3', 'test_cntl' ) );
	}

	/**
	 * With "Merge anyway" the merge goes ahead and lists the posts now below the minimum.
	 */
	public function test_below_minimum_merge_anyway() {
		$this->set_control( array( 'st_cc_min' => '2' ) );

		$response = $this->merge_phase(
			'four',
			'test_cntl',
			array(
				'destination' => $this->term( 'test_cntl', 'green' )->term_id,
				'sources'     => $this->term( 'test_cntl', 'red' )->term_id . ',' . $this->term( 'test_cntl', 'blue' )->term_id,
				'below_min'   => 'proceed',
			)
		);

		$this->assertFalse( $this->term_exists_by_name( 'test_cntl', 'red' ) );
		$this->assertFalse( $this->term_exists_by_name( 'test_cntl', 'blue' ) );
		$this->assertSame( array( 'green' ), $this->post_terms( 'P3', 'test_cntl' ) );
		$this->assertStringContainsString( '1 post now has fewer than the minimum of 2 terms:', $response );
		$this->assertStringContainsString( '>P3</a>', $response );
	}

	/**
	 * No valid sources: nothing changes.
	 */
	public function test_no_valid_sources() {
		$jazz = $this->term( 'test_hier', 'Jazz' )->term_id;
		$red  = $this->term( 'test_flat', 'red' )->term_id;

		$response = $this->merge_phase(
			'four',
			'test_hier',
			array(
				'destination' => $jazz,
				'sources'     => $jazz . ',' . $red . ',999999',
			)
		);

		$this->assertStringContainsString( 'No valid Source Terms were selected.', $response );
		$this->assertTrue( $this->term_exists_by_name( 'test_flat', 'red' ), 'Term of another taxonomy deleted' );
		$this->assertSame( array( 'green', 'red' ), $this->post_terms( 'P1', 'test_flat' ) );
	}

	/**
	 * An unknown destination is rejected.
	 */
	public function test_invalid_destination() {
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( 'Invalid destination term.' );
		$this->merge_phase(
			'four',
			'test_hier',
			array(
				'destination' => 999999,
				'sources'     => (string) $this->term( 'test_hier', 'Bebop' )->term_id,
			)
		);
	}

	/**
	 * A destination from another taxonomy is rejected.
	 */
	public function test_destination_from_other_taxonomy() {
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( 'Invalid destination term.' );
		$this->merge_phase(
			'four',
			'test_hier',
			array(
				'destination' => $this->term( 'test_flat', 'red' )->term_id,
				'sources'     => (string) $this->term( 'test_hier', 'Bebop' )->term_id,
			)
		);
	}

	/**
	 * An unknown taxonomy is rejected.
	 */
	public function test_invalid_taxonomy() {
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( 'Invalid taxonomy.' );
		$this->merge_phase( 'one', 'no_such_taxo' );
	}

	/**
	 * A subscriber cannot merge.
	 */
	public function test_subscriber_rejected() {
		$this->login( 'subscriber' );

		try {
			$this->merge( 'test_hier', 'Jazz', array( 'Bebop' ) );
			$this->fail( 'Expected WPAjaxDieStopException' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertStringContainsString( 'You do not have the necessary permissions.', $e->getMessage() );
		}
		$this->assertTrue( $this->term_exists_by_name( 'test_hier', 'Bebop' ), 'Merge ran for a subscriber' );
	}

	/**
	 * A bad nonce is rejected and nothing changes.
	 */
	public function test_bad_nonce_rejected() {
		try {
			$this->ajax(
				SimpleTaxonomyRefreshed_Admin_Merge::MERGE_SLUG,
				array(
					'phase'       => 'four',
					'taxonomy'    => 'test_hier',
					'destination' => $this->term( 'test_hier', 'Jazz' )->term_id,
					'sources'     => (string) $this->term( 'test_hier', 'Bebop' )->term_id,
				),
				'bad'
			);
			$this->fail( 'Expected WPAjaxDieStopException' );
		} catch ( WPAjaxDieStopException $e ) {
			unset( $e );
		}
		$this->assertTrue( $this->term_exists_by_name( 'test_hier', 'Bebop' ), 'Merge ran with a bad nonce' );
	}
}
