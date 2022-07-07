<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Interfaces\Service;

/**
 * SSP Series Handler
 *
 * @package Seriously Simple Podcasting
 */
class Series_Handler implements Service {

	const TAXONOMY = 'series';

	public function __construct() {
		$taxonomy = self::TAXONOMY;
		add_filter( "{$taxonomy}_row_actions", array($this, 'add_term_actions'), 10, 2 );
	}

	/**
	 * @param array $actions
	 * @param \WP_Term $term
	 *
	 * @return array
	 */
	public function add_term_actions( $actions, $term ) {

		$link = '<a href="%s">' . __( 'Edit&nbsp;Feed&nbsp;Details', 'seriously-simple-podcasting' ) . '</a>';
		$link = sprintf( $link, sprintf(
			'edit.php?post_type=%s&page=podcast_settings&tab=feed-details&feed-series=%s',
			SSP_CPT_PODCAST,
			$term->slug
		) );

		$actions['edit_feed_details'] = $link;

		return $actions;
	}

	public function maybe_save_series() {
		if ( ! isset( $_GET['page'] ) || 'podcast_settings' !== $_GET['page'] ) {
			return false;
		}
		if ( ! isset( $_GET['tab'] ) || 'feed-details' !== $_GET['tab'] ) {
			return false;
		}
		if ( ! isset( $_GET['settings-updated'] ) || 'true' !== $_GET['settings-updated'] ) {
			return false;
		}

		// Only do this if this is a Castos Customer
		if ( ! current_user_can( 'manage_podcast' ) || ! ssp_is_connected_to_castos() ) {
			return false;
		}

		if ( ! isset( $_GET['feed-series'] ) ) {
			$feed_series_slug = 'default';
		} else {
			$feed_series_slug = sanitize_text_field( $_GET['feed-series'] );
			if ( empty( $feed_series_slug ) ) {
				return false;
			}
		}

		if ( 'default' === $feed_series_slug ) {
			$series_data              = get_series_data_for_castos( 0 );
			$series_data['series_id'] = 0;
		} else {
			$series                   = get_term_by( 'slug', $feed_series_slug, 'series' );
			$series_data              = get_series_data_for_castos( $series->term_id );
			$series_data['series_id'] = $series->term_id;
		}

		$castos_handler = new Castos_Handler();
		$response       = $castos_handler->update_podcast_data( $series_data );

		if ( 'success' !== $response['status'] ) {
			return false;
		}

		return true;
	}

}
