<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seriously Simple Podcasting Podcast Playlist Widget
 *
 * @author 		Hugh Lashbrooke
 * @package 	SeriouslySimplePodcasting
 * @category 	SeriouslySimplePodcasting/Widgets
 * @since 		1.15.0
 */
class SSP_Widget_Playlist extends WP_Widget {
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
		$this->widget_cssclass = 'widget_podcast_playlist';
		$this->widget_description = __( 'Display a playlist of episodes.', 'seriously-simple-podcasting' );
		$this->widget_idbase = 'ss_podcast';
		$this->widget_title = __( 'Podcast: Playlist', 'seriously-simple-podcasting' );

		// Widget settings
		$widget_ops = array(
			'classname' => $this->widget_cssclass,
			'description' => $this->widget_description,
			'customize_selective_refresh' => true,
		);

		parent::__construct('podcast-playlist', $this->widget_title, $widget_ops);

		$this->alt_option_name = 'widget_podcast_playlist';

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );

	} // End __construct()

	public function widget($args, $instance) {
		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'widget_podcast_playlist', 'widget' );
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

		$episodes = ( $instance['episodes'] ) ? $instance['episodes'] : '';
		$series_slug = ( $instance['series_slug'] ) ? $instance['series_slug'] : '';

		$shortcode = '[podcast_playlist';

		if( $episodes ) {
			$shortcode .= ' episodes=' . $episodes;
		}

		if( $series_slug ) {
			$shortcode .= ' series=' . $series_slug;
		}

		$shortcode .= ']';

		$title = $instance['title'];

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo do_shortcode( $shortcode );

		echo $args['after_widget'];

		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ] = ob_get_flush();
			wp_cache_set( 'widget_podcast_series', $cache, 'widget' );
		} else {
			ob_end_flush();
		}
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] 		  = strip_tags( $new_instance['title'] );
		$instance['episodes']  	  = isset( $new_instance['episodes'] ) ? $new_instance['episodes'] : '';
		$instance['series_slug']  = isset( $new_instance['series_slug'] ) ? $new_instance['series_slug'] : '';
		$this->flush_widget_cache();

		return $instance;
	}

	public function flush_widget_cache() {
		wp_cache_delete('widget_podcast_playlist', 'widget');
	}

	public function form( $instance ) {
		$title        = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$episodes     = isset( $instance['episodes'] ) ? esc_attr( $instance['episodes'] ) : '';
		$series_slug  = isset( $instance['series_slug'] ) ? $instance['series_slug'] : '';

		// Get all podcast series
		$series = get_terms( 'series' );
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title (optional):', 'seriously-simple-podcasting' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" placeholder="<?php _e( 'Widget title', 'seriously-simple-podcasting' ); ?>" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'episodes' ); ?>"><?php _e( 'Episodes (comma-separated IDs):', 'seriously-simple-podcasting' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'episodes' ); ?>" name="<?php echo $this->get_field_name( 'episodes' ); ?>" type="text" placeholder="<?php _e( 'Display all episodes', 'seriously-simple-podcasting' ); ?>" value="<?php echo $episodes; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'series_slug' ); ?>"><?php _e( 'Series:', 'seriously-simple-podcasting' ); ?></label>
		<select id="<?php echo $this->get_field_id( 'series_slug' ); ?>" name="<?php echo $this->get_field_name( 'series_slug' ); ?>">
			<option value=""><?php _e( 'Use episodes specified above', 'seriously-simple-podcasting' ); ?></option>
			<?php
			foreach ( $series as $s ) {
				echo '<option value="' . esc_attr( $s->slug ) . '" ' . selected( $series_slug, $s->slug, false ) . '>' . $s->name . '</option>' . "\n";
			}
			?>
		</select>
		</p>
<?php
	}
} // End Class

?>