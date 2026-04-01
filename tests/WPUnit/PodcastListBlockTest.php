<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Presenters\Episode_List_Presenter;

/**
 * Tests for Episode_List_Presenter — the shared rendering service
 * used by the podcast-list block and [ssp_episode_list] shortcode.
 *
 * @package SeriouslySimplePodcasting\Tests
 * @since 3.15.0
 */
class PodcastListBlockTest extends \Codeception\TestCase\WPTestCase
{
	/**
	 * @var Episode_List_Presenter
	 */
	protected $renderer;

	protected function setUp(): void
	{
		parent::setUp();

		$this->renderer = new Episode_List_Presenter(
			ssp_get_service( 'episode_repository' ),
			ssp_get_service( 'players_controller' ),
			ssp_get_service( 'renderer' )
		);
	}

	/**
	 * Helper: create a published podcast episode with an audio file.
	 *
	 * @param string $title   Episode title.
	 * @param array  $series  Optional term IDs to assign.
	 *
	 * @return int Post ID.
	 */
	protected function create_episode( $title = 'Test Episode', $series = array() ) {
		$episode_id = $this->factory()->post->create( array(
			'post_type'   => 'podcast',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_excerpt' => 'Excerpt for ' . $title,
		) );

		update_post_meta( $episode_id, 'audio_file', 'https://example.com/episode.mp3' );

		if ( ! empty( $series ) ) {
			wp_set_post_terms( $episode_id, $series, 'series' );
		}

		return $episode_id;
	}

	/**
	 * Returns default block attributes matching the registered defaults.
	 *
	 * @param array $overrides Attributes to override.
	 *
	 * @return array
	 */
	protected function get_default_attributes( $overrides = array() ) {
		return array_merge( array(
			'showTitle'          => true,
			'featuredImage'      => true,
			'featuredImageSize'  => 'full',
			'excerpt'            => false,
			'player'             => false,
			'playerBelowExcerpt' => false,
			'selectedPodcast'    => '-1',
			'postsPerPage'       => 0,
			'orderBy'            => 'date',
			'order'              => 'desc',
			'columnsPerRow'      => 1,
			'titleSize'          => 16,
			'titleUnderImage'    => false,
		), $overrides );
	}

	// =========================================================================
	// Render callback — HTML structure
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderOutputContainsListWrapper()
	{
		$this->create_episode( 'Wrapper Test' );

		$output = $this->renderer->render(
			$this->get_default_attributes()
		);

		$this->assertStringContainsString( 'ssp-podcast-list', $output );
		$this->assertStringContainsString( 'ssp-podcast-list__articles', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderOutputContainsEpisodeArticle()
	{
		$episode_id = $this->create_episode( 'Article Test' );

		$output = $this->renderer->render(
			$this->get_default_attributes()
		);

		$this->assertStringContainsString( '<article', $output );
		$this->assertStringContainsString( 'podcast-' . $episode_id, $output );
		$this->assertStringContainsString( 'Article Test', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderOutputContainsTitleLink()
	{
		$episode_id = $this->create_episode( 'Title Link Test' );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'showTitle' => true ) )
		);

		$this->assertStringContainsString( 'entry-title-link', $output );
		$this->assertStringContainsString( 'Title Link Test', $output );
		$this->assertStringContainsString( get_permalink( $episode_id ), $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderOutputHidesTitleWhenDisabled()
	{
		$this->create_episode( 'Hidden Title' );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'showTitle' => false ) )
		);

		$this->assertStringNotContainsString( 'entry-title-link', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderOutputContainsExcerptWhenEnabled()
	{
		$this->create_episode( 'Excerpt Test' );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'excerpt' => true ) )
		);

		$this->assertStringContainsString( 'Excerpt for Excerpt Test', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderOutputHidesExcerptWhenDisabled()
	{
		$this->create_episode( 'No Excerpt' );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'excerpt' => false ) )
		);

		$this->assertStringNotContainsString( 'Excerpt for No Excerpt', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderOutputContainsCssVariables()
	{
		$this->create_episode( 'CSS Vars Test' );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'titleSize' => 48, 'columnsPerRow' => 2 ) )
		);

		$this->assertStringContainsString( '--ssp-episode-list-title-size: 48px', $output );
		$this->assertStringContainsString( '--ssp-episode-list-cols: 2', $output );
	}

	// =========================================================================
	// Query building — series filtering
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderShowsAllEpisodesWhenSelectedPodcastIsMinusOne()
	{
		$series_a = $this->factory()->term->create( array( 'taxonomy' => 'series', 'name' => 'Series A' ) );
		$series_b = $this->factory()->term->create( array( 'taxonomy' => 'series', 'name' => 'Series B' ) );

		$this->create_episode( 'Episode A', array( $series_a ) );
		$this->create_episode( 'Episode B', array( $series_b ) );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'selectedPodcast' => '-1' ) )
		);

		$this->assertStringContainsString( 'Episode A', $output );
		$this->assertStringContainsString( 'Episode B', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderFiltersBySeriesWhenSelected()
	{
		$series_a = $this->factory()->term->create( array( 'taxonomy' => 'series', 'name' => 'Series A' ) );
		$series_b = $this->factory()->term->create( array( 'taxonomy' => 'series', 'name' => 'Series B' ) );

		$this->create_episode( 'Episode A', array( $series_a ) );
		$this->create_episode( 'Episode B', array( $series_b ) );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'selectedPodcast' => strval( $series_a ) ) )
		);

		$this->assertStringContainsString( 'Episode A', $output );
		$this->assertStringNotContainsString( 'Episode B', $output );
	}

	// =========================================================================
	// Query building — ordering
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderOrdersByDateDesc()
	{
		$this->create_episode( 'Older Episode' );
		sleep( 1 ); // Ensure different post_date.
		$this->create_episode( 'Newer Episode' );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'orderBy' => 'date', 'order' => 'desc' ) )
		);

		$pos_newer = strpos( $output, 'Newer Episode' );
		$pos_older = strpos( $output, 'Older Episode' );

		$this->assertLessThan( $pos_older, $pos_newer, 'Newer episode should appear before older in desc order.' );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderOrdersByTitleAsc()
	{
		$this->create_episode( 'Banana Episode' );
		$this->create_episode( 'Apple Episode' );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'orderBy' => 'title', 'order' => 'asc' ) )
		);

		$pos_apple  = strpos( $output, 'Apple Episode' );
		$pos_banana = strpos( $output, 'Banana Episode' );

		$this->assertLessThan( $pos_banana, $pos_apple, 'Apple should appear before Banana in asc title order.' );
	}

	// =========================================================================
	// Query building — pagination
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderShowsPaginationWhenMoreEpisodesThanPerPage()
	{
		for ( $i = 1; $i <= 3; $i++ ) {
			$this->create_episode( 'Episode ' . $i );
		}

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'postsPerPage' => '2' ) )
		);

		$this->assertStringContainsString( 'ssp-podcast-list__pagination', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderShowsEmptyPaginationWhenAllEpisodesFit()
	{
		$this->create_episode( 'Only Episode' );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'postsPerPage' => '10' ) )
		);

		// Pagination wrapper is omitted entirely when all episodes fit on one page.
		$this->assertStringNotContainsString( 'ssp-podcast-list__pagination', $output );
		$this->assertStringNotContainsString( 'class="next', $output );
		$this->assertStringNotContainsString( 'class="prev', $output );
	}

	// =========================================================================
	// Query building — audio_file meta query
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderExcludesEpisodesWithoutAudioFile()
	{
		$this->create_episode( 'Has Audio' );

		// Create episode without audio_file.
		$no_audio = $this->factory()->post->create( array(
			'post_type'   => 'podcast',
			'post_status' => 'publish',
			'post_title'  => 'No Audio',
		) );

		$output = $this->renderer->render(
			$this->get_default_attributes()
		);

		$this->assertStringContainsString( 'Has Audio', $output );
		$this->assertStringNotContainsString( 'No Audio', $output );
	}

	// =========================================================================
	// Edge cases
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderShowsEmptyMessageWhenNoEpisodes()
	{
		$output = $this->renderer->render(
			$this->get_default_attributes()
		);

		$this->assertStringContainsString( 'Sorry, episodes not found', $output );
		$this->assertStringNotContainsString( 'ssp-podcast-list__articles', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderHandlesInvalidSeriesId()
	{
		$this->create_episode( 'Valid Episode' );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'selectedPodcast' => '999999' ) )
		);

		// Invalid series — no episodes should match.
		$this->assertStringNotContainsString( 'Valid Episode', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter::render()
	 */
	public function testRenderHandlesInvalidOrderBy()
	{
		$this->create_episode( 'Fallback Order Test' );

		$output = $this->renderer->render(
			$this->get_default_attributes( array( 'orderBy' => 'invalid_column' ) )
		);

		// Should fall back to 'date' ordering and still render.
		$this->assertStringContainsString( 'Fallback Order Test', $output );
	}
}
