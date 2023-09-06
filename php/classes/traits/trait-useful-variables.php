<?php
/**
 * Singleton Trait
 */

namespace SeriouslySimplePodcasting\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton Trait
 * Moved this code from the parent Controller class.
 *
 * @author Sergiy Zakharchenko
 * @package SeriouslySimplePodcasting
 * @since 2.9.3
 */
trait Useful_Variables {

	public $version;
	public $file;
	public $dir;
	public $assets_dir;
	public $assets_url;
	public $template_path;
	public $template_url;
	public $home_url;
	public $site_url;
	public $token;
	public $plugin_slug;
	public $script_suffix;

	/**
	 * Init useful plugin variables
	 */
	protected function init_useful_variables() {
		$this->version       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : SSP_VERSION;
		$this->file          = SSP_PLUGIN_FILE;
		$this->dir           = trailingslashit( SSP_PLUGIN_PATH );
		$this->assets_dir    = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url    = esc_url( trailingslashit( SSP_PLUGIN_URL . 'assets' ) );
		$this->template_path = trailingslashit( $this->dir ) . 'templates/';
		$this->template_url  = esc_url( trailingslashit( SSP_PLUGIN_URL . 'templates' ) );
		$this->home_url      = trailingslashit( home_url() );
		$this->site_url      = trailingslashit( site_url() );
		$this->token         = SSP_CPT_PODCAST;
		$this->plugin_slug   = 'seriously-simple-podcasting';
		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}
}
