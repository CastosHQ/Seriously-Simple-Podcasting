<?php
/*
 * Plugin Name: Seriously Simple Podcasting
 * Version: 1.3.3
 * Plugin URI: http://www.hughlashbrooke.com
 * Description: An incredibly easy-to-use podcasting plugin.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com
 * Requires at least: 3.0
 * Tested up to: 3.5.1
 * 
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

/*
 * Uses MediaElement.js for audio player
 * http://mediaelementjs.com/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( 'classes/class-seriously-simple-podcasting.php' );

global $ss_podcasting;
$ss_podcasting = new SeriouslySimplePodcasting( __FILE__ );

if( is_admin() ) {
	require_once( 'classes/class-seriously-simple-podcasting-admin.php' );
	$ss_podcasting_admin = new SeriouslySimplePodcasting_Admin( __FILE__ );
}

require_once( 'seriously-simple-podcasting-functions.php' );
require_once( 'classes/class-seriously-simple-podcasting-widget.php' );

?>