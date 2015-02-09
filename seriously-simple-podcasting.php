<?php
/*
 * Plugin Name: Seriously Simple Podcasting
 * Version: 1.8.5
 * Plugin URI: https://wordpress.org/plugins/seriously-simple-podcasting/
 * Description: Podcasting the way it's meant to be.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.0
 * Tested up to: 4.1
 *
 * Text Domain: ss-podcasting
 * Domain Path: /lang/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( 'includes/ssp-functions.php' );
require_once( 'includes/class-ssp-admin.php' );
require_once( 'includes/class-ssp-frontend.php' );

global $ssp_admin, $ss_podcasting;
$ssp_admin = new SSP_Admin( __FILE__ );
$ss_podcasting = new SSP_Frontend( __FILE__ );

if( is_admin() ) {
	global $ssp_settings;
	require_once( 'includes/class-ssp-settings.php' );
	$ssp_settings = new SSP_Settings( __FILE__ );
}