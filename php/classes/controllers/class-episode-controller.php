<?php

namespace SeriouslySimplePodcasting\Controllers;

/**
 * SSP Episode Controller
 *
 * @package Seriously Simple Podcasting
 */
class Episode_Controller extends Controller {

	/**
	 * Get episode enclosure
	 * @param  integer $episode_id ID of episode
	 * @return string              URL of enclosure
	 */
	public function get_enclosure( $episode_id = 0 ) {

		if ( $episode_id ) {
			return apply_filters( 'ssp_episode_enclosure', get_post_meta( $episode_id, apply_filters( 'ssp_audio_file_meta_key', 'audio_file' ), true ), $episode_id );
		}

		return '';
	}

	/**
	 * Get download link for episode
	 *
	 * @param $episode_id
	 * @param string $referrer
	 *
	 * @return string
	 */

	public function get_episode_download_link( $episode_id, $referrer = '' ) {

		// Get file URL
		$file = $this->get_enclosure( $episode_id );

		if ( ! $file ) {
			return '';
		}

		// Get download link based on permalink structure
		if ( get_option( 'permalink_structure' ) ) {
			$episode = get_post( $episode_id );
			// Get file extension - default to MP3 to prevent empty extension strings
			$ext = pathinfo( $file, PATHINFO_EXTENSION );
			if ( ! $ext ) {
				$ext = 'mp3';
			}
			$link = $this->home_url . 'podcast-download/' . $episode_id . '/' . $episode->post_name . '.' . $ext;
		} else {
			$link = add_query_arg( array( 'podcast_episode' => $episode_id ), $this->home_url );
		}

		// Allow for dyamic referrer
		$referrer = apply_filters( 'ssp_download_referrer', $referrer, $episode_id );

		// Add referrer flag if supplied
		if ( $referrer ) {
			$link = add_query_arg( array( 'ref' => $referrer ), $link );
		}

		return apply_filters( 'ssp_episode_download_link', esc_url( $link ), $episode_id, $file );
	}

	/**
	 * Returns the no album art image
	 *
	 * @return array
	 */
	private function get_no_album_art_image_array() {
		$src    = SSP_PLUGIN_URL . 'assets/images/no-album-art.png';
		$width  = 300;
		$height = 300;

		return compact( 'src', 'width', 'height' );
	}

	/**
	 * Convert the array returned from wp_get_attachment_image_src into a human readable version
	 * @todo check if there is a WordPress function for this
	 *
	 * @param $image_data_array
	 *
	 * @return mixed
	 */
	private function return_renamed_image_array_keys( $image_data_array ) {
		$new_image_data_array = array();
		if ( $image_data_array && ! empty( $image_data_array ) ) {
			$new_image_data_array['src']    = isset( $image_data_array[0] ) ? $image_data_array[0] : '';
			$new_image_data_array['width']  = isset( $image_data_array[1] ) ? $image_data_array[1] : '';
			$new_image_data_array['height'] = isset( $image_data_array[2] ) ? $image_data_array[2] : '';
		}

		return $new_image_data_array;
	}

	/**
	 * Check if the image in the formatted image_data_array is a square image
	 *
	 * @param array $image_data_array
	 *
	 * @return bool
	 */
	private function check_image_is_square( $image_data_array = array() ) {
		if ( isset( $image_data_array['width'] ) && isset( $image_data_array['height'] ) ) {
			if ( ( $image_data_array['width'] / $image_data_array['height'] ) === 1 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get Album Art for Player
	 *
	 * Iteratively tries to find the correct album art based on whether the desired image is of square aspect ratio.
	 * Falls back to default album art if it can not find the correct ones.
	 *
	 * @param $episode_id ID of the episode being loaded into the player
	 *
	 * @return array [ $src, $width, $height ]
	 *
	 * @since 1.19.4
	 */
	public function get_album_art( $episode_id = false ) {

		/**
		 * In case the episode id is not passed
		 */
		if ( ! $episode_id ) {
			return $this->get_no_album_art_image_array();
		}

		/**
		 * Option 1 : if the episode has a featured image that is square, then use that
		 */
		$thumb_id = get_post_thumbnail_id( $episode_id );
		if ( ! empty( $thumb_id ) ) {
			$image_data_array = $this->return_renamed_image_array_keys( wp_get_attachment_image_src( $thumb_id, 'medium' ) );
			if ( $this->check_image_is_square( $image_data_array ) ) {
				return $image_data_array;
			}
		}

		/**
		 * Option 2: if the episode belongs to a series, which has an image that is square, then use that
		 */
		$series_id    = false;
		$series_image = '';

		$series = get_the_terms( $episode_id, 'series' );

		if ( $series ) {
			$series_id = ( ! empty( $series ) && isset( $series[0] ) ) ? $series[0]->term_id : false;
		}

		if ( $series_id ) {
			$series_image = get_option( "ss_podcasting_data_image_{$series_id}", false );
		}

		if ( $series_image ) {
			$series_image_attachment_id = ssp_get_image_id_from_url( $series_image );
			$image_data_array           = $this->return_renamed_image_array_keys( wp_get_attachment_image_src( $series_image_attachment_id, 'medium' ) );
			if ( $this->check_image_is_square( $image_data_array ) ) {
				return $image_data_array;
			}
		}

		/**
		 * Option 3: if the feed settings have an image that is square, then use that
		 */
		$feed_image = get_option( 'ss_podcasting_data_image', false );
		if ( $feed_image ) {
			$feed_image_attachment_id = ssp_get_image_id_from_url( $feed_image );
			$image_data_array         = $this->return_renamed_image_array_keys( wp_get_attachment_image_src( $feed_image_attachment_id, 'medium' ) );
			if ( $this->check_image_is_square( $image_data_array ) ) {
				return $image_data_array;
			}
		}

		/**
		 * None of the above passed, return the no-album-art image
		 */
		return $this->get_no_album_art_image_array();
	}
}
