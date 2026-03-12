<?php
/**
 * Plugin Name: Seriously Simple Podcasting
 * Version: 3.14.3
 * Plugin URI: https://castos.com/seriously-simple-podcasting/?utm_medium=sspodcasting&utm_source=wordpress&utm_campaign=wpplugin_08_2019
 * Description: Podcasting the way it's meant to be. No mess, no fuss - just you and your content taking over the world.
 * Author: Castos
 * Author URI: https://castos.com/?utm_medium=sspodcasting&utm_source=wordpress&utm_campaign=wpplugin_08_2019
 * Requires PHP: 7.4
 * Requires at least: 5.3
 * Tested up to: 6.8
 *
 * Text Domain: seriously-simple-podcasting
 *
 * @package Seriously Simple Podcasting
 *
 * GitHub Plugin URI: https://github.com/CastosHQ/Seriously-Simple-Podcasting
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSP_VERSION', '3.14.3' );
define( 'SSP_PLUGIN_FILE', __FILE__ );
define( 'SSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'SSP_CASTOS_APP_URL' ) ) {
	define( 'SSP_CASTOS_APP_URL', 'https://app.castos.com/' );
}
if ( ! defined( 'SSP_CASTOS_EPISODES_URL' ) ) {
	define( 'SSP_CASTOS_EPISODES_URL', 'https://episodes.castos.com/' );
}
if ( ! defined( 'SSP_CPT_PODCAST' ) ) {
	define( 'SSP_CPT_PODCAST', 'podcast' );
}

require SSP_PLUGIN_PATH . 'vendor/autoload.php';

require_once SSP_PLUGIN_PATH . 'php/includes/ssp-functions.php';

ssp_app();

add_filter( 'template_include', 'ssp_replace_archive_with_page', 99 );

function ssp_replace_archive_with_page( $template ) {
    // Check if this is a podcast archive
    if ( is_post_type_archive( SSP_CPT_PODCAST ) || is_tax( ssp_series_taxonomy() ) ) {
        // Get the page ID from settings/option
        $page_id = get_option( 'ssp_archive_page_id' );

		$page_id = 358;
        
        if ( $page_id ) {
            $page = get_post( $page_id );
            if ( $page && 'page' === $page->post_type ) {
                // Get the page template
                $page_template = get_page_template_slug( $page_id );
                if ( $page_template ) {
                    $new_template = locate_template( $page_template );
                } else {
                    // Use default page template
                    $new_template = locate_template( array( 'page.php', 'singular.php', 'index.php' ) );
                }
                
                if ( $new_template ) {
                    // Set up global post data for the page
                    global $wp_query;
                    
                    // Replace the query results with just this page
                    $wp_query->posts = array( $page );
                    $wp_query->post_count = 1;
                    $wp_query->found_posts = 1;
                    $wp_query->max_num_pages = 1;
                    $wp_query->post = $page;
                    
                    // Set query flags to match a page query
                    $wp_query->queried_object = $page;
                    $wp_query->queried_object_id = $page_id;
                    $wp_query->is_page = true;
                    $wp_query->is_singular = true;
                    $wp_query->is_archive = false;
                    $wp_query->is_home = false;
                    $wp_query->is_single = false;
                    
                    // Set up the post data for template functions
                    $wp_query->rewind_posts();
                    
                    return $new_template;
                }
            }
        }
    }
    
    return $template;
}
