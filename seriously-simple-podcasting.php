<?php
/*
 * Plugin Name: Seriously Simple Podcasting
 * Version: 2.0.0-alpha
 * Plugin URI: http://wordpress.org/extend/plugins/seriously-simple-podcasting/
 * Description: Podcasting done right.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com
 * Requires at least: 3.6
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
require_once( 'includes/class-seriously-simple-podcasting.php' );

global $ss_podcasting;
$ss_podcasting = new Seriously_Simple_Podcasting( __FILE__ );

if( is_admin() ) {
	global $ssp_admin;
	require_once( 'includes/class-ssp-settings.php' );
	$ssp_admin = new SSP_Settings( __FILE__ );
}

require_once( 'includes/class-ssp-widget.php' );