<?php
/**
 * Elementor Widget Helper.
 */
namespace SeriouslySimplePodcasting\Traits;

use Elementor\Controls_Manager;
use SeriouslySimplePodcasting\Renderers\Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Elementor_Widget_Helper.
 *
 * @author Sergiy Zakharchenko
 * @package SeriouslySimplePodcasting
 * @since 2.14.0
 */
trait Elementor_Widget_Helper {

	protected $select_podcast_settings;
	protected $renderer;

	protected function get_select_podcast_settings() {
		if ( $this->select_podcast_settings ) {
			return $this->select_podcast_settings;
		}

		$series = get_terms(
			array(
				'taxonomy' => 'series',
			)
		);

		$series_options = array(
			0 => 'Default',
		);

		if ( ! empty( $series ) ) {
			foreach ( $series as $term ) {
				if ( is_object( $term ) ) {
					$series_options[ $term->term_id ] = $term->name;
				}
			}
		}

		$series_options_ids = array_keys( $series_options );

		$this->select_podcast_settings = array(
			'label'    => __( 'Select Podcast', 'seriously-simple-podcasting' ),
			'type'     => Controls_Manager::SELECT2,
			'options'  => $series_options,
			'multiple' => false,
			'default'  => array_shift( $series_options_ids )
		);

		return $this->select_podcast_settings;
	}

	protected function add_episodes_query_controls( $args = array() ){

		$defaults = array(
			'episodes_number' => 3,
		);

		$args = wp_parse_args($args, $defaults);

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
			'podcast_term',
			$this->get_select_podcast_settings()
		);

		$this->add_control(
			'episodes_number',
			array(
				'label'   => __( 'Episodes Number', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => $args['episodes_number'],
			)
		);

		$this->add_control(
			'order_by',
			array(
				'label'   => __( 'Order Episodes By', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'published' => __( 'Published date' ),
					'recorded'  => __( 'Recorded date' ),
				),
				'default' => 'published',
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => __( 'Order', 'seriously-simple-podcasting' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'DESC' => __( 'DESC' ),
					'ASC'  => __( 'ASC' ),
				),
				'default' => 'DESC',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * @return Renderer
	 */
	protected function renderer() {
		if ( empty( $this->renderer ) ) {
			$this->renderer = Renderer::instance();
		}

		return $this->renderer;
	}
}
