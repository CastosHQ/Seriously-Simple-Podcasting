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
	 * @return string URL of enclosure
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
	 * @deprecated Please use
	 * @see Episode_Repository::get_album_art()
	 */
	public function get_album_art( $episode_id = false, $size = 'full' ) {
		return $this->episode_repository->get_album_art( $episode_id, $size );
	}
}
