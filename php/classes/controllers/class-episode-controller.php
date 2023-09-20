<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;
use WP_Query;

/**
 * SSP Episode Controller
 *
 * @package Seriously Simple Podcasting
 *
 * @deprecated Almost all episode-related functions now in Episode_Repository or Frontend_Controller.
 * So lets just get rid of this class.
 * @todo: move functions to Episode_Repository, rest - to Podcast Post Types Controller
 */
class Episode_Controller {

	use Useful_Variables;

	/**
	 * @var Renderer
	 * */
	public $renderer;

	/**
	 * @var Episode_Repository
	 * */
	public $episode_repository;

	/**
	 * @param Renderer $renderer
	 * @param Episode_Repository $episode_repository
	 */
	public function __construct( $renderer, $episode_repository ) {
		$this->init_useful_variables();

		$this->renderer = $renderer;
		$this->episode_repository = $episode_repository;
	}


	/**
	 * Get episode enclosure
	 *
	 * @param integer $episode_id ID of episode
	 *
	 * @return string              URL of enclosure
	 * @deprecated Use Episode_Repository::get_enclosure()
	 */
	public function get_enclosure( $episode_id = 0 ) {
		return $this->episode_repository->get_enclosure( $episode_id );
	}

	/**
	 * Get download link for episode
	 *
	 * @param $episode_id
	 * @param string $referrer
	 *
	 * @return string
	 * @deprecated Use Episode_Repository::get_episode_download_link()
	 */
	public function get_episode_download_link( $episode_id, $referrer = '' ) {
		return $this->episode_repository->get_episode_download_link( $episode_id, $referrer );
	}

	/**
	 * Get player link for episode.
	 *
	 * @param int $episode_id
	 *
	 * @return string
	 * @deprecated Use Episode_Repository::get_episode_player_link()
	 */
	public function get_episode_player_link( $episode_id ) {
		return $this->episode_repository->get_episode_player_link( $episode_id );
	}

	/**
	 * Get Album Art for Player
	 *
	 * Iteratively tries to find the correct album art based on whether the desired image is of square aspect ratio.
	 * Falls back to default album art if it can not find the correct ones.
	 *
	 * @param int $episode_id ID of the episode being loaded into the player
	 *
	 * @return array [ $src, $width, $height ]
	 *
	 * @since 1.19.4
	 *
	 * @deprecated Please use Episode_Repository::get_album_art()
	 */
	public function get_album_art( $episode_id = false, $size = 'full' ) {
		return $this->episode_repository->get_album_art( $episode_id, $size );
	}

	/**
	 * Get featured image src.
	 *
	 * @param int $episode_id ID of the episode.
	 *
	 * @return array|null [ $src, $width, $height ]
	 *
	 * @since 2.9.9
	 */
	public function get_featured_image_src( $episode_id, $size = 'full' ) {
		$thumb_id = get_post_thumbnail_id( $episode_id );
		if ( empty( $thumb_id ) ) {
			return null;
		}
		return ssp_get_attachment_image_src( $thumb_id, $size );
	}
}
