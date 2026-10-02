<?php
/**
 * Shared fixtures for the Simple Taxonomy Refreshed tests.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Taxonomies (from a config file), terms (through Terms Import) and a matrix of posts.
 *
 * Used by STaxo_Test_Case and STaxo_Ajax_Test_Case.
 */
trait STaxo_Fixtures {

	/**
	 * Taxonomies defined in tests/files/staxo-config-suite.json.
	 *
	 * @var string[]
	 */
	protected static $staxo_taxonomies = array( 'test_hier', 'test_flat', 'test_cntl', 'test_count' );

	/**
	 * Posts created by make_posts(), keyed by label (P1 ... P9, PG1).
	 *
	 * @var int[]
	 */
	protected $posts = array();

	/**
	 * Load the admin classes (the plugin only loads them in wp-admin) and clear STR state.
	 *
	 * @return void
	 */
	protected function staxo_set_up() {
		// add_settings_error() lives in an admin include.
		require_once ABSPATH . 'wp-admin/includes/template.php';

		$dir = dirname( __DIR__ ) . '/includes/class-simpletaxonomyrefreshed-';
		foreach ( array( 'admin', 'admin-config', 'admin-import', 'admin-merge', 'admin-conversion', 'admin-rename', 'admin-order' ) as $file ) {
			require_once $dir . $file . '.php';
		}

		$this->reset_staxo();
	}

	/**
	 * Clear STR state and the request data.
	 *
	 * @return void
	 */
	protected function staxo_tear_down() {
		$this->reset_staxo();
		$_POST    = array();
		$_REQUEST = array();
		$_FILES   = array();
	}

	/**
	 * Remove the test taxonomies, the STR option, transients and caches.
	 *
	 * @return void
	 */
	protected function reset_staxo() {
		foreach ( self::$staxo_taxonomies as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				unregister_taxonomy( $taxonomy );
			}
			delete_transient( 'staxo_sel_' . $taxonomy );
			delete_option( 'default_term_' . $taxonomy );
		}
		if ( taxonomy_exists( 'test_locked' ) ) {
			unregister_taxonomy( 'test_locked' );
		}
		delete_option( OPTION_STAXO );
		delete_transient( 'staxo_cntl_post_types' );
		delete_transient( 'simple_taxonomy_refreshed_rewrite' );
		foreach ( array( 'staxo_own_taxos', 'staxo_orderings', 'staxo_terms', 'staxo_taxonomies' ) as $key ) {
			wp_cache_delete( $key );
		}
		$this->clear_settings_errors();
	}

	/**
	 * Register `test_locked`: a hierarchical taxonomy with its own capabilities, holding the term "Locked term".
	 *
	 * No role has these capabilities; give them to the current user with grant_caps().
	 * Capabilities: manage_terms `manage_locked`, edit_terms `edit_locked`, delete_terms `delete_locked`, assign_terms `assign_locked`.
	 * On multisite the current user stops being a super admin (who has every capability) and stays a site administrator.
	 *
	 * @return void
	 */
	protected function register_locked_taxonomy() {
		if ( is_multisite() && is_super_admin() ) {
			revoke_super_admin( get_current_user_id() );
		}

		register_taxonomy(
			'test_locked',
			'post',
			array(
				'label'        => 'Locked',
				'public'       => true,
				'show_ui'      => true,
				'hierarchical' => true,
				'capabilities' => array(
					'manage_terms' => 'manage_locked',
					'edit_terms'   => 'edit_locked',
					'delete_terms' => 'delete_locked',
					'assign_terms' => 'assign_locked',
				),
			)
		);
		wp_insert_term( 'Locked term', 'test_locked' );
	}

	/**
	 * Give the current user extra capabilities.
	 *
	 * @param string ...$caps capabilities.
	 * @return void
	 */
	protected function grant_caps( ...$caps ) {
		$user = wp_get_current_user();
		foreach ( $caps as $cap ) {
			$user->add_cap( $cap );
		}
	}

	/**
	 * Clear the settings errors (notices) raised so far in this request.
	 *
	 * @return void
	 */
	protected function clear_settings_errors() {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- tests start each handler call with no notices.
		$GLOBALS['wp_settings_errors'] = array();
	}

	/**
	 * Create a user with the given role and log in as that user.
	 *
	 * @param string $role role name.
	 * @return int user id.
	 */
	protected function login( $role = 'administrator' ) {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		if ( 'administrator' === $role && is_multisite() ) {
			grant_super_admin( $user_id );
		}
		wp_set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * Run the config import handler on a file from tests/files, as if uploaded from the Export/Import screen.
	 *
	 * @param string $file     file name in tests/files.
	 * @param bool   $register whether to register the imported taxonomies afterwards.
	 * @return array settings errors raised by the import.
	 */
	protected function import_config( $file, $register = true ) {
		$this->clear_settings_errors();

		$_POST[ SimpleTaxonomyRefreshed_Admin_Config::IMP_FILE_SLUG ] = '1';
		$_REQUEST['_wpnonce']  = wp_create_nonce( SimpleTaxonomyRefreshed_Admin_Config::IMP_FILE_SLUG );
		$_FILES['config_file'] = array(
			'error'    => 0,
			'tmp_name' => __DIR__ . '/files/' . $file,
		);

		SimpleTaxonomyRefreshed_Admin_Config::check_importexport();

		unset( $_POST[ SimpleTaxonomyRefreshed_Admin_Config::IMP_FILE_SLUG ], $_REQUEST['_wpnonce'], $_FILES['config_file'] );

		if ( $register ) {
			$this->register_taxonomies();
		}

		return get_settings_errors( 'simple-taxonomy-refreshed' );
	}

	/**
	 * Register the taxonomies held in the STR option, as on 'init'.
	 *
	 * @return void
	 */
	protected function register_taxonomies() {
		SimpleTaxonomyRefreshed_Client::init();
		SimpleTaxonomyRefreshed_Client::init_2();
	}

	/**
	 * Run the Terms Import handler.
	 *
	 * @param string $taxonomy  taxonomy name.
	 * @param string $source    file name in tests/files, or the term lines themselves.
	 * @param string $hierarchy 'no', 'tab' or 'space'.
	 * @return array settings errors raised by the import.
	 */
	protected function import_terms( $taxonomy, $source, $hierarchy = 'no' ) {
		$this->clear_settings_errors();

		$path = __DIR__ . '/files/' . $source;
		if ( false === strpos( $source, "\n" ) && is_file( $path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local fixture file.
			$source = file_get_contents( $path );
		}

		$_POST[ SimpleTaxonomyRefreshed_Admin_Import::IMPORT_SLUG ] = '1';
		$_POST['taxonomy']       = $taxonomy;
		$_POST['hierarchy']      = $hierarchy;
		$_POST['import_content'] = $source;
		$_REQUEST['_wpnonce']    = wp_create_nonce( SimpleTaxonomyRefreshed_Admin_Import::IMPORT_SLUG );

		SimpleTaxonomyRefreshed_Admin_Import::check_importation();

		unset( $_POST[ SimpleTaxonomyRefreshed_Admin_Import::IMPORT_SLUG ], $_POST['taxonomy'], $_POST['hierarchy'], $_POST['import_content'], $_REQUEST['_wpnonce'] );

		return get_settings_errors( 'simple-taxonomy-refreshed' );
	}

	/**
	 * Load the four test taxonomies and their terms, and add term meta used by the merge tests.
	 *
	 * @return void
	 */
	protected function load_fixture() {
		$this->import_config( 'staxo-config-suite.json' );

		$this->import_terms( 'test_hier', 'terms-hier-tab.txt', 'tab' );
		$this->import_terms( 'test_count', 'terms-hier-tab.txt', 'tab' );
		$this->import_terms( 'test_flat', 'terms-flat.txt' );
		$this->import_terms( 'test_cntl', 'terms-flat.txt' );
		$this->clear_settings_errors();

		add_term_meta( $this->term( 'test_hier', 'Bebop' )->term_id, 'staxo_test_meta', 'bebop' );
		add_term_meta( $this->term( 'test_flat', 'blue' )->term_id, 'staxo_test_meta', 'blue' );
	}

	/**
	 * Create the post matrix.
	 *
	 * | Post | Status  | test_hier   | test_flat | test_cntl | test_count |
	 * | P1   | publish | Jazz        | red green | red       | Jazz       |
	 * | P2   | publish | Bebop       | green     | green     | Bebop      |
	 * | P3   | publish | Jazz Bebop  | blue      | red blue  | Jazz       |
	 * | P4   | publish | Big Band    | red blue  | blue      | -          |
	 * | P5   | draft   | Rock        | yellow    | -         | Rock       |
	 * | P6   | pending | Punk        | yellow    | -         | Rock       |
	 * | P7   | private | Painting    | cyan      | -         | Painting   |
	 * | P8   | future  | Sculpture   | (Unsorted)| -         | Sculpture  |
	 * | P9   | trash   | Jazz        | red       | -         | Jazz       |
	 * | PG1  | publish (page) | -    | red       | -         | -          |
	 *
	 * P8 gets the test_flat default term "Unsorted" from wp_insert_post().
	 *
	 * @return int[] post ids keyed by label.
	 */
	protected function make_posts() {
		$matrix = array(
			'P1'  => array( 'publish', 'post', array( 'Jazz' ), array( 'red', 'green' ), array( 'red' ), array( 'Jazz' ) ),
			'P2'  => array( 'publish', 'post', array( 'Bebop' ), array( 'green' ), array( 'green' ), array( 'Bebop' ) ),
			'P3'  => array( 'publish', 'post', array( 'Jazz', 'Bebop' ), array( 'blue' ), array( 'red', 'blue' ), array( 'Jazz' ) ),
			'P4'  => array( 'publish', 'post', array( 'Big Band' ), array( 'red', 'blue' ), array( 'blue' ), array() ),
			'P5'  => array( 'draft', 'post', array( 'Rock' ), array( 'yellow' ), array(), array( 'Rock' ) ),
			'P6'  => array( 'pending', 'post', array( 'Punk' ), array( 'yellow' ), array(), array( 'Rock' ) ),
			'P7'  => array( 'private', 'post', array( 'Painting' ), array( 'cyan' ), array(), array( 'Painting' ) ),
			'P8'  => array( 'future', 'post', array( 'Sculpture' ), array(), array(), array( 'Sculpture' ) ),
			'P9'  => array( 'trash', 'post', array( 'Jazz' ), array( 'red' ), array(), array( 'Jazz' ) ),
			'PG1' => array( 'publish', 'page', array(), array( 'red' ), array(), array() ),
		);
		$taxos  = array( 'test_hier', 'test_flat', 'test_cntl', 'test_count' );

		$this->posts = array();
		foreach ( $matrix as $label => $row ) {
			$args = array(
				'post_title'  => $label,
				'post_status' => $row[0],
				'post_type'   => $row[1],
			);
			if ( 'future' === $row[0] ) {
				$args['post_date'] = gmdate( 'Y-m-d H:i:s', time() + YEAR_IN_SECONDS );
			}
			$post_id = self::factory()->post->create( $args );

			foreach ( $taxos as $i => $taxonomy ) {
				$names = $row[ $i + 2 ];
				if ( empty( $names ) ) {
					continue;
				}
				$ids = array();
				foreach ( $names as $name ) {
					$ids[] = $this->term( $taxonomy, $name )->term_id;
				}
				wp_set_object_terms( $post_id, $ids, $taxonomy );
			}
			$this->posts[ $label ] = $post_id;
		}

		return $this->posts;
	}

	/**
	 * Get a term by name.
	 *
	 * @param string $taxonomy taxonomy name.
	 * @param string $name     term name (unique in the fixtures).
	 * @return WP_Term
	 */
	protected function term( $taxonomy, $name ) {
		$terms = get_terms(
			array(
				'taxonomy'      => $taxonomy,
				'name'          => $name,
				'hide_empty'    => false,
				'cache_results' => false,
			)
		);
		$this->assertIsArray( $terms, "Terms query failed for $taxonomy" );
		$this->assertCount( 1, $terms, "Expected one term '$name' in $taxonomy" );

		return $terms[0];
	}

	/**
	 * Whether a term with this name exists in the taxonomy.
	 *
	 * @param string $taxonomy taxonomy name.
	 * @param string $name     term name.
	 * @return bool
	 */
	protected function term_exists_by_name( $taxonomy, $name ) {
		$terms = get_terms(
			array(
				'taxonomy'      => $taxonomy,
				'name'          => $name,
				'hide_empty'    => false,
				'cache_results' => false,
			)
		);

		return is_array( $terms ) && ! empty( $terms );
	}

	/**
	 * The sorted term names on a post, read from the database rather than a cache.
	 *
	 * @param int|string $post     post id, or label from make_posts().
	 * @param string     $taxonomy taxonomy name.
	 * @return string[]
	 */
	protected function post_terms( $post, $taxonomy ) {
		$post_id = ( is_string( $post ) ? $this->posts[ $post ] : $post );
		clean_object_term_cache( $post_id, get_post_type( $post_id ) );
		$names = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
		$this->assertIsArray( $names );
		sort( $names );

		return $names;
	}

	/**
	 * The stored count of a term.
	 *
	 * @param string $taxonomy taxonomy name.
	 * @param string $name     term name.
	 * @return int
	 */
	protected function term_count( $taxonomy, $name ) {
		$term = $this->term( $taxonomy, $name );
		clean_term_cache( $term->term_id, $taxonomy );

		return (int) get_term( $term->term_id, $taxonomy )->count;
	}

	/**
	 * Map of term name => parent name ('' for top level) for a taxonomy.
	 *
	 * @param string $taxonomy taxonomy name.
	 * @return array
	 */
	protected function term_tree( $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'      => $taxonomy,
				'hide_empty'    => false,
				'cache_results' => false,
			)
		);
		$names = wp_list_pluck( $terms, 'name', 'term_id' );
		$tree  = array();
		foreach ( $terms as $term ) {
			$tree[ $term->name ] = ( 0 === (int) $term->parent ? '' : $names[ $term->parent ] );
		}
		ksort( $tree );

		return $tree;
	}

	/**
	 * Make wp_redirect() throw, so handlers that redirect then exit can be tested.
	 *
	 * @return void
	 */
	protected function expect_redirect() {
		add_filter(
			'wp_redirect',
			static function ( $location ) {
				throw new STaxo_Redirect_Exception( $location ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
		);
	}

	/**
	 * Messages of a given type from a settings errors array.
	 *
	 * @param array  $errors settings errors.
	 * @param string $type   'updated', 'error', 'warning' ...
	 * @return string[]
	 */
	protected function messages( $errors, $type ) {
		$out = array();
		foreach ( $errors as $error ) {
			if ( $type === $error['type'] ) {
				$out[] = $error['message'];
			}
		}

		return $out;
	}
}
