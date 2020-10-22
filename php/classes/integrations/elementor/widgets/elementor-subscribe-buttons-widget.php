<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use SeriouslySimplePodcasting\Controllers\Players_Controller;

class Elementor_Subscribe_Buttons_Widget extends \Elementor\Widget_Base {
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
		return [ 'podcasting' ];
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

		$series = get_terms(
			array(
				'taxonomy' => 'series',
			)
		);

		$series_options = array(
			0 => 'Default',
		);

		if ( ! empty( $series ) ) {
			foreach ( $series as $key => $series ) {
				if ( is_object( $series ) ) {
					$series_options[ $series->term_id ] = $series->name;
				}
			}
		}

		$series_options_ids = array_keys( $series_options );
		$this->add_control(
			'show_elements',
			[
				'label'    => __( 'Select Podcast', 'plugin-domain' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'options'  => $series_options,
				'multiple' => false,
				'default'  => array_shift( $series_options_ids )
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
