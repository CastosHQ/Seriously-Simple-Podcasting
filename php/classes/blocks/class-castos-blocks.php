<?php

/**
 * @todo
 * Get the number of episodes from the Reading Settings
 */

namespace SeriouslySimplePodcasting\Blocks;

use SeriouslySimplePodcasting\Controllers\Controller;
use SeriouslySimplePodcasting\Player\Media_Player;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blocks class, used to load blocks and any relevant block assets
 *
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Blocks
 * @since       2.0.4
 */
class Castos_Blocks extends Controller {

	/**
	 * @var Blocks asset file
	 */
	protected $asset_file;

	/**
	 * Castos_Blocks constructor.
	 *
	 * @param $file
	 * @param $version
	 */
	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->bootstrap();
	}

	/**
	 * Dynamic Podcast List Block callback
	 *
	 * @param $attributes
	 *
	 * @return string
	 */
	public function podcast_list_render_callback($attributes) {
		global $ss_podcasting;
		return $ss_podcasting->render_podcast_list_dynamic_block($attributes);
	}

	/**
	 * Loads the asset file and the block registration
	 */
	protected function bootstrap() {
		$this->asset_file = include SSP_PLUGIN_PATH . 'build/index.asset.php';
		add_action( 'init', array( $this, 'register_castos_blocks' ) );
	}

	/**
	 * Registers the Castos Player Block
	 *
	 * @return void
	 */
	public function register_castos_blocks() {

		$script_asset_path = SSP_PLUGIN_PATH . 'build/index.asset.php';
		if ( ! file_exists( $script_asset_path ) ) {
			// @todo make this an admin notice and exit gracefully
			throw new Error(
				'An error has occurred in loading the block assets. Please report this to the plugin developer.'
			);
		}

		wp_register_script(
			'ssp-block-script',
			esc_url( SSP_PLUGIN_URL . 'build/index.js' ),
			$this->asset_file['dependencies'],
			$this->asset_file['version'],
			true
		);

		wp_register_style(
			'ssp-castos-player',
			esc_url( SSP_PLUGIN_URL . 'assets/css/castos-player.css' ),
			array(),
			$this->asset_file['version']
		);

		/*register_block_type( 'seriously-simple-podcasting/block-name',
			array(
				'editor_script' => '',
				'editor_style'  => '',
				'script'        => '',
				'style'         => '',
			)
		);*/

		register_block_type(
			'seriously-simple-podcasting/castos-player',
			array(
				'editor_script' => 'ssp-block-script',
				'editor_style'  => 'ssp-castos-player',
				'style'  => 'ssp-castos-player',
			)
		);

		register_block_type(
			'seriously-simple-podcasting/audio-player',
			array(
				'editor_script' => 'ssp-block-script',
			)
		);

		register_block_type( 'seriously-simple-podcasting/podcast-list',
			array(
				'editor_script'   => 'ssp-block-script',
				'render_callback' => array( $this, 'podcast_list_render_callback' )
			)
		);
	}
}
