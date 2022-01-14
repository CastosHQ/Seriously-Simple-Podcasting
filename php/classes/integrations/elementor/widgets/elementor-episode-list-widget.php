<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use SeriouslySimplePodcasting\Controllers\Episode_Controller;

class Elementor_Episode_List_Widget extends Widget_Base {
	public function get_name() {
		return 'Episode List';
	}

	public function get_title() {
		return __( 'Episode List', 'seriously-simple-podcasting' );
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	public function get_categories() {
		return [ 'podcasting' ];
	}

	protected function _register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'seriously-simple-podcasting' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_featured_image',
			[
				'label'   => __( 'Show Featured Image', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes'
			]
		);
		$this->add_control(
			'show_episode_player',
			[
				'label'   => __( 'Show Episode Player', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes'
			]
		);
		$this->add_control(
			'show_episode_excerpt',
			[
				'label'   => __( 'Show Episode Excerpt', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::SWITCHER,
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
		global $ss_podcasting;
		echo $ss_podcasting->episode_controller->render_episodes( $render_settings );
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
