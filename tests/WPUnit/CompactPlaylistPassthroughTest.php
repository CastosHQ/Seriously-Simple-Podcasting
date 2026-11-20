<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist;

class CompactPlaylistPassthroughTest extends \Codeception\TestCase\WPTestCase {
	const AUDIO = 'https://example.com/compact-episode.mp3';
	const TITLE = 'Compact playlist episode';

	private $episode_id;
	private $passthrough_filter;
	private $cookies;
	private $permalink_structure;

	protected function setUp(): void {
		parent::setUp();
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		$property = new \ReflectionProperty( 'SeriouslySimplePodcasting\Controllers\App_Controller', 'series_controller' );
		$property->setAccessible( true );
		$property->getValue( ssp_app() )->register_taxonomy();

		global $wp_rewrite;
		$this->permalink_structure = $wp_rewrite->permalink_structure;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$this->cookies = $_COOKIE;
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
		wp_set_current_user( 0 );

		$this->episode_id = self::factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => 'publish',
				'post_title'  => self::TITLE,
				'post_name'   => 'compact-playlist-episode',
			)
		);
		update_post_meta( $this->episode_id, 'audio_file', self::AUDIO );
	}

	protected function tearDown(): void {
		if ( $this->passthrough_filter ) {
			remove_filter( 'ssp_episode_passthrough_required', $this->passthrough_filter );
		}
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( $this->permalink_structure );
		$_COOKIE = $this->cookies;
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	public function passthrough_cases() {
		return array(
			'not required' => array( false ),
			'required'     => array( true ),
		);
	}

	/** @dataProvider passthrough_cases */
	public function test_compact_playlist_uses_expected_track_url( $required ) {
		$this->set_passthrough_required( $required );
		$tracks = $this->compact_tracks( $this->render_playlist( 'compact' ) );

		$this->assertCount( 1, $tracks );
		$this->assertSame( $this->expected_url( $required ), $tracks[0]['src'] );
	}

	/** @dataProvider passthrough_cases */
	public function test_compact_playlist_uses_expected_track_url_with_plain_permalinks( $required ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '' );
		$this->set_passthrough_required( $required );
		$tracks = $this->compact_tracks( $this->render_playlist( 'compact' ) );
		$expected = $required ? home_url( '/?podcast_episode=' . $this->episode_id ) : self::AUDIO;

		$this->assertCount( 1, $tracks );
		$this->assertSame( $expected, $tracks[0]['src'] );
	}

	public function test_compact_track_rejects_javascript_url_in_json_and_noscript() {
		$this->set_passthrough_required( false );
		update_post_meta( $this->episode_id, 'audio_file', 'javascript:alert(1)' );
		$output = $this->render_playlist( 'compact' );
		$tracks = $this->compact_tracks( $output );

		$this->assertCount( 1, $tracks );
		$this->assertSame( '', $tracks[0]['src'] );
		$this->assertSame( 1, preg_match( '~<noscript>(.*?)</noscript>~s', $output, $matches ) );
		$this->assertStringNotContainsString( 'javascript:', strtolower( $matches[1] ) );
	}

	public function test_compact_track_preserves_file_url_query_in_json_and_noscript() {
		$this->set_passthrough_required( false );
		$url = self::AUDIO . '?token=a&b=1';
		update_post_meta( $this->episode_id, 'audio_file', $url );
		$output = $this->render_playlist( 'compact' );
		$tracks = $this->compact_tracks( $output );

		$this->assertCount( 1, $tracks );
		$this->assertSame( $url, $tracks[0]['src'] );
		$this->assertSame( 1, preg_match( '~<noscript>(.*?)</noscript>~s', $output, $matches ) );
		$this->assertSame( 1, preg_match( '~<li>(.*?)</li>~s', $matches[1], $item ) );
		$this->assertSame( $url, html_entity_decode( trim( $item[1] ), ENT_QUOTES, 'UTF-8' ) );
	}

	/** @dataProvider passthrough_cases */
	public function test_inaccessible_compact_track_has_no_url_in_json_or_noscript( $required ) {
		$this->set_passthrough_required( $required );
		wp_update_post( array( 'ID' => $this->episode_id, 'post_password' => 'secret' ) );
		$output = $this->render_playlist( 'compact' );
		$tracks = $this->compact_tracks( $output );

		$this->assertCount( 1, $tracks );
		$this->assertSame( '', $tracks[0]['src'] );
		$this->assertSame( 1, preg_match( '~<noscript>(.*?)</noscript>~s', $output, $matches ) );
		$this->assertSame( 1, preg_match( '~<li>(.*?)</li>~s', $matches[1], $item ) );
		$this->assertSame( self::TITLE, trim( $item[1] ) );
		$this->assertStringNotContainsString( self::AUDIO, $matches[1] );
		$this->assertStringNotContainsString( $this->expected_url( true ), $matches[1] );
	}

	/** @dataProvider passthrough_cases */
	public function test_default_playlist_and_single_player_keep_their_urls( $required ) {
		$this->set_passthrough_required( $required );
		$expected = $this->expected_url( $required );
		$playlist = $this->render_playlist( 'standard' );
		$single   = ssp_frontend_controller()->players_controller->render_media_player( $this->episode_id );

		$this->assertSame( 2, preg_match_all( '~<source\b[^>]*\bsrc="([^"]*)"~i', $playlist, $sources ) );
		$this->assertSame( array( $expected, $expected ), array_map( 'html_entity_decode', $sources[1] ) );
		$this->assertSame( 1, preg_match( '~<source\b[^>]*\bsrc="([^"]*)"~i', $single, $source ) );
		$single_url = html_entity_decode( $source[1] );
		$this->assertMatchesRegularExpression( '~^' . preg_quote( $expected, '~' ) . '\\?_=\d+$~', $single_url );
	}

	private function set_passthrough_required( $required ) {
		$this->passthrough_filter = static function () use ( $required ) {
			return $required;
		};
		add_filter( 'ssp_episode_passthrough_required', $this->passthrough_filter );
	}

	private function expected_url( $required ) {
		if ( ! $required ) {
			return self::AUDIO;
		}
		return home_url( '/podcast-player/' . $this->episode_id . '/compact-playlist-episode.mp3' );
	}

	private function render_playlist( $style ) {
		return ( new Podcast_Playlist() )->shortcode(
			array(
				'episodes'     => (string) $this->episode_id,
				'player_style' => $style,
				'images'       => false,
			)
		);
	}

	private function compact_tracks( $output ) {
		$this->assertSame( 1, preg_match( '~<script type="application/json" class="wp-playlist-script">(.*?)</script>~s', $output, $matches ) );
		$data = json_decode( $matches[1], true );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'tracks', $data );
		return $data['tracks'];
	}
}
