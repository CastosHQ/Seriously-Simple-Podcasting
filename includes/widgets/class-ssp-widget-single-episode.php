<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seriously Simple Podcasting Single Podcast Episode Widget
 *
 * @author 		Hugh Lashbrooke
 * @package 	SeriouslySimplePodcasting
 * @category 	SeriouslySimplePodcasting/Widgets
 * @since 		1.9.0
 */
class SSP_Widget_Single_Episode extends WP_Widget {
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
		$this->widget_cssclass = 'widget_podcast_episode';
		$this->widget_description = __( 'Display a single podcast episode.', 'seriously-simple-podcasting' );
		$this->widget_idbase = 'ss_podcast';
		$this->widget_title = __( 'Podcast: Single Episode', 'seriously-simple-podcasting' );

		// Widget settings
		$widget_ops = array(
			'classname' => $this->widget_cssclass,
			'description' => $this->widget_description,
			'customize_selective_refresh' => true,
		);

		parent::__construct('single-podcast-episode', $this->widget_title, $widget_ops);

		$this->alt_option_name = 'widget_single_episode';

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );

	} // End __construct()

	public function widget( $args, $instance ) {
		global $ss_podcasting;

		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'widget_single_episode', 'widget' );
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

		$episode_id = $instance['episode_id'];

		if( 0 == $episode_id ) {
			$ssp_episodes = ssp_episodes( 1 );
			if( 0 < count( $ssp_episodes ) ) {
				foreach( $ssp_episodes as $episode ) {
					$episode_id = $episode->ID;
					break;
				}
			}
		}

		if( ! $episode_id ) {
			return;
		}

		$title = ( $instance['title'] ) ? $instance['title'] : get_the_title( $episode_id );

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$show_title = isset( $instance['show_title'] ) ? $instance['show_title'] : false;
		$show_excerpt = isset( $instance['show_excerpt'] ) ? $instance['show_excerpt'] : false;
		$show_content = isset( $instance['show_content'] ) ? $instance['show_content'] : false;
		$show_player = isset( $instance['show_player'] ) ? $instance['show_player'] : false;
		$show_details = isset( $instance['show_details'] ) ? $instance['show_details'] : false;

		$content_items = array();

		if ( $show_title ) {
			$content_items[] = 'title';
		}

		if ( $show_excerpt ) {
			$content_items[] = 'excerpt';
		}

		if ( $show_content ) {
			$content_items[] = 'content';
		}

		if ( $show_player ) {
			$content_items[] = 'player';
		}

		if ( $show_details ) {
			$content_items[] = 'details';
		}

		// Get episode markup
		$html = $ss_podcasting->podcast_episode( $episode_id, $content_items, 'widget' );

		if ( ! $html ) {
			return;
		}

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo $html;

		echo $args['after_widget'];

		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ] = ob_get_flush();
			wp_cache_set( 'widget_single_episode', $cache, 'widget' );
		} else {
			ob_end_flush();
		}
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] 		  = strip_tags( $new_instance['title'] );
		$instance['episode_id']   = isset( $new_instance['episode_id'] ) ? (int) $new_instance['episode_id'] : 0;
		$instance['show_title']   = isset( $new_instance['show_title'] ) ? (bool) $new_instance['show_title'] : false;
		$instance['show_excerpt'] = isset( $new_instance['show_excerpt'] ) ? (bool) $new_instance['show_excerpt'] : false;
		$instance['show_content'] = isset( $new_instance['show_content'] ) ? (bool) $new_instance['show_content'] : false;
		$instance['show_player']  = isset( $new_instance['show_player'] ) ? (bool) $new_instance['show_player'] : false;
		$instance['show_details'] = isset( $new_instance['show_details'] ) ? (bool) $new_instance['show_details'] : false;
		$this->flush_widget_cache();

		return $instance;
	}

	public function flush_widget_cache() {
		wp_cache_delete('widget_single_episode', 'widget');
	}

	public function form( $instance ) {
		$title     	  = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$episode_id   = isset( $instance['episode_id'] ) ? $instance['episode_id'] : 0;
		$show_title   = isset( $instance['show_title'] ) ? (bool) $instance['show_title'] : false;
		$show_excerpt = isset( $instance['show_excerpt'] ) ? (bool) $instance['show_excerpt'] : false;
		$show_content = isset( $instance['show_content'] ) ? (bool) $instance['show_content'] : false;
		$show_player  = isset( $instance['show_player'] ) ? (bool) $instance['show_player'] : false;
		$show_details = isset( $instance['show_details'] ) ? (bool) $instance['show_details'] : false;

		// Get all podcast episodes
		$episode_ids = (array) ssp_episode_ids();
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'seriously-simple-podcasting' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" placeholder="<?php _e( 'Use episode title', 'seriously-simple-podcasting' ); ?>" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'episode_id' ); ?>"><?php _e( 'Episode:', 'seriously-simple-podcasting' ); ?></label>
		<select id="<?php echo $this->get_field_id( 'episode_id' ); ?>" name="<?php echo $this->get_field_name( 'episode_id' ); ?>">
			<option value="0"><?php _e( '- Latest episode -', 'seriously-simple-podcasting' ); ?></option>
			<?php
			foreach ( $episode_ids as $id ) {
				echo '<option value="' . esc_attr( $id ) . '" ' . selected( $episode_id, $id, false ) . '>' . get_the_title( $id ) . '</option>' . "\n";
			}
			?>
		</select>
		</p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_title ); ?> id="<?php echo $this->get_field_id( 'show_title' ); ?>" name="<?php echo $this->get_field_name( 'show_title' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Display episode title inside widget?', 'seriously-simple-podcasting' ); ?></label></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_excerpt ); ?> id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"><?php _e( 'Display episode excerpt?', 'seriously-simple-podcasting' ); ?></label></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_content ); ?> id="<?php echo $this->get_field_id( 'show_content' ); ?>" name="<?php echo $this->get_field_name( 'show_content' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_content' ); ?>"><?php _e( 'Display full episode content?', 'seriously-simple-podcasting' ); ?></label></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_player ); ?> id="<?php echo $this->get_field_id( 'show_player' ); ?>" name="<?php echo $this->get_field_name( 'show_player' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_player' ); ?>"><?php _e( 'Display episode audio player?', 'seriously-simple-podcasting' ); ?></label></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_details ); ?> id="<?php echo $this->get_field_id( 'show_details' ); ?>" name="<?php echo $this->get_field_name( 'show_details' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_details' ); ?>"><?php _e( 'Display episode details?', 'seriously-simple-podcasting' ); ?></label></p>
<?php
	}
} // End Class

?>