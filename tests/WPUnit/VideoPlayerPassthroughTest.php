<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Integrations\Blocks\Episode_List_Block;
use SeriouslySimplePodcasting\Presenters\Episode_List_Presenter;
use SeriouslySimplePodcasting\ShortCodes\Player;
use SeriouslySimplePodcasting\ShortCodes\Podcast_Episode;

class VideoPlayerPassthroughTest extends \Codeception\TestCase\WPTestCase {
	const VIDEO = 'https://example.com/video.mp4';
	const AUDIO = 'https://example.com/audio.mp3';

	private $episode_id;
	private $passthrough_filter;
	private $cookies;
	private $permalink_structure;
	private $original_post;
	private $original_user_id;

	protected function setUp(): void {
		parent::setUp();
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		$property = new \ReflectionProperty( 'SeriouslySimplePodcasting\Controllers\App_Controller', 'series_controller' );
		$property->setAccessible( true );
		$property->getValue( ssp_app() )->register_taxonomy();

		global $wp_rewrite, $post;
		$this->original_post       = $post;
		$this->permalink_structure = $wp_rewrite->permalink_structure;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$this->cookies          = $_COOKIE;
		$this->original_user_id = get_current_user_id();
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
		wp_set_current_user( 0 );
		update_option( 'ss_podcasting_player_style', 'standard' );

		$this->episode_id = self::factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => 'publish',
				'post_title'  => 'Video player episode',
				'post_name'   => 'video-player-episode',
			)
		);
		update_post_meta( $this->episode_id, 'audio_file', self::VIDEO );
		update_post_meta( $this->episode_id, 'episode_type', 'video' );
		$post = get_post( $this->episode_id );
		setup_postdata( $post );
	}

	protected function tearDown(): void {
		if ( $this->passthrough_filter ) {
			remove_filter( 'ssp_episode_passthrough_required', $this->passthrough_filter );
		}
		global $wp_rewrite, $post;
		$wp_rewrite->set_permalink_structure( $this->permalink_structure );
		$post = $this->original_post;
		if ( $post ) {
			setup_postdata( $post );
		} else {
			wp_reset_postdata();
		}
		$_COOKIE = $this->cookies;
		wp_set_current_user( $this->original_user_id );
		parent::tearDown();
	}

	public function passthrough_cases() {
		return array(
			'not required' => array( false ),
			'required'     => array( true ),
		);
	}

	/** @dataProvider passthrough_cases */
	public function test_ss_player_video_uses_expected_source( $required ) {
		$this->set_passthrough_required( $required );
		$this->assert_video_source( ( new Player() )->shortcode( array() ), $required );
	}

	/** @dataProvider passthrough_cases */
	public function test_podcast_episode_video_uses_expected_source( $required ) {
		$this->set_passthrough_required( $required );
		$output = ( new Podcast_Episode() )->shortcode(
			array( 'episode' => $this->episode_id, 'content' => 'player' )
		);
		$this->assert_video_source( $output, $required );
	}

	/** @dataProvider passthrough_cases */
	public function test_podcast_list_block_video_uses_expected_source( $required ) {
		$this->set_passthrough_required( $required );
		$this->assert_video_source( $this->render_podcast_list_block(), $required );
	}

	private function render_podcast_list_block() {
		$presenter = new Episode_List_Presenter(
			ssp_get_service( 'episode_repository' ),
			ssp_app()->players_controller,
			ssp_get_service( 'renderer' )
		);
		$block = new Episode_List_Block( $presenter );
		return $block->render_callback(
			array(
				'selectedPodcast'    => '-1',
				'postsPerPage'       => '10',
				'orderBy'            => 'date',
				'order'              => 'desc',
				'player'             => true,
				'showTitle'          => true,
				'featuredImage'      => false,
				'featuredImageSize'  => 'full',
				'playerBelowExcerpt' => false,
				'excerpt'            => false,
				'columnsPerRow'      => '1',
				'titleSize'          => '16',
				'titleUnderImage'    => false,
			)
		);
	}

	/** @dataProvider passthrough_cases */
	public function test_content_player_video_uses_expected_source( $required ) {
		$this->set_passthrough_required( $required );
		$output = ssp_frontend_controller()->get_episode_player( $this->episode_id, 'content' );
		$this->assert_video_source( $output, $required );
	}

	/** @dataProvider passthrough_cases */
	public function test_ssp_player_video_uses_expected_source( $required ) {
		$this->set_passthrough_required( $required );
		$this->assert_video_source( ssp_player( $this->episode_id ), $required );
	}

	/** @dataProvider passthrough_cases */
	public function test_ss_player_audio_keeps_expected_source( $required ) {
		update_post_meta( $this->episode_id, 'episode_type', 'audio' );
		update_post_meta( $this->episode_id, 'audio_file', self::AUDIO );
		$this->set_passthrough_required( $required );
		$output = ( new Player() )->shortcode( array() );
		$expected = $required ? home_url( '/podcast-player/' . $this->episode_id . '/video-player-episode.mp3' ) : self::AUDIO;

		$this->assertSame( 1, preg_match( '~<audio\b[^>]*>(.*?)</audio>~is', $output, $audio ) );
		$this->assert_source_url( $audio[1], $expected );
	}

	/** @dataProvider passthrough_cases */
	public function test_protected_video_in_podcast_list_block_has_no_media_url( $required ) {
		wp_update_post( array( 'ID' => $this->episode_id, 'post_password' => 'secret' ) );
		$this->set_passthrough_required( $required );
		$output = $this->render_podcast_list_block();

		$this->assertStringContainsString( 'Video player episode', $output );
		$this->assert_no_media_url( $output );
	}

	public function test_ss_player_video_renders_passthrough_with_plain_permalinks() {
		$this->enable_plain_passthrough();
		$referrer_filter = static function () {
			return 'test';
		};
		add_filter( 'ssp_download_referrer', $referrer_filter );
		try {
			$output = ( new Player() )->shortcode( array() );
		} finally {
			remove_filter( 'ssp_download_referrer', $referrer_filter );
		}

		$expected = home_url( '/?podcast_episode=' . $this->episode_id . '&ref=test&ssp_video_file=video.mp4' );
		$this->assertSame( 1, preg_match( '~<video\b[^>]*>(.*?)</video>~is', $output, $video ) );
		$this->assert_source_url( $video[1], $expected );
	}

	private function assert_no_media_url( $output ) {
		$output = html_entity_decode( $output, ENT_QUOTES, 'UTF-8' );
		$this->assertStringNotContainsString( self::VIDEO, $output );
		$this->assertStringNotContainsString( '/podcast-player/', $output );
		$this->assertStringNotContainsString( 'podcast_episode=' . $this->episode_id, $output );
	}

	private function enable_plain_passthrough() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '' );
		$this->set_passthrough_required( true );
	}

	private function set_passthrough_required( $required ) {
		$this->passthrough_filter = static function () use ( $required ) {
			return $required;
		};
		add_filter( 'ssp_episode_passthrough_required', $this->passthrough_filter );
	}

	private function assert_video_source( $output, $required ) {
		$expected = $required ? home_url( '/podcast-player/' . $this->episode_id . '/video-player-episode.mp4' ) : self::VIDEO;
		$this->assertSame( 1, preg_match( '~<video\b[^>]*>(.*?)</video>~is', $output, $video ) );
		$this->assert_source_url( $video[1], $expected );
	}

	private function assert_source_url( $output, $expected ) {
		$this->assertSame( 1, preg_match( '~<source\b[^>]*\bsrc="([^"]*)"~i', $output, $source ) );
		$url = html_entity_decode( $source[1], ENT_QUOTES, 'UTF-8' );
		$separator = false === strpos( $expected, '?' ) ? '?' : '&';
		$this->assertMatchesRegularExpression( '~^' . preg_quote( $expected . $separator, '~' ) . '_=\d+$~', $url );
	}
}
