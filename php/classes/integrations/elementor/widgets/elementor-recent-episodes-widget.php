<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Plugin as ElementorPlugin;
use Elementor\Widget_Base;
use SeriouslySimplePodcasting\Traits\Elementor_Widget_Helper;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

class Elementor_Recent_Episodes_Widget extends Widget_Base {

	use Useful_Variables;
	use Elementor_Widget_Helper;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->init_useful_variables();

		// Enqueue styles early in preview mode
		if ( ElementorPlugin::$instance->preview->is_preview_mode() ) {
			$this->enqueue_style();
		}
	}

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

	protected function register_controls() {

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
				'label'     => __( 'Image Source', 'seriously-simple-podcasting' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'featured_image' => __( 'Featured Image' ),
					'player_image'   => __( 'Player Image' ),
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
				'options'   => array(
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

		$this->add_episodes_query_controls();
	}

	protected function render() {
		$template_data = $this->get_settings_for_display();

		$this->enqueue_style();
		$episode_repository        = $this->episode_repository();
		$template_data['episodes'] = $episode_repository->get_episodes_query( $template_data )->get_posts();
		$template_data             = apply_filters( 'recent_episodes_template_data', $template_data );

		$this->renderer()->render( 'episodes/recent-episodes', $template_data );
	}

	/**
	 * Render plain content (what data should be stored in the post_content).
	 *
	 * @since 2.11.0
	 */
	public function render_plain_content() {
		echo '';
	}

	protected function enqueue_style() {
		wp_enqueue_style( 'ssp-recent-episodes', $this->assets_url . 'css/recent-episodes.css', array(), $this->version );
	}
}
