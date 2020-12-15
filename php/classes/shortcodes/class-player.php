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
class Player {

	/**
	 * Load ss_player shortcode
	 * @return string          HTML output
	 */
	public function shortcode() {

		/**
		 * If we're in an RSS feed, don't render this shortcode
		 */
		if ( is_feed() ) {
			return;
		}

		global $ss_podcasting;

		$current_post = get_post();

		// only render if this is a valid podcast type
		if ( ! in_array( $current_post->post_type, ssp_post_types( true ), true ) ) {
			return;
		}

		$episode_id = $current_post->ID;
		$file       = $ss_podcasting->get_enclosure( $episode_id );
		if ( get_option( 'permalink_structure' ) ) {
			$file = $ss_podcasting->get_episode_download_link( $episode_id );
		}

		$player_style = (string) get_option( 'ss_podcasting_player_style', 'standard' );

		// Make sure we return and don't echo.
		$args['echo'] = false;

		$shortcode_player = $ss_podcasting->load_media_player( $file, $episode_id, $player_style );

		if ( apply_filters( 'ssp_show_episode_details', true, $episode_id, 'content' ) ) {
			$shortcode_player .= $ss_podcasting->episode_meta_details( $episode_id, 'content' );
		}

		return $shortcode_player;
	}

}
