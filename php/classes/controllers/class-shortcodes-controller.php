<?php
/**
 * Shortcodes controller class file.
 *
 * @package Seriously Simple Podcasting
 */

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\ShortCodes\Player;
use SeriouslySimplePodcasting\ShortCodes\Podcast;
use SeriouslySimplePodcasting\ShortCodes\Podcast_Episode;
use SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist;
use SeriouslySimplePodcasting\ShortCodes\Podcast_List;
use SeriouslySimplePodcasting\ShortCodes\Episode_List;
use SeriouslySimplePodcasting\Presenters\Episode_List_Presenter;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes Controller
 *
 * @author      Serhiy Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.10.0
 */
class Shortcodes_Controller extends Controller {

	/**
	 * @var Episode_List_Presenter
	 */
	private $episode_list_presenter;

	/**
	 * Constructor
	 *
	 * @param string                 $file                   Plugin base file.
	 * @param string                 $version                Plugin version number.
	 * @param Episode_List_Presenter $episode_list_presenter Episode list presenter instance.
	 */
	public function __construct( $file, $version, $episode_list_presenter ) {
		parent::__construct( $file, $version );

		$this->episode_list_presenter = $episode_list_presenter;

		$this->register_hooks_and_filters();
	}

	/**
	 * Register all relevant front end hooks and filters
	 */
	public function register_hooks_and_filters() {
		// Add shortcodes.
		add_action( 'init', array( $this, 'register_shortcodes' ), 1 );
	}

	/**
	 * Register plugin shortcodes
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'ss_player', array( new Player(), 'shortcode' ) );
		add_shortcode( 'ss_podcast', array( new Podcast(), 'shortcode' ) );
		add_shortcode( 'podcast_episode', array( new Podcast_Episode(), 'shortcode' ) );
		add_shortcode( 'podcast_playlist', array( new Podcast_Playlist(), 'shortcode' ) );
		add_shortcode( 'ssp_podcasts', array( new Podcast_List(), 'shortcode' ) );
		add_shortcode( 'ssp_episode_list', array( new Episode_List( $this->episode_list_presenter ), 'shortcode' ) );
	}
}
