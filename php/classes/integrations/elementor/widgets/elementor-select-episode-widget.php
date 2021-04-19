<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use SeriouslySimplePodcasting\Controllers\Episode_Controller;

class Elementor_Select_Episode_Widget extends \Elementor\Widget_Base {
	public function get_name() {
		return 'Select Episode';
	}

	public function get_title() {
		return __( 'Select Episode', 'seriously-simple-podcasting' );
	}

	public function get_icon() {
		return 'fa fa-check';
	}

	public function get_categories() {
		return [ 'podcasting' ];
	}

	public function get_episodes() {
		$args = array(
			'fields'          => array('post_title, id'),
			'posts_per_page'  => get_option('posts_per_page', 10),
			'post_type' => SSP_CPT_PODCAST
		);

		$episodes = get_posts($args);
		$episode_options = [];
		foreach($episodes as $episode) {
			$episode_options[$episode->ID] = $episode->post_title;
		}

		return $episode_options;
	}

	protected function _register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'seriously-simple-podcasting' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$episode_options = $this->get_episodes();

		$this->add_control(
			'show_elements',
			[
				'label'    => __( 'Show Elements', 'plugin-domain' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'options'  => $episode_options,
				'multiple' => true,
				'default'  => array_shift( array_values( $episode_options ) )
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$i        = 0;
		foreach ( $settings['show_elements'] as $element ) {
			$episode_ids[ $i ] = $element;
			$i ++;
		}

		$episode_controller = new Episode_Controller( __FILE__, SSP_VERSION );

		echo $episode_controller->episode_list( $episode_ids );
	}
}
