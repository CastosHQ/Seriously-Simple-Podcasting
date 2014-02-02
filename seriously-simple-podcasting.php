<?php
/*
 * Plugin Name: Seriously Simple Podcasting
 * Version: 2.0.0-alpha
 * Plugin URI: http://wordpress.org/extend/plugins/seriously-simple-podcasting/
 * Description: Podcasting done right.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com
 * Requires at least: 3.8
 * Tested up to: 3.8.1
 *
 * Text Domain: ss-podcasting
 *
 * @package 	SeriouslySimplePodcasting
 * @category 	Core
 * @author 		Hugh Lashbrooke
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( 'includes/ssp-functions.php' );
require_once( 'includes/class-ssp-admin.php' );
require_once( 'includes/class-ssp-frontend.php' );

global $ssp_admin, $ss_podcasting;
$ssp_admin = new SSP_Admin( __FILE__ );
$ss_podcasting = new SSP_Frontend( __FILE__ );

if( is_admin() ) {
	require_once( 'includes/class-ssp-settings.php' );
	new SSP_Settings( __FILE__ );
}

require_once( 'includes/class-ssp-widget.php' );