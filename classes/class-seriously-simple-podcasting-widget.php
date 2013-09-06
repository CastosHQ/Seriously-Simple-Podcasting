<?php
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'ss_podcast' ) ) exit; // Exit if accessed directly.

/**
 * Seriously Simple Podcasting Widget
 *
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 * @category Widgets
 * @author Hugh Lashbrooke
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * protected $widget_cssclass
 * protected $widget_description
 * protected $widget_idbase
 * protected $widget_title
 *
 * - __construct()
 * - widget()
 * - update()
 * - form()
 * - get_content_options()
 * - get_all_series()
 */
class SeriouslySimplePodcasting_Widget extends WP_Widget {
	protected $widget_cssclass;
	protected $widget_description;
	protected $widget_idbase;
	protected $widget_title;

	/**
	 * Constructor function.
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct() {
		/* Widget variable settings. */
		$this->widget_cssclass = 'widget_ss_podcast';
		$this->widget_description = __( 'Display a list of your podcast series or episodes from a selected series.', 'ss-podcast' );
		$this->widget_idbase = 'ss_podcast';
		$this->widget_title = __( 'Podcast', 'ss-podcast' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->widget_cssclass, 'description' => $this->widget_description );

		/* Widget control settings. */
		$control_ops = array( 'width' => 250, 'height' => 350, 'id_base' => $this->widget_idbase );

		/* Create the widget. */
		$this->WP_Widget( $this->widget_idbase, $this->widget_title, $widget_ops, $control_ops );	
	} // End __construct()

	/**
	 * Display the widget on the frontend.
	 * @since  1.0.0
	 * @param  array $args     Widget arguments.
	 * @param  array $instance Widget settings for this instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {  
		extract( $args, EXTR_SKIP );
		
		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base );
			
		/* Before widget (defined by themes). */
		$args = array();

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title ) {
			$args['title'] = $title;
		}
		
		/* Widget content. */
		// Add actions for plugins/themes to hook onto.
		do_action( $this->widget_cssclass . '_top' );

		// Select boxes.
		if ( isset( $instance['content'] ) && in_array( $instance['content'], array_keys( $this->get_content_options() ) ) ) { $args['content'] = $instance['content']; }
		if ( isset( $instance['series'] ) && in_array( $instance['series'], array_keys( $this->get_all_series() ) ) ) { $args['series'] = $instance['series']; }

		// Display the data.
		ss_podcast( $args );

		// Add actions for plugins/themes to hook onto.
		do_action( $this->widget_cssclass . '_bottom' );
	} // End widget()

	/**
	 * Method to update the settings from the form() method.
	 * @since  1.0.0
	 * @param  array $new_instance New settings.
	 * @param  array $old_instance Previous settings.
	 * @return array               Updated settings.
	 */
	public function update ( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );

		/* The select box is returning a text value, so we escape it. */
		$instance['content'] = esc_attr( $new_instance['content'] );
		$instance['series'] = esc_attr( $new_instance['series'] );

		return $instance;
	} // End update()

	/**
	 * The form on the widget control in the widget administration area.
	 * @since  1.0.0
	 * @param  array $instance The settings for this instance.
	 * @return void
	 */
    public function form( $instance ) {       
   
		/* Set up some default widget settings. */
		/* Make sure all keys are added here, even with empty string values. */
		$defaults = array(
			'title' => '',
			'content' => 'series',
			'series' => 0
		);
		
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title (optional):', 'ss-podcast' ); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>"  value="<?php echo $instance['title']; ?>" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" />
		</p>
		<!-- Widget Content: Select Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'content' ); ?>"><?php _e( 'Content:', 'ss-podcast' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'content' ); ?>" class="widefat" id="<?php echo $this->get_field_id( 'content' ); ?>">
			<?php foreach ( $this->get_content_options() as $k => $v ) { ?>
				<option value="<?php echo $k; ?>"<?php selected( $instance['content'], $k ); ?>><?php echo $v; ?></option>
			<?php } ?>       
			</select>
		</p>
		<!-- Widget Series: Select Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'series' ); ?>"><?php _e( 'Series to display:', 'ss-podcast' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'series' ); ?>" class="widefat" id="<?php echo $this->get_field_id( 'series' ); ?>">
			<?php foreach ( $this->get_all_series() as $k => $v ) { ?>
				<option value="<?php echo $k; ?>"<?php selected( $instance['series'], $k ); ?>><?php echo $v; ?></option>
			<?php } ?>       
			</select><br/>
			<small><?php _e( 'Required if content is set to show episodes from selected series.', 'ss-podcast' ); ?></small>
		</p>
		<?php
	} // End form()

	/**
	 * Get an array of the available content options
	 * @since  1.0.0
	 * @return array
	 */
	protected function get_content_options () {
		return array(
					'series' => __( 'All Series', 'ss-podcast' ), 
					'episodes' => __( 'Episodes from Selected Series', 'ss-podcast' ),
					);
	} // End get_content_options()

	/**
	 * Get an array of the available podcast series
	 * @since  1.0.0
	 * @return array
	 */
	protected function get_all_series () {

		$series = array();

		$terms = get_terms( 'series' );

		if( count( $terms ) > 0) {
			foreach ( $terms as $term ) {
	    		$series[ $term->slug ] = $term->name;
		    }
		}

		return $series;
	} // End get_all_series()
} // End Class

/* Register the widget. */
add_action( 'widgets_init', create_function( '', 'return register_widget("SeriouslySimplePodcasting_Widget");' ), 1 ); 
?>