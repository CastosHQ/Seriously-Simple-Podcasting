<?php
/**
 * Elementor Media Player Widget
 *
 * Elementor widget for displaying media player.
 *
 * @package Seriously Simple Podcasting
 * @since 2.4.0
 */

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;

/**
 * Media Player Widget Class
 *
 * Elementor widget for displaying media player.
 *
 * @package SeriouslySimplePodcasting\Integrations\Elementor\Widgets
 * @since 2.4.0
 */
class Elementor_Media_Player_Widget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Media Player';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Media Player', 'seriously-simple-podcasting' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-play';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'podcasting' );
	}

	/**
	 * Get episodes for widget options.
	 *
	 * @return array
	 */
	public function get_episodes() {
		$args = array(
			'fields'         => array( 'post_title, id' ),
			'posts_per_page' => get_option( 'posts_per_page', 10 ),
			'post_type'      => ssp_post_types( true ),
			'post_status'    => array( 'publish', 'draft', 'future' ),
		);

		$episodes        = get_posts( $args );
		$episode_options = array(
			'-1' => 'Current Episode',
			'0'  => 'Latest Episode',
		);
		foreach ( $episodes as $episode ) {
			$episode_options[ (string) $episode->ID ] = $episode->post_title;
		}

		return $episode_options;
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'seriously-simple-podcasting' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$episode_options = $this->get_episodes();
		$this->add_control(
			'show_elements',
			array(
				'label'   => __( 'Select Episode', 'seriously-simple-podcasting' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => $episode_options,
				'default' => '-1',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 */
	protected function render() {
		global $ss_podcasting;
		$players_controller = $ss_podcasting->players_controller;
		$settings           = $this->get_settings_for_display();
		$episode_id         = $settings['show_elements'];
		if ( '-1' === $episode_id ) {
			$episode_id = get_post()->ID;
		}
		if ( empty( $episode_id ) ) {
			$episode_id = $players_controller->get_latest_episode_id();
		}
		$media_player = $players_controller->render_media_player( $episode_id );
		echo $media_player;
	}

	protected function _content_template() {
		?>
		<# _.each( settings.show_elements, function( element ) { #>
		<div>{{{ element }}}</div>
		<# } ) #>
		<?php
	}

	/**
	 * Render plain content (what data should be stored in the post_content).
	 *
	 * @since 2.11.0
	 */
	public function render_plain_content() {
		echo '';
	}
}
