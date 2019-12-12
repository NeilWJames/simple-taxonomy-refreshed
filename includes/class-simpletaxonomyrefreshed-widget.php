<?php
/**
 * Simple Taxonomy Widget class file.
 *
 * @package simple-taxonomy-refreshed
 * @author Amaury Balmer/Neil James
 */

/**
 * Class to provide a widget for custom taxonomy (tag cloud or list)
 *
 * @package simple-taxonomy-refreshed
 */
class SimpleTaxonomyRefreshed_Widget extends WP_Widget {
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
				'classname'   => 'staxo-widget',
				'description' => __( 'An advanced tag cloud or list for your custom taxonomy!', 'simple-taxonomy-refreshed' ),
			)
		);
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
	 * @return void
	 */
	public function widget( $args, $instance ) {
		// phpcs:ignore
		extract( $args );
		$current_taxonomy = $this->get_current_taxonomy( $instance );

		// Build or not the name of the widget.
		if ( ! empty( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			if ( 'post_tag' === $current_taxonomy ) {
				$title = __( 'Tags', 'simple-taxonomy-refreshed' );
			} else {
				$tax   = get_taxonomy( $current_taxonomy );
				$title = $tax->labels->name;
			}
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

		// phpcs:ignore  WordPress.Security.EscapeOutput
		echo $before_widget;
		if ( $title ) {
			// phpcs:ignore  WordPress.Security.EscapeOutput
			echo $before_title . esc_html( $title ) . $after_title;
		}

		if ( 'cloud' === $instance['type'] ) {
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
					'taxonomy' => $current_taxonomy,
					'number'   => $instance['number'],
					'order'    => $instance['cloudorder'],
				)
			);
			echo '<div>' . "\n";
			wp_tag_cloud( $cloud_args );
			echo '</div>' . "\n";

		} else {

			$terms = get_terms( $current_taxonomy, 'number=' . $instance['number'] . '&order=' . $instance['listorder'] );
			if ( false === $terms ) {
				echo '<p>' . esc_html_e( 'No terms actually for this taxonomy.', 'simple-taxonomy-refreshed' ) . '</p>';
			} else {
				echo '<ul class="simpletaxonomy-list">' . "\n";
				foreach ( (array) $terms as $term ) {
					echo '<li><a href="' . esc_url( get_term_link( $term, $current_taxonomy ) ) . '">' . esc_html( $term->name ) . '</a>' . "\n";
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
		foreach ( array( 'title', 'taxonomy', 'number', 'type', 'cloudorder', 'listorder' ) as $val ) {
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
		$defaults = array(
			'title'      => __( 'Adv Tag Cloud', 'simple-taxonomy-refreshed' ),
			'type'       => 'cloud',
			'cloudorder' => 'RAND',
			'listorder'  => 'ASC',
			'showcount'  => true,
			'number'     => 45,
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

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
				foreach ( get_taxonomies() as $taxonomy ) {
					$tax = get_taxonomy( $taxonomy );
					if ( ! $tax->show_tagcloud || empty( $tax->labels->name ) ) {
						continue;
					}

					echo '<option ' . esc_attr( selected( $current_taxonomy, $taxonomy, false ) ) . ' value="' . esc_attr( $taxonomy ) . '">' . esc_html( $tax->labels->name ) . '</option>';
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'type' ) ); ?>"><?php esc_html_e( 'How to show it', 'simple-taxonomy-refreshed' ); ?>:</label>
			<select id="<?php echo esc_html( $this->get_field_id( 'type' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'type' ) ); ?>" class="widefat">
				<?php
				foreach ( array(
					'cloud' => __( 'Cloud', 'simple-taxonomy-refreshed' ),
					'list'  => __( 'List', 'simple-taxonomy-refreshed' ),
				) as $optval => $option ) {
					echo '<option ' . esc_attr( selected( $instance['type'], $optval, false ) ) . ' value="' . esc_attr( $optval ) . '">' . esc_html( $option ) . '</option>';
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'cloudorder' ) ); ?>"><?php esc_html_e( 'Order for cloud', 'simple-taxonomy-refreshed' ); ?>:</label>
			<select id="<?php echo esc_html( $this->get_field_id( 'cloudorder' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'cloudorder' ) ); ?>" class="widefat">
				<?php
				foreach ( array(
					'RAND' => __( 'Random', 'simple-taxonomy-refreshed' ),
					'ASC'  => __( 'Ascending', 'simple-taxonomy-refreshed' ),
					'DESC' => __( 'Descending', 'simple-taxonomy-refreshed' ),
				) as $optval => $option ) {
					echo '<option ' . esc_attr( selected( $instance['cloudorder'], $optval, false ) ) . ' value="' . esc_attr( $optval ) . '">' . esc_html( $option ) . '</option>';
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'listorder' ) ); ?>"><?php esc_html_e( 'Order for list', 'simple-taxonomy-refreshed' ); ?>:</label>
			<select id="<?php echo esc_html( $this->get_field_id( 'listorder' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'listorder' ) ); ?>" class="widefat">
				<?php
				foreach ( array(
					'ASC'  => __( 'Ascending', 'simple-taxonomy-refreshed' ),
					'DESC' => __( 'Descending', 'simple-taxonomy-refreshed' ),
				) as $optval => $option ) {
					echo '<option ' . esc_attr( selected( $instance['listorder'], $optval, false ) ) . ' value="' . esc_attr( $optval ) . '">' . esc_html( $option ) . '</option>';
				}
				?>
			</select>
		</p>

		<p>
			<input type="checkbox" id="<?php echo esc_html( $this->get_field_id( 'showcount' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'showcount' ) ); ?>" <?php checked( $instance['showcount'], true ); ?> />
			<label for="<?php echo esc_html( $this->get_field_id( 'showcount' ) ); ?>"><?php esc_html_e( 'Show post count in list ?', 'simple-taxonomy-refreshed' ); ?></label>
		</p>

		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e( 'Number of terms to show', 'simple-taxonomy-refreshed' ); ?>:</label>
			<input id="<?php echo esc_html( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'number' ) ); ?>" value="<?php echo (int) $instance['number']; ?>" class="widefat" />
		</p>
		<?php
	}
}
