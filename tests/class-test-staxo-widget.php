<?php
/**
 * Tests for the Simple Taxonomy Widget: settings form, registration, taxonomy list and output options.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * SimpleTaxonomyRefreshed_Widget beyond the front-end output tested in class I.
 *
 * The plugin keeps its widget in the global $strw; the form tests use a new instance.
 *
 * @group widget
 */
class Test_STaxo_Widget extends STaxo_Test_Case {

	/**
	 * Name of the Taxonomy Cloud block.
	 */
	const BLOCK = 'simple-taxonomy-refreshed/cloud-widget';

	/**
	 * Log in an administrator and load the fixture.
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
	 * Remove the title filter.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_all_filters( 'staxo_widget_title' );
		parent::tear_down();
	}

	/**
	 * A new widget, set up as WordPress does on widgets_init, as widget number 2.
	 *
	 * @return SimpleTaxonomyRefreshed_Widget
	 */
	private function new_widget() {
		$widget = new SimpleTaxonomyRefreshed_Widget();
		$widget->str_widgets_init();
		$widget->_set( 2 );

		return $widget;
	}

	/**
	 * The settings form.
	 *
	 * @param array $instance widget settings.
	 * @return string HTML.
	 */
	private function form( $instance ) {
		wp_cache_delete( 'staxo_taxonomies' );
		ob_start();
		$result = $this->new_widget()->form( $instance );
		$html   = ob_get_clean();
		$this->assertSame( '', $result, 'form() returns an empty string so the Save button is shown' );

		return $html;
	}

	/**
	 * The value chosen in one of the form's selects.
	 *
	 * @param string $html  form HTML.
	 * @param string $field field name.
	 * @return string|null
	 */
	private function chosen( $html, $field ) {
		$this->assertMatchesRegularExpression( '#<select id="widget-staxonomy-2-' . $field . '".*?</select>#s', $html, "No select $field" );
		preg_match( '#<select id="widget-staxonomy-2-' . $field . '".*?</select>#s', $html, $select );
		return ( preg_match( "/selected='selected' value=\"([^\"]*)\"/", $select[0], $match ) ? $match[1] : null );
	}

	/**
	 * Render the plugin's widget.
	 *
	 * @param array $instance widget settings.
	 * @param array $args     widget area arguments.
	 * @return string
	 */
	private function render( $instance, $args = array() ) {
		global $strw;
		ob_start();
		$strw->widget( $args, $instance );
		return ob_get_clean();
	}

	/**
	 * Settings for a widget on test_hier.
	 *
	 * @param array $settings settings to add or replace.
	 * @return array
	 */
	private function instance( $settings = array() ) {
		return array_merge(
			array(
				'title'     => 'Genres',
				'taxonomy'  => 'test_hier',
				'disptype'  => 'list',
				'orderby'   => 'name',
				'order'     => 'ASC',
				'numdisp'   => 0,
				'minposts'  => 0,
				'showcount' => true,
			),
			$settings
		);
	}

	/**
	 * Registration as a WordPress widget.
	 */
	public function test_widget_registration() {
		$widget = $this->new_widget();

		$this->assertSame( 'staxonomy', $widget->id_base );
		$this->assertSame( 'Simple Taxonomy Widget', $widget->name );
		$this->assertSame( 'simpletaxonomyrefreshed-widget', $widget->widget_options['classname'] );
		$this->assertTrue( $widget->widget_options['show_instance_in_rest'] );
		$this->assertSame( 'staxonomy-2', $widget->id );

		// The plugin registers its widget on widgets_init, and hides it from the Legacy Widget block.
		global $strw, $wp_widget_factory;
		init_staxo_widget();
		$this->assertContains( $strw, $wp_widget_factory->widgets );
		$this->assertSame( array( 'archives', 'staxonomy' ), hide_staxonomy_widget( array( 'archives' ) ) );
	}

	/**
	 * The form with the default settings.
	 */
	public function test_form_defaults() {
		$html = $this->form( array() );

		$this->assertStringContainsString( '<input id="widget-staxonomy-2-title" name="widget-staxonomy[2][title]" value="Advanced Taxonomy Cloud" class="widefat" />', $html );
		$this->assertSame( 'post_tag', $this->chosen( $html, 'taxonomy' ) );
		$this->assertStringContainsString( 'value="test_hier">Test Terms</option>', $html, 'Public taxonomies are offered' );
		$this->assertSame( 'cloud', $this->chosen( $html, 'disptype' ) );
		$this->assertSame( 'justify', $this->chosen( $html, 'alignment' ) );
		$this->assertSame( 'count', $this->chosen( $html, 'orderby' ) );
		$this->assertSame( 'DESC', $this->chosen( $html, 'order' ) );
		$this->assertMatchesRegularExpression( '#id="widget-staxonomy-2-showcount" name="widget-staxonomy\[2\]\[showcount\]"\s+checked=\'checked\'#', $html );
		$this->assertStringContainsString( 'name="widget-staxonomy[2][small]" type="number" value="50"', $html );
		$this->assertStringContainsString( 'name="widget-staxonomy[2][big]" type="number" value="150"', $html );
		$this->assertStringContainsString( 'name="widget-staxonomy[2][numdisp]" type="number" value="45"', $html );
		$this->assertStringContainsString( 'name="widget-staxonomy[2][minposts]" type="number" value="0"', $html );
		$this->assertStringNotContainsString( '&#039;selected&#039;', $html, 'The chosen options are marked as plain HTML' );
	}

	/**
	 * The form shows saved settings; an unknown taxonomy is shown as tags.
	 */
	public function test_form_saved_settings() {
		$html = $this->form(
			array(
				'title'     => 'My "terms"',
				'taxonomy'  => 'test_hier',
				'disptype'  => 'list',
				'alignment' => 'center',
				'orderby'   => 'name',
				'order'     => 'RAND',
				'showcount' => false,
				'numdisp'   => 10,
				'minposts'  => 2,
			)
		);

		$this->assertStringContainsString( 'name="widget-staxonomy[2][title]" value="My &quot;terms&quot;"', $html );
		$this->assertSame( 'test_hier', $this->chosen( $html, 'taxonomy' ) );
		$this->assertSame( 'list', $this->chosen( $html, 'disptype' ) );
		$this->assertSame( 'center', $this->chosen( $html, 'alignment' ) );
		$this->assertSame( 'name', $this->chosen( $html, 'orderby' ) );
		$this->assertSame( 'RAND', $this->chosen( $html, 'order' ) );
		$this->assertDoesNotMatchRegularExpression( '#id="widget-staxonomy-2-showcount"[^>]*checked#', $html );
		$this->assertStringContainsString( 'name="widget-staxonomy[2][numdisp]" type="number" value="10"', $html );
		$this->assertStringContainsString( 'name="widget-staxonomy[2][minposts]" type="number" value="2"', $html );

		$this->assertSame( 'post_tag', $this->chosen( $this->form( array( 'taxonomy' => 'no_such_taxo' ) ), 'taxonomy' ) );
	}

	/**
	 * The taxonomies offered: public ones, by label, cached.
	 */
	public function test_get_taxonomies() {
		global $strw;
		wp_cache_delete( 'staxo_taxonomies' );

		$taxonomies = $strw->get_taxonomies();
		$this->assertSame( 'Test Terms', $taxonomies['test_hier'] );
		$this->assertSame( 'Categories', $taxonomies['category'] );
		$this->assertArrayNotHasKey( 'nav_menu', $taxonomies, 'Not public' );
		$sorted = $taxonomies;
		asort( $sorted );
		$this->assertSame( $sorted, $taxonomies, 'Sorted by label' );
		$this->assertSame( $taxonomies, wp_cache_get( 'staxo_taxonomies' ) );

		wp_cache_set( 'staxo_taxonomies', array( 'cached' => 'From cache' ) );
		$this->assertSame( array( 'cached' => 'From cache' ), $strw->get_taxonomies() );
	}

	/**
	 * Without a title the taxonomy's name is used; the title can be filtered; the widget area markup is kept.
	 */
	public function test_title() {
		$args = array(
			'before_widget' => '<section class="area">',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
			'after_widget'  => '</section>',
		);

		$output = $this->render( $this->instance( array( 'title' => '' ) ), $args );
		$this->assertStringStartsWith( '<section class="area"><h3>Test Terms</h3>', $output );
		$this->assertStringEndsWith( '</section>', $output );

		add_filter(
			'staxo_widget_title',
			static function ( $title, $instance, $id_base ) {
				return $title . ' (' . $instance['taxonomy'] . ', ' . $id_base . ')';
			},
			10,
			3
		);
		$this->assertStringContainsString( '<h3>Genres (test_hier, staxonomy)</h3>', $this->render( $this->instance(), $args ) );

		add_filter( 'staxo_widget_title', '__return_empty_string', 20 );
		$this->assertStringNotContainsString( '<h3>', $this->render( $this->instance(), $args ), 'No title, no title markup' );
	}

	/**
	 * List items: link, accessible name with the count, and the count shown only when asked.
	 */
	public function test_list_items() {
		$output = $this->render( $this->instance() );
		$jazz   = $this->term( 'test_hier', 'Jazz' );

		$this->assertStringContainsString( '<ul class="staxo-terms-list" role="grid">', $output );
		$this->assertStringContainsString( '<li role="row"><a href="' . esc_url( get_term_link( $jazz ) ) . '" role="link" aria-label="Jazz (2 items)">Jazz</a> (2)</li>', $output );
		$this->assertStringContainsString( 'aria-label="Big Band (1 item)">Big Band</a> (1)</li>', $output );

		$output = $this->render( $this->instance( array( 'showcount' => false ) ) );
		$this->assertStringContainsString( 'aria-label="Jazz (2 items)">Jazz</a></li>', $output );
	}

	/**
	 * Number of terms, ordering by count and descending order.
	 */
	public function test_list_number_and_order() {
		$output = $this->render(
			$this->instance(
				array(
					'orderby' => 'count',
					'order'   => 'DESC',
					'numdisp' => 2,
				)
			)
		);

		$this->assertSame( 2, substr_count( $output, '<li role="row">' ) );
		$this->assertStringNotContainsString( '>Big Band</a>', $output, 'The term with fewest posts is left out' );
	}

	/**
	 * Cloud: font sizes when counts differ, the chosen alignment, and a list when the taxonomy has no tag cloud.
	 */
	public function test_cloud() {
		$output = $this->render(
			$this->instance(
				array(
					'disptype'  => 'cloud',
					'alignment' => 'center',
				)
			)
		);

		$this->assertStringContainsString( '<div class="staxo-terms-cloud" style="text-align:center;">', $output );
		$this->assertStringContainsString( 'font-size:', $output, 'Counts differ, so sizes are kept' );
		$this->assertFalse( has_filter( 'terms_clauses', array( 'SimpleTaxonomyRefreshed_Widget', 'filter_terms' ) ), 'Query filter removed afterwards' );
		$this->assertFalse( has_filter( 'wp_generate_tag_cloud', array( 'SimpleTaxonomyRefreshed_Widget', 'filter_result' ) ), 'Cloud filter removed afterwards' );

		$options = get_option( OPTION_STAXO );
		$options['taxonomies']['test_hier']['show_tagcloud'] = '0';
		update_option( OPTION_STAXO, $options );
		$this->register_taxonomies();

		$output = $this->render( $this->instance( array( 'disptype' => 'cloud' ) ) );
		$this->assertStringNotContainsString( 'staxo-terms-cloud', $output );
		$this->assertStringContainsString( 'staxo-terms-list', $output );
	}

	/**
	 * Query filter: a minimum number of posts replaces the "has posts" condition; random order.
	 */
	public function test_filter_terms() {
		$pieces = array(
			'where'   => "tt.taxonomy IN ('test_hier') AND tt.count > 0",
			'orderby' => 'ORDER BY t.name',
		);

		$this->assertSame( $pieces, SimpleTaxonomyRefreshed_Widget::filter_terms( $pieces, array( 'test_hier' ), array( 'filter_min' => 0 ) ) );
		$this->assertSame( $pieces, SimpleTaxonomyRefreshed_Widget::filter_terms( $pieces, array( 'test_hier' ), array() ) );

		$filtered = SimpleTaxonomyRefreshed_Widget::filter_terms( $pieces, array( 'test_hier' ), array( 'filter_min' => '3' ) );
		$this->assertSame( "tt.taxonomy IN ('test_hier') AND tt.count >= 3", $filtered['where'] );

		$random = SimpleTaxonomyRefreshed_Widget::filter_terms( $pieces, array( 'test_hier' ), array( 'order' => 'RAND' ) );
		$this->assertSame( 'ORDER BY RAND()', $random['orderby'] );

		// Random order in a list still shows every term with posts.
		$output = $this->render( $this->instance( array( 'order' => 'RAND' ) ) );
		foreach ( array( 'Jazz', 'Bebop', 'Big Band' ) as $name ) {
			$this->assertStringContainsString( '>' . $name . '</a>', $output, $name );
		}
	}

	/**
	 * Cloud result filter: sizes are removed only when every term has the same count.
	 */
	public function test_filter_result() {
		$html = '<a style="font-size: 150%;">A</a><a style="font-size: 50%;">B</a>';
		$same = array(
			(object) array( 'count' => 2 ),
			(object) array( 'count' => 2 ),
		);
		$diff = array(
			(object) array( 'count' => 1 ),
			(object) array( 'count' => 3 ),
		);

		$this->assertSame( '<a>A</a><a>B</a>', SimpleTaxonomyRefreshed_Widget::filter_result( $html, $same, array() ) );
		$this->assertSame( $html, SimpleTaxonomyRefreshed_Widget::filter_result( $html, $diff, array() ) );
	}

	/**
	 * Block registration: render callback, translated title and the taxonomy list for the editor script.
	 */
	public function test_block_registration() {
		if ( ! file_exists( dirname( __DIR__ ) . '/build/blocks/staxo-widget/block.json' ) ) {
			$this->markTestSkipped( 'build/blocks missing' );
		}
		global $strw;
		$registry = WP_Block_Type_Registry::get_instance();
		if ( $registry->is_registered( self::BLOCK ) ) {
			$registry->unregister( self::BLOCK );
		}
		wp_cache_delete( 'staxo_taxonomies' );

		staxo_widgets_block_init();

		$block = $registry->get_registered( self::BLOCK );
		$this->assertInstanceOf( 'WP_Block_Type', $block );
		$this->assertSame( array( $strw, 'staxo_widget_display' ), $block->render_callback );
		$this->assertSame( 'Taxonomy Cloud', $block->title );
		$this->assertSame( 'Display a Taxonomy Cloud.', $block->description );

		$script = wp_scripts()->get_data( 'simple-taxonomy-refreshed-cloud-widget-editor-script', 'before' );
		$this->assertIsArray( $script );
		$this->assertStringContainsString( 'const staxo_data = {', implode( "\n", $script ) );
		$this->assertStringContainsString( '"test_hier":"Test Terms"', implode( "\n", $script ) );

		// Other blocks' settings are left alone.
		$settings = array( 'title' => 'Other' );
		$this->assertSame( $settings, $strw->update_settings( $settings, array( 'name' => 'core/paragraph' ) ) );
	}

	/**
	 * Block rendering: with "header" the title is a level 2 heading.
	 */
	public function test_block_display_header() {
		global $strw;

		$output = $strw->staxo_widget_display(
			array(
				'title'    => 'Genres',
				'taxonomy' => 'test_hier',
				'disptype' => 'list',
				'header'   => true,
				'ordering' => 'DESC',
				'orderby'  => 'name',
			)
		);
		$this->assertStringContainsString( '<h2>Genres</h2>', $output );
		$this->assertLessThan( strpos( $output, '>Bebop</a>' ), strpos( $output, '>Jazz</a>' ), 'ordering DESC: Jazz before Bebop' );

		$output = $strw->staxo_widget_display(
			array(
				'title'    => 'Genres',
				'taxonomy' => 'test_hier',
				'disptype' => 'list',
			)
		);
		$this->assertStringNotContainsString( '<h2>', $output );
		$this->assertStringContainsString( 'Genres', $output );
	}
}
