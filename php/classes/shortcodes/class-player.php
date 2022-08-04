<?php

namespace SeriouslySimplePodcasting\ShortCodes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seriously Simple Podcasting HTML 5 media player
 *
 * @author     Jonathan Bossenger
 * @package    SeriouslySimplePodcasting
 * @category   SeriouslySimplePodcasting/Shortcodes
 * @since      1.19.6
 */
class Player implements Shortcode {

	/**
	 * Load ss_player shortcode
	 * @return string          HTML output
	 */
	public function shortcode( $params ) {

		/**
		 * If we're in an RSS feed, don't render this shortcode
		 */
		if ( is_feed() ) {
			return '';
		}

		$frontent_controller = ssp_frontend_controller();

		$current_post = get_post();

		// only render if this is a valid podcast type
		if ( ! in_array( $current_post->post_type, ssp_post_types( true ), true ) ) {
			return '';
		}

		$episode_id = $current_post->ID;
		$file       = $frontent_controller->get_enclosure( $episode_id );
		if ( get_option( 'permalink_structure' ) ) {
			$file = $frontent_controller->get_episode_download_link( $episode_id );
		}

		$player_style = (string) get_option( 'ss_podcasting_player_style', 'standard' );

		return $frontent_controller->load_media_player( $file, $episode_id, $player_style, 'shortcode' );
	}

}
