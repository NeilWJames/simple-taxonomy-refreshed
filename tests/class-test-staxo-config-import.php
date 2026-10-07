<?php
/**
 * Tests for checking and sanitising imported configurations (review finding M1).
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Configuration import: each taxonomy is checked and sanitised as the admin form does.
 *
 * A taxonomy with a name that is not valid, a name used by WordPress or another plugin, or a
 * setting with a value the form would not store, is skipped and listed. Settings the plugin does
 * not use are dropped and counted. The other taxonomies are imported.
 *
 * @group config
 */
class Test_STaxo_Config_Import extends STaxo_Test_Case {

	/**
	 * Settings of the suite's test_hier, as exported.
	 *
	 * @var array
	 */
	private static $hier;

	/**
	 * Log in an administrator and read test_hier from the suite file.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->login( 'administrator' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local fixture file.
		$suite      = json_decode( substr( file_get_contents( __DIR__ . '/files/staxo-config-suite.json' ), strlen( 'SIMPLETAXONOMYREFRESHED' ) ), true );
		self::$hier = $suite['taxonomies']['test_hier'];
	}

	/**
	 * Import a configuration given as an array, as the Import button does.
	 *
	 * @param array $config configuration.
	 * @return array settings errors raised.
	 */
	private function import( $config ) {
		$path = wp_tempnam( 'staxo-import' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- temporary test file.
		file_put_contents( $path, 'SIMPLETAXONOMYREFRESHED' . wp_json_encode( $config ) );

		$this->clear_settings_errors();
		$_POST[ SimpleTaxonomyRefreshed_Admin_Config::IMP_FILE_SLUG ] = '1';
		$_REQUEST['_wpnonce']  = wp_create_nonce( SimpleTaxonomyRefreshed_Admin_Config::IMP_FILE_SLUG );
		$_FILES['config_file'] = array(
			'error'    => 0,
			'tmp_name' => $path,
		);
		SimpleTaxonomyRefreshed_Admin_Config::check_importexport();
		wp_delete_file( $path );

		return get_settings_errors( 'simple-taxonomy-refreshed' );
	}

	/**
	 * A copy of test_hier under another name, with settings changed.
	 *
	 * @param string $name     taxonomy name.
	 * @param array  $settings settings to add or replace.
	 * @return array
	 */
	private function taxonomy( $name, $settings = array() ) {
		return array_merge( self::$hier, array( 'name' => $name ), $settings );
	}

	/**
	 * The message of a settings error with the given code.
	 *
	 * @param array  $errors settings errors.
	 * @param string $code   error code.
	 * @return string|null
	 */
	private function notice( $errors, $code ) {
		foreach ( $errors as $error ) {
			if ( $code === $error['code'] ) {
				return $error['message'];
			}
		}
		return null;
	}

	/**
	 * Files written by the plugin import unchanged, with no warnings.
	 */
	public function test_valid_files_unchanged() {
		foreach ( array( 'staxo-config-suite.json', 'staxo-config-e2e-notice.json', 'staxo-config-e2e-radio-notify.json' ) as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local fixture file.
			$config = json_decode( substr( file_get_contents( __DIR__ . '/files/' . $file ), strlen( 'SIMPLETAXONOMYREFRESHED' ) ), true );
			$errors = $this->import( $config );

			$this->assertSame( array( 'updated' ), wp_list_pluck( $errors, 'type' ), "$file: only the success message" );
			$stored = get_option( OPTION_STAXO );
			foreach ( $config['taxonomies'] as $name => $settings ) {
				foreach ( $settings as $key => $value ) {
					$this->assertEquals( $value, $stored['taxonomies'][ $name ][ $key ], "$file: $name $key" );
				}
			}
		}
	}

	/**
	 * Taxonomies with a name that cannot be used are skipped and listed; the others are imported.
	 */
	public function test_invalid_names_skipped() {
		$errors = $this->import(
			array(
				'taxonomies' => array(
					'test_hier'                           => $this->taxonomy( 'test_hier' ),
					'Test_Upper'                          => $this->taxonomy( 'Test_Upper' ),
					'test_name_much_longer_than_32_chars' => $this->taxonomy( 'test_name_much_longer_than_32_chars' ),
					'test_other'                          => $this->taxonomy( 'test_different' ),
					'category'                            => $this->taxonomy( 'category' ),
					'test_list'                           => 'not a list',
				),
			)
		);

		$this->assertSame( array( 'test_hier' ), array_keys( get_option( OPTION_STAXO )['taxonomies'] ) );
		$this->assertContains( 'OK. Configuration is restored.', $this->messages( $errors, 'updated' ) );
		$skipped = $this->notice( $errors, 'config_skipped' );
		$this->assertStringContainsString( 'These taxonomies in the file were not imported:', $skipped );
		$this->assertStringContainsString( '&quot;Test_Upper&quot;: the name may only contain lowercase letters, numbers, hyphens and underscores.', $skipped );
		$this->assertStringContainsString( '&quot;test_name_much_longer_than_32_chars&quot;: the name must have 1 to 32 characters.', $skipped );
		$this->assertStringContainsString( '&quot;test_other&quot;: the name in its settings is different.', $skipped );
		$this->assertStringContainsString( '&quot;category&quot;: WordPress or another plugin already has a taxonomy with this name.', $skipped );
		$this->assertStringContainsString( '&quot;test_list&quot;: its settings are not a list.', $skipped );
	}

	/**
	 * A setting the plugin uses with a value the form would not store skips the whole taxonomy.
	 */
	public function test_invalid_values_skip_the_taxonomy() {
		$bad    = array(
			'test_label'  => array( 'labels' => array_merge( self::$hier['labels'], array( 'name' => '<script>alert(1)</script>Genres' ) ) ),
			'test_before' => array( 'st_before' => '<script>alert(1)</script>Genres:' ),
			'test_type'   => array( 'st_cc_type' => '7' ),
			'test_hard'   => array( 'st_cc_hard' => 'yes' ),
			'test_min'    => array( 'st_cc_min' => '-1' ),
			'test_mask'   => array( 'st_ep_mask' => 'all' ),
			'test_types'  => array( 'objects' => array( array( 'post' ) ) ),
			'test_slug'   => array( 'st_slug' => "two\nlines" ),
		);
		$config = array( 'taxonomies' => array( 'test_hier' => $this->taxonomy( 'test_hier' ) ) );
		foreach ( $bad as $name => $settings ) {
			$config['taxonomies'][ $name ] = $this->taxonomy( $name, $settings );
		}
		$errors = $this->import( $config );

		$this->assertSame( array( 'test_hier' ), array_keys( get_option( OPTION_STAXO )['taxonomies'] ) );
		$skipped = $this->notice( $errors, 'config_skipped' );
		$fields  = array(
			'test_label'  => 'labels',
			'test_before' => 'st_before',
			'test_type'   => 'st_cc_type',
			'test_hard'   => 'st_cc_hard',
			'test_min'    => 'st_cc_min',
			'test_mask'   => 'st_ep_mask',
			'test_types'  => 'objects',
			'test_slug'   => 'st_slug',
		);
		foreach ( $fields as $name => $field ) {
			$this->assertStringContainsString( '&quot;' . $name . '&quot;: the setting &quot;' . $field . '&quot; is not valid.', $skipped, $name );
		}
		$this->assertStringNotContainsString( '<script', (string) wp_json_encode( get_option( OPTION_STAXO ) ) );
	}

	/**
	 * Settings the plugin does not use are dropped and counted; the taxonomy is imported.
	 */
	public function test_unknown_settings_ignored() {
		$errors = $this->import(
			array(
				'taxonomies'    => array(
					'test_hier' => $this->taxonomy(
						'test_hier',
						array(
							'unknown_setting' => 'x',
							'other'           => array( 1, 2 ),
						)
					),
				),
				'unknown_group' => array(),
			)
		);

		$taxo = get_option( OPTION_STAXO )['taxonomies']['test_hier'];
		$this->assertArrayNotHasKey( 'unknown_setting', $taxo );
		$this->assertArrayNotHasKey( 'other', $taxo );
		$this->assertArrayNotHasKey( 'unknown_group', get_option( OPTION_STAXO ) );
		$this->assertSame( '3 settings in the file are not used by the plugin and have been ignored.', $this->notice( $errors, 'config_ignored' ) );
		$this->assertNull( $this->notice( $errors, 'config_skipped' ) );
	}

	/**
	 * Missing settings take their defaults; empty choices from older versions are accepted.
	 */
	public function test_missing_settings_defaults() {
		$errors = $this->import(
			array(
				'taxonomies' => array(
					'test_min' => array(
						'name'       => 'test_min',
						'labels'     => array( 'name' => 'Minimal' ),
						'objects'    => array( 'post' ),
						'st_cc_type' => '',
					),
				),
			)
		);

		$this->assertSame( array( 'updated' ), wp_list_pluck( $errors, 'type' ) );
		$taxo = get_option( OPTION_STAXO )['taxonomies']['test_min'];
		$this->assertSame( ', ', $taxo['st_sep'] );
		$this->assertSame( 'none', $taxo['auto'] );
		$this->assertSame( 0, $taxo['st_cb_override'] );
		$this->assertSame( '', $taxo['st_cc_type'] );
	}

	/**
	 * External taxonomies keep only the settings for integrating them; WordPress's own may be used.
	 */
	public function test_externals() {
		$errors = $this->import(
			array(
				'externals' => array(
					'category' => array(
						'name'       => 'category',
						'st_cc_type' => '2',
						'st_cc_hard' => '1',
						'st_cc_umin' => '1',
						'st_cc_min'  => '1',
						'labels'     => array( 'name' => 'Not stored' ),
						'public'     => '1',
					),
					'post_tag' => array(
						'st_cb_type' => '9',
					),
				),
			)
		);

		$externals = get_option( OPTION_STAXO )['externals'];
		$this->assertSame( array( 'category' ), array_keys( $externals ) );
		$this->assertSame( '2', $externals['category']['st_cc_type'] );
		$this->assertSame( '1', $externals['category']['st_cc_min'] );
		$this->assertSame( '0', $externals['category']['st_cc_max'], 'Missing settings take their defaults' );
		$this->assertArrayNotHasKey( 'labels', $externals['category'] );
		$this->assertArrayNotHasKey( 'public', $externals['category'] );
		$this->assertSame( '2 settings in the file are not used by the plugin and have been ignored.', $this->notice( $errors, 'config_ignored' ) );
		$this->assertStringContainsString( '&quot;post_tag&quot;: the setting &quot;st_cb_type&quot; is not valid.', $this->notice( $errors, 'config_skipped' ) );
	}

	/**
	 * The list ordering keeps only names; other values are dropped and counted.
	 */
	public function test_list_order() {
		$errors = $this->import(
			array(
				'taxonomies' => array( 'test_hier' => $this->taxonomy( 'test_hier' ) ),
				'list_order' => array(
					'post' => array( 'test_hier', array( 'x' ), 'category' ),
					'page' => array( 'test_hier', 'category' ),
				),
			)
		);

		$this->assertSame(
			array(
				'post' => array( 'test_hier', 'category' ),
				'page' => array( 'test_hier', 'category' ),
			),
			get_option( OPTION_STAXO )['list_order']
		);
		$this->assertSame( '1 setting in the file is not used by the plugin and has been ignored.', $this->notice( $errors, 'config_ignored' ) );
	}

	/**
	 * A taxonomy whose REST name clashes with a field of posts or another taxonomy is skipped.
	 */
	public function test_rest_name_conflict_skipped() {
		$errors = $this->import(
			array(
				'taxonomies' => array(
					'test_hier'   => $this->taxonomy( 'test_hier' ),
					'format'      => $this->taxonomy( 'format' ),
					'test_status' => $this->taxonomy( 'test_status', array( 'rest_base' => 'status' ) ),
					'test_tags'   => $this->taxonomy( 'test_tags', array( 'rest_base' => 'tags' ) ),
					'test_kinds'  => $this->taxonomy( 'test_kinds', array( 'rest_base' => 'kinds' ) ),
				),
			)
		);

		$this->assertSame( array( 'test_hier', 'test_kinds' ), array_keys( get_option( OPTION_STAXO )['taxonomies'] ) );
		$skipped = $this->notice( $errors, 'config_skipped' );
		$clashes = array(
			'format'      => 'format',
			'test_status' => 'status',
			'test_tags'   => 'tags',
		);
		foreach ( $clashes as $name => $rest_name ) {
			$this->assertStringContainsString( '&quot;' . $name . '&quot;: its REST name &quot;' . $rest_name . '&quot; is already used', $skipped, $name );
		}
	}

	/**
	 * With nothing valid in the file, the current configuration is kept.
	 */
	public function test_nothing_valid_keeps_configuration() {
		$this->import_config( 'staxo-config-suite.json', false );
		$before = get_option( OPTION_STAXO );

		$errors = $this->import( array( 'taxonomies' => array( 'post_tag' => $this->taxonomy( 'post_tag' ) ) ) );

		$this->assertSame( $before, get_option( OPTION_STAXO ) );
		$this->assertSame( array( 'The config file holds no valid settings, so nothing has been imported.' ), $this->messages( $errors, 'error' ) );
		$this->assertStringContainsString( '&quot;post_tag&quot;', $this->notice( $errors, 'config_skipped' ) );
	}

	/**
	 * Taxonomies this plugin has registered can be imported again, even after the settings are removed.
	 */
	public function test_own_registered_taxonomy() {
		$this->import_config( 'staxo-config-suite.json' );
		$this->assertTrue( SimpleTaxonomyRefreshed_Client::registered_by_plugin( 'test_hier' ) );
		delete_option( OPTION_STAXO );

		$this->import( array( 'taxonomies' => array( 'test_hier' => $this->taxonomy( 'test_hier' ) ) ) );
		$this->assertArrayHasKey( 'test_hier', get_option( OPTION_STAXO )['taxonomies'] );
	}
}
