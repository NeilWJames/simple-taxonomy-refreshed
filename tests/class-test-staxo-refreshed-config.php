<?php
/**
 * Tests for configuration import and taxonomy registration.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Configuration import tests, using the files in tests/files.
 *
 * @group config
 */
class Test_STaxo_Refreshed_Config extends STaxo_Test_Case {

	/**
	 * Name of the taxonomy defined in the single-taxonomy configuration files.
	 */
	const TAXO = 'test_hier';

	/**
	 * Log in an administrator.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->login( 'administrator' );
	}

	/**
	 * A valid file is stored in the option and reported as restored.
	 */
	public function test_import_saves_option() {
		$errors  = $this->import_config( 'staxo-config-test-hier.json', false );
		$options = get_option( OPTION_STAXO );

		$this->assertIsArray( $options, 'Option not saved' );
		$this->assertArrayHasKey( self::TAXO, $options['taxonomies'], 'Taxonomy missing from option' );
		$this->assertCount( 1, $errors, 'Expected one settings message' );
		$this->assertSame( 'updated', $errors[0]['type'], 'Import not reported as successful' );
	}

	/**
	 * An imported taxonomy is registered with its settings.
	 */
	public function test_imported_taxonomy_registered() {
		$this->import_config( 'staxo-config-test-hier.json' );

		$this->assertTrue( taxonomy_exists( self::TAXO ), 'Taxonomy not registered' );
		$this->assertTrue( is_taxonomy_hierarchical( self::TAXO ), 'Taxonomy not hierarchical' );

		$taxonomy = get_taxonomy( self::TAXO );
		$this->assertSame( 'Test Terms', $taxonomy->labels->name, 'Label not applied' );
		$this->assertContains( 'post', $taxonomy->object_type, 'Not attached to posts' );
		$this->assertTrue( $taxonomy->show_in_rest, 'Not shown in REST' );
	}

	/**
	 * The suite file registers all four test taxonomies with their settings.
	 */
	public function test_suite_taxonomies_registered() {
		$errors = $this->import_config( 'staxo-config-suite.json' );
		$this->assertSame( array( 'updated' ), wp_list_pluck( $errors, 'type' ), 'Import not reported as successful' );

		foreach ( self::$staxo_taxonomies as $name ) {
			$this->assertTrue( taxonomy_exists( $name ), "$name not registered" );
		}

		$this->assertTrue( is_taxonomy_hierarchical( 'test_hier' ) );
		$this->assertTrue( is_taxonomy_hierarchical( 'test_count' ) );
		$this->assertFalse( is_taxonomy_hierarchical( 'test_flat' ) );
		$this->assertFalse( is_taxonomy_hierarchical( 'test_cntl' ) );

		$flat = get_taxonomy( 'test_flat' );
		$this->assertEqualSets( array( 'post', 'page' ), $flat->object_type, 'test_flat object types' );
		$this->assertSame( 'flat-terms', $flat->rest_base, 'test_flat rest_base' );
		$this->assertSame( 'Unsorted', $flat->default_term['name'], 'test_flat default term' );
		$this->assertGreaterThan( 0, (int) get_option( 'default_term_test_flat' ), 'Default term not created' );

		// Terms control is cached per post type.
		$cntl = SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache();
		$this->assertArrayHasKey( 'test_cntl', $cntl['post'], 'Terms control not set up for test_cntl' );
		$this->assertArrayNotHasKey( 'test_hier', $cntl['post'], 'Unexpected terms control on test_hier' );
	}

	/**
	 * Importing a second file replaces the first; no taxonomy is left over.
	 */
	public function test_import_replaces_config() {
		$this->import_config( 'staxo-config-suite.json', false );
		$this->import_config( 'staxo-config-test-hier.json', false );

		$options = get_option( OPTION_STAXO );
		$this->assertSame( array( self::TAXO ), array_keys( $options['taxonomies'] ) );
	}

	/**
	 * A file without the header is rejected and the option is left alone.
	 */
	public function test_import_rejects_bad_file() {
		$errors = $this->import_config( 'staxo-config-bad.json', false );

		$this->assertFalse( get_option( OPTION_STAXO ), 'Option should not be written' );
		$this->assertCount( 1, $errors, 'Expected one settings message' );
		$this->assertSame( 'error', $errors[0]['type'], 'Bad file not reported as an error' );
	}

	/**
	 * A user without manage_options cannot import.
	 */
	public function test_import_needs_manage_options() {
		$this->login( 'editor' );

		$this->expectException( 'WPDieException' );
		$this->import_config( 'staxo-config-test-hier.json', false );
	}

	/**
	 * Callback fields are imported when the user may edit them.
	 */
	public function test_import_keeps_callbacks_when_allowed() {
		add_filter( 'staxo_can_edit_callbacks', '__return_true' );
		$this->import_config( 'staxo-config-test-callback.json', false );
		remove_filter( 'staxo_can_edit_callbacks', '__return_true' );

		$taxo = get_option( OPTION_STAXO )['taxonomies'][ self::TAXO ];
		$this->assertSame( '_update_generic_term_count', $taxo['update_count_callback'] );
		$this->assertSame( '_update_generic_term_count', $taxo['st_update_count_callback'] );
	}

	/**
	 * Callback fields are cleared on import when the user may not edit them.
	 */
	public function test_import_clears_callbacks_when_not_allowed() {
		add_filter( 'staxo_can_edit_callbacks', '__return_false' );
		$this->import_config( 'staxo-config-test-callback.json', false );
		remove_filter( 'staxo_can_edit_callbacks', '__return_false' );

		$taxo = get_option( OPTION_STAXO )['taxonomies'][ self::TAXO ];
		$this->assertSame( '', $taxo['update_count_callback'] );
		$this->assertSame( '', $taxo['st_update_count_callback'] );
	}
}
