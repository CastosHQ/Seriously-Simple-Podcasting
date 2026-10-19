<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist;

/**
 * Tests for the [podcast_playlist] shortcode's markup-selecting attributes.
 *
 * @package SeriouslySimplePodcasting\Tests
 * @since 3.17.1
 */
class PodcastPlaylistShortcodeTest extends \Codeception\TestCase\WPTestCase
{
	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist::prepare_atts()
	 *
	 * @since 3.17.1
	 */
	public function testPlaylistTypeWhitelistNormalizesValuesAndFallsBackToAudio()
	{
		$normalized_types = array(
			'Video'   => 'video',
			' VIDEO ' => 'video',
		);

		foreach ( $normalized_types as $type => $expected_type ) {
			$atts = $this->prepareAttributes( array( 'type' => $type ) );

			$this->assertSame( $expected_type, $atts['type'] );
		}

		$invalid_types = array(
			'span class=sample',
			'audio data-format=sample',
			'video data-format=sample',
		);

		foreach ( $invalid_types as $invalid_type ) {
			$atts = $this->prepareAttributes( array( 'type' => $invalid_type ) );

			$this->assertSame( 'audio', $atts['type'], 'Invalid playlist types must fall back to audio.' );
		}
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist::prepare_atts()
	 *
	 * @since 3.17.1
	 */
	public function testPlaylistTypeWhitelistClampsShortcodeAttributesFilterOutput()
	{
		$filter = static function ( $atts ) {
			$atts['type'] = 'span class=sample';

			return $atts;
		};
		add_filter( 'shortcode_atts_podcast_playlist', $filter );

		$atts = $this->prepareAttributes( array( 'type' => 'video' ) );

		remove_filter( 'shortcode_atts_podcast_playlist', $filter );

		$this->assertSame( 'audio', $atts['type'] );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist::prepare_atts()
	 *
	 * @since 3.17.1
	 */
	public function testPlaylistStyleWhitelistNormalizesAndFallsBackToDarkDefault()
	{
		$dark_atts = $this->prepareAttributes( array( 'style' => 'Dark' ) );
		$this->assertSame( 'dark', $dark_atts['style'] );

		$filter = static function () {
			return 'dark';
		};
		add_filter( 'pre_option_ss_podcasting_player_mode', $filter );

		$atts = $this->prepareAttributes( array( 'style' => 'dark data-theme=sample' ) );

		remove_filter( 'pre_option_ss_podcasting_player_mode', $filter );

		$this->assertSame( 'dark', $atts['style'] );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist::prepare_atts()
	 *
	 * @since 3.17.1
	 */
	public function testPlaylistStyleWhitelistFallsBackToLightForInvalidOption()
	{
		$filter = static function () {
			return 'dark data-theme=sample';
		};
		add_filter( 'pre_option_ss_podcasting_player_mode', $filter );

		$atts = $this->prepareAttributes( array( 'style' => 'invalid' ) );

		remove_filter( 'pre_option_ss_podcasting_player_mode', $filter );

		$this->assertSame( 'light', $atts['style'] );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist::shortcode()
	 *
	 * @since 3.17.1
	 */
	public function testCompactPlaylistUsesPlainAudioTagForInvalidType()
	{
		$output = $this->renderCompactPlaylist( array( 'type' => 'span class=sample' ) );

		$this->assertDoesNotMatchRegularExpression( '/<\s*span\b/i', $output );
		$this->assertDoesNotMatchRegularExpression( '/\bclass\s*=\s*["\']?sample\b/i', $output );
		$this->assertMatchesRegularExpression( '/<audio\b[^>]*controls="controls"/i', $output );
		$this->assertStringContainsString( '</audio>', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Controllers\Players_Controller::render_playlist_compact_player()
	 *
	 * @since 3.17.1
	 */
	public function testPlayersControllerUsesPlainAudioTagForInvalidType()
	{
		$output = ssp_frontend_controller()->players_controller->render_playlist_compact_player(
			array(
				array(
					'src' => 'https://example.com/episode.mp3',
				),
			),
			array(
				'type'         => 'span class=sample',
				'style'        => 'light',
				'class'        => '',
				'tracklist'    => true,
				'tracknumbers' => true,
				'images'       => false,
			),
			640,
			360
		);

		$media_tags = array();
		preg_match_all( '/<(?P<tag>audio|video|span)\b[^>]*>/i', $output, $media_tags );

		$this->assertCount( 1, $media_tags[0] );
		$this->assertSame( 'audio', strtolower( $media_tags['tag'][0] ) );
		$this->assertMatchesRegularExpression( '/^<audio\b[^>]*controls="controls"/i', $media_tags[0][0] );
		$this->assertStringNotContainsString( 'class=sample', $media_tags[0][0] );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist::shortcode()
	 *
	 * @since 3.17.1
	 */
	public function testCompactPlaylistPreservesVideoTagAndDarkStyle()
	{
		$output = $this->renderCompactPlaylist(
			array(
				'type'  => 'Video',
				'style' => 'dark',
			)
		);

		$this->assertStringContainsString( 'wp-video-playlist', $output );
		$this->assertStringContainsString( 'wp-playlist-dark', $output );
		$this->assertMatchesRegularExpression( '/<video\b[^>]*controls="controls"/i', $output );
		$this->assertStringContainsString( '</video>', $output );
	}

	/**
	 * Invoke the shortcode's attribute preparation so the whitelist can be
	 * tested without rendering a playlist.
	 *
	 * @param array $attributes Shortcode attributes.
	 *
	 * @return array
	 *
	 * @since 3.17.1
	 */
	protected function prepareAttributes( array $attributes )
	{
		$shortcode = new Podcast_Playlist();
		$method    = new \ReflectionMethod( $shortcode, 'prepare_atts' );
		$method->setAccessible( true );

		return $method->invoke( $shortcode, $attributes );
	}

	/**
	 * Render a compact playlist through the shortcode entry point.
	 *
	 * @param array $attributes Shortcode attributes.
	 *
	 * @return string
	 *
	 * @since 3.17.1
	 */
	protected function renderCompactPlaylist( array $attributes )
	{
		$episode_id = $this->factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => 'publish',
				'post_title'  => 'Playlist test episode',
			)
		);
		update_post_meta( $episode_id, 'audio_file', 'https://example.com/episode.mp3' );

		$attributes = array_merge(
			array(
				'include'      => (string) $episode_id,
				'images'       => false,
				'player_style' => 'compact',
			),
			$attributes
		);

		return ( new Podcast_Playlist() )->shortcode( $attributes );
	}
}
