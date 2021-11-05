<?php

namespace SeriouslySimplePodcasting\Controllers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main controller class
 *
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.0
 * @deprecated Use Useful_Variables trait instead
 */
abstract class Controller {

	/**
	 * JavaScript Suffix
	 *
	 * @var string
	 */
	public $script_suffix;

	/**
	 * Directory
	 *
	 * @var string
	 */
	public $dir;
	/**
	 * File
	 *
	 * @var string
	 */
	public $file;
	/**
	 * Assets Directory
	 *
	 * @var string
	 */
	public $assets_dir;
	/**
	 * Assets URI
	 *
	 * @var string
	 */
	public $assets_url;
	/**
	 * Home URL
	 *
	 * @var string
	 */
	public $home_url;
	/**
	 * Site URL
	 *
	 * @var string
	 */
	public $site_url;
	/**
	 * Templates Directory Path
	 *
	 * @var string
	 */
	public $template_path;
	/**
	 * Templates Directory URL
	 *
	 * @var string
	 */
	public $template_url;
	/**
	 * Token
	 *
	 * @var string
	 */
	public $token;
	/**
	 * Version
	 *
	 * @var string version.
	 */
	public $version;


	/**
	 * Unique identifier for the plugin.
	 *
	 * The variable name is used as the text domain when internationalizing strings of text.
	 * Its value should match the Text Domain file header in the main plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public $plugin_slug;

	public function __construct( $file, $version ) {
		$this->version       = $version;
		$this->dir           = dirname( $file );
		$this->file          = $file;
		$this->assets_dir    = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url    = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->template_path = trailingslashit( $this->dir ) . 'templates/';
		$this->template_url  = esc_url( trailingslashit( plugins_url( '/templates/', $file ) ) );
		$this->home_url      = trailingslashit( home_url() );
		$this->site_url      = trailingslashit( site_url() );
		$this->token         = SSP_CPT_PODCAST;
		$this->plugin_slug   = 'seriously-simple-podcasting';
		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	}

}
