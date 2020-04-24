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
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Blocks
 * @since       2.0.4
 */
class Block extends Controller {

	protected $asset_file;

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->bootstrap();
	}

	protected function bootstrap() {
		$this->asset_file = include SSP_PLUGIN_PATH . '/build/index.asset.php';
		add_action( 'init', array( $this, 'examples_01_register_block' ) );
		//add_action( 'enqueue_block_editor_assets', array( $this, 'block_enqueue_scripts' ) );
		//add_action( 'enqueue_block_assets', 'block_enqueue_styles' );
	}

	public function block_enqueue_scripts() {
		wp_register_script(
			'ssp-block-script',
			esc_url( SSP_PLUGIN_URL . 'build/index.js' ),
			$this->asset_file['dependencies'],
			$this->asset_file['version'],
			true
		);
		wp_enqueue_script( 'ssp-block-script' );
	}

	public function block_enqueue_styles() {
		//
	}

	public function examples_01_register_block() {
		wp_register_script(
			'ssp-block-script',
			esc_url( SSP_PLUGIN_URL . 'build/index.js' ),
			$this->asset_file['dependencies'],
			$this->asset_file['version'],
			true
		);

		register_block_type(
			'seriously-simple-podcasting/example-01-basic-esnext',
			array(
				'editor_script' => 'ssp-block-script',
			)
		);
	}

}
