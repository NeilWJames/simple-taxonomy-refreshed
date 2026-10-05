<?php
/**
 * Tests for Terms Control on the post and post list screens: notices, radio buttons and limit scripts.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * SimpleTaxonomyRefreshed_Admin::check_posts_outside_limits() (all_admin_notices) on the shared fixture.
 *
 * The suite's test_cntl has Terms Control: any status except trash, checked when the post is saved,
 * 1 to 2 terms; it is flat. Tests change the controls with set_control().
 * Classic editor notices are printed; block editor notices and all scripts are added to
 * the staxo_client script (radio buttons in the block editor: before staxo_radio_editor).
 *
 * @group screens
 */
class Test_STaxo_Post_Screens extends STaxo_Test_Case {

	/**
	 * Notice text for too few test_cntl terms on an existing post.
	 */
	const BELOW = 'The number of terms for taxonomy (Control Terms) is less than the required minimum number 1.';

	/**
	 * Log in an administrator and load the fixture.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		require_once ABSPATH . 'wp-admin/includes/post.php';

		$this->login( 'administrator' );
		$this->load_fixture();
		$this->make_posts();
		$this->reset_scripts();
	}

	/**
	 * Clear the screen, post, scripts and the plugin's editor state.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'use_block_editor_for_post', '__return_false' );
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- each test starts with no screen or post.
		$GLOBALS['current_screen'] = null;
		$GLOBALS['post']           = null;
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->reset_scripts();
		$GLOBALS['wp_scripts'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp_styles']  = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		parent::tear_down();
	}

	/**
	 * Start with no scripts, the radio editor handle registered, and the plugin's editor state cleared.
	 *
	 * @return void
	 */
	private function reset_scripts() {
		$GLOBALS['wp_scripts'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		// Registered as enqueue_radio_editor() does, so inline settings can be read whether or not build/editor exists.
		wp_register_script( 'staxo_radio_editor', 'https://example.org/staxo-radio-editor.js', array(), '1', true );

		SimpleTaxonomyRefreshed_Admin::$use_block_editor = null;
		// PHP 8.1+ allows access to the private property without setAccessible().
		$property = new ReflectionProperty( 'SimpleTaxonomyRefreshed_Admin', 'enqueue_client' );
		$property->setValue( null, false );
	}

	/**
	 * Change the Terms Control settings of a taxonomy and rebuild the control cache.
	 *
	 * @param string $taxonomy taxonomy name.
	 * @param array  $settings settings to change (st_cc_*, show_in_rest ...).
	 * @return void
	 */
	private function set_control( $taxonomy, $settings ) {
		$options = get_option( OPTION_STAXO );
		foreach ( $settings as $key => $value ) {
			$options['taxonomies'][ $taxonomy ][ $key ] = $value;
		}
		update_option( OPTION_STAXO, $options );
		SimpleTaxonomyRefreshed_Client::refresh_term_cntl_cache();
	}

	/**
	 * Radio buttons for test_hier: hierarchical, maximum 1, no minimum (so "No term" is offered).
	 *
	 * @param array $settings other settings to change.
	 * @return void
	 */
	private function hier_radio( $settings = array() ) {
		$this->set_control(
			'test_hier',
			array_merge(
				array(
					'st_cc_type' => '2',
					'st_cc_hard' => '1',
					'st_cc_umin' => '0',
					'st_cc_min'  => '0',
					'st_cc_umax' => '1',
					'st_cc_max'  => '1',
				),
				$settings
			)
		);
	}

	/**
	 * Run the post screen check for a post, as on post.php (or post-new.php with action 'add').
	 *
	 * @param int|string $post   post id, or label from make_posts().
	 * @param bool       $block  whether the block editor is used.
	 * @param string     $action screen action ('' or 'add').
	 * @return string printed output.
	 */
	private function post_screen( $post, $block = false, $action = '' ) {
		$this->reset_scripts();
		$post_id = ( is_string( $post ) ? $this->posts[ $post ] : $post );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the screen's post.
		$GLOBALS['post'] = get_post( $post_id );
		set_current_screen( 'post' );
		$screen = get_current_screen();
		$screen->is_block_editor( $block );
		$screen->action = $action;

		ob_start();
		SimpleTaxonomyRefreshed_Admin::check_posts_outside_limits();
		return ob_get_clean();
	}

	/**
	 * Run the post list (Quick Edit) screen check for posts.
	 *
	 * @return string printed output.
	 */
	private function list_screen() {
		$this->reset_scripts();
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- no post on the list screen.
		$GLOBALS['post'] = null;
		set_current_screen( 'edit-post' );

		ob_start();
		SimpleTaxonomyRefreshed_Admin::check_posts_outside_limits();
		return ob_get_clean();
	}

	/**
	 * Inline scripts added after a script handle.
	 *
	 * @param string $handle   script handle.
	 * @param string $position 'after' or 'before'.
	 * @return string
	 */
	private function inline( $handle, $position = 'after' ) {
		$data = wp_scripts()->get_data( $handle, $position );
		return ( is_array( $data ) ? implode( "\n", $data ) : '' );
	}

	/**
	 * Set the test_cntl terms of a post.
	 *
	 * @param string   $label post label from make_posts().
	 * @param string[] $names term names.
	 * @return void
	 */
	private function set_cntl_terms( $label, $names ) {
		$ids = array();
		foreach ( $names as $name ) {
			$ids[] = $this->term( 'test_cntl', $name )->term_id;
		}
		wp_set_object_terms( $this->posts[ $label ], $ids, 'test_cntl' );
	}

	/**
	 * Classic editor: a draft with too few terms shows an error notice for the taxonomy.
	 */
	public function test_classic_below_minimum() {
		$html = $this->post_screen( 'P5' );

		$this->assertStringContainsString( 'class="notice notice-error" id="err-test_cntl"', $html );
		$this->assertStringContainsString( self::BELOW . '  Please review and add additional terms before trying to save.', $html );
		$this->assertStringNotContainsString( 'createNotice', $this->inline( 'staxo_client' ), 'No block editor notice' );
	}

	/**
	 * Classic editor, new post: the notice asks for the terms to be added.
	 */
	public function test_classic_new_post() {
		$html = $this->post_screen( 'P5', false, 'add' );

		$this->assertStringContainsString( 'The number of terms for taxonomy (Control Terms) needs to be at least 1.  Please review and add additional terms.', $html );
	}

	/**
	 * Classic editor: too many terms shows an error notice.
	 */
	public function test_classic_above_maximum() {
		$this->set_cntl_terms( 'P1', array( 'red', 'green', 'blue' ) );
		$html = $this->post_screen( 'P1' );

		$this->assertStringContainsString( 'class="notice notice-error" id="err-test_cntl"', $html );
		$this->assertStringContainsString( 'The number of terms for taxonomy (Control Terms) is greater than the required maximum number 2.  Please review and remove terms before trying to save.', $html );
	}

	/**
	 * Classic editor: within the limits, only a hidden notice is printed (for the scripts to fill).
	 */
	public function test_classic_within_limits() {
		$html = $this->post_screen( 'P1' );

		$this->assertStringContainsString( '<div class="notice notice-error hidden" id="err-test_cntl">', $html );
		$this->assertStringNotContainsString( 'less than the required minimum', $html );
		$this->assertStringNotContainsString( 'greater than the required maximum', $html );
	}

	/**
	 * A new post (auto-draft) is not checked yet.
	 */
	public function test_auto_draft_not_checked() {
		$post = self::factory()->post->create( array( 'post_status' => 'auto-draft' ) );

		$this->assertStringNotContainsString( 'err-test_cntl', $this->post_screen( $post ) );
	}

	/**
	 * Controls for published posts only: a draft with too few terms gets a warning, not an error.
	 */
	public function test_published_only_draft_warning() {
		$this->set_control( 'test_cntl', array( 'st_cc_type' => '1' ) );
		$html = $this->post_screen( 'P5' );

		$this->assertStringContainsString( 'class="notice notice-warning" id="err-test_cntl"', $html );
		$this->assertStringContainsString( self::BELOW, $html );
	}

	/**
	 * A user who cannot assign the terms is warned, and told that saving will be refused.
	 */
	public function test_user_cannot_change_terms() {
		$this->login( 'subscriber' );
		$html = $this->post_screen( 'P5' );

		$this->assertStringContainsString( 'class="notice notice-warning" id="err-test_cntl"', $html );
		$this->assertStringContainsString( self::BELOW . '  For information as you cannot add them.  N.B. You will not be able to save any changes!', $html );
	}

	/**
	 * Notification only: no notice for users who can change the terms; a warning (saving allowed) for others.
	 */
	public function test_notification_only() {
		$this->set_control( 'test_cntl', array( 'st_cc_hard' => '0' ) );

		$this->assertStringNotContainsString( 'err-test_cntl', $this->post_screen( 'P5' ), 'User who can change the terms' );

		$this->login( 'subscriber' );
		$html = $this->post_screen( 'P5' );
		$this->assertStringContainsString( 'class="notice notice-warning" id="err-test_cntl"', $html );
		$this->assertStringContainsString( self::BELOW . '  For information as you cannot add them.', $html );
		$this->assertStringNotContainsString( 'You will not be able to save', $html );
	}

	/**
	 * Block editor: the notice is a core/notices script on staxo_client, not printed.
	 */
	public function test_block_editor_notice() {
		$html   = $this->post_screen( 'P5', true );
		$script = $this->inline( 'staxo_client' );

		$this->assertStringNotContainsString( 'err-test_cntl', $html );
		$this->assertTrue( wp_script_is( 'staxo_client', 'enqueued' ) );
		$this->assertStringContainsString( 'createNotice( "error", "' . self::BELOW . '  Please review and add additional terms before trying to save.', $script );
		$this->assertStringContainsString( 'id: "str_notice_test_cntl"', $script );
		$this->assertStringContainsString( 'removeNotice( "str_notice_test_cntl" )', $script );
	}

	/**
	 * Block editor: no notice when the taxonomy is not in the REST API.
	 */
	public function test_block_editor_notice_needs_rest() {
		$this->set_control( 'test_cntl', array( 'show_in_rest' => '0' ) );
		$this->post_screen( 'P5', true );

		$this->assertStringNotContainsString( 'createNotice', $this->inline( 'staxo_client' ) );
	}

	/**
	 * Labels with quotes are escaped: HTML in the classic notice, JSON in the block editor notice.
	 */
	public function test_notice_label_with_quotes() {
		$options = get_option( OPTION_STAXO );
		$options['taxonomies']['test_cntl']['labels']['name'] = 'Writer\'s "Notes"';
		update_option( OPTION_STAXO, $options );
		$this->register_taxonomies();

		$this->assertStringContainsString( '(Writer&#039;s &quot;Notes&quot;) is less than', $this->post_screen( 'P5' ) );

		$this->post_screen( 'P5', true );
		$this->assertStringContainsString( '(Writer\'s \"Notes\") is less than', $this->inline( 'staxo_client' ) );
	}

	/**
	 * Checked as terms are changed: the limits are passed to the client script with the check for
	 * the editor (tags, categories or block editor); no notice is added in the block editor.
	 */
	public function test_hard_limits_scripts() {
		$this->set_control( 'test_cntl', array( 'st_cc_hard' => '2' ) );

		$this->post_screen( 'P1' );
		$script = $this->inline( 'staxo_client' );
		$this->assertStringContainsString( 'tax_cntl.push( ["test_cntl",2,1,"' . self::BELOW . ' Saving is blocked.",2,', $script );
		$this->assertStringContainsString( ',0,"No term","publish",', $script, 'Flat, default "No term", post status' );
		$this->assertStringContainsString( 'dom_tag_cntl_check( "test_cntl" )', $script );

		$this->post_screen( 'P5', true );
		$script = $this->inline( 'staxo_client' );
		$this->assertStringContainsString( 'block_limit( window.wp, "test_cntl" )', $script );
		$this->assertStringNotContainsString( 'createNotice', $script, 'The script reports the limits itself' );

		$this->set_control(
			'test_hier',
			array(
				'st_cc_type' => '2',
				'st_cc_hard' => '2',
				'st_cc_umin' => '1',
				'st_cc_min'  => '1',
				'st_cc_umax' => '1',
				'st_cc_max'  => '2',
			)
		);
		$this->post_screen( 'P1' );
		$this->assertStringContainsString( 'dom_hier_cntl_check( "test_hier" )', $this->inline( 'staxo_client' ) );

		// Users who cannot change the terms get no checks as they are changed.
		$this->login( 'subscriber' );
		$this->post_screen( 'P1' );
		$this->assertStringNotContainsString( 'cntl_check', $this->inline( 'staxo_client' ) );
	}

	/**
	 * Classic editor: a hierarchical taxonomy limited to one term gets radio buttons, with "No term".
	 */
	public function test_radio_classic() {
		$this->hier_radio();
		$this->post_screen( 'P1' );
		$script = $this->inline( 'staxo_client' );

		$this->assertStringContainsString( 'tax_cntl.push( ["test_hier",2,0,', $script );
		$this->assertStringContainsString( ',1,"No term","publish",', $script, 'Hierarchical, "No term", post status' );
		$this->assertStringContainsString( 'More than one term for taxonomy (Test Terms) has already been attached.', $script );
		$this->assertStringContainsString( 'wait_for_editor_ready( function() { dom_radio_client( "test_hier" ); } );', $script );

		// Two terms already attached: left as checkboxes.
		$this->post_screen( 'P3' );
		$this->assertStringNotContainsString( 'dom_radio_client( "test_hier" )', $this->inline( 'staxo_client' ) );

		// A new post has no terms yet: radio buttons.
		$post = self::factory()->post->create( array( 'post_status' => 'auto-draft' ) );
		$this->post_screen( $post );
		$this->assertStringContainsString( 'dom_radio_client( "test_hier" )', $this->inline( 'staxo_client' ) );
	}

	/**
	 * Radio buttons are only for hierarchical taxonomies limited to exactly one term, at any control level.
	 */
	public function test_radio_conditions() {
		// Flat taxonomy limited to one term: no radio buttons.
		$this->set_control( 'test_cntl', array( 'st_cc_max' => '1' ) );
		$this->post_screen( 'P1' );
		$this->assertStringNotContainsString( 'dom_radio_client', $this->inline( 'staxo_client' ) );

		// Hierarchical, maximum 2: no radio buttons.
		$this->hier_radio( array( 'st_cc_max' => '2' ) );
		$this->post_screen( 'P1' );
		$this->assertStringNotContainsString( 'dom_radio_client( "test_hier" )', $this->inline( 'staxo_client' ) );

		// Notification only: radio buttons too.
		$this->hier_radio( array( 'st_cc_hard' => '0' ) );
		$this->post_screen( 'P1' );
		$this->assertStringContainsString( 'dom_radio_client( "test_hier" )', $this->inline( 'staxo_client' ) );
	}

	/**
	 * Block editor: the radio term selector is told which taxonomies to change, and whether to offer "No term".
	 */
	public function test_radio_block_editor() {
		$this->hier_radio();
		$this->post_screen( 'P1', true );

		$this->assertStringContainsString( 'window.staxo_radio[ "test_hier" ] = {"noTerm":"No term"};', $this->inline( 'staxo_radio_editor', 'before' ) );
		$this->assertStringNotContainsString( 'dom_radio_client', $this->inline( 'staxo_client' ) );

		// With a minimum of 1 there is no "No term" choice.
		$this->hier_radio(
			array(
				'st_cc_umin' => '1',
				'st_cc_min'  => '1',
			)
		);
		$this->post_screen( 'P1', true );
		$this->assertStringContainsString( 'window.staxo_radio[ "test_hier" ] = {"noTerm":null};', $this->inline( 'staxo_radio_editor', 'before' ) );
	}

	/**
	 * Post list: Quick Edit gets radio buttons, and the checks as terms are changed.
	 */
	public function test_list_screen() {
		$this->hier_radio();
		$this->set_control( 'test_cntl', array( 'st_cc_hard' => '2' ) );

		$this->assertSame( '', $this->list_screen(), 'Nothing printed' );
		$script = $this->inline( 'staxo_client' );
		$this->assertStringContainsString( 'tax_cntl.push( ["test_hier",2,0,', $script );
		$this->assertStringContainsString( ',1,"No term","",', $script, 'No post on the list screen' );
		$this->assertStringContainsString( 'dom_qe_radio_client( "test_hier" )', $script );
		$this->assertStringContainsString( 'wait_for_editor_ready( function() { dom_qe_cntl_check( "test_cntl", 0 ); } );', $script );

		// Checked only when the post is saved: no Quick Edit check.
		$this->set_control( 'test_cntl', array( 'st_cc_hard' => '1' ) );
		$this->list_screen();
		$this->assertStringNotContainsString( 'dom_qe_cntl_check', $this->inline( 'staxo_client' ) );
	}

	/**
	 * Other screens: nothing.
	 */
	public function test_other_screen() {
		$this->set_control( 'test_cntl', array( 'st_cc_hard' => '2' ) );
		set_current_screen( 'dashboard' );

		ob_start();
		SimpleTaxonomyRefreshed_Admin::check_posts_outside_limits();
		$this->assertSame( '', ob_get_clean() );
		$this->assertFalse( wp_script_is( 'staxo_client', 'enqueued' ) );
	}

	/**
	 * Block editor detection: the screen, else the post, and the block editor assets hook.
	 */
	public function test_is_block_editor() {
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- no screen; the post decides.
		$GLOBALS['current_screen'] = null;
		$GLOBALS['post']           = get_post( $this->posts['P1'] );
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->assertTrue( SimpleTaxonomyRefreshed_Admin::is_block_editor(), 'Posts use the block editor' );

		SimpleTaxonomyRefreshed_Admin::$use_block_editor = null;
		add_filter( 'use_block_editor_for_post', '__return_false' );
		$this->assertFalse( SimpleTaxonomyRefreshed_Admin::is_block_editor(), 'Classic editor' );

		SimpleTaxonomyRefreshed_Admin::block_editor_active();
		$this->assertTrue( SimpleTaxonomyRefreshed_Admin::is_block_editor(), 'Set when the block editor loads its assets' );
	}

	/**
	 * The radio term selector is loaded in the block editor when it has been built.
	 */
	public function test_enqueue_radio_editor() {
		$GLOBALS['wp_scripts'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		SimpleTaxonomyRefreshed_Admin::enqueue_radio_editor();

		$built = file_exists( dirname( __DIR__ ) . '/build/editor/index.asset.php' );
		$this->assertSame( $built, wp_script_is( 'staxo_radio_editor', 'enqueued' ), 'Loaded only when build/editor exists' );
		if ( $built ) {
			$this->assertStringContainsString( 'build/editor/index.js', wp_scripts()->registered['staxo_radio_editor']->src );
			$this->assertContains( 'wp-editor', wp_scripts()->registered['staxo_radio_editor']->deps );
		}
	}
}
