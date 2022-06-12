<?php

use Codeception\TestCase\WPTestCase;
use SeriouslySimplePodcasting\Controllers\Players_Controller;

class Players_Controller_Test extends WPTestCase {
	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Tests that the Players_Controller::html_player method returns the new html player code
	 */
	public function test_player_controller_html_player_method() {
		$this->players_controller = new Players_Controller();
		$episode_id               = $this->factory->post->create(
			array(
				'title'       => 'My Custom Podcast',
				'post_status' => 'publish',
				'post_type'   => SSP_CPT_PODCAST,
			)
		);
		$episode                  = get_post( $episode_id );
		$html_player_content      = $this->players_controller->render_html_player( $episode->ID );

		$player_parts = array(
			'<div class="castos-player dark-mode"',
			'<div class="currently-playing">',
			sprintf( '<div class="episode-title player__episode-title">%s</div>', $episode->post_title ),
			'<div class="play-progress">',
			'<div class="play-pause-controls">',
			'<button title="Play" class="play-btn">',
			'<span class="screen-reader-text">Play Episode</span>',
			'<button title="Pause" class="pause-btn hide">',
			'<span class="screen-reader-text">Pause Episode</span>',
			'/wp-content/plugins/seriously-simple-podcasting/assets/css/images/player/images/icon-loader.svg" class="ssp-loader hide"/>',
			sprintf( '<audio preload="none" class="clip clip-%s">', $episode_id ),
			'<div class="ssp-progress" title="Seek">',
			'<span class="progress__filled"></span>',

			'<div class="ssp-playback playback">',
			'<div class="playback__controls">',
			'<button class="player-btn__volume" title="Mute/Unmute">',
			'<span class="screen-reader-text">Mute/Unmute Episode</span>',

			'<button data-skip="-10" class="player-btn__rwd" title="Rewind 10 seconds">',
			'<span class="screen-reader-text">Rewind 10 Seconds</span>',
			'<button data-speed="1" class="player-btn__speed" title="Playback Speed">1x</button>',
			'<button data-skip="30" class="player-btn__fwd" title="Fast Forward 30 seconds">',
			'<span class="screen-reader-text">Fast Forward 30 seconds</span>',
			'<div class="playback__timers">',
			'<time class="ssp-timer">00:00</time>',
			'<time class="ssp-duration"></time>',


			'<nav class="player-panels-nav">',
			'<button class="subscribe-btn" id="subscribe-btn-4" title="Subscribe">Subscribe</button>',
			'<button class="share-btn" id="share-btn-4" title="Share">Share</button>',

			'<div class="player-panels player-panels-4">',
			'<div class="subscribe player-panel subscribe-4">',
			'<div class="close-btn close-btn-4">',

			'<div class="panel__inner">',
			'<div class="subscribe-icons">',

			'<div class="player-panel-row">',

			'RSS Feed',
			'<input value="http://castos.loc/?feed=podcast" class="input-rss input-rss-4" readonly />',

			'<button class="copy-rss copy-rss-4"></button>',

			'<div class="share share-4 player-panel">',
			'<div class="close-btn close-btn-4">',

			'<div class="player-panel-row">',

			'Share',
			'<div class="icons-holder">',
			'<a href="https://www.facebook.com/sharer/sharer.php?u=http://castos.loc/?podcast=post-title-18&t=Post title 18"
					   target="_blank" rel="noopener noreferrer" class="share-icon facebook" title="Share on Facebook">',
			'<a href="https://twitter.com/intent/tweet?text=http://castos.loc/?podcast=post-title-18&url=Post title 18"
					   target="_blank" rel="noopener noreferrer" class="share-icon twitter" title="Share on Twitter">',
			'<a href=""
					   target="_blank" rel="noopener noreferrer" class="share-icon download" title="Download" download>',
			'<div class="player-panel-row">',
			'Link',
			'<input value="http://castos.loc/?podcast=post-title-18" class="input-link input-link-4" readonly />',
			'<button class="copy-link copy-link-4" readonly=""></button>',
			'<div class="player-panel-row">',
			'Embed',
			'<button class="copy-embed copy-embed-4"></button>',
		);

		foreach ( $player_parts as $player_part ) {
			$this->assertStringContainsString( $player_part, $html_player_content );
		}
	}
}
