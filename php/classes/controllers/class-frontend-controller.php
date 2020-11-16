<?php

namespace SeriouslySimplePodcasting\Controllers;

use stdClass;
use WP_Query;

use SeriouslySimplePodcasting\ShortCodes\Player;
use SeriouslySimplePodcasting\ShortCodes\Podcast;
use SeriouslySimplePodcasting\ShortCodes\Podcast_Episode;
use SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist;

use SeriouslySimplePodcasting\Widgets\Playlist;
use SeriouslySimplePodcasting\Widgets\Series;
use SeriouslySimplePodcasting\Widgets\Recent_Episodes;
use SeriouslySimplePodcasting\Widgets\Single_Episode;

use SeriouslySimplePodcasting\Handlers\Options_Handler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.0
 */
class Frontend_Controller extends Controller {

	public $episode_controller;

	// @todo reference prior to analytics launch
	public $style_guide = array(
		'dark'     => '#3A3A3A',
		'medium'   => '#666666',
		'light'    => '#939393',
		'lightest' => '#f9f9f9',
		'accent'   => '#ea5451',
	);

	/**
	 * Constructor
	 *
	 * @param    string $file Plugin base file
	 * @param    string $version
	 */
	public function __construct( $file, $version ) {

		parent::__construct( $file, $version );

		$this->episode_controller = new Episode_Controller( $file, $version );

		global $large_player_instance_number;
		$large_player_instance_number = 0;

		// Apply filters to the style guide so that users may swap out colours of the player
		$this->style_guide = apply_filters( 'ssp_filter_style_guide', $this->style_guide );

		$this->register_hooks_and_filters();
	}

	public function register_hooks_and_filters() {

		// Add meta data to start of podcast content
		$locations = get_option( 'ss_podcasting_player_locations', array( 'content' ) );

		if ( in_array( 'content', (array) $locations, true ) ) {
			add_filter( 'the_content', array( $this, 'content_meta_data' ), 10, 1 );
		}

		if ( in_array( 'excerpt', (array) $locations, true ) ) {
			add_filter( 'the_excerpt', array( $this, 'get_excerpt_meta_data' ), 10, 1 );
		}

		if ( in_array( 'excerpt_embed', (array) $locations, true ) ) {
			add_filter( 'the_excerpt_embed', array( $this, 'get_embed_meta_data' ), 10, 1 );
		}

		// Add SSP label and version to generator tags
		add_action( 'get_the_generator_html', array( $this, 'generator_tag' ), 10, 2 );
		add_action( 'get_the_generator_xhtml', array( $this, 'generator_tag' ), 10, 2 );

		// Add RSS meta tag to site header
		add_action( 'wp_head', array( $this, 'rss_meta_tag' ) );

		// Add podcast episode to main query loop if setting is activated
		add_action( 'pre_get_posts', array( $this, 'add_to_home_query' ) );

		// Make sure to fetch all relevant post types when viewing series archive
		add_action( 'pre_get_posts', array( $this, 'add_all_post_types' ) );

		// Make sure to fetch all relevant post types when viewing a tag archive
		add_action( 'pre_get_posts', array( $this, 'add_all_post_types_for_tag_archive' ) );

		// Download podcast episode
		add_action( 'wp', array( $this, 'download_file' ), 1 );

		// Trigger import podcast process (if active)
		add_action( 'wp_loaded', array( $this, 'import_existing_podcast_to_podmotor' ) );

		// Register widgets
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		// Add shortcodes
		add_action( 'init', array( $this, 'register_shortcodes' ), 1 );

		add_filter( 'feed_content_type', array( $this, 'feed_content_type' ), 10, 2 );

		// Handle localisation
		add_action( 'plugins_loaded', array( $this, 'load_localisation' ) );

		// Load fonts, styles and javascript
		add_action( 'wp_enqueue_scripts', array( $this, 'load_styles_and_scripts' ) );

		// Enqueue HTML5 scripts only if the page has an HTML5 player on it
		add_action( 'wp_print_footer_scripts', array( $this, 'html5_player_conditional_scripts' ) );

		// Enqueue HTML5 styles only if the page has an HTML5 player on it
		add_action( 'wp_print_footer_scripts', array( $this, 'html5_player_styles' ) );

		// Add overridable styles to footer
		add_action( 'wp_footer', array( $this, 'ssp_override_player_styles' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
	}

	/**
	 * HTML5 player additional scripts
	 * @todo this should really be done in a better way
	 */
	public function html5_player_conditional_scripts() {
		global $large_player_instance_number;
		if ( ( ! (int) $large_player_instance_number ) > 0 ) {
			return;
		}
		?>
		<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:400,700&v=<?php echo SSP_VERSION ?>"/>
		<link rel="stylesheet" href="<?php echo SSP_PLUGIN_URL ?>assets/css/icon_fonts.css?v=<?php echo SSP_VERSION ?>"/>
		<link rel="stylesheet" href="<?php echo SSP_PLUGIN_URL ?>assets/fonts/Gizmo/gizmo.css?v=<?php echo SSP_VERSION ?>"/>
		<link rel="stylesheet" href="<?php echo SSP_PLUGIN_URL ?>assets/css/frontend.css?v=<?php echo SSP_VERSION ?>"/>
		<?php if (defined('SCRIPT_DEBUG')){ ?>
			<script src="//cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/1.4.0/wavesurfer.js?v=<?php echo SSP_VERSION ?>"></script>
		<?php } else { ?>
			<script src="//cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/1.4.0/wavesurfer.min.js?v=<?php echo SSP_VERSION ?>"></script>
		<?php }
	}

	/**
	 * Register Custom HTML player styles
	 * @todo can this be merged into the load_styles_and_scripts function
	 */
	public function html5_player_styles() {
		global $large_player_instance_number;
		if ( ( ! (int) $large_player_instance_number ) > 0 ) {
			return;
		}
		wp_register_style( 'ssp-html5-player', $this->assets_url . 'css/html5-player.css', array(), $this->version );
		wp_enqueue_style( 'ssp-html5-player' );
	}

	/**
	 * Override player styles
	 * @todo what is this used for
	 */
	public function ssp_override_player_styles() {
		$player_wave_form_progress_colour = get_option( 'ss_podcasting_player_wave_form_progress_colour', false );
		?>
		<style type="text/css">
			.ssp-wave wave wave {
				background: <?php echo $player_wave_form_progress_colour ? $player_wave_form_progress_colour : "#28c0e1"; ?> !important;
			}
		</style>
		<?php
	}

	/**
	 * Enqueue styles and scripts
	 */
	public function load_styles_and_scripts() {
		$player_style = get_option( 'ss_podcasting_player_style', 'standard' );

		if ( 'standard' === $player_style ) {
			return;
		}
		wp_register_script( 'media-player', $this->assets_url . 'js/media.player.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'media-player' );

		wp_register_script( 'html5-player', $this->assets_url . 'js/html5.player.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'html5-player' );
	}

	/**
	 * @todo can this be merged into the load_styles_and_scripts method?
	 */
	public function load_scripts() {
		wp_register_style( 'ssp-frontend-player', $this->assets_url . 'css/player.css', array(), $this->version );
		wp_enqueue_style( 'ssp-frontend-player' );
	}

	/**
	 * Add episode meta data to the full content
	 * @param  string $content Existing content
	 * @return string          Modified content
	 */
	public function content_meta_data( $content = '' ) {

		global $post, $wp_current_filter, $episode_context;

		// Don't do anything if we don't have a valid post object
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return $content;
		}

		// Don't output unformatted data on excerpts
		if ( in_array( 'get_the_excerpt', (array) $wp_current_filter, true ) ) {
			return $content;
		}

		// Don't output episode meta in shortcode or widget
		if ( isset( $episode_context ) && in_array( $episode_context, array( 'shortcode', 'widget' ), true ) ) {
			return $content;
		}

		if ( post_password_required( $post->ID ) ) {
			return $content;
		}

		// Don't output episode meta in a REST Request
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $content;
		}

		$podcast_post_types = ssp_post_types( true );

		$player_visibility = get_option( 'ss_podcasting_player_content_visibility', 'all' );

		switch ( $player_visibility ) {
			case 'all':
				$show_player = true;
				break;
			case 'membersonly':
				$show_player = is_user_logged_in();
				break;
			default:
				$show_player = true;
				break;
		}

		if ( $show_player && in_array( $post->post_type, $podcast_post_types ) && ! is_feed() && ! isset( $_GET['feed'] ) ) {

			// Get episode meta data
			$meta = $this->episode_meta( $post->ID, 'content' );

			// Get specified player position
			$player_position = get_option( 'ss_podcasting_player_content_location', 'above' );

			switch ( $player_position ) {
				case 'above':
					$content = $meta . $content;
					break;
				case 'below':
					$content = $content . $meta;
					break;
			}
		}

		return $content;
	}

	/**
	 * Runs checks to see if the player should be rendered or not
	 *
	 * @param $episode_id
	 *
	 * @param bool $show_player default true
	 *
	 * @return boolean
	 */
	public function validate_media_player( $episode_id, $show_player = true ) {
		/**
		 * If the show_player variable was already set false (specifically for the ss_podcast shortcode)
		 */
		if ( false === $show_player ) {
			return false;
		}
		/**
		 * Check if the user is using the ss_player shortcode anywhere in this post
		 */
		if ( ssp_check_if_podcast_has_shortcode( $episode_id, 'ss_player' ) ) {
			$show_player = false;
		}

		if ( ! $show_player ) {
			return false;
		}
		/**
		 * @todo add a check for the block player
		 */
		/**
		 * Check if the user is using an elementor widget version of the player.
		 */
		if ( ssp_is_elementor_ok() && ssp_check_if_podcast_has_elementor_player( $episode_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get episode meta data
	 *
	 * @param  integer $episode_id ID of episode post
	 * @param  string $context Context for display
	 *
	 * @return string               Episode meta
	 */
	public function episode_meta( $episode_id = 0, $context = 'content' ) {

		$meta = '';

		if ( ! $episode_id ) {
			return $meta;
		}

		$file = $this->get_enclosure( $episode_id );

		if ( $file ) {

			if ( get_option( 'permalink_structure' ) ) {
				$file = $this->get_episode_download_link( $episode_id );
			}

			// Hide audio player in `ss_podcast` shortcode by default
			$show_player = true;
			if ( 'shortcode' === $context ) {
				$show_player = false;
			}

			// Show audio player if requested
			$player_style = get_option( 'ss_podcasting_player_style' );

			$show_player = $this->validate_media_player( $episode_id, $show_player );

			// Allow media player to be dynamically hidden/displayed
			$show_player = apply_filters( 'ssp_show_media_player', $show_player, $context );

			if ( $show_player ) {
				$meta .= '<div class="podcast_player">' . $this->load_media_player( $file, $episode_id, $player_style ) . '</div>';
				if ( apply_filters( 'ssp_show_episode_details', true, $episode_id, $context ) ) {
					$meta .= $this->episode_meta_details( $episode_id, $context );
				}
			}
		}

		$meta = apply_filters( 'ssp_episode_meta', $meta, $episode_id, $context );
		return $meta;
	}

	/**
	 * Get episode enclosure
	 * Wrapper for Episode_Controller get_enclosure method
	 *
	 * @param  integer $episode_id ID of episode
	 * @return string              URL of enclosure
	 */
	public function get_enclosure( $episode_id = 0 ) {
		return $this->episode_controller->get_enclosure( $episode_id );
	}

	/**
	 * Get download link for episode
	 * Wrapper for Episode_Controller get_episode_download_link method
	 *
	 * @param  integer $episode_id ID of episode
	 * @param  string $referrer Referrer
	 * @return string              Episode download link
	 */
	public function get_episode_download_link( $episode_id, $referrer = '' ) {
		return $this->episode_controller->get_episode_download_link( $episode_id, $referrer );
	}

	/**
	 * Return media player for a given file. Used to enable other checks or to prevent the player from loading
	 * @param string $src_file
	 * @param int $episode_id
	 * @param string $player_size
	 *
	 * @return string
	 */
	public function media_player( $src_file = '', $episode_id = 0, $player_size = 'large' ) {
		$media_player = '';
		$show_player  = $this->validate_media_player( $episode_id );
		if ( $show_player ) {
			$media_player = $this->load_media_player( $src_file, $episode_id, $player_size );
		}

		return $media_player;
	}

	/**
	 * Load media player for given file
	 * @param  string  $src_file        Source of file
	 * @param  integer $episode_id Episode ID for audio file
	 * @param  string $player_size mini or large
	 * @return string              Media player HTML on success, empty string on failure
	 */
	public function load_media_player($src_file = '', $episode_id = 0, $player_size){
		/**
		 * Check if this player is being loaded via the AMP for WordPress plugin and if so, force the standard player
		 * https://wordpress.org/plugins/amp/
		 */
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			$player_size = 'mini';
		}

		if ( $player_size == 'large' || $player_size == 'larger' ) {
			global $large_player_instance_number;
			$large_player_instance_number++;
		}

		$player = '';

		if ( $src_file ) {

			// Get episode type and default to audio
			$type = $this->get_episode_type( $episode_id );
			if( ! $type ) {
				$type = 'audio';
			}

			// Switch to podcast player URL
			$src_file = str_replace( 'podcast-download', 'podcast-player', $src_file );

			// Set up parameters for media player
			$params = array( 'src' => $src_file, 'preload' => 'none' );

			// Use built-in WordPress media player
			// Or use new custom player if user has selected as such

			switch( $type ) {

				case 'audio' :

					$player_style = (string) get_option( 'ss_podcasting_player_style' );
					if( $player_size == "large" ){
						$player_style = "larger";
					}

					if( "larger" !== $player_style || "mini" === $player_size ){
						$player = wp_audio_shortcode( $params );
					}else{

						// ---- NEW PLAYER -----

						// Get episode album art
						$albumArt = $this->episode_controller->get_album_art( $episode_id );

						$player_background_colour = get_option( 'ss_podcasting_player_background_skin_colour', false );
						$player_wave_form_colour = get_option( 'ss_podcasting_player_wave_form_colour', false );
						$player_wave_form_progress_colour = get_option( 'ss_podcasting_player_wave_form_progress_colour', false );

						$player_background_colour = ( $player_background_colour ? ' style="background: ' . $player_background_colour . ';"' : 'background: #333;' );
						$player_wave_form_progress_colour = ( $player_wave_form_progress_colour ? $player_wave_form_progress_colour : "#28c0e1" );

						$meta = $this->episode_meta_details( $episode_id, '', true );

						ob_start();
						// @todo move this all into an overridable template file
						?>
						<div class="ssp-player ssp-player-large" data-player-instance-number="<?php echo $large_player_instance_number; ?>" data-player-waveform-colour="<?php echo $player_wave_form_colour; ?>" data-player-waveform-progress-colour="<?php echo $player_wave_form_progress_colour; ?>" data-source-file="<?php echo $src_file ?>" id="ssp_player_id_<?php echo $large_player_instance_number; ?>" <?php echo $player_background_colour ?>>
							<?php if( apply_filters( 'ssp_show_album_art', true, get_the_ID() ) ) { ?>
								<div class="ssp-album-art-container">
									<div class="ssp-album-art" style="background: url( <?php echo apply_filters( 'ssp_album_art_cover', $albumArt['src'], get_the_ID() ); ?> ) center center no-repeat; -webkit-background-size: cover;background-size: cover;"></div>
								</div>
							<?php }; ?>
							<div style="overflow: hidden">
								<div class="ssp-player-inner" style="overflow: hidden;">
									<div class="ssp-player-info">
										<div style="width: 80%; float:left;">
											<h3 class="ssp-player-title episode-title">
												<?php
												echo apply_filters( 'ssp_podcast_title', get_the_title( $episode_id ), get_the_ID() );
												if( $series = get_the_terms( $episode_id, 'series' ) ){
													echo ( !empty( $series ) && isset( $series[0] ) ) ? '<br><span class="ssp-player-series">' . substr( $series[0]->name, 0, 35) . ( strlen( $series[0]->name ) > 35 ? '...' : '' ) . '</span>' : '';
												}
												?>
											</h3>
										</div>
										<div class="ssp-download-episode" style="overflow: hidden;text-align:right;"></div>
										<div>&nbsp;</div>
										<div class="ssp-media-player">
											<div class="ssp-custom-player-controls">
												<div class="ssp-play-pause" id="ssp-play-pause">
													<span class="ssp-icon ssp-icon-play_icon"><span class="screen-reader-text">Play/Pause Episode</span></span>
												</div>
												<div class="ssp-wave-form">
													<div class="ssp-inner">
														<div data-waveform-id="waveform_<?php echo $large_player_instance_number; ?>" id="waveform<?php echo $large_player_instance_number; ?>" class="ssp-wave"></div>
													</div>
												</div>
												<div class="ssp-time-volume">
													<div class="ssp-duration">
														<span id="sspPlayedDuration">00:00</span> / <span id="sspTotalDuration"><?php echo $meta['duration']; ?></span>
													</div>
													<div class="ssp-volume">
														<div class="ssp-back-thirty-container">
															<div class="ssp-back-thirty-control" id="ssp-back-thirty">
																<i class="ssp-icon icon-replay"><span class="screen-reader-text">Rewind 30 Seconds</span></i>
															</div>
														</div>
														<div class="ssp-playback-speed-label-container">
															<div class="ssp-playback-speed-label-wrapper">
																<span data-playback-speed-id="ssp_playback_speed_<?php echo $large_player_instance_number; ?>" id="ssp_playback_speed<?php echo $large_player_instance_number; ?>" data-ssp-playback-rate="1">1X</span>
															</div>
														</div>
														<div class="ssp-download-container">
															<div class="ssp-download-control">
																<a class="ssp-episode-download" href="<?php echo $this->get_episode_download_link( $episode_id, 'download' ); ?>" target="_blank"><i class="ssp-icon icon-cloud-download"><span class="screen-reader-text">Download Episode</span></i></a>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						$player = ob_get_clean();
						// ---- /NEW PLAYER -----
					}

					break;

				case 'video':

					// Use featured image as video poster
					if( $episode_id && has_post_thumbnail( $episode_id ) ) {
						$poster = wp_get_attachment_url( get_post_thumbnail_id( $episode_id ) );
						if( $poster ) {
							$params['poster'] = $poster;
						}
					}

					$player = wp_video_shortcode( $params );
					break;
			}

			// Allow filtering so that alternative players can be used
			$player = apply_filters( 'ssp_media_player', $player, $src_file, $episode_id );
		}

		return $player;
	}

	/**
	 * Get the type of podcast episode (audio or video)
	 * @param  integer $episode_id ID of episode
	 * @return mixed              [description]
	 */
	public function get_episode_type( $episode_id = 0 ) {

		if( ! $episode_id ) {
			return false;
		}

		$type = get_post_meta( $episode_id , 'episode_type' , true );

		if( ! $type ) {
			$type = 'audio';
		}

		return $type;
	}

	/**
	 * Fetch episode meta details
	 * @param  integer $episode_id ID of episode post
	 * @param  string  $context    Context for display
	 * @return string              Episode meta details
	 */
	public function episode_meta_details ( $episode_id = 0, $context = 'content', $return = false ) {

		if ( ! $episode_id ) {
			return;
		}

		$file = $this->get_enclosure( $episode_id );

		if ( ! $file ) {
			return;
		}

		$link = $this->get_episode_download_link( $episode_id, 'download' );
		$duration = get_post_meta( $episode_id , 'duration' , true );
		$size = get_post_meta( $episode_id , 'filesize' , true );
		if ( ! $size ) {
			$size_data = $this->get_file_size( $file );
			$size = $size_data['formatted'];
			if ( $size ) {
				if ( isset( $size_data['formatted'] ) ) {
					update_post_meta( $episode_id, 'filesize', $size_data['formatted'] );
				}

				if ( isset( $size_data['raw'] ) ) {
					update_post_meta( $episode_id, 'filesize_raw', $size_data['raw'] );
				}
			}
		}

		$date_recorded = get_post_meta( $episode_id, 'date_recorded', true );

		// Build up meta data array with default values
		$meta = array(
			'link' => '',
			'new_window' => false,
			'duration' => 0,
			'date_recorded' => '',
		);

		if( $link ) {
			$meta['link'] = $link;
		}

		if( $link && apply_filters( 'ssp_show_new_window_link', true, $context ) ) {
			$meta['new_window'] = true;
		}

		if( $link ) {
			$meta['duration'] = $duration;
		}

		if( $date_recorded ) {
			$meta['date_recorded'] = $date_recorded;
		}

		// Allow dynamic filtering of meta data - to remove, add or reorder meta items
		$meta = apply_filters( 'ssp_episode_meta_details', $meta, $episode_id, $context );

		if( true === $return ){
			return $meta;
		}

		$meta_display = '';
		$podcast_display = '';
		$subscribe_display = '';

		$meta_sep = apply_filters( 'ssp_episode_meta_separator', ' | ' );

		foreach ( $meta as $key => $data ) {

			if( ! $data ) {
				continue;
			}

			if( $podcast_display ) {
				$podcast_display .= $meta_sep;
			}

			switch( $key ) {

				case 'link':
					$podcast_display .= '<a href="' . esc_url( $data ) . '" title="' . get_the_title() . ' " class="podcast-meta-download">' . __( 'Download file' , 'seriously-simple-podcasting' ) . '</a>';
					break;

				case 'new_window':
					$play_link = add_query_arg( 'ref', 'new_window', $link );
					$podcast_display .= '<a href="' . esc_url( $play_link ) . '" target="_blank" title="' . get_the_title() . ' " class="podcast-meta-new-window">' . __( 'Play in new window' , 'seriously-simple-podcasting' ) . '</a>';
					break;

				case 'duration':
					$podcast_display .= '<span class="podcast-meta-duration">' . __( 'Duration' , 'seriously-simple-podcasting' ) . ': ' . $data . '</span>';
					break;

				case 'date_recorded':
					$podcast_display .= '<span class="podcast-meta-date">' . __( 'Recorded on' , 'seriously-simple-podcasting' ) . ' ' . date_i18n( get_option( 'date_format' ), strtotime( $data ) ) . '</span>';
					break;

				// Allow for custom items to be added, but only allow a small amount of HTML tags
				default:
					$allowed_tags = array(
						'strong' => array(),
						'b' => array(),
						'em' => array(),
						'i' => array(),
						'a' => array(
							'href' => array(),
							'title' => array(),
							'target' => array(),
						),
					);
					$podcast_display .= wp_kses( $data, $allowed_tags );
					break;

			}
		}

		// @todo implement dependancy injection
		$options_handler = new Options_Handler();
		$subscribe_urls  = $options_handler->get_subscribe_urls( $episode_id, $context );

		foreach( $subscribe_urls as $key => $data ){

			if ( empty( $data['url'] ) ) {
				continue;
			}

			if( $subscribe_display ){
				$subscribe_display .= $meta_sep;
			}

			if (preg_match('/\b_url\b/', $key) === false) {
				$allowed_tags = array(
					'strong' => array(),
					'b' => array(),
					'em' => array(),
					'i' => array(),
					'a' => array(
						'href' => array(),
						'title' => array(),
						'target' => array(),
					),
				);
				$subscribe_display .= wp_kses( $data['url'], $allowed_tags );
			}else {
				$subscribe_display .= '<a href="' . esc_url( $data['url'] ) . '" target="_blank" title="' . $data['label'] . '" class="podcast-meta-itunes">' . $data['label'] . '</a>';
			}

		}

		if ( ! empty( $podcast_display ) || ! empty( $subscribe_display ) ) {

			$meta_display .= '<div class="podcast_meta"><aside>';

			$ss_podcasting_player_meta_data_enabled = get_option('ss_podcasting_player_meta_data_enabled', 'on');

			if ( $ss_podcasting_player_meta_data_enabled && $ss_podcasting_player_meta_data_enabled == 'on' ) {
				if ( ! empty( $podcast_display ) ) {
					$podcast_display = '<p>' . $podcast_display . '</p>';
					$podcast_display = apply_filters( 'ssp_include_episode_meta_data', $podcast_display );
					if ( $podcast_display && ! empty( $podcast_display ) ) {
						$meta_display .= $podcast_display;
					}
				}
			}

			if ( ! empty( $subscribe_display ) ) {
				$subscribe_display = '<p>' . __( 'Subscribe:', 'seriously-simple-podcasting' ) . ' ' . $subscribe_display . '</p>';
				$subscribe_display = apply_filters( 'ssp_include_podcast_subscribe_links', $subscribe_display );
				if ( $subscribe_display && ! empty( $subscribe_display ) ) {
					$meta_display .= $subscribe_display;
				}
			}

			$meta_display .= '</aside></div>';
		}

		return apply_filters('ssp_include_player_meta', $meta_display );

	}


	/**
	 * Get size of media file
	 * @param  string  $file File name & path
	 * @return boolean       File size on success, boolean false on failure
	 */
	public function get_file_size( $file = '' ) {

		/**
		 * ssp_enable_get_file_size filter to allow this functionality to be disabled programmatically
		 */
		$enabled = apply_filters( 'ssp_enable_get_file_size', true );
		if ( ! $enabled ) {
			return false;
		}

		if ( $file ) {

			// Include media functions if necessary
			if ( ! function_exists( 'wp_read_audio_metadata' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			// translate file URL to local file path if possible
			$file = $this->get_local_file_path( $file );

			// Get file data (for local file)
			$data = wp_read_audio_metadata( $file );

			$raw = $formatted = '';

			if ( $data ) {
				$raw = $data['filesize'];
				$formatted = $this->format_bytes( $raw );
			} else {

				// get file data (for remote file)
				$data = wp_remote_head( $file, array( 'timeout' => 10, 'redirection' => 5 ) );

				if ( ! is_wp_error( $data ) && is_array( $data ) && isset( $data['headers']['content-length'] ) ) {
					$raw = $data['headers']['content-length'];
					$formatted = $this->format_bytes( $raw );
				}
			}

			if ( $raw || $formatted ) {

				$size = array(
					'raw' => $raw,
					'formatted' => $formatted
				);

				return apply_filters( 'ssp_file_size', $size, $file );
			}

		}

		return false;
	}

	/**
	 * Returns a local file path for the given file URL if it's local. Otherwise
	 * returns the original URL
	 *
	 * @param    string    file
	 * @return   string    file or local file path
	 */
	function get_local_file_path( $file ) {

		// Identify file by root path and not URL (required for getID3 class)
		$site_root = trailingslashit( ABSPATH );

		// Remove common dirs from the ends of site_url and site_root, so that file can be outside of the WordPress installation
		$root_chunks = explode( '/', $site_root );
		$url_chunks  = explode( '/', $this->site_url );

		end( $root_chunks );
		end( $url_chunks );

		while ( ! is_null( key( $root_chunks ) ) && ! is_null( key( $url_chunks ) ) && ( current( $root_chunks ) == current( $url_chunks ) ) ) {
			array_pop( $root_chunks );
			array_pop( $url_chunks );
			end( $root_chunks );
			end( $url_chunks );
		}

		$site_root = implode('/', $root_chunks);
		$site_url  = implode('/', $url_chunks);

		$file = str_replace( $site_url, $site_root, $file );

		return $file;
	}

	/**
	 * Format filesize for display
	 * @param  integer $size      Raw file size
	 * @param  integer $precision Level of precision for formatting
	 * @return mixed              Formatted file size on success, false on failure
	 */
	protected function format_bytes( $size , $precision = 2 ) {

		if ( $size ) {

			$base = log ( $size ) / log( 1024 );
			$suffixes = array( '' , 'k' , 'M' , 'G' , 'T' );
			$formatted_size = round( pow( 1024 , $base - floor( $base ) ) , $precision ) . $suffixes[ floor( $base ) ];

			return apply_filters( 'ssp_file_size_formatted', $formatted_size, $size );
		}

		return false;
	}

	/**
	 * Add the meta data to the episode excerpt
	 * @param  string $excerpt Existing excerpt
	 * @return string          Modified excerpt
	 */
	public function get_excerpt_meta_data( $excerpt = '' ) {
		return $this->excerpt_meta_data( $excerpt, 'excerpt' );
	}

	/**
	 * Add episode meta data to the excerpt
	 * @param  string $excerpt Existing excerpt
	 * @return string          Modified excerpt
	 */
	public function excerpt_meta_data( $excerpt = '', $content = 'excerpt' ) {

		global $post;

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return $excerpt;
		}

		if( post_password_required( $post->ID ) ) {
			return $excerpt;
		}

		// Don't output episode meta in a REST Request
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $content;
		}

		$podcast_post_types = ssp_post_types( true );

		$player_visibility = get_option( 'ss_podcasting_player_content_visibility', 'all' );

		switch( $player_visibility ) {
			case 'all': $show_player = true; break;
			case 'membersonly': $show_player = is_user_logged_in(); break;
			default: $show_player = true; break;
		}

		if ( $show_player && in_array( $post->post_type, $podcast_post_types ) && ! is_feed() && ! isset( $_GET['feed'] ) ) {

			$meta = $this->episode_meta( $post->ID, $content );

			$excerpt = $meta . $excerpt;

		}

		return $excerpt;
	}

	/**
	 * Add the meta data to the embedded episode excerpt
	 * @param  string $excerpt Existing excerpt
	 * @return string          Modified excerpt
	 */
	public function get_embed_meta_data( $excerpt = '' ) {
		return $this->excerpt_meta_data( $excerpt, 'embed' );
	}

	/**
	 * Add podcast to home page query
	 * @param object $query The query object
	 */
	public function add_to_home_query( $query ) {

		if ( is_admin() ) {
			return;
		}

		$include_in_main_query = get_option('ss_podcasting_include_in_main_query');
		if ( $include_in_main_query && $include_in_main_query == 'on' ) {
			if ( $query->is_home() && $query->is_main_query() ) {
				$query->set( 'post_type', array( 'post', 'podcast' ) );
			}
		}
	}

	public function add_all_post_types ( $query ) {

		if ( is_admin() ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( is_post_type_archive( 'podcast' ) || is_tax( 'series' ) ) {

			$podcast_post_types = ssp_post_types( false );

			if ( empty( $podcast_post_types ) ) {
				return;
			}

			$episode_ids = ssp_episode_ids();
			if ( ! empty( $episode_ids ) ) {

				$query->set( 'post__in', $episode_ids );

				$podcast_post_types[] = 'podcast';
				$query->set( 'post_type', $podcast_post_types );

			}

		}

	}

	public function add_all_post_types_for_tag_archive( $query ) {

		if ( is_admin() ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( !is_tag() ) {
			return;
		}

		$tag_archive_post_types = apply_filters( 'ssp_tag_archive_post_types', array('post', 'podcast') ) ;
		$query->set( 'post_type', $tag_archive_post_types );

	}

	/**
	 * Get duration of audio file
	 * @param  string $file File name & path
	 * @return mixed        File duration on success, boolean false on failure
	 */
	public function get_file_duration( $file ) {

		/**
		 * ssp_enable_get_file_duration filter to allow this functionality to be disabled programmatically
		 */
		$enabled = apply_filters( 'ssp_enable_get_file_duration', true );
		if ( ! $enabled ) {
			return false;
		}

		if ( $file ) {

			// Include media functions if necessary
			if ( ! function_exists( 'wp_read_audio_metadata' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			// translate file URL to local file path if possible
			$file = $this->get_local_file_path( $file );

			// Get file data (will only work for local files)
			$data = wp_read_audio_metadata( $file );

			$duration = false;

			if ( $data ) {
				if ( isset( $data['length_formatted'] ) && strlen( $data['length_formatted'] ) > 0 ) {
					$duration = $data['length_formatted'];
				} else {
					if ( isset( $data['length'] ) && strlen( $data['length'] ) > 0 ) {
						$duration = gmdate( 'H:i:s', $data['length'] );
					}
				}
			}

			if ( $data ) {
				return apply_filters( 'ssp_file_duration', $duration, $file );
			}

		}

		return false;
	}

	/**
	 * Load audio player for given file - wrapper for `media_player` method to maintain backwards compatibility
	 * @param  string  $src 	   Source of audio file
	 * @param  integer $episode_id Episode ID for audio empty string
	 * @return string        	   Audio player HTML on success, false on failure
	 */
	public function audio_player( $src = '', $episode_id = 0 ) {
		$player = $this->media_player( $src, $episode_id );
		return apply_filters( 'ssp_audio_player', $player, $src, $episode_id );
	}

	/**
	 * Get episode image
	 * @param  integer $id   ID of episode
	 * @param  string  $size Image size
	 * @return string        Image HTML markup
	 */
	public function get_image( $id = 0, $size = 'full' ) {
		$image = '';

		if ( has_post_thumbnail( $id ) ) {
			// If not a string or an array, and not an integer, default to 200x9999.
			if ( is_int( $size ) || ( 0 < intval( $size ) ) ) {
				$size = array( intval( $size ), intval( $size ) );
			} elseif ( ! is_string( $size ) && ! is_array( $size ) ) {
				$size = array( 200, 9999 );
			}
			$image = get_the_post_thumbnail( intval( $id ), $size );
		}

		return apply_filters( 'ssp_episode_image', $image, $id );
	}

	/**
	 * Get podcast
	 * @param  mixed $args Arguments to be passed to the query.
	 * @return mixed       Array if true, boolean if false.
	 */
	public function get_podcast( $args = '' ) {
		$defaults = array(
			'title' => '',
			'content' => 'series',
			'series' => ''
		);

		$args = apply_filters( 'ssp_get_podcast_args', wp_parse_args( $args, $defaults ) );

		$query = array();

		if ( 'episodes' == $args['content'] ) {

			// Get selected series
			$podcast_series = '';
			if ( isset( $args['series'] ) && $args['series'] ) {
				$podcast_series = $args['series'];
			}

			// Get query args
			$query_args = apply_filters( 'ssp_get_podcast_query_args', ssp_episodes( -1, $podcast_series, true, '' ) );

			// The Query
			$query = get_posts( $query_args );

			// The Display
			if ( ! is_wp_error( $query ) && is_array( $query ) && count( $query ) > 0 ) {
				foreach ( $query as $k => $v ) {
					// Get the URL
					$query[$k]->url = get_permalink( $v->ID );
				}
			} else {
				$query = false;
			}

		} else {

			$terms = get_terms( 'series' );

			if ( count( $terms ) > 0) {

				foreach ( $terms as $term ) {
					$query[ $term->term_id ] = new stdClass();
					$query[ $term->term_id ]->title = $term->name;
					$query[ $term->term_id ]->url = get_term_link( $term );

					$query_args = apply_filters( 'ssp_get_podcast_series_query_args', ssp_episodes( -1, $term->slug, true, '' ) );

					$posts = get_posts( $query_args );

					$count = count( $posts );
					$query[ $term->term_id ]->count = $count;
				}
			}

		}

		$query['content'] = $args['content'];

		return $query;
	}

	/**
	 * Get episode from audio file
	 * @param  string $file File name & path
	 * @return object       Episode post object
	 */
	public function get_episode_from_file( $file = '' ) {
		global $post;

		$episode = false;

		if ( $file != '' ) {

			$post_types = ssp_post_types( true );

			$args = array(
				'post_type' => $post_types,
				'post_status' => 'publish',
				'posts_per_page' => 1,
				'meta_key' => 'audio_file',
				'meta_value' => $file
			);

			$qry = new WP_Query( $args );

			if ( $qry->have_posts() ) {
				while ( $qry->have_posts() ) { $qry->the_post();
					$episode = $post;
					break;
				}
			}
		}

		return apply_filters( 'ssp_episode_from_file', $episode, $file );

	}

	/**
	 * Public action which is triggered from the Seriously Simple Hosting queue
	 * Imports episodes to Serioulsy Simple Hosting
	 */
	public function import_existing_podcast_to_podmotor(){
		// this will soon be deprecated
		$podcast_importer = ( isset( $_GET['podcast_importer'] ) ? filter_var( $_GET['podcast_importer'], FILTER_SANITIZE_STRING ) : '' );
		if (empty($podcast_importer)){
			$podcast_importer = ( isset( $_GET['ssp_podcast_importer'] ) ? filter_var( $_GET['ssp_podcast_importer'], FILTER_SANITIZE_STRING ) : '' );
		}
		if ( ! empty( $podcast_importer ) && 'true' == $podcast_importer ) {
			$continue = import_existing_podcast();
			if ( $continue ) {
				$reponse = array( 'continue' => 'false', 'response' => 'Podcast data imported' );
			} else {
				$reponse = array( 'continue' => 'true', 'response' => 'An error occurred importing the podcast data' );
			}
			wp_send_json( $reponse );
		}
	}

	/**
	 * Download file from `podcast_episode` query variable
	 * @return void
	 */
	public function download_file() {

		if ( is_podcast_download() ) {
			global $wp_query;

			// Get requested episode ID
			$episode_id = intval( $wp_query->query_vars['podcast_episode'] );

			if ( isset( $episode_id ) && $episode_id ) {

				// Get episode post object
				$episode = get_post( $episode_id );

				// Make sure we have a valid episode post object
				if ( ! $episode || ! is_object( $episode ) || is_wp_error( $episode ) || ! isset( $episode->ID ) ) {
					return;
				}

				// Do we have newlines?
				$parts = false;
				if( is_string( $episode ) ) {
					$parts = explode( "\n", $episode );
				}

				if ( $parts && is_array( $parts ) && count( $parts ) > 1 ) {
					$file = $parts[0];
				} else {
					// Get audio file for download
					$file = $this->get_enclosure( $episode_id );
				}

				// Exit if no file is found
				if ( ! $file ) {
					return;
				}

				// Get file referrer
				$referrer = '';
				if( isset( $wp_query->query_vars['podcast_ref'] ) && $wp_query->query_vars['podcast_ref'] ) {
					$referrer = $wp_query->query_vars['podcast_ref'];
				} else {
					if( isset( $_GET['ref'] ) ) {
						$referrer = esc_attr( $_GET['ref'] );
					}
				}

				// Allow other actions - functions hooked on here must not output any data
				do_action( 'ssp_file_download', $file, $episode, $referrer );

				// Set necessary headers
				header( "Pragma: no-cache" );
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Robots: none" );

				// Check file referrer
				if( 'download' == $referrer ) {

					// Set size of file
					// Do we have anything in Cache/DB?
					$size = wp_cache_get( $episode_id, 'filesize_raw' );

					// Nothing in the cache, let's see if we can figure it out.
					if ( false === $size ) {

						// Do we have anything in post_meta?
						$size = get_post_meta( $episode_id, 'filesize_raw', true );

						if ( empty( $size ) ) {

							// Let's see if we can figure out the path...
							$attachment_id = $this->get_attachment_id_from_url( $file );

							if ( ! empty( $attachment_id )  ) {
								$size = filesize( get_attached_file( $attachment_id ) );
								update_post_meta( $episode_id, 'filesize_raw', $size );
							}

						}

						// Update the cache
						wp_cache_set( $episode_id, $size, 'filesize_raw' );
					}

					// Send Content-Length header
					if ( ! empty( $size ) ) {
						header( "Content-Length: " . $size );
					}

					// Force file download
					header( "Content-Type: application/force-download" );

					// Set other relevant headers
					header( "Content-Description: File Transfer" );
					header( "Content-Disposition: attachment; filename=\"" . basename( $file ) . "\";" );
					header( "Content-Transfer-Encoding: binary" );

					// Encode spaces in file names until this is fixed in core (https://core.trac.wordpress.org/ticket/36998)
					$file = str_replace( ' ', '%20', $file );

					// Use ssp_readfile_chunked() if allowed on the server or simply access file directly
					@ssp_readfile_chunked( $file ) or header( 'Location: ' . $file );
				} else {

					// Encode spaces in file names until this is fixed in core (https://core.trac.wordpress.org/ticket/36998)
					$file = str_replace( ' ', '%20', $file );

					// For all other referrers redirect to the raw file
					wp_redirect( $file, 302 );
				}

				// Exit to prevent other processes running later on
				exit;

			}
		}
	}

	/**
	 * Get the ID of an attachment from its image URL.
	 *
	 * @param   string      $url    The path to an image.
	 * @return  int|bool            ID of the attachment or 0 on failure.
	 */
	public function get_attachment_id_from_url( $url = '' ) {

		// Let's hash the URL to ensure that we don't get
		// any illegal chars that might break the cache.
		$key = md5( $url );

		// Do we have anything in the cache for this URL?
		$attachment_id = wp_cache_get( $key, 'attachment_id' );

		if ( $attachment_id === false ) {

			// Globalize
			global $wpdb;

			// If there is no url, return.
			if ( '' === $url ) {
				return false;
			}

			// Set the default
			$attachment_id = 0;


			// Function introduced in 4.0, let's try this first.
			if ( function_exists( 'attachment_url_to_postid' ) ) {
				$attachment_id = absint( attachment_url_to_postid( $url ) );
				if ( 0 !== $attachment_id ) {
					wp_cache_set( $key, $attachment_id, 'attachment_id', DAY_IN_SECONDS );
					return $attachment_id;
				}
			}

			// Then this.
			if ( preg_match( '#\.[a-zA-Z0-9]+$#', $url ) ) {
				$sql = $wpdb->prepare(
					"SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s",
					esc_url_raw( $url )
				);
				$attachment_id = absint( $wpdb->get_var( $sql ) );
				if ( 0 !== $attachment_id ) {
					wp_cache_set( $key, $attachment_id, 'attachment_id', DAY_IN_SECONDS );
					return $attachment_id;
				}
			}

			// And then try this
			$upload_dir_paths = wp_upload_dir();
			if ( false !== strpos( $url, $upload_dir_paths['baseurl'] ) ) {
				// Ensure that we have file extension that matches iTunes.
				$url = preg_replace( '/(?=\.(m4a|mp3|mov|mp4)$)/i', '', $url );
				// Remove the upload path base directory from the attachment URL
				$url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $url );
				// Finally, run a custom database query to get the attachment ID from the modified attachment URL
				$sql = $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $url );
				$attachment_id = absint( $wpdb->get_var( $sql ) );
				if ( 0 !== $attachment_id ) {
					wp_cache_set( $key, $attachment_id, 'attachment_id', DAY_IN_SECONDS );
					return $attachment_id;
				}
			}

		}

		return $attachment_id;
	}

	/**
	 * Get MIME type of attachment file
	 *
	 * @param  string $attachment URL of resource
	 *
	 * @return mixed MIME type on success, false on failure
	 */
	public function get_attachment_mimetype( $attachment = '' ) {
		// Let's hash the URL to ensure that we don't get any illegal chars that might break the cache.
		$key = md5( $attachment );
		if ( $attachment ) {
			// Do we have anything in the cache for this?
			$mime = wp_cache_get( $key, 'mime-type' );
			if ( $mime === false ) {
				// Get the ID
				$id = $this->get_attachment_id_from_url( $attachment );
				// Get the MIME type
				$mime = get_post_mime_type( $id );
				// Set the cache
				wp_cache_set( $key, $mime, 'mime-type', DAY_IN_SECONDS );
			}

			return $mime;
		}

		return false;
	}

	/**
	 * Display plugin name and version in generator meta tag
	 * @return void
	 */
	public function generator_tag( $gen, $type ) {

		// Allow generator tags to be hidden if necessary
		if ( apply_filters( 'ssp_show_generator_tag', true, $type ) ) {

			$generator = 'Seriously Simple Podcasting ' . esc_attr( $this->version );

			switch ( $type ) {
				case 'html':
					$gen .= "\n" . '<meta name="generator" content="' . $generator . '">';
					break;
				case 'xhtml':
					$gen .= "\n" . '<meta name="generator" content="' . $generator . '" />';
					break;
			}

		}

		return $gen;
	}

	/**
	 * Display feed meta tag in site HTML
	 * @return void
	 */
	public function rss_meta_tag() {

		// Get feed slug
		$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );

		if ( get_option( 'permalink_structure' ) ) {
			$feed_url = $this->home_url . 'feed/' . $feed_slug;
		} else {
			$feed_url = $this->home_url . '?feed=' . $feed_slug;
		}

		$custom_feed_url = get_option( 'ss_podcasting_feed_url' );
		if ( $custom_feed_url ) {
			$feed_url = $custom_feed_url;
		}

		$feed_url = apply_filters( 'ssp_feed_url', $feed_url );

		$html = '';

		if( apply_filters( 'ssp_show_global_feed_tag', true ) ) {
			$html = '<link rel="alternate" type="application/rss+xml" title="' . __( 'Podcast RSS feed', 'seriously-simple-podcasting' ) . '" href="' . esc_url( $feed_url ) . '" />';
		}

		// Check if this is a series taxonomy archive and display series-specific RSS feed tag
		$current_obj = get_queried_object();
		if( isset( $current_obj->taxonomy ) && 'series' == $current_obj->taxonomy && isset( $current_obj->slug ) && $current_obj->slug ) {

			if( apply_filters( 'ssp_show_series_feed_tag', true, $current_obj->slug ) ) {

				if ( get_option( 'permalink_structure' ) ) {
					$series_feed_url = $feed_url . '/' . $current_obj->slug;
				} else {
					$series_feed_url = $feed_url . '&podcast_series=' . $current_obj->slug;
				}

				$html .= "\n" . '<link rel="alternate" type="application/rss+xml" title="' . sprintf( __( '%s RSS feed', 'seriously-simple-podcasting' ), $current_obj->name ) . '" href="' . esc_url( $series_feed_url ) . '" />';

			}

		}

		echo "\n" . apply_filters( 'ssp_rss_meta_tag', $html ) . "\n\n";
	}

	/**
	 * Register plugin widgets
	 * @return void
	 */
	public function register_widgets () {
		register_widget( new Playlist() );
		register_widget( new Series() );
		register_widget( new Single_Episode() );
		register_widget( new Recent_Episodes() );
	}

	/**
	 * Register plugin shortcodes
	 * @return void
	 */
	public function register_shortcodes () {
		add_shortcode( 'ss_player', array( new Player(), 'shortcode' ) );
		add_shortcode( 'ss_podcast', array( new Podcast(), 'shortcode' ) );
		add_shortcode( 'podcast_episode', array( new Podcast_Episode(), 'shortcode' ) );
		add_shortcode( 'podcast_playlist', array( new Podcast_Playlist(), 'shortcode' ) );
	}

	/**
	 * Show single podcast episode with specified content items
	 * @param  integer $episode_id    ID of episode post
	 * @param  array   $content_items Orderd array of content items to display
	 * @return string                 HTML of episode with specified content items
	 */
	public function podcast_episode ( $episode_id = 0, $content_items = array( 'title', 'player', 'details' ), $context = '', $style = 'mini' ) {

		global $post, $episode_context, $large_player_instance_number;

		$player_background_colour = get_option( 'ss_podcasting_player_background_skin_colour', false );
		$player_wave_form_colour = get_option( 'ss_podcasting_player_wave_form_colour', false );
		$player_wave_form_progress_colour = get_option( 'ss_podcasting_player_wave_form_progress_colour', false );

		if ( $style == 'large' || $style == 'larger' ) {
			$large_player_instance_number+= 1;
		}

		if ( ! $episode_id || ! is_array( $content_items ) || empty( $content_items ) ) {
			return;
		}

		// Get episode object
		$episode = get_post( $episode_id );

		if ( ! $episode || is_wp_error( $episode ) ) {
			return;
		}

		$html = '<div class="podcast-episode episode-' . esc_attr( $episode_id ) . '">' . "\n";

		// Setup post data for episode post object
		$post = $episode;
		setup_postdata( $post );

		$episode_context = $context;

		// Get episode album art
		$thumb_id = get_post_thumbnail_id( $episode_id );
		if ( ! empty( $thumb_id ) ) {
			list( $src, $width, $height ) = wp_get_attachment_image_src( $thumb_id, 'full' );
			$albumArt = compact( 'src', 'width', 'height' );
		} else {
			$albumArt['src'] = SSP_PLUGIN_URL . '/assets/images/no-album-art.png';
			$albumArt['width'] = 300;
			$albumArt['height'] = 300;
		}

		// Render different player styles
		/**
		 * This is very much the start of what needs to become a more integrated player.
		 * This player needs to also adapt for embeds, and needs to look presentable in many sizes
		 * @author Simon Dowdles - SSP <simon.dowdles@gmail.com>
		 * @todo Seperate logic into own js file
		 * @todo Work on styles
		 * @todo Work on feedback on player
		 * @todo Move CSS to own file
		 * @todo Add filters
		 * @todo Add settings pages to customize layout / colours
		 */

		if( 'mini' !== $style ){
			if( 'large' == $style ){

				foreach ( $content_items as $item ) {

					switch( $item ) {

						case 'title':
							$html .= '<h3 class="episode-title">' . get_the_title() . '</h3>' . "\n";
							break;

						case 'excerpt':
							$html .= '<p class="episode-excerpt">' . get_the_excerpt() . '</p>' . "\n";
							break;

						case 'content':
							$html .= '<div class="episode-content">' . apply_filters( 'the_content', get_the_content() ) . '</div>' . "\n";
							break;

						case 'player':
							$file = $this->get_enclosure( $episode_id );
							if ( get_option( 'permalink_structure' ) ) {
								$file = $this->get_episode_download_link( $episode_id );
							}
							$html .= '<div class="podcast_player">' . $this->media_player( $file, $episode_id, "large" ) . '</div>' . "\n";
							break;

						case 'details':
							$html .= $this->episode_meta_details( $episode_id, $episode_context );
							break;

						case 'image':
							$html .= get_the_post_thumbnail( $episode_id, apply_filters( 'ssp_frontend_context_thumbnail_size', 'thumbnail' ) );
							break;

					}
				}
			}
		}

		if( 'mini' === $style ){
			// Display specified content items in the order supplied
			foreach ( $content_items as $item ) {

				switch( $item ) {

					case 'title':
						$html .= '<h3 class="episode-title">' . get_the_title() . '</h3>' . "\n";
						break;

					case 'excerpt':
						$html .= '<p class="episode-excerpt">' . get_the_excerpt() . '</p>' . "\n";
						break;

					case 'content':
						$html .= '<div class="episode-content">' . apply_filters( 'the_content', get_the_content() ) . '</div>' . "\n";
						break;

					case 'player':
						$file = $this->get_enclosure( $episode_id );
						if ( get_option( 'permalink_structure' ) ) {
							$file = $this->get_episode_download_link( $episode_id );
						}
						$html .= '<div class="podcast_player">' . $this->media_player( $file, $episode_id, $style ) . '</div>' . "\n";
						break;

					case 'details':
						$html .= $this->episode_meta_details( $episode_id, $episode_context );
						break;

					case 'image':
						$html .= get_the_post_thumbnail( $episode_id, apply_filters( 'ssp_frontend_context_thumbnail_size', 'thumbnail' ) );
						break;

				}
			}
		}

		// Reset post data after fetching episode details
		wp_reset_postdata();

		$html .= '</div>' . "\n";

		return $html;
	}

	/**
	 * Set RSS content type for podcast feed
	 *
	 * @param  string $content_type Current content type
	 * @param  string $type         Type of feed
	 * @return string               Updated content type
	 */
	public function feed_content_type ( $content_type = '', $type = '' ) {

		if( 'podcast' == $type ) {
			$content_type = 'text/xml';
		}

		return $content_type;
	}

	public function load_localisation () {
		load_plugin_textdomain( 'seriously-simple-podcasting', false, basename( dirname( $this->file ) ) . '/languages/' );
	}

	/**
	 * Render the HTML content for the podcast list dynamic block
	 *
	 * @param $attributes block attributes
	 *
	 * @return string
	 */
	public function render_podcast_list_dynamic_block( $attributes ) {

		$paged                    = ( get_query_var( 'paged' ) ) ?: 1;
		$podcast_post_types       = ssp_post_types( true );
		$query_args               = array(
			'post_status'         => 'publish',
			'post_type'           => $podcast_post_types,
			'posts_per_page'      => get_option( 'posts_per_page', 10 ),
			'ignore_sticky_posts' => true,
			'paged'               => $paged
		);
		$query_args['meta_query'] = array(
			array(
				'key'     => 'audio_file',
				'compare' => '!=',
				'value'   => '',
			),
		);
		$query_args               = apply_filters( 'podcast_list_dynamic_block_query_arguments', $query_args );
		$episodes_query           = new WP_Query();
		$query_result             = $episodes_query->query( $query_args );

		$player_style = (string) get_option( 'ss_podcasting_player_style', '' );

		ob_start();
		foreach ( $query_result as $episode ) {
			$player = '';
			if ( isset( $attributes['player'] ) ) {
				$file = $this->get_enclosure( $episode->ID );
				if ( get_option( 'permalink_structure' ) ) {
					$file = $this->get_episode_download_link( $episode->ID );
				}
				$player = $this->load_media_player( $file, $episode->ID, $player_style );
				$player .= $this->episode_meta_details( $episode->ID, 'content' );
			}
			?>
			<article class="podcast-<?php echo $episode->ID ?> podcast type-podcast">
				<h2>
					<a class="entry-title-link" rel="bookmark" href="<?php echo esc_url( get_permalink( $episode->ID ) ); ?>">
						<?php echo $episode->post_title; ?>
					</a>
				</h2>
				<div class="podcast-content">
					<?php if ( isset( $attributes['featuredImage'] ) ) { ?>
						<a class="podcast-image-link" href="<?php echo esc_url( get_permalink( $episode->ID ) ) ?>" aria-hidden="true"
						   tabindex="-1">
							<?php echo get_the_post_thumbnail( $episode->ID, 'full' ); ?>
						</a>
					<?php } ?>
					<?php if ( ! empty( $player ) ) { ?>
						<p><?php echo $player; ?></p>
					<?php } ?>
					<?php if ( isset( $attributes['excerpt'] ) ) { ?>
						<p><?php echo get_the_excerpt( $episode->ID ); ?></p>
					<?php } ?>
				</div>
			</article>
		<?php }

		$episodeItems = ob_get_clean();

		$next_episodes_link     = get_next_posts_link( 'Older Episodes &raquo;', $episodes_query->max_num_pages );
		$previous_episodes_link = get_previous_posts_link( '&laquo; Newer Episodes' );
		if ( ! empty( $previous_episodes_link ) ) {
			$episodeItems .= $previous_episodes_link . ' | ';
		}
		$episodeItems .= $next_episodes_link;

		return apply_filters( 'podcast_list_dynamic_block_html_content', '<div>' . $episodeItems . '</div>' );
	}

}
