<?php
/**
 * Plugin Name: Seriously Simple Podcasting
 * Version: 1.19.20
 * Plugin URI: https://www.castos.com/seriously-simple-podcasting
 * Description: Podcasting the way it's meant to be. No mess, no fuss - just you and your content taking over the world.
 * Author: Castos
 * Author URI: https://www.castos.com/
 * Requires PHP: 5.3.3
 * Requires at least: 4.4
 * Tested up to: 5.1.1
 *
 * Text Domain: seriously-simple-podcasting
 *
 * @package Seriously Simple Podcasting
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SeriouslySimplePodcasting\Controllers\AdminController;
use SeriouslySimplePodcasting\Controllers\FrontendController;
use SeriouslySimplePodcasting\Controllers\SettingsController;
//use SeriouslySimplePodcasting\Rest;


/**
 * Only require the REST API endpoints if the user is using WordPress greater than 4.7
global $wp_version;
if ( version_compare( $wp_version, '4.7', '>=' ) ) {
	require_once 'includes/class-ssp-wp-rest-api.php';
	require_once 'includes/class-ssp-wp-rest-episodes-controller.php';
}
 */

require_once 'includes/ssp-functions.php';
if ( ! ssp_is_php_version_ok() ) {
	return;
}

define( 'SSP_VERSION', '1.19.20' );
define( 'SSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'SSP_CASTOS_APP_URL' ) ) {
	define( 'SSP_CASTOS_APP_URL', 'https://app.castos.com/' );
}
if ( ! defined( 'SSP_CASTOS_EPISODES_URL' ) ) {
	define( 'SSP_CASTOS_EPISODES_URL', 'https://episodes.castos.com/' );
}

require SSP_PLUGIN_PATH . 'vendor/autoload.php';

/*
require_once 'includes/class-ssp-admin.php';
require_once 'includes/class-ssp-frontend.php';
*/
/*
require_once 'includes/class-podmotor-handler.php';
require_once 'includes/class-ssp-external-rss-importer.php';
*/


global $ssp_admin, $ss_podcasting, $ssp_wp_rest_api;
$ssp_admin     = new admincontroller( __FILE__, SSP_VERSION );
$ss_podcasting = new FrontendController( __FILE__, SSP_VERSION );
//$ssp_wp_rest_api = new Rest\RestApi( SSP_VERSION );

if ( is_admin() ) {
	global $ssp_settings;
	$ssp_settings = new SettingsController( __FILE__, SSP_VERSION );
}
