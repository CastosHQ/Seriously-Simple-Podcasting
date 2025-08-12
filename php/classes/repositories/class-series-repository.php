<?php
/**
 * Series Repository class.
 *
 * @package SeriouslySimplePodcasting
 * @since 3.0.1
 */

namespace SeriouslySimplePodcasting\Repositories;

use SeriouslySimplePodcasting\Interfaces\Singleton;
use SeriouslySimplePodcasting\Traits\Singleton as SingletonTrait;
use SeriouslySimplePodcasting\Traits\Useful_Variables;
use WP_Term;

/**
 * Episode Repository
 *
 * Used to set or get specific data for repository
 *
 * @package Seriously Simple Podcasting
 * @since 3.0.1
 */
class Series_Repository implements Singleton {

	use SingletonTrait;

	use Useful_Variables;

	/**
	 * Protected constructor.
	 *
	 * Initializes useful variables.
	 */
	protected function __construct() {
		$this->init_useful_variables();
	}

	/**
	 * Get feed URL for a series.
	 *
	 * @param WP_Term $term Series term object.
	 *
	 * @return string
	 */
	public function get_feed_url( $term ) {
		$series_slug = $term->slug;

		if ( get_option( 'permalink_structure' ) ) {
			$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
			$feed_url  = $this->home_url . 'feed/' . $feed_slug . '/' . $series_slug;
		} else {
			$feed_url = add_query_arg(
				array(
					'feed'           => $this->token,
					'podcast_series' => $series_slug,
				),
				$this->home_url
			);
		}

		return $feed_url;
	}

	/**
	 * Get image source for a series.
	 *
	 * @param WP_Term $term Series term object.
	 * @param string  $size Image size.
	 *
	 * @return string
	 */
	public function get_image_src( $term, $size = 'thumbnail' ) {

		if ( ! empty( $term->term_id ) ) {
			$media_id = get_term_meta( $term->term_id, SSP_CPT_PODCAST . '_series_image_settings', true );
		}

		$default_image = esc_url( SSP_PLUGIN_URL . 'assets/images/no-image.png' );

		if ( empty( $media_id ) ) {
			return $default_image;
		}

		$src = wp_get_attachment_image_src( $media_id, $size );

		return ! empty( $src[0] ) ? $src[0] : $default_image;
	}
}
