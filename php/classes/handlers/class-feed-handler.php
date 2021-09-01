<?php

namespace SeriouslySimplePodcasting\Handlers;

/**
 * SSP Feed Handler
 *
 * @package Seriously Simple Podcasting
 * @author Sergey Zakharchenko, Jonathan Bossenger
 * @since 2.8.2
 */
class Feed_Handler {

	/**
	 * Unique "podcast" namespace UUID
	 * @see https://github.com/Podcastindex-org/podcast-namespace/blob/main/docs/1.0.md#guid
	 * */
	const PODCAT_NAMESPACE_UUID = 'ead4c236-bf58-58c6-a2c6-a6b28d128cb6';

	/**
	 * Suppress all errors to make sure the feed is not broken
	 *
	 * @return void
	 */
	public function suppress_errors() {
		$force_hide_errors = true; //todo: remove
		$hide_errors       = ! defined( 'WP_DEBUG' ) || ! WP_DEBUG;
		if ( $force_hide_errors || $hide_errors ) {
			error_reporting( 0 );// Hide all errors
		}
	}

	/**
	 * @return bool
	 */
	public function has_access() {
		// Allow feed access by default.
		$give_access = true;

		// Check if feed is password protected.
		$protection = get_option( 'ss_podcasting_protect', '' );

		// Handle feed protection if required.
		if ( $protection && 'on' === $protection ) {

			$give_access = false;

			// Request password and give access if correct.
			if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) && ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
				$give_access = false;
			} else {
				$username = get_option( 'ss_podcasting_protection_username' );
				$password = get_option( 'ss_podcasting_protection_password' );

				if ( $_SERVER['PHP_AUTH_USER'] === $username ) {
					if ( md5( $_SERVER['PHP_AUTH_PW'] ) === $password ) {
						$give_access = true;
					}
				}
			}
		}

		return $give_access;
	}

	/**
	 * Get podcast series
	 *
	 * @return string
	 */
	public function get_podcast_series() {
		global $wp_query;

		$podcast_series = '';

		if ( isset( $wp_query->query_vars['podcast_series'] ) ) {
			$podcast_series = esc_attr( $wp_query->query_vars['podcast_series'] );
		}

		if ( empty( $podcast_series ) && isset( $_GET['podcast_series'] ) ) {
			$podcast_series = esc_attr( $_GET['podcast_series'] );
		}

		return $podcast_series;
	}

	/**
	 * Get series id
	 *
	 * @param string $podcast_series
	 *
	 * @return int Series id.
	 */
	public function get_series_id( $podcast_series ) {
		$series_id = 0;
		if ( $podcast_series ) {
			$series    = get_term_by( 'slug', $podcast_series, 'series' );
			$series_id = $series->term_id;
		}

		return $series_id;
	}

	/**
	 * Close access to protected feed
	 *
	 * @param bool $give_access
	 * @param int $series_id
	 */
	public function protect_unauthorized_access( $give_access, $series_id ) {

		// Allow dynamic access control.
		$give_access = apply_filters( 'ssp_feed_access', $give_access, $series_id );

		// Send 401 status and display no access message if access has been denied.
		if ( ! $give_access ) {

			// Set default message.
			$message = __( 'You are not permitted to view this podcast feed.', 'seriously-simple-podcasting' );

			// Check message option from plugin settings.
			$message_option = get_option( 'ss_podcasting_protection_no_access_message' );
			if ( $message_option ) {
				$message = $message_option;
			}

			// Allow message to be filtered dynamically.
			$message = apply_filters( 'ssp_feed_no_access_message', $message );

			$no_access_message = '<div style="text-align:center;font-family:sans-serif;border:1px solid red;background:pink;padding:20px 0;color:red;">' . $message . '</div>';

			header( 'WWW-Authenticate: Basic realm="Podcast Feed"' );
			header( 'HTTP/1.0 401 Unauthorized' );

			die( $no_access_message );
		}
	}

	/**
	 * If redirect is on, get new feed URL and redirect if setting was changed more than 48 hours ago.
	 */
	public function maybe_redirect_to_the_new_feed() {
		$redirect = get_option( 'ss_podcasting_redirect_feed' );
		if ( ! $redirect || 'on' !== $redirect ) {
			return;
		}

		$new_feed_url = get_option( 'ss_podcasting_new_feed_url' );
		$update_date  = get_option( 'ss_podcasting_redirect_feed_date' );

		if ( ! $new_feed_url || ! $update_date ) {
			return;
		}

		$redirect_date = strtotime( '+2 days', $update_date );
		$current_date  = time();

		// Redirect with 301 if it is more than 2 days since redirect was saved
		if ( $current_date > $redirect_date ) {
			header( 'HTTP/1.1 301 Moved Permanently' );
			header( 'Location: ' . $new_feed_url );
			exit;
		}
	}

	/**
	 * Get excluded series
	 *
	 * @param $series_id
	 *
	 * @return array Array of excluded series slugs.
	 */
	public function get_excluded_series( $series_id ) {
		$exclude_series = array();
		if ( $series_id ) {
			return $exclude_series;
		}

		$series = get_terms(
			array(
				'taxonomy'   => 'series',
				'hide_empty' => false,
			)
		);

		foreach ( $series as $feed ) {
			$exclude_feed_option = get_option( 'ss_podcasting_exclude_feed_' . $feed->term_id, 'off' );
			if ( 'on' === $exclude_feed_option ) {
				$exclude_series[] = $feed->slug;
			}
		}

		return $exclude_series;
	}

	/**
	 * Maybe redirect series to the new feed url
	 *
	 * @param $series_id
	 */
	public function maybe_redirect_series( $series_id ) {
		if ( ! $series_id ) {
			return;
		}

		$redirect = get_option( 'ss_podcasting_redirect_feed_' . $series_id );

		if ( $redirect && 'on' === $redirect ) {
			$new_feed_url = get_option( 'ss_podcasting_new_feed_url_' . $series_id );
			if ( $new_feed_url ) {
				header( 'HTTP/1.1 301 Moved Permanently' );
				header( 'Location: ' . $new_feed_url );
				exit;
			}
		}
	}

	/**
	 * Gets podcast title
	 *
	 * @param $series_id
	 *
	 * @return string
	 */
	public function get_podcast_title( $series_id ) {
		if ( $series_id ) {
			$title = get_option( 'ss_podcasting_data_title_' . $series_id, '' );
		}

		if ( empty( $title ) ) {
			$title = get_option( 'ss_podcasting_data_title', get_bloginfo( 'name' ) );
		}

		return apply_filters( 'ssp_feed_title', $title, $series_id );
	}

	/**
	 * Gets podcast description
	 *
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_podcast_description( $series_id ) {
		if ( $series_id ) {
			$description = get_option( 'ss_podcasting_data_description_' . $series_id, '' );
		}

		if ( empty( $description ) ) {
			$description = get_option( 'ss_podcasting_data_description', get_bloginfo( 'description' ) );
		}

		$podcast_description = mb_substr( strip_tags( $description ), 0, 3999 );

		return apply_filters( 'ssp_feed_description', $podcast_description, $series_id );
	}

	/**
	 * Gets podcast language
	 *
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_podcast_language( $series_id ) {
		if ( $series_id ) {
			$language = get_option( 'ss_podcasting_data_language_' . $series_id, '' );
		}

		if ( empty( $language ) ) {
			$language = get_option( 'ss_podcasting_data_language', get_bloginfo( 'language' ) );
		}

		return apply_filters( 'ssp_feed_language', $language, $series_id );
	}


	/**
	 * Gets podcast copyright
	 *
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_podcast_copyright( $series_id ) {
		if ( $series_id ) {
			$copyright = get_option( 'ss_podcasting_data_copyright_' . $series_id, '' );
		}

		if ( empty( $copyright ) ) {
			$copyright = get_option( 'ss_podcasting_data_copyright', '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) );
		}

		return apply_filters( 'ssp_feed_copyright', $copyright, $series_id );
	}

	/**
	 * Get podcast subtitle
	 *
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_podcast_subtitle( $series_id ) {
		if ( $series_id ) {
			$subtitle = get_option( 'ss_podcasting_data_subtitle_' . $series_id, '' );
		}

		if ( empty( $subtitle ) ) {
			$subtitle = get_option( 'ss_podcasting_data_subtitle', get_bloginfo( 'description' ) );
		}

		return apply_filters( 'ssp_feed_subtitle', $subtitle, $series_id );
	}

	/**
	 * Gets podcast author
	 *
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_podcast_author( $series_id ) {
		if ( $series_id ) {
			$author = get_option( 'ss_podcasting_data_author_' . $series_id, '' );
		}

		if ( empty( $author ) ) {
			$author = get_option( 'ss_podcasting_data_author', get_bloginfo( 'name' ) );
		}

		return apply_filters( 'ssp_feed_author', $author, $series_id );
	}

	/**
	 * Gets podcast owner name
	 *
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_podcast_owner_name( $series_id ) {
		if ( $series_id ) {
			$owner_name = get_option( 'ss_podcasting_data_owner_name_' . $series_id, '' );
		}

		if ( empty( $owner_name ) ) {
			$owner_name = get_option( 'ss_podcasting_data_owner_name', get_bloginfo( 'name' ) );
		}

		return apply_filters( 'ssp_feed_owner_name', $owner_name, $series_id );
	}


	/**
	 * Gets podcast owner email
	 *
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_podcast_owner_email( $series_id ) {
		if ( $series_id ) {
			$owner_email = get_option( 'ss_podcasting_data_owner_email_' . $series_id, '' );
		}

		if ( empty( $owner_email ) ) {
			$owner_email = get_option( 'ss_podcasting_data_owner_email', get_bloginfo( 'admin_email' ) );
		}

		return apply_filters( 'ssp_feed_owner_email', $owner_email, $series_id );
	}

	/**
	 * Gets explicit option
	 *
	 * @param int $series_id
	 *
	 * @return bool
	 */
	public function is_explicit( $series_id ) {
		if ( $series_id ) {
			$explicit_option = get_option( 'ss_podcasting_explicit_' . $series_id, null );
		}

		if ( ! isset( $explicit_option ) ) {
			$explicit_option = get_option( 'ss_podcasting_explicit', '' );
		}

		$explicit_option = apply_filters( 'ssp_feed_explicit', $explicit_option, $series_id );

		return $explicit_option === 'on';
	}

	/**
	 * Checks complete setting
	 *
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_complete( $series_id ) {
		if ( $series_id ) {
			$complete_option = get_option( 'ss_podcasting_complete_' . $series_id, null );
		}

		if ( ! isset( $complete_option ) ) {
			$complete_option = get_option( 'ss_podcasting_complete', '' );
		}

		$complete_option = apply_filters( 'ssp_feed_complete', $complete_option, $series_id );

		return 'on' === $complete_option ? 'yes' : '';
	}


	/**
	 * Gets feed image
	 *
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_feed_image( $series_id ) {
		// If it's series feed, try first to show its own image.
		if ( $series_id ) {
			$series_image = get_option( 'ss_podcasting_data_image_' . $series_id, 'no-image' );
			if ( $series_image && 'no-image' !== $series_image ) {
				$image = $series_image;
			}
		}

		// If couldn't show the series image, or if it's default feed, lets show the default cover image.
		if ( empty( $image ) || ! ssp_is_feed_image_valid( $image ) ) {
			$image = get_option( 'ss_podcasting_data_image', '' );
		}

		// Here we'll sanitize the image, if it's not valid - it will be just empty string.
		$image = apply_filters( 'ssp_feed_image', $image, $series_id );

		return $image;
	}

	/**
	 * Gets turbo setting
	 *
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_turbo( $series_id ) {
		if ( $series_id ) {
			$turbo = get_option( 'ss_podcasting_turbocharge_feed_' . $series_id, null );
		}

		if ( ! isset( $turbo ) ) {
			$turbo = get_option( 'ss_podcasting_turbocharge_feed', 'off' );
		}

		return $turbo;
	}

	/**
	 * Gets media prefix
	 *
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_media_prefix( $series_id ) {
		if ( $series_id ) {
			$media_prefix = get_option( 'ss_podcasting_media_prefix_' . $series_id );
		}

		if ( empty( $media_prefix ) ) {
			$media_prefix = get_option( 'ss_podcasting_media_prefix', '' );
		}

		return $media_prefix;
	}

	/**
	 * Checks whether the current feed is in excerpt mode or not
	 *
	 * @param int $series_id
	 *
	 * @return bool
	 */
	public function is_excerpt_mode( $series_id ) {
		if ( $series_id ) {
			$description_mode = get_option( 'ss_podcasting_episode_description_' . $series_id );
		} else {
			$description_mode = get_option( 'ss_podcasting_episode_description', 'excerpt' );
		}

		return 'excerpt' === $description_mode;
	}

	/**
	 * Gets stylesheet url
	 *
	 * @return string
	 */
	public function get_stylesheet_url() {
		if ( ! apply_filters( 'ssp_enable_rss_stylesheet', true ) ) {
			return '';
		}

		global $ss_podcasting;

		return apply_filters( 'ssp_rss_stylesheet', $ss_podcasting->template_url . 'feed-stylesheet.xsl' );
	}

	/**
	 * Checks whether the current feed is in excerpt mode or not
	 *
	 * @param int $series_id
	 *
	 * @return string Yes|No
	 */
	public function get_locked( $series_id ) {
		return null; // Todo: enable when we're ready.
		if ( $series_id ) {
			$locked = get_option( 'ss_podcasting_locked_' . $series_id, null );
		}

		if ( ! isset( $locked ) ) {
			$locked = get_option( 'ss_podcasting_locked', '' );
		}

		return 'on' === $locked ? 'yes' : 'no';
	}

	/**
	 * Gets funding settings
	 *
	 * @param int $series_id
	 *
	 * @return array|null
	 */
	public function get_funding( $series_id ) {
		return null; // Todo: enable when we're ready.
		if ( $series_id ) {
			$funding = get_option( 'ss_podcasting_funding_' . $series_id, null );
		}

		if ( ! isset( $funding ) ) {
			$funding = get_option( 'ss_podcasting_funding', '' );
		}

		return $funding;
	}

	/**
	 * Gets funding settings
	 *
	 * @param string $series_slug
	 *
	 * @return string|null
	 */
	public function get_guid( $series_slug ) {
		return null; // Todo: enable when we're ready.
		$series_url = ssp_get_feed_url( $series_slug );

		$series_url = 'http://castos.loc/feed/podcast/first-series/';

		$url_data = parse_url( $series_url );

		$url = $url_data['host'] . rtrim( $url_data['path'], '/' );

		return UUID_Handler::v5( self::PODCAT_NAMESPACE_UUID, $url );
	}

}
