<?php
/**
 * WPSEO plugin file.
 *
 * @package Yoast\WP\SEO\Generators\Schema
 */

namespace SeriouslySimplePodcasting\Integrations\Yoast\Schema;

use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Returns schema PodcastSeries data.
 *
 * @since 2.7.3
 */
class PodcastSeries extends Abstract_Schema_Piece {

	/**
	 * Determines whether PodcastSeries graph piece should be added.
	 *
	 * @return bool
	 */
	public function is_needed() {
		return is_tax( 'series' );
	}

	/**
	 * Returns the PodcastSeries Schema data.
	 *
	 * @return array $data The PodcastSeries schema.
	 */
	public function generate() {
		$series_repository = ssp_series_repository();

		/**
		 * @var \WP_Term
		 * */
		$series = get_queried_object();

		$description = trim( strip_tags( get_the_archive_description() ) );

		$author = $this->get_series_author( $series );

		$schema = array(
			"@type"   => "PodcastSeries",
			"@id"     => $this->context->canonical . '#/schema/podcastSeries',
			"image"   => $series_repository->get_image_src( $series ),
			"url"     => $this->context->canonical,
			"name"    => $this->context->title,
			"webFeed" => $series_repository->get_feed_url( $series ),
		);

		if ( $description ) {
			$schema['description'] = $description;
		}

		if ( $author ) {
			$schema['author'] = [
				"@type" => "Person",
				"name"  => $author,
			];
		}

		return $schema;
	}

	/**
	 * @param \WP_Term $series
	 *
	 * @return string
	 */
	protected function get_series_author( $series ) {
		$option = 'ss_podcasting_data_author';
		$author = get_option( $option . '_' . $series->term_id, '' );

		if ( ! $author ) {
			$author = get_option( $option, get_bloginfo( 'name' ) );
		}

		return $author;
	}
}
