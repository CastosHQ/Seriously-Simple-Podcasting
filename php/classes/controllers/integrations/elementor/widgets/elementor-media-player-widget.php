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
			'fields'          => array('post_title, id'), // Only get post IDs
			'posts_per_page'  => -1,
			'post_type' => 'podcast'
		);

		$episodes = get_posts($args);
		$episodeOptions = [];
		foreach($episodes as $episode) {
			$episodeOptions[$episode->ID] = $episode->post_title;
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

		$this->add_control(
			'show_elements',
			[
				'label' => __( 'Show Elements', 'plugin-domain' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options' => $episodeOptions,
			]
		);

		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$episodes = $this->get_episodes();

		$media_player = new Players_Controller(__FILE__, SSP_VERSION);
		foreach ( $settings['show_elements'] as $element ) {
			echo '<div>' . $episodes[$element] . '</div>';
			echo '<div>' . $media_player->render_media_player($element) . '</div>';
		}

	}

	protected function _content_template() {
		?>
		<# _.each( settings.show_elements, function( element ) { #>
		<div>{{{ element }}}</div>
		<# } ) #>
		<?php
	}
}
