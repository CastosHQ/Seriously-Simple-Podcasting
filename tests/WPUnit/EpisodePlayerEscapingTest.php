<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Players_Controller;

/**
 * Verifies escaping of episode duration and formatted dates in players.
 *
 * @since 3.17.1
 */
class EpisodePlayerEscapingTest extends \Codeception\TestCase\WPTestCase {

	/** @var Players_Controller */
	private $controller;

	/** @var array */
	private $options_backup;

	/** @var array */
	private $asset_backup;

	/** @var int */
	private $user_backup;

	protected function setUp(): void {
		parent::setUp();

		$this->user_backup = get_current_user_id();
		wp_set_current_user( 0 );
		$this->options_backup = array();
		foreach ( array(
			'ss_podcasting_use_post_types'                => array(),
			'ss_podcasting_duration_enabled'              => 'on',
			'ss_podcasting_date_recorded_enabled'         => 'on',
			'ss_podcasting_download_file_enabled'         => 'off',
			'ss_podcasting_play_in_new_window_enabled'    => 'off',
			'ss_podcasting_player_subscribe_urls_enabled' => 'off',
			'date_format'                                => 'Y-m-d',
		) as $option => $value ) {
			$this->options_backup[ $option ] = get_option( $option, null );
			update_option( $option, $value );
		}

		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		ssp_app()->series_controller->register_taxonomy();
		$this->controller   = ssp_app()->players_controller;
		$this->asset_backup = array(
			'scripts' => wp_scripts()->queue,
			'styles'  => wp_styles()->queue,
			'extra'   => isset( wp_scripts()->registered['ssp-castos-player'] ) ? wp_scripts()->registered['ssp-castos-player']->extra : null,
		);
	}

	protected function tearDown(): void {
		wp_set_current_user( $this->user_backup );
		foreach ( $this->options_backup as $option => $value ) {
			null === $value ? delete_option( $option ) : update_option( $option, $value );
		}
		wp_scripts()->queue = $this->asset_backup['scripts'];
		wp_styles()->queue  = $this->asset_backup['styles'];
		if ( null !== $this->asset_backup['extra'] && isset( wp_scripts()->registered['ssp-castos-player'] ) ) {
			wp_scripts()->registered['ssp-castos-player']->extra = $this->asset_backup['extra'];
		}

		parent::tearDown();
	}

	/** @dataProvider duration_text_provider */
	public function testDurationIsEscapedInEpisodeMeta( $text ) {
		$episode_id = $this->create_episode( $text );
		$html       = $this->controller->episode_meta_details( $episode_id );
		$xpath      = $this->parse_html( $html );
		$duration   = $this->find_one( $xpath, '//span[@class="podcast-meta-duration"]' );

		$this->assertSame( 'Duration: ' . $text, $duration->textContent );
		$this->assertCount( 0, $duration->getElementsByTagName( '*' ), 'Duration must print as text, not HTML.' );
		$this->assertCount( 0, $xpath->query( '//em' ) );
	}

	public static function duration_text_provider() {
		return array(
			'formatted text' => array( '00:01<em>duration</em>' ),
		);
	}

	public function testRecordedDateIsEscapedAfterFormatting() {
		$episode_id = $this->create_episode( '01:02:03' );
		update_post_meta( $episode_id, 'date_recorded', '2026-10-15' );
		$text = '<span class="date">today</span> & <em>recorded</em>';
		// The stored date goes through strtotime(); exercise escaping of the formatted result.
		$filter = static function () use ( $text ) {
			return $text;
		};
		add_filter( 'date_i18n', $filter );
		try {
			$html = $this->controller->episode_meta_details( $episode_id );
		} finally {
			remove_filter( 'date_i18n', $filter );
		}
		$xpath = $this->parse_html( $html );
		$date  = $this->find_one( $xpath, '//span[@class="podcast-meta-date"]' );

		$this->assertSame( 'Recorded on ' . $text, $date->textContent );
		$this->assertCount( 0, $date->getElementsByTagName( '*' ) );
		$this->assertCount( 0, $xpath->query( '//span[@class="date"] | //em' ) );
		$this->assertStringContainsString( '&lt;span', $html );
		$this->assertStringContainsString( '&amp;', $html );
	}

	/** @dataProvider playlist_text_provider */
	public function testPlaylistDateAndDurationAreEscaped( $date, $duration ) {
		$episode_id = $this->create_episode( $duration );
		// Playlist dates come from the post date, with a public player-data filter.
		$filter = static function ( $data ) use ( $episode_id, $date ) {
			if ( isset( $data['episode_id'] ) && $episode_id === $data['episode_id'] ) {
				$data['date'] = $date;
			}
			return $data;
		};
		add_filter( 'ssp_html_player_data', $filter );
		try {
			$html = $this->render_playlist( $episode_id );
		} finally {
			remove_filter( 'ssp_html_player_data', $filter );
		}
		$xpath   = $this->parse_html( $html );
		$details = $this->find_one( $xpath, '//div[@class="playlist__item__details"]/p[1]' );

		$this->assertSame( $date . ' • ' . $duration, $details->textContent );
		$this->assertCount( 0, $details->getElementsByTagName( '*' ), 'Playlist date and duration must both print as text.' );
		$this->assertCount( 0, $xpath->query( '//em | //span[@class="duration"]' ) );
	}

	public static function playlist_text_provider() {
		return array(
			'formatted date and duration' => array(
				'<em>recorded</em> & today',
				'00:01<span class="duration">minutes</span>',
			),
		);
	}

	private function create_episode( $duration ) {
		$episode_id = self::factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => 'publish',
				'post_title'  => 'Player escaping episode',
				'post_date'   => '2020-01-02 12:00:00',
			)
		);
		update_post_meta( $episode_id, 'audio_file', 'https://example.com/episode.mp3' );
		// Avoid file-size discovery; the tests exercise rendering, not remote metadata.
		update_post_meta( $episode_id, 'filesize', '1 MB' );
		update_post_meta( $episode_id, 'filesize_raw', '1048576' );
		update_post_meta( $episode_id, 'duration', $duration );

		return $episode_id;
	}

	private function render_playlist( $episode_id ) {
		return $this->controller->render_playlist_player(
			array( get_post( $episode_id ) ),
			array( 'class' => '', 'style' => 'light' )
		);
	}

	private function parse_html( $html ) {
		$document = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		try {
			$loaded = $document->loadHTML( '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>', LIBXML_NONET );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
		}
		$this->assertTrue( $loaded );

		return new \DOMXPath( $document );
	}

	private function find_one( $xpath, $query ) {
		$nodes = $xpath->query( $query );
		$this->assertCount( 1, $nodes, $query );

		return $nodes->item( 0 );
	}
}
