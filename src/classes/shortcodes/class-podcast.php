<?php

namespace SeriouslySimplePodcasting\ShortCodes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seriously Simple Podcasting Recent Podcast Episodes Widget
 *
 * @author        Hugh Lashbrooke
 * @package    SeriouslySimplePodcasting
 * @category    SeriouslySimplePodcasting/Shortcodes
 * @since        1.15.0
 */
class Podcast {

	/**
	 * Load ss_podcast shortcode
	 * @param  array  $params  Shortcode attributes
	 * @return string          HTML output
	 */
	public function shortcode( $params ) {

		$defaults = array(
			'title'      => '',
			'content'    => 'series',
			'series'     => '',
			'echo'       => false,
			'size'       => 100,
			'link_title' => true,
		);

		$args = shortcode_atts( $defaults, $params, 'ss_podcast' );

		// Make sure we return and don't echo.
		$args['echo'] = false;

		return ss_podcast( $args );
	}
}
