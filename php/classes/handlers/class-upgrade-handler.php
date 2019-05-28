<?php

namespace SeriouslySimplePodcasting\Handlers;

class Upgrade_Handler {

	/**
	 * Adds the ss_podcasting_subscribe_options array to the options table
	 */
	public function upgrade_subscribe_links_options() {
		$subscribe_links_options = array(
			'itunes_url'      => 'iTunes',
			'stitcher_url'    => 'Sticher',
			'google_play_url' => 'Google Play',
			'spotify_url'     => 'Spotify',
		);
		update_option( 'ss_podcasting_subscribe_options', $subscribe_links_options );
	}
}
