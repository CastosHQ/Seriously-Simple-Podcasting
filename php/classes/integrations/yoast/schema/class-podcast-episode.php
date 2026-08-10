<?php
/**
 * Yoast SEO Podcast Episode Schema.
 *
 * @package Seriously Simple Podcasting
 * @since 2.7.3
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
	 * Episode repository instance.
	 *
	 * @var Episode_Repository
	 */
	protected $episode_repository;

	/**
	 * Constructor.
	 *
	 * @param Episode_Repository $episode_repository Episode repository instance.
	 */
	public function __construct( $episode_repository ) {
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
	/**
	 * Generate podcast episode schema.
	 *
	 * @return array
	 */
	public function generate() {
		$enclosure = $this->episode_repository->get_enclosure( $this->context->post->ID );
		if ( ! $enclosure ) {
			return array();
		}

		$series_parts = array();

		/**
		 * Series terms attached to this episode.
		 *
		 * @var \WP_Term[] $series
		 */
		$series = wp_get_post_terms( $this->context->post->ID, ssp_series_taxonomy() );

		foreach ( $series as $term ) {
			$url = get_term_link( $term );

			if ( is_wp_error( $url ) ) {
				continue;
			}

			$series_parts[] = array(
				'@type' => 'PodcastSeries',
				'name'  => $term->name,
				'url'   => $url,
				'id'    => $url . '#/schema/podcastSeries',
			);
		}

		$description = get_the_excerpt( $this->context->post->ID );
		$duration    = $this->get_duration( $this->context->post->ID, $enclosure );

		$schema = array(
			'@type'         => 'PodcastEpisode',
			'@id'           => $this->context->canonical . '#/schema/podcast',
			'url'           => $this->context->canonical,
			'name'          => $this->context->title,
			'datePublished' => gmdate( 'Y-m-d', strtotime( $this->context->post->post_date ) ),
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
	 * @param int    $episode_id ID of the episode whose duration meta is read.
	 * @param string $enclosure  Episode media URL, used to calculate the duration when no meta is stored.
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

		$duration = trim( (string) $duration );

		// SSP stores durations unpadded (`0:27`, `1:13:38`), while hand-entered and
		// Castos-synced values may be zero-padded — accept both, in either arity.
		if ( ! preg_match( '/^\d+:\d{1,2}(:\d{1,2})?$/', $duration ) ) {
			return '';
		}

		$time_parts = array_map( 'intval', explode( ':', $duration ) );

		if ( 2 === count( $time_parts ) ) {
			array_unshift( $time_parts, 0 );
		}

		list( $hours, $minutes, $seconds ) = $time_parts;

		// Round the minute up past the half, and lift a sub-minute duration to one
		// minute so it is not reported as zero.
		if ( $seconds > 30 || ( ! $hours && ! $minutes && $seconds ) ) {
			++$minutes;
		}

		// Rounding the 59th minute up makes a whole hour, not a 60th minute.
		if ( 60 === $minutes ) {
			++$hours;
			$minutes = 0;
		}

		if ( ! $hours && ! $minutes ) {
			return '';
		}

		$time = 'PT';

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
	 * @param string $enclosure Episode media URL to expose as the media object's contentUrl.
	 * @param array  $schema    Episode schema the media object is added to.
	 *
	 * @return array
	 */
	private function add_enclosure_to_schema( $enclosure, $schema ) {
		$type = $this->episode_repository->get_episode_type( $this->context->post->ID );

		$object = array(
			'contentUrl'  => $enclosure,
			'contentSize' => get_post_meta( $this->context->post->ID, 'filesize', true ),
		);

		if ( 'audio' === $type ) {
			$object['@type'] = 'AudioObject';
			$schema['audio'] = $object;

			return $schema;
		}

		if ( 'video' === $type ) {
			$object['@type'] = 'VideoObject';
			$schema['video'] = $object;

			return $schema;
		}

		$object['@type']           = 'MediaObject';
		$schema['associatedMedia'] = $object;

		return $schema;
	}
}
