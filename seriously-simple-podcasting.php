<?php
/*
 * Plugin Name: Seriously Simple Podcasting
 * Version: 1.0.0
 * Plugin URI: http://www.hughlashbrooke.com
 * Description: A seriously simple and easy to use podcasting plugin for WordPress.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com
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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

require_once( 'classes/class-seriously-simple-podcasting.php' );

global $ss_podcasting;
$ss_podcasting = new SeriouslySimplePodcasting( __FILE__ );

if( is_admin() ) {
	require_once( 'classes/class-seriously-simple-podcasting-admin.php' );
	$ss_podcasting_admin = new SeriouslySimplePodcasting_Admin( __FILE__ );
}

/**
 * TO DO:
 *
 * Add RSS feed link
 * Add iTunes RSS feed link
 * Add user options for feed details (category, etc.)
 * Create widget to display podcast series
 * Create widget to display podcast episodes from a selected series
 * 
 */

?>