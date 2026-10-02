<?php
/**
 * Tests for front-end output: terms with posts, shortcode, blocks, feeds, admin filter and widget.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Front-end output on the shared fixture.
 *
 * @group frontend
 */
class Test_STaxo_Front_End extends STaxo_Test_Case {

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
	 * Change settings of a fixture taxonomy.
	 *
	 * @param string $taxonomy taxonomy name.
	 * @param array  $settings settings to change.
	 * @return void
	 */
	private function set_taxonomy( $taxonomy, $settings ) {
		$options = get_option( OPTION_STAXO );
		foreach ( $settings as $key => $value ) {
			$options['taxonomies'][ $taxonomy ][ $key ] = $value;
		}
		update_option( OPTION_STAXO, $options );
	}

	/**
	 * View a single post and enter the loop, as a theme template does.
	 *
	 * @param int|string $post post id, or label from make_posts().
	 * @return void
	 */
	private function view_post( $post ) {
		$post_id = ( is_string( $post ) ? $this->posts[ $post ] : $post );
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( have_posts(), 'Post not found by the main query' );
		the_post();
	}

	/**
	 * The link get_the_term_list() makes for a term.
	 *
	 * @param string $taxonomy taxonomy name.
	 * @param string $name     term name.
	 * @return string
	 */
	private function link( $taxonomy, $name ) {
		$term = $this->term( $taxonomy, $name );

		return '<a href="' . esc_url( get_term_link( $term, $taxonomy ) ) . '" rel="tag">' . $name . '</a>';
	}

	/**
	 * Output of a callable that echoes.
	 *
	 * @param callable $callback function to run.
	 * @return string
	 */
	private function capture( $callback ) {
		ob_start();
		call_user_func( $callback );

		return ob_get_clean();
	}

	/**
	 * Render the classic widget.
	 *
	 * @param array $instance widget settings.
	 * @return string
	 */
	private function widget( $instance ) {
		global $strw;

		return $this->capture(
			static function () use ( $strw, $instance ) {
				$strw->widget( array(), $instance );
			}
		);
	}

	/**
	 * Settings for a list widget on test_hier.
	 *
	 * @param array $settings settings to add or replace.
	 * @return array
	 */
	private function list_widget( $settings = array() ) {
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
	 * Skip a test when the plugin's blocks are not registered (no build directory).
	 *
	 * @param string $name block name.
	 * @return void
	 */
	private function require_block( $name ) {
		if ( ! WP_Block_Type_Registry::get_instance()->is_registered( $name ) ) {
			$this->markTestSkipped( "Block $name is not registered (build/blocks missing?)" );
		}
	}

	/**
	 * Terms are added after the content of a single post, for taxonomies set to content or both.
	 */
	public function test_content() {
		$this->view_post( 'P1' );

		$content = SimpleTaxonomyRefreshed_Client::the_content( 'Body' );

		$this->assertStringStartsWith( 'Body<div class="simple-taxonomy">', $content );
		$this->assertStringContainsString( '<div class="taxonomy-test_hier wp-block-post-terms">Test Terms: ' . $this->link( 'test_hier', 'Jazz' ) . '</div>', $content );
		$this->assertStringContainsString( 'Flat Terms: ' . $this->link( 'test_flat', 'green' ) . ', ' . $this->link( 'test_flat', 'red' ), $content );
		$this->assertStringNotContainsString( 'taxonomy-test_cntl', $content, 'auto = none' );
		$this->assertStringNotContainsString( 'taxonomy-test_count', $content, 'auto = none' );
	}

	/**
	 * The excerpt gets the taxonomies set to excerpt or both only.
	 */
	public function test_excerpt() {
		$this->view_post( 'P1' );

		$excerpt = SimpleTaxonomyRefreshed_Client::the_excerpt( 'Summary' );

		$this->assertStringContainsString( 'taxonomy-test_flat', $excerpt );
		$this->assertStringNotContainsString( 'taxonomy-test_hier', $excerpt, 'test_hier is content only' );
	}

	/**
	 * Nothing is added outside a single post's main loop.
	 */
	public function test_content_only_in_single_loop() {
		$this->go_to( home_url( '/' ) );
		$this->assertSame( 'Body', SimpleTaxonomyRefreshed_Client::the_content( 'Body' ), 'Not singular' );

		$this->go_to( get_permalink( $this->posts['P1'] ) );
		$this->assertSame( 'Body', SimpleTaxonomyRefreshed_Client::the_content( 'Body' ), 'Not in the loop' );
	}

	/**
	 * On a page only the taxonomies used by pages are shown.
	 */
	public function test_content_on_page() {
		$this->view_post( 'PG1' );

		$content = SimpleTaxonomyRefreshed_Client::the_content( '' );

		$this->assertStringContainsString( 'Flat Terms: ' . $this->link( 'test_flat', 'red' ), $content );
		$this->assertStringNotContainsString( 'taxonomy-test_hier', $content );
	}

	/**
	 * A post without terms gets an HTML comment instead.
	 */
	public function test_content_without_terms() {
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$this->view_post( $post_id );

		$content = SimpleTaxonomyRefreshed_Client::the_content( '' );

		$this->assertStringContainsString( '<!-- Taxonomy : test_hier : No categories found. -->', $content );
	}

	/**
	 * Plain-text before, separator and after: spaces added at the joins, text escaped.
	 */
	public function test_plain_text_display() {
		$this->set_taxonomy(
			'test_flat',
			array(
				'st_before' => 'Reds & Greens',
				'st_sep'    => ' | ',
				'st_after'  => 'end',
			)
		);
		$this->view_post( 'P1' );

		$content = SimpleTaxonomyRefreshed_Client::the_content( '' );

		$this->assertStringContainsString( 'Reds &amp; Greens ' . $this->link( 'test_flat', 'green' ) . ' | ' . $this->link( 'test_flat', 'red' ) . ' end</div>', $content );
	}

	/**
	 * A plain-text separator with spaces at the ends is still plain text (the fixture uses ", ").
	 */
	public function test_plain_text_keeps_space_after_before_text() {
		$this->view_post( 'P1' );

		$this->assertStringContainsString( 'Test Terms: <a', SimpleTaxonomyRefreshed_Client::the_content( '' ) );
	}

	/**
	 * HTML before, separator and after are used as they are, limited to post HTML.
	 */
	public function test_html_display() {
		$this->set_taxonomy(
			'test_flat',
			array(
				'st_before' => '<strong>Colours:</strong>',
				'st_sep'    => ' / ',
				'st_after'  => '<script>alert(1)</script>',
			)
		);
		$this->view_post( 'P1' );

		$content = SimpleTaxonomyRefreshed_Client::the_content( '' );

		$this->assertStringContainsString( '<strong>Colours:</strong>' . $this->link( 'test_flat', 'green' ) . ' / ' . $this->link( 'test_flat', 'red' ), $content );
		$this->assertStringNotContainsString( '<script', $content );
	}

	/**
	 * The shortcode shows one taxonomy's terms, whatever its display setting.
	 */
	public function test_shortcode() {
		$this->view_post( 'P3' );

		$output = do_shortcode( '[staxo_post_terms tax="test_cntl"]' );

		$this->assertStringContainsString( '<div class="taxonomy-test_cntl wp-block-post-terms">' . $this->link( 'test_cntl', 'blue' ) . ', ' . $this->link( 'test_cntl', 'red' ) . '</div>', $output );
		$this->assertStringNotContainsString( 'taxonomy-test_hier', $output );
	}

	/**
	 * The shortcode outputs nothing for an unknown taxonomy, or outside a single post's loop.
	 */
	public function test_shortcode_empty_cases() {
		$this->view_post( 'P1' );
		$this->assertSame( '', do_shortcode( '[staxo_post_terms tax="no_such_taxo"]' ) );

		$this->go_to( home_url( '/' ) );
		$this->assertSame( '', do_shortcode( '[staxo_post_terms tax="test_flat"]' ) );
	}

	/**
	 * The Display Post Terms block shows the chosen taxonomy inside the block wrapper.
	 */
	public function test_terms_block() {
		$this->require_block( 'simple-taxonomy-refreshed/staxo-terms' );
		$this->view_post( 'P1' );

		$output = do_blocks( '<!-- wp:simple-taxonomy-refreshed/staxo-terms {"tax":"test_flat"} /-->' );

		$this->assertStringContainsString( 'wp-block-simple-taxonomy-refreshed-staxo-terms', $output );
		$this->assertStringContainsString( $this->link( 'test_flat', 'red' ), $output );
		$this->assertStringNotContainsString( 'taxonomy-test_hier', $output );
	}

	/**
	 * Feeds include the terms of taxonomies set to show in feeds, in each feed format.
	 */
	public function test_feed() {
		$this->view_post( 'P1' );
		$this->assertSame( '', SimpleTaxonomyRefreshed_Client::the_category_feed( '', 'rss2' ), 'No taxonomy is set to show in feeds' );

		$this->set_taxonomy( 'test_flat', array( 'st_feed' => '1' ) );

		$rss = SimpleTaxonomyRefreshed_Client::the_category_feed( '<category>x</category>', 'rss2' );
		$this->assertStringStartsWith( '<category>x</category>', $rss );
		$this->assertStringContainsString( '<test_flat><![CDATA[green]]></test_flat>', $rss );
		$this->assertStringContainsString( '<test_flat><![CDATA[red]]></test_flat>', $rss );

		$atom = SimpleTaxonomyRefreshed_Client::the_category_feed( '', 'atom' );
		$this->assertStringContainsString( 'term="green" />', $atom );
		$this->assertStringContainsString( '<test_flat scheme="', $atom );

		$rdf = SimpleTaxonomyRefreshed_Client::the_category_feed( '', 'rdf' );
		$this->assertStringContainsString( '<dc:subject><![CDATA[red]]></dc:subject>', $rdf );
	}

	/**
	 * The admin list filter dropdown is added for the selected post types.
	 */
	public function test_admin_filter() {
		$this->set_taxonomy( 'test_hier', array( 'st_adm_types' => array( 'post' ) ) );

		$post_list = $this->capture(
			static function () {
				SimpleTaxonomyRefreshed_Client::manage_filters( 'post' );
			}
		);
		$this->assertStringContainsString( "name='test_hier'", $post_list );
		$this->assertStringContainsString( '>All Categories</option>', $post_list );
		$this->assertStringContainsString( 'value="jazz"', $post_list );
		$this->assertStringContainsString( 'value="misc"', $post_list, 'Empty terms shown (hide empty is off)' );

		$page_list = $this->capture(
			static function () {
				SimpleTaxonomyRefreshed_Client::manage_filters( 'page' );
			}
		);
		$this->assertSame( '', $page_list );
	}

	/**
	 * An external taxonomy that is not registered does not break the admin filter.
	 */
	public function test_admin_filter_unregistered_external() {
		$options              = get_option( OPTION_STAXO );
		$options['externals'] = array(
			'not_there' => array(
				'name'         => 'not_there',
				'st_adm_types' => array( 'post' ),
			),
		);
		update_option( OPTION_STAXO, $options );

		$output = $this->capture(
			static function () {
				SimpleTaxonomyRefreshed_Client::manage_filters( 'post' );
			}
		);
		$this->assertStringNotContainsString( 'not_there', $output );
	}

	/**
	 * Widget as a list: terms with posts, with counts.
	 */
	public function test_widget_list() {
		$output = $this->widget( $this->list_widget() );

		$this->assertStringContainsString( 'Genres', $output );
		$this->assertStringContainsString( '>Jazz</a> (2)', $output );
		$this->assertStringContainsString( '>Big Band</a> (1)', $output );
		$this->assertStringNotContainsString( '>Misc</a>', $output, 'Terms without posts are not listed' );
	}

	/**
	 * Widget minimum number of posts, including values that are not numbers (review finding M3).
	 */
	public function test_widget_minimum_posts() {
		$output = $this->widget( $this->list_widget( array( 'minposts' => 2 ) ) );
		$this->assertStringContainsString( '>Jazz</a>', $output );
		$this->assertStringContainsString( '>Bebop</a>', $output );
		$this->assertStringNotContainsString( '>Big Band</a>', $output );

		$injected = $this->widget( $this->list_widget( array( 'minposts' => '2 OR 1=1' ) ) );
		$this->assertSame( $output, $injected, 'Only the number is used' );

		$zero = $this->widget( $this->list_widget( array( 'minposts' => '0 OR 1=1' ) ) );
		$this->assertStringContainsString( '>Big Band</a>', $zero );
		$this->assertStringNotContainsString( '>Misc</a>', $zero );
	}

	/**
	 * Widget as a cloud: minimum posts applied, and equal counts give no font sizes.
	 */
	public function test_widget_cloud() {
		$output = $this->widget(
			$this->list_widget(
				array(
					'disptype' => 'cloud',
					'minposts' => 2,
				)
			)
		);

		$this->assertStringContainsString( 'staxo-terms-cloud', $output );
		$this->assertStringContainsString( '>Jazz', $output );
		$this->assertStringNotContainsString( '>Big Band', $output );
		$this->assertStringNotContainsString( 'font-size', $output, 'Jazz and Bebop have the same count' );
	}

	/**
	 * Widget title is escaped; an unknown taxonomy falls back to tags.
	 */
	public function test_widget_title_and_fallback() {
		$output = $this->widget(
			$this->list_widget(
				array(
					'title'    => '<b>Mine</b>',
					'taxonomy' => 'no_such_taxo',
				)
			)
		);

		$this->assertStringContainsString( '&lt;b&gt;Mine&lt;/b&gt;', $output );
		$this->assertStringContainsString( 'No terms available for this taxonomy.', $output );
	}

	/**
	 * Widget settings are sanitised when saved.
	 */
	public function test_widget_update() {
		global $strw;

		$saved = $strw->update(
			array(
				'title'    => '<script>alert(1)</script>Genres',
				'minposts' => '3 OR 1=1',
				'numdisp'  => '-5',
			),
			array()
		);

		$this->assertSame( 'Genres', $saved['title'] );
		$this->assertSame( 3, $saved['minposts'] );
		$this->assertSame( 5, $saved['numdisp'] );
		$this->assertFalse( $saved['showcount'] );
	}

	/**
	 * The Taxonomy Cloud block renders the widget inside the block wrapper.
	 */
	public function test_widget_block() {
		$this->require_block( 'simple-taxonomy-refreshed/cloud-widget' );

		$output = do_blocks( '<!-- wp:simple-taxonomy-refreshed/cloud-widget {"taxonomy":"test_hier","disptype":"list","minposts":2} /-->' );

		$this->assertStringContainsString( 'wp-block-simple-taxonomy-refreshed-cloud-widget', $output );
		$this->assertStringContainsString( '>Jazz</a>', $output );
		$this->assertStringNotContainsString( '>Big Band</a>', $output );
	}

	/**
	 * The block's ordering attribute is used as the order (the block names it "ordering").
	 */
	public function test_widget_block_ordering() {
		$this->require_block( 'simple-taxonomy-refreshed/cloud-widget' );

		$block = '<!-- wp:simple-taxonomy-refreshed/cloud-widget {"taxonomy":"test_hier","disptype":"list","orderby":"name","ordering":"%s"} /-->';

		$asc = do_blocks( sprintf( $block, 'ASC' ) );
		$this->assertLessThan( strpos( $asc, '>Jazz</a>' ), strpos( $asc, '>Bebop</a>' ), 'Ascending: Bebop before Jazz' );

		$desc = do_blocks( sprintf( $block, 'DESC' ) );
		$this->assertLessThan( strpos( $desc, '>Bebop</a>' ), strpos( $desc, '>Jazz</a>' ), 'Descending: Jazz before Bebop' );
	}

	/**
	 * The block supports both blocks declare (the plugin's standard list).
	 *
	 * @return array
	 */
	private static function standard_supports() {
		return array(
			'align'      => true,
			'color'      => array( 'gradients' => true ),
			'spacing'    => array(
				'margin'  => true,
				'padding' => true,
			),
			'typography' => array(
				'fontSize'   => true,
				'lineHeight' => true,
			),
		);
	}

	/**
	 * Render one of the plugin's blocks with display settings for each standard support.
	 *
	 * @param string $name     block name.
	 * @param array  $attrs    the block's own attributes.
	 * @param array  $settings display (support) attributes.
	 * @return string the block's opening tag.
	 */
	private function render_with_supports( $name, $attrs, $settings ) {
		$output = ltrim( do_blocks( '<!-- wp:' . $name . ' ' . wp_json_encode( array_merge( $attrs, $settings ) ) . ' /-->' ) );

		$class = 'wp-block-' . str_replace( '/', '-', $name );
		$this->assertSame( 1, substr_count( $output, $class ), "$name: one wrapper" );
		$this->assertStringStartsWith( '<div ', $output, "$name: output starts with the wrapper" );

		return substr( $output, 0, strpos( $output, '>' ) + 1 );
	}

	/**
	 * Settings for each standard support, and what each one adds to the wrapper.
	 *
	 * @return array[] each: array( attributes, expected strings ).
	 */
	private static function support_cases() {
		return array(
			'presets'          => array(
				array(
					'align'           => 'wide',
					'backgroundColor' => 'staxo-bg',
					'textColor'       => 'staxo-text',
					'fontSize'        => 'staxo-size',
				),
				array( 'alignwide', 'has-staxo-bg-background-color', 'has-background', 'has-staxo-text-color', 'has-text-color', 'has-staxo-size-font-size' ),
			),
			'gradient, custom' => array(
				array(
					'gradient' => 'staxo-grad',
					'style'    => array(
						'color'      => array( 'text' => '#123456' ),
						'spacing'    => array(
							'margin'  => array( 'top' => '11px' ),
							'padding' => array( 'left' => '12px' ),
						),
						'typography' => array( 'lineHeight' => '1.7' ),
					),
				),
				array( 'has-staxo-grad-gradient-background', 'color:#123456', 'margin-top:11px', 'padding-left:12px', 'line-height:1.7' ),
			),
		);
	}

	/**
	 * Both blocks are registered with the standard supports list.
	 */
	public function test_block_supports_registered() {
		foreach ( array( 'simple-taxonomy-refreshed/staxo-terms', 'simple-taxonomy-refreshed/cloud-widget' ) as $name ) {
			$this->require_block( $name );
			$supports = WP_Block_Type_Registry::get_instance()->get_registered( $name )->supports;

			foreach ( self::standard_supports() as $support => $value ) {
				$this->assertArrayHasKey( $support, $supports, "$name: $support" );
				$this->assertEquals( $value, $supports[ $support ], "$name: $support" );
			}
		}
	}

	/**
	 * Display Post Terms: each standard support is applied to the block's wrapper.
	 */
	public function test_terms_block_supports() {
		$name = 'simple-taxonomy-refreshed/staxo-terms';
		$this->require_block( $name );
		$this->view_post( 'P1' );

		foreach ( self::support_cases() as $label => $case ) {
			$wrapper = $this->render_with_supports( $name, array( 'tax' => 'test_flat' ), $case[0] );
			foreach ( $case[1] as $expected ) {
				$this->assertStringContainsString( $expected, $wrapper, "$label: $expected" );
			}
		}
	}

	/**
	 * Taxonomy Cloud: each standard support is applied to the block's wrapper.
	 */
	public function test_widget_block_supports() {
		$name = 'simple-taxonomy-refreshed/cloud-widget';
		$this->require_block( $name );

		$attrs = array(
			'taxonomy' => 'test_hier',
			'disptype' => 'list',
		);
		foreach ( self::support_cases() as $label => $case ) {
			$wrapper = $this->render_with_supports( $name, $attrs, $case[0] );
			foreach ( $case[1] as $expected ) {
				$this->assertStringContainsString( $expected, $wrapper, "$label: $expected" );
			}
		}
	}

	/**
	 * The block display functions can be called outside a block render: no wrapper div, no error.
	 */
	public function test_block_display_outside_block() {
		global $strw;

		$this->assertSame( '', SimpleTaxonomyRefreshed_Client::get_block_attributes() );

		$widget = $strw->staxo_widget_display(
			array(
				'taxonomy' => 'test_hier',
				'disptype' => 'list',
			)
		);
		$this->assertStringStartsNotWith( '<div', $widget, 'No wrapper div outside a block' );
		$this->assertStringContainsString( '>Jazz</a>', $widget, 'Works without the ordering attribute' );

		$this->view_post( 'P1' );
		$terms = SimpleTaxonomyRefreshed_Client::block_terms( array( 'tax' => 'test_flat' ), '' );
		$this->assertStringStartsWith( '<div class="simple-taxonomy">', $terms, 'No wrapper div outside a block' );
		$this->assertStringContainsString( 'taxonomy-test_flat', $terms );
	}

	/**
	 * While another plugin's dynamic block renders (as core/post-content does when it runs a shortcode), no wrapper attributes are taken.
	 */
	public function test_block_attributes_only_for_own_blocks() {
		register_block_type(
			'staxo-test/outer',
			array(
				'render_callback' => static function () {
					return '[' . SimpleTaxonomyRefreshed_Client::get_block_attributes() . ']';
				},
			)
		);

		$output = do_blocks( '<!-- wp:staxo-test/outer /-->' );
		unregister_block_type( 'staxo-test/outer' );

		$this->assertSame( '[]', $output );
	}
}
