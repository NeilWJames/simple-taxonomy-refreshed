<?php
/**
 * Tests for the Custom Taxonomies screen: the list of taxonomies and the add/edit form.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * SimpleTaxonomyRefreshed_Admin::page_manage() rendered with output buffering: the list screen,
 * the notices after a change, the form to add a taxonomy and the form to edit one held by the plugin.
 * (The form for an external taxonomy is tested in class M, Test_STaxo_Externals.)
 *
 * @group form
 */
class Test_STaxo_Taxonomy_Form extends STaxo_Test_Case {

	/**
	 * Tabs of the form for a custom taxonomy (aria-controls of each tab button).
	 *
	 * @var string[]
	 */
	private static $tabs = array( 'mainopts', 'visibility', 'labels', 'rewriteURL', 'permissions', 'rest', 'other', 'wpgraphql', 'adm_filter', 'callback', 'countt' );

	/**
	 * Fields whose values name PHP code: read-only for users who may not change callbacks.
	 *
	 * @var string[]
	 */
	private static $callback_fields = array( 'rest_controller_class', 'st_update_count_callback', 'st_meta_box_cb', 'st_meta_box_sanitize_cb' );

	/**
	 * Log in an administrator, load the WordPress default labels and the suite taxonomies.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->login( 'administrator' );
		SimpleTaxonomyRefreshed_Client::get_wp_default_labels();
		$this->import_config( 'staxo-config-suite.json' );
		// Start with no notices (the import adds one).
		$this->clear_settings_errors();
	}

	/**
	 * Clear the request and the filter used for read-only callback fields.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'staxo_can_edit_callbacks', '__return_false' );
		$_GET = array();
		parent::tear_down();
	}

	/**
	 * Render the Custom Taxonomies screen for a request.
	 *
	 * @param array $get query arguments ($_GET).
	 * @return string HTML.
	 */
	private function render( $get = array() ) {
		$_GET  = $get;
		$level = ob_get_level();
		ob_start();
		try {
			SimpleTaxonomyRefreshed_Admin::page_manage();
			return ob_get_clean();
		} finally {
			// Also close the buffer when the page throws (wp_die()).
			while ( ob_get_level() > $level ) {
				ob_end_clean();
			}
		}
	}

	/**
	 * Render the form to edit a custom taxonomy.
	 *
	 * @param string $taxonomy taxonomy name.
	 * @return string HTML.
	 */
	private function edit_form( $taxonomy ) {
		return $this->render(
			array(
				'action'        => 'edit',
				'taxonomy_name' => $taxonomy,
			)
		);
	}

	/**
	 * Change stored settings of a custom taxonomy.
	 *
	 * @param string $taxonomy taxonomy name.
	 * @param array  $fields   fields to set.
	 * @param array  $remove   fields to remove.
	 * @return void
	 */
	private function store( $taxonomy, $fields, $remove = array() ) {
		$options                            = get_option( OPTION_STAXO );
		$options['taxonomies'][ $taxonomy ] = array_diff_key( array_merge( $options['taxonomies'][ $taxonomy ], $fields ), array_flip( $remove ) );
		update_option( OPTION_STAXO, $options );
	}

	/**
	 * The first input tag with this id.
	 *
	 * @param string $html HTML.
	 * @param string $id   input id.
	 * @return string the tag.
	 */
	private function input( $html, $id ) {
		$this->assertMatchesRegularExpression( '/<input[^>]*\sid="' . preg_quote( $id, '/' ) . '"[^>]*>/', $html, "No input $id" );
		preg_match( '/<input[^>]*\sid="' . preg_quote( $id, '/' ) . '"[^>]*>/', $html, $match );
		return $match[0];
	}

	/**
	 * Value of a text input.
	 *
	 * @param string $html HTML.
	 * @param string $id   input id.
	 * @return string|null value (HTML encoded), null if none.
	 */
	private function value( $html, $id ) {
		return ( preg_match( '/\svalue="([^"]*)"/', $this->input( $html, $id ), $match ) ? $match[1] : null );
	}

	/**
	 * Whether a radio button or checkbox is ticked.
	 *
	 * @param string $html HTML.
	 * @param string $id   input id.
	 * @return bool
	 */
	private function is_checked( $html, $id ) {
		return false !== strpos( $this->input( $html, $id ), "checked='checked'" );
	}

	/**
	 * The value chosen in a select.
	 *
	 * @param string $html HTML.
	 * @param string $name select name (and id).
	 * @return string|null value, null if none chosen.
	 */
	private function selected( $html, $name ) {
		$this->assertMatchesRegularExpression( '#<select name="' . $name . '" id="' . $name . '">.*?</select>#s', $html, "No select $name" );
		preg_match( '#<select name="' . $name . '" id="' . $name . '">(.*?)</select>#s', $html, $select );
		return ( preg_match( "/selected='selected' value=\"([^\"]*)\"/", $select[1], $match ) ? $match[1] : null );
	}

	/**
	 * One table body of the list screen.
	 *
	 * @param string $html HTML.
	 * @param string $id   'the-list-custom' or 'the-list-external'.
	 * @return string
	 */
	private function tbody( $html, $id ) {
		$this->assertMatchesRegularExpression( '#<tbody id="' . $id . '".*?</tbody>#s', $html );
		preg_match( '#<tbody id="' . $id . '".*?</tbody>#s', $html, $match );
		return $match[0];
	}

	/**
	 * The list screen shows a row for each custom taxonomy, with its links and columns,
	 * and the other public taxonomies in the external list.
	 */
	public function test_list_screen() {
		$html = $this->render();

		$this->assertStringContainsString( '<h1 class="wp-heading-inline">Custom Taxonomies</h1>', $html );
		$this->assertStringContainsString( 'admin.php?page=staxo_settings&action=add', $html, 'Add New link' );

		$custom = $this->tbody( $html, 'the-list-custom' );
		$this->assertSame( 4, preg_match_all( '/<tr id="taxonomy-\d+"/', $custom ) );
		$labels = array(
			'test_hier'  => 'Test Terms',
			'test_flat'  => 'Flat Terms',
			'test_cntl'  => 'Control Terms',
			'test_count' => 'Count Terms',
		);
		foreach ( $labels as $name => $label ) {
			$this->assertStringContainsString( '&amp;action=edit&amp;taxonomy_name=' . $name . '" title="Edit the taxonomy &#039;' . $label . '&#039;" aria-label="Modify - ' . $label . '">' . $label . '</a>', $custom );
			$this->assertStringContainsString( 'aria-label="Export PHP - ' . $label . '"', $custom );
			$this->assertStringContainsString( 'aria-label="Delete - ' . $label . '"', $custom );
			$this->assertStringContainsString( 'aria-label="Flush &amp; Delete - ' . $label . '"', $custom );
			$this->assertStringContainsString( wp_create_nonce( 'staxo_delete_' . $name ), $custom, "$name: delete nonce" );
		}
		// Columns: name, post types (labels), hierarchical, rewrite, public, block editor.
		$this->assertMatchesRegularExpression( '#<td>test_flat</td>\s*<td>\s*Posts, Pages\s*</td>\s*<td>False</td>\s*<td>False</td>\s*<td>True</td>\s*<td>True</td>#', $custom );
		$this->assertMatchesRegularExpression( '#<td>test_hier</td>\s*<td>\s*Posts\s*</td>\s*<td>True</td>#', $custom );

		$external = $this->tbody( $html, 'the-list-external' );
		$this->assertStringContainsString( 'taxonomy_name=category" title="Edit the taxonomy &#039;Categories&#039;" aria-label="Extra Functions - Categories"', $external );
		$this->assertStringContainsString( 'aria-label="Extra Functions - Tags"', $external );
		$this->assertStringNotContainsString( 'Delete Extra Functions', $external, 'No extra functions saved' );
		foreach ( array_keys( $labels ) as $name ) {
			$this->assertStringNotContainsString( 'taxonomy_name=' . $name . '"', $external, "$name is not external" );
		}
	}

	/**
	 * Quotes in a label are shown in the link text and its title (the title was once empty).
	 */
	public function test_list_screen_label_with_quotes() {
		$options = get_option( OPTION_STAXO );
		$options['taxonomies']['test_hier']['labels']['name'] = 'Writer\'s "Terms"';
		update_option( OPTION_STAXO, $options );

		$custom = $this->tbody( $this->render(), 'the-list-custom' );

		$this->assertStringContainsString( 'title="Edit the taxonomy &#039;Writer&#039;s &quot;Terms&quot;&#039;"', $custom );
		$this->assertStringContainsString( '>Writer&#039;s &quot;Terms&quot;</a>', $custom );
	}

	/**
	 * An external taxonomy with saved extra functions offers to delete them.
	 */
	public function test_list_screen_external_saved() {
		$options                          = get_option( OPTION_STAXO );
		$options['externals']['category'] = array( 'st_cc_type' => 0 );
		update_option( OPTION_STAXO, $options );

		$external = $this->tbody( $this->render(), 'the-list-external' );

		$this->assertStringContainsString( 'aria-label="Delete Extra Functions - Categories"', $external );
		$this->assertStringContainsString( wp_create_nonce( 'staxo_delete_category' ), $external );
		$this->assertStringNotContainsString( 'aria-label="Delete Extra Functions - Tags"', $external );
	}

	/**
	 * With no custom taxonomy the list says so, and all public taxonomies are external.
	 */
	public function test_list_screen_empty() {
		delete_option( OPTION_STAXO );
		$html = $this->render();

		$this->assertStringContainsString( 'No custom taxonomy.', $this->tbody( $html, 'the-list-custom' ) );
		$external = $this->tbody( $html, 'the-list-external' );
		$this->assertStringContainsString( 'aria-label="Extra Functions - Categories"', $external );
		$this->assertStringContainsString( 'aria-label="Extra Functions - Test Terms"', $external, 'Registered taxonomies not in the settings are external' );
	}

	/**
	 * After a change the screen reports it, with the taxonomy name made safe; unknown messages show nothing.
	 */
	public function test_messages() {
		$messages = array(
			'added'         => 'Taxonomy "test_hier" added successfully !',
			'updated'       => 'Taxonomy "test_hier" updated successfully !',
			'deleted'       => 'Taxonomy "test_hier" deleted successfully !',
			'flush-deleted' => 'Taxonomy "test_hier" and relations deleted successfully !',
			'ext_deleted'   => 'Taxonomy "test_hier" extra functions deleted successfully !',
		);
		foreach ( $messages as $message => $text ) {
			$this->clear_settings_errors();
			$html = $this->render(
				array(
					'message' => $message,
					'staxo'   => 'test_hier',
				)
			);
			$this->assertContains( $text, $this->messages( get_settings_errors( 'simple-taxonomy-refreshed' ), 'updated' ), $message );
			$this->assertStringContainsString( 'successfully !', $html, "$message: shown" );
			$this->assertStringContainsString( 'id="the-list-custom"', $html, "$message: then the list" );
		}

		// Tags are removed from the name.
		$this->clear_settings_errors();
		$this->render(
			array(
				'message' => 'added',
				'staxo'   => '<script>x</script>test_hier',
			)
		);
		$this->assertSame( array( 'Taxonomy "test_hier" added successfully !' ), $this->messages( get_settings_errors( 'simple-taxonomy-refreshed' ), 'updated' ) );

		// An unknown message, or no taxonomy name, adds no notice.
		$requests = array(
			array(
				'message' => 'other',
				'staxo'   => 'test_hier',
			),
			array( 'message' => 'added' ),
		);
		foreach ( $requests as $get ) {
			$this->clear_settings_errors();
			$html = $this->render( $get );
			$this->assertSame( array(), get_settings_errors( 'simple-taxonomy-refreshed' ) );
			$this->assertStringNotContainsString( 'successfully !', $html );
		}
	}

	/**
	 * Add New: an empty form with the default values, all tabs, and a disabled Add button.
	 */
	public function test_add_form() {
		$html = $this->render( array( 'action' => 'add' ) );

		$this->assertStringContainsString( '<h1 class="wp-heading-_">Add Custom Taxonomy</h1>', $html );
		$this->assertStringContainsString( '<form id="addtag" method="post"', $html );
		$this->assertStringContainsString( '<input type="hidden" name="action" value="add-taxonomy" />', $html );
		$this->assertStringContainsString( 'name="_wpnonce" value="' . wp_create_nonce( 'staxo_add_taxo' ) . '"', $html );
		$this->assertStringContainsString( 'The taxonomy definition options are spread across 7 tabs.', $html );
		foreach ( self::$tabs as $tab ) {
			$this->assertStringContainsString( 'role="tab" aria-controls="' . $tab . '"', $html, "Tab $tab" );
			$this->assertMatchesRegularExpression( '/<div id="' . $tab . '" class="meta-box-sortabless( is-hidden)?" role="tabpanel">/', $html, "Panel $tab" );
		}
		$this->assertStringNotContainsString( 'id="the-list-custom"', $html, 'Only the form' );

		// The name can be typed.
		$name = $this->input( $html, 'name' );
		$this->assertSame( '', $this->value( $html, 'name' ) );
		$this->assertStringNotContainsString( 'readonly', $name );

		// Defaults: no post type chosen, not hierarchical, public, labels and capabilities from WordPress.
		$this->assertDoesNotMatchRegularExpression( '/aria-checked="true"[^>]*name="objects\[\]"/', $html );
		$this->assertMatchesRegularExpression( '/name="objects\[\]" value="post"/', $html );
		$this->assertSame( '0', $this->selected( $html, 'hierarchical' ) );
		$this->assertSame( '1', $this->selected( $html, 'public' ) );
		$this->assertSame( '1', $this->selected( $html, 'show_in_rest' ) );
		$this->assertSame( '0', $this->selected( $html, 'rewrite' ) );
		$this->assertSame( ', ', $this->value( $html, 'st_sep' ) );
		$this->assertSame( '', $this->value( $html, 'labels-name' ) );
		$this->assertSame( 'Search Categories', $this->value( $html, 'labels-search_items' ) );
		$this->assertSame( 'No term', $this->value( $html, 'labels-no_term' ) );
		$this->assertSame( 'manage_categories', $this->value( $html, 'manage_terms' ) );
		$this->assertSame( 'edit_posts', $this->value( $html, 'assign_terms' ) );
		$this->assertSame( '', $this->value( $html, 'rest_base' ) );

		// Term Count: the options (no own function), Standard chosen. Term Control: none.
		$this->assertMatchesRegularExpression( '/<span id="count_tab_0" class="is-hidden">/', $html );
		$this->assertMatchesRegularExpression( '/<span id="count_tab_1" >/', $html );
		$this->assertTrue( $this->is_checked( $html, 'cb_std' ) );
		$this->assertMatchesRegularExpression( '/<span id="count_sel_1" class="is-hidden">/', $html );
		$this->assertTrue( $this->is_checked( $html, 'cc_off' ) );
		$this->assertMatchesRegularExpression( '/<span id="control_tab_1" class="is-hidden">/', $html );
		$this->assertStringNotContainsString( 'id="st_cb_override"', $html, 'The count override is for external taxonomies' );

		// Add is enabled by the script once a name is entered.
		$this->assertMatchesRegularExpression( '/id="submit"[^>]*\sdisabled>Add taxonomy/', $html );
		$this->assertStringContainsString( 'hideSel(evt, 0)', $html );
	}

	/**
	 * Editing a custom taxonomy: its name is fixed and its settings are shown.
	 */
	public function test_edit_form() {
		$html = $this->edit_form( 'test_hier' );

		$this->assertStringContainsString( '<h1 class="wp-heading-_">Custom Taxonomy : Test Terms</h1>', $html );
		$this->assertStringContainsString( '<input type="hidden" name="action" value="merge-taxonomy" />', $html );
		$this->assertStringContainsString( 'name="_wpnonce" value="' . wp_create_nonce( 'staxo_edit_taxo' ) . '"', $html );
		foreach ( self::$tabs as $tab ) {
			$this->assertStringContainsString( 'aria-controls="' . $tab . '"', $html, "Tab $tab" );
		}

		$this->assertSame( 'test_hier', $this->value( $html, 'name' ) );
		$this->assertStringContainsString( 'readonly="readonly"', $this->input( $html, 'name' ), 'The name cannot be changed here' );
		$this->assertSame( '1', $this->selected( $html, 'hierarchical' ) );
		$this->assertSame( 'content', $this->selected( $html, 'auto' ) );
		$this->assertStringContainsString( "<option  selected='selected' value=\"0\">EP_NONE</option>", $html, 'No EP_MASK' );
		$this->assertMatchesRegularExpression( '/aria-checked="true"[^>]*name="objects\[\]" value="post"/', $html );
		$this->assertMatchesRegularExpression( '/aria-checked="false"[^>]*name="objects\[\]" value="page"/', $html );
		$this->assertSame( 'Test Terms', $this->value( $html, 'labels-name' ) );
		$this->assertSame( 'Test Term', $this->value( $html, 'labels-singular_name' ) );
		$this->assertSame( '', $this->value( $html, 'labels-menu_name' ), 'Menu name is kept for editing' );
		$this->assertSame( 'test_hier', $this->value( $html, 'query_var' ) );
		$this->assertMatchesRegularExpression( '#Current value:\s*<span id="show_ui_2">True</span>#', $html, 'Term Control tab shows Display on admin' );

		$this->assertMatchesRegularExpression( '/id="submit"[^>]*>Update taxonomy/', $html );
		$this->assertDoesNotMatchRegularExpression( '/id="submit"[^>]*disabled/', $html );
	}

	/**
	 * A flat taxonomy for posts and pages with its own REST base and a default term.
	 */
	public function test_edit_form_rest_and_default_term() {
		$html = $this->edit_form( 'test_flat' );

		$this->assertStringContainsString( 'Custom Taxonomy : Flat Terms', $html );
		$this->assertSame( '0', $this->selected( $html, 'hierarchical' ) );
		$this->assertMatchesRegularExpression( '/aria-checked="true"[^>]*name="objects\[\]" value="page"/', $html );
		$this->assertSame( 'flat-terms', $this->value( $html, 'rest_base' ) );
		$this->assertSame( 'Unsorted', $this->value( $html, 'st_dft_name' ) );
		$this->assertSame( 'Flat Term', $this->value( $html, 'labels-singular_name' ) );
	}

	/**
	 * Term Control settings: Any (Except Trash), checked when the post is saved, 1 to 2 terms.
	 */
	public function test_edit_form_term_control() {
		$html = $this->edit_form( 'test_cntl' );

		$this->assertTrue( $this->is_checked( $html, 'cc_any' ) );
		$this->assertFalse( $this->is_checked( $html, 'cc_off' ) );
		$this->assertTrue( $this->is_checked( $html, 'cc_sft' ) );
		$this->assertMatchesRegularExpression( '/<span id="control_tab_0" class="is-hidden">/', $html );
		$this->assertMatchesRegularExpression( '/<span id="control_tab_1" >/', $html );
		$this->assertSame( '1', $this->selected( $html, 'st_cc_umin' ) );
		$this->assertSame( '1', $this->selected( $html, 'st_cc_umax' ) );
		$this->assertSame( '1', $this->value( $html, 'st_cc_min' ) );
		$this->assertSame( '2', $this->value( $html, 'st_cc_max' ) );
		$this->assertStringContainsString( 'ccSel(evt, 2)', $html );
		$this->assertStringContainsString( 'cchSel(evt, 1)', $html );
	}

	/**
	 * Term Count settings: a selection of statuses (Publish and Draft).
	 */
	public function test_edit_form_term_count() {
		$html = $this->edit_form( 'test_count' );

		$this->assertTrue( $this->is_checked( $html, 'cb_sel' ) );
		$this->assertFalse( $this->is_checked( $html, 'cb_std' ) );
		$this->assertMatchesRegularExpression( '/<span id="count_sel_0" class="is-hidden">/', $html );
		$this->assertMatchesRegularExpression( '/<span id="count_sel_1" >/', $html );
		$statuses = array(
			'st_cb_pub' => true,
			'st_cb_fut' => false,
			'st_cb_dft' => true,
			'st_cb_pnd' => false,
			'st_cb_prv' => false,
			'st_cb_tsh' => false,
		);
		foreach ( $statuses as $id => $ticked ) {
			$this->assertSame( $ticked, $this->is_checked( $html, $id ), $id );
			$this->assertStringContainsString( 'aria-checked="' . ( $ticked ? 'true' : 'false' ) . '"', $this->input( $html, $id ), "$id: aria-checked" );
		}
		$this->assertStringContainsString( 'hideSel(evt, 2)', $html );
	}

	/**
	 * A custom taxonomy with its own count function: the Term Count options are not offered.
	 */
	public function test_edit_form_own_count_function() {
		$this->store(
			'test_count',
			array(
				'st_update_count_callback' => '_update_generic_term_count',
				'update_count_callback'    => '_update_generic_term_count',
			)
		);
		$html = $this->edit_form( 'test_count' );

		$this->assertSame( '_update_generic_term_count', $this->value( $html, 'st_update_count_callback' ) );
		$this->assertStringContainsString( 'A function has been defined for calculating term counts. This function is therefore not available.', $html );
		$this->assertMatchesRegularExpression( '/<span id="count_tab_0" >/', $html );
		$this->assertMatchesRegularExpression( '/<span id="count_tab_1" class="is-hidden">/', $html );
		$this->assertStringNotContainsString( 'id="st_cb_override"', $html );
	}

	/**
	 * Settings held in the register_taxonomy() arguments are shown in their own fields:
	 * the rewrite slug and options, and the WPGraphQL names.
	 */
	public function test_edit_form_rewrite_and_graphql() {
		$this->store(
			'test_hier',
			array(
				'rewrite'            => '1',
				'st_slug'            => 'music',
				'st_with_front'      => '0',
				'st_hierarchical'    => '1',
				'st_show_in_graphql' => '1',
				'st_graphql_single'  => 'testTerm',
				'st_graphql_plural'  => 'testTerms',
			)
		);
		$html = $this->edit_form( 'test_hier' );

		$this->assertSame( '1', $this->selected( $html, 'rewrite' ) );
		$this->assertSame( 'music', $this->value( $html, 'st_slug' ) );
		$this->assertSame( '0', $this->selected( $html, 'st_with_front' ) );
		$this->assertSame( '1', $this->selected( $html, 'st_hierarchical' ) );
		$this->assertSame( '1', $this->selected( $html, 'st_show_in_graphql' ) );
		$this->assertSame( 'testTerm', $this->value( $html, 'st_graphql_single' ) );
		$this->assertSame( 'testTerms', $this->value( $html, 'st_graphql_plural' ) );
	}

	/**
	 * Settings saved by older versions lack the fields added since: the form fills them with defaults.
	 */
	public function test_edit_form_older_settings() {
		$added = array(
			// 1.0 (display texts and register_taxonomy() code fields).
			'st_before',
			'st_after',
			'st_slug',
			'st_with_front',
			'st_hierarchical',
			'st_ep_mask',
			'st_update_count_callback',
			'st_meta_box_cb',
			'st_meta_box_sanitize_cb',
			'st_args',
			// 1.1 (admin list filter and term count).
			'st_adm_types',
			'st_adm_hier',
			'st_adm_depth',
			'st_adm_count',
			'st_adm_h_e',
			'st_adm_h_i_e',
			'st_cb_type',
			'st_cb_pub',
			'st_cb_fut',
			'st_cb_dft',
			'st_cb_pnd',
			'st_cb_prv',
			'st_cb_tsh',
			// 1.2 (term control and default term).
			'st_cc_type',
			'st_cc_hard',
			'st_cc_umin',
			'st_cc_umax',
			'st_cc_min',
			'st_cc_max',
			'st_dft_name',
			'st_dft_slug',
			'st_dft_desc',
			// 2.0, 2.4, 3.1.
			'st_feed',
			'rest_namespace',
			'st_sep',
			'st_cc_types',
		);
		$this->store( 'test_cntl', array(), $added );
		$html = $this->edit_form( 'test_cntl' );

		$this->assertStringContainsString( 'Custom Taxonomy : Control Terms', $html );
		$this->assertSame( '', $this->value( $html, 'st_before' ) );
		$this->assertSame( '', $this->value( $html, 'st_sep' ) );
		$this->assertSame( '', $this->value( $html, 'st_slug' ) );
		$this->assertSame( '', $this->value( $html, 'st_update_count_callback' ) );
		$this->assertSame( '', $this->value( $html, 'st_dft_name' ) );
		$this->assertSame( '0', $this->selected( $html, 'st_feed' ) );
		$this->assertTrue( $this->is_checked( $html, 'cb_std' ) );
		$this->assertTrue( $this->is_checked( $html, 'cc_off' ), 'No Term Control in the older settings' );
		$this->assertSame( '0', $this->value( $html, 'st_cc_min' ) );
		$this->assertSame( '0', $this->value( $html, 'st_adm_depth' ) );
	}

	/**
	 * Callback fields are editable by users who may change code settings, and read-only for others.
	 */
	public function test_callback_fields_read_only() {
		$this->store( 'test_hier', array( 'st_meta_box_cb' => 'post_categories_meta_box' ) );
		$notice = 'Read-only: you do not have permission to change callback settings.';

		$html = $this->edit_form( 'test_hier' );
		foreach ( self::$callback_fields as $field ) {
			$this->assertStringNotContainsString( 'readonly', $this->input( $html, $field ), "$field: editable" );
		}
		$this->assertStringNotContainsString( $notice, $html );

		add_filter( 'staxo_can_edit_callbacks', '__return_false' );
		$html = $this->edit_form( 'test_hier' );
		foreach ( self::$callback_fields as $field ) {
			$this->assertStringContainsString( 'readonly="readonly" aria-readonly="true"', $this->input( $html, $field ), "$field: read-only" );
		}
		$this->assertSame( count( self::$callback_fields ), substr_count( $html, $notice ) );
		$this->assertSame( 'post_categories_meta_box', $this->value( $html, 'st_meta_box_cb' ), 'The stored value is still shown' );
		$this->assertStringNotContainsString( 'readonly', $this->input( $html, 'rest_base' ), 'Other fields stay editable' );

		// The add form too.
		$html = $this->render( array( 'action' => 'add' ) );
		$this->assertSame( count( self::$callback_fields ), substr_count( $html, $notice ) );
	}

	/**
	 * The edit link for a name held neither by the plugin nor WordPress stops; action=edit without a name shows the list.
	 */
	public function test_edit_unknown() {
		$this->assertStringContainsString( 'id="the-list-custom"', $this->render( array( 'action' => 'edit' ) ) );

		$this->expectException( 'WPDieException' );
		$this->edit_form( 'no_such_taxonomy' );
	}
}
