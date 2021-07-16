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
	 * Determines whether an Organization graph piece should be added.
	 *
	 * @return bool
	 */
	public function is_needed() {
		return is_tax( 'series' );
	}

	/**
	 * Returns the Organization Schema data.
	 *
	 * @return array $data The Organization schema.
	 */
	public function generate() {
		global $ssp_admin;

		/**
		 * @var \WP_Term
		 * */
		$series = get_queried_object();

		$description = trim( strip_tags( get_the_archive_description() ) );

		$author = $this->get_series_author( $series );

		$schema = array(
			"@type"   => "PodcastSeries",
			"image"   => $ssp_admin->get_series_image_src( $series ),
			"url"     => $this->context->canonical,
			"name"    => $this->context->title,
			"webFeed" => $ssp_admin->get_series_feed_url( $series ),
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
