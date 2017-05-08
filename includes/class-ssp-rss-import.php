<?php
/**
 * Seriously Simple Podcasting RSS importer
 * Import RSS feed podcasts.
 *
 * @package Seriously Simple Podcasting
 */

// Load SimplePie Class API
require_once ABSPATH . 'wp-includes/class-simplepie.php';

/**
 * Class RSS_Import
 */
class SSP_RSS_Import {
	/**
	 * Posts
	 *
	 * @var array posts posts.
	 */
	public $posts = array();
	/**
	 * Feed Url
	 *
	 * @var string $feed_url url.
	 */
	public $feed_url;

	public function __construct( $url ) {
		$this->feed_url = $url;
	}
	/**
	 * Get the posts from the feed url.
	 */
	public function get_posts() {

		$ss_podcasting_podcast_rss_url = get_option( 'ss_podcasting_podcast_rss_url', '' );
		$feed = fetch_feed( $ss_podcasting_podcast_rss_url );

		$hard_limit = 5;
		$index = 0;
		$items = $feed->get_items();

		foreach ( $items as $item ) {
			$gm_date       = $item->get_gmdate();
			$post_date_gmt = strtotime( $gm_date );
			$post_date_gmt = gmdate( 'Y-m-d H:i:s', $post_date_gmt );
			$post_date     = get_date_from_gmt( $post_date_gmt );
			$post_content  = esc_sql( str_replace( "\n", '', $item->get_content() ) );
			$post_title    = esc_sql( $item->get_title() );
			$enclosure     = $item->get_enclosure();
			$audio_file    = esc_sql( $enclosure->get_link() );

			$guid          = esc_sql( $item->get_id() );
			$categories    = $item->get_categories();
			if ( is_array( $categories ) ) {
				$cat_index     = 0;
				foreach ( $categories as $category ) {
					$categories[ $cat_index ] = esc_sql( html_entity_decode( $category->get_term() ) );
					$cat_index ++;
				}
			} else {
				$categories = array();
			}
			$post_author           = 1;
			$post_status           = 'publish';
			$post_type             = 'podcast';
			$this->posts[ $index ] = compact( 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_status', 'post_type', 'audio_file', 'guid', 'categories' );
			$index ++;

			if ( defined( 'SSP_DEBUG' ) && SSP_DEBUG ) {
				if ( $index === $hard_limit ) {
					break;
				}
			}
		}
	}

	/**
	 * Import the Posts into WordPress
	 *
	 * @return int|void|WP_Error
	 */
	public function import_posts() {
		foreach ( $this->posts as $post ) {
			$post_id = post_exists( $post['post_title'], $post['post_content'], $post['post_date'] );
			if ( empty( $post_id ) ) {
				$post_id = wp_insert_post( $post );
				if ( $post_id && ! is_wp_error( $post_id ) ) {
					if ( isset( $post['audio_file'] ) && ! empty( $post['audio_file'] ) ) {
						update_post_meta( $post_id, 'audio_file', $post['audio_file'] );
					}
					if ( 0 !== count( $post['categories'] ) ) {
						wp_create_categories( $post['categories'], $post_id );
					}
				} else {
					$error_string = $post_id->get_error_message();
					echo esc_attr( $error_string );
				}
			}
		}
	}

	/**
	 * Run the import
	 *
	 * @return boolean
	 */
	public function import() {
		$this->get_posts();
		$result = $this->import_posts();
		if ( is_wp_error( $result ) ) {
			return false;
		}

		return true;
	}

}
