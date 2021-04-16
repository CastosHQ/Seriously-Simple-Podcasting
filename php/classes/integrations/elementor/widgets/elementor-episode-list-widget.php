<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use SeriouslySimplePodcasting\Controllers\Episode_Controller;
use WP_Query;

class Elementor_Episode_List_Widget extends \Elementor\Widget_Base {
	public function get_name() {
		return 'Episode List';
	}

	public function get_title() {
		return __( 'Episode List', 'seriously-simple-podcasting' );
	}

	public function get_icon() {
		return 'fa fa-list';
	}

	public function get_categories() {
		return [ 'podcasting' ];
	}

	public function get_episodes() {
		$args = array(
			'fields'         => array( 'post_title, id' ),
			'posts_per_page' => get_option('posts_per_page', 10),
			'post_type'      => SSP_CPT_PODCAST
		);

		$episodes = new \WP_Query( $args );

		$episode_options = [];
		foreach ( $episodes as $episode ) {
			$episode_options[ $episode->ID ] = $episode->post_title;
		}

		return $episode_options;
	}

	protected function _register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'seriously-simple-podcasting' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_featured_image',
			[
				'label'   => __( 'Show Featured Image', 'seriously-simple-podcasting' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes'
			]
		);
		$this->add_control(
			'show_episode_player',
			[
				'label'   => __( 'Show Episode Player', 'seriously-simple-podcasting' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes'
			]
		);
		$this->add_control(
			'show_episode_excerpt',
			[
				'label'   => __( 'Show Episode Excerpt', 'seriously-simple-podcasting' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes'
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings          = $this->get_settings_for_display();
		$render_settings         = array(
			'show_featured_image'  => $settings['show_featured_image'],
			'show_episode_player'  => $settings['show_episode_player'],
			'show_episode_excerpt' => $settings['show_episode_excerpt'],
		);
		$episode_controller = new Episode_Controller( __FILE__, SSP_VERSION );
		echo $episode_controller->render_episodes( $render_settings );
	}
}
