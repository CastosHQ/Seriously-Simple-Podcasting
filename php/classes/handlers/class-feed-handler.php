<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Renderers\Renderer;
use WP_Query;

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
	const PODCAST_NAMESPACE_UUID = 'ead4c236-bf58-58c6-a2c6-a6b28d128cb6';

	/**
	 * @var Renderer
	 * */
	protected $renderer;


	/**
	 * Feed_Handler constructor.
	 */
	public function __construct() {
		$this->renderer = new Renderer();
	}

	/**
	 * Suppress all errors to make sure the feed is not broken
	 *
	 * @return void
	 */
	public function suppress_errors() {
		$suppress_errors = apply_filters( 'ssp_suppress_feed_errors', true );

		if ( $suppress_errors ) {
			error_reporting( 0 );
		}
	}

	/**
	 * @return bool
	 */
	public function has_password_protected_access() {
		// Allow feed access by default.
		$give_access = true;

		// Check if feed is password protected.
		$protection = get_option( 'ss_podcasting_protect', '' );

		// Handle feed protection if required.
		if ( 'on' === $protection ) {

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
	 * Close access to password protected feed ( Podcast->Settings->Security ).
	 *
	 * @param int $series_id
	 */
	public function maybe_protect_unauthorized_access( $series_id ) {

		// Allow dynamic access control.
		$has_access = apply_filters( 'ssp_feed_access', $this->has_password_protected_access(), $series_id );

		if ( $has_access ) {
			return;
		}

		// Set default message.
		$default_message = __( 'You are not permitted to view this podcast feed.', 'seriously-simple-podcasting' );

		// Check message option from plugin settings.
		$message = get_option( 'ss_podcasting_protection_no_access_message', $default_message );

		// Allow message to be filtered dynamically.
		$message = apply_filters( 'ssp_feed_no_access_message', $message );

		header( 'WWW-Authenticate: Basic realm="Podcast Feed"' );

		$this->render_feed_no_access( $series_id, $message );
	}


	/**
	 * Close access to private feed ( Podcast->Settings->Feed details->Set Podcast To Private ).
	 *
	 * @param int $series_id
	 */
	public function maybe_protect_private_feed( $series_id ) {
		if ( 'yes' !== ssp_get_option( 'is_podcast_private', '', $series_id ) ) {
			return;
		}

		$message = __( 'This content is Private. To access this podcast, contact the site owner.', 'seriously-simple-podcasting' );

		$message = apply_filters( 'ssp_private_feed_message', $message );

		$this->render_feed_no_access( $series_id, $message );
	}


	/**
	 * @param int $series_id
	 * @param string $description
	 */
	public function render_feed_no_access( $series_id, $description ) {
		header( 'HTTP/1.0 401 Unauthorized' );

		$stylesheet_url = $this->get_stylesheet_url();
		$title          = esc_html( $this->get_podcast_title( $series_id ) );
		$args           = apply_filters( 'ssp_feed_no_access_args', compact( 'stylesheet_url', 'title', 'description' ) );
		$path           = apply_filters( 'ssp_feed_no_access_path', 'feed/feed-no-access' );

		$this->renderer->render( $path, $args );
		exit;
	}

	/**
	 * If redirect is on, redirect user to the new url.
	 */
	public function maybe_redirect_to_the_new_feed( $series_id ) {
		$redirect = ssp_get_option( 'redirect_feed', '', $series_id );
		if ( 'on' !== $redirect ) {
			return;
		}

		$new_feed_url = ssp_get_option( 'new_feed_url', '', $series_id );

		if ( ! $new_feed_url ) {
			return;
		}

		wp_redirect( $new_feed_url, 301 );
		exit;
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
		if ( $series_id ) {
			$locked = get_option( 'ss_podcasting_locked_' . $series_id, 'on' );
		}

		if ( ! isset( $locked ) ) {
			$locked = get_option( 'ss_podcasting_locked', 'on' );
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
		if ( $series_id ) {
			$funding = get_option( 'ss_podcasting_funding_' . $series_id, null );
		}

		if ( ! isset( $funding ) ) {
			$funding = get_option( 'ss_podcasting_funding', null );
		}

		return $funding;
	}

	/**
	 * Gets funding settings
	 *
	 * @param string $series_slug
	 *
	 * @return string
	 */
	public function get_guid( $series_slug ) {

		$feed_url = ssp_get_feed_url( $series_slug );

		$term    = get_term_by( 'slug', $series_slug, 'series' );
		$term_id = isset( $term->term_id ) ? $term->term_id : null;

		$option     = $term_id ? 'ss_podcasting_data_guid_' . $term_id : 'ss_podcasting_data_guid';
		$saved_guid = get_option( $option );

		if ( empty( $saved_guid ) ) {
			$url_data = parse_url( $feed_url );
			$url      = $url_data['host'] . rtrim( $url_data['path'], '/' );
			$guid     = UUID_Handler::v5( self::PODCAST_NAMESPACE_UUID, $url );
			update_option( $option, $guid );
		} else {
			$guid = $saved_guid;
		}

		return $guid;
	}

	/**
	 * Gets the variant of publication date type
	 *
	 * @param int $series_id
	 *
	 * @return string Either 'published' or 'recorded'
	 */
	public function get_pub_date_type( $series_id ) {
		$pub_date_type_option = $series_id ? 'ss_podcasting_publish_date_' . $series_id : 'ss_podcasting_publish_date';

		return get_option( $pub_date_type_option, 'published' );
	}

	/**
	 * Gets the feed query
	 *
	 * @param string $podcast_series
	 * @param array $exclude_series
	 * @param string $pub_date_type
	 *
	 * @return WP_Query
	 */
	public function get_feed_query( $podcast_series, $exclude_series, $pub_date_type ) {
		$num_posts = intval( apply_filters( 'ssp_feed_number_of_posts', get_option( 'posts_per_rss', 10 ) ) );

		$args = ssp_episodes( $num_posts, $podcast_series, true, 'feed', $exclude_series );

		if ( 'recorded' === $pub_date_type ) {
			$args['orderby']  = 'meta_value';
			$args['meta_key'] = 'date_recorded';
		}

		return new WP_Query( $args );
	}


	/**
	 * Gets the feed link
	 *
	 * @param string $podcast_series
	 *
	 * @return string
	 */
	public function get_feed_link( $podcast_series ) {
		$link = $podcast_series ? get_term_link( $podcast_series, 'series' ) : trailingslashit( home_url() );

		return apply_filters( 'ssp_feed_channel_link_tag', $link );
	}


	/**
	 * Gets feed item description
	 *
	 * @param int $post_id
	 * @param bool $is_excerpt_mode
	 * @param int $turbo_post_count
	 *
	 * @return string
	 */
	public function get_feed_item_description( $post_id, $is_excerpt_mode, $turbo_post_count = 0 ) {
		if ( $is_excerpt_mode ) {
			$output  = get_the_excerpt( $post_id );
			$content = apply_filters( 'the_excerpt_rss', $output );
		} else {
			$content = ssp_get_the_feed_item_content( $post_id );
			if ( $turbo_post_count > 10 ) {
				// If turbo is on, limit the full html description to 4000 chars.
				$content = mb_substr( $content, 0, 3999 );
			}
		}

		return apply_filters( 'ssp_feed_item_description', $content, $post_id );
	}

}
