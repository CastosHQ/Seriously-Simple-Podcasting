<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class Elementor_Recent_Episodes_Widget extends Widget_Base {
	public function get_name() {
		return 'Recent Episodes';
	}

	public function get_title() {
		return __( 'Recent Episodes', 'seriously-simple-podcasting' );
	}

	public function get_icon() {
		return 'eicon-archive-posts';
	}

	public function get_categories() {
		return array( 'podcasting' );
	}

	protected function _register_controls() {

		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'seriously-simple-podcasting' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_episode_image',
			array(
				'label'   => __( 'Show Episode Image', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'episode_image_source',
			array(
				'label'   => __( 'Image Source', 'seriously-simple-podcasting' ),
				'type'      => Controls_Manager::SELECT,
				'options' => array(
					'featured_image' => __( 'Featured Image' ),
					'player_image'  => __( 'Player Image' ),
				),
				'default'   => 'featured_image',
				'condition' => array(
					'show_episode_image' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_episode_title',
			array(
				'label'   => __( 'Show Episode Title', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_episode_excerpt',
			array(
				'label'   => __( 'Show Episode Excerpt', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_read_more',
			array(
				'label'   => __( 'Show Read More', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'read_more_text',
			array(
				'label'     => __( 'Read More Text', 'seriously-simple-podcasting' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'Listen →',
				'condition' => array(
					'show_read_more' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_date',
			array(
				'label'   => __( 'Show Episode Date', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'date_source',
			array(
				'label'     => __( 'Episode Date Source', 'seriously-simple-podcasting' ),
				'type'      => Controls_Manager::SELECT,
				'options' => array(
					'published' => __( 'Published date' ),
					'recorded'  => __( 'Recorded date' ),
				),
				'default'   => 'published',
				'condition' => array(
					'show_date' => 'yes',
				),
			)
		);

		$this->add_control(
			'date_format',
			array(
				'label'     => __( 'Episode Date Format', 'seriously-simple-podcasting' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'F j, Y',
				'condition' => array(
					'show_date' => 'yes',
				),
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => __( 'Columns', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3,
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'query_section',
			array(
				'label' => __( 'Query', 'seriously-simple-podcasting' ),
				'tab'   => 'Query',
			)
		);

		$this->add_control(
			'episode_types',
			array(
				'label'   => __( 'Post type', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'only_podcast'      => sprintf( __( 'Only %s' ), SSP_CPT_PODCAST ),
					'all_podcast_types' => __( 'All podcast post types' ),
				),
				'default' => 'all_podcast_types',
			)
		);

		$this->add_control(
			'episodes_number',
			array(
				'label'   => __( 'Episodes Number', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3,
			)
		);

		$this->add_control(
			'order_by',
			array(
				'label'     => __( 'Order Episodes By', 'seriously-simple-podcasting' ),
				'type'      => Controls_Manager::SELECT,
				'options' => array(
					'published' => __( 'Published date' ),
					'recorded'  => __( 'Recorded date' ),
				),
				'default'   => 'published',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo ssp_episode_controller()->render_recent_episodes( $settings );
	}
}
