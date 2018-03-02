<?php
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
class SSP_Shortcode_SS_Player {

	/**
	 * Load ss_player shortcode
	 * @return string          HTML output
	 */
	public function shortcode() {

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

		// Make sure we return and don't echo.
		$args['echo'] = false;

		return $ss_podcasting->load_media_player( $file, $episode_id, 'large' );
	}

}

$GLOBALS['ssp_shortcodes']['ss_player'] = new SSP_Shortcode_SS_Player();