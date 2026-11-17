<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist;

/**
 * The playlist series filter applies to text values only; other value types leave the query unfiltered.
 */
class EpisodeRepositoryPlaylistSeriesTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Episode_Repository
	 */
	private $episode_repository;

	/**
	 * @var int
	 */
	private $news_episode_id;

	/**
	 * @var int
	 */
	private $music_episode_id;

	/**
	 * @var int
	 */
	private $unassigned_episode_id;

	protected function setUp(): void {
		parent::setUp();

		// The test teardown unregisters custom post types and taxonomies after each test.
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		ssp_app()->series_controller->register_taxonomy();

		$this->episode_repository = ssp_get_service( 'episode_repository' );

		$this->news_episode_id  = $this->create_episode_in_series( 'news' );
		$this->music_episode_id = $this->create_episode_in_series( 'music' );

		$this->unassigned_episode_id = $this->create_episode_without_series();
	}

	public function test_get_episodes_filters_by_series_slug() {
		$episodes = $this->episode_repository->get_episodes( $this->atts( 'news' ) );

		$this->assertSame( array( $this->news_episode_id ), $this->sorted_ids( $episodes ) );
	}

	public function test_get_episodes_accepts_comma_separated_series_slugs() {
		$episodes = $this->episode_repository->get_episodes( $this->atts( 'news,music' ) );

		$this->assertSame( $this->series_ids(), $this->sorted_ids( $episodes ) );
	}

	public function non_text_series_provider() {
		return array(
			'list'        => array( array( 'news' ) ),
			'list of two' => array( array( 'news', 'music' ) ),
			'number'      => array( 5 ),
			'boolean'     => array( true ),
		);
	}

	/**
	 * @dataProvider non_text_series_provider
	 */
	public function test_get_episodes_ignores_series_filter_for_non_text_value( $series ) {
		$episodes = $this->episode_repository->get_episodes( $this->atts( $series ) );

		$this->assertSame( $this->all_ids(), $this->sorted_ids( $episodes ) );
	}

	/**
	 * Builds the full set of query attributes with the given series value.
	 */
	private function atts( $series ) {
		$atts           = Podcast_Playlist::get_query_atts( array() );
		$atts['series'] = $series;
		$atts['page']   = 1;

		return $atts;
	}

	/**
	 * Creates a published episode with audio in a series with the given slug.
	 */
	private function create_episode_in_series( $slug ) {
		wp_insert_term( ucfirst( $slug ), ssp_series_taxonomy(), array( 'slug' => $slug ) );

		$episode_id = $this->factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => 'publish',
				'post_title'  => 'Series episode ' . $slug,
			)
		);
		update_post_meta( $episode_id, 'audio_file', 'https://example.com/' . $slug . '.mp3' );
		wp_set_object_terms( $episode_id, $slug, ssp_series_taxonomy() );

		return $episode_id;
	}

	/**
	 * @return int[]
	 */
	private function series_ids() {
		$ids = array( $this->news_episode_id, $this->music_episode_id );
		sort( $ids );

		return $ids;
	}

	/**
	 * @return int[]
	 */
	private function all_ids() {
		$ids = array( $this->news_episode_id, $this->music_episode_id, $this->unassigned_episode_id );
		sort( $ids );

		return $ids;
	}

	/**
	 * Creates a published episode with audio that is not in any series.
	 */
	private function create_episode_without_series() {
		$episode_id = $this->factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => 'publish',
				'post_title'  => 'Episode without series',
			)
		);
		update_post_meta( $episode_id, 'audio_file', 'https://example.com/unassigned.mp3' );

		return $episode_id;
	}

	/**
	 * @param int[]|\WP_Post[] $episodes
	 *
	 * @return int[]
	 */
	private function sorted_ids( $episodes ) {
		$ids = array_map(
			function ( $episode ) {
				return (int) ( $episode instanceof \WP_Post ? $episode->ID : $episode );
			},
			$episodes
		);
		sort( $ids );

		return $ids;
	}
}
