<?php
/**
 * Plugin Name: Seriously Simple Podcasting
 * Version: 1.16.4
 * Plugin URI: https://www.seriouslysimplepodcasting.com/
 * Description: Podcasting the way it's meant to be. No mess, no fuss - just you and your content taking over the world.
 * Author: PodcastMotor
 * Author URI: https://www.podcastmotor.com/
 * Requires at least: 4.4
 * Required PHP version: 5.3.3
 * Tested up to: 4.8
 *
 * Text Domain: seriously-simple-podcasting
 *
 * @package Seriously Simple Podcasting
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( version_compare( PHP_VERSION, '5.3.3', '<=' ) ) { // PHP 5.3.3 or greater
	/**
	 * We are running under PHP 5.3.3
	 * Display an admin notice and gracefully do nothing.
	 */
	is_admin() && add_action( 'admin_notices', create_function( '', "
	echo '
		<div class=\"error\">
			<p>
				<strong>The Seriously Simple Podcasting plugin requires PHP version 5.3.3 or later. Please contact your web host to upgrade your PHP version or deactivate the plugin.</strong>.
			</p>
			<p>We apologise for any inconvenience.</p>
		</div>
	';"
	) );
	return;
}

define( 'SSP_DEBUG', false );

define( 'SSP_VERSION', '1.16.4' );
define( 'SSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

define( 'SSP_LOG_PATH', SSP_PLUGIN_PATH . 'log/ssp.log.' . date( 'd-m-y' ) . '.txt' );
define( 'SSP_LOG_URL', SSP_PLUGIN_URL . 'log/ssp.log.' . date( 'd-m-y' ) . '.txt' );

define( 'SSP_PODMOTOR_APP_URL', 'https://app.seriouslysimplepodcasting.com/' );

require_once( 'includes/ssp-functions.php' );
require_once( 'includes/class-ssp-admin.php' );
require_once( 'includes/class-ssp-frontend.php' );
require_once( 'includes/class-podmotor-handler.php' );
require_once( 'includes/class-ssp-rss-import.php' );

global $ssp_admin, $ss_podcasting;
$ssp_admin     = new SSP_Admin( __FILE__, SSP_VERSION );
$ss_podcasting = new SSP_Frontend( __FILE__, SSP_VERSION );

if ( is_admin() ) {
	global $ssp_settings;
	require_once( 'includes/class-ssp-settings.php' );
	$ssp_settings = new SSP_Settings( __FILE__, SSP_VERSION );
}
