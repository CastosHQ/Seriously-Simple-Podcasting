<?php

namespace SeriouslySimplePodcasting\Handlers;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Helpers\Log_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RSS Import Handler
 *
 * Handles importing podcast episodes from external RSS feeds.
 *
 * @package SeriouslySimplePodcasting
 * @category Handlers
 * @author Jonathan Bossenger, Sergiy Zakharchenko
 * @since 1.19.18
 */
class RSS_Import_Handler {

	/**
	 * Option key for storing RSS import data.
	 *
	 * @var string
	 */
	const RSS_IMPORT_DATA_KEY = 'ssp_rss_import_data';

	/**
	 * Form value used to create a new podcast.
	 *
	 * @since 3.18.0
	 *
	 * @var string
	 */
	const CREATE_NEW_SERIES = 'ssp_create_new';

	/**
	 * URL path segments that are not suitable podcast names.
	 *
	 * @since 3.18.0
	 *
	 * @var string[]
	 */
	const GENERIC_URL_SEGMENTS = array( 'rss', 'feed', 'feeds', 'podcast', 'feed.xml', 'rss.xml', 'index.xml', 'atom.xml' );

	/**
	 * Number of names to try before adding a random suffix.
	 *
	 * @since 3.18.0
	 *
	 * @var int
	 */
	const MAX_NAME_ATTEMPTS = 50;

	/**
	 * Number of items to process per request.
	 *
	 * @var int
	 */
	const ITEMS_PER_REQUEST = 3;

	/**
	 * Running-import lock: option storing the last request timestamp.
	 * An option, not a transient, so a cache flush cannot lift it.
	 *
	 * @var string
	 */
	const IMPORTING_LOCK = 'ssp_rss_import_running';

	/**
	 * Lock lifetime after the last request; longer means the import was abandoned.
	 *
	 * @var int
	 */
	const IMPORTING_TTL = 10 * MINUTE_IN_SECONDS;

	/**
	 * RSS feed URL to import from.
	 *
	 * @var string
	 */
	private $rss_feed;

	/**
	 * Post type to import episodes to.
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * Target podcast ID, or CREATE_NEW_SERIES before a new podcast is created.
	 *
	 * @var int|string
	 */
	private $series;

	/**
	 * Feed object created by loading the XML URL.
	 *
	 * @var \SimpleXMLElement
	 */
	private $feed_object;

	/**
	 * Total number of episodes in the feed.
	 *
	 * @var int
	 */
	private $episodes_count = 0;

	/**
	 * Number of episodes successfully imported.
	 *
	 * @var int
	 */
	private $episodes_added = 0;

	/**
	 * Titles of successfully imported episodes.
	 *
	 * @var string[]
	 */
	private $episodes_imported = array();

	/**
	 * Logger instance.
	 *
	 * @var Log_Helper
	 */
	private $logger;

	/**
	 * Castos handler, used to push the imported podcast on completion.
	 *
	 * @var Castos_Handler
	 */
	private $castos_handler;


	/**
	 * RSS_Import_Handler constructor.
	 *
	 * @param array          $ssp_external_rss {
	 *     RSS import configuration.
	 *
	 *     @type string $import_rss_feed  RSS feed URL to import from.
	 *     @type string $import_post_type Post type to import episodes to.
	 *     @type int|string $import_series Target podcast ID, or CREATE_NEW_SERIES.
	 * }
	 * @param Castos_Handler $castos_handler Castos handler.
	 */
	public function __construct( $ssp_external_rss, $castos_handler ) {
		$this->rss_feed       = $ssp_external_rss['import_rss_feed'];
		$this->post_type      = $ssp_external_rss['import_post_type'];
		$this->series         = $ssp_external_rss['import_series'];
		$this->castos_handler = $castos_handler;
		$this->logger         = new Log_Helper();
	}

	/**
	 * Update the import data
	 *
	 * @param string $key
	 * @param mixed  $data
	 *
	 * @return void
	 */
	public static function update_import_data( $key, $data ) {
		$feed_data         = self::get_import_data();
		$feed_data[ $key ] = $data;
		update_option( self::RSS_IMPORT_DATA_KEY, $feed_data );
	}

	/**
	 * Reset the import data
	 *
	 * @return void
	 */
	public static function reset_import_data() {
		delete_option( 'ssp_external_rss' );
		delete_option( self::RSS_IMPORT_DATA_KEY );
		self::stop_importing();
	}

	/**
	 * Get the import data
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public static function get_import_data( $key = null, $default = null ) {
		$data = get_option( self::RSS_IMPORT_DATA_KEY, array() );
		if ( $key ) {
			return isset( $data[ $key ] ) ? $data[ $key ] : $default;
		}

		return $data;
	}

	/**
	 * Whether an RSS import is actively running — an import request ran within
	 * the last IMPORTING_TTL seconds and the import has not completed or been
	 * reset since. Self-expiring, so an abandoned import cannot keep
	 * suppressing Castos series pushes indefinitely.
	 *
	 * @since 3.18.0
	 *
	 * @return bool
	 */
	public static function is_importing() {
		$locked_at = (int) get_option( self::IMPORTING_LOCK, 0 );

		return $locked_at && ( time() - $locked_at ) < self::IMPORTING_TTL;
	}

	/**
	 * Marks an import as running (or refreshes the lock on each chunk).
	 *
	 * @since 3.18.0
	 *
	 * @return void
	 */
	public static function start_importing() {
		update_option( self::IMPORTING_LOCK, time(), false );
	}

	/**
	 * Releases the running-import lock.
	 *
	 * @since 3.18.0
	 *
	 * @return void
	 */
	public static function stop_importing() {
		delete_option( self::IMPORTING_LOCK );
	}

	/**
	 * Load the import data
	 *
	 * @return bool
	 */
	public function load_import_data() {
		$feed_content = $this->get_import_data( 'feed_content' );
		if ( empty( $feed_content ) ) {
			return false;
		}

		$this->feed_object       = simplexml_load_string( $feed_content );
		$this->episodes_count    = $this->get_import_data( 'episodes_count' );
		$this->episodes_added    = $this->get_import_data( 'episodes_added' );
		$this->episodes_imported = $this->get_import_data( 'episodes_imported' );

		return true;
	}

	/**
	 * Load the xml feed url into the feed_object
	 */
	public function load_rss_feed() {
		$wp_remote_content = wp_remote_get( $this->rss_feed );

		if ( is_wp_error( $wp_remote_content ) || empty( $wp_remote_content['body'] ) ) {
			$error = sprintf(
				'Could not load external feed %s. Please check the feed URL or your server firewall restrictions and try again.',
				$this->rss_feed
			);

			throw new \Exception( $error );
		} else {
			$feed_content = $wp_remote_content['body'];
		}

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

			self::start_importing();

			$is_initial = ! $this->load_import_data();

			if ( $is_initial ) {
				$this->load_rss_feed();
				$this->check_lock_status();
				$this->check_duplicate_guid();
				$this->maybe_create_series();
				$this->update_podcast_data();
			}

			$start_from = $this->episodes_added;

			for ( $i = $start_from, $count = 0; $i < $this->episodes_count; $i++, $count++ ) {
				if ( $count >= self::ITEMS_PER_REQUEST ) {
					return $this->create_response( 'Partially imported' );
				}
				$item = $this->feed_object->channel->item[ $i ];
				$this->create_episode( $item );
			}

			$this->push_podcast_to_castos();
			self::stop_importing();

			$msg = '<h3>' . __( 'RSS Feed successfully imported.', 'seriously-simple-podcasting' ) . '</h3>';

			if ( ssp_is_connected_to_castos() ) {
				$msg .= '<p>' . sprintf(
					__(
						'To complete the sync of your podcast(s) to your Castos account, navigate to the <a href="%s">Hosting</a> tab',
						'seriously-simple-podcasting'
					),
					ssp_get_tab_url( 'castos-hosting' )
				) . '</p>';
			}

			return $this->create_response( $msg, true );
		} catch ( \Exception $e ) {
			$this->logger->log( __METHOD__ . ' Error: ' . $e->getMessage() );

			// Release the lock so a failed import doesn't keep suppressing series
			// pushes for the rest of its TTL. Import data is left intact so the
			// user can retry from where it stopped.
			self::stop_importing();

			return array(
				'status'  => 'error',
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Creates a podcast when the user selects "Create new podcast".
	 *
	 * Runs after the duplicate-GUID check so a failed import creates nothing.
	 *
	 * @since 3.18.0
	 *
	 * @return void
	 * @throws \Exception When the podcast could not be created.
	 */
	protected function maybe_create_series() {
		if ( self::CREATE_NEW_SERIES !== $this->series ) {
			return;
		}

		$series_id = self::insert_series( $this->get_new_series_name() );

		if ( ! $series_id ) {
			self::reset_import_data();

			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- shown via alert(), not rendered as HTML.
			throw new \Exception(
				__( 'Could not create a podcast for this feed. Please create one manually and run the import again.', 'seriously-simple-podcasting' )
			);
			// phpcs:enable
		}

		$this->series = $series_id;

		// Store the new podcast ID so later import chunks reuse it.
		$ssp_external_rss                  = get_option( 'ssp_external_rss', array() );
		$ssp_external_rss['import_series'] = $series_id;
		update_option( 'ssp_external_rss', $ssp_external_rss );

		// The first imported podcast must be the default so it can sync to Castos.
		if ( ! ssp_get_default_series_id() ) {
			ssp_update_option( 'default_series', $series_id );
		}
	}

	/**
	 * Creates a podcast with an available name and slug.
	 *
	 * @since 3.18.0
	 *
	 * @param string $name Podcast name taken from the feed.
	 *
	 * @return int|null Term ID, or null if every attempt was rejected.
	 */
	public static function insert_series( $name ) {
		$taxonomy = ssp_series_taxonomy();
		$name     = self::find_free_series_name( $name );
		$slug     = wp_unique_term_slug( sanitize_title( $name ), (object) compact( 'taxonomy' ) );

		$res = wp_insert_term( $name, $taxonomy, compact( 'slug' ) );

		if ( is_wp_error( $res ) ) {
			$logger = new Log_Helper();
			$logger->log( __METHOD__ . ' Could not create podcast: ' . $res->get_error_message() );

			return null;
		}

		return empty( $res['term_id'] ) ? null : (int) $res['term_id'];
	}

	/**
	 * Returns an unused podcast name.
	 *
	 * Names are checked directly because hierarchical taxonomies allow duplicate
	 * names when their slugs differ.
	 *
	 * @since 3.18.0
	 *
	 * @param string $name Podcast name taken from the feed.
	 *
	 * @return string
	 */
	public static function find_free_series_name( $name ) {
		$taxonomy = ssp_series_taxonomy();

		for ( $attempt = 1; $attempt <= self::MAX_NAME_ATTEMPTS; $attempt++ ) {
			$candidate = ( 1 === $attempt ) ? $name : sprintf( '%s (%d)', $name, $attempt );

			if ( ! term_exists( $candidate, $taxonomy ) ) {
				return $candidate;
			}
		}

		return sprintf( '%s (%s)', $name, wp_generate_password( 6, false ) );
	}

	/**
	 * Returns the feed title, or a name derived from the feed URL.
	 *
	 * @since 3.18.0
	 *
	 * @return string
	 */
	protected function get_new_series_name() {
		return self::podcast_name_from_feed( $this->feed_object, $this->rss_feed );
	}

	/**
	 * Returns the feed title, or a name derived from the feed URL.
	 *
	 * Shared with the onboarding wizard, which names its target podcast from the
	 * feed before the import handler runs.
	 *
	 * @since 3.18.0
	 *
	 * @param \SimpleXMLElement|null $feed_object Parsed feed.
	 * @param string                 $feed_url    Feed URL the podcast was imported from.
	 *
	 * @return string
	 */
	public static function podcast_name_from_feed( $feed_object, $feed_url ) {
		$title = isset( $feed_object->channel->title )
			? trim( (string) $feed_object->channel->title )
			: '';

		if ( '' !== $title ) {
			return $title;
		}

		return self::podcast_name_from_url( $feed_url );
	}

	/**
	 * Returns a name from the URL path, host, or a generic fallback.
	 *
	 * @since 3.18.0
	 *
	 * @param string $feed_url Feed URL.
	 *
	 * @return string
	 */
	public static function podcast_name_from_url( $feed_url ) {
		$parts    = wp_parse_url( $feed_url );
		$segments = array_filter( explode( '/', isset( $parts['path'] ) ? $parts['path'] : '' ) );

		foreach ( array_reverse( $segments ) as $segment ) {
			if ( in_array( strtolower( $segment ), self::GENERIC_URL_SEGMENTS, true ) ) {
				continue;
			}

			return ucwords( str_replace( array( '-', '_' ), ' ', $segment ) );
		}

		if ( ! empty( $parts['host'] ) ) {
			return $parts['host'];
		}

		return __( 'Imported Podcast', 'seriously-simple-podcasting' );
	}

	/**
	 * Update podcast data.
	 *
	 * @return void
	 */
	protected function update_podcast_data() {

		$series_id = $this->series;

		if ( isset( $this->feed_object->channel->title ) ) {
			ssp_update_option( 'data_title', (string) $this->feed_object->channel->title, $series_id );
		}

		$itunes = $this->feed_object->channel->children( 'itunes', true );

		if ( isset( $itunes->subtitle ) ) {
			ssp_update_option( 'data_subtitle', (string) $itunes->subtitle, $series_id );
		}

		if ( isset( $itunes->author ) ) {
			ssp_update_option( 'data_author', (string) $itunes->author, $series_id );
		}

		if ( isset( $itunes->category ) && is_iterable( $itunes->category ) ) {
			$i = 0;
			foreach ( $itunes->category as $category_item ) {
				++$i;
				// Update category
				if ( isset( $category_item->attributes()->text ) ) {
					ssp_update_option(
						'data_category' . ( ( 1 === $i ) ? '' : $i ),
						(string) $category_item->attributes()->text,
						$series_id
					);
				}

				// Update subcategory
				if ( isset( $category_item->category ) && isset( $category_item->category->attributes()->text ) ) {
					ssp_update_option(
						'data_subcategory' . ( ( 1 === $i ) ? '' : $i ),
						(string) $category_item->category->attributes()->text,
						$series_id
					);
				}
			}
		}

		if ( isset( $this->feed_object->channel->description ) ) {
			ssp_update_option( 'data_description', (string) $this->feed_object->channel->description, $series_id );
		}

		if ( isset( $itunes->image ) && isset( $itunes->image->attributes()->href ) ) {
			$this->save_podcast_image( (string) $itunes->image->attributes()->href, $series_id );
		}

		if ( isset( $itunes->owner->name ) ) {
			ssp_update_option( 'data_owner_name', (string) $itunes->owner->name, $series_id );
		}

		if ( isset( $itunes->owner->email ) ) {
			ssp_update_option( 'data_owner_email', (string) $itunes->owner->email, $series_id );
		}

		if ( isset( $this->feed_object->channel->language ) ) {
			ssp_update_option( 'data_language', (string) $this->feed_object->channel->language, $series_id );
		}

		if ( isset( $this->feed_object->channel->copyright ) ) {
			ssp_update_option( 'data_copyright', (string) $this->feed_object->channel->copyright, $series_id );
		}

		if ( isset( $itunes->type ) ) {
			ssp_update_option( 'consume_order', (string) $itunes->type, $series_id );
		}

		// Get the podcast guid and use it as the podcast guid if it exists.
		$guid = $this->get_podcast_guid();

		if ( $guid ) {
			ssp_update_option( 'data_guid', $guid, $series_id );
		}
	}

	/**
	 * Pushes the imported podcast to Castos once the feed's data and GUID are saved.
	 *
	 * @since 3.18.0
	 *
	 * @return void
	 */
	protected function push_podcast_to_castos() {
		if ( ! $this->series || ! ssp_is_connected_to_castos() ) {
			return;
		}

		if ( ! ssp_get_default_series_id() ) {
			return;
		}

		$series_data              = $this->castos_handler->generate_series_data_for_castos( $this->series );
		$series_data['series_id'] = $this->series;

		$this->castos_handler->update_podcast_data( $series_data );
	}

	/**
	 * Get the podcast guid
	 *
	 * @return string|null
	 */
	protected function get_podcast_guid() {
		$guid_elements = $this->feed_object->channel->xpath( 'podcast:guid' );
		if ( empty( $guid_elements ) ) {
			return null;
		}

		return (string) $guid_elements[0];
	}

	protected function create_response( $msg = '', $is_finished = false ) {
		return array(
			'status'      => 'success',
			'message'     => $msg,
			'count'       => $this->episodes_added,
			'episodes'    => $this->episodes_imported,
			'is_finished' => $is_finished,
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

		self::reset_import_data();

		$msg  = 'Your podcast cannot be imported at this time because the RSS feed is locked by the existing podcast hosting provider. ';
		$msg .= 'Please unlock your RSS feed with your current host before attempting to import again. ';
		$msg .= 'You can find out more about the podcast:lock tag here - https://support.castos.com/article/289-external-rss-feed-import-canceled';

		$msg = __( $msg, 'seriously-simple-podcasting' );

		throw new \Exception( sprintf( $msg, 'https://support.castos.com/article/289-external-rss-feed-import-canceled' ) );
	}

	protected function finish_import() {
		update_option( 'ssp_external_rss', '' );
	}

	/**
	 * Refuses the import when another podcast on this site already owns the feed's GUID.
	 *
	 * @since 3.18.0
	 *
	 * @return void
	 * @throws \Exception Names the podcast that owns the GUID.
	 */
	protected function check_duplicate_guid() {
		$guid = $this->get_podcast_guid();

		if ( ! $guid ) {
			return;
		}

		foreach ( ssp_get_podcasts() as $podcast ) {
			if ( (int) $podcast->term_id === (int) $this->series ) {
				continue;
			}

			if ( ssp_get_podcast_guid( $podcast->term_id ) === $guid ) {
				self::reset_import_data();

				// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- shown via alert(), not rendered as HTML.
				throw new \Exception(
					sprintf(
						// translators: %s is the podcast name.
						__( 'This feed has already been imported into the podcast "%s". Importing it again would connect both podcasts to the same Castos show, so the import was cancelled.', 'seriously-simple-podcasting' ),
						$podcast->name
					)
				);
				// phpcs:enable
			}
		}
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
		$this->save_episode_image( $post_id, $this->get_image_url( $item ) );

		// Save original GUID if it exists
		if ( ! empty( $post_data['original_guid'] ) ) {
			update_post_meta( $post_id, 'ssp_original_guid', $post_data['original_guid'] );
		}

		// Set the series, if it is available
		if ( ! empty( $this->series ) ) {
			wp_set_post_terms( $post_id, $this->series, ssp_series_taxonomy() );
		}

		// Update the added count and imported title array
		++$this->episodes_added;
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

		// Extract original GUID from RSS feed item
		$original_guid = trim( (string) $item->guid );
		if ( ! empty( $original_guid ) ) {
			$post_data['original_guid'] = $original_guid;
		}

		return $post_data;
	}

	/**
	 * @param int    $post_id
	 * @param string $image_url
	 *
	 * @return bool
	 */
	protected function save_episode_image( $post_id, $image_url ) {
		if ( ! $image_url ) {
			return false;
		}

		$image_id = $this->save_image_from_url( $image_url );

		if ( is_wp_error( $image_id ) ) {
			return false;
		}

		update_post_meta( $post_id, 'cover_image_id', $image_id );

		$local_image_url = wp_get_attachment_url( $image_id );
		update_post_meta( $post_id, 'cover_image', $local_image_url );

		do_action( 'ssp_rss_import_save_episode_image', $post_id, $image_id, $local_image_url );

		return true;
	}

	/**
	 * @param string $image_url
	 * @param int    $series_id
	 *
	 * @return bool
	 */
	protected function save_podcast_image( $image_url, $series_id ) {
		if ( ! $image_url ) {
			return false;
		}

		$image_id = $this->save_image_from_url( $image_url );

		if ( is_wp_error( $image_id ) ) {
			return false;
		}

		$url = wp_get_attachment_url( $image_id );
		ssp_update_option( 'data_image', $url, $series_id );

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
			'tmp_name' => $tmp,
		);

		return media_handle_sideload( $file_array );
	}

	/**
	 * @param int    $post_id
	 * @param string $url
	 *
	 * @return void
	 */
	protected function save_enclosure( $post_id, $url ) {
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
