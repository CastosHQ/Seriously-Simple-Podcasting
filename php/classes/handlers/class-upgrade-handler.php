<?php

namespace SeriouslySimplePodcasting\Handlers;

class Upgrade_Handler {

	/**
	 * Adds the ss_podcasting_subscribe_options array to the options table
	 */
	public function upgrade_subscribe_links_options() {
		$subscribe_links_options = array(
			'itunes_url'      => 'iTunes',
			'stitcher_url'    => 'Stitcher',
			'google_play_url' => 'Google Play',
			'spotify_url'     => 'Spotify',
		);
		update_option( 'ss_podcasting_subscribe_options', $subscribe_links_options );
	}

	/**
	 * Fixes an incorrectly spelled subscribe option
	 */
	public function upgrade_stitcher_subscribe_link_option() {
		$subscribe_links_options = get_option( 'ss_podcasting_subscribe_options', array() );
		if ( isset( $subscribe_links_options['stitcher_url'] ) ) {
			$subscribe_links_options['stitcher_url'] = 'Stitcher';
		}
		update_option( 'ss_podcasting_subscribe_options', $subscribe_links_options );
	}
}
