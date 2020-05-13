<?php

namespace SeriouslySimplePodcasting\Player;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Media_Player {

	public $style_guide = array(
		'dark'     => '#3A3A3A',
		'medium'   => '#666666',
		'light'    => '#939393',
		'lightest' => '#f9f9f9',
		'accent'   => '#ea5451',
	);

	public function __construct() {
		// @todo all of this should only be triggered if there is a player to be rendered on the page

		global $large_player_instance_number;
		$large_player_instance_number = 0;

		// Apply filters to the style guide so that users may swap out colours of the player
		$this->style_guide = apply_filters( 'ssp_filter_style_guide', $this->style_guide );

		$this->register_hooks_and_filters();
	}

	public function register_hooks_and_filters(){
		$player_style = get_option( 'ss_podcasting_player_style', 'standard' );
		if ( 'standard' === $player_style ) {
			return;
		}

		// Load fonts, styles and javascript
		add_action( 'wp_enqueue_scripts', array( $this, 'load_styles_and_scripts' ) );

		// Enqueue HTML5 scripts only if the page has an HTML5 player on it
		add_action( 'wp_print_footer_scripts', array( $this, 'html5_player_conditional_scripts' ) );

		// Enqueue HTML5 styles only if the page has an HTML5 player on it
		add_action( 'wp_print_footer_scripts', array( $this, 'html5_player_styles' ) );

		// Add overridable styles to footer
		add_action( 'wp_footer', array( $this, 'ssp_override_player_styles' ) );

	}

	public function load_styles_and_scripts() {
		$this->register_media_player_styles();
		$this->register_media_player_scripts();
	}

	public function register_media_player_styles(){
		wp_register_style( 'ssp-frontend-player', $this->assets_url . 'css/player.css', array(), $this->version );
		wp_enqueue_style( 'ssp-frontend-player' );
	}

	public function register_media_player_scripts(){
		wp_register_script( 'media-player', $this->assets_url . 'js/media.player.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'media-player' );

		wp_register_script( 'html5-player', $this->assets_url . 'js/html5.player.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'html5-player' );
	}

	/**
	 * HTML5 player additional scripts
	 * @todo enqueue these correctly
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
		wp_register_style( 'ssp-html5-player', $this->assets_url . 'css/html5.player.css', array(), $this->version );
		wp_enqueue_style( 'ssp-html5-player' );
	}

	/**
	 * Override player styles
	 * @todo what is this used for, it looks like this is applying the settings
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
	 * Load audio player for given file - wrapper for `media_player` method to maintain backwards compatibility
	 * @param  string  $src 	   Source of audio file
	 * @param  integer $episode_id Episode ID for audio empty string
	 * @return string        	   Audio player HTML on success, false on failure
	 *
	 * @todo check for usage of this method elsewhere
	 *
	 */
	public function audio_player( $src = '', $episode_id = 0 ) {
		$player = $this->media_player( $src, $episode_id );
		return apply_filters( 'ssp_audio_player', $player, $src, $episode_id );
	}

	/**
	 * Return media player for a given file. Used to enable other checks or to prevent the player from loading
	 * @param string $src_file
	 * @param int $episode_id
	 * @param string $player_size
	 *
	 * @return string
	 *
	 * @todo check for usage of this method elsewhere
	 *
	 */
	public function media_player( $src_file = '', $episode_id = 0, $player_size = "large" ) {
		// check if the ss_player shortcode has been used in the episode already
		if ( ! ssp_check_if_podcast_has_shortcode( $episode_id, 'ss_player' ) ) {
			return $this->load_media_player( $src_file, $episode_id, $player_size );
		}
	}

	/**
	 * Load media player for given file
	 * @param  string  $src_file        Source of file
	 * @param  integer $episode_id Episode ID for audio file
	 * @param  string $player_size mini or large
	 * @return string              Media player HTML on success, empty string on failure
	 *
	 * @todo check for usage of this method elsewhere
	 *
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
										<div>&nbsp</div>
										<div class="ssp-media-player">
											<div class="ssp-custom-player-controls">
												<div class="ssp-play-pause" id="ssp-play-pause">
													<span class="ssp-icon ssp-icon-play_icon">&nbsp;</span>
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
																<i class="ssp-icon icon-replay">&nbsp;</i>
															</div>
														</div>
														<div class="ssp-playback-speed-label-container">
															<div class="ssp-playback-speed-label-wrapper">
																<span data-playback-speed-id="ssp_playback_speed_<?php echo $large_player_instance_number; ?>" id="ssp_playback_speed<?php echo $large_player_instance_number; ?>" data-ssp-playback-rate="1">1X</span>
															</div>
														</div>
														<div class="ssp-download-container">
															<div class="ssp-download-control">
																<a class="ssp-episode-download" href="<?php echo $this->get_episode_download_link( $episode_id, 'download' ); ?>" target="_blank"><i class="ssp-icon icon-cloud-download">&nbsp;</i></a>
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


}
