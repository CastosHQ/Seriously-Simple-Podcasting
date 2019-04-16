<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class SSP_External_RSS_Importer {

	private $rss_feed;

	private $feed_object;

	public function __construct( $rss_feed ) {
		$this->rss_feed = $rss_feed;
	}

	public function load_rss_feed() {
		$this->feed_object = simplexml_load_file( $this->rss_feed );
	}

	public function import_rss_feed() {

	}
}
