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
 * Todo:
 * Now we can easily get rid of passing $file and $variable parameters in each controller class.
 *
 * @author Sergey Zakharchenko
 * @package SeriouslySimplePodcasting
 * @since 2.9.3
 */
trait Useful_Variables {

	protected $version;
	protected $dir;
	protected $assets_dir;
	protected $assets_url;
	protected $template_path;
	protected $template_url;
	protected $home_url;
	protected $site_url;
	protected $token;
	protected $plugin_slug;
	protected $script_suffix;

	/**
	 * Init useful plugin variables
	 */
	protected function init_useful_variables() {
		$this->version       = SSP_VERSION;
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
