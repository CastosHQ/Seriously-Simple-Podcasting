<?php

namespace SeriouslySimplePodcasting\Controllers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feed Controller Class
 *
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.20.7
 */
class Feed_Controller extends Controller {

	public $feed_file_name = 'feed-podcast.php';

	/**
	 * Admin_Controller constructor.
	 *
	 * @param string $file main plugin file
	 * @param string $version plugin version
	 */
	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );

		$this->bootstrap();
	}

	/**
	 * Set up all hooks and filters for this class
	 */
	public function bootstrap() {
		// Register podcast feed.
		add_action( 'init', array( $this, 'add_feed' ), 11 );

		// Handle v1.x feed URL as well as feed URLs for default permalinks.
		add_action( 'init', array( $this, 'redirect_old_feed' ), 12 );

		// Sanitize the podcast image
		add_filter( 'ssp_feed_image', array( $this, 'sanitize_image' ) );
	}

	/**
	 * Register podcast feed
	 * @return void
	 */
	public function add_feed() {
		$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
		add_feed( $feed_slug, array( $this, 'render_podcast_feed' ) );
	}

	/**
	 * Redirect feed URLs created prior to v1.8 to ensure backwards compatibility
	 * @return void
	 */
	public function redirect_old_feed() {
		if ( isset( $_GET['feed'] ) && in_array( $_GET['feed'], array( $this->token, 'itunes' ) ) ) {
			$this->render_podcast_feed();
			exit;
		}
	}

	/**
	 * Render the podcast feed
	 * @return void
	 */
	public function render_podcast_feed() {
		global $wp_query;

		// Prevent 404 on feed
		$wp_query->is_404 = false;

		/**
		 * Fix the is_feed attribute on the old feed url structure
		 */
		if ( ! $wp_query->is_feed ) {
			$wp_query->is_feed = true;
		}

		$this->load_feed_template();

		exit;

	}

	/**
	 * Loads the feed template file
	 * @todo: Further refactoring - split to different functions, use renderer
	 */
	public function load_feed_template() {
		status_header( 200 );

		$user_template_file = apply_filters( 'ssp_feed_template_file', trailingslashit( get_stylesheet_directory() ) . $this->feed_file_name );

		// Any functions hooked in here must NOT output any data or else feed will break
		do_action( 'ssp_before_feed' );

		// @todo move all of this logic into the Feed_Controller render_podcast_feed method, at the very least
		global $ss_podcasting, $wp_query;

		// @todo turn this off and fix any errors
		// Hide all errors
		error_reporting( 0 );

		// Allow feed access by default
		$give_access = true;

		// Check if feed is password protected
		$protection = get_option( 'ss_podcasting_protect', '' );

		// Handle feed protection if required
		if ( $protection && 'on' === $protection ) {

			$give_access = false;

			// Request password and give access if correct
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

		// Get specified podcast series
		$podcast_series = '';
		if ( isset( $_GET['podcast_series'] ) && $_GET['podcast_series'] ) {
			$podcast_series = esc_attr( $_GET['podcast_series'] );
		} elseif ( isset( $wp_query->query_vars['podcast_series'] ) && $wp_query->query_vars['podcast_series'] ) {
			$podcast_series = esc_attr( $wp_query->query_vars['podcast_series'] );
		}

		// Get series ID
		$series_id = 0;
		if ( $podcast_series ) {
			$series    = get_term_by( 'slug', $podcast_series, 'series' );
			$series_id = $series->term_id;
		}

		// Allow dynamic access control
		$give_access = apply_filters( 'ssp_feed_access', $give_access, $series_id );

		// Send 401 status and display no access message if access has been denied
		if ( ! $give_access ) {

			// Set default message
			$message = __( 'You are not permitted to view this podcast feed.', 'seriously-simple-podcasting' );

			// Check message option from plugin settings
			$message_option = get_option( 'ss_podcasting_protection_no_access_message' );
			if ( $message_option ) {
				$message = $message_option;
			}

			// Allow message to be filtered dynamically
			$message = apply_filters( 'ssp_feed_no_access_message', $message );

			$no_access_message = '<div style="text-align:center;font-family:sans-serif;border:1px solid red;background:pink;padding:20px 0;color:red;">' . $message . '</div>';

			header( 'WWW-Authenticate: Basic realm="Podcast Feed"' );
			header( 'HTTP/1.0 401 Unauthorized' );

			die( $no_access_message );
		}

		// If redirect is on, get new feed URL and redirect if setting was changed more than 48 hours ago
		$redirect     = get_option( 'ss_podcasting_redirect_feed' );
		$new_feed_url = false;
		if ( $redirect && 'on' === $redirect ) {

			$new_feed_url = get_option( 'ss_podcasting_new_feed_url' );
			$update_date  = get_option( 'ss_podcasting_redirect_feed_date' );

			if ( $new_feed_url && $update_date ) {
				$redirect_date = strtotime( '+2 days', $update_date );
				$current_date  = time();

				// Redirect with 301 if it is more than 2 days since redirect was saved
				if ( $current_date > $redirect_date ) {
					header( 'HTTP/1.1 301 Moved Permanently' );
					header( 'Location: ' . $new_feed_url );
					exit;
				}
			}
		}

		// if this is the default feed, check for series for which posts should be excluded
		$exclude_series = array();
		if ( empty( $series_id ) ) {
			$series = get_terms(
				array(
					'taxonomy'   => 'series',
					'hide_empty' => false,
				)
			);
			foreach ( $series as $feed ) {
				$option_name         = 'ss_podcasting_exclude_feed_' . $feed->term_id;
				$exclude_feed_option = get_option( $option_name, 'off' );
				if ( 'on' === $exclude_feed_option ) {
					$exclude_series[] = $feed->slug;
				}
			}
		}

		// If this is a series specific feed, then check if we need to redirect
		if ( $series_id ) {
			$redirect     = get_option( 'ss_podcasting_redirect_feed_' . $series_id );
			$new_feed_url = false;
			if ( $redirect && 'on' === $redirect ) {
				$new_feed_url = get_option( 'ss_podcasting_new_feed_url_' . $series_id );
				if ( $new_feed_url ) {
					header( 'HTTP/1.1 301 Moved Permanently' );
					header( 'Location: ' . $new_feed_url );
					exit;
				}
			}
		}

		// Podcast title
		$title = get_option( 'ss_podcasting_data_title', get_bloginfo( 'name' ) );
		if ( $podcast_series ) {
			$series_title = get_option( 'ss_podcasting_data_title_' . $series_id, '' );
			if ( $series_title ) {
				$title = $series_title;
			}
		}
		$title = apply_filters( 'ssp_feed_title', $title, $series_id );

		// Podcast description
		$description = get_option( 'ss_podcasting_data_description', get_bloginfo( 'description' ) );
		if ( $podcast_series ) {
			$series_description = get_option( 'ss_podcasting_data_description_' . $series_id, '' );
			if ( $series_description ) {
				$description = $series_description;
			}
		}
		$podcast_description = mb_substr( strip_tags( $description ), 0, 3999 );
		$podcast_description = apply_filters( 'ssp_feed_description', $podcast_description, $series_id );

		// Podcast language
		$language = get_option( 'ss_podcasting_data_language', get_bloginfo( 'language' ) );
		if ( $podcast_series ) {
			$series_language = get_option( 'ss_podcasting_data_language_' . $series_id, '' );
			if ( $series_language ) {
				$language = $series_language;
			}
		}
		$language = apply_filters( 'ssp_feed_language', $language, $series_id );

		// Podcast copyright string
		$copyright = get_option( 'ss_podcasting_data_copyright', '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) );
		if ( $podcast_series ) {
			$series_copyright = get_option( 'ss_podcasting_data_copyright_' . $series_id, '' );
			if ( $series_copyright ) {
				$copyright = $series_copyright;
			}
		}
		$copyright = apply_filters( 'ssp_feed_copyright', $copyright, $series_id );

		// Podcast subtitle
		$subtitle = get_option( 'ss_podcasting_data_subtitle', get_bloginfo( 'description' ) );
		if ( $podcast_series ) {
			$series_subtitle = get_option( 'ss_podcasting_data_subtitle_' . $series_id, '' );
			if ( $series_subtitle ) {
				$subtitle = $series_subtitle;
			}
		}
		$subtitle = apply_filters( 'ssp_feed_subtitle', $subtitle, $series_id );

		// Podcast author
		$author = get_option( 'ss_podcasting_data_author', get_bloginfo( 'name' ) );
		if ( $podcast_series ) {
			$series_author = get_option( 'ss_podcasting_data_author_' . $series_id, '' );
			if ( $series_author ) {
				$author = $series_author;
			}
		}
		$author = apply_filters( 'ssp_feed_author', $author, $series_id );

		// Podcast owner name
		$owner_name = get_option( 'ss_podcasting_data_owner_name', get_bloginfo( 'name' ) );
		if ( $podcast_series ) {
			$series_owner_name = get_option( 'ss_podcasting_data_owner_name_' . $series_id, '' );
			if ( $series_owner_name ) {
				$owner_name = $series_owner_name;
			}
		}
		$owner_name = apply_filters( 'ssp_feed_owner_name', $owner_name, $series_id );

		// Podcast owner email address
		$owner_email = get_option( 'ss_podcasting_data_owner_email', get_bloginfo( 'admin_email' ) );
		if ( $podcast_series ) {
			$series_owner_email = get_option( 'ss_podcasting_data_owner_email_' . $series_id, '' );
			if ( $series_owner_email ) {
				$owner_email = $series_owner_email;
			}
		}
		$owner_email = apply_filters( 'ssp_feed_owner_email', $owner_email, $series_id );

		// Podcast explicit setting
		$explicit_option = get_option( 'ss_podcasting_explicit', '' );
		if ( $podcast_series ) {
			$series_explicit_option = get_option( 'ss_podcasting_explicit_' . $series_id, '' );
			$explicit_option        = $series_explicit_option;
		}
		$explicit_option = apply_filters( 'ssp_feed_explicit', $explicit_option, $series_id );
		if ( $explicit_option && 'on' === $explicit_option ) {
			$itunes_explicit     = 'yes';
			$googleplay_explicit = 'Yes';
		} else {
			$itunes_explicit     = 'clean';
			$googleplay_explicit = 'No';
		}

		// Podcast complete setting
		$complete_option = get_option( 'ss_podcasting_complete', '' );
		if ( $podcast_series ) {
			$series_complete_option = get_option( 'ss_podcasting_complete_' . $series_id, '' );
			$complete_option        = $series_complete_option;
		}
		$complete_option = apply_filters( 'ssp_feed_complete', $complete_option, $series_id );
		if ( $complete_option && 'on' === $complete_option ) {
			$complete = 'yes';
		} else {
			$complete = '';
		}

		// If it's series feed, try first to show its own image
		if ( $podcast_series ) {
			$series_image = get_option( 'ss_podcasting_data_image_' . $series_id, 'no-image' );
			if ( 'no-image' !== $series_image ) {
				$image = $series_image;
			}
		}

		// If couldn't show the series image, or if it's default feed, lets show the default cover image
		if ( empty( $image ) || ! ssp_is_feed_image_valid( $image ) ) {
			$image = get_option( 'ss_podcasting_data_image', '' );
		}

		// Here we'll sanitize the image, if it's not valid - it will be just empty string
		$image = apply_filters( 'ssp_feed_image', $image, $series_id );

		// Podcast category and subcategory (all levels) - can be filtered with `ssp_feed_category_output`
		$category1 = ssp_get_feed_category_output( 1, $series_id );
		$category2 = ssp_get_feed_category_output( 2, $series_id );
		$category3 = ssp_get_feed_category_output( 3, $series_id );

		// Get iTunes Type
		$itunes_type = get_option( 'ss_podcasting_consume_order' . ( $series_id > 0 ? '_' . $series_id : null ) );

		// Get turbo setting
		$turbo = get_option( 'ss_podcasting_turbocharge_feed', 'off' );
		if ( $series_id && $series_id > 0 ) {
			$series_turbo = get_option( 'ss_podcasting_turbocharge_feed_' . $series_id );
			if ( false !== $series_turbo ) {
				$turbo = $series_turbo;
			}
		}

		// Get media prefix setting
		$media_prefix = get_option( 'ss_podcasting_media_prefix', '' );
		if ( $series_id && $series_id > 0 ) {
			$series_media_prefix = get_option( 'ss_podcasting_media_prefix_' . $series_id );
			if ( false !== $series_media_prefix ) {
				$media_prefix = $series_media_prefix;
			}
		}

		// Get episode description setting
		$episode_description_mode = get_option( 'ss_podcasting_episode_description', 'excerpt' );
		if ( $series_id && $series_id > 0 ) {
			$episode_description_mode = get_option( 'ss_podcasting_episode_description_' . $series_id, 'excerpt' );
			$series_episode_description = get_option( 'episode_description_' . $series_id );
			if ( false !== $series_episode_description ) {
				$episode_description = $series_episode_description;
			}
		}
		$episode_description_uses_excerpt = 'excerpt' === $episode_description_mode;

		// Get stylehseet URL (filterable to allow disabling or custom RSS stylesheets)
		$apply_stylesheet_url = apply_filters( 'ssp_enable_rss_stylesheet', true );
		if ( $apply_stylesheet_url ) {
			$stylesheet_url = apply_filters( 'ssp_rss_stylesheet', $ss_podcasting->template_url . 'feed-stylesheet.xsl' );
		}

		// Set RSS content type and charset headers
		header( 'Content-Type: ' . feed_content_type( SSP_CPT_PODCAST ) . '; charset=' . get_option( 'blog_charset' ), true );


		// Load user feed template if it exists, otherwise use plugin template
		if ( file_exists( $user_template_file ) ) {
			require $user_template_file;
		} else {
			require $this->template_path . $this->feed_file_name;
		}

		// Any functions hooked in here must NOT output any data or else feed will break
		do_action( 'ssp_after_feed' );
	}

	/**
	 * Sanitizes the image, if it's not valid - change it to empty string
	 *
	 * @param string $image_url
	 *
	 * @return string
	 */
	public function sanitize_image( $image_url ) {
		if ( ! ssp_is_feed_image_valid( $image_url ) ) {
			$image_url = '';
		}

		return $image_url;
	}
}
