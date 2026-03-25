<?php
/**
 * Castos Blocks Integration
 *
 * Handles Gutenberg blocks integration for Seriously Simple Podcasting.
 *
 * @package Seriously Simple Podcasting
 * @since 2.0.4
 */

namespace SeriouslySimplePodcasting\Integrations\Blocks;

use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Presenters\Episode_List_Presenter;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blocks coordinator — registers shared editor assets and delegates
 * block registration to individual block classes.
 *
 * @since 2.0.4
 */
class Castos_Blocks {

	/**
	 * Asset file array.
	 *
	 * @var array
	 */
	protected $asset_file;

	/**
	 * Admin notifications handler instance.
	 *
	 * @var Admin_Notifications_Handler
	 */
	protected $admin_notices_handler;

	/**
	 * Episode list renderer instance.
	 *
	 * @var Episode_List_Presenter
	 */
	protected $episode_list_renderer;

	/**
	 * Castos_Blocks constructor.
	 *
	 * @param Admin_Notifications_Handler $admin_notices_handler Admin notifications handler instance.
	 * @param Episode_List_Presenter       $episode_list_renderer Episode list renderer instance.
	 */
	public function __construct( $admin_notices_handler, $episode_list_renderer ) {
		$this->admin_notices_handler = $admin_notices_handler;
		$this->episode_list_renderer = $episode_list_renderer;

		if ( ! file_exists( SSP_PLUGIN_PATH . 'build/index.asset.php' ) ) {
			if ( is_admin() ) {
				add_action( 'admin_notices', array( $this->admin_notices_handler, 'blocks_error_notice' ) );
			}

			return;
		}
		$this->asset_file = include SSP_PLUGIN_PATH . 'build/index.asset.php';

		// Our custom post types and taxonomies are registered on 11. Let's register blocks after that on 12.
		add_action( 'init', array( $this, 'register_castos_blocks' ), 12 );
	}

	/**
	 * Registers shared editor assets, deprecated block stubs, and active blocks.
	 *
	 * @return void
	 */
	public function register_castos_blocks() {
		$this->register_shared_assets();
		$this->register_deprecated_blocks();

		( new Castos_Html_Player_Block() )->register();
		( new Podcast_List_Block( $this->episode_list_renderer ) )->register();
		( new Playlist_Player_Block() )->register();
		( new Ssp_Podcasts_Block() )->register();
	}

	/**
	 * Registers shared editor script and styles used by all blocks.
	 *
	 * @return void
	 */
	protected function register_shared_assets() {
		$dependencies = $this->asset_file['dependencies'];

		// Dependency wp-edit-post is needed only for PostPublishPanel block, and it leads to a warning on widgets page.
		// So, we can safely remove it since it's automatically included on post edit pages.
		$dependencies = array_diff( $dependencies, array( 'wp-edit-post' ) );

		wp_register_script(
			'ssp-block-script',
			esc_url( SSP_PLUGIN_URL . 'build/index.js' ),
			$dependencies,
			$this->asset_file['version'],
			true
		);

		$itunes_enabled = ssp_get_option( 'itunes_fields_enabled', 'on' ) === 'on';

		wp_localize_script(
			'ssp-block-script',
			'sspAdmin',
			array(
				'sspPostTypes'    => ssp_post_types( true, false ),
				'isCastosUser'    => ssp_is_connected_to_castos(),
				'isItunesEnabled' => $itunes_enabled,
			)
		);

		wp_register_style(
			'ssp-block-style',
			esc_url( SSP_PLUGIN_URL . 'assets/css/block-editor-styles.css' ),
			array(),
			$this->asset_file['version']
		);
	}

	/**
	 * Registers deprecated blocks for backward compatibility with existing posts.
	 *
	 * @return void
	 */
	protected function register_deprecated_blocks() {
		/**
		 * @deprecated Use 'seriously-simple-podcasting/castos-html-player' instead.
		 *             Kept registered for backward compatibility with existing posts.
		 */
		register_block_type(
			'seriously-simple-podcasting/castos-player',
			array(
				'editor_script' => 'ssp-block-script',
				'editor_style'  => 'ssp-castos-player',
			)
		);

		/**
		 * @deprecated Use 'seriously-simple-podcasting/castos-html-player' instead.
		 *             Kept registered for backward compatibility with existing posts.
		 */
		register_block_type(
			'seriously-simple-podcasting/audio-player',
			array(
				'editor_script' => 'ssp-block-script',
			)
		);
	}
}
