<?php
/**
 * Simple Taxonomy Widget class file.
 *
 * @package simple-taxonomy-refreshed
 * @author Neil James/Amaury Balmer
 */

/**
 * Class to provide a widget for custom taxonomy (tag cloud or list)
 *
 * @package simple-taxonomy-refreshed
 */
class SimpleTaxonomyRefreshed_Widget extends WP_Widget {

	/**
	 * Defaults.
	 *
	 * @var mixed[] $defaults
	 */
	private static $defaults = array(
		'title'     => '',
		'taxonomy'  => 'post_tag',
		'disptype'  => 'cloud',
		'small'     => 50,
		'big'       => 150,
		'alignment' => 'justify',
		'orderby'   => 'count',
		'order'     => 'DESC',
		'showcount' => true,
		'numdisp'   => 45,
		'minposts'  => 0,
	);

	/**
	 * Display the widget�s instance in the REST API )Legacy method).
	 *
	 * @var boolean $show_instance_in_rest Let RESR method work.
	 */
	public $show_instance_in_rest = true;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			'staxonomy',
			__( 'Simple Taxonomy Widget', 'simple-taxonomy-refreshed' ),
			array(
				'classname'             => 'simpletaxonomyrefreshed-widget',
				'description'           => __( 'An advanced tag cloud or list for your custom taxonomy!', 'simple-taxonomy-refreshed' ),
				'show_instance_in_rest' => true,

			)
		);

		// can't i18n outside of a function.
		self::$defaults['title'] = __( 'Advanced Taxonomy Cloud', 'simple-taxonomy-refreshed' );

	}

	/**
	 * Check if taxonomy exist and return it, otherwise return default post tags.
	 *
	 * @param array $instance  The settings for the particular instance of the widget.
	 * @return string
	 */
	private function get_current_taxonomy( $instance ) {
		if ( ! empty( $instance['taxonomy'] ) && taxonomy_exists( $instance['taxonomy'] ) ) {
			return $instance['taxonomy'];
		}

		return 'post_tag';
	}

	/**
	 * Client side widget render
	 *
	 * @param array $args      Display arguments including 'before_title', 'after_title', 'before_widget', and 'after_widget'.
	 * @param array $instance  Saved values from database.
	 * @return string
	 */
	private function widget_gen( $args, $instance ) {
		$dflt_args = array(
			'before_widget' => '',
			'before_title'  => '',
			'after_title'   => '',
			'after_widget'  => '',
		);
		$args      = wp_parse_args( $args, $dflt_args );
		$instance  = wp_parse_args( (array) $instance, self::$defaults );

		// phpcs:ignore
		extract( $args );
		$current_taxonomy = $this->get_current_taxonomy( $instance );

		// Build or not the name of the widget.
		if ( ! empty( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$tax   = get_taxonomy( $current_taxonomy );
			$title = $tax->labels->name;
		}

		/*
		 *
		 * Filters the widget title.
		 *
		 * @param string $title         Default Title.
		 * @param array  $instance      Saved values from database.
		 * @param string $this->id_base	Root ID of widget
		 */
		$title = apply_filters( 'staxo_widget_title', $title, $instance, $this->id_base );

		// buffer output to return rather than echo directly.
		ob_start();

		// phpcs:ignore  WordPress.Security.EscapeOutput
		echo $before_widget;
		if ( $title ) {
			// phpcs:ignore  WordPress.Security.EscapeOutput
			echo $before_title . esc_html( $title ) . $after_title;
		}

		// if we request a tag cloud, check that it has been allowed.
		if ( 'cloud' === $instance['disptype'] && get_taxonomy( $current_taxonomy )->show_tagcloud ) {
			/*
			 *
			 * Filters the cloud widget arguments.
			 *
			 * @param array {
			 *     array  taxonomy taxonomy data structure (from register_taxonomy).
			 *     int    number   Number of items to display in the cloud.
			 *     string order    Ordering of the items.
			 */
			$cloud_args = apply_filters(
				'staxo_widget_tag_cloud_args',
				array(
					'taxonomy'   => $current_taxonomy,
					'smallest'   => $instance['small'],
					'largest'    => $instance['big'],
					'unit'       => '%',
					'number'     => $instance['numdisp'],
					'orderby'    => $instance['orderby'],
					'order'      => $instance['order'],
					'show_count' => $instance['showcount'],
					'format'     => 'list',
					'filter'     => true,
					// sneak in the parameter for the terms_clauses filter to use.
					'filter_min' => $instance['minposts'],
				)
			);
			echo '<div class="staxo-terms-cloud" style="text-align:' . esc_attr( $instance['alignment'] ) . ';">' . "\n";
			// add in filter to make sure only items with specific counts returned.
			add_filter( 'terms_clauses', array( __CLASS__, 'filter_terms' ), 3, 10 );
			// add in filter to keep font size at 100% if count(min) = count(max).
			add_filter( 'wp_generate_tag_cloud', array( __CLASS__, 'filter_result' ), 3, 10 );
			wp_tag_cloud( $cloud_args );
			remove_filter( 'terms_clauses', array( __CLASS__, 'filter_terms' ), 3, 10 );
			remove_filter( 'wp_generate_tag_cloud', array( __CLASS__, 'filter_result' ), 3, 10 );
			echo '</div>' . "\n";
		} else {
			/*
			 *
			 * Filters the list get_terms arguments.
			 *
			 * @param array {
			 *     array  taxonomy taxonomy data structure (from register_taxonomy).
			 *     int    number   Number of items to display in the list.
			 *     string order    Ordering of the items.
			 */
			$list_args = apply_filters(
				'staxo_widget_tag_list_args',
				array(
					'taxonomy'   => $current_taxonomy,
					'number'     => $instance['numdisp'],
					'orderby'    => $instance['orderby'],
					'order'      => $instance['order'],
					// sneak in the parameter for the filter to use.
					'filter_min' => $instance['minposts'],
				)
			);
			// add in filter to make sure only items with specific counts returned.
			add_filter( 'terms_clauses', array( __CLASS__, 'filter_terms' ), 3, 10 );
			$terms = get_terms( $list_args );
			remove_filter( 'terms_clauses', array( __CLASS__, 'filter_terms' ), 3, 10 );
			if ( false === $terms ) {
				echo '<p>' . esc_html__( 'No terms available for this taxonomy.', 'simple-taxonomy-refreshed' ) . '</p>';
			} else {
				echo '<ul class="staxo-terms-list" role="grid">' . "\n";
				foreach ( (array) $terms as $term ) {
					// Translators: Use WP test_domain so no need to translate.
					$formatted_count = sprintf( translate_nooped_plural( _n_noop( '%s item', '%s items' ), $term->count ), number_format_i18n( $term->count ) );
					echo '<li role="row"><a href="' . esc_url( get_term_link( $term, $current_taxonomy ) )
					. '" role="link" aria-label="' . esc_html( $term->name ) . ' (' . esc_attr( $formatted_count ) . ')">'
					. esc_html( $term->name ) . '</a>';
					if ( $instance['showcount'] ) {
						echo esc_html( ' (' . $term->count . ')' );
					}
					echo '</li>' . "\n";
				}
				echo '</ul>' . "\n";
			}
		}

		// phpcs:ignore  WordPress.Security.EscapeOutput
		echo $after_widget;

		// return buffer contents and remove it.
		return ob_get_clean();
	}

	/**
	 * Filters the terms query to restrict (by count) the number of terms returned.
	 *
	 * @param string[] $pieces     Array of query SQL clauses.
	 * @param string[] $taxonomies An array of taxonomy names.
	 * @param array    $args       An array of term query arguments.
	 */
	public static function filter_terms( $pieces, $taxonomies, $args ) {
		if ( $args['filter_min'] > 0 ) {
			$pieces['where']  = str_replace( ' AND tt.count > 0', '', $pieces['where'] );
			$pieces['where'] .= ' AND tt.count >= ' . $args['filter_min'];
		}
		// tag_cloud is always DESC, so must be list.
		if ( 'RAND' === $args['order'] ) {
			$pieces['orderby'] = 'ORDER BY RAND()';
		}
		return $pieces;
	}

	/**
	 * Filters the result text to remove fontsize onformation if min = max for term.
	 *
	 * @param string[]|string $return String containing the generated HTML tag cloud output
	 *                                or an array of tag links if the 'format' argument
	 *                                equals 'array'.
	 * @param WP_Term[]       $tags   An array of terms used in the tag cloud.
	 * @param array           $args   An array of wp_generate_tag_cloud() arguments.
	 */
	public static function filter_result( $return, $tags, $args ) {
		$values = array_column( $tags, 'count' );
		if ( max( $values ) > min( $values ) ) {
			// normal case.
			return $return;
		}
		$return = preg_replace( '/ style="font-size: [.0-9]+%;"/', '', $return );
		return $return;
	}

	/**
	 * Callback to display widget contents in classic widget.
	 *
	 * @param Array  $args the widget arguments.
	 * @param Object $instance the WP Widget instance.
	 */
	public function widget( $args, $instance ) {

		$instance = wp_parse_args( $instance, self::$defaults );
		$output   = $this->widget_gen( $args, $instance );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $output;
	}

	/**
	 * Method for save widgets options.
	 *
	 * @param string $new_instance new settings for this widget.
	 * @param string $old_instance old settings for this widget.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		// String.
		foreach ( array( 'title', 'taxonomy', 'small', 'big', 'alignment', 'numdisp', 'disptype', 'orderby', 'order', 'minposts' ) as $val ) {
			$instance[ $val ] = wp_strip_all_tags( $new_instance[ $val ] );
		}

		// Checkbox.
		$instance['showcount'] = ( isset( $new_instance['showcount'] ) ) ? true : false;

		return $instance;
	}

	/**
	 * Control for widget admin
	 *
	 * @param array $instance current settings.
	 * @return void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, self::$defaults );

		$current_taxonomy = $this->get_current_taxonomy( $instance );
		?>
		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'simple-taxonomy-refreshed' ); ?>:</label>
			<input id="<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_html( $instance['title'] ); ?>" class="widefat" />
		</p>

		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'taxonomy' ) ); ?>"><?php esc_html_e( 'What to show', 'simple-taxonomy-refreshed' ); ?>:</label>
			<select id="<?php echo esc_html( $this->get_field_id( 'taxonomy' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'taxonomy' ) ); ?>" class="widefat">
				<?php
				$taxonomies = $this->get_taxonomies();
				foreach ( $taxonomies as $key => $label ) {
					echo '<option ' . esc_attr( selected( $current_taxonomy, $key, false ) ) . ' value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>';
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'disptype' ) ); ?>"><?php esc_html_e( 'How to show it', 'simple-taxonomy-refreshed' ); ?>:</label>
			<select id="<?php echo esc_html( $this->get_field_id( 'disptype' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'disptype' ) ); ?>" class="widefat">
				<?php
				foreach ( array(
					'cloud' => __( 'Cloud', 'simple-taxonomy-refreshed' ),
					'list'  => __( 'List', 'simple-taxonomy-refreshed' ),
				) as $optval => $option ) {
					echo '<option ' . esc_attr( selected( $instance['disptype'], $optval, false ) ) . ' value="' . esc_attr( $optval ) . '">' . esc_html( $option ) . '</option>';
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'small' ) ); ?>"><?php esc_html_e( 'Small size:', 'simple_taxonomy-refreshed' ); ?></label><br />
			<input class="small-text" id="<?php echo esc_attr( $this->get_field_id( 'small' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'small' ) ); ?>" type="number" value="<?php echo esc_attr( $instance['small'] ); ?>" min="40" max="100" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'big' ) ); ?>"><?php esc_html_e( 'Big size:', 'simple_taxonomy-refreshed' ); ?></label><br />
			<input class="small-text" id="<?php echo esc_attr( $this->get_field_id( 'big' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'big' ) ); ?>" type="number" value="<?php echo esc_attr( $instance['big'] ); ?>" min="100" max="160" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'alignment' ) ); ?>"><?php esc_html_e( 'Alignment:', 'simple_taxonomy-refreshed' ); ?></label>
			<select id="<?php echo esc_html( $this->get_field_id( 'alignment' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'alignment' ) ); ?>" class="widefat">
				<?php
				foreach ( array(
					'left'    => __( 'Left', 'simple-taxonomy-refreshed' ),
					'center'  => __( 'Centre', 'simple-taxonomy-refreshed' ),
					'right'   => __( 'Right', 'simple-taxonomy-refreshed' ),
					'justify' => __( 'Justify', 'simple-taxonomy-refreshed' ),
				) as $optval => $option ) {
					echo '<option ' . esc_attr( selected( $instance['alignment'], $optval, false ) ) . ' value="' . esc_attr( $optval ) . '">' . esc_html( $option ) . '</option>';
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'orderby' ) ); ?>"><?php esc_html_e( 'Ordering Field', 'simple-taxonomy-refreshed' ); ?>:</label>
			<select id="<?php echo esc_html( $this->get_field_id( 'orderby' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'orderby' ) ); ?>" class="widefat">
				<?php
				foreach ( array(
					'count' => __( 'Count', 'simple-taxonomy-refreshed' ),
					'name'  => __( 'Name', 'simple-taxonomy-refreshed' ),
				) as $optval => $option ) {
					echo '<option ' . esc_attr( selected( $instance['orderby'], $optval, false ) ) . ' value="' . esc_attr( $optval ) . '">' . esc_html( $option ) . '</option>';
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'order' ) ); ?>"><?php esc_html_e( 'Output Order', 'simple-taxonomy-refreshed' ); ?>:</label>
			<select id="<?php echo esc_html( $this->get_field_id( 'order' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'order' ) ); ?>" class="widefat">
				<?php
				foreach ( array(
					'ASC'  => __( 'Ascending', 'simple-taxonomy-refreshed' ),
					'DESC' => __( 'Descending', 'simple-taxonomy-refreshed' ),
					'RAND' => __( 'Random', 'simple-taxonomy-refreshed' ),
				) as $optval => $option ) {
					echo '<option ' . esc_attr( selected( $instance['order'], $optval, false ) ) . ' value="' . esc_attr( $optval ) . '">' . esc_html( $option ) . '</option>';
				}
				?>
			</select>
		</p>

		<p>
			<input type="checkbox" id="<?php echo esc_html( $this->get_field_id( 'showcount' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'showcount' ) ); ?>" <?php checked( $instance['showcount'], true ); ?> />
			<label for="<?php echo esc_html( $this->get_field_id( 'showcount' ) ); ?>"><?php esc_html_e( 'Show post count in result ?', 'simple-taxonomy-refreshed' ); ?></label>
		</p>

		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'numdisp' ) ); ?>"><?php esc_html_e( 'Number of terms to show', 'simple-taxonomy-refreshed' ); ?>:</label>
			<input id="<?php echo esc_html( $this->get_field_id( 'numdisp' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'numdisp' ) ); ?>" type="number" value="<?php echo (int) $instance['numdisp']; ?>" min="1" max="100" class="widefat" />
		</p>

		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'minposts' ) ); ?>"><?php esc_html_e( 'Minimum count of posts for term to be shown', 'simple-taxonomy-refreshed' ); ?>:</label>
			<input id="<?php echo esc_html( $this->get_field_id( 'minposts' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'minposts' ) ); ?>" type="number" value="<?php echo (int) $instance['minposts']; ?>" min="0" class="widefat" />
		</p>
		<?php
	}

	/**
	 * Get taxonomy names for selection (use cache).
	 *
	 * @return Array Taxonomy names for documents
	 * @since 2.1.0
	 */
	public function get_taxonomies() {
		$taxonomies = wp_cache_get( 'staxo_taxonomies' );

		if ( false === $taxonomies ) {
			$taxonomies = array();
			// build and create cache entry.
			foreach ( get_taxonomies( array( 'public' => true ) ) as $taxonomy ) {
				$tax                      = get_taxonomy( $taxonomy );
				$taxonomies[ $tax->name ] = ( empty( $tax->labels->name ) ? $tax->name : $tax->labels->name );
			}

			asort( $taxonomies );

			wp_cache_set( 'staxo_taxonomies', $taxonomies, '', ( WP_DEBUG ? 10 : 120 ) );
		}

		return $taxonomies;
	}

	/**
	 * Register widget block.
	 *
	 * @since 2.1.0
	 */
	public function staxo_widget_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active, e.g. Old WP version installed.
			return;
		}

		$dir      = dirname( __DIR__ );
		$suffix   = ( WP_DEBUG ) ? '.dev' : '';
		$index_js = 'js/staxo-widget' . $suffix . '.js';
		wp_register_script(
			'staxo-widget-editor',
			plugins_url( $index_js, __DIR__ ),
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-server-side-render',
				'wp-i18n',
			),
			filemtime( "$dir/$index_js" ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			),
		);

		// Add supplementary script for additional information.
		// Ensure taxonomies are set.
		$taxonomies = $this->get_taxonomies();
		wp_add_inline_script( 'staxo-widget-editor', 'const staxo_data = ' . wp_json_encode( $taxonomies ), 'before' );

		$index_css = 'css/staxo-widget-editor-style' . $suffix . '.css';
		wp_register_style(
			'staxo-widget-editor-style',
			plugins_url( $index_css, __DIR__ ),
			array( 'wp-edit-blocks' ),
			filemtime( "$dir/$index_css" )
		);

		register_block_type(
			'simple-taxonomy-refreshed/cloud-widget',
			array(
				'description'     => __( 'This block provides a tag cloud widget for the selected taxonomy.', 'simple-taxonomy-refreshed' ),
				'editor_script'   => 'staxo-widget-editor',
				'editor_style'    => 'staxo-widget-editor-style',
				'render_callback' => array( $this, 'staxo_widget_display' ),
				'attributes'      => array(
					'title'           => array(
						'type'    => 'string',
						'default' => 'Advanced Taxonomy Cloud',
					),
					'taxonomy'        => array(
						'type'    => 'string',
						'default' => 'post_tag',
					),
					'disptype'        => array(
						'type'    => 'string',
						'default' => 'cloud',
					),
					'small'           => array(
						'type'    => 'number',
						'default' => 50,
					),
					'big'             => array(
						'type'    => 'number',
						'default' => 150,
					),
					'alignment'       => array(
						'type'    => 'string',
						'default' => 'justify',
					),
					'orderby'         => array(
						'type'    => 'string',
						'default' => 'name',
					),
					'ordering'        => array(
						'type'    => 'string',
						'default' => 'ASC',
					),
					'showcount'       => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'numdisp'         => array(
						'type'    => 'number',
						'default' => 45,
					),
					'minposts'        => array(
						'type'    => 'number',
						'default' => 0,
					),
					// phpcs:disable
					// 'excludes'      => array(
					// 'type'  => 'array',
					// 'items' => array(
					// 'type' => 'number',
					// ),
					// ),
					// phpcs:enable
					'align'           => array(
						'type' => 'string',
					),
					'backgroundColor' => array(
						'type' => 'string',
					),
					'linkColor'       => array(
						'type' => 'string',
					),
					'textColor'       => array(
						'type' => 'string',
					),
					'gradient'        => array(
						'type' => 'string',
					),
					'fontSize'        => array(
						'type' => 'string',
					),
					'style'           => array(
						'type' => 'object',
					),
				),
			)
		);

		// set translations.
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'str-widget-editor', 'simple_taxonomy-refreshed' );
		}
	}

	/**
	 * Render widget block server side.
	 *
	 * @param array  $atts     block attributes coming from block.
	 * @param string $content  Optional. Block content. Default empty string.
	 * @since 2.1.0
	 */
	public function staxo_widget_display( $atts, $content = '' ) {
		// Create the two parameter sets.
		$args     = array(
			'before_widget' => '',
			'before_title'  => '',
			'after_title'   => '',
			'after_widget'  => '',
		);
		$instance = wp_parse_args( $atts, self::$defaults );

		// if header is set, then title at level h2.
		if ( isset( $atts['header'] ) ) {
			$args['before_title'] = '<h2>';
			$args['after_title']  = '</h2>';
		}

		// 'ordering' needs to be i='order'.
		$instance['order'] = $instance['ordering'];
		unset( $instance['ordering'] );

		global $strw;
		$output = $strw->widget_gen( $args, $instance );
		return $output;
	}
}
