<?php

namespace SeriouslySimplePodcasting\Controllers;

/**
 * SSP Episode Controller
 *
 * To be used when retrieving specific data for a single episode
 * ALL public methods must at least have an episode id argument and should only return data relevant to an episode
 * ALL protected methods should only be helper methods to allow the public methods to function
 *
 * @todo consider moving this to an episode Repository or Model type class
 *
 * @package Seriously Simple Podcasting
 */
class Episode_Controller {

	/**
	 * Returns the no album art image
	 *
	 * @return array
	 */
	protected function get_no_album_art_image_array() {
		$src    = SSP_PLUGIN_URL . 'assets/images/no-album-art.png';
		$width  = 300;
		$height = 300;

		return compact( 'src', 'width', 'height' );
	}

	/**
	 * Convert the array returned from wp_get_attachment_image_src into a human readable version
	 *
	 * @param $image_data_array
	 *
	 * @return mixed
	 * @todo check if there is a WordPress function for this
	 *
	 */
	protected function return_renamed_image_array_keys( $image_data_array ) {
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
	protected function check_image_is_square( $image_data_array = array() ) {
		if ( isset( $image_data_array['width'] ) && isset( $image_data_array['height'] ) ) {
			if ( ( $image_data_array['width'] / $image_data_array['height'] ) === 1 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return a series id for an episode
	 *
	 * @param $id
	 *
	 * @return int
	 * @todo check if there is a global function for this, and use it.
	 */
	public function get_series_id( $id ) {
		$series_id = 0;
		$series    = get_the_terms( $id, 'series' );

		/**
		 * In some instances, this could return a WP_Error object
		 */
		if ( ! is_wp_error( $series ) && $series ) {
			$series_id = ( isset( $series[0] ) ) ? $series[0]->term_id : 0;
		}

		return $series_id;
	}

	/**
	 * Return feed url for a specific episode.
	 *
	 * @param $id
	 *
	 * @return string
	 *
	 */
	public function get_feed_url( $id ) {
		$feed_series = 'default';
		$series_id   = $this->get_series_id( $id );
		if ( ! empty( $series_id ) ) {
			$series      = get_term_by( 'id', $series_id, 'series' );
			$feed_series = $series->slug;
		}

		$permalink_structure = get_option( 'permalink_structure' );

		if ( $permalink_structure ) {
			$feed_slug = apply_filters( 'ssp_feed_slug', 'podcast' );
			$feed_url  = trailingslashit( home_url() ) . 'feed/' . $feed_slug;
		} else {
			$feed_url = trailingslashit( home_url() ) . '?feed=' . 'podcast';
		}

		if ( $feed_series && 'default' !== $feed_series ) {
			if ( $permalink_structure ) {
				$feed_url .= '/' . $feed_series;
			} else {
				$feed_url .= '&podcast_series=' . $feed_series;
			}
		}

		$feed_url = apply_filters( 'ssp_feed_url', $feed_url );

		return $feed_url;
	}

	/**
	 * Get episode enclosure
	 *
	 * @param integer $id ID of episode
	 *
	 * @return string              URL of enclosure
	 */
	public function get_enclosure( $id = 0 ) {

		if ( $id ) {
			return apply_filters( 'ssp_episode_enclosure', get_post_meta( $id, apply_filters( 'ssp_audio_file_meta_key', 'audio_file' ), true ), $id );
		}

		return '';
	}

	/**
	 * Get download link for episode
	 *
	 * @param $id
	 * @param string $referrer
	 *
	 * @return string
	 */

	public function get_episode_download_link( $id, $referrer = '' ) {

		// Get file URL
		$file = $this->get_enclosure( $id );

		if ( ! $file ) {
			return '';
		}

		$home_url = trailingslashit( home_url() );
		// Get download link based on permalink structure
		if ( get_option( 'permalink_structure' ) ) {
			$episode = get_post( $id );
			// Get file extension - default to MP3 to prevent empty extension strings
			$ext = pathinfo( $file, PATHINFO_EXTENSION );
			if ( ! $ext ) {
				$ext = 'mp3';
			}
			$link = $home_url . 'podcast-download/' . $id . '/' . $episode->post_name . '.' . $ext;
		} else {
			$link = add_query_arg( array( 'podcast_episode' => $id ), $home_url );
		}

		// Allow for dyamic referrer
		$referrer = apply_filters( 'ssp_download_referrer', $referrer, $id );

		// Add referrer flag if supplied
		if ( $referrer ) {
			$link = add_query_arg( array( 'ref' => $referrer ), $link );
		}

		return apply_filters( 'ssp_episode_download_link', esc_url( $link ), $id, $file );
	}

	/**
	 * Get Album Art for Player
	 *
	 * Iteratively tries to find the correct album art based on whether the desired image is of square aspect ratio.
	 * Falls back to default album art if it can not find the correct ones.
	 *
	 * @param $id ID of the episode being loaded into the player
	 *
	 * @return array [ $src, $width, $height ]
	 *
	 * @since 1.19.4
	 */
	public function get_album_art( $id = false ) {

		/**
		 * In case the episode id is not passed
		 */
		if ( ! $id ) {
			return $this->get_no_album_art_image_array();
		}

		/**
		 * Option 1 : if the episode has a featured image that is square, then use that
		 */
		$thumb_id = get_post_thumbnail_id( $id );
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

		$series = get_the_terms( $id, 'series' );

		/**
		 * In some instances, this could return a WP_Error object
		 */
		if ( ! is_wp_error( $series ) && $series ) {
			$series_id = ( isset( $series[0] ) ) ? $series[0]->term_id : false;
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

	/**
	 * @param $id episode id
	 *
	 * Get the podcast title of an episode
	 * Will check for a series, and return the series feed title first
	 * Then will attempt to return either the default feed title, or the site name
	 *
	 * @return false|mixed|void
	 */
	public function get_podcast_title( $id ) {
		get_option( 'ss_podcasting_data_title' );
		$series_id = $this->get_series_id( $id );
		if ( ! empty( $series_id ) ) {
			$podcast_title = get_option( 'ss_podcasting_data_title_' . $series_id );
			if ( ! empty( $podcast_title ) ) {
				return $podcast_title;
			}
		}

		return get_option( 'ss_podcasting_data_title', get_bloginfo( 'name' ) );
	}

}
