<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist;

class PlaylistEpisodeAccessTest extends \Codeception\TestCase\WPTestCase {
	const PASSWORD = 'playlist-password';
	const PROTECTED_AUDIO = 'https://example.com/playlist-protected-marker.mp3';
	const PUBLIC_AUDIO = 'https://example.com/playlist-public-marker.mp3';
	const EXCERPT = 'Playlist protected excerpt marker';
	const BODY = 'Playlist protected body marker';
	const TITLE = 'Playlist protected title marker';

	private $protected_id;
	private $public_id;
	private $series_id;
	private $cookie_backup;
	private $shortcode_backup;
	private $restore_filter;
	private $track_filter;

	protected function setUp(): void {
		parent::setUp();
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		$property = new \ReflectionProperty( 'SeriouslySimplePodcasting\Controllers\App_Controller', 'series_controller' );
		$property->setAccessible( true );
		$property->getValue( ssp_app() )->register_taxonomy();

		$this->cookie_backup = $_COOKIE;
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
		wp_set_current_user( 0 );
		update_option( 'permalink_structure', '' );
		update_option( 'ss_podcasting_share_button_enabled', 'on' );
		$this->series_id = self::factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		$this->public_id = $this->create_episode( 'Playlist public title', self::PUBLIC_AUDIO );
		$this->protected_id = $this->create_episode( self::TITLE, self::PROTECTED_AUDIO, self::PASSWORD );

		global $shortcode_tags;
		$this->shortcode_backup = isset( $shortcode_tags['podcast_playlist'] ) ? $shortcode_tags['podcast_playlist'] : null;
		add_shortcode( 'podcast_playlist', array( new Podcast_Playlist(), 'shortcode' ) );
	}

	protected function tearDown(): void {
		if ( $this->restore_filter ) {
			remove_filter( 'ssp_feed_include_password_protected', $this->restore_filter, 10 );
		}
		if ( $this->track_filter ) {
			remove_filter( 'ssp_podcast_playlist_track_data', $this->track_filter, 10 );
		}
		remove_shortcode( 'podcast_playlist' );
		if ( null !== $this->shortcode_backup ) {
			add_shortcode( 'podcast_playlist', $this->shortcode_backup );
		}
		$_COOKIE = $this->cookie_backup;
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	public function styles() {
		return array( 'standard' => array( 'standard' ), 'compact' => array( 'compact' ) );
	}

	/** @dataProvider styles */
	public function test_anonymous_playlist_retains_titles_and_public_audio_only( $style ) {
		// Exercise the default shortcode and an explicit protected-first playlist.
		$outputs = array(
			$this->render_playlist( $style ),
			$this->render_playlist( $style, true ),
		);
		foreach ( $outputs as $output ) {
			$this->assertStringContainsString( self::TITLE, $output );
			if ( 'compact' === $style ) {
				$this->assertStringContainsString( '"title":' . wp_json_encode( self::TITLE ), $output );
			}
			$this->assertStringContainsString( self::PUBLIC_AUDIO, $output );
			$this->assertStringNotContainsString( self::PROTECTED_AUDIO, $output );
			$this->assertStringNotContainsString( substr( wp_json_encode( self::PROTECTED_AUDIO ), 1, -1 ), $output );
			$this->assertStringNotContainsString( self::EXCERPT, $output );
			$this->assertStringNotContainsString( self::BODY, $output );
		}
	}

	/** @dataProvider styles */
	public function test_password_cookie_retains_playlist_data( $style ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$hasher = new \PasswordHash( 8, true );
		$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = $hasher->HashPassword( self::PASSWORD );
		$this->assert_full_playlist_data( $style );
	}

	/** @dataProvider styles */
	public function test_editor_retains_playlist_data( $style ) {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertTrue( current_user_can( 'edit_post', $this->protected_id ) );
		$this->assert_full_playlist_data( $style, false );
	}

	/** @dataProvider styles */
	public function test_feed_restored_episode_retains_playlist_data( $style ) {
		$series_id = $this->series_id;
		$this->restore_filter = static function ( $include, $id ) use ( $series_id ) {
			return $id === $series_id;
		};
		add_filter( 'ssp_feed_include_password_protected', $this->restore_filter, 10, 2 );
		$this->assert_full_playlist_data( $style, false );
	}

	public function test_compact_track_filter_cannot_restore_inaccessible_data() {
		$this->track_filter = function ( $track, $episode ) {
			if ( $episode->ID === $this->protected_id ) {
				$track['src'] = self::PROTECTED_AUDIO;
				$track['description'] = self::BODY;
				$track['caption'] = self::BODY;
			} elseif ( $episode->ID === $this->public_id ) {
				$track['caption'] = 'Playlist filtered public marker';
			}
			return $track;
		};
		add_filter( 'ssp_podcast_playlist_track_data', $this->track_filter, 10, 2 );
		$output = $this->render_playlist( 'compact', true );
		$this->assertStringContainsString( 'Playlist filtered public marker', $output );
		$this->assertStringContainsString( self::TITLE, $output );
		$this->assertStringContainsString( self::PUBLIC_AUDIO, $output );
		$this->assertStringNotContainsString( self::PROTECTED_AUDIO, $output );
		$this->assertStringNotContainsString( substr( wp_json_encode( self::PROTECTED_AUDIO ), 1, -1 ), $output );
		$this->assertStringNotContainsString( self::EXCERPT, $output );
		$this->assertStringNotContainsString( self::BODY, $output );
	}

	private function create_episode( $title, $audio, $password = '' ) {
		$id = self::factory()->post->create(
			array(
				'post_type' => SSP_CPT_PODCAST,
				'post_status' => 'publish',
				'post_title' => $title,
				'post_password' => $password,
				'post_excerpt' => $password ? self::EXCERPT : 'Public excerpt',
				'post_content' => $password ? self::BODY : 'Public body',
			)
		);
		update_post_meta( $id, 'audio_file', $audio );
		wp_set_object_terms( $id, array( $this->series_id ), ssp_series_taxonomy() );
		return $id;
	}

	private function render_playlist( $style, $protected_first = false ) {
		$attributes = 'compact' === $style ? ' player_style="compact"' : '';
		if ( $protected_first ) {
			$ids = array( $this->protected_id, $this->public_id );
			$attributes .= ' episodes="' . implode( ',', $ids ) . '"';
		}
		return do_shortcode( '[podcast_playlist' . $attributes . ']' );
	}

	private function assert_full_playlist_data( $style, $expect_standard_excerpt = true ) {
		$output = $this->render_playlist( $style, true );
		$this->assertStringContainsString( self::TITLE, $output );
		$this->assertStringContainsString( self::PROTECTED_AUDIO, $output );
		$this->assertStringContainsString( self::PUBLIC_AUDIO, $output );
		if ( 'compact' === $style || $expect_standard_excerpt ) {
			$this->assertStringContainsString( self::EXCERPT, $output );
		}
		if ( 'compact' === $style ) {
			$this->assertStringContainsString( self::BODY, $output );
			$this->assertStringContainsString( substr( wp_json_encode( self::PROTECTED_AUDIO ), 1, -1 ), $output );
			$this->assertMatchesRegularExpression( '~<noscript>.*' . preg_quote( self::PROTECTED_AUDIO, '~' ) . '.*</noscript>~s', $output );
		} else {
			$this->assertMatchesRegularExpression( '~<source src="' . preg_quote( self::PROTECTED_AUDIO, '~' ) . '"~', $output );
			$this->assertMatchesRegularExpression( '~href="' . preg_quote( self::PROTECTED_AUDIO, '~' ) . '"[^>]*class="share-icon download"~s', $output );
		}
	}
}
