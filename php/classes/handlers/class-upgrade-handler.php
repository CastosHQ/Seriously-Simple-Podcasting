<?php

namespace SeriouslySimplePodcasting\Handlers;

class Upgrade_Handler {

	/**
	 * Adds the ss_podcasting_subscribe_links_options array to the options table
	 */
	public function upgrade_subscribe_links_option() {
		$subscribe_links_options = array(
			'itunes_url'      => 'iTunes URL',
			'stitcher_url'    => 'Sticher URL',
			'google_play_url' => 'Google Play URL',
			'spotify_url'     => 'Spotify URL',
		);
		update_option( 'ss_podcasting_subscribe_links_options', $subscribe_links_options );
	}
}
