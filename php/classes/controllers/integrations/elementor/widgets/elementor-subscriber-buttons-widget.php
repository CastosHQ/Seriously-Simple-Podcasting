<?php

namespace SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets;

use SeriouslySimplePodcasting\Controllers\Players_Controller;

class Elementor_Subscribe_Buttons extends \Elementor\Widget_Base {
	public function get_name() {
		return 'Subscribe Buttons';
	}

	public function get_title() {
		return __( 'Subscribe Buttons', 'seriously-simple-podcasting' );
	}

	public function get_icon() {
		return 'fa fa-link';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	public function get_episodes() {
		$args = array(
			'fields'         => array( 'post_title, id' ),
			'posts_per_page' => - 1,
			'post_type'      => 'podcast'
		);

		$episodes       = get_posts( $args );
		$episodeOptions = [];
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
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$episodeOptions = $this->get_episodes();

		$series = get_terms( array(
				'taxonomy' => 'series'
			)
		);

		$seriesOptions = array(
			0 => 'Default'
		);

		foreach ( $series as $key => $series ) {
			$seriesOptions[ $series->term_id ] = $series->name;
		}

		$seriesOptionsIds = array_keys( $seriesOptions );

		$episodeOptionsValues = array_values( $episodeOptions );
		$this->add_control(
			'show_elements',
			[
				'label'    => __( 'Select Podcast', 'plugin-domain' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'options'  => $seriesOptions,
				'multiple' => false,
				'default'  => array_shift( $seriesOptionsIds )
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$player   = new Players_Controller( __FILE__, SSP_VERSION );
		$seriesId = $settings['show_elements'];

		$episode = array();

		$args = array(
			'post_type' => 'podcast',
			'tax_query' => array(
				array(
					'taxonomy' => 'series',
					'field'    => 'term_id',
					'terms'    => $seriesId,
				)
			)
		);

		$posts = new \WP_Query( $args );

		if ( ! empty( $posts->posts ) ) {
			$episode['id'] = $posts->posts[0]->ID;
		}

		echo $player->render_subscribe_buttons( $episode );
	}

}
