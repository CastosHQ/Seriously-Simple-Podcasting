<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Options_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Players_Controller class
 *
 * @author      Danilo Radovic, Sergiy Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.3
 */
class Players_Controller {

	use Useful_Variables;

	/**
	 * @var Renderer
	 * */
	public $renderer;

	/**
	 * @var Options_Handler
	 * */
	public $options_handler;

	/**
	 * @var Episode_Repository
	 * */
	public $episode_repository;

	/**
	 * @param Renderer $renderer
	 * @param Options_Handler $options_handler
	 * @param Episode_Repository $episode_repository
	 */
	public function __construct( $renderer, $options_handler, $episode_repository ) {
		$this->renderer           = $renderer;
		$this->options_handler    = $options_handler;
		$this->episode_repository = $episode_repository;

		$this->init_useful_variables();
		add_action( 'init', array( $this, 'register_player_assets' ) );
	}

	/**
	 * Registers player assets
	 *
	 * @return void
	 */
	public function register_player_assets() {

		wp_register_script(
			'ssp-castos-player',
			esc_url( SSP_PLUGIN_URL . 'assets/js/castos-player'. $this->script_suffix . '.js' ),
			array(),
			$this->version,
			true
		);

		wp_register_style(
			'ssp-castos-player',
			esc_url( SSP_PLUGIN_URL . 'assets/css/castos-player'. $this->script_suffix . '.css' ),
			array(),
			$this->version
		);
	}


	/**
	 * @return Episode_Controller
	 */
	protected function episode_controller(){
		return ssp_episode_controller();
	}


	/**
	 * Todo: move it to Episode_Repository
	 * */
	public function get_ajax_playlist_items() {
		$atts      = json_decode( filter_input( INPUT_GET, 'atts' ), ARRAY_A );
		$page      = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT );
		$nonce     = filter_input( INPUT_GET, 'nonce' );
		$player_id = filter_input( INPUT_GET, 'player_id' );

		if ( ! $atts || ! $page || ! wp_verify_nonce($nonce, 'ssp_castos_player_' . $player_id) ) {
			wp_send_json_error();
		}

		$episode_repository = ssp_episode_controller()->episode_repository;

		$episodes = $episode_repository->get_episodes( array_merge( $atts, compact( 'page' ) ) );
		$items    = array();

		$allowed_keys = array(
			'episode_id',
			'album_art',
			'podcast_title',
			'title',
			'date',
			'duration',
			'excerpt',
			'audio_file'
		);

		foreach ( $episodes as $episode ) {
			$player_data = $this->episode_repository->get_player_data( $episode->ID );
			$items[] = array_intersect_key( $player_data, array_flip( $allowed_keys ) );
		}

		return $items;
	}


	/**
	 * Renders the HTML5 player, based on the attributes sent to the method
	 * If the player assets are registered but not already enqueued, this will enqueue them
	 *
	 * @param int $episode_id Post ID, -1 for current, 0 for latest
	 *
	 * @return string
	 */
	public function render_html_player( $episode_id, $skip_empty_audio = true, $context = 'block' ) {
		$template_data = $this->episode_repository->get_player_data( $episode_id, null, false );
		if ( $skip_empty_audio && ! array_key_exists( 'audio_file', $template_data ) ) {
			return '';
		}

		$this->enqueue_player_assets();

		$player = $this->renderer->fetch( 'players/castos-player', $template_data );

		$meta = '';

		// For podcast_episode shortcode, we only show meta when explicitly specified with details parameter.
		// Example: [podcast_episode episode="123" content="player,details"].
		$no_meta_contexts = array( 'podcast_episode' );

		$show_meta = ! in_array( $context, $no_meta_contexts );
		$show_meta = apply_filters( 'ssp_show_episode_details', $show_meta, $template_data['episode_id'], $context );

		// Adding 'block' context here for future, to distinguish that request is not from the content filter
		if ( $show_meta ) {
			$meta = $this->episode_meta_details( $template_data['episode_id'], $context );
		}

		return $player . $meta;
	}


	/**
	 * Fetch episode meta details
	 * @param  integer $episode_id ID of episode post
	 * @param  string  $context    Context for display
	 * @return string              Episode meta details
	 */
	public function episode_meta_details ( $episode_id = 0, $context = 'content', $return = false ) {

		if ( ! $episode_id ) {
			return '';
		}

		$file = $this->episode_repository->get_enclosure( $episode_id );

		if ( ! $file ) {
			return '';
		}

		$link = $this->episode_repository->get_episode_download_link( $episode_id, 'download' );

		$duration = get_post_meta( $episode_id , 'duration' , true );
		$size = get_post_meta( $episode_id , 'filesize' , true );
		if ( ! $size ) {
			$size_data = $this->episode_repository->get_file_size( $file );
			$size = isset( $size_data['formatted'] ) ? $size_data['formatted'] : 0;
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

		$meta_sep = apply_filters( 'ssp_episode_meta_separator', ' | ' );

		$podcast_display   = $this->get_podcast_display( $meta, $meta_sep );
		$subscribe_display = $this->get_subscribe_display( $episode_id, $context, $meta_sep );
		$meta_display      = $this->get_meta_display( $podcast_display, $subscribe_display, $context );

		return apply_filters( 'ssp_include_player_meta', $meta_display, $episode_id, $context, $meta_sep );
	}


	/**
	 * Renders the Playlist player, based on the attributes sent to the method
	 * If the player assets are registered but not already enqueued, this will enqueue them
	 *
	 * @param $episodes
	 * @param $atts
	 *
	 * @return string
	 */
	public function render_playlist_player( $episodes, $atts ) {
		if ( empty( $episodes ) ) {
			return '';
		}

		// For the case if multiple players are rendered on the same page, we need to generate the player id;
		$player_id = wp_rand();

		wp_localize_script( 'ssp-castos-player', 'ssp_castos_player_' . $player_id, array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'atts'     => $atts,
			'nonce'    => wp_create_nonce( 'ssp_castos_player_' . $player_id ),
		) );
		$this->enqueue_player_assets();

		$template_data = $this->episode_repository->get_player_data( $episodes[0]->ID, get_post() );

		global $wp;
		$template_data['current_url'] = home_url( $wp->request );
		$template_data['player_id']   = $player_id;

		if ( in_array( $atts['style'], array( 'light', 'dark' ) ) ) {
			$template_data['player_mode'] = $atts['style'];
		}

		foreach ( $episodes as $episode ) {
			$template_data['playlist'][] = $this->episode_repository->get_player_data( $episode->ID );
		}

		return $this->renderer->render_deprecated( $template_data, 'players/castos-player' );
	}

	/**
	 * Renders the Playlist player, based on the attributes sent to the method
	 * If the player assets are registered but not already enqueued, this will enqueue them
	 *
	 * @param array $tracks
	 * @param array $atts
	 *
	 * @return string
	 */
	public function render_playlist_compact_player( $tracks, $atts, $width, $height ) {

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '';
		}

		$this->enqueue_player_assets();

		$data = array(
			'type'         => $atts['type'],
			// don't pass strings to JSON, will be truthy in JS
			'tracklist'    => wp_validate_boolean( $atts['tracklist'] ),
			'tracknumbers' => wp_validate_boolean( $atts['tracknumbers'] ),
			'images'       => wp_validate_boolean( $atts['images'] ),
			'artists'      => false,
			'tracks'       => $tracks,
		);

		$safe_type  = esc_attr( $atts['type'] );
		$safe_style = esc_attr( $atts['style'] );

		static $instance = 0;
		$instance ++;

		if ( 1 === $instance ) {
			/* This hook is defined in wp-includes/media.php */
			do_action( 'wp_playlist_scripts', $atts['type'], $atts['style'] );
		}

		return $this->renderer->render_deprecated(
			compact('safe_style', 'safe_type', 'data', 'width', 'height'),
			'players/playlist-compact-player'
		);
	}

	public function enqueue_player_assets() {
		if ( $this->can_enqueue_script( 'ssp-castos-player' ) ) {
			wp_enqueue_script( 'ssp-castos-player' );
		}
		if ( $this->can_enqueue_style( 'ssp-castos-player' ) ) {
			wp_enqueue_style( 'ssp-castos-player' );
		}

		$is_player_custom_colors_enabled = ssp_app()->get_settings_handler()->is_player_custom_colors_enabled();

		if ( $is_player_custom_colors_enabled && ! wp_style_is( 'ssp-dynamic-style', 'enqueued' ) ) {
			$version = ssp_get_option( 'dynamic_style_version', time() );
			$url = wp_upload_dir()['baseurl'] . '/ssp/css/ssp-dynamic-style.css';
			wp_enqueue_style(
				'ssp-dynamic-style',
				esc_url( set_url_scheme( $url ) ),
				array( 'ssp-castos-player' ),
				$version
			);
		}
	}

	/**
	 * Checks if the script is registered and not enqueued yet.
	 *
	 * @param string $handle
	 *
	 * @return bool
	 */
	protected function can_enqueue_script( $handle ){
		return wp_script_is( $handle, 'registered' ) && ! wp_script_is( $handle, 'enqueued' );
	}

	/**
	 * Checks if the style is registered and not enqueued yet.
	 *
	 * @param string $handle
	 *
	 * @return bool
	 */
	protected function can_enqueue_style( $handle ){
		return wp_style_is( $handle, 'registered' ) && ! wp_style_is( $handle, 'enqueued' );
	}


	/**
	 * Renders the Subscribe Buttons, based on the attributes sent to the method
	 *
	 * @param $episode_id
	 *
	 * @return mixed|void
	 */
	public function render_subscribe_buttons( $episode_id ) {
		$subscribe_urls                  = $this->options_handler->get_subscribe_urls( $episode_id, 'subscribe_buttons' );
		$template_data['subscribe_urls'] = $subscribe_urls;

		if ( isset( $template_data['subscribe_urls']['itunes_url'] ) ) {
			$template_data['subscribe_urls']['itunes_url']['label'] = 'Apple Podcast';
			$template_data['subscribe_urls']['itunes_url']['icon']  = 'apple-podcasts.png';
		}

		if ( isset( $template_data['subscribe_urls']['google_play_url'] ) ) {
			$template_data['subscribe_urls']['google_play_url']['label'] = 'Google Podcast';
			$template_data['subscribe_urls']['google_play_url']['icon']  = 'google-podcasts.png';
		}

		$template_data = apply_filters( 'ssp_subscribe_buttons_data', $template_data );

		return $this->renderer->render_deprecated( $template_data, 'players/subscribe-buttons' );
	}

	/**
	 * @param string $src_file
	 * @param int $episode_id
	 * @param string $player_size
	 * @param string $context
	 *
	 * @return mixed|void
	 */
	public function load_media_player( $src_file, $episode_id, $player_size, $context = 'block' ) {
		// Get episode type and default to audio
		$type = $this->episode_repository->get_episode_type( $episode_id );
		if ( ! $type ) {
			$type = 'audio';
		}

		// Switch to podcast player URL
		$src_file = str_replace( 'podcast-download', 'podcast-player', $src_file );

		// Set up parameters for media player
		$params = array( 'src' => $src_file, 'preload' => 'none' );


		/**
		 * If the media file is of type video
		 * @todo is this necessary in the case of the HTML5 player?
		 */
		if ( 'video' === $type ) {
			// Use featured image as video poster
			if ( $episode_id && has_post_thumbnail( $episode_id ) ) {
				$poster = wp_get_attachment_url( get_post_thumbnail_id( $episode_id ) );
				if ( $poster ) {
					$params['poster'] = $poster;
				}
			}
			$player = wp_video_shortcode( $params );
			// Allow filtering so that alternative players can be used
			return apply_filters( 'ssp_media_player', $player, $src_file, $episode_id );
		}

		/**
		 * Check if this player is being loaded via the AMP for WordPress plugin and if so, force the standard player
		 * https://wordpress.org/plugins/amp/
		 */
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			$player_size = 'standard';
		}


		if ( 'standard' === $player_size ) {
			$player = $this->render_media_player( $episode_id, $context );
		} else {
			$player = $this->render_html_player( $episode_id, true, $context );
		}

		// Allow filtering so that alternative players can be used
		return apply_filters( 'ssp_media_player', $player, $src_file, $episode_id );
	}

	public function media_player( $id ) {
		/**
		 * Get the episode (post) object
		 * If the id passed is empty or 0, get_post will return the current post
		 */
		$episode  = get_post( $id );
		$src_file = $this->episode_controller()->get_episode_player_link( $id );
		$params   = array(
			'src'     => $src_file,
			'preload' => 'none',
		);

		$audio_player = wp_audio_shortcode( $params );
		return array(
			'episode'      => $episode,
			'audio_player' => $audio_player,
		);
	}

	/**
	 * Return media player for a given podcast (episode) id.
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function render_media_player( $id, $context = 'block' ) {
		$template_data = $this->media_player( $id );

		$player = $this->renderer->fetch( 'players/media-player', $template_data );

		$meta = '';

		$episode_id = isset( $template_data['episode']->ID ) ? $template_data['episode']->ID : 0;

		// For podcast_episode shortcode, we only show meta when explicitly specified with details parameter.
		// Example: [podcast_episode episode="123" content="player,details"].
		$no_meta_contexts = array( 'podcast_episode' );

		$show_meta = ! in_array( $context, $no_meta_contexts );
		$show_meta = apply_filters( 'ssp_show_episode_details', $show_meta, $episode_id, $context );

		// Adding 'block' context here for future, to distinguish that request is not from the content filter
		if ( $show_meta ) {
			$meta = $this->episode_meta_details( $episode_id, $context );
		}

		return $player . $meta;
	}

	/**
	 * @param array $atts
	 *
	 * @return int[]|\WP_Post[]
	 * @deprecated Use Episode_Repository::get_playlist_episodes()
	 */
	public function get_playlist_episodes( $atts ) {
		return $this->episode_controller()->episode_repository->get_episodes( $atts );
	}

	/**
	 * Get the latest episode ID for a player
	 *
	 * @return int
	 * @deprecated Use Episode_Repository::get_latest_episode_id()
	 */
	public function get_latest_episode_id() {
		return $this->episode_controller()->episode_repository->get_latest_episode_id();
	}

	/**
	 * @param array $meta
	 * @param string $meta_sep
	 *
	 * @return string
	 */
	public function get_podcast_display( $meta, $meta_sep ) {
		$podcast_display = '';

		foreach ( $meta as $key => $data ) {

			if ( ! $data ) {
				continue;
			}

			$sep = $podcast_display ? $meta_sep : '';

			switch ( $key ) {

				case 'link':
					if ( 'on' === get_option( 'ss_podcasting_download_file_enabled', 'on' ) ) {
						$podcast_display .= $sep . '<a href="' . esc_url( $data ) . '" title="' . get_the_title() . ' " class="podcast-meta-download">' . __( 'Download file', 'seriously-simple-podcasting' ) . '</a>';
					}
					break;

				case 'new_window':
					if ( isset( $meta['link'] ) && 'on' === get_option( 'ss_podcasting_play_in_new_window_enabled', 'on' ) ) {
						$play_link       = add_query_arg( 'ref', 'new_window', $meta['link'] );
						$podcast_display .= $sep . '<a href="' . esc_url( $play_link ) . '" target="_blank" title="' . get_the_title() . ' " class="podcast-meta-new-window">' . __( 'Play in new window', 'seriously-simple-podcasting' ) . '</a>';
					}

					break;

				case 'duration':
					if ( 'on' === get_option( 'ss_podcasting_duration_enabled', 'on' ) ) {
						$podcast_display .= $sep . '<span class="podcast-meta-duration">' . __( 'Duration', 'seriously-simple-podcasting' ) . ': ' . $data . '</span>';
					}
					break;

				case 'date_recorded':
					if ( 'on' === get_option( 'ss_podcasting_date_recorded_enabled', 'on' ) ) {
						$podcast_display .= $sep . '<span class="podcast-meta-date">' . __( 'Recorded on', 'seriously-simple-podcasting' ) . ' ' . date_i18n( get_option( 'date_format' ), strtotime( $data ) ) . '</span>';
					}
					break;

				// Allow for custom items to be added, but only allow a small amount of HTML tags
				default:
					$allowed_tags    = array(
						'strong' => array(),
						'b'      => array(),
						'em'     => array(),
						'i'      => array(),
						'a'      => array(
							'href'   => array(),
							'title'  => array(),
							'target' => array(),
						),
						'span'   => array(
							'style' => array(),
						),
					);
					$podcast_display .= $sep . wp_kses( $data, $allowed_tags );
					break;
			}
		}

		return $podcast_display;
	}

	/**
	 * @param int $episode_id
	 * @param string $context
	 * @param string $meta_sep
	 *
	 * @return string
	 */
	public function get_subscribe_display( $episode_id, $context, $meta_sep ){
		$subscribe_display = '';
		if ( 'on' !== get_option( 'ss_podcasting_player_subscribe_urls_enabled', 'on' ) ) {
			return $subscribe_display;
		}
		$options_handler = new Options_Handler();
		$subscribe_urls  = $options_handler->get_subscribe_urls( $episode_id, $context );
		foreach ( $subscribe_urls as $key => $data ) {

			if ( empty( $data['url'] ) ) {
				continue;
			}

			if ( $subscribe_display ) {
				$subscribe_display .= $meta_sep;
			}

			if ( preg_match( '/\b_url\b/', $key ) === false ) {
				$allowed_tags      = array(
					'strong' => array(),
					'b'      => array(),
					'em'     => array(),
					'i'      => array(),
					'a'      => array(
						'href'   => array(),
						'title'  => array(),
						'target' => array(),
					),
				);
				$subscribe_display .= wp_kses( $data['url'], $allowed_tags );
			} else {
				$subscribe_display .= '<a href="' . esc_url( $data['url'] ) . '" target="_blank" title="' . $data['label'] . '" class="podcast-meta-itunes">' . $data['label'] . '</a>';
			}

		}

		return $subscribe_display;
	}

	/**
	 * @param string $podcast_display
	 * @param string $subscribe_display
	 *
	 * @return string
	 */
	public function get_meta_display( $podcast_display, $subscribe_display, $context = '' ) {
		$meta_display = '';

		if ( ! $podcast_display && ! $subscribe_display ) {
			return $meta_display;
		}

		if ( ! empty( $podcast_display ) || ! empty( $subscribe_display ) ) {

			$meta_display .= '<div class="podcast_meta"><aside>';

			$ss_podcasting_player_meta_data_enabled = get_option( 'ss_podcasting_player_meta_data_enabled', 'on' );

			if ( 'on' === $ss_podcasting_player_meta_data_enabled || 'shortcode' === $context ) {
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

		return $meta_display;
	}
}
