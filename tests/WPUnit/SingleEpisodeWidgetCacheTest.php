<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Widgets\Single_Episode;

/**
 * Covers the Single Episode widget under persistent object caching (#883):
 * the "Latest episode" setting must follow newly published episodes, and the
 * styled player's assets must load on every page load.
 */
class SingleEpisodeWidgetCacheTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WP_Scripts|null
	 */
	protected $original_scripts;

	/**
	 * @var \WP_Styles|null
	 */
	protected $original_styles;

	protected function setUp(): void {
		parent::setUp();

		delete_option( 'ss_podcasting_use_post_types' );
		wp_cache_flush();

		$this->original_scripts = $GLOBALS['wp_scripts'] ?? null;
		$this->original_styles  = $GLOBALS['wp_styles'] ?? null;
	}

	protected function tearDown(): void {
		$GLOBALS['wp_scripts'] = $this->original_scripts;
		$GLOBALS['wp_styles']  = $this->original_styles;

		parent::tearDown();
	}

	/* -------------------------------------------------------------------------
	 * Helpers
	 * ---------------------------------------------------------------------- */

	/**
	 * Creates a published podcast episode with an audio file.
	 */
	protected function create_episode( $post_date ) {
		$post_id = $this->factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => 'publish',
				'post_date'   => $post_date,
			)
		);

		update_post_meta( $post_id, 'audio_file', 'https://example.com/episode-' . $post_id . '.mp3' );

		return $post_id;
	}

	/**
	 * Starts a fresh script and style queue, as on a new page load.
	 */
	protected function reset_asset_queues() {
		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles']  = null;
	}

	/**
	 * Renders the widget set to "Latest episode" with the player shown.
	 */
	protected function render_latest_episode_widget() {
		$widget = new Single_Episode();

		$args = array(
			'widget_id'     => 'single-podcast-episode-1',
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		);

		$instance = array(
			'title'        => '',
			'episode_id'   => 0,
			'show_title'   => true,
			'show_excerpt' => false,
			'show_content' => false,
			'show_player'  => true,
			'show_details' => false,
		);

		ob_start();
		$widget->widget( $args, $instance );

		return ob_get_clean();
	}

	/* -------------------------------------------------------------------------
	 * ssp_episodes()
	 * ---------------------------------------------------------------------- */

	public function test_ssp_episodes_returns_episode_published_after_previous_call() {
		$this->create_episode( '2026-01-01 10:00:00' );
		ssp_episodes( 1 );

		$newer_id = $this->create_episode( '2026-02-01 10:00:00' );
		$episodes = ssp_episodes( 1 );

		$this->assertSame( $newer_id, $episodes[0]->ID );
	}

	public function test_ssp_episodes_returns_results_for_its_own_arguments() {
		$this->create_episode( '2026-01-01 10:00:00' );
		$this->create_episode( '2026-02-01 10:00:00' );
		$this->create_episode( '2026-03-01 10:00:00' );

		$this->assertCount( 1, ssp_episodes( 1 ) );
		$this->assertCount( 3, ssp_episodes( 3 ) );
	}

	public function test_ssp_has_episodes_is_false_without_published_episodes() {
		$this->factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => 'draft',
			)
		);

		$this->assertFalse( ssp_has_episodes() );
	}

	public function test_ssp_has_episodes_is_true_with_a_published_episode() {
		$this->create_episode( '2026-01-01 10:00:00' );

		$this->assertTrue( ssp_has_episodes() );
	}

	/* -------------------------------------------------------------------------
	 * Single Episode widget
	 * ---------------------------------------------------------------------- */

	public function test_widget_shows_episode_published_after_previous_render() {
		$this->create_episode( '2026-01-01 10:00:00' );
		$this->render_latest_episode_widget();

		$newer_id = $this->create_episode( '2026-02-01 10:00:00' );
		$html     = $this->render_latest_episode_widget();

		$this->assertStringContainsString( 'episode-' . $newer_id . '.mp3', $html );
	}

	public function test_widget_enqueues_player_assets_on_every_render() {
		$this->create_episode( '2026-01-01 10:00:00' );

		$this->reset_asset_queues();
		$this->render_latest_episode_widget();
		$this->assertTrue( wp_script_is( 'wp-mediaelement', 'enqueued' ), 'First render' );

		$this->reset_asset_queues();
		$this->render_latest_episode_widget();
		$this->assertTrue( wp_script_is( 'wp-mediaelement', 'enqueued' ), 'Second render' );
	}
}
