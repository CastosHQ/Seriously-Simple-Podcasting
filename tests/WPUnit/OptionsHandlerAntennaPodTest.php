<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Options_Handler;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;

class OptionsHandlerAntennaPodTest extends \Codeception\TestCase\WPTestCase {

	private $episode_id;
	private $series_id;
	private $url = 'https://example.com/antennapod-subscribe';

	protected function setUp(): void {
		parent::setUp();

		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		ssp_app()->series_controller->register_taxonomy();

		$this->series_id = self::factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		$this->episode_id = self::factory()->post->create( array( 'post_type' => SSP_CPT_PODCAST ) );
		wp_set_object_terms( $this->episode_id, array( $this->series_id ), ssp_series_taxonomy() );
		update_option( 'ss_podcasting_antennapod_url_' . $this->series_id, $this->url );
	}

	public function testEnabledAntennaPodReturnsSeriesSubscribeDetails() {
		update_option( 'ss_podcasting_subscribe_options', array( 'antennapod' ) );

		$urls = ( new Options_Handler() )->get_subscribe_urls( $this->episode_id, 'player' );

		$this->assertArrayHasKey( 'antennapod', $urls );
		$this->assertSame( $this->url, $urls['antennapod']['url'] );
		$this->assertSame( 'AntennaPod', $urls['antennapod']['label'] );
		$this->assertSame( 'antennapod', $urls['antennapod']['class'] );
		$this->assertSame( 'antennapod.png', $urls['antennapod']['icon'] );
	}

	public function testAntennaPodAssetsAndPlayerCssExist() {
		$plugin_dir = dirname( __DIR__, 2 );
		$css_path   = $plugin_dir . '/assets/css/castos-player.css';

		$this->assertFileExists( $plugin_dir . '/assets/icons/subscribe/antennapod.png' );
		$this->assertFileExists( $plugin_dir . '/assets/css/images/player/images/brands/antennapod.svg' );
		$this->assertFileExists( $css_path );
		$this->assertMatchesRegularExpression(
			'~\.castos-player\s+\.subscribe-icons\s+\.antennapod\s+span\s*\{[^}]*\bmask-image\s*:\s*url\(\s*[\'"]?images/player/images/brands/antennapod\.svg[\'"]?\s*\)~',
			file_get_contents( $css_path )
		);
	}

	public function testEnabledAntennaPodAddsOneUrlFieldForEachSeries() {
		update_option( 'ss_podcasting_subscribe_options', array( 'antennapod' ) );
		$other_series_id = self::factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		$other_url       = 'https://example.com/other-antennapod-subscribe';
		update_option( 'ss_podcasting_antennapod_url_' . $other_series_id, $other_url );
		$handler = new Settings_Handler();

		foreach ( array( $this->series_id => $this->url, $other_series_id => $other_url ) as $series_id => $url ) {
			$fields = $handler->get_subscribe_field_options( $series_id );
			$matches = array_values( array_filter( $fields, function ( $field ) {
				return 'antennapod_url' === $field['id'];
			} ) );

			$this->assertCount( 1, $matches );
			$this->assertStringContainsString( 'AntennaPod', $matches[0]['label'] );
			$this->assertSame( $url, $matches[0]['default'] );
		}
	}

	public function testDisabledAntennaPodHasNoUrlField() {
		update_option( 'ss_podcasting_subscribe_options', array( 'spotify' ) );
		$fields = ( new Settings_Handler() )->get_subscribe_field_options( $this->series_id );
		$ids    = array_column( $fields, 'id' );

		$this->assertContains( 'spotify_url', $ids );
		$this->assertNotContains( 'antennapod_url', $ids );
	}

	public function testStoredAntennaPodUrlIsAbsentWhenNotEnabled() {
		update_option( 'ss_podcasting_subscribe_options', array( 'spotify' ) );

		$urls = ( new Options_Handler() )->get_subscribe_urls( $this->episode_id, 'player' );

		$this->assertArrayHasKey( 'spotify', $urls );
		$this->assertArrayNotHasKey( 'antennapod', $urls );
	}
}
