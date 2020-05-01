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
class Player_Block extends Controller {

	protected $asset_file;

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->bootstrap();
	}

	protected function bootstrap() {
		$this->asset_file = include SSP_PLUGIN_PATH . '/build/index.asset.php';
		add_action( 'init', array( $this, 'register_castos_player_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_player_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_player_assets' ) );
	}

	/**
	 * Registers the Castos Player Block
	 *
	 * @return false|\WP_Block_Type
	 */
	public function register_castos_player_block() {
		wp_register_script(
			'ssp-block-script',
			esc_url( SSP_PLUGIN_URL . 'build/index.js' ),
			$this->asset_file['dependencies'],
			$this->asset_file['version'],
			true
		);

		return register_block_type(
			'seriously-simple-podcasting/castos-player',
			array(
				'editor_script' => 'ssp-block-script',
			)
		);
	}

	/**
	 * Enqueues the assets needed for the player to work.
	 */
	public function enqueue_player_assets() {
		wp_register_script(
			'ssp-block-wavesurfer',
			'https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/1.4.0/wavesurfer.min.js',
			array(),
			$this->asset_file['version'],
			true
		);

		wp_register_script(
			'ssp-block-html5-player',
			esc_url( SSP_PLUGIN_URL . 'assets/js/html5.player.js' ),
			array( 'jquery' ),
			$this->asset_file['version'],
			true
		);

		wp_register_style(
			'ssp-block-style',
			esc_url( SSP_PLUGIN_URL . 'assets/css/block_style.css' ),
			array(),
			$this->asset_file['version']
		);
		wp_register_style(
			'ssp-block-fonts-style',
			esc_url( SSP_PLUGIN_URL . 'assets/css/icon_fonts.css' ),
			array(),
			$this->asset_file['version']
		);
		wp_register_style(
			'ssp-block-gizmo-fonts-style',
			esc_url( SSP_PLUGIN_URL . 'assets/fonts/Gizmo/gizmo.css' ),
			array(),
			$this->asset_file['version']
		);

		wp_enqueue_script( 'ssp-block-wavesurfer' );
		wp_enqueue_script( 'ssp-block-html5-player' );

		wp_enqueue_style( 'ssp-block-style' );
		wp_enqueue_style( 'ssp-block-fonts-style' );
		wp_enqueue_style( 'ssp-block-gizmo-fonts-style' );
	}
}
