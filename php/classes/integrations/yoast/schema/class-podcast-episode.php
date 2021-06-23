<?php
/**
 * WPSEO plugin file.
 *
 * @package Yoast\WP\SEO\Generators\Schema
 */

namespace SeriouslySimplePodcasting\Integrations\Yoast\Schema;

use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Returns schema PodcastEpisode data.
 *
 * @since 2.7.3
 */
class PodcastEpisode extends Abstract_Schema_Piece {

	/**
	 * Determines whether an Organization graph piece should be added.
	 *
	 * @return bool
	 */
	public function is_needed() {
		$ssp_post_types = ssp_post_types( true );

		return is_singular( $ssp_post_types );
	}

	/**
	 * Returns the Organization Schema data.
	 *
	 * @return array $data The Organization schema.
	 */
	public function generate() {
		global $ss_podcasting;

		$series_parts = [];
		$series       = wp_get_post_terms( $this->context->post->ID, 'series' );

		foreach ( $series as $term ) {
			/** @var \WP_Term $term */
			$series_parts[] = array(
				"@type" => "PodcastSeries",
				"name"  => $term->name,
				"url"   => get_term_link( $term ),
			);
		}

		$enclosure     = $ss_podcasting->get_enclosure( $this->context->post->ID );
		$description   = get_the_excerpt( $this->context->post->ID );
		$time_required = $this->generate_required_time( $this->context->post->ID );

		$schema = array(
			"@type"         => "PodcastEpisode",
			"url"           => $this->context->canonical,
			"name"          => $this->context->title,
			"datePublished" => date( 'Y-m-d', strtotime( $this->context->post->post_date ) ),
		);

		if ( $description ) {
			$schema['description'] = $description;
		}

		if ( $time_required ) {
			$schema['timeRequired'] = $time_required;
		}

		if ( $enclosure ) {
			$schema['associatedMedia'] = array(
				"@type"      => "MediaObject",
				"contentUrl" => $ss_podcasting->get_enclosure( $this->context->post->ID ),
			);
		}

		if ( $series_parts ) {
			$schema['partOfSeries'] = $series_parts;
		}

		return $schema;
	}

	protected function generate_required_time( $episode_id ) {
		$duration = get_post_meta( $episode_id, 'duration', true );

		preg_match( '/(\d\d:\d\d:\d\d)/', $duration, $matches );

		if ( empty( $matches ) ) {
			return null;
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
			$time .= intval( $hours ) . 'H';
		}
		if ( $minutes ) {
			$time .= intval( $minutes ) . 'M';
		}

		return $time;
	}
}
