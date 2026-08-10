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
	 * Episode list presenter instance.
	 *
	 * @var Episode_List_Presenter
	 */
	protected $episode_list_presenter;

	/**
	 * Castos_Blocks constructor.
	 *
	 * @param Admin_Notifications_Handler $admin_notices_handler Admin notifications handler instance.
	 * @param Episode_List_Presenter       $episode_list_presenter Episode list presenter instance.
	 */
	public function __construct( $admin_notices_handler, $episode_list_presenter ) {
		$this->admin_notices_handler = $admin_notices_handler;
		$this->episode_list_presenter = $episode_list_presenter;

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
		( new Episode_List_Block( $this->episode_list_presenter ) )->register();
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
				// The editor fetches podcasts itself, so registration stays free of term queries.
				// It needs the route to fetch them from; the labels arrive ready to display.
				'seriesRestRoute' => $this->get_series_rest_route(),
			)
		);

		// The editor builds the podcast and tag option labels itself now, so its strings need
		// translations on the JS side too.
		wp_set_script_translations( 'ssp-block-script', 'seriously-simple-podcasting' );

		wp_register_style(
			'ssp-block-style',
			esc_url( SSP_PLUGIN_URL . 'assets/css/block-editor-styles.css' ),
			array(),
			$this->asset_file['version']
		);
	}

	/**
	 * Resolves the full REST route the editor uses to fetch podcasts.
	 *
	 * The series taxonomy name and its registration args are both filterable, so the namespace and
	 * base are read back from the registered taxonomy rather than assumed. Sending the whole route
	 * keeps the editor from having to reassemble it.
	 *
	 * @return string
	 */
	protected function get_series_rest_route() {
		$taxonomy  = get_taxonomy( ssp_series_taxonomy() );
		$namespace = $taxonomy && ! empty( $taxonomy->rest_namespace ) ? $taxonomy->rest_namespace : 'wp/v2';
		$base      = $taxonomy && ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : ssp_series_taxonomy();

		return sprintf( '/%s/%s', $namespace, $base );
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
