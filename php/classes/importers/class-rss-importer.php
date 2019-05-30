<?php

namespace SeriouslySimplePodcasting\Importers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * External RSS feed importer
 *
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Classes
 * @since       1.19.18
 */
class Rss_Importer {

	/**
	 * RSS feed url
	 *
	 * @var mixed
	 */
	private $rss_feed;

	/**
	 * Post type to import episodes to
	 *
	 * @var mixed
	 */
	private $post_type;

	/**
	 * Series to import episodes to
	 *
	 * @var mixed
	 */
	private $series;

	/**
	 * Feed object created by loading the xml url
	 *
	 * @var
	 */
	private $feed_object;

	/**
	 * Number of episodes processed
	 *
	 * @var int
	 */
	private $podcast_count = 0;

	/**
	 * Number of episodes successfully added
	 *
	 * @var int
	 */
	private $podcast_added = 0;

	/**
	 * Episode titles added
	 *
	 * @var array
	 */
	private $podcasts_imported = array();

	/**
	 * SSP_External_RSS_Importer constructor.
	 *
	 * @param $ssp_external_rss
	 */
	public function __construct( $ssp_external_rss ) {
		$this->rss_feed  = $ssp_external_rss['import_rss_feed'];
		$this->post_type = $ssp_external_rss['import_post_type'];
		$this->series    = $ssp_external_rss['import_series'];
	}

	/**
	 * Load the xml feed url into the feed_object
	 */
	public function load_rss_feed() {
		$this->feed_object = simplexml_load_file( $this->rss_feed );
	}

	/**
	 * Update the import progress option
	 */
	public function update_ssp_rss_import() {
		$progress = round( ( $this->podcast_added / $this->podcast_count ) * 100 );
		update_option( 'ssp_rss_import', $progress );
	}

	/**
	 * Import the RSS Feed episodes
	 *
	 * @return array
	 */
	public function import_rss_feed() {

		$this->load_rss_feed();

		$this->podcast_count = count( $this->feed_object->channel->item );

		for ( $i = 0; $i < $this->podcast_count; $i ++ ) {

			$item = $this->feed_object->channel->item[ $i ];

			$itunes      = $item->children( 'http://www.itunes.com/dtds/podcast-1.0.dtd' );
			$post_title  = trim( (string) $item->title );
			$post_author = get_current_user_id();
			$url         = (string) $item->enclosure['url'];
			$post_date   = strtotime( (string) $item->pubDate ); //phpcs:ignore WordPress.NamingConventions

			// Setup the post array.
			$post = array(
				'post_content' => trim( (string) $itunes->summary ),
				'post_excerpt' => trim( (string) $itunes->subtitle ),
				'post_title'   => $post_title,
				'post_status'  => 'publish',
				'post_author'  => $post_author,
				'post_date'    => date( 'Y-m-d H:i:s', $post_date ),
				'post_type'    => $this->post_type,
			);

			// Add the post
			$post_id = wp_insert_post( $post );

			/**
			 * If an error occurring adding a post, continue the loop
			 */
			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			// strips out any possible weirdness in the file url
			$url = preg_replace( '/(?s:.*)(https?:\/\/(?:[\w\-\.]+[^#?\s]+)(?:\.mp3))(?s:.*)/', '$1', $url );

			// Set the audio_file
			add_post_meta( $post_id, 'audio_file', $url );

			// Set the series, if it is available
			if ( ! empty( $this->series ) ) {
				wp_set_post_terms( $post_id, $this->series, 'series' );
			}

			// Update the added count and imported title array
			$this->podcast_added ++;
			$this->podcasts_imported[] = $post_title;

			$this->update_ssp_rss_import();
		}

		update_option( 'ssp_external_rss', '' );
		update_option( 'ssp_rss_import', '100' );

		$response = array(
			'status'   => 'success',
			'message'  => 'RSS Feed successfully imported',
			'count'    => $this->podcast_added,
			'episodes' => $this->podcasts_imported,
		);

		return $response;

	}
}
