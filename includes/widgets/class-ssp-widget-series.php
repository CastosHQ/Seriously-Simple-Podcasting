<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seriously Simple Podcasting Podcast Series Widget
 *
 * @author 		Hugh Lashbrooke
 * @package 	SeriouslySimplePodcasting
 * @category 	SeriouslySimplePodcasting/Widgets
 * @since 		1.9.0
 */
class SSP_Widget_Series extends WP_Widget {
	protected $widget_cssclass;
	protected $widget_description;
	protected $widget_idbase;
	protected $widget_title;

	/**
	 * Constructor function.
	 * @since  1.9.0
	 */
	public function __construct() {
		// Widget variable settings
		$this->widget_cssclass = 'widget_podcast_series';
		$this->widget_description = __( 'Display a list of episodes from a single series.', 'seriously-simple-podcasting' );
		$this->widget_idbase = 'ss_podcast';
		$this->widget_title = __( 'Podcast: Series', 'seriously-simple-podcasting' );

		// Widget settings
		$widget_ops = array(
			'classname' => $this->widget_cssclass,
			'description' => $this->widget_description,
			'customize_selective_refresh' => true,
		);

		parent::__construct('podcast-series', $this->widget_title, $widget_ops);

		$this->alt_option_name = 'widget_podcast_series';

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );

	} // End __construct()

	public function widget($args, $instance) {
		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'widget_podcast_series', 'widget' );
		}

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();

		$series_id = $instance['series_id'];

		if ( ! $series_id ) {
			return;
		}

		$series = get_term( $series_id, 'series' );

		if ( ! $series || is_wp_error( $series ) ) {
			return;
		}

		$title = ( $instance['title'] ) ? $instance['title'] : $series->name;

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$show_title = isset( $instance['show_title'] ) ? $instance['show_title'] : false;
		$show_desc = isset( $instance['show_desc'] ) ? $instance['show_desc'] : false;
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;

		$query_args = ssp_episodes( 999, $series->slug, true, 'widget' );

		$qry = new WP_Query( apply_filters( 'ssp_widget_series_episodes_args', $query_args ) );

		if ( $qry->have_posts() ) :
?>
		<?php echo $args['before_widget']; ?>
		<?php if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		} ?>

		<?php if ( $show_title ) { ?>
			<h3><?php echo $series->name; ?></h3>
		<?php } ?>

		<?php if ( $show_desc ) { ?>
			<p><?php echo $series->description; ?></p>
		<?php } ?>

		<ul>
		<?php while ( $qry->have_posts() ) : $qry->the_post(); ?>
			<li>
				<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
			<?php if ( $show_date ) : ?>
				<span class="post-date"><?php echo get_the_date(); ?></span>
			<?php endif; ?>
			</li>
		<?php endwhile; ?>
		</ul>
		<?php echo $args['after_widget']; ?>
<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;

		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ] = ob_get_flush();
			wp_cache_set( 'widget_podcast_series', $cache, 'widget' );
		} else {
			ob_end_flush();
		}
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] 		= strip_tags( $new_instance['title'] );
		$instance['series_id']  = isset( $new_instance['series_id'] ) ? (int) $new_instance['series_id'] : 0;
		$instance['show_title'] = isset( $new_instance['show_title'] ) ? (bool) $new_instance['show_title'] : false;
		$instance['show_desc']  = isset( $new_instance['show_desc'] ) ? (bool) $new_instance['show_desc'] : false;
		$instance['show_date']  = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		$this->flush_widget_cache();

		return $instance;
	}

	public function flush_widget_cache() {
		wp_cache_delete('widget_podcast_series', 'widget');
	}

	public function form( $instance ) {
		$title        = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$series_id    = isset( $instance['series_id'] ) ? $instance['series_id'] : 0;
		$show_title   = isset( $instance['show_title'] ) ? (bool) $instance['show_title'] : false;
		$show_desc    = isset( $instance['show_desc'] ) ? (bool) $instance['show_desc'] : false;
		$show_date    = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;

		// Get all podcast series
		$series = get_terms( 'series' );
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'seriously-simple-podcasting' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" placeholder="<?php _e( 'Use series title', 'seriously-simple-podcasting' ); ?>" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'series_id' ); ?>"><?php _e( 'Series:', 'seriously-simple-podcasting' ); ?></label>
		<select id="<?php echo $this->get_field_id( 'series_id' ); ?>" name="<?php echo $this->get_field_name( 'series_id' ); ?>">
			<?php
			foreach ( $series as $s ) {
				echo '<option value="' . esc_attr( $s->term_id ) . '" ' . selected( $series_id, $s->term_id, false ) . '>' . $s->name . '</option>' . "\n";
			}
			?>
		</select>
		</p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_title ); ?> id="<?php echo $this->get_field_id( 'show_title' ); ?>" name="<?php echo $this->get_field_name( 'show_title' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Display series title inside widget?', 'seriously-simple-podcasting' ); ?></label></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_desc ); ?> id="<?php echo $this->get_field_id( 'show_desc' ); ?>" name="<?php echo $this->get_field_name( 'show_desc' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_desc' ); ?>"><?php _e( 'Display series description?', 'seriously-simple-podcasting' ); ?></label></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display episode date?', 'seriously-simple-podcasting' ); ?></label></p>
<?php
	}
} // End Class

?>