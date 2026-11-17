<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Integrations\Blocks\Playlist_Player_Block;
use SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist;
use SeriouslySimplePodcasting\Widgets\Playlist;

/**
 * Tests for the episode query attributes of the playlist, and the paging data
 * the castos player sends back for infinite scroll.
 *
 * @package SeriouslySimplePodcasting\Tests
 * @since 3.18.0
 */
class PodcastPlaylistQueryAttsTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \SeriouslySimplePodcasting\Repositories\Episode_Repository
	 */
	private $episode_repository;

	/**
	 * @var int[]
	 */
	private $episode_ids = array();

	protected function setUp(): void {
		parent::setUp();

		// The test teardown unregisters custom post types after each test.
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();

		$this->episode_repository = ssp_get_service( 'episode_repository' );

		if ( ! shortcode_exists( 'podcast_playlist' ) ) {
			add_shortcode( 'podcast_playlist', array( new Podcast_Playlist(), 'shortcode' ) );
		}

		if ( ! wp_script_is( 'ssp-castos-player', 'registered' ) ) {
			ssp_frontend_controller()->players_controller->register_player_assets();
		}

		// Start each test without player data from earlier renders.
		wp_scripts()->add_data( 'ssp-castos-player', 'data', '' );

		$this->episode_ids = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			$episode_id = $this->factory()->post->create(
				array(
					'post_type'   => SSP_CPT_PODCAST,
					'post_status' => 'publish',
					'post_title'  => 'Playlist paging episode ' . $i,
					'post_date'   => sprintf( '2024-01-0%d 10:00:00', $i ),
				)
			);
			update_post_meta( $episode_id, 'audio_file', 'https://example.com/episode-' . $i . '.mp3' );

			$this->episode_ids[] = $episode_id;
		}
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist::get_query_atts()
	 */
	public function test_get_query_atts_drops_unknown_keys_and_keeps_known_values() {
		$atts = array(
			'colour'    => 'blue',
			'style'     => 'dark',
			'class'     => 'wide',
			'tracklist' => true,
			'series'    => 'news',
			'limit'     => 5,
			'order'     => 'DESC',
			'include'   => array( 1, 2 ),
		);

		$expected = array(
			'series'  => 'news',
			'tag'     => '',
			'order'   => 'DESC',
			'orderby' => 'menu_order ID',
			'include' => array( 1, 2 ),
			'exclude' => '',
			'limit'   => 5,
		);

		$this->assertSame( $expected, Podcast_Playlist::get_query_atts( $atts ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist::get_query_atts()
	 */
	public function test_get_query_atts_fills_missing_keys_with_defaults() {
		$expected = array(
			'series'  => '',
			'tag'     => '',
			'order'   => 'ASC',
			'orderby' => 'menu_order ID',
			'include' => '',
			'exclude' => '',
			'limit'   => 10,
		);

		$this->assertSame( $expected, Podcast_Playlist::get_query_atts( array() ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist::get_query_atts()
	 */
	public function test_get_query_atts_ignores_only_unknown_keys() {
		$atts = Podcast_Playlist::get_query_atts( array( 'colour' => 'blue', 'size' => 'large' ) );

		$this->assertSame( array( 'series', 'tag', 'order', 'orderby', 'include', 'exclude', 'limit' ), array_keys( $atts ) );
		$this->assertSame( 10, $atts['limit'] );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist::shortcode()
	 */
	public function test_shortcode_playlist_next_page_returns_a_different_episode() {
		$output = do_shortcode( '[podcast_playlist limit="1"]' );

		$this->assertNotSame( '', $output );
		$this->assert_next_page_differs_from_first_page();
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Blocks\Playlist_Player_Block::render_callback()
	 */
	public function test_block_playlist_next_page_returns_a_different_episode() {
		$block  = new Playlist_Player_Block();
		$output = $block->render_callback(
			array(
				'selectedPodcast' => '-1',
				'selectedTag'     => '',
				'limit'           => '1',
				'orderBy'         => 'date',
				'order'           => 'desc',
			)
		);

		$this->assertNotSame( '', $output );
		$this->assert_next_page_differs_from_first_page();
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Widgets\Playlist::get_widget_body()
	 */
	public function test_widget_playlist_next_page_returns_a_different_episode() {
		$widget = new Playlist();

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '<div>',
				'after_widget'  => '</div>',
				'before_title'  => '<h2>',
				'after_title'   => '</h2>',
				'widget_id'     => 'podcast-playlist-test',
			),
			array(
				'title' => '',
				'limit' => 1,
			)
		);
		$output = ob_get_clean();

		$this->assertNotSame( '', $output );
		$this->assert_next_page_differs_from_first_page();
	}

	/**
	 * Uses the attributes localized for the castos player, as the player sends them
	 * back, to check that page 2 holds exactly one episode that is not on page 1.
	 */
	private function assert_next_page_differs_from_first_page() {
		$localized = $this->get_localized_player_data();

		$this->assertIsArray( $localized, 'The castos player data was not localized.' );
		$this->assertArrayHasKey( 'atts', $localized );
		$this->assertSame( 'standard', $localized['atts']['player_style'] );

		// Simulate the AJAX round trip.
		$atts = json_decode( wp_json_encode( $localized['atts'] ), true );
		$atts = Podcast_Playlist::get_query_atts( $atts );

		$first_page  = $this->episode_repository->get_episodes( array_merge( $atts, array( 'page' => 1 ) ) );
		$second_page = $this->episode_repository->get_episodes( array_merge( $atts, array( 'page' => 2 ) ) );

		$this->assertCount( 1, $first_page );
		$this->assertCount( 1, $second_page );
		$this->assertNotSame( $first_page[0]->ID, $second_page[0]->ID );
		$this->assertContains( $second_page[0]->ID, $this->episode_ids );
	}

	/**
	 * Reads the data the last rendered castos player localized for its script.
	 *
	 * @return array|null
	 */
	private function get_localized_player_data() {
		$data = wp_scripts()->get_data( 'ssp-castos-player', 'data' );

		if ( ! is_string( $data ) ) {
			return null;
		}

		$matches = array();
		if ( ! preg_match_all( '/var ssp_castos_player_\d+ = (.*);/', $data, $matches ) ) {
			return null;
		}

		return json_decode( end( $matches[1] ), true );
	}
}
