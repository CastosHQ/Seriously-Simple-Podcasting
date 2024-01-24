<?php
/**
 * Elementor Widget Helper.
 */
namespace SeriouslySimplePodcasting\Traits;

use Elementor\Controls_Manager;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;

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

	/**
	 * @var Renderer
	 * */
	protected $renderer;

	/**
	 * @var Episode_Repository $episode_repository
	 * */
	protected $episode_repository;

	protected function get_select_podcast_settings( $show_all_podcasts = true ) {
		if ( $this->select_podcast_settings ) {
			return $this->select_podcast_settings;
		}

		$series = get_terms(
			array(
				'taxonomy' => 'series',
			)
		);

		$series_options = $show_all_podcasts ? array(
			0 => __( 'All podcasts', 'seriously-simple-podcasting' ),
		) : array();

		$default_series_id = ssp_get_default_series_id();

		if ( ! empty( $series ) ) {
			foreach ( $series as $term ) {
				if ( is_object( $term ) ) {
					$term_name = ( $default_series_id === $term->term_id ) ? ssp_get_default_series_name( $term->name ): $term->name;
					$series_options[ $term->term_id ] = $term_name;
				}
			}
		}

		$this->select_podcast_settings = array(
			'label'    => __( 'Select Podcast', 'seriously-simple-podcasting' ),
			'type'     => Controls_Manager::SELECT2,
			'options'  => $series_options,
			'multiple' => false,
			'default'  => $default_series_id
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

	/**
	 * @return Episode_Repository
	 */
	protected function episode_repository() {
		if ( ! isset( $this->episode_repository ) ) {
			$this->episode_repository = ssp_get_service( 'episode_repository' );
		}

		return $this->episode_repository;
	}
}
