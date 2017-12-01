<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seriously Simple Podcasting Recent Podcast Episodes Widget
 *
 * @author 		Hugh Lashbrooke
 * @package 	SeriouslySimplePodcasting
 * @category 	SeriouslySimplePodcasting/Shortcodes
 * @since 		1.15.0
 */
class SSP_Shortcode_Podcast_Episode {

	/**
	 * Shortcode function to display single podcast episode
	 * @param  array  $params Shortcode paramaters
	 * @return string         HTML output
	 */
	public function shortcode ( $params ) {

		global $ss_podcasting;

		$atts = shortcode_atts( array(
	        'episode' => 0,
	        'content' => 'title,player,details',
            'style' => 'mini',
	    ), $params, 'podcast_episode' );

		extract( $atts );

		// If no episode ID is specified then use the current post's ID
	    if ( ! $episode ) {

	    	global $post;

	    	if( isset( $post->ID ) ) {
	    		$episode = intval( $post->ID );
	    	}

	    	if ( ! $episode ) {
	    		return;
	    	}

	    }

	    // Setup array of content items and trim whitespace
	    $content_items = explode( ',', $content );
	    $content_items = array_map( 'trim', $content_items );

	    // Get episode for display
	    $html = $ss_podcasting->podcast_episode( $episode, $content_items, 'shortcode', $style );

	    return $html;

	}

}

$GLOBALS['ssp_shortcodes']['podcast_episode'] = new SSP_Shortcode_Podcast_Episode();