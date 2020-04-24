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
		add_action( 'init', array( $this, 'examples_02_register_block' ) );
	}

	public function examples_02_register_block() {
		wp_register_script(
			'ssp-block-script',
			esc_url( SSP_PLUGIN_URL . 'build/index.js' ),
			$this->asset_file['dependencies'],
			$this->asset_file['version'],
			true
		);

		wp_register_style(
			'ssp-block-editor-style',
			esc_url( SSP_PLUGIN_URL . 'assets/css/block_editor.css' ),
			array( 'wp-edit-blocks' ),
			$this->asset_file['version']
		);

		wp_register_style(
			'ssp-block-frontend-style',
			esc_url( SSP_PLUGIN_URL . 'assets/css/block_style.css' ),
			array(),
			$this->asset_file['version']
		);

		return register_block_type(
			'seriously-simple-podcasting/example-02-stylesheets',
			array(
				'editor_script' => 'ssp-block-script',
				'editor_style'  => 'ssp-block-editor-style',
				'style'         => 'ssp-block-frontend-style',
			)
		);
	}

}
