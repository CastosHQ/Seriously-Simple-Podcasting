<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;


use Elementor\Widget_Base;

class Elementor_Html_Player_Widget extends Widget_Base {

	/**
	 * Class constructor.
	 *
	 * @param array $data Widget data.
	 * @param array $args Widget arguments.
	 */
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
		$this->register_scripts_and_styles();
	}

	/**
	 * Register any scripts and styles for this widget
	 */
	public function register_scripts_and_styles() {
		if ( wp_script_is( 'ssp-castos-player', 'registered' ) && ! wp_script_is( 'ssp-castos-player', 'enqueued' ) ) {
			wp_enqueue_script( 'ssp-castos-player' );
		}
		if ( wp_style_is( 'ssp-castos-player', 'registered' ) && ! wp_style_is( 'ssp-castos-player', 'enqueued' ) ) {
			wp_enqueue_style( 'ssp-castos-player' );
		}
	}

	public function get_name() {
		return 'Castos Player';
	}

	public function get_title() {
		return __( 'Castos Player', 'seriously-simple-podcasting' );
	}

	public function get_icon() {
		return 'eicon-play-o';
	}

	public function get_categories() {
		return [ 'podcasting' ];
	}

	public function get_episodes() {
		$args = array(
			'fields'         => array( 'post_title, id' ),
			'posts_per_page' => -1,
			'post_type'      => ssp_post_types( true ),
			'post_status'    => array( 'publish', 'draft', 'future' ),
		);

		$episodes       = get_posts( $args );
		$episode_options = [
			'-1' => __( 'Current Episode', 'seriously-simple-podcasting' ),
			'0'  => __( 'Latest Episode', 'seriously-simple-podcasting' ),
		];
		foreach ( $episodes as $episode ) {
			$episode_options[ (string) $episode->ID ] = $episode->post_title;
		}

		return $episode_options;
	}

	protected function register_controls() {

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
				'label'   => __( 'Select Episode', 'seriously-simple-podcasting' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => $episode_options,
				'default' => '-1'
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		global $ss_podcasting;
		$players_controller = $ss_podcasting->players_controller;
		$settings           = $this->get_settings_for_display();
		$episode_id         = $settings['show_elements'];
		if ( '-1' === $episode_id ) {
			$episode_id = get_post()->ID;
		}
		if ( empty( $episode_id ) ) {
			$episode_id = $players_controller->get_latest_episode_id();
		}
		$html_player = $players_controller->render_html_player( $episode_id );
		echo $html_player;
	}

	protected function _content_template() {
		?>
        <# _.each( settings.show_elements, function( element ) { #>
        <div>{{{ element }}}</div>
        <# } ) #>
		<?php
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
