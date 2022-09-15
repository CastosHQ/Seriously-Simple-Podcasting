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
class Podcast_Episode implements Shortcode {

	/**
	 * Shortcode function to display single podcast episode
	 * @param  array  $params Shortcode paramaters
	 * @return string         HTML output
	 */
	public function shortcode( $params ) {

		$player_style = get_option( 'ss_podcasting_player_style', 'standard' );

		$atts = shortcode_atts(
			array(
				'episode' => 0,
				'content' => 'title,player,details',
				'style'   => $player_style,
			),
			$params,
			'podcast_episode'
		);

		$episode = $atts['episode'];
		$content = $atts['content'];
		$style   = $atts['style'];

		// If no episode ID is specified then use the current post's ID
		if ( ! $episode ) {

			global $post;

			if ( isset( $post->ID ) ) {
				$episode = intval( $post->ID );
			}

			if ( ! $episode ) {
				return '';
			}
		}

		// Setup array of content items and trim whitespace
		$content_items = explode( ',', $content );
		$content_items = array_map( 'trim', $content_items );

		// Get episode for display
		$html = ssp_frontend_controller()->podcast_episode( $episode, $content_items, 'shortcode', $style );

		return $html;
	}
}
