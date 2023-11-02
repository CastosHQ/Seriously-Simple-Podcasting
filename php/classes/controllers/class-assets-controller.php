<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Traits\URL_Helper;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Assets_Controller
 *
 * @package Seriously Simple Podcasting
 */
class Assets_Controller {

	use Useful_Variables;
	use URL_Helper;


	public function __construct() {
		$this->init_useful_variables();

		// Admin JS & CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Register HTML5 player scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'register_html5_player_assets' ) );
	}

	/**
	 * Used to load the HTML5 player scripts and styles
	 * Only load this if the HTML5 player is enabled in the plugin
	 * Additionally, if we're rendering a post or page which includes a player block, enqueue the player assets
	 */
	public function register_html5_player_assets() {
		/**
		 * If we're rendering a SSP Block, which includes the HTML5 player, also enqueue the player scripts
		 */
		if ( has_block( 'seriously-simple-podcasting/castos-player' ) || has_block( 'seriously-simple-podcasting/podcast-list' ) ) {
			wp_enqueue_script( 'ssp-castos-player' );
			wp_enqueue_style( 'ssp-castos-player' );
		}
	}


	/**
	 * Load admin CSS
	 * @return void
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( ! $this->need_admin_scripts( $hook ) ) {
			return;
		}

		wp_register_style( 'ssp-admin', esc_url( $this->assets_url . 'admin/css/admin' . $this->script_suffix . '.css' ), array(), $this->version );
		wp_enqueue_style( 'ssp-admin' );

		// Datepicker
		wp_register_style( 'jquery-ui-datepicker-wp', esc_url( $this->assets_url . 'css/datepicker' . $this->script_suffix . '.css' ), array(), $this->version );
		wp_enqueue_style( 'jquery-ui-datepicker-wp' );

		wp_register_style( 'ssp-select2-css', esc_url( $this->assets_url . 'css/select2' . $this->script_suffix . '.css' ), array(), $this->version );
		wp_enqueue_style( 'ssp-select2-css' );

		/**
		 * Only load the peekabar styles when adding/editing podcasts
		 */
		if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
			global $post;
			if ( in_array( $post->post_type, ssp_post_types() ) ) {
				wp_register_style( 'jquery-peekabar', esc_url( $this->assets_url . 'css/jquery-peekabar' . $this->script_suffix . '.css' ), array(), $this->version );
				wp_enqueue_style( 'jquery-peekabar' );
			}
		}

		/**
		 * Only load the jquery-ui CSS when the import settings screen is loaded
		 * @todo load this locally perhaps? and only the progress bar stuff?
		 */
		if ( 'podcast_page_podcast_settings' === $hook && isset( $_GET['tab'] ) && 'import' == $_GET['tab'] ) {
			//wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', array(), $this->version  );

			wp_register_style( 'jquery-ui-smoothness', esc_url( $this->assets_url . 'css/jquery-ui-smoothness' . $this->script_suffix . '.css' ), array(), $this->version );
			wp_enqueue_style( 'jquery-ui-smoothness' );

			wp_register_style( 'import-rss', esc_url( $this->assets_url . 'css/import-rss' . $this->script_suffix . '.css' ), array(), $this->version );
			wp_enqueue_style( 'import-rss' );

		}
	}


	/**
	 * Load admin JS
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {

		if ( ! $this->need_admin_scripts( $hook ) ) {
			return;
		}

		wp_register_script( 'ssp-admin', esc_url( $this->assets_url . 'js/admin' . $this->script_suffix . '.js' ), array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-datepicker'
		), $this->version );
		wp_enqueue_script( 'ssp-admin' );

		wp_register_script( 'ssp-settings', esc_url( $this->assets_url . 'js/settings' . $this->script_suffix . '.js' ), array( 'jquery' ), $this->version );
		wp_enqueue_script( 'ssp-settings' );

		wp_register_script( 'ssp-select2-js', esc_url( $this->assets_url . 'js/select2' . $this->script_suffix . '.js' ), array( 'jquery' ), $this->version );
		wp_enqueue_script( 'ssp-select2-js' );

		// Only enqueue the WordPress Media Library picker for adding and editing SSP tags/terms post types.
		if ( 'edit-tags.php' === $hook || 'term.php' === $hook ) {
			if ( 'series' === $_REQUEST['taxonomy'] ) {
				wp_enqueue_media();
			}
		}

		/**
		 * Only load the upload scripts when adding/editing posts/podcasts
		 */
		if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
			global $post;
			if ( in_array( $post->post_type, ssp_post_types() ) ) {
				wp_enqueue_script( 'plupload-all' );
				$upload_credentials = ssp_setup_upload_credentials();
				wp_register_script( 'ssp-fileupload', esc_url( $this->assets_url . 'js/fileupload' . $this->script_suffix . '.js' ), array(), $this->version );
				wp_localize_script( 'ssp-fileupload', 'upload_credentials', $upload_credentials );
				wp_enqueue_script( 'ssp-fileupload' );
				wp_register_script( 'jquery-peekabar', esc_url( $this->assets_url . 'js/jquery.peekabar' . $this->script_suffix . '.js' ), array( 'jquery' ), $this->version );
				wp_enqueue_script( 'jquery-peekabar' );
			}
		}

		/**
		 * Only load the import js when the import settings screen is loaded
		 */
		if ( 'podcast_page_podcast_settings' === $hook && isset( $_GET['tab'] ) && 'import' == $_GET['tab'] ) {
			wp_register_script( 'ssp-import-rss', esc_url( $this->assets_url . 'js/import.rss' . $this->script_suffix . '.js' ), array(
				'jquery',
				'jquery-ui-progressbar'
			), $this->version );
			wp_enqueue_script( 'ssp-import-rss' );
		}
	}


	protected function need_admin_scripts( $hook ) {
		$ssp = ssp_post_types();

		return 'post.php' === $hook ||
		       'post-new.php' === $hook ||
		       strpos( $hook, 'ssp-onboarding' ) ||
		       $this->is_ssp_admin_page() ||
		       ( in_array( 'post', $ssp ) && 'edit.php' === $hook ) ||
		       ( 'term.php' === $hook && ssp_series_taxonomy() === filter_input( INPUT_GET, 'taxonomy' ) );
	}
}
