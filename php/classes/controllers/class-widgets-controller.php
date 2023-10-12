<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Widgets\Playlist;
use SeriouslySimplePodcasting\Widgets\Series;
use SeriouslySimplePodcasting\Widgets\Recent_Episodes;
use SeriouslySimplePodcasting\Widgets\Single_Episode;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widgets Controller
 *
 * @author      Serhiy Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.10.0
 */
class Widgets_Controller extends Controller {

	/**
	 * Constructor
	 *
	 * @param string $file Plugin base file.
	 * @param string $version Plugin version number
	 */
	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );

		$this->register_hooks_and_filters();
	}

	/**
	 * Register all relevant front end hooks and filters
	 */
	public function register_hooks_and_filters() {
		// Register widgets
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register plugin widgets
	 * @return void
	 */
	public function register_widgets() {
		register_widget( new Playlist() );
		register_widget( new Series() );
		register_widget( new Single_Episode() );
		register_widget( new Recent_Episodes() );
	}
}
