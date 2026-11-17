<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist;

/**
 * The playlist include and exclude filters apply to lists only; other value types leave the query unfiltered.
 */
class EpisodeRepositoryPlaylistIncludeTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Episode_Repository
	 */
	private $episode_repository;

	/**
	 * @var int
	 */
	private $first_episode_id;

	/**
	 * @var int
	 */
	private $second_episode_id;

	protected function setUp(): void {
		parent::setUp();

		// The test teardown unregisters custom post types and taxonomies after each test.
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		ssp_app()->series_controller->register_taxonomy();

		$this->episode_repository = ssp_get_service( 'episode_repository' );

		$this->first_episode_id  = $this->create_episode( 'first' );
		$this->second_episode_id = $this->create_episode( 'second' );
	}

	public function test_get_episodes_includes_only_listed_episode() {
		$episodes = $this->episode_repository->get_episodes( $this->atts( array( 'include' => array( $this->first_episode_id ) ) ) );

		$this->assertSame( array( $this->first_episode_id ), $this->sorted_ids( $episodes ) );
	}

	public function test_get_episodes_excludes_listed_episode() {
		$episodes = $this->episode_repository->get_episodes( $this->atts( array( 'exclude' => array( $this->first_episode_id ) ) ) );

		$this->assertSame( array( $this->second_episode_id ), $this->sorted_ids( $episodes ) );
	}

	public function test_get_episodes_follows_include_order_second_then_first() {
		$include  = array( $this->second_episode_id, $this->first_episode_id );
		$episodes = $this->episode_repository->get_episodes(
			$this->atts( array( 'include' => $include, 'orderby' => 'post__in' ) )
		);

		$this->assertSame( $include, $this->ids( $episodes ) );
	}

	public function non_list_value_provider() {
		return array(
			'text holding an episode id' => array( 'id-as-text' ),
			'number'                     => array( 5 ),
			'boolean'                    => array( true ),
			'plain text'                 => array( 'abc' ),
		);
	}

	/**
	 * @dataProvider non_list_value_provider
	 */
	public function test_get_episodes_ignores_include_for_non_list_value( $value ) {
		$episodes = $this->episode_repository->get_episodes( $this->atts( array( 'include' => $this->resolve( $value ) ) ) );

		$this->assertSame( $this->expected_ids(), $this->sorted_ids( $episodes ) );
	}

	/**
	 * @dataProvider non_list_value_provider
	 */
	public function test_get_episodes_ignores_include_for_non_list_value_with_include_order( $value ) {
		$episodes = $this->episode_repository->get_episodes(
			$this->atts( array( 'include' => $this->resolve( $value ), 'orderby' => 'post__in' ) )
		);

		$this->assertSame( $this->expected_ids(), $this->sorted_ids( $episodes ) );
	}

	/**
	 * @dataProvider non_list_value_provider
	 */
	public function test_get_episodes_ignores_exclude_for_non_list_value( $value ) {
		$episodes = $this->episode_repository->get_episodes( $this->atts( array( 'exclude' => $this->resolve( $value ) ) ) );

		$this->assertSame( $this->expected_ids(), $this->sorted_ids( $episodes ) );
	}

	public function empty_value_provider() {
		return array(
			'empty text' => array( '' ),
			'null'       => array( null ),
			'empty list' => array( array() ),
		);
	}

	/**
	 * @dataProvider empty_value_provider
	 */
	public function test_get_episodes_returns_all_episodes_for_empty_include( $value ) {
		$episodes = $this->episode_repository->get_episodes( $this->atts( array( 'include' => $value ) ) );

		$this->assertSame( $this->expected_ids(), $this->sorted_ids( $episodes ) );
	}

	/**
	 * @dataProvider empty_value_provider
	 */
	public function test_get_episodes_returns_all_episodes_for_empty_include_with_include_order( $value ) {
		$episodes = $this->episode_repository->get_episodes(
			$this->atts( array( 'include' => $value, 'orderby' => 'post__in' ) )
		);

		$this->assertSame( $this->expected_ids(), $this->sorted_ids( $episodes ) );
	}

	/**
	 * @dataProvider empty_value_provider
	 */
	public function test_get_episodes_returns_all_episodes_for_empty_exclude( $value ) {
		$episodes = $this->episode_repository->get_episodes( $this->atts( array( 'exclude' => $value ) ) );

		$this->assertSame( $this->expected_ids(), $this->sorted_ids( $episodes ) );
	}

	/**
	 * Data providers run before setUp, so the episode id is resolved here.
	 */
	private function resolve( $value ) {
		return 'id-as-text' === $value ? (string) $this->first_episode_id : $value;
	}

	/**
	 * Builds the full set of query attributes with the given overrides.
	 */
	private function atts( $overrides ) {
		$atts         = Podcast_Playlist::get_query_atts( array() );
		$atts['page'] = 1;

		return array_merge( $atts, $overrides );
	}

	/**
	 * Creates a published episode with audio.
	 */
	private function create_episode( $slug ) {
		$episode_id = $this->factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => 'publish',
				'post_title'  => 'Include episode ' . $slug,
			)
		);
		update_post_meta( $episode_id, 'audio_file', 'https://example.com/' . $slug . '.mp3' );

		return $episode_id;
	}

	/**
	 * @return int[]
	 */
	private function expected_ids() {
		$ids = array( $this->first_episode_id, $this->second_episode_id );
		sort( $ids );

		return $ids;
	}

	/**
	 * @param int[]|\WP_Post[] $episodes
	 *
	 * @return int[]
	 */
	private function ids( $episodes ) {
		return array_map(
			function ( $episode ) {
				return (int) ( $episode instanceof \WP_Post ? $episode->ID : $episode );
			},
			array_values( $episodes )
		);
	}

	/**
	 * @param int[]|\WP_Post[] $episodes
	 *
	 * @return int[]
	 */
	private function sorted_ids( $episodes ) {
		$ids = $this->ids( $episodes );
		sort( $ids );

		return $ids;
	}
}
