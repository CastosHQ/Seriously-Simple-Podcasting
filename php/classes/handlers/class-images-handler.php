<?php
/**
 * Images Handler
 *
 * @package SeriouslySimplePodcasting
 * @category Handlers
 * @since 2.6.3
 */

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Interfaces\Service;

/**
 * Class Images_Handler
 *
 * Handles image validation and processing for podcast images.
 *
 * @package Seriously Simple Podcasting
 * @since 2.6.3
 */
class Images_Handler implements Service {

	/**
	 * Minimum size in pixels for podcast feed images.
	 *
	 * @var int
	 */
	const MIN_FEED_IMAGE_SIZE = 1400;

	/**
	 * Maximum size in pixels for podcast feed images.
	 *
	 * @var int
	 */
	const MAX_FEED_IMAGE_SIZE = 3000;

	/**
	 * Validates if a feed image meets the size requirements.
	 *
	 * @param string $image_url URL of the image to validate.
	 *
	 * @return bool True if image is valid, false otherwise.
	 */
	public function is_feed_image_valid( $image_url ) {
		$key   = md5( $image_url );
		$valid = wp_cache_get( $key, 'valid' );

		if ( false === $valid ) {
			$image_id = attachment_url_to_postid( $image_url );

			$image_att = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : null;
			$min_size  = apply_filters( 'ssp_episode_min_image_size', self::MIN_FEED_IMAGE_SIZE );
			$max_size  = apply_filters( 'ssp_episode_min_image_size', self::MAX_FEED_IMAGE_SIZE );
			if ( empty( $image_att ) ) {
				$valid = false;
			} else {
				$width  = isset( $image_att[1] ) ? $image_att[1] : 0;
				$height = isset( $image_att[2] ) ? $image_att[2] : 0;
				$valid  = $width === $height && $width >= $min_size && $width <= $max_size;
			}
			wp_cache_set( $key, $valid, 'valid', WEEK_IN_SECONDS );
		}

		return $valid;
	}


	/**
	 * Checks if an image is square (width equals height).
	 *
	 * @param array $image_data Converted image data array with width and height keys.
	 *
	 * @return bool True if image is square, false otherwise.
	 */
	public function is_image_square( $image_data = array() ) {
		if ( isset( $image_data['width'] ) && isset( $image_data['height'] ) ) {
			return ( $image_data['width'] === $image_data['height'] );
		}

		return false;
	}

	/**
	 * Gets attachment image source data in a human-readable format.
	 * Similar to wp_get_attachment_image_src(), but returns an associative array.
	 *
	 * @param int    $attachment_id The attachment ID.
	 * @param string $size          The image size to retrieve. Default 'medium'.
	 *
	 * @return array Array containing image data (src, width, height, alt).
	 */
	public function get_attachment_image_src( $attachment_id, $size = 'medium' ) {
		$src = wp_get_attachment_image_src( $attachment_id, $size );
		return $this->make_associative_image_src( $src, $attachment_id );
	}

	/**
	 * Converts the array returned from wp_get_attachment_image_src into a human-readable version.
	 *
	 * @param array    $image_data_array Array containing image data from wp_get_attachment_image_src.
	 * @param int|null $attachment_id    Optional. The attachment ID for getting alt text.
	 *
	 * @return array Associative array containing image data (src, width, height, alt).
	 */
	protected function make_associative_image_src( $image_data_array, $attachment_id = null ) {
		if ( ! is_array( $image_data_array ) || ! $image_data_array ) {
			return array();
		}

		$new_image_data_array['src']    = isset( $image_data_array[0] ) ? $image_data_array[0] : '';
		$new_image_data_array['width']  = isset( $image_data_array[1] ) ? $image_data_array[1] : '';
		$new_image_data_array['height'] = isset( $image_data_array[2] ) ? $image_data_array[2] : '';

		$alt_text = $attachment_id ? get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) : '';

		$new_image_data_array['alt'] = $alt_text ?: '';

		return $new_image_data_array;
	}
}
