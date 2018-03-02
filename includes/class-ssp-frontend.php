<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Classes
 * @since       1.0
 */
class SSP_Frontend {

	// @todo reference prior to analytics launch
	public $style_guide = array(
		'dark'      => '#3A3A3A',
		'medium'    => '#666666',
		'light'     => '#939393',
		'lightest'  => '#f9f9f9',
		'accent'    => '#ea5451'
	);

	public $version;
	public $template_url;
	public $home_url;
	public $site_url;
	public $token;
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $template_path;

	/**
	 * Constructor
	 * @param 	string $file Plugin base file
	 */
	public function __construct( $file, $version ) {

		global $largePlayerInstanceNumber;
		$largePlayerInstanceNumber = 0;

		$this->version = $version;

		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->template_path = trailingslashit( $this->dir ) . 'templates/';
		$this->template_url = esc_url( trailingslashit( plugins_url( '/templates/', $file ) ) );
		$this->home_url = trailingslashit( home_url() );
		$this->site_url = trailingslashit( site_url() );
		$this->token = 'podcast';

		// Add meta data to start of podcast content
		$locations = get_option( 'ss_podcasting_player_locations', array() );

		if ( in_array( 'content', (array) $locations ) ) {
			add_filter( 'the_content', array( $this, 'content_meta_data' ), 10, 1 );
		}

		if ( in_array( 'excerpt', (array) $locations ) ) {
			add_filter( 'the_excerpt', array( $this, 'get_excerpt_meta_data' ), 10, 1 );
		}

		if ( in_array( 'excerpt_embed', (array) $locations ) ) {
			add_filter( 'the_excerpt_embed', array( $this, 'get_embed_meta_data' ), 10, 1 );
		}

		// Add SSP label and version to generator tags
		add_action( 'get_the_generator_html', array( $this, 'generator_tag' ), 10, 2 );
		add_action( 'get_the_generator_xhtml', array( $this, 'generator_tag' ), 10, 2 );

		// Add RSS meta tag to site header
		add_action( 'wp_head' , array( $this, 'rss_meta_tag' ) );

		// Add podcast episode to main query loop if setting is activated
		add_action( 'pre_get_posts' , array( $this, 'add_to_home_query' ) );

		// Make sure to fetch all relevant post types when viewing series archive
		add_action( 'pre_get_posts' , array( $this, 'add_all_post_types' ) );

		// Download podcast episode
		add_action( 'wp', array( $this, 'download_file' ), 1 );

		// Trigger import podcast process (if active)
		add_action( 'wp_loaded', array( $this, 'import_existing_podcast_to_podmotor') );

		// Update podmotor_episode_id and audio file values from import process
		add_action( 'wp_loaded', array( $this, 'update_episode_data_from_podmotor') );

		// Register widgets
		add_action( 'widgets_init', array( $this, 'register_widgets' ), 1 );

		// Add shortcodes
		add_action( 'init', array( $this, 'register_shortcodes' ), 1 );

		add_filter( 'feed_content_type', array( $this, 'feed_content_type' ), 10, 2 );

		// Handle localisation
		add_action( 'plugins_loaded', array( $this, 'load_localisation' ) );

		// Load fonts, styles and javascript
		add_action( 'wp_enqueue_scripts', array( $this, 'load_styles_and_scripts' ) );

		// Enqueue HTML5 scripts only if the page has an HTML5 player on it
		add_action( 'wp_print_footer_scripts', array( $this, 'html5_player_conditional_scripts' ) );

		// Add overridable styles to footer
		add_action( 'wp_footer', array( $this, 'ssp_override_player_styles' ) );

		// Apply filters to the style guide so that users may swap out colours of the player
		$this->style_guide = apply_filters( 'ssp_filter_style_guide', $this->style_guide );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
	}

	public function html5_player_conditional_scripts(){
		global $largePlayerInstanceNumber;
		if( (int) $largePlayerInstanceNumber > 0 ){
			echo '<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:400,700&v=' . SSP_VERSION . '" />';
			echo '<link rel="stylesheet" href="' . SSP_PLUGIN_URL . 'assets/css/icon_fonts.css?v=' . SSP_VERSION . '" />';
			echo '<link rel="stylesheet" href="' . SSP_PLUGIN_URL . 'assets/fonts/Gizmo/gizmo.css?v=' . SSP_VERSION . '" />';
			echo '<link rel="stylesheet" href="' . SSP_PLUGIN_URL . 'assets/css/frontend.css?v=' . SSP_VERSION . '" />';
			echo '<script src="//cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/1.4.0/wavesurfer.min.js?v=' . SSP_VERSION . '"></script>';
		}
	}

	public function ssp_override_player_styles(){
		$player_wave_form_progress_colour = get_option( 'ss_podcasting_player_wave_form_progress_colour', false );
		?>
			<style type="text/css">
				.ssp-wave wave wave{
					background: <?php echo $player_wave_form_progress_colour ? $player_wave_form_progress_colour : "#28c0e1"; ?> !important;
				}
			</style>
		<?php
	}


	/**
	 * Enqueue styles and scripts
	 */
	public function load_styles_and_scripts(){
		// @todo load styles and scripts here
	}

	/**
	 * Add episode meta data to the full content
	 * @param  string $content Existing content
	 * @return string          Modified content
	 */
	public function content_meta_data( $content = '' ) {

		global $post, $wp_current_filter, $episode_context;

		// Don't output unformatted data on excerpts
		if ( in_array( 'get_the_excerpt', (array) $wp_current_filter ) ) {
			return $content;
		}

		// Don't output episode meta in shortcode or widget
		if ( isset( $episode_context ) && in_array( $episode_context, array( 'shortcode', 'widget' ) ) ) {
			return $content;
		}

		if( post_password_required( $post->ID ) ) {
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
			
			// Get episode meta data
			$meta = $this->episode_meta( $post->ID, 'content' );

			// Get specified player position
			$player_position = get_option( 'ss_podcasting_player_content_location', 'above' );

			switch( $player_position ) {
				case 'above': $content = $meta . $content; break;
				case 'below': $content = $content . $meta; break;
			}

		}

		return $content;
	}

	/**
	 * Get episode meta data
	 * @param  integer $episode_id ID of episode post
	 * @param  string  $context    Context for display
	 * @return string          	   Episode meta
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
			if( 'shortcode' == $context ) {
				$show_player = false;
			}

			// Allow media player to be dynamically hidden/displayed
			$show_player = apply_filters( 'ssp_show_media_player', $show_player, $context );

			// Show audio player if requested
			$player_style = get_option( 'ss_podcasting_player_style' );
			
			if( $show_player ) {
				$meta .= '<div class="podcast_player">' . $this->media_player( $file, $episode_id, $player_style ) . '</div>';
			}
			
			if ( apply_filters( 'ssp_show_episode_details', true, $episode_id, $context ) ) {
				$meta .= $this->episode_meta_details( $episode_id, $context );
			}
		}

		$meta = apply_filters( 'ssp_episode_meta', $meta, $episode_id, $context );
		return $meta;
	}

	/**
	 * Get episode enclosure
	 * @param  integer $episode_id ID of episode
	 * @return string              URL of enclosure
	 */
	public function get_enclosure( $episode_id = 0 ) {

		if ( $episode_id ) {
			return apply_filters( 'ssp_episode_enclosure', get_post_meta( $episode_id, apply_filters( 'ssp_audio_file_meta_key', 'audio_file' ), true ), $episode_id );
		}

		return '';
	}

	/**
	 * Get download link for episode
	 * @param  integer $episode_id ID of episode
	 * @return string              Episode download link
	 */
	public function get_episode_download_link( $episode_id, $referrer = '' ) {

		// Get file URL
		$file = $this->get_enclosure( $episode_id );

		if ( ! $file ) {
			return;
		}

		// Get download link based on permalink structure
		if ( get_option( 'permalink_structure' ) ) {
			$episode = get_post( $episode_id );
			// Get file extension - default to MP3 to prevent empty extension strings
			$ext = pathinfo( $file, PATHINFO_EXTENSION );
			if ( ! $ext ) {
				$ext = 'mp3';
			}
			$link = $this->home_url . 'podcast-download/' . $episode_id . '/' . $episode->post_name . '.' . $ext;
		} else {
			$link = add_query_arg( array( 'podcast_episode' => $episode_id ), $this->home_url );
		}

		// Allow for dyamic referrer
		$referrer = apply_filters( 'ssp_download_referrer', $referrer, $episode_id );

		// Add referrer flag if supplied
		if ( $referrer ) {
			$link = add_query_arg( array( 'ref' => $referrer ), $link );
		}

		return apply_filters( 'ssp_episode_download_link', esc_url( $link ), $episode_id, $file );
	}
	
	/**
	 * Get Album Art for Player
	 *
	 * Iteratively tries to find the correct album art based on whether the desired image is of square aspect ratio.
	 * Falls back to default album art if it can not find the correct ones.
	 *
	 * @param $episode_id ID of the episode being loaded into the player
	 *
	 * @return array [ $src, $width, $height ]
	 *
	 * @since 1.19.4
	 */
	public function get_album_art( $episode_id = false ) {
		
		/**
		 * In case the episode id is not passed
		 */
		if (!$episode_id){
			return $this->get_no_album_art_image_array();
		}
		
		$image_data_array = array();
		
		/**
		 * Option 1 : if the episode has a featured image that is square, then use that
		 */
		$thumb_id = get_post_thumbnail_id( $episode_id );
		if ( ! empty( $thumb_id ) ) {
			$image_data_array = $this->return_renamed_image_array_keys( wp_get_attachment_image_src( $thumb_id, 'medium' ) );
			if ( $this->check_image_is_square( $image_data_array ) ) {
				return $image_data_array;
			}
		}
		
		/**
		 * Option 2: if the episode belongs to a series, which has an image that is square, then use that
		 */
		$series_id = false;
		$series_image = '';

		$series = get_the_terms( $episode_id, 'series' );
		if ( $series ) {
			$series_id = ( ! empty( $series ) && isset( $series[0] ) ) ? $series[0]->term_id : false;
		}
		if ( $series_id ) {
			$series_image = get_option( "ss_podcasting_data_image_{$series_id}", false );
		}
		if ( $series_image ) {
			$series_image_attachment_id = ssp_get_image_id_from_url( $series_image );
			$image_data_array = $this->return_renamed_image_array_keys( wp_get_attachment_image_src( $series_image_attachment_id, 'medium' ) );
			if ( $this->check_image_is_square( $image_data_array ) ) {
				return $image_data_array;
			}
		}
		
		/**
		 * Option 3: if the feed settings have an image that is square, then use that
		 */
		$feed_image = get_option( 'ss_podcasting_data_image', false );
		if ( $feed_image ) {
			$feed_image_attachment_id = ssp_get_image_id_from_url( $feed_image );
			$image_data_array = $this->return_renamed_image_array_keys( wp_get_attachment_image_src( $feed_image_attachment_id, 'medium' ) );
			if ( $this->check_image_is_square( $image_data_array ) ) {
				return $image_data_array;
			}
		}
		
		/**
		 * None of the above passed, return the no-album-art image
		 */
		return $this->get_no_album_art_image_array();
	}
	
	/**
	 * Convert the array returned from wp_get_attachment_image_src into a human readable version
	 * @todo check if there is a WordPress function for this
	 *
	 * @param $image_data_array
	 *
	 * @return mixed
	 */
	private function return_renamed_image_array_keys($image_data_array){
		if ( $image_data_array && ! empty( $image_data_array ) ) {
			$new_image_data_array['src']    = isset($image_data_array[0]) ? $image_data_array[0] : '' ;
			$new_image_data_array['width']  = isset($image_data_array[1]) ? $image_data_array[1] : '' ;
			$new_image_data_array['height'] = isset($image_data_array[2]) ? $image_data_array[2] : '' ;
		}
		return $new_image_data_array;
	}
	
	/**
	 * Check if the image in the formatted image_data_array is a square image
	 *
	 * @param array $image_data_array
	 *
	 * @return bool
	 */
	private function check_image_is_square( $image_data_array = array() ) {
		if ( isset( $image_data_array['width'] ) && isset( $image_data_array['height'] ) ) {
			if ( ( $image_data_array['width'] / $image_data_array['height'] ) === 1 ) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Returns the no album art image
	 *
	 * @return array
	 */
	private function get_no_album_art_image_array(){
		$src    = SSP_PLUGIN_URL . '/assets/images/no-album-art.png';
		$width  = 300;
		$height = 300;
		
		return compact( 'src', 'width', 'height' );
	}


	/**
	 * Return media player for a given file. Used to enable other checks or to prevent the player from loading
	 * @param string $srcFile
	 * @param int $episode_id
	 * @param string $player_size
	 *
	 * @return string
	 */
	public function media_player( $srcFile = '', $episode_id = 0, $player_size = "large" ) {
		// check if the ss_player shortcode has been used in the episode already
		if ( ! ssp_check_if_podcast_has_shortcode( $episode_id, 'ss_player' ) ) {
			return $this->load_media_player( $srcFile, $episode_id, $player_size );
		}
	}

	/**
	 * Load media player for given file
	 * @param  string  $srcFile        Source of file
	 * @param  integer $episode_id Episode ID for audio file
	 * @param  string $player_size mini or large
	 * @return string              Media player HTML on success, empty string on failure
	 */
	public function load_media_player($srcFile = '', $episode_id = 0, $player_size){
		global $largePlayerInstanceNumber;
		$largePlayerInstanceNumber++;

		$player = '';

		if ( $srcFile ) {

			// Get episode type and default to audio
			$type = $this->get_episode_type( $episode_id );
			if( ! $type ) {
				$type = 'audio';
			}

			// Switch to podcast player URL
			$srcFile = str_replace( 'podcast-download', 'podcast-player', $srcFile );

			// Set up parameters for media player
			$params = array( 'src' => $srcFile, 'preload' => 'none' );

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
						$albumArt = $this->get_album_art( $episode_id );

						$player_background_colour = get_option( 'ss_podcasting_player_background_skin_colour', false );
						$player_wave_form_colour = get_option( 'ss_podcasting_player_wave_form_colour', false );
						$player_wave_form_progress_colour = get_option( 'ss_podcasting_player_wave_form_progress_colour', false );

						$meta = $this->episode_meta_details( $episode_id, '', true );

						ob_start();

						?>
						<div class="ssp-player ssp-player-large" id="ssp_player_id_<?php echo $largePlayerInstanceNumber; ?>"<?php echo $player_background_colour ? ' style="background: ' . $player_background_colour . ';"' : 'background: #333;' ;?>>
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
										<div class="ssp-download-episode" style="overflow: hidden;text-align:right;">
											<?php if( apply_filters( 'ssp_player_show_logo', true ) ) { ?>
												<img class="<?php echo apply_filters( 'ssp_player_logo_class', 'ssp-player-branding' ); ?>" src="<?php echo apply_filters( 'ssp_player_logo_src', SSP_PLUGIN_URL . '/assets/svg/castos_logo_white.svg' ); ?>" width="<?php echo apply_filters( 'ssp_player_logo_width', 68 ); ?>" />
											<?php }; ?>
										</div>
										<div>&nbsp;</div>
										<div class="ssp-media-player">
											<div class="ssp-custom-player-controls">
												<div class="ssp-play-pause" id="ssp-play-pause">
													<span class="ssp-icon ssp-icon-play_icon">&nbsp;</span>
												</div>
												<div class="ssp-wave-form">
													<div class="ssp-inner">
														<div id="waveform<?php echo $largePlayerInstanceNumber; ?>" class="ssp-wave"></div>
													</div>
												</div>

												<div class="ssp-time-volume">

													<div class="ssp-duration">
														<span id="sspPlayedDuration">00:00</span> / <span id="sspTotalDuration"><?php echo $meta['duration']; ?></span>
													</div>

													<div class="ssp-volume">

														<div class="ssp-back-thirty-container">
															<div class="ssp-back-thirty-control" id="ssp-back-thirty">
																<i class="icon icon-replay">&nbsp;</i>
															</div>
														</div>

														<div class="ssp-playback-speed-label-container">
															<div class="ssp-playback-speed-label-wrapper">
																<span id="ssp_playback_speed<?php echo $largePlayerInstanceNumber; ?>" data-ssp-playback-rate="1">1X</span>
															</div>
														</div>

														<div class="ssp-download-container">
															<div class="ssp-download-control">
																<a class="ssp-episode-download" href="<?php echo $this->get_episode_download_link( $episode_id, 'download' ); ?>" target="_blank"><i class="icon icon-cloud-download">&nbsp;</i></a>
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

						<script>

							// @todo _paq variable declaration

							String.prototype.toFormattedDuration = function () {
								var sec_num = parseInt(this, 10); // don't forget the second param
								var hours   = Math.floor(sec_num / 3600);
								var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
								var seconds = sec_num - (hours * 3600) - (minutes * 60);

								if (hours   < 10) {hours   = "0"+hours;}
								if (minutes < 10) {minutes = "0"+minutes;}
								if (seconds < 10) {seconds = "0"+seconds;}
								return hours > 0 ? ( hours+':'+ minutes+':'+seconds) : (minutes+':'+seconds);
							}

							jQuery( document ).ready( function($){

								(function($){

									var sspUpdateDuration<?php echo $largePlayerInstanceNumber; ?>;

									// Create Player
									window.ssp_player<?php echo $largePlayerInstanceNumber; ?> = WaveSurfer.create({
										container: '#waveform<?php echo $largePlayerInstanceNumber; ?>',
										waveColor: '#444',
										progressColor: '<?php echo $player_wave_form_progress_colour ? $player_wave_form_progress_colour : "#28c0e1"; ?>',
										barWidth: 3,
										barHeight: 15,
										height: 2,
										hideScrollbar: true,
										skipLength: 30,
										backend: 'MediaElement'
									});

									//Set player track
									window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.track = '<?php echo $srcFile; ?>';

									/**
									 * Setting and drawing the peaks seems to be required for the 'load on play' functionality to work
									 */
									//Set peaks
									window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.backend.peaks = [0.0218, 0.0183, 0.0165, 0.0198, 0.2137, 0.2888, 0.2313, 0.15, 0.2542, 0.2538, 0.2358, 0.1195, 0.1591, 0.2599, 0.2742, 0.1447, 0.2328, 0.1878, 0.1988, 0.1645, 0.1218, 0.2005, 0.2828, 0.2051, 0.1664, 0.1181, 0.1621, 0.2966, 0.189, 0.246, 0.2445, 0.1621, 0.1618, 0.189, 0.2354, 0.1561, 0.1638, 0.2799, 0.0923, 0.1659, 0.1675, 0.1268, 0.0984, 0.0997, 0.1248, 0.1495, 0.1431, 0.1236, 0.1755, 0.1183, 0.1349, 0.1018, 0.1109, 0.1833, 0.1813, 0.1422, 0.0961, 0.1191, 0.0791, 0.0631, 0.0315, 0.0157, 0.0166, 0.0108];

									//Draw peaks
									window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.drawBuffer();

									//Variable to check if the track is loaded
									window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.loaded = false;

									// @todo Track Player errors

									// On Media Ready
									window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.on( 'ready', function(e){

										if(!window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.loaded) {
											window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.loaded = true;
											window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.play();
										}

										$( '#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> #sspTotalDuration' ).text( window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.getDuration().toString().toFormattedDuration() );
										$( '#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> #sspPlayedDuration' ).text( window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.getCurrentTime().toString().toFormattedDuration() );
									} );

									// On Media Played
									window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.on( 'play', function(e){

										if(!window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.loaded) {
											window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.load(window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.track, window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.backend.peaks);
										}

										// @todo Track Podcast Specific Play

										$( '#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> #ssp-play-pause .ssp-icon' ).removeClass().addClass( 'ssp-icon ssp-icon-pause_icon' );
										$( '#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> #sspPlayedDuration' ).text( window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.getCurrentTime().toString().toFormattedDuration() )

										sspUpdateDuration<?php echo $largePlayerInstanceNumber; ?> = setInterval( function(){
											$( '#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> #sspPlayedDuration' ).text( window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.getCurrentTime().toString().toFormattedDuration() );
										}, 100 );

									} );

									// On Media Paused
									window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.on( 'pause', function(e){

										// @todo Track Podcast Specific Pause

										$( '#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> #ssp-play-pause .ssp-icon' ).removeClass().addClass( 'ssp-icon ssp-icon-play_icon' );

										clearInterval( sspUpdateDuration<?php echo $largePlayerInstanceNumber; ?> );

									} );

									// On Media Finished
									window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.on( 'finish', function(e){

										$( '#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> #ssp-play-pause .ssp-icon' ).removeClass().addClass( 'ssp-icon ssp-icon-play_icon' );

										// @todo Track Podcast Specific Finish

									} );

									$('#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> #ssp-play-pause').on( 'click', function(e){
										window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.playPause();
									} );

									$('#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> #ssp-back-thirty').on( 'click', function(e){

										// @todo Track Podcast Specific Back 30

										window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.skipBackward();

									} );

									$('#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> #ssp_playback_speed<?php echo $largePlayerInstanceNumber; ?>').on( 'click', function(e){
										switch( $( e.currentTarget ).parent().find( '[data-ssp-playback-rate]' ).attr( 'data-ssp-playback-rate' ) ){
											case "1":
												$( e.currentTarget ).parent().find( '[data-ssp-playback-rate]' ).attr( 'data-ssp-playback-rate', '1.5' );
												$( e.currentTarget ).parent().find( '[data-ssp-playback-rate]' ).text('1.5X' );
												window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.setPlaybackRate(1.5);
												break;
											case "1.5":
												$( e.currentTarget ).parent().find( '[data-ssp-playback-rate]' ).attr( 'data-ssp-playback-rate', '2' );
												$( e.currentTarget ).parent().find( '[data-ssp-playback-rate]' ).text('2X' );
												window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.setPlaybackRate(2);
												break;
											case "2":
												$( e.currentTarget ).parent().find( '[data-ssp-playback-rate]' ).attr( 'data-ssp-playback-rate', '1' );
												$( e.currentTarget ).parent().find( '[data-ssp-playback-rate]' ).text('1X' );
												window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.setPlaybackRate(1);
											default:
												break;
										}
									} );

								}(jQuery))

							} );

						</script>

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
			$player = apply_filters( 'ssp_media_player', $player, $srcFile, $episode_id );
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

		// don't render is if the ss_player shortcode is being used.
		if ( ssp_check_if_podcast_has_shortcode( $episode_id, 'ss_player' ) ) {
			return;
		}

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

		$series = get_the_terms( $episode_id, 'series' );
		$episode_series = !empty( $series ) && isset( $series[0] ) ? $series[0]->term_id : false;
		$share_url_array = array();

		if( $itunes_share_url = get_option( 'ss_podcasting_itunes_url_' . $episode_series ) ){
			$share_url_array['iTunes'] = $itunes_share_url;
			//$meta_display .= $meta_sep . '<a href="' . esc_url( $itunes_share_url ) . '" title="' . __( 'View on iTunes', 'seriously-simple-podcasting' ) . '" class="podcast-meta-itunes">' . __( 'iTunes', 'seriously-simple-podcasting' ) . '</a>';
		}

		if( $google_play_share_url = get_option( 'ss_podcasting_google_play_url_' . $episode_series ) ){
			$share_url_array['Google Play'] = $google_play_share_url;
			//$meta_display .= $meta_sep . '<a href="' . esc_url( $google_play_share_url ) . '" title="' . __( 'View on Google Play', 'seriously-simple-podcasting' ) . '" class="podcast-meta-itunes">' . __( 'Google Play', 'seriously-simple-podcasting' ) . '</a>';
		}

		if( $stitcher_share_url = get_option( 'ss_podcasting_stitcher_url_' . $episode_series ) ){
			$share_url_array['Stitcher'] = $stitcher_share_url;
			//$meta_display .= $meta_sep . '<a href="' . esc_url( $stitcher_share_url ) . '" title="' . __( 'View on Stitcher', 'seriously-simple-podcasting' ) . '" class="podcast-meta-itunes">' . __( 'Stitcher', 'seriously-simple-podcasting' ) . '</a>';
		}

		$terms = get_the_terms( $episode_id, 'series' );

		$itunes_url = get_option( 'ss_podcasting_itunes_url', '' );
		$stitcher_url = get_option( 'ss_podcasting_stitcher_url', '' );
		$google_play_url = get_option( 'ss_podcasting_google_play_url', '' );

		if ( is_array( $terms ) ) {
			if ( isset( $terms[0] ) ) {
				if ( false !== get_option( 'ss_podcasting_itunes_url_' . $terms[0]->term_id, '' ) ) {
					$itunes_url = get_option( 'ss_podcasting_itunes_url_' . $terms[0]->term_id, '' );
				}
				if ( false !== get_option( 'ss_podcasting_stitcher_url_' . $terms[0]->term_id, '' ) ) {
					$stitcher_url = get_option( 'ss_podcasting_stitcher_url_' . $terms[0]->term_id, '' );
				}
				if ( false !== get_option( 'ss_podcasting_google_play_url_' . $terms[0]->term_id, '' ) ) {
					$google_play_url = get_option( 'ss_podcasting_google_play_url_' . $terms[0]->term_id, '' );
				}
			}
		}

		$subscribe_array = array(
			'itunes_url' => $itunes_url,
			'stitcher_url' => $stitcher_url,
			'google_play_url' => $google_play_url
		);

		$subscribe_urls = apply_filters( 'ssp_episode_subscribe_details', $subscribe_array, $episode_id, $context );

		foreach( $subscribe_urls as $key => $data ){

			if( !$data ){
				continue;
			}

			if( $subscribe_display ){
				$subscribe_display .= $meta_sep;
			}

			switch( $key ) {

				case 'itunes_url':
					$subscribe_display .= '<a href="' . esc_url( $data ) . '" target="_blank" title="' . apply_filters( 'ssp_subscribe_link_name_itunes', __( 'iTunes', 'seriously-simple-podcasting' ) ) . '" class="podcast-meta-itunes">' . apply_filters( 'ssp_subscribe_link_name_itunes', __( 'iTunes', 'seriously-simple-podcasting' ) ) . '</a>';
				break;

				case 'stitcher_url':
					$subscribe_display .= '<a href="' . esc_url( $data ) . '" target="_blank" title="' . apply_filters( 'ssp_subscribe_link_name_stitcher', __( 'Stitcher', 'seriously-simple-podcasting' ) ) . '" class="podcast-meta-itunes">' . apply_filters( 'ssp_subscribe_link_name_stitcher', __( 'Stitcher', 'seriously-simple-podcasting' ) ) . '</a>';
				break;

				case 'google_play_url':
					$subscribe_display .= '<a href="' . esc_url( $data ) . '" target="_blank" title="' . apply_filters( 'ssp_subscribe_link_name_google_play', __( 'Google Play', 'seriously-simple-podcasting' ) ) . '" class="podcast-meta-itunes">' . apply_filters( 'ssp_subscribe_link_name_google_play', __( 'Google Play', 'seriously-simple-podcasting' ) ) . '</a>';
				break;

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
					$subscribe_display .= wp_kses( $data, $allowed_tags );
				break;

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

		if( post_password_required( $post->ID ) ) {
			return $excerpt;
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

	/**
	 * Get duration of audio file
	 * @param  string $file File name & path
	 * @return mixed        File duration on success, boolean false on failure
	 */
	public function get_file_duration( $file ) {

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
	 * Public facing action which is triggered from Seriously Simple Hosting
	 * Updates episode_id and audio_file data from import process
	 * Expects ssp_podcast_updater, ssp_podcast_api_token form fields
	 * and ssp_podcast_file csv data file
	 */
	public function update_episode_data_from_podmotor() {
		$podcast_updater = ( isset( $_POST['podcast_updater'] ) ? filter_var( $_POST['podcast_updater'], FILTER_SANITIZE_STRING ) : '' );
		if ( ! empty( $podcast_updater ) && 'true' == $podcast_updater ) {
			$reponse = array( 'updated' => 'false' );
			$ssp_podcast_api_token = ( isset( $_POST['ssp_podcast_api_token'] ) ? filter_var( $_POST['ssp_podcast_api_token'], FILTER_SANITIZE_STRING ) : '' );
			$podmotor_api_token    = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
			if ( $ssp_podcast_api_token === $podmotor_api_token ) {
				if ( isset( $_FILES['ssp_podcast_file'] ) ) {
					$episode_data_array = array_map( 'str_getcsv', file( $_FILES['ssp_podcast_file']['tmp_name'] ) );
					foreach ( $episode_data_array as $episode_data ) {
						update_post_meta( $episode_data[0], 'podmotor_episode_id', $episode_data[1] );
						update_post_meta( $episode_data[0], 'audio_file', $episode_data[2] );
					}
					ssp_email_podcasts_imported();
					$reponse['updated'] = 'true';
				}
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

		$widgets = array(
			'recent-episodes' => 'Recent_Episodes',
			'single-episode' => 'Single_Episode',
			'series' => 'Series',
			'playlist' => 'Playlist',
		);

		foreach ( $widgets as $id => $name ) {
			require_once( $this->dir . '/includes/widgets/class-ssp-widget-' . $id . '.php' );
			register_widget( 'SSP_Widget_' . $name );
		}

	}

	/**
	 * Register plugin shortcodes
	 * @return void
	 */
	public function register_shortcodes () {

		$shortcodes = array(
			'podcast_episode',
			'podcast_playlist',
			'ss_podcast',
			'ss_player',
		);

		foreach ( $shortcodes as $shortcode ) {
			require_once( $this->dir . '/includes/shortcodes/class-ssp-shortcode-' . $shortcode . '.php' );
			add_shortcode( $shortcode, array( $GLOBALS['ssp_shortcodes'][ $shortcode ], 'shortcode' ) );
		}

	}

	/**
	 * Show single podcast episode with specified content items
	 * @param  integer $episode_id    ID of episode post
	 * @param  array   $content_items Orderd array of content items to display
	 * @return string                 HTML of episode with specified content items
	 */
	public function podcast_episode ( $episode_id = 0, $content_items = array( 'title', 'player', 'details' ), $context = '', $style = 'mini' ) {

		global $post, $episode_context, $largePlayerInstanceNumber;

		$player_background_colour = get_option( 'ss_podcasting_player_background_skin_colour', false );
		$player_wave_form_colour = get_option( 'ss_podcasting_player_wave_form_colour', false );
		$player_wave_form_progress_colour = get_option( 'ss_podcasting_player_wave_form_progress_colour', false );

		$largePlayerInstanceNumber+= 1;

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
	 *
	 */
	public function load_scripts(){

		wp_register_style( 'ssp-frontend-player', $this->assets_url.'css/player.css', array(), $this->version );
		wp_enqueue_style( 'ssp-frontend-player' );

	}

}

add_action( 'wp_enqueue_scripts', 'ssp_enqueue_wave_surfer' );

function ssp_enqueue_wave_surfer(){
	wp_enqueue_script( 'ssp-wavesurfer', '//cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/1.4.0/wavesurfer.min.js', SSP_VERSION, array( 'jquery' ) );
}

function example_mejs_add_container_class() {
	return;
	if ( ! wp_script_is( 'wp-mediaelement', 'done' ) ) {
		return;
	}
	?>
	<?php //@todo add piwik.js setup ?>
	<script>
		(function() {

			var sspTickerBanner, sspTickerBannerContainer, sspTickerOffset;

			var settings = window._wpmejsSettings || {};
			settings.features = settings.features || mejs.MepDefaults.features;
			settings.features.push( 'addsspclass' );
			settings.features.push( 'addcustomcontrol' );
			settings.features.push( 'addcustomtracking' );

			MediaElementPlayer.prototype.buildaddcustomtracking = function( player, controls, layers, media ) {
				// Play Episode
				jQuery(media).bind( 'play', function(){
					window.sspTrackProgress = setInterval(
						function(){
							//_paq.push(['trackEvent', 'Podcast', 'Play-' + Math.floor( ( media.currentTime / media.duration ) * 100 ) + '%', 'Generic'])
						},
						1000
					);
				} );

				// Pause Episode
				jQuery(media).bind( 'pause', function(){
					//_paq.push(['trackEvent', 'Podcast', 'Pause', 'Generic']);
						clearInterval(window.sspTrackProgress);
				} );

				// End Episode
				jQuery(media).bind( 'ended', function(){
					//_paq.push(['trackEvent', 'Podcast', 'Play-100%', 'Generic']);
					clearInterval(window.sspTrackProgress);
				} );
			};

			MediaElementPlayer.prototype.buildaddsspclass = function( player ) {
				player.container.addClass( 'ssp-mejs-container ssp-dark' );
			};

			MediaElementPlayer.prototype.buildaddcustomcontrol = function( player, controls, layers, media ) {

				var backThirtySeconds = jQuery(
					'<div style="display:inline-block;margin:7px 3px 7px 5px !important;text-align:center;color:#fff;cursor:pointer;width:16px;height:16px;background: url(<?php echo content_url("plugins/seriously-simple-podcasting/assets/svg/ssp_back_30.svg"); ?>) center center no-repeat;background-size: cover;padding: 0;">' +
						'&nbsp;' +
					'</div>'
					).on( 'click', function(e){
					media.currentTime -= 30;
				} );

				var expanCollapseButton = jQuery(
					'<div style="display:inline-block;float:right;margin:7px 7px 5px !important;text-align:center;color:#fff;cursor:pointer;width:17px;height:17px;background: url(<?php echo content_url("plugins/seriously-simple-podcasting/assets/svg/ssp_download.svg"); ?>) center center no-repeat;background-size: cover;padding: 0;">' +
						'&nbsp;' +
					'</div>'
				).on( 'click', function( e ){
				   if( jQuery( '#ssp-expanded-controls' ).is( ':hidden' ) ){
					   //jQuery( e.currentTarget ).css( 'background', 'url(<?php echo content_url("plugins/seriously-simple-podcasting/assets/svg/ssp-expand.svg"); ?>) center center no-repeat' );
					   jQuery( '#ssp-expanded-controls:hidden' ).css( 'display', 'block' );
					   sspTickerBanner = jQuery( '.ssp-ticker-banner' );
					   sspTickerBannerContainer = sspTickerBanner.parent();
					   sspTickerOffset = Math.floor( ( sspTickerBannerContainer.width() - sspTickerBanner.width() ) );

					   var moved = 0;
					   var offset, tickInterval;

					   function doTickBanner(){
						   sspTickerBanner.css( 'left','0' );
						   window.tickInterval = setInterval( function(){
							   moved = moved-10;
							   if( moved <= sspTickerOffset ){
								   sspTickerBanner.css( 'left', sspTickerOffset + 'px' );
								   offset = 0;
								   moved = 0;
								   clearInterval( window.tickInterval );
								   window.tickTimeout = setTimeout( function(){ doTickBanner() }, 2000 );
								   return;
							   }else{
								   offset = moved;
							   }
							   moved--;
							   sspTickerBanner.css( 'left', offset + 'px' );
						   }, 500 );
					   }
					   doTickBanner();
				   }else{
					   //jQuery( e.currentTarget ).css( 'background', 'url(<?php echo content_url("plugins/seriously-simple-podcasting/assets/svg/ssp-collapse.svg"); ?>) center center no-repeat' );
					   jQuery( '#ssp-expanded-controls:visible' ).css( 'display', 'none' );
					   clearInterval( window.tickInterval );
					   clearTimeout( window.tickTimeout );
				   };

				} );

				var playSpeed = jQuery(
					'<div style="display:inline-block;margin:7px 0 7px 3px !important;text-align:center;color:#fff;cursor:pointer;width:16px;height:16px;background: url(<?php echo content_url("plugins/seriously-simple-podcasting/assets/svg/ssp_speed.svg"); ?>) center center no-repeat;background-size: cover;padding: 0;">' +
						'&nbsp;' +
					'</div>' +
					'<div style="display:inline-block;margin:10px 8px 7px 0 !important;text-align:center;color:#fff;cursor:pointer;width:14px;height:14px;">' +
						' <span id="ssp_playback_speed" data-ssp-playback-rate="1" style="display:inline-block;padding:0 3px;margin-right: 2px;">1x</span>' +
					'</div>'
					).on( 'click', function( e ){
						switch( jQuery( '[data-ssp-playback-rate]' ).attr( 'data-ssp-playback-rate' ) ){
							case "1":
								jQuery( '[data-ssp-playback-rate]' ).attr( 'data-ssp-playback-rate', '1.5' );
								jQuery( '[data-ssp-playback-rate]' ).text('1.5x' );
								media.playbackRate = 1.5;
								break;
							case "1.5":
								jQuery( '[data-ssp-playback-rate]' ).attr( 'data-ssp-playback-rate', '2' );
								jQuery( '[data-ssp-playback-rate]' ).text('2x' );
								media.playbackRate = 2.0;
								break;
							case "2":
								jQuery( '[data-ssp-playback-rate]' ).attr( 'data-ssp-playback-rate', '1' );
								jQuery( '[data-ssp-playback-rate]' ).text('1x' );
								media.playbackRate = 1.0;
							default:
								break;
						}
					} );

				jQuery(controls).find('.mejs-duration-container').after( backThirtySeconds, playSpeed );
				jQuery(controls).find('.mejs-horizontal-volume-slider').after( expanCollapseButton );

				// @todo player custom controls
				var sspCustomControls = jQuery('' +
					'<div class="ssp-controls" id="ssp-expanded-controls" style="display:none;">\n' +
'                        <ul class="ssp-sub-controls">\n' +
'                            <li>' +
								'<div style="display:inline-block;margin:0 3px 0 5px !important;text-align:center;color:#fff;cursor:pointer;width:14px;height:14px;background: url(<?php echo content_url("plugins/seriously-simple-podcasting/assets/svg/ssp_back_30.svg"); ?>) center center no-repeat;background-size: cover;padding: 0;">' +
									'&nbsp;' +
								'</div>' +
								'<div style="display:none;margin:0 5px 0 0 !important;text-align:center;color:#fff;cursor:pointer;width:14px;height:14px;">' +
									' <span id="ssp_back_thirty">-30</span>' +
								'</div>' +
							'</li>\n' +
							'<li>' +
								'<div style="display:inline-block;margin::0 3px 0 5px !important;text-align:center;color:#fff;cursor:pointer;width:14px;height:14px;background: url(<?php echo content_url("plugins/seriously-simple-podcasting/assets/svg/ssp_speed.svg"); ?>) center center no-repeat;background-size: cover;padding: 0;">' +
								'&nbsp;' +
								'</div>' +
								'<div style="display:inline-block;margin:0 5px 0 0 !important;text-align:center;color:#fff;cursor:pointer;width:14px;height:14px;">' +
								' <span id="ssp_playback_speed" data-ssp-playback-rate="1" style="display:inline-block;padding-left:3px;">1x</span>' +
								'</div>' +
							'</li>\n' +
'                        </ul>\n' +
'                        <ul class="ssp-ticker">\n' +
'                            <li>\n' +
'                                <div class="ssp-ticker-banner">\n' +
'                                    Some Series, Episode 1 - Dr. Dove & Company Talk Organic\n' +
'                                </div>\n' +
'                            </li>\n' +
'                        </ul>\n' +
'                    </div>');

				player.container.after( sspCustomControls );
			}

		})();
	</script>
	<?php
}
add_action( 'wp_print_footer_scripts', 'example_mejs_add_container_class' );

add_action( 'wp_print_footer_scripts', function(){
	?>

	<style type="text/css">

		.ssp-mejs-container .mejs-time-rail{
			width: 170px !important;
		}

		.ssp-mejs-container .mejs-time-slider{
			width: 160px !important;
		}

		.ssp-controls{
			overflow:hidden;
			padding: 5px 10px;
			background:#333;
			color: #999;
			font-size: 0.75em;
		}

		.ssp-controls ul.ssp-sub-controls{
			list-style:none;
			margin:0;
			padding:0;
			display: inline-block;
			float:left;
			clear:none;
			width: 40%;
			-webkit-box-sizing: border-box;
			-moz-box-sizing: border-box;
			box-sizing: border-box;
		}

		.ssp-controls ul li{
			display: inline-block;
			padding: 3px;
			cursor: pointer;
			border-left: 1px solid #666;
		}
		.ssp-controls ul li:first-child{
			border-left: none;
		}
		.ssp-controls ul li:hover{
			color: #fff;
		}

		ul.ssp-ticker{
			display:inline-block;
			overflow:hidden;
			position: relative;
			width:60%;
			float:right;
			clear:none;
			margin:2px 0 0 0;
			padding:0;
		}

		ul.ssp-ticker li{
			overflow:hidden;
			width: 100%;
		}

		ul.ssp-ticker li .ssp-ticker-banner{
			position: absolute;
			white-space: nowrap;
			overflow: hidden;
			top: 0;
			left: 0;
		}

	</style>

	<?php
} );
