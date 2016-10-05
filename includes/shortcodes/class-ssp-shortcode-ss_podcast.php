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
class SSP_Shortcode_SS_Podcast {

	/**
	 * Load podcast shortcode
	 * @param  array  $atts    Shortcode attributes
	 * @return string          HTML output
	 */
	function shortcode ( $atts ) {

		$defaults = array(
			'title' => '',
			'content' => 'series',
			'series' => '',
			'echo' => false,
			'size' => 100,
			'link_title' => true
		);

		$args = shortcode_atts( $defaults, $atts );

		// Make sure we return and don't echo.
		$args['echo'] = false;

		return ss_podcast( $args );
	}

}

$GLOBALS['ssp_shortcodes']['ss_podcast'] = new SSP_Shortcode_SS_Podcast();