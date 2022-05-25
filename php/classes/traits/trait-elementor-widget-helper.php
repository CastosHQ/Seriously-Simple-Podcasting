<?php
/**
 * Elementor Widget Helper.
 */
namespace SeriouslySimplePodcasting\Traits;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Elementor_Widget_Helper.
 *
 * @author Sergey Zakharchenko
 * @package SeriouslySimplePodcasting
 * @since 2.14.0
 */
trait Elementor_Widget_Helper {

	protected $select_podcast_settings;

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
}
