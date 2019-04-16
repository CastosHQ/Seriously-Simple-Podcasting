<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSP_External_RSS_Importer {

	private $rss_feed;

	private $feed_object;

	private $podcast_count = 0;

	private $podcast_added = 0;

	private $podcast_success = 0;

	private $podcast_failure = 0;

	public function __construct( $rss_feed ) {
		$this->rss_feed = $rss_feed;
	}

	public function load_rss_feed() {
		$this->feed_object = simplexml_load_file( $this->rss_feed );
	}

	public function update_ssp_rss_import() {
		$progress = round( ( $this->podcast_added / $this->podcast_count ) * 100 );
		update_option( 'ssp_rss_import', $progress );
	}

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
				'post_type'    => 'podcast', // todo get type from import selection
			);

			// Add the post
			$post_id = wp_insert_post( $post );

			if ( is_wp_error( $post_id ) ) {
				$this->podcast_failure ++;
			}

			// Update the added count
			$this->podcast_added ++;

			// Set the audio_file
			$audio_file = add_post_meta( $post_id, 'audio_file', $url );

			// Log whether or not this failed.
			if ( ! is_wp_error( $audio_file ) ) {
				$this->podcast_success ++;
			}

			$this->update_ssp_rss_import();
		}

		$response = array(
			'status'  => 'success',
			'message' => 'RSS Feed successfully imported',
			'count'   => $this->podcast_added,
		);

		return $response;

	}
}
