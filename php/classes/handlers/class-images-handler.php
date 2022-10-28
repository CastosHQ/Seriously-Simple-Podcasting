<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Interfaces\Service;

/**
 * SSP Roles Handler
 *
 * @package Seriously Simple Podcasting
 * @since 2.6.3
 */
class Images_Handler implements Service {

	const MIN_FEED_IMAGE_SIZE = 1400;
	const MAX_FEED_IMAGE_SIZE = 3000;

	/**
	 * @param string $image_url
	 *
	 * @return bool
	 */
	public function is_feed_image_valid( $image_url ) {
		$key = md5( $image_url );
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
				$valid = $width === $height && $width >= $min_size && $width <= $max_size;
			}
			wp_cache_set( $key, $valid, 'valid', WEEK_IN_SECONDS );
		}

		return $valid;
	}


	/**
	 * @param array $image_data Converted image data array with width and height keys
	 *
	 * @return bool
	 */
	public function is_image_square( $image_data = array() ) {
		if ( isset( $image_data['width'] ) && isset( $image_data['height'] ) ) {
			if ( ( $image_data['width'] / $image_data['height'] ) === 1 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Almost the same function as wp_get_attachment_image_src(), but returning the associative human readable array
	 *
	 * @param $attachment_id
	 * @param string $size
	 *
	 * @return array
	 */
	public function get_attachment_image_src( $attachment_id, $size = 'medium' ) {
		$src = wp_get_attachment_image_src( $attachment_id, $size );
		return $this->make_associative_image_src( $src );
	}

	/**
	 * Convert the array returned from wp_get_attachment_image_src into a human readable version
	 *
	 * @param $image_data_array
	 *
	 * @return mixed
	 *
	 */
	protected function make_associative_image_src( $image_data_array ) {
		$new_image_data_array = array();
		if ( is_array( $image_data_array ) && $image_data_array ) {
			$new_image_data_array['src']    = isset( $image_data_array[0] ) ? $image_data_array[0] : '';
			$new_image_data_array['width']  = isset( $image_data_array[1] ) ? $image_data_array[1] : '';
			$new_image_data_array['height'] = isset( $image_data_array[2] ) ? $image_data_array[2] : '';
		}

		return $new_image_data_array;
	}
}
