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
		add_action( 'init', array( $this, 'register_shortcodes' ), 1 );
		
	}
	
	public regsiter_shortcodes(){
		add_shortcode('elementor_html_player', array($this, 'elementor_html_player'));
	}
	
	public function elementor_html_player($atttributes){
		return $this->html_player($atttributes['id'])
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
