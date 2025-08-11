<?php
/**
 * Episode controller class file.
 *
 * @package Seriously Simple Podcasting
 */

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;
use WP_Query;

/**
 * Episode Controller
 *
 * Legacy controller for episode operations.
 *
 * @package Seriously Simple Podcasting
 *
 * @deprecated Almost all episode-related functions now in Episode_Repository or Frontend_Controller.
 * So lets just get rid of this class.
 */
class Episode_Controller extends Episode_Repository {

	use Useful_Variables;

	/**
	 * Renderer instance.
	 *
	 * @var Renderer
	 */
	public $renderer;

	/**
	 * Episode repository instance.
	 *
	 * @var Episode_Repository
	 */
	public $episode_repository;

	/**
	 * Episode_Controller constructor.
	 *
	 * @param Renderer           $renderer           Renderer instance for rendering views.
	 * @param Episode_Repository $episode_repository Repository for episode data operations.
	 */
	public function __construct( $renderer, $episode_repository ) {
		$this->init_useful_variables();

		$this->renderer           = $renderer;
		$this->episode_repository = $episode_repository;
	}


	/**
	 * Get episode enclosure.
	 *
	 * @param int $episode_id ID of episode.
	 *
	 * @return string URL of enclosure.
	 * @deprecated Use Episode_Repository::get_enclosure()
	 */
	public function get_enclosure( $episode_id = 0 ) {
		return $this->episode_repository->get_enclosure( $episode_id );
	}

	/**
	 * Get download link for episode.
	 *
	 * @param int    $episode_id Episode ID.
	 * @param string $referrer   Referrer URL.
	 *
	 * @return string Download link URL.
	 * @deprecated Use Episode_Repository::get_episode_download_link()
	 */
	public function get_episode_download_link( $episode_id, $referrer = '' ) {
		return $this->episode_repository->get_episode_download_link( $episode_id, $referrer );
	}
}
