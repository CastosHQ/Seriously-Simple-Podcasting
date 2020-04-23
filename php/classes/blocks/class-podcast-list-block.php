<?php
namespace SeriouslySimplePodcasting\Blocks;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Controllers\Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @todo this will probably change to a blocks only class, just to load the js
 *
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Blocks
 * @since       2.0.4
 */
class Podcast_List_Block extends Controller {

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->bootstrap();
	}

	protected function bootstrap() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'podcast_block_enqueue_scripts' ) );
		add_action( 'enqueue_block_assets', array( $this, 'podcast_block_enqueue_styles' ) );
	}

	public function podcast_block_enqueue_scripts() {
		wp_register_script(
			'podcast-list-block-script',
			esc_url( $this->assets_url . 'blocks/podcast_list' . $this->script_suffix . '.js' ),
			array( 'wp-blocks' ),
			$this->version,
			true
		);
		wp_enqueue_script( 'podcast-list-block-script' );
	}

	public function podcast_block_enqueue_styles() {
		wp_register_style(
			'podcast-list-block-style',
			esc_url( $this->assets_url . 'blocks/podcast_list' . $this->script_suffix . '.css' ),
			array(),
			$this->version
		);
		wp_enqueue_style( 'podcast-list-block-style' );
	}
}
