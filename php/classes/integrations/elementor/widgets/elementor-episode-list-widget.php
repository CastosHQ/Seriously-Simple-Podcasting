<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Elementor_Widget_Helper;

class Elementor_Episode_List_Widget extends Widget_Base {

	use Elementor_Widget_Helper;

	/**
	 * @var Episode_Repository
	 * */
	protected $episode_repository;

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

	protected function register_controls() {

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

		$this->add_episodes_query_controls( array( 'episodes_number' => 10 ) );
	}

	protected function render() {
		$settings          = $this->get_settings_for_display();
		$settings['paged'] = get_query_var( 'paged' ) ?: 1;

		$supported_args = array(
			'episodes_number',
			'episode_types',
			'order_by',
			'order',
			'podcast_term',
			'paged',
		);

		$query_args = array_intersect_key( $settings, array_flip( $supported_args ) );

		$episode_repository = $this->episode_repository();

		$data = array(
			'player'               => ssp_frontend_controller()->players_controller,
			'episodes_query'       => $episode_repository->get_episodes_query( $query_args ),
			'show_featured_image'  => $settings['show_featured_image'],
			'show_episode_player'  => $settings['show_episode_player'],
			'show_episode_excerpt' => $settings['show_episode_excerpt'],
		);

		$data = apply_filters( 'episode_list_data', $data );

		$this->renderer()->render( 'episodes/all-episodes-list', $data );
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
