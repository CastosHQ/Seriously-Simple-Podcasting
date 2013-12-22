<?php
/*
 * Plugin Name: Seriously Simple Podcasting
 * Version: 1.7.5
 * Plugin URI: http://wordpress.org/extend/plugins/seriously-simple-podcasting/
 * Description: Podcasting done right.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com
 * Requires at least: 3.5
 * Tested up to: 3.8
 *
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( 'seriously-simple-podcasting-functions.php' );
require_once( 'classes/class-seriously-simple-podcasting.php' );

global $ss_podcasting;
$ss_podcasting = new SeriouslySimplePodcasting( __FILE__ );

if( is_admin() ) {
	require_once( 'classes/class-seriously-simple-podcasting-admin.php' );
	$ss_podcasting_admin = new SeriouslySimplePodcasting_Admin( __FILE__ );
}

require_once( 'classes/class-seriously-simple-podcasting-widget.php' );

?>