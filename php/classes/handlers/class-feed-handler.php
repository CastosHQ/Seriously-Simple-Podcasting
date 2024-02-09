<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Interfaces\Service;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Traits\Useful_Variables;
use WP_Query;

/**
 * SSP Feed Handler
 *
 * @package Seriously Simple Podcasting
 * @author Serhiy Zakharchenko, Jonathan Bossenger
 * @since 2.8.2
 */
class Feed_Handler implements Service {

	use Useful_Variables;

	/**
	 * Unique "podcast" namespace UUID
	 * @see https://github.com/Podcastindex-org/podcast-namespace/blob/main/docs/1.0.md#guid
	 * */
	const PODCAST_NAMESPACE_UUID = 'ead4c236-bf58-58c6-a2c6-a6b28d128cb6';

	/**
	 * @var Settings_Handler
	 * */
	protected $settings_handler;

	/**
	 * @var Renderer
	 * */
	protected $renderer;


	/**
	 * Feed_Handler constructor.
	 */
	public function __construct( $settings_handler, $renderer ) {
		$this->settings_handler = $settings_handler;
		$this->renderer = $renderer;
		$this->init_useful_variables();
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
	public function get_series_slug() {
		global $wp_query;

		$podcast_series = '';

		if ( isset( $wp_query->query_vars['podcast_series'] ) ) {
			$podcast_series = esc_attr( $wp_query->query_vars['podcast_series'] );
		}

		if ( empty( $podcast_series ) && isset( $_GET['podcast_series'] ) ) {
			$podcast_series = esc_attr( $_GET['podcast_series'] );
		}

		return untrailingslashit( $podcast_series );
	}

	/**
	 * Redirect the default feed to the default series feed
	 * @since 3.0.0
	 * */
	public function redirect_default_feed(){
		$default_series_id = ssp_get_option( 'default_series' );
		if ( $default_series_id ) {
			$term = get_term_by( 'id', $default_series_id, ssp_series_taxonomy() );
			if ( $term ) {
				$url = ssp_get_feed_url( $term->slug );
				wp_redirect( $url );
				exit();
			}
		}
	}

	/**
	 * Get series id
	 *
	 * @param string $series_slug
	 *
	 * @return int Series id.
	 */
	public function get_series_id( $series_slug ) {
		$series_id = 0;
		if ( $series_slug ) {
			$series    = get_term_by( 'slug', $series_slug, ssp_series_taxonomy() );
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

	public function render_feed_404() {
		header( 'HTTP/1.0 404 Not Found' );

		$stylesheet_url = $this->get_stylesheet_url();
		$title          = apply_filters( 'ssp_feed_404_title', __( 'Podcast feed does not exist', 'seriously-simple-podcasting' ) );
		$description    = apply_filters( 'ssp_feed_404_description', __( 'Please check the podcast feed URL', 'seriously-simple-podcasting' ) );
		$args           = apply_filters( 'ssp_feed_404_args', compact( 'stylesheet_url', 'title', 'description' ) );
		$path           = apply_filters( 'ssp_feed_404_path', 'feed/feed-no-access' );

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

		if ( $series_id && $series_id != ssp_get_default_series_id() ) {
			return $exclude_series;
		}

		$series = get_terms(
			array(
				'taxonomy'   => 'series',
				'hide_empty' => false,
			)
		);

		$default_series_id = ssp_get_default_series_id();

		foreach ( $series as $feed ) {
			if ( $default_series_id == $feed->term_id ) {
				continue;
			}
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
		$title = $this->settings_handler->get_feed_option( 'data_title', $series_id );

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
		$description = $this->settings_handler->get_feed_option( 'data_description', $series_id, get_bloginfo( 'description' ) );

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
		$language = $this->settings_handler->get_feed_option( 'data_language', $series_id, get_bloginfo( 'language' ) );

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
		$default   = date( 'Y' ) . ' ' . get_bloginfo( 'name' );
		$copyright = $this->settings_handler->get_feed_option( 'data_copyright', $series_id, $default );

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
		$subtitle = $this->settings_handler->get_feed_option( 'data_subtitle', $series_id, get_bloginfo( 'description' ) );

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
		$author = $this->settings_handler->get_feed_option( 'data_author', $series_id, get_bloginfo( 'name' ) );

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
		$owner_name = $this->settings_handler->get_feed_option( 'data_owner_name', $series_id, get_bloginfo( 'name' ) );

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
		$owner_email = $this->settings_handler->get_feed_option( 'data_owner_email', $series_id );

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
		$explicit_option = $this->settings_handler->get_feed_option( 'explicit', $series_id );

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
		$complete_option = $this->settings_handler->get_feed_option( 'complete', $series_id );

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
		$image = $this->settings_handler->get_feed_option( 'data_image', $series_id );

		// Here we'll sanitize the image, if it's not valid - it will be just empty string.
		return apply_filters( 'ssp_feed_image', $image, $series_id );
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
			$turbo = get_option( 'ss_podcasting_turbocharge_feed_' . ssp_get_default_series_id(), null );
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
	 *
	 * @since 2.20.0 Do not carry over the media prefix to subsequent podcasts
	 */
	public function get_media_prefix( $series_id ) {
		return ssp_get_media_prefix( $series_id );
	}

	/**
	 * Checks whether the current feed is in excerpt mode or not
	 *
	 * @param int $series_id
	 *
	 * @return bool
	 */
	public function is_excerpt_mode( $series_id ) {

		$description_mode = ssp_get_option( 'episode_description', 'excerpt', $series_id );

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

		return apply_filters( 'ssp_rss_stylesheet', $this->template_url . 'feed-stylesheet.xsl' );
	}

	/**
	 * Checks whether the current feed is in excerpt mode or not
	 *
	 * @param int $series_id
	 *
	 * @return string Yes|No
	 */
	public function get_locked( $series_id ) {
		$locked = ssp_get_option( 'locked', 'on', $series_id );

		return 'on' === $locked ? 'yes' : 'no';
	}

	/**
	 * Gets funding settings
	 * @see https://github.com/Podcastindex-org/podcast-namespace/blob/main/docs/1.0.md#funding
	 *
	 * @param int $series_id
	 *
	 * @return array|null
	 */
	public function get_funding( $series_id ) {
		if ( $series_id ) {
			$funding = get_option( 'ss_podcasting_funding_' . $series_id, null );
		} else {
			$funding = get_option( 'ss_podcasting_funding', null );
		}

		return $funding;
	}

	/**
	 * Gets podcast value settings ( recipient wallet address )
	 * @see https://github.com/Podcastindex-org/podcast-namespace/blob/main/docs/1.0.md#value
	 *
	 * @param int $series_id
	 *
	 * @return array|null
	 */
	public function get_podcast_value( $series_id ) {
		if ( $series_id ) {
			$value = get_option( 'ss_podcasting_podcast_value_' . $series_id, null );
		} else {
			$value = get_option( 'ss_podcasting_podcast_value', null );
		}

		return $value;
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

		// Try to check the old default podcast option
		if ( empty( $saved_guid ) && ( $term_id == ssp_get_default_series_id() ) ) {
			$saved_guid = get_option( 'ss_podcasting_data_guid' );
		}

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
		return ssp_get_option( 'publish_date', 'published', $series_id );
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
	public function get_feed_link( $podcast_id ) {
		$link = get_term_link( $podcast_id, ssp_series_taxonomy() );

		if ( is_wp_error( $link ) || ! $link ) {
			$link = trailingslashit( home_url() );
		}

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
			// Remove filter convert_chars, because our feed is already escaped with CDATA.
			remove_filter( 'the_excerpt_rss', 'convert_chars' );
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

	/**
	 * Get episode image (cover or featured image).
	 *
	 * @param $post_id
	 *
	 * @return mixed|void
	 */
	public function get_feed_item_image( $post_id ) {
		$episode_image = ssp_frontend_controller()->get_episode_image_url( $post_id );
		return apply_filters( 'ssp_feed_item_image', $episode_image, $post_id );
	}

	/**
	 * Get feed item duration.
	 * Episode duration (default to 0:00 to ensure there is always a value for this)
	 *
	 * @param $post_id
	 *
	 * @return mixed|void
	 */
	public function get_feed_item_duration( $post_id ){
		$duration = get_post_meta( $post_id, 'duration', true );
		if ( ! $duration ) {
			$duration = '0:00';
		}
		return apply_filters( 'ssp_feed_item_duration', $duration, $post_id );
	}

	/**
	 * Get feed item file size in bytes.
	 *
	 * @param $post_id
	 *
	 * @return int
	 */
	public function get_feed_item_file_size( $post_id ){
		$size = get_post_meta( $post_id, 'filesize_raw', true );

		if ( ! $size ) {
			$formatted_size = get_post_meta( $post_id, 'filesize', true );
			if ( ssp_is_connected_to_castos() || $formatted_size ) {
				$size = convert_human_readable_to_bytes( $formatted_size );
			} else {
				$size = 1;
			}
		}
		return apply_filters( 'ssp_feed_item_size', $size, $post_id );
	}

	/**
	 * Get feed item mime type.
	 * Default to MP3/MP4 to ensure there is always a value for this.
	 *
	 * @param $audio_file
	 * @param $post_id
	 *
	 * @return mixed|void
	 */
	public function get_feed_item_mime_type( $audio_file, $post_id ) {

		$ss_podcasting = ssp_frontend_controller();
		$mime_type     = $ss_podcasting->get_attachment_mimetype( $audio_file );
		if ( ! $mime_type ) {

			// Get the episode type (audio or video) to determine the appropriate default MIME type
			$episode_type = $ss_podcasting->get_episode_type( $post_id );
			switch ( $episode_type ) {
				case 'audio':
					$mime_type = 'audio/mpeg';
					break;
				case 'video':
					$mime_type = 'video/mp4';
					break;
			}
		}

		return apply_filters( 'ssp_feed_item_mime_type', $mime_type, $post_id );
	}

	/**
	 * Get feed item itunes summary.
	 * iTunes summary excludes HTML and must be shorter than 4000 characters.
	 *
	 * @param $description
	 * @param $post_id
	 *
	 * @return mixed|void
	 */
	public function get_feed_item_itunes_summary( $description, $post_id ) {
		$itunes_summary = wp_strip_all_tags( $description );
		$itunes_summary = mb_substr( $itunes_summary, 0, 3999 );
		return apply_filters( 'ssp_feed_item_itunes_summary', $itunes_summary, $post_id );
	}

	/**
	 * Get feed item Google Play description.
	 * Google Play description is the same as iTunes summary, but must be shorter than 1000 characters.
	 *
	 * @param $description
	 * @param $post_id
	 *
	 * @return mixed|void
	 */
	public function get_feed_item_google_play_description( $description, $post_id ){
		$gp_description = wp_strip_all_tags( $description );
		$gp_description = mb_substr( $gp_description, 0, 999 );
		return apply_filters( 'ssp_feed_item_gp_description', $gp_description, $post_id );
	}

	/**
	 * Get feed item iTunes subtitle.
	 * iTunes subtitle excludes HTML and must be shorter than 255 characters.
	 *
	 * @param $description
	 * @param $post_id
	 *
	 * @return mixed|void
	 */
	public function get_feed_item_itunes_subtitle( $description, $post_id ){
		$itunes_subtitle = wp_strip_all_tags( $description );
		$itunes_subtitle = str_replace(
			array(
				'>',
				'<',
				'\'',
				'"',
				'`',
				'[andhellip;]',
				'[&hellip;]',
				'[&#8230;]',
			),
			array( '', '', '', '', '', '', '', '' ),
			$itunes_subtitle
		);

		$itunes_subtitle = mb_substr( $itunes_subtitle, 0, 254 );
		return apply_filters( 'ssp_feed_item_itunes_subtitle', $itunes_subtitle, $post_id );
	}

	/**
	 * Get feed item publication date.
	 *
	 * @param $pub_date_type
	 * @param $post_id
	 *
	 * @return mixed|void
	 */
	public function get_feed_item_pub_date( $pub_date_type, $post_id ) {
		$pub_date = ( 'published' === $pub_date_type ) ? get_post_time( 'Y-m-d H:i:s', true ) : get_post_meta( $post_id, 'date_recorded', true );
		$pub_date = esc_html( mysql2date( 'D, d M Y H:i:s +0000', $pub_date, false ) );

		return apply_filters( 'ssp_feed_item_pub_date', $pub_date, $post_id, $pub_date_type );
	}

	/**
	 * @param $post_id
	 *
	 * @return string
	 */
	public function get_feed_item_explicit_flag( $post_id ) {
		$ep_explicit = get_post_meta( $post_id, 'explicit', true );
		return apply_filters( 'ssp_feed_item_explicit', $ep_explicit, $post_id );
	}

	/**
	 * @param string $category
	 * @param string $subcategory
	 *
	 * @return string
	 */
	public function get_castos_category_name( $category, $subcategory ) {
		if ( $category && $subcategory ) {
			$category .= ': ' . $subcategory;
		}

		return $category;
	}
}
