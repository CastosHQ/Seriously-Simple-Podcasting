<?php
/**
 * Plugin Name: Seriously Simple Podcasting
 * Version: 1.16.1
 * Plugin URI: https://www.seriouslysimplepodcasting.com/
 * Description: Podcasting the way it's meant to be. No mess, no fuss - just you and your content taking over the world.
 * Author: PodcastMotor
 * Author URI: https://www.podcastmotor.com/
 * Requires at least: 4.4
 * Required PHP version: 5.5
 * Tested up to: 4.7.3
 *
 * Text Domain: seriously-simple-podcasting
 *
 * @package Seriously Simple Podcasting
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( version_compare( PHP_VERSION, '5.5.0', '>=' ) ) {
	
	define( 'SSP_DEBUG', false );
	
	define( 'SSP_VERSION', '1.16.1' );
	define( 'SSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'SSP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
	
	define( 'SSP_UPLOADS_DIR', ABSPATH . 'wp-content/ssp/' );
	
	define( 'SSP_LOG_PATH', SSP_PLUGIN_PATH . 'log/ssp.log.' . date( 'd-m-y' ) . '.txt' );
	define( 'SSP_LOG_URL', SSP_PLUGIN_URL . 'log/ssp.log.' . date( 'd-m-y' ) . '.txt' );
	
	define( 'SSP_PODMOTOR_APP_URL', 'http://app.seriouslysimplepodcasting.com/' );
	
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
} else {
	/**
	 * We are running under PHP 5.5.x
	 * Display an admin notice and do nothing.
	 */
	is_admin() && add_action( 'admin_notices', create_function( '', "
	echo '
		<div class=\"error\">
			<p>
				<strong>The Seriously Simple Podcasting plugin requires PHP 5.5 or later. Please upgrade your PHP version or deactivate the plugin.</strong>.
			</p>
		</div>
	';"
	) );
	
} // End if().


