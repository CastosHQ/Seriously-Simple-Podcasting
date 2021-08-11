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


	/**
	 * @param $episode_id
	 *
	 * @return int|null
	 */
	public function get_episode_series_id( $episode_id ) {
		$series_id = null;
		$series    = get_the_terms( $episode_id, 'series' );

		/**
		 * In some instances, this could return a WP_Error object
		 */
		if ( ! is_wp_error( $series ) && $series ) {
			$series_id = ( isset( $series[0] ) ) ? $series[0]->term_id : null;
		}

		return $series_id;
	}

	/**
	 * @param array $atts
	 *
	 * @return int[]|\WP_Post[]
	 */
	public function get_playlist_episodes( $atts ) {
		// Get all podcast post types
		$podcast_post_types = ssp_post_types( true );

		// Set up query arguments for fetching podcast episodes
		$query_args = array(
			'post_status'         => 'publish',
			'post_type'           => $podcast_post_types,
			'posts_per_page'      => (int) $atts['limit'] > 0 ? $atts['limit'] : 10,
			'order'               => $atts['order'],
			'orderby'             => $atts['orderby'],
			'ignore_sticky_posts' => true,
			'post__in'            => $atts['include'],
			'post__not_in'        => $atts['exclude'],
			'paged'               => $atts['page'] > 0 ? $atts['page'] : 1,
		);

		// Make sure to only fetch episodes that have a media file
		$query_args['meta_query'] = array(
			array(
				'key'     => 'audio_file',
				'compare' => '!=',
				'value'   => '',
			),
		);

		// Limit query to episodes in defined series only
		if ( $atts['series'] ) {
			$series_arr = strpos( $atts['series'], ',' ) ? explode( ',', $atts['series'] ) : (array) $atts['series'];

			foreach ( $series_arr as $series ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'series',
					'field'    => 'slug',
					'terms'    => $series,
				);
			}

			if ( count( $series_arr ) > 1 ) {
				$query_args['tax_query']['relation'] = 'OR';
			}
		}

		// Allow dynamic filtering of query args
		$query_args = apply_filters( 'ssp_podcast_playlist_query_args', $query_args );

		// Fetch all episodes for display
		return get_posts( $query_args );
	}

	/**
	 * @param $episode_id
	 *
	 * @return false|mixed|void
	 */
	public function get_podcast_title( $episode_id ) {
		$series_id = $this->get_episode_series_id( $episode_id );

		if ( $series_id ) {
			$title = get_option( 'ss_podcasting_data_title_' . $series_id );
		}

		if ( empty( $title ) ) {
			$title = get_option( 'ss_podcasting_data_title' );
		}

		return $title;
	}


	/**
	 * Get the latest episode ID for a player
	 *
	 * @return int
	 */
	public function get_latest_episode_id() {
		if ( is_admin() ) {
			$post_status = array( 'publish', 'draft', 'future' );
		} else {
			$post_status = array( 'publish' );
		}
		$args     = array(
			'fields'         => array( 'post_title, id' ),
			'posts_per_page' => 1,
			'post_type'      => ssp_post_types( true ),
			'post_status'    => $post_status,
		);
		$episodes = get_posts( $args );
		if ( empty( $episodes ) ) {
			return 0;
		}
		$episode = $episodes[0];

		return $episode->ID;
	}
}
