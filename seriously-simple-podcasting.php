<?php
/*
 * Plugin Name: Seriously Simple Podcasting
 * Version: 1.15.2
 * Plugin URI: https://www.seriouslysimplepodcasting.com/
 * Description: Podcasting the way it's meant to be. No mess, no fuss - just you and your content taking over the world.
 * Author: Craig Hewitt
 * Author URI: https://craighewitt.me/
 * Requires at least: 4.4
 * Tested up to: 4.6.1
 *
 * Text Domain: seriously-simple-podcasting
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( 'includes/ssp-functions.php' );
require_once( 'includes/class-ssp-admin.php' );
require_once( 'includes/class-ssp-frontend.php' );

global $ssp_admin, $ss_podcasting;
$ssp_admin = new SSP_Admin( __FILE__, '1.15.2' );
$ss_podcasting = new SSP_Frontend( __FILE__, '1.15.2' );

if ( is_admin() ) {
	global $ssp_settings;
	require_once( 'includes/class-ssp-settings.php' );
	$ssp_settings = new SSP_Settings( __FILE__ );
}
