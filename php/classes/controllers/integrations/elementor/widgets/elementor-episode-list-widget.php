<?php

namespace SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets;

use SeriouslySimplePodcasting\Controllers\Episode_Controller;

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
		return [ 'basic' ];
	}

	public function get_episodes() {
		$args = array(
			'fields'          => array('post_title, id'),
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

		$episodeOptionsValues = array_values( $episodeOptions );
		$this->add_control(
			'show_elements',
			[
				'label' => __( 'Show Elements', 'plugin-domain' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'options' => $episodeOptions,
				'multiple' => true,
				'default' => array_shift( $episodeOptionsValues )
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$i = 0;
		foreach($settings['show_elements'] as $element) {
			$episodeIds[$i] = $element;
			$i++;
		}

		$episodeController = new Episode_Controller(__FILE__, SSP_VERSION);

		echo $episodeController->episode_list($episodeIds);
	}
}
