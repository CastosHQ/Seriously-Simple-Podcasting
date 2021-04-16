<?php

namespace SeriouslySimplePodcasting\Repositories;

/**
 * Episode Repository
 *
 * Used to set or get specific data for an episode
 * Eventually any methods on the episode controller
 * not specific to processing/rendering a request to display and episode
 * should be moved here
 *
 * @package Seriously Simple Podcasting
 * @since 2.4.3
 */
class Episode_Repository {

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
			$feed_slug = apply_filters( 'ssp_feed_slug', SSP_CPT_PODCAST );
			$feed_url  = trailingslashit( home_url() ) . 'feed/' . $feed_slug;
		} else {
			$feed_url = trailingslashit( home_url() ) . '?feed=' . $this->token;
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

}
