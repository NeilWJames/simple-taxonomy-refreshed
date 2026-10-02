<?php
/**
 * Tests for Terms Conversion (copying terms between taxonomies).
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Terms Conversion over AJAX; its output is fed into Terms Import as the screen does.
 *
 * @group convert
 */
class Test_STaxo_Convert extends STaxo_Ajax_Test_Case {

	/**
	 * Log in an administrator; terms only in test_hier and test_flat.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->login( 'administrator' );
		$this->import_config( 'staxo-config-suite.json' );
		$this->import_terms( 'test_hier', 'terms-hier-tab.txt', 'tab' );
		$this->import_terms( 'test_flat', 'terms-flat.txt' );
	}

	/**
	 * Run the conversion from one taxonomy to another.
	 *
	 * @param string $source      source taxonomy.
	 * @param string $destination destination taxonomy.
	 * @return string response.
	 */
	private function convert( $source, $destination ) {
		return $this->ajax(
			SimpleTaxonomyRefreshed_Admin_Conversion::CONVERT_SLUG,
			array(
				'name' => array( $source, $destination ),
				'copy' => array( 0 => '1' ),
				'oput' => array( 1 => '1' ),
			)
		);
	}

	/**
	 * The term list from the response's textarea, as the browser would submit it.
	 *
	 * @param string $response conversion response.
	 * @return string[] lines.
	 */
	private function term_lines( $response ) {
		$this->assertSame( 1, preg_match( '#<textarea name="import_content"[^>]*>(.*?)</textarea>#s', $response, $match ), 'No term list' );
		$text = html_entity_decode( $match[1], ENT_QUOTES | ENT_HTML5 );

		return explode( "\n", rtrim( $text, "\n" ) );
	}

	/**
	 * The hierarchy option offered in the response.
	 *
	 * @param string $response conversion response.
	 * @return string
	 */
	private function hierarchy( $response ) {
		$this->assertSame( 1, preg_match( '#<select name="hierarchy" id="hierarchy">\s*<option value="([a-z]+)"#', $response, $match ), 'No hierarchy option' );

		return $match[1];
	}

	/**
	 * Hierarchical to hierarchical: the tree is copied, levels as spaces.
	 */
	public function test_hierarchical_to_hierarchical() {
		$response = $this->convert( 'test_hier', 'test_count' );

		$this->assertSame( 'space', $this->hierarchy( $response ) );
		$this->assertSame(
			array( 'Art', ' Painting', ' Sculpture', 'Misc', 'Music', ' Jazz', '  Bebop', '  Big Band', ' Rock', '  Punk' ),
			$this->term_lines( $response )
		);
		$this->assertStringContainsString( '<option value="test_count" selected>', $response );

		$this->import_terms( 'test_count', implode( "\n", $this->term_lines( $response ) ), 'space' );
		$this->assertSame( $this->term_tree( 'test_hier' ), $this->term_tree( 'test_count' ) );
	}

	/**
	 * Hierarchical to flat: a flat list of all terms.
	 */
	public function test_hierarchical_to_flat() {
		$response = $this->convert( 'test_hier', 'test_cntl' );

		$this->assertSame( 'no', $this->hierarchy( $response ) );
		$lines = $this->term_lines( $response );
		$this->assertSame( array( 'Art', 'Bebop', 'Big Band', 'Jazz', 'Misc', 'Music', 'Painting', 'Punk', 'Rock', 'Sculpture' ), $lines );

		$this->import_terms( 'test_cntl', implode( "\n", $lines ) );
		$this->assertCount( 10, $this->term_tree( 'test_cntl' ) );
	}

	/**
	 * Flat to hierarchical: all terms at the top level (including the default term).
	 */
	public function test_flat_to_hierarchical() {
		$response = $this->convert( 'test_flat', 'test_count' );

		$this->assertSame( 'no', $this->hierarchy( $response ) );
		$this->assertSame( array( 'blue', 'cyan', 'green', 'red', 'Unsorted', 'yellow' ), $this->term_lines( $response ) );
	}

	/**
	 * Converting copies terms only; posts keep their terms and get none in the destination.
	 */
	public function test_posts_not_moved() {
		$post_id = self::factory()->post->create();
		wp_set_object_terms( $post_id, $this->term( 'test_hier', 'Jazz' )->term_id, 'test_hier' );

		$response = $this->convert( 'test_hier', 'test_count' );
		$this->import_terms( 'test_count', implode( "\n", $this->term_lines( $response ) ), 'space' );

		$this->assertSame( array( 'Jazz' ), $this->post_terms( $post_id, 'test_hier' ) );
		$this->assertSame( array(), $this->post_terms( $post_id, 'test_count' ) );
	}

	/**
	 * The list has one term per line: no HTML entity in place of the line breaks.
	 */
	public function test_one_term_per_line() {
		$response = $this->convert( 'test_flat', 'test_count' );

		$this->assertSame( 1, preg_match( '#<textarea name="import_content"[^>]*>(.*?)</textarea>#s', $response, $match ) );
		$this->assertStringNotContainsString( '&#013;', html_entity_decode( $match[1], ENT_QUOTES | ENT_HTML5 ) );
		$this->assertSame( 6, substr_count( $match[1], "\n" ) );
	}

	/**
	 * A name with an ampersand is escaped once in the page, shown as typed, and importing it gives the same name.
	 */
	public function test_ampersand_round_trip() {
		wp_insert_term( 'Rock & Roll', 'test_hier' );

		$response = $this->convert( 'test_hier', 'test_cntl' );
		$lines    = $this->term_lines( $response );
		$this->assertContains( 'Rock & Roll', $lines );

		// Escaped once in the HTML.
		$this->assertStringContainsString( 'Rock &amp; Roll', $response );
		$this->assertStringNotContainsString( 'Rock &amp;amp; Roll', $response );

		$this->import_terms( 'test_cntl', implode( "\n", $lines ) );
		$this->assertSame(
			get_term_by( 'name', 'Rock & Roll', 'test_hier' )->name,
			get_term_by( 'name', 'Rock & Roll', 'test_cntl' )->name
		);
	}

	/**
	 * An unknown taxonomy is refused.
	 */
	public function test_invalid_taxonomy() {
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( 'Invalid taxonomy.' );
		$this->convert( 'test_hier', 'no_such_taxo' );
	}

	/**
	 * The Terms Migrate page as the current user sees it.
	 *
	 * @return string
	 */
	private function migrate_page() {
		ob_start();
		SimpleTaxonomyRefreshed_Admin_Conversion::page_conversion();
		return ob_get_clean();
	}

	/**
	 * The Copy From or Copy To checkbox of a taxonomy on the Terms Migrate page.
	 *
	 * @param string $page     page output.
	 * @param string $group    "copy" (Copy From) or "oput" (Copy To).
	 * @param string $taxonomy taxonomy name.
	 * @return string the input tag.
	 */
	private function checkbox( $page, $group, $taxonomy ) {
		$found = preg_match( '#<input type="checkbox"[^>]*class="' . $group . '"[^>]*aria-labelledby="' . $taxonomy . '"[^>]*>#s', $page, $match );
		$this->assertSame( 1, $found, "No $group checkbox for $taxonomy" );

		return $match[0];
	}

	/**
	 * Taxonomies the user may not copy from (manage_terms) or to (edit_terms) are shown but cannot be selected.
	 */
	public function test_page_locks_taxonomies() {
		$this->register_locked_taxonomy();
		$this->grant_caps( 'manage_locked' );

		$page = $this->migrate_page();

		$this->assertStringNotContainsString( 'disabled', $this->checkbox( $page, 'copy', 'test_hier' ) );
		$this->assertStringNotContainsString( 'disabled', $this->checkbox( $page, 'oput', 'test_hier' ) );
		$this->assertStringNotContainsString( 'disabled', $this->checkbox( $page, 'copy', 'test_locked' ), 'manage_terms: can copy from' );
		$this->assertStringContainsString( 'data-locked="1"', $this->checkbox( $page, 'oput', 'test_locked' ), 'no edit_terms: cannot copy to' );
		$this->assertStringContainsString( "disabled='disabled'", $this->checkbox( $page, 'oput', 'test_locked' ) );
	}

	/**
	 * Without the taxonomy's manage_terms capability, it cannot be copied from.
	 */
	public function test_copy_from_needs_manage_terms() {
		$this->register_locked_taxonomy();
		$this->grant_caps( 'edit_locked' );

		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( 'You do not have the necessary permissions.' );
		$this->convert( 'test_locked', 'test_hier' );
	}

	/**
	 * Without the taxonomy's edit_terms capability, it cannot be copied to; with manage_terms it can be copied from.
	 */
	public function test_copy_to_needs_edit_terms() {
		$this->register_locked_taxonomy();
		$this->grant_caps( 'manage_locked' );

		$this->assertSame( array( 'Locked term' ), $this->term_lines( $this->convert( 'test_locked', 'test_hier' ) ) );

		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( 'You do not have the necessary permissions.' );
		$this->convert( 'test_hier', 'test_locked' );
	}

	/**
	 * An editor cannot convert.
	 */
	public function test_editor_refused() {
		$this->login( 'editor' );

		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( 'You do not have the necessary permissions.' );
		$this->convert( 'test_hier', 'test_count' );
	}
}
