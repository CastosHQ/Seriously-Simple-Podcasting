<?php

namespace SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets;

use SeriouslySimplePodcasting\Controllers\Players_Controller;

class Elementor_Media_Player_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'Media Player';
	}

	public function get_title() {
		return __( 'Media Player', 'seriously-simple-podcasting' );
	}

	public function get_icon() {
		return 'fa fa-play';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	public function get_episodes() {
		$args = array(
			'fields'         => array( 'post_title, id' ),
			'posts_per_page' => - 1,
			'post_type'      => ssp_post_types( true ),
			'post_status'    => array( 'publish', 'draft', 'future' ),
		);

		$episodes       = get_posts( $args );
		$episodeOptions = [0 => 'Latest Epsiode'];
		foreach ( $episodes as $episode ) {
			$episodeOptions[ $episode->ID ] = $episode->post_title;
		}

		return $episodeOptions;
	}

	protected function _register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'seriously-simple-podcasting' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$episodeOptions = $this->get_episodes();
		$episodeOptionsValues = array_values( $episodeOptions );
		$this->add_control(
			'show_elements',
			[
				'label' => __( 'Select Episode', 'seriously-simple-podcasting' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'options' => $episodeOptions,
				'default' => '0'
			]
		);

		$this->end_controls_section();

	}

	protected function render() {
		$players_controller = new Players_Controller( __FILE__, SSP_VERSION );
		$settings           = $this->get_settings_for_display();
		$episode_id         = $settings['show_elements'];
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
}
