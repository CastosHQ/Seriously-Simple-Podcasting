<?php
/**
 * Plugin Name: Seriously Simple Podcasting
 * Version: 3.0.2
 * Plugin URI: https://castos.com/seriously-simple-podcasting/?utm_medium=sspodcasting&utm_source=wordpress&utm_campaign=wpplugin_08_2019
 * Description: Podcasting the way it's meant to be. No mess, no fuss - just you and your content taking over the world.
 * Author: Castos
 * Author URI: https://castos.com/?utm_medium=sspodcasting&utm_source=wordpress&utm_campaign=wpplugin_08_2019
 * Requires PHP: 7.4
 * Requires at least: 5.3
 * Tested up to: 6.4
 *
 * Text Domain: seriously-simple-podcasting
 *
 * @package Seriously Simple Podcasting
 *
 * GitHub Plugin URI: https://github.com/CastosHQ/Seriously-Simple-Podcasting
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSP_VERSION', '3.0.2' );
define( 'SSP_PLUGIN_FILE', __FILE__ );
define( 'SSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'SSP_CASTOS_APP_URL' ) ) {
	define( 'SSP_CASTOS_APP_URL', 'https://app.castos.com/' );
}
if ( ! defined( 'SSP_CASTOS_EPISODES_URL' ) ) {
	define( 'SSP_CASTOS_EPISODES_URL', 'https://episodes.castos.com/' );
}
if ( ! defined( 'SSP_CPT_PODCAST' ) ) {
	define( 'SSP_CPT_PODCAST', 'podcast' );
}

require SSP_PLUGIN_PATH . 'vendor/autoload.php';

require_once SSP_PLUGIN_PATH . 'php/includes/ssp-functions.php';

ssp_app();
