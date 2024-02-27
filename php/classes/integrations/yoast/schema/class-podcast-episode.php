<?php
/**
 * WPSEO plugin file.
 *
 * @package Yoast\WP\SEO\Generators\Schema
 */

namespace SeriouslySimplePodcasting\Integrations\Yoast\Schema;

use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Returns schema PodcastEpisode data.
 *
 * @since 2.7.3
 */
class PodcastEpisode extends Abstract_Schema_Piece {

	/**
	 * @var Episode_Repository
	 * */
	protected $episode_repository;

	/**
	 * @param Episode_Repository $episode_repository
	 */
	public function __construct( $episode_repository ){
		$this->episode_repository = $episode_repository;
	}

	/**
	 * Determines whether PodcastEpisode graph piece should be added.
	 *
	 * @return bool
	 */
	public function is_needed() {
		$ssp_post_types = ssp_post_types( true );

		return is_singular( $ssp_post_types );
	}

	/**
	 * Returns the Podcast Schema data.
	 *
	 * @return array $data The schema data.
	 */
	public function generate() {
		$enclosure   = $this->episode_repository->get_enclosure( $this->context->post->ID );
		if ( ! $enclosure ) {
			return array();
		}

		$series_parts = [];
		$series       = wp_get_post_terms( $this->context->post->ID, 'series' );

		foreach ( $series as $term ) {
			/** @var \WP_Term $term */

			$url = get_term_link( $term );

			if ( is_wp_error( $url ) ) {
				continue;
			}

			$series_parts[] = array(
				"@type" => "PodcastSeries",
				"name"  => $term->name,
				"url"   => $url,
				"id"    => $url . '#/schema/podcastSeries',
			);
		}

		$description = get_the_excerpt( $this->context->post->ID );
		$duration    = $this->get_duration( $this->context->post->ID, $enclosure );

		$schema = array(
			"@type"               => "PodcastEpisode",
			"@id"                 => $this->context->canonical . '#/schema/podcast',
			"eventAttendanceMode" => "https://schema.org/OnlineEventAttendanceMode",
			"location"            => array(
				"@type" => "VirtualLocation",
				"url"   => $this->context->canonical,
				"@id"   => $this->context->canonical . "#webpage",
			),
			"url"                 => $this->context->canonical,
			"name"                => $this->context->title,
			"datePublished"       => date( 'Y-m-d', strtotime( $this->context->post->post_date ) ),
		);

		if ( $description ) {
			$schema['description'] = $description;
		}

		if ( ! empty( $duration ) ) {
			$schema['duration'] = $duration;
		}

		$schema = $this->add_enclosure_to_schema( $enclosure, $schema );

		if ( $series_parts ) {
			$schema['partOfSeries'] = $series_parts;
		}

		return $schema;
	}

	/**
	 * Gets a ISO 8601 duration compliant duration string.
	 *
	 * @param int                 $episode_id
	 * @param string              $enclosure
	 *
	 * @return string
	 */
	protected function get_duration( $episode_id, $enclosure ) {
		$duration = get_post_meta( $episode_id, 'duration', true );
		if ( empty( $duration ) ) {
			$duration = $this->episode_repository->get_file_duration( $enclosure );
			if ( $duration ) {
				update_post_meta( $episode_id, 'duration', $duration );
			}
		}

		preg_match( '/(\d\d:\d\d:\d\d)/', $duration, $matches );

		if ( empty( $matches ) ) {
			return '';
		}

		$time_parts = explode( ':', $duration );

		$hours   = intval( $time_parts[0] );
		$minutes = intval( $time_parts[1] );
		$seconds = intval( $time_parts[2] );

		if ( ( ! $minutes && $seconds ) || $seconds > 30 ) {
			$minutes ++;
		}

		$time = 'P';

		if ( $hours ) {
			$time .= $hours . 'H';
		}
		if ( $minutes ) {
			$time .= $minutes . 'M';
		}

		return $time;
	}

	/**
	 * Add the enclosure to the schema based on its type.
	 *
	 * @param string              $enclosure
	 * @param array               $schema
	 *
	 * @return array
	 */
	private function add_enclosure_to_schema( $enclosure, $schema ) {
		$type = $this->episode_repository->get_episode_type( $this->context->post->ID );

		$object = array(
			"contentUrl"  => $enclosure,
			"contentSize" => get_post_meta( $this->context->post->ID , 'filesize' , true ),
		);

		if ( $type === 'audio' ) {
			$object['@type'] = "AudioObject";
			$schema['audio'] = $object;

			return $schema;
		}

		if ( $type === 'video' ) {
			$object['@type'] = "VideoObject";
			$schema['video'] = $object;

			return $schema;
		}

		$object['@type']           = "MediaObject";
		$schema['associatedMedia'] = $object;

		return $schema;
	}
}
