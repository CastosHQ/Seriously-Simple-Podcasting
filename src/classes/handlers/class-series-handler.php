<?php

namespace SeriouslySimplePodcasting\Handlers;

/**
 * SSP Series Handler
 *
 * @package Seriously Simple Podcasting
 */
class Series_Handler {

	public function maybe_save_series() {
		// Only do this if this is a Castos Customer
		if ( ! ssp_is_connected_to_podcastmotor() ) {
			return false;
		}

		if ( ! isset( $_GET['page'] ) || 'podcast_settings' !== $_GET['page'] ) {
			return false;
		}
		if ( ! isset( $_GET['tab'] ) || 'feed-details' !== $_GET['tab'] ) {
			return false;
		}
		if ( ! isset( $_GET['settings-updated'] ) || 'true' !== $_GET['settings-updated'] ) {
			return false;
		}

		if ( isset( $_GET['feed-series'] ) ) {
			$feed_series_slug = ( isset( $_GET['feed-series'] ) ? filter_var( $_GET['feed-series'], FILTER_SANITIZE_STRING ) : '' );
			if ( empty( $feed_series_slug ) ) {
				return false;
			}
			$series                   = get_term_by( 'slug', $feed_series_slug, 'series' );
			$series_data              = get_series_data_for_castos( $series->term_id );
			$series_data['series_id'] = $series->term_id;
		} else {
			$series_data              = get_series_data_for_castos( 0 );
			$series_data['series_id'] = 0;
		}

		$castos_handler = new Castos_Handler();
		$response       = $castos_handler->upload_series_to_podmotor( $series_data );

		if ( ! 'success' === $response['status'] ) {
			return false;
		}

		return true;
	}

}
