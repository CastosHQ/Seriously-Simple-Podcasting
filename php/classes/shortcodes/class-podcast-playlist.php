<?php

namespace SeriouslySimplePodcasting\ShortCodes;

use SeriouslySimplePodcasting\Controllers\Frontend_Controller;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seriously Simple Podcasting Podcast Playlist Shortcode
 *
 * @author     Hugh Lashbrooke, Serhiy Zakharchenko
 * @package    SeriouslySimplePodcasting
 * @category   SeriouslySimplePodcasting/Shortcodes
 * @since      1.15.0
 */
class Podcast_Playlist implements Shortcode {

	const OUTER = 22; // default padding and border of wrapper
	const DEFAULT_WIDTH = 640;
	const DEFAULT_HEIGHT = 360;

	/**
	 * @var Frontend_Controller;
	 * */
	protected $ss_podcasting;

	/**
	 * @var int
	 * */
	protected $theme_width;

	/**
	 * @var int
	 * */
	protected $theme_height;

	/**
	 * Shortcode function to display podcast playlist (copied and modified from wp-includes/media.php)
	 *
	 * @param array $params Shortcode paramaters
	 *
	 * @return string         HTML output
	 */
	public function shortcode( $params ) {
		$this->prepare_properties();

		$atts     = $this->prepare_atts( $params );
		$episodes = $this->ss_podcasting->players_controller->get_playlist_episodes( $atts );

		if ( empty ( $episodes ) ) {
			return '';
		}

		if ( 'compact' === $atts['player_style'] ) {
			return $this->render_compact_player( $episodes, $atts );
		} else {
			return $this->render_default_player( $episodes, $atts );
		}
	}

	protected function render_default_player( $episodes, $atts ) {
		return $this->ss_podcasting->players_controller->render_playlist_player( $episodes, $atts );
	}


	/**
	 * @param array $episodes
	 * @param array $atts
	 *
	 * @return string
	 */
	protected function render_compact_player( $episodes, $atts ) {
		$tracks = $this->get_tracks( $episodes, $atts );

		return $this->ss_podcasting->players_controller->render_playlist_compact_player( $tracks, $atts, $this->theme_width, $this->theme_height );
	}

	protected function prepare_properties(){
		global $ss_podcasting, $content_width;

		$this->ss_podcasting = $ss_podcasting;
		$this->theme_width   = empty( $content_width ) ? self::DEFAULT_WIDTH : ( $content_width - self::OUTER );
		$this->theme_height  = empty( $content_width ) ? self::DEFAULT_HEIGHT : round( ( self::DEFAULT_HEIGHT * $this->theme_width ) / self::DEFAULT_WIDTH );
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	protected function prepare_atts( $params ) {
		// Get list of episode IDs for display from `episodes` parameter
		if ( ! empty( $params['episodes'] ) ) {
			// 'episodes' is explicitly ordered, unless you specify otherwise.
			if ( empty( $params['orderby'] ) ) {
				$params['orderby'] = 'post__in';
			}
			$params['include'] = $params['episodes'];
		}

		// Parse shortcode attributes
		$atts = shortcode_atts(
			array(
				'type'         => 'audio',
				'series'       => '',
				'order'        => 'ASC',
				'orderby'      => 'menu_order ID',
				'include'      => '',
				'exclude'      => '',
				'style'        => 'light',
				'player_style' => 'standard',
				'tracklist'    => true,
				'tracknumbers' => true,
				'images'       => true,
				'limit'        => 10,
				'page'         => 1,
			),
			$params,
			'podcast_playlist'
		);

		// Included posts must be passed as an array
		if ( $atts['include'] ) {
			$atts['include'] = explode( ',', $atts['include'] );
		}

		// Excluded posts must be passed as an array
		if ( $atts['exclude'] ) {
			$atts['exclude'] = explode( ',', $atts['exclude'] );
		}

		return $atts;
	}

	/**
	 * @param array $atts
	 *
	 * @return array
	 */
	protected function get_tracks( $episodes, $atts ) {
		$tracks = array();
		$is_permalink_structure = get_option( 'permalink_structure' );
		foreach ( $episodes as $episode ) {

			if ( $is_permalink_structure ) {
				$url = $this->ss_podcasting->get_episode_download_link( $episode->ID );
				$url = str_replace( 'podcast-download', 'podcast-player', $url );
			} else {
				$url = $this->ss_podcasting->get_enclosure( $episode->ID );
			}

			// Get episode file type
			$ftype = wp_check_filetype( $url, wp_get_mime_types() );

			if ( $episode->post_excerpt ) {
				$episode_excerpt = $episode->post_excerpt;
			} else {
				$episode_excerpt = $episode->post_title;
			}

			// Setup episode data for media player
			$track = array(
				'src'         => $url,
				'type'        => $ftype['type'],
				'caption'     => $episode->post_title,
				'title'       => $episode_excerpt,
				'description' => $episode->post_content,
				'id'          => $episode->ID,
			);

			// We don't need the ID3 meta data here, but still need to set an empty array
			$track['meta'] = array();

			// Set video dimensions for player
			if ( 'video' === $atts['type'] ) {
				$track['dimensions'] = array(
					'original' => array(
						'width'  => self::DEFAULT_WIDTH,
						'height' => self::DEFAULT_HEIGHT,
					),
					'resized'  => array(
						'width'  => $this->theme_width,
						'height' => $this->theme_height,
					)
				);
			}

			// Get episode image
			if ( $atts['images'] ) {
				$thumb_id = get_post_thumbnail_id( $episode->ID );
				if ( ! empty( $thumb_id ) ) {
					list( $src, $width, $height ) = wp_get_attachment_image_src( $thumb_id, 'full' );
					$track['image'] = compact( 'src', 'width', 'height' );
					list( $src, $width, $height ) = wp_get_attachment_image_src( $thumb_id, 'thumbnail' );
					$track['thumb'] = compact( 'src', 'width', 'height' );
				} else {
					$track['image'] = '';
					$track['thumb'] = '';
				}
			}

			// Allow dynamic filtering of track data
			$track = apply_filters( 'ssp_podcast_playlist_track_data', $track, $episode );

			$tracks[] = $track;
		}

		return $tracks;
	}
}

