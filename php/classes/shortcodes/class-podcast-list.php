<?php
/**
 * Podcast List shortcode class.
 *
 * @package SeriouslySimplePodcasting
 * @since 3.13.0
 */

namespace SeriouslySimplePodcasting\ShortCodes;

use SeriouslySimplePodcasting\Renderers\Renderer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Podcast List Shortcode
 *
 * @author     Serhiy Zakharchenko
 * @package    SeriouslySimplePodcasting
 * @since      3.13.0
 */
class Podcast_List implements Shortcode {

	/**
	 * Minimum number of columns allowed.
	 *
	 * @since 3.13.0
	 */
	const MIN_COLUMNS = 1;

	/**
	 * Maximum number of columns allowed.
	 *
	 * @since 3.13.0
	 */
	const MAX_COLUMNS = 3;

	/**
	 * Renderer instance.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Initializes the podcast list shortcode.
	 *
	 * @since 3.13.0
	 */
	public function __construct() {
		$this->renderer = new Renderer();
	}

	/**
	 * Renders the podcast list shortcode with the provided attributes.
	 *
	 * @since 3.13.0
	 *
	 * @param  array $params  Shortcode attributes.
	 * @return string          HTML output
	 */
	public function shortcode( $params ) {
		$defaults = array(
			'ids'     => '',
			'columns' => 1,
		);

		$args = shortcode_atts( $defaults, $params, 'ssp_podcasts' );

		// Validate and sanitize columns parameter.
		$columns = $this->validate_columns_parameter( $args['columns'] );

		// Get podcasts based on IDs parameter.
		$podcasts = $this->get_podcasts( $args['ids'] );

		// Prepare template data.
		$template_data = array(
			'podcasts' => $podcasts,
			'columns'  => $columns,
		);

		// Render the template.
		return $this->renderer->fetch( 'podcast-list', $template_data );
	}

	/**
	 * Retrieves and processes podcast data for the shortcode display.
	 *
	 * @since 3.13.0
	 *
	 * @param string $ids Comma-separated podcast IDs.
	 * @return array Array of podcast data.
	 */
	private function get_podcasts( $ids = '' ) {
		// Get all podcasts using existing function.
		$podcasts = ssp_get_podcasts( false );

		// Filter by specific IDs if provided.
		$podcasts = $this->filter_podcasts_by_ids( $podcasts, $ids );

		// Process each podcast into our data structure.
		$podcasts_data = array();
		foreach ( $podcasts as $podcast ) {
			if ( is_wp_error( $podcast ) ) {
				continue;
			}

			$podcast_data = array(
				'id'            => $podcast->term_id,
				'name'          => $podcast->name,
				'description'   => $podcast->description,
				'slug'          => $podcast->slug,
				'episode_count' => $this->get_episode_count( $podcast->term_id ),
				'cover_image'   => $this->get_cover_image( $podcast->term_id ),
				'url'           => $this->get_podcast_url( $podcast ),
			);

			$podcasts_data[] = $podcast_data;
		}

		return $podcasts_data;
	}

	/**
	 * Filters podcast collection to include only specified IDs.
	 *
	 * @since 3.13.0
	 *
	 * @param array  $podcasts Array of podcast term objects.
	 * @param string $ids      Comma-separated podcast IDs.
	 * @return array Filtered array of podcast term objects.
	 */
	private function filter_podcasts_by_ids( $podcasts, $ids ) {
		if ( empty( $ids ) ) {
			return $podcasts;
		}

		$podcast_ids = array_map( 'intval', explode( ',', $ids ) );
		$podcast_ids = array_filter( $podcast_ids );

		if ( empty( $podcast_ids ) ) {
			return array();
		}

		return array_filter(
			$podcasts,
			function ( $podcast ) use ( $podcast_ids ) {
				return in_array( $podcast->term_id, $podcast_ids, true );
			}
		);
	}

	/**
	 * Counts published episodes for a specific podcast.
	 *
	 * @since 3.13.0
	 *
	 * @param int $podcast_id Podcast term ID.
	 * @return int Episode count.
	 */
	private function get_episode_count( $podcast_id ) {
		$episodes = ssp_episode_repository()->get_podcast_episodes( $podcast_id );
		return count( $episodes );
	}

	/**
	 * Retrieves the cover image URL for a specific podcast.
	 *
	 * @since 3.13.0
	 *
	 * @param int $podcast_id Podcast term ID.
	 * @return string Cover image URL or default image.
	 */
	private function get_cover_image( $podcast_id ) {
		$term = get_term( $podcast_id, ssp_series_taxonomy() );
		if ( is_wp_error( $term ) || ! $term ) {
			return '';
		}

		return ssp_get_podcast_image_src( $term, 'medium' );
	}

	/**
	 * Generates the public URL for a podcast term.
	 *
	 * @since 3.13.0
	 *
	 * @param object $podcast Podcast term object.
	 * @return string Podcast URL.
	 */
	private function get_podcast_url( $podcast ) {
		$url = get_term_link( $podcast, ssp_series_taxonomy() );

		// Return the URL or empty string if there's an error.
		return is_wp_error( $url ) ? '' : $url;
	}

	/**
	 * Validates and sanitizes the columns parameter to ensure it's within the allowed range.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $columns The columns parameter value.
	 * @return int Validated columns value (MIN_COLUMNS to MAX_COLUMNS).
	 */
	private function validate_columns_parameter( $columns ) {
		// Convert to integer and ensure it's within valid range.
		$columns = intval( $columns );

		// Ensure columns is within the allowed range.
		if ( $columns < self::MIN_COLUMNS ) {
			$columns = self::MIN_COLUMNS;
		} elseif ( $columns > self::MAX_COLUMNS ) {
			$columns = self::MAX_COLUMNS;
		}

		return $columns;
	}
}
