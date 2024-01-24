<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Exception;
use SeriouslySimplePodcasting\Traits\Elementor_Widget_Helper;

class Elementor_Subscribe_Buttons_Widget extends Widget_Base {

	use Elementor_Widget_Helper;

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
		return 'eicon-link';
	}

	public function get_categories() {
		return [ 'podcasting' ];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'seriously-simple-podcasting' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control( 'show_elements', $this->get_select_podcast_settings( false ) );

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

		echo ssp_app()->players_controller->render_subscribe_buttons( $episode_id );
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
