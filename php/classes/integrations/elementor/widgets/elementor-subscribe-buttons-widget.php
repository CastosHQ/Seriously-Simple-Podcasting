<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use Exception;
use SeriouslySimplePodcasting\Controllers\Players_Controller;

class Elementor_Subscribe_Buttons_Widget extends \Elementor\Widget_Base {

	/**
	 * Class constructor.
	 *
	 * @param array $data Widget data.
	 * @param null $args Widget arguments.
	 *
	 * @throws Exception
	 */
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
		$this->register_scripts_and_styles();
	}

	/**
	 * Register any scripts and styles for this widget
	 */
	public function register_scripts_and_styles() {
		$assets_url = trailingslashit( SSP_PLUGIN_URL ) . 'assets/';
		$version    = SSP_VERSION;
		wp_register_style( 'ssp-subscribe-buttons', $assets_url . 'css/subscribe-buttons.css', array(), $version );
		wp_enqueue_style( 'ssp-subscribe-buttons' );
	}

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
				'label'    => __( 'Select Podcast', 'seriously-simple-podcasting' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'options'  => $series_options,
				'multiple' => false,
				'default'  => array_shift( $series_options_ids )
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings    = $this->get_settings_for_display();
		$series_id   = $settings['show_elements'];
		$args        = array(
			'post_type' => ssp_post_types( true ),
			'tax_query' => array(
				array(
					'taxonomy' => 'series',
					'field'    => 'term_id',
					'terms'    => $series_id,
				),
			),
		);
		$episode_id  = 0;
		$posts_query = new \WP_Query( $args );
		$posts       = $posts_query->get_posts();

		if ( ! empty( $posts ) ) {
			$episode_id = $posts[0]->ID;
		}

		$player = new Players_Controller( __FILE__, SSP_VERSION );
		echo $player->render_subscribe_buttons( $episode_id );
	}

}
