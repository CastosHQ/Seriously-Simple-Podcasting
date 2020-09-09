<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Renderers\Renderer;

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
class Players_Controller extends Controller {

	public $renderer = null;

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->render = new Renderer();
	}

	public function html_player($id){
		$episode = get_post($id);
		// set any other info
		$templateData = array(
			'episode'    => $episode,
			'additional' => array(
				'feedUrl' => $feedUrl,
			)
		);

		$templateData = apply_filters( 'html_player_data', $templateData );

		$this->render->render($templateData, 'players/html-player');

	}

}
