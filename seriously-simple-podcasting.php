<?php
/*
 * Plugin Name: Seriously Simple Podcasting
 * Version: 1.11.2
 * Plugin URI: http://www.seriouslysimplepodcasting.com/
 * Description: Podcasting the way it's meant to be. No mess, no fuss - just you and your content taking over the world.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.2
 * Tested up to: 4.3.1
 *
 * Text Domain: seriously-simple-podcasting
 * Domain Path: /lang/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( 'includes/ssp-functions.php' );
require_once( 'includes/class-ssp-admin.php' );
require_once( 'includes/class-ssp-frontend.php' );

global $ssp_admin, $ss_podcasting;
$ssp_admin = new SSP_Admin( __FILE__, '1.11.2' );
$ss_podcasting = new SSP_Frontend( __FILE__, '1.11.2' );

if ( is_admin() ) {
	global $ssp_settings;
	require_once( 'includes/class-ssp-settings.php' );
	$ssp_settings = new SSP_Settings( __FILE__ );
}