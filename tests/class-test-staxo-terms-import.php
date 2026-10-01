<?php
/**
 * Tests for Terms Import.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Terms Import: flat and hierarchical lists, duplicates, rejections and skipped-line reporting.
 *
 * @group import
 */
class Test_STaxo_Terms_Import extends STaxo_Test_Case {

	/**
	 * Expected tree from terms-hier-tab.txt and terms-hier-space.txt (name => parent).
	 *
	 * @var array
	 */
	private static $tree = array(
		'Art'       => '',
		'Bebop'     => 'Jazz',
		'Big Band'  => 'Jazz',
		'Jazz'      => 'Music',
		'Misc'      => '',
		'Music'     => '',
		'Painting'  => 'Art',
		'Punk'      => 'Rock',
		'Rock'      => 'Music',
		'Sculpture' => 'Art',
	);

	/**
	 * Log in an administrator and register the test taxonomies (no terms).
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->login( 'administrator' );
		$this->import_config( 'staxo-config-suite.json' );
	}

	/**
	 * A flat list creates one term per line.
	 */
	public function test_flat_list() {
		$errors = $this->import_terms( 'test_flat', 'terms-flat.txt' );

		$this->assertSame(
			array( 'Done, 5 term lines processed successfully !', ' 5 new terms were created.' ),
			$this->messages( $errors, 'updated' )
		);
		$this->assertEmpty( $this->messages( $errors, 'warning' ), 'Nothing should be skipped' );

		$tree = $this->term_tree( 'test_flat' );
		unset( $tree['Unsorted'] ); // Default term, created on registration.
		$this->assertSame( array( 'blue', 'cyan', 'green', 'red', 'yellow' ), array_keys( $tree ) );
	}

	/**
	 * Tab indentation builds the hierarchy.
	 */
	public function test_tab_hierarchy() {
		$errors = $this->import_terms( 'test_hier', 'terms-hier-tab.txt', 'tab' );

		$this->assertSame( self::$tree, $this->term_tree( 'test_hier' ) );
		$this->assertContains( ' 10 new terms were created.', $this->messages( $errors, 'updated' ) );
	}

	/**
	 * Space indentation builds the same hierarchy.
	 */
	public function test_space_hierarchy() {
		$this->import_terms( 'test_hier', 'terms-hier-space.txt', 'space' );

		$this->assertSame( self::$tree, $this->term_tree( 'test_hier' ) );
	}

	/**
	 * Importing the same list again creates nothing new.
	 */
	public function test_reimport_creates_nothing() {
		$this->import_terms( 'test_hier', 'terms-hier-tab.txt', 'tab' );
		$errors = $this->import_terms( 'test_hier', 'terms-hier-tab.txt', 'tab' );

		$this->assertSame( array( 'Done, 10 term lines processed successfully !' ), $this->messages( $errors, 'updated' ) );
		$this->assertSame( self::$tree, $this->term_tree( 'test_hier' ) );
	}

	/**
	 * The same name under two parents gives two terms.
	 */
	public function test_same_name_different_parents() {
		$this->import_terms( 'test_hier', "Music\n\tOther\nArt\n\tOther", 'tab' );

		$others = get_terms(
			array(
				'taxonomy'   => 'test_hier',
				'name'       => 'Other',
				'hide_empty' => false,
			)
		);
		$this->assertCount( 2, $others );

		$parents = array();
		foreach ( $others as $other ) {
			$parents[] = get_term( $other->parent, 'test_hier' )->name;
		}
		sort( $parents );
		$this->assertSame( array( 'Art', 'Music' ), $parents );
	}

	/**
	 * Blank and whitespace-only lines are ignored and not reported.
	 */
	public function test_blank_lines_ignored() {
		$errors = $this->import_terms( 'test_cntl', "red\n\n   \r\nblue\n" );

		$this->assertContains( 'Done, 2 term lines processed successfully !', $this->messages( $errors, 'updated' ) );
		$this->assertEmpty( $this->messages( $errors, 'warning' ) );
		$this->assertSame( array( 'blue', 'red' ), array_keys( $this->term_tree( 'test_cntl' ) ) );
	}

	/**
	 * Blank lines inside a hierarchy do not break it.
	 */
	public function test_blank_lines_in_hierarchy() {
		$this->import_terms( 'test_hier', "Music\n\n\tJazz\n\n\t\tBebop", 'tab' );

		$this->assertSame(
			array(
				'Bebop' => 'Jazz',
				'Jazz'  => 'Music',
				'Music' => '',
			),
			$this->term_tree( 'test_hier' )
		);
	}

	/**
	 * Hierarchical data cannot be loaded into a flat taxonomy.
	 */
	public function test_hierarchy_into_flat_rejected() {
		$errors = $this->import_terms( 'test_cntl', 'terms-hier-tab.txt', 'tab' );

		$this->assertCount( 1, $this->messages( $errors, 'error' ) );
		$this->assertEmpty( $this->term_tree( 'test_cntl' ) );
	}

	/**
	 * An indented first line is created at the top level.
	 */
	public function test_indented_first_line_is_top_level() {
		$this->import_terms( 'test_hier', "\tFirst\nMusic\n\tJazz", 'tab' );

		$this->assertSame(
			array(
				'First' => '',
				'Jazz'  => 'Music',
				'Music' => '',
			),
			$this->term_tree( 'test_hier' )
		);
	}

	/**
	 * A line that skips levels attaches to the nearest term above it.
	 */
	public function test_skipped_level_attaches_to_nearest_ancestor() {
		$this->import_terms( 'test_hier', "Music\n\tJazz\n\t\tBebop\nArt\n\t\t\tDeep\n\tPainting", 'tab' );

		$tree = $this->term_tree( 'test_hier' );
		$this->assertSame( 'Art', $tree['Deep'], 'Deep should be under Art, not under Bebop from the previous branch' );
		$this->assertSame( 'Art', $tree['Painting'] );
	}

	/**
	 * Skipped lines are reported with their line number, and children of a skipped term are skipped too.
	 */
	public function test_skipped_lines_reported() {
		// Make WordPress refuse one term.
		$refuse = static function ( $term ) {
			return ( 'Clash' === $term ? new WP_Error( 'staxo_test', 'Refused by test.' ) : $term );
		};
		add_filter( 'pre_insert_term', $refuse );

		$errors = $this->import_terms( 'test_hier', "Music\nClash\n\tChild\n\t\tGrandchild\n<b></b>\nArt\n\tPainting", 'tab' );

		$warnings = $this->messages( $errors, 'warning' );
		$this->assertCount( 1, $warnings, 'Expected one warning notice' );
		$warning = $warnings[0];
		$this->assertStringContainsString( '4 term lines were skipped:', $warning );
		$this->assertStringContainsString( 'Line 2: &quot;Clash&quot; - Refused by test.', $warning );
		$this->assertStringContainsString( 'Line 3: &quot;Child&quot; - Its parent term on line 2 was skipped.', $warning );
		$this->assertStringContainsString( 'Line 4: &quot;Grandchild&quot; - Its parent term on line 3 was skipped.', $warning );
		$this->assertStringContainsString( 'Line 5:', $warning );
		$this->assertStringContainsString( 'The name is empty once invalid characters are removed.', $warning );

		// The skipped term name is escaped in the notice.
		$this->assertStringNotContainsString( '<b>', $warning );

		$this->assertSame(
			array(
				'Art'      => '',
				'Music'    => '',
				'Painting' => 'Art',
			),
			$this->term_tree( 'test_hier' )
		);
		$this->assertContains( 'Done, 3 term lines processed successfully !', $this->messages( $errors, 'updated' ) );
	}

	/**
	 * A term named "0" is imported.
	 */
	public function test_zero_is_a_valid_name() {
		$this->import_terms( 'test_cntl', "0\nred" );

		$this->assertSame( array( '0', 'red' ), array_map( 'strval', array_keys( $this->term_tree( 'test_cntl' ) ) ) );
	}

	/**
	 * Terms cannot be imported into a taxonomy that does not exist.
	 */
	public function test_unknown_taxonomy_rejected() {
		$this->expectException( 'WPDieException' );
		$this->import_terms( 'no_such_taxo', 'terms-flat.txt' );
	}

	/**
	 * A subscriber cannot import terms.
	 */
	public function test_subscriber_rejected() {
		$this->login( 'subscriber' );

		$this->expectException( 'WPDieException' );
		$this->import_terms( 'test_flat', 'terms-flat.txt' );
	}

	/**
	 * A bad nonce is rejected and nothing is created.
	 */
	public function test_bad_nonce_rejected() {
		$_POST[ SimpleTaxonomyRefreshed_Admin_Import::IMPORT_SLUG ] = '1';
		$_POST['taxonomy']       = 'test_cntl';
		$_POST['hierarchy']      = 'no';
		$_POST['import_content'] = "red\nblue";
		$_REQUEST['_wpnonce']    = 'bad';

		try {
			SimpleTaxonomyRefreshed_Admin_Import::check_importation();
			$this->fail( 'Expected WPDieException' );
		} catch ( WPDieException $e ) {
			unset( $e );
		}
		$this->assertEmpty( $this->term_tree( 'test_cntl' ) );
	}
}
