<?php
/**
 * Onboarding import handler class file.
 *
 * @package Seriously Simple Podcasting
 */

namespace SeriouslySimplePodcasting\Handlers;

use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prepares an RSS import started from the onboarding wizard.
 *
 * The wizard confirms a feed before importing it, so this handler fetches and
 * validates the feed up front and resolves which podcast the episodes land in.
 * Once it hands over, the import itself is the ordinary RSS_Import_Handler run.
 *
 * @since 3.18.0
 */
class Onboarding_Import_Handler {

	/**
	 * Stores the podcast the wizard is working on, so the steps after the import
	 * read and write the imported podcast rather than the site default.
	 */
	const TARGET_SERIES_OPTION = 'ss_podcasting_onboarding_series';

	/**
	 * Fetches a feed and describes what would be imported from it.
	 *
	 * @since 3.18.0
	 *
	 * @param string $feed_url Feed URL entered by the user.
	 *
	 * @return array|WP_Error Feed summary, or the reason it cannot be imported.
	 */
	public function preview( $feed_url ) {
		$feed_url = trim( $feed_url );

		if ( ! wp_http_validate_url( $feed_url ) ) {
			return new WP_Error(
				'ssp_invalid_url',
				__( 'That doesn\'t look like a web address. Paste the full link to your podcast\'s RSS feed.', 'seriously-simple-podcasting' )
			);
		}

		$feed = $this->fetch_feed( $feed_url );

		if ( is_wp_error( $feed ) ) {
			return $feed;
		}

		$duplicate = $this->find_podcast_with_guid( $this->get_feed_guid( $feed ) );

		if ( $duplicate ) {
			return new WP_Error(
				'ssp_duplicate_feed',
				sprintf(
					// translators: %s is the podcast name.
					__( 'You\'ve already imported this feed into "%s". Importing it again would tie both podcasts to the same Castos show.', 'seriously-simple-podcasting' ),
					$duplicate->name
				)
			);
		}

		return $this->summarize_feed( $feed, $feed_url );
	}

	/**
	 * Resolves the target podcast and stores the configuration used by later
	 * import requests.
	 *
	 * @since 3.18.0
	 *
	 * @param string $feed_url Feed URL confirmed by the user.
	 *
	 * @return array|WP_Error Feed summary plus the target podcast, or the reason it cannot be imported.
	 */
	public function start( $feed_url ) {
		$summary = $this->preview( $feed_url );

		if ( is_wp_error( $summary ) ) {
			return $summary;
		}

		// Clear any abandoned import before acquiring the lock. Keep the lock while
		// creating the podcast so Castos cannot sync it before its GUID is imported.
		RSS_Import_Handler::reset_import_data();
		RSS_Import_Handler::start_importing();

		$series_id = $this->resolve_target_series( $summary['title'] );

		if ( is_wp_error( $series_id ) ) {
			RSS_Import_Handler::stop_importing();

			return $series_id;
		}

		update_option(
			'ssp_external_rss',
			array(
				'import_rss_feed'     => $summary['feed_url'],
				'import_post_type'    => SSP_CPT_PODCAST,
				'import_series'       => $series_id,
				'import_podcast_data' => true,
			)
		);

		update_option( self::TARGET_SERIES_OPTION, $series_id, false );

		$summary['series_id'] = $series_id;

		return $summary;
	}

	/**
	 * Returns the podcast the wizard is working on — the imported one once an
	 * import has run, the site default otherwise.
	 *
	 * @since 3.18.0
	 *
	 * @return int
	 */
	public function get_target_series_id() {
		$series_id = (int) get_option( self::TARGET_SERIES_OPTION, 0 );

		if ( $series_id && get_term( $series_id, ssp_series_taxonomy() ) instanceof \WP_Term ) {
			return $series_id;
		}

		return ssp_get_default_series_id();
	}

	/**
	 * Forgets the wizard's target podcast.
	 *
	 * @since 3.18.0
	 *
	 * @return void
	 */
	public static function forget_target_series() {
		delete_option( self::TARGET_SERIES_OPTION );
	}

	/**
	 * Picks the podcast an onboarding import writes into.
	 *
	 * An untouched default podcast is adopted and renamed after the feed, so a
	 * first-run site is not left with an empty podcast beside the imported one.
	 * A default that already holds episodes is left exactly as it is.
	 *
	 * @since 3.18.0
	 *
	 * @param string $name Podcast name taken from the feed.
	 *
	 * @return int|WP_Error Target podcast ID.
	 */
	protected function resolve_target_series( $name ) {
		$default_id = ssp_get_default_series_id();

		if ( $default_id && ! $this->series_has_episodes( $default_id ) ) {
			$this->rename_series( $default_id, $name );

			return $default_id;
		}

		$series_id = $this->create_series( $name );

		// Make the imported podcast the default only when the site has none. Never
		// replace an existing default podcast.
		if ( ! is_wp_error( $series_id ) && ! $default_id ) {
			ssp_update_option( 'default_series', $series_id );
		}

		return $series_id;
	}

	/**
	 * Whether a podcast already holds episodes, drafts included.
	 *
	 * @since 3.18.0
	 *
	 * @param int $series_id Podcast term ID.
	 *
	 * @return bool
	 */
	protected function series_has_episodes( $series_id ) {
		$episodes = get_posts(
			array(
				'post_type'        => ssp_post_types( true ),
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => ssp_series_taxonomy(),
						'field'    => 'term_id',
						'terms'    => $series_id,
					),
				),
			)
		);

		return ! empty( $episodes );
	}

	/**
	 * Renames a podcast after the feed it is about to receive.
	 *
	 * @since 3.18.0
	 *
	 * @param int    $series_id Podcast term ID.
	 * @param string $name      Podcast name taken from the feed.
	 *
	 * @return void
	 */
	protected function rename_series( $series_id, $name ) {
		$taxonomy = ssp_series_taxonomy();
		$term     = get_term( $series_id, $taxonomy );

		if ( ! $term instanceof \WP_Term || $term->name === $name ) {
			return;
		}

		$name = RSS_Import_Handler::find_free_series_name( $name );
		$slug = wp_unique_term_slug( sanitize_title( $name ), $term );

		wp_update_term( $series_id, $taxonomy, compact( 'name', 'slug' ) );
	}

	/**
	 * Creates a podcast named after the feed.
	 *
	 * @since 3.18.0
	 *
	 * @param string $name Podcast name taken from the feed.
	 *
	 * @return int|WP_Error Term ID.
	 */
	protected function create_series( $name ) {
		$series_id = RSS_Import_Handler::insert_series( $name );

		if ( ! $series_id ) {
			return new WP_Error(
				'ssp_series_not_created',
				__( 'We couldn\'t set up a podcast for this feed. Please try again.', 'seriously-simple-podcasting' )
			);
		}

		return $series_id;
	}

	/**
	 * Requests the feed and parses it, describing whatever went wrong instead.
	 *
	 * @since 3.18.0
	 *
	 * @param string $feed_url Feed URL.
	 *
	 * @return \SimpleXMLElement|WP_Error
	 */
	protected function fetch_feed( $feed_url ) {
		$response = wp_safe_remote_get( $feed_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'ssp_feed_unreachable',
				__( 'We couldn\'t reach that address. Check the link for a typo — if it looks right, your server\'s firewall may be blocking us.', 'seriously-simple-podcasting' )
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== (int) $code ) {
			return new WP_Error(
				'ssp_feed_unreachable',
				sprintf(
					// translators: %s is an HTTP status code.
					__( 'We couldn\'t read the feed at that address (error %s). Check the link and try again.', 'seriously-simple-podcasting' ),
					$code
				)
			);
		}

		$feed = $this->parse_feed( $body );

		if ( ! $feed instanceof \SimpleXMLElement || ! isset( $feed->channel ) ) {
			return new WP_Error(
				'ssp_not_a_feed',
				__( 'That link doesn\'t point to a podcast feed. It\'s easy to copy the website address by mistake — look for the RSS feed link instead.', 'seriously-simple-podcasting' )
			);
		}

		if ( ! isset( $feed->channel->item ) || ! count( $feed->channel->item ) ) {
			return new WP_Error(
				'ssp_empty_feed',
				__( 'That feed doesn\'t have any episodes yet, so there\'s nothing for us to bring over.', 'seriously-simple-podcasting' )
			);
		}

		return $feed;
	}

	/**
	 * Parses feed XML without letting malformed markup raise warnings.
	 *
	 * @since 3.18.0
	 *
	 * @param string $body Response body.
	 *
	 * @return \SimpleXMLElement|false
	 */
	protected function parse_feed( $body ) {
		if ( ! trim( (string) $body ) ) {
			return false;
		}

		$previous = libxml_use_internal_errors( true );
		$feed     = simplexml_load_string( $body );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		return $feed;
	}

	/**
	 * Describes the feed for the confirmation screen.
	 *
	 * @since 3.18.0
	 *
	 * @param \SimpleXMLElement $feed     Parsed feed.
	 * @param string            $feed_url Feed URL.
	 *
	 * @return array
	 */
	protected function summarize_feed( $feed, $feed_url ) {
		$itunes = $feed->channel->children( 'itunes', true );

		return array(
			'feed_url' => $feed_url,
			'title'    => RSS_Import_Handler::podcast_name_from_feed( $feed, $feed_url ),
			'author'   => isset( $itunes->author ) ? trim( (string) $itunes->author ) : '',
			'host'     => $this->get_feed_host( $feed ),
			'image'    => $this->get_feed_image( $feed, $itunes ),
			'episodes' => count( $feed->channel->item ),
		);
	}

	/**
	 * Returns the service that generated the feed, as the feed itself reports it.
	 *
	 * @since 3.18.0
	 *
	 * @param \SimpleXMLElement $feed Parsed feed.
	 *
	 * @return string
	 */
	protected function get_feed_host( $feed ) {
		return isset( $feed->channel->generator ) ? trim( (string) $feed->channel->generator ) : '';
	}

	/**
	 * Returns the podcast's cover image URL.
	 *
	 * @since 3.18.0
	 *
	 * @param \SimpleXMLElement $feed   Parsed feed.
	 * @param \SimpleXMLElement $itunes iTunes namespaced children of the channel.
	 *
	 * @return string
	 */
	protected function get_feed_image( $feed, $itunes ) {
		if ( isset( $itunes->image ) && isset( $itunes->image->attributes()->href ) ) {
			return esc_url_raw( (string) $itunes->image->attributes()->href );
		}

		if ( isset( $feed->channel->image->url ) ) {
			return esc_url_raw( (string) $feed->channel->image->url );
		}

		return '';
	}

	/**
	 * Returns the feed's podcast GUID.
	 *
	 * @since 3.18.0
	 *
	 * @param \SimpleXMLElement $feed Parsed feed.
	 *
	 * @return string
	 */
	protected function get_feed_guid( $feed ) {
		$podcast_ns = $feed->channel->children( 'podcast', true );

		return isset( $podcast_ns->guid ) ? trim( (string) $podcast_ns->guid ) : '';
	}

	/**
	 * Returns the podcast already carrying this GUID, if any.
	 *
	 * @since 3.18.0
	 *
	 * @param string $guid Podcast GUID from the feed.
	 *
	 * @return \WP_Term|null
	 */
	protected function find_podcast_with_guid( $guid ) {
		if ( ! $guid ) {
			return null;
		}

		// Ignore the wizard's current target: after a reload it may already have
		// this GUID, but it is not a duplicate podcast.
		$own_target = (int) get_option( self::TARGET_SERIES_OPTION, 0 );

		foreach ( ssp_get_podcasts() as $podcast ) {
			if ( $own_target && (int) $podcast->term_id === $own_target ) {
				continue;
			}

			if ( ssp_get_podcast_guid( $podcast->term_id ) === $guid ) {
				return $podcast;
			}
		}

		return null;
	}
}
