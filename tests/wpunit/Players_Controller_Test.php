<?php

namespace wpunit;

use Codeception\TestCase\WPTestCase;
use SeriouslySimplePodcasting\Controllers\Players_Controller;
use SeriouslySimplePodcasting\Handlers\Options_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;

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
	 * @covers Players_Controller::render_html_player() method returns the new html player code
	 */
	public function test_player_controller_html_player_method() {
		$renderer           = new Renderer();
		$options_handler    = new Options_Handler();
		$episode_repository = $this->episode_repository();

		$this->players_controller = new Players_Controller( $renderer, $options_handler, $episode_repository );
		$episode_id               = $this->factory->post->create(
			array(
				'title'       => 'My Custom Podcast',
				'post_status' => 'publish',
				'post_type'   => SSP_CPT_PODCAST,
			)
		);
		$episode                  = get_post( $episode_id );

		$html_player_content      = $this->players_controller->render_html_player( $episode->ID );

		// Empty, because the file is not assigned
		$this->assertEmpty( $html_player_content );

		// Now, let's check that it's not empty for admin
		define( 'WP_ADMIN', true );
		$html_player_content      = $this->players_controller->render_html_player( $episode->ID );
		$this->assertNotEmpty( $html_player_content );

		$permalink                = get_post_permalink( $episode_id );
		$site_url                 = site_url();

		$this->assertStringContainsString( 'Warning: the player will not be shown', $html_player_content );

		$player_parts = array(
			'class="castos-player dark-mode',
			'<div class="currently-playing">',
			sprintf( '<div class="episode-title player__episode-title">%s</div>', $episode->post_title ),
			'<div class="play-progress">',
			'<div class="play-pause-controls">',
			'<button title="Play" aria-label="Play Episode" aria-pressed="false" class="play-btn">',
			'<span class="screen-reader-text">Play Episode</span>',
			'<button title="Pause" aria-label="Pause Episode" aria-pressed="false" class="pause-btn hide">',
			'<span class="screen-reader-text">Pause Episode</span>',
			'/wp-content/plugins/seriously-simple-podcasting/assets/css/images/player/images/icon-loader.svg" alt="Loading" class="ssp-loader hide"/>',
			sprintf( '<audio preload="none" class="clip clip-%s">', $episode_id ),
			'<div class="ssp-progress" role="progressbar" title="Seek" aria-valuenow="0" aria-valuemin="0" aria-valuemax="',
			'<span class="progress__filled"></span>',

			'<div class="ssp-playback playback">',
			'<div class="playback__controls">',
			'<button class="player-btn player-btn__volume" title="Mute/Unmute">',
			'<span class="screen-reader-text">Mute/Unmute Episode</span>',

			'<button data-skip="-10" class="player-btn player-btn__rwd" title="Rewind 10 seconds">',
			'<span class="screen-reader-text">Rewind 10 Seconds</span>',
			'<button data-speed="1" class="player-btn player-btn__speed" title="Playback Speed" aria-label="Playback Speed">1x</button>',
			'<button data-skip="10" class="player-btn player-btn__fwd" title="Fast Forward 10 seconds">',
			'<span class="screen-reader-text">Fast Forward 10 seconds</span>',
			'<div class="playback__timers">',
			'<time class="ssp-timer">00:00</time>',
			'<time class="ssp-duration" datetime="PT0H0M0S"></time>',


			'<nav class="player-panels-nav">',
			sprintf( '<button class="subscribe-btn" id="subscribe-btn-%s" title="Subscribe">Subscribe</button>', $episode_id ),
			sprintf( '<button class="share-btn" id="share-btn-%s" title="Share">Share</button>', $episode_id ),
			sprintf( '<div class="player-panels player-panels-%s">', $episode_id ),
			sprintf( '<div class="subscribe player-panel subscribe-%s">', $episode_id ),
			sprintf( '<div class="close-btn close-btn-%s">', $episode_id ),

			'<div class="panel__inner">',
			'<div class="subscribe-icons">',

			'<div class="player-panel-row">',

			'RSS Feed',
			sprintf( '<input value="%s/?feed=podcast&amp;podcast_series=wordpress-test" class="input-rss input-rss-%s" title="RSS Feed URL" readonly />', $site_url, $episode_id ),

			sprintf( '<button class="copy-rss copy-rss-%s" title="Copy RSS Feed URL" aria-label="Copy RSS Feed URL"></button>', $episode_id ),

			sprintf( '<div class="share share-%s player-panel">', $episode_id ),
			sprintf( '<div class="close-btn close-btn-%s">', $episode_id ),

			'<div class="player-panel-row">',

			'Share',
			'<div class="icons-holder">',
			sprintf(
				'<a href="https://www.facebook.com/sharer/sharer.php?u=%s&t=%s"',
				$permalink,
				$episode->post_title
			),
			'target="_blank" rel="noopener noreferrer" class="share-icon facebook" title="Share on Facebook"',
			sprintf(
				'<a href="https://twitter.com/intent/tweet?text=%s&url=%s"',
				$permalink,
				$episode->post_title
			),
			'target="_blank" rel="noopener noreferrer" class="share-icon twitter" title="Share on Twitter">',
			'target="_blank" rel="noopener noreferrer" class="share-icon download" title="Download" download>',
			'<div class="title">',
			'Link',
			sprintf( '<input value="%s" class="input-link input-link-%d" title="Episode URL" readonly />', $permalink, $episode_id ),
			sprintf( '<button class="copy-link copy-link-%s" title="Copy Episode URL" aria-label="Copy Episode URL" readonly=""></button>', $episode_id ),
			'<div class="player-panel-row">',
			'Embed',
			sprintf( '<button class="copy-embed copy-embed-%s" title="Copy Embed Code" aria-label="Copy Embed Code"></button>', $episode_id ),
		);

		foreach ( $player_parts as $player_part ) {
			$this->assertStringContainsString( $player_part, $html_player_content );
		}
	}

	/**
	 * @return Episode_Repository
	 */
	protected function episode_repository() {
		return ssp_get_service( 'episode_repository' );
	}
}
