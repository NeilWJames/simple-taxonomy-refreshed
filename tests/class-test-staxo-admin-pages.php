<?php
/**
 * Tests for the admin screens: singletons, menu pages, help tabs, page output and config export.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * The admin screens as WordPress loads them (the plugin creates them only in wp-admin).
 *
 * @group pages
 */
class Test_STaxo_Admin_Pages extends STaxo_Test_Case {

	/**
	 * Admin screen classes and their page slugs.
	 *
	 * @return string[] class => slug.
	 */
	private static function screens() {
		return array(
			'SimpleTaxonomyRefreshed_Admin_Config'     => SimpleTaxonomyRefreshed_Admin_Config::CONFIG_SLUG,
			'SimpleTaxonomyRefreshed_Admin_Conversion' => SimpleTaxonomyRefreshed_Admin_Conversion::CONVERT_SLUG,
			'SimpleTaxonomyRefreshed_Admin_Import'     => SimpleTaxonomyRefreshed_Admin_Import::IMPORT_SLUG,
			'SimpleTaxonomyRefreshed_Admin_Merge'      => SimpleTaxonomyRefreshed_Admin_Merge::MERGE_SLUG,
			'SimpleTaxonomyRefreshed_Admin_Order'      => SimpleTaxonomyRefreshed_Admin_Order::ORDER_SLUG,
			'SimpleTaxonomyRefreshed_Admin_Rename'     => SimpleTaxonomyRefreshed_Admin_Rename::RENAME_SLUG,
		);
	}

	/**
	 * Log in an administrator, register the suite taxonomies and load the admin menu and screen functions.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';

		$this->login( 'administrator' );
		$this->import_config( 'staxo-config-suite.json' );
	}

	/**
	 * Clear the admin menu and the current screen.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $submenu;
		unset( $submenu[ SimpleTaxonomyRefreshed_Admin::ADMIN_SLUG ] );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- no screen is current after each test.
		$GLOBALS['current_screen'] = null;
		parent::tear_down();
	}

	/**
	 * Capture the output of a callable.
	 *
	 * @param callable $callback function to run.
	 * @return string
	 */
	private function capture( $callback ) {
		$level = ob_get_level();
		ob_start();
		try {
			call_user_func( $callback );
			return ob_get_clean();
		} finally {
			// Also close the buffer when the callable throws (wp_die()).
			while ( ob_get_level() > $level ) {
				ob_end_clean();
			}
		}
	}

	/**
	 * Import a configuration file from any path, as the Import button does.
	 *
	 * @param string $path file path.
	 * @return void
	 */
	private function import_file( $path ) {
		$_POST[ SimpleTaxonomyRefreshed_Admin_Config::IMP_FILE_SLUG ] = '1';
		$_REQUEST['_wpnonce']  = wp_create_nonce( SimpleTaxonomyRefreshed_Admin_Config::IMP_FILE_SLUG );
		$_FILES['config_file'] = array(
			'error'    => 0,
			'tmp_name' => $path,
		);
		SimpleTaxonomyRefreshed_Admin_Config::check_importexport();
	}

	/**
	 * Each screen is a singleton whose constructor hooks its menu page.
	 */
	public function test_singletons() {
		foreach ( array_keys( self::screens() ) as $class ) {
			// Clear the instance so the constructor runs (PHP 8.1+ allows access without setAccessible()).
			$property = new ReflectionProperty( $class, 'instance' );
			$property->setValue( null, null );

			$instance = $class::get_instance();
			$this->assertInstanceOf( $class, $instance );
			$this->assertSame( $instance, $class::get_instance(), "$class: one instance" );
			$this->assertSame( 20, has_action( 'admin_menu', array( $class, 'add_menu' ) ), "$class: menu hooked" );
		}
	}

	/**
	 * Each screen adds its page under the Taxonomies menu, with its help tabs on the page's load hook.
	 */
	public function test_menu_pages() {
		global $submenu;

		foreach ( self::screens() as $class => $slug ) {
			$class::add_menu();

			$this->assertContains( $slug, wp_list_pluck( $submenu[ SimpleTaxonomyRefreshed_Admin::ADMIN_SLUG ], 2 ), "$class: page added" );
			$this->assertSame( 10, has_action( 'load-taxonomies_page_' . $slug, array( $class, 'add_help_tab' ) ), "$class: help hooked" );
		}
	}

	/**
	 * Each screen adds its help tabs.
	 */
	public function test_help_tabs() {
		foreach ( self::screens() as $class => $slug ) {
			set_current_screen( 'taxonomies_page_' . $slug );
			$class::add_help_tab();

			$this->assertNotEmpty( get_current_screen()->get_help_tabs(), "$class: no help tabs" );
		}
	}

	/**
	 * Configuration and List Order pages load the sortable script and the plugin's admin script and style.
	 */
	public function test_sortable_scripts() {
		$saved = ( isset( $GLOBALS['stra'] ) ? $GLOBALS['stra'] : null );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the plugin keeps its admin instance in $stra.
		$GLOBALS['stra'] = SimpleTaxonomyRefreshed_Admin::get_instance();
		try {
			foreach ( array( 'SimpleTaxonomyRefreshed_Admin_Config', 'SimpleTaxonomyRefreshed_Admin_Order' ) as $class ) {
				wp_dequeue_script( 'jquery-ui-sortable' );
				$class::add_js_libs();
				$this->assertTrue( wp_script_is( 'jquery-ui-sortable', 'enqueued' ), "$class: sortable" );
				$this->assertTrue( wp_script_is( 'staxo_admin', 'enqueued' ), "$class: admin script" );
				$this->assertTrue( wp_style_is( 'staxo-admin-style', 'enqueued' ), "$class: admin style" );
			}
		} finally {
			// Restore the plugin's admin instance and start later tests with no scripts or styles queued.
			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
			$GLOBALS['stra']       = $saved;
			$GLOBALS['wp_scripts'] = null;
			$GLOBALS['wp_styles']  = null;
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Configuration page: nothing to export, one taxonomy (no reordering), several (sortable list).
	 */
	public function test_config_page() {
		$page = array( 'SimpleTaxonomyRefreshed_Admin_Config', 'page_config' );

		$output = $this->capture( $page );
		$this->assertStringContainsString( 'You can reorder the custom taxonomies defined on export.', $output );
		foreach ( self::$staxo_taxonomies as $taxonomy ) {
			$this->assertStringContainsString( '<li class="sort-li" tabindex="0">' . $taxonomy . '</li>', $output );
		}
		$this->assertStringContainsString( 'name="taxo_list_arr"', $output );
		$this->assertStringContainsString( 'name="' . SimpleTaxonomyRefreshed_Admin_Config::IMP_FILE_SLUG . '"', $output );

		$this->import_config( 'staxo-config-test-hier.json' );
		$this->assertStringContainsString( 'Only one custom taxonomy defined. No reorder possible on export.', $this->capture( $page ) );

		update_option( OPTION_STAXO, array( 'list_order' => array() ) );
		$this->assertStringContainsString( 'No custom taxonomies to export.', $this->capture( $page ) );

		delete_option( OPTION_STAXO );
		$output = $this->capture( $page );
		$this->assertStringContainsString( 'No configuration exists to export.', $output );
		$this->assertStringNotContainsString( SimpleTaxonomyRefreshed_Admin_Config::EXP_FILE_SLUG, $output );
	}

	/**
	 * Exporting and importing the configuration gives back the same settings (plan A4).
	 */
	public function test_config_export_round_trip() {
		$before = get_option( OPTION_STAXO );
		$export = SimpleTaxonomyRefreshed_Admin_Config::build_config_export( $before );
		$this->assertStringStartsWith( 'SIMPLETAXONOMYREFRESHED{', $export );

		$path = wp_tempnam( 'staxo-export' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- temporary test file.
		file_put_contents( $path, $export );
		delete_option( OPTION_STAXO );

		$this->clear_settings_errors();
		$this->import_file( $path );
		wp_delete_file( $path );

		$this->assertContains( 'OK. Configuration is restored.', $this->messages( get_settings_errors( 'simple-taxonomy-refreshed' ), 'updated' ) );
		$this->assertEquals( $before, get_option( OPTION_STAXO ) );
	}

	/**
	 * The export follows the order chosen on the screen; names not stored are ignored and unlisted taxonomies are kept.
	 */
	public function test_config_export_order() {
		$options = get_option( OPTION_STAXO );

		$export = SimpleTaxonomyRefreshed_Admin_Config::build_config_export( $options, array( 'test_count', 'no_such_taxo', array( 'x' ), 'test_hier' ) );
		$data   = json_decode( substr( $export, strlen( 'SIMPLETAXONOMYREFRESHED' ) ), true );

		$this->assertSame( array( 'test_count', 'test_hier', 'test_flat', 'test_cntl' ), array_keys( $data['taxonomies'] ) );
		$this->assertSame( $options['taxonomies']['test_count'], $data['taxonomies']['test_count'] );

		$this->assertSame( 'SIMPLETAXONOMYREFRESHED[]', SimpleTaxonomyRefreshed_Admin_Config::build_config_export( false ) );
		$this->assertSame( SimpleTaxonomyRefreshed_Admin_Config::build_config_export( $options ), SimpleTaxonomyRefreshed_Admin_Config::build_config_export( $options, 'not a list' ) );
	}

	/**
	 * Rename page lists each taxonomy with the script that shows its current values; with none it stops.
	 */
	public function test_rename_page() {
		$page   = array( 'SimpleTaxonomyRefreshed_Admin_Rename', 'page_rename' );
		$output = $this->capture( $page );

		foreach ( self::$staxo_taxonomies as $taxonomy ) {
			$this->assertMatchesRegularExpression( '#<input type="radio"[^>]*id="' . $taxonomy . '"#', $output );
		}
		$this->assertStringContainsString( 'function c4() {', $output );
		$this->assertStringContainsString( 'document.getElementById("curr_slug").textContent = "test_hier";', $output );
		$this->assertStringContainsString( 'name="new_slug"', $output );

		delete_option( OPTION_STAXO );
		try {
			$this->capture( $page );
			$this->fail( 'Expected WPDieException with no taxonomies' );
		} catch ( WPDieException $e ) {
			$this->assertStringContainsString( 'do not have the necessary permissions to change any taxonomies', $e->getMessage() );
		}
	}

	/**
	 * Terms Import page lists the taxonomies, keeps the posted choice and shows the hierarchy options.
	 */
	public function test_import_page() {
		$_POST['taxonomy']  = 'test_flat';
		$_POST['hierarchy'] = 'tab';

		$output = $this->capture( array( 'SimpleTaxonomyRefreshed_Admin_Import', 'page_importation' ) );

		$this->assertMatchesRegularExpression( "#<option value=\"test_flat\"\s+selected='selected'#", $output );
		$this->assertMatchesRegularExpression( "#<option value=\"tab\"\s+selected='selected'#", $output );
		$this->assertStringContainsString( '<textarea name="import_content"', $output );
		$this->assertStringContainsString( 'name="' . SimpleTaxonomyRefreshed_Admin_Import::IMPORT_SLUG . '"', $output );
	}

	/**
	 * Taxonomy List Order page shows the post types with more than one admin-column taxonomy.
	 */
	public function test_order_page() {
		$output = $this->capture( array( 'SimpleTaxonomyRefreshed_Admin_Order', 'page_admin_order' ) );

		$this->assertStringContainsString( 'Taxonomy List Ordering', $output );
		$this->assertStringContainsString( 'test_hier', $output );
		$this->assertStringContainsString( 'name="post_arr"', $output );
	}
}
