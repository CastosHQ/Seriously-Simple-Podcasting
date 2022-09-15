<?php

namespace SeriouslySimplePodcasting\Handlers;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Helpers\Log_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * External RSS feed importer
 *
 * @author      Jonathan Bossenger, Sergiy Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Classes
 * @since       1.19.18
 */
class RSS_Import_Handler {

	const RSS_IMPORT_DATA_KEY = 'ssp_rss_import_data';

	const ITEMS_PER_REQUEST = 10;

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
	private $episodes_count = 0;

	/**
	 * Number of episodes successfully added
	 *
	 * @var int
	 */
	private $episodes_added = 0;

	/**
	 * Episode titles added
	 *
	 * @var array
	 */
	private $episodes_imported = array();

	/**
	 * @var Log_Helper
	 */
	private $logger;


	/**
	 * SSP_External_RSS_Importer constructor.
	 *
	 * @param $ssp_external_rss
	 */
	public function __construct( $ssp_external_rss ) {
		$this->rss_feed  = $ssp_external_rss['import_rss_feed'];
		$this->post_type = $ssp_external_rss['import_post_type'];
		$this->series    = $ssp_external_rss['import_series'];
		$this->logger    = new Log_Helper();
	}

	public static function update_import_data( $key, $data ) {
		$feed_data         = self::get_import_data();
		$feed_data[ $key ] = $data;
		update_option( self::RSS_IMPORT_DATA_KEY, $feed_data );
	}

	public static function reset_import_data() {
		delete_option( self::RSS_IMPORT_DATA_KEY );
	}

	public static function get_import_data( $key = null ) {
		$data = get_option( self::RSS_IMPORT_DATA_KEY, array() );
		if ( $key ) {
			return isset( $data[ $key ] ) ? $data[ $key ] : null;
		}

		return $data;
	}

	public function load_import_data() {
		$feed_content            = $this->get_import_data( 'feed_content' );
		$this->feed_object       = simplexml_load_string( $feed_content );
		$this->episodes_count    = $this->get_import_data( 'episodes_count' );
		$this->episodes_added    = $this->get_import_data( 'episodes_added' );
		$this->episodes_imported = $this->get_import_data( 'episodes_imported' );
	}

	/**
	 * Load the xml feed url into the feed_object
	 */
	public function load_rss_feed() {
		$feed_content = file_get_contents( $this->rss_feed );
		$this->update_import_data( 'feed_content', $feed_content );
		$this->feed_object = simplexml_load_string( $feed_content );

		$this->episodes_count = count( $this->feed_object->channel->item );
		$this->update_import_data( 'episodes_count', $this->episodes_count );
	}

	/**
	 * Update the import progress option
	 */
	public function update_import_progress() {
		$progress = round( ( $this->episodes_added / $this->episodes_count ) * 100 );

		$this->update_import_data( 'episodes_added', $this->episodes_added );
		$this->update_import_data( 'episodes_imported', $this->episodes_imported );
		$this->update_import_data( 'import_progress', $progress );
	}

	/**
	 * Import the RSS Feed episodes
	 *
	 * @return array
	 */
	public function import_rss_feed() {
		try {
			set_time_limit( 0 );
			$start_from = filter_input( INPUT_GET, 'start_from', FILTER_VALIDATE_INT );

			if ( $start_from ) {
				$this->load_import_data();
			} else {
				$this->reset_import_data();
				$this->load_rss_feed();
				$this->check_lock_status();
			}

			for ( $i = $start_from, $count = 0; $i < $this->episodes_count; $i ++, $count ++ ) {
				if ( $count >= self::ITEMS_PER_REQUEST ) {
					return $this->create_response( 'Partially imported', $i );
				}
				$item = $this->feed_object->channel->item[ $i ];
				$this->create_episode( $item );
			}

			$this->finish_import();

			return $this->create_response( 'RSS Feed successfully imported' );
		} catch ( \Exception $e ) {
			$this->logger->log( __METHOD__ . ' Error: ' . $e->getMessage() );

			$this->reset_import_data();

			return array(
				'status'  => 'error',
				'message' => $e->getMessage(),
			);
		}
	}

	protected function create_response( $msg = '', $start_from = null ) {
		return array(
			'status'     => 'success',
			'message'    => $msg,
			'count'      => $this->episodes_added,
			'episodes'   => $this->episodes_imported,
			'start_from' => $start_from,
		);
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	protected function check_lock_status() {
		if ( ! $this->is_rss_feed_locked() ) {
			return;
		}

		$msg = 'Your podcast cannot be imported at this time because the RSS feed is locked by the existing podcast hosting provider. ';
		$msg .= 'Please unlock your RSS feed with your current host before attempting to import again. ';
		$msg .= 'You can find out more about the podcast:lock tag here - https://support.castos.com/article/289-external-rss-feed-import-canceled';

		$msg = __( $msg, 'seriously-simple-podcasting' );

		throw new \Exception( sprintf( $msg, 'https://support.castos.com/article/289-external-rss-feed-import-canceled' ) );
	}

	protected function get_last_imported() {

	}

	protected function finish_import() {
		update_option( 'ssp_external_rss', '' );
		update_option( 'ssp_rss_import', '100' );
	}

	/**
	 * @param \SimpleXMLElement $item
	 *
	 * @return void
	 */
	protected function create_episode( $item ) {

		$post_data = $this->get_post_data( $item );

		// Add the post
		$post_id = wp_insert_post( $post_data );

		/**
		 * If an error occurring adding a post, continue the loop
		 */
		if ( is_wp_error( $post_id ) ) {
			$this->logger->log( __METHOD__ . ' Could not create episode!', compact( 'post_data' ) );

			return;
		}

		$this->save_enclosure( $post_id, $this->get_enclosure_url( $item ) );
		$this->save_image( $post_id, $this->get_image_url( $item ) );

		// Set the series, if it is available
		if ( ! empty( $this->series ) ) {
			wp_set_post_terms( $post_id, $this->series, 'series' );
		}

		// Update the added count and imported title array
		$this->episodes_added ++;
		$this->episodes_imported[] = $post_data['post_title'];

		$this->update_import_progress();
	}

	/**
	 * Get the value for post_content from the RSS episode item
	 *
	 * @param \SimpleXMLElement $item
	 * @param \SimpleXMLElement $itunes
	 *
	 * @return string
	 */
	public function get_item_post_content( $item, $itunes ) {
		$content = $item->children( 'content', true );
		if ( ! empty( $content->encoded ) ) {
			return trim( (string) $content->encoded );
		}
		if ( ! empty( $item->description ) ) {
			return trim( (string) $item->description );
		}
		if ( ! empty( $itunes->summary ) ) {
			return trim( (string) $itunes->summary );
		}

		return '';
	}

	/**
	 * Create post_data from RSS Feed item
	 *
	 * @param \SimpleXMLElement $item
	 *
	 * @return array
	 */
	public function get_post_data( $item ) {
		$itunes                    = $item->children( 'http://www.itunes.com/dtds/podcast-1.0.dtd' );
		$post_data                 = array();
		$post_data['post_content'] = $this->get_item_post_content( $item, $itunes );
		$post_data['post_excerpt'] = trim( (string) $itunes->subtitle );
		$post_data['post_title']   = trim( (string) $item->title );
		$post_data['post_status']  = 'publish';
		$post_data['post_author']  = get_current_user_id();
		$post_data['post_date']    = date( 'Y-m-d H:i:s', strtotime( (string) $item->pubDate ) ); //phpcs:ignore WordPress.NamingConventions
		$post_data['post_type']    = $this->post_type;

		return $post_data;
	}

	/**
	 * @param int $post_id
	 * @param string $image_url
	 *
	 * @return bool
	 */
	protected function save_image( $post_id, $image_url ) {
		if ( ! $image_url ) {
			return false;
		}

		$image_id = $this->save_image_from_url( $image_url );

		if ( is_wp_error( $image_id ) ) {
			return false;
		}

		update_post_meta( $post_id, 'cover_image_id', $image_id );

		$url = wp_get_attachment_url( $image_id );
		update_post_meta( $post_id, 'cover_image', $url );

		return true;
	}

	/**
	 * @param string $url
	 *
	 * @return bool|int|string|\WP_Error
	 */
	protected function save_image_from_url( $url ) {
		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$file_array = array(
			'name'     => basename( $url ),
			'tmp_name' => $tmp
		);

		return media_handle_sideload( $file_array );
	}

	/**
	 * @param int $post_id
	 * @param string $url
	 *
	 * @return void
	 */
	protected function save_enclosure( $post_id, $url ) {
		// strips out any possible weirdness in the file url
		$url = preg_replace( '/(?s:.*)(https?:\/\/(?:[\w\-\.]+[^#?\s]+)(?:\.mp3))(?s:.*)/', '$1', $url );

		// Set the audio_file
		add_post_meta( $post_id, 'audio_file', $url );
	}

	/**
	 * @return bool
	 */
	protected function is_rss_feed_locked() {
		return 'yes' === (string) $this->feed_object->channel->children( 'podcast', true )->locked;
	}

	/**
	 * @param \SimpleXMLElement $item
	 *
	 * @return string
	 */
	protected function get_enclosure_url( $item ) {
		return (string) @$item->enclosure['url'];
	}

	/**
	 * @param \SimpleXMLElement $item
	 *
	 * @return string
	 */
	protected function get_image_url( $item ) {
		$image_url = '';

		if ( count( $item->children( 'itunes', true )->image ) ) {
			$image_url = (string) @$item->children( 'itunes', true )->image->attributes()->href;
		}

		if ( ! $image_url && count( $item->children( 'googleplay', true )->image ) ) {
			$image_url = (string) @$item->children( 'googleplay', true )->image->attributes()->href;
		}

		return $image_url;
	}
}
