<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Repositories\Episode_Repository;

/**
 * The number of episodes a playlist query returns is capped at Episode_Repository::MAX_PLAYLIST_LIMIT.
 */
class EpisodeRepositoryPlaylistLimitTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Episode_Repository
	 */
	private $episode_repository;

	/**
	 * @var callable|null
	 */
	private $max_limit_filter;

	protected function setUp(): void {
		parent::setUp();

		// The test teardown unregisters custom post types after each test.
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();

		$this->episode_repository = ssp_get_service( 'episode_repository' );
	}

	public function limit_provider() {
		return array(
			'zero'          => array( 0, 10 ),
			'negative'      => array( -1, 10 ),
			'not a number'  => array( 'abc', 10 ),
			'below the cap' => array( 5, 5 ),
			'at the cap'    => array( 100, 100 ),
			'above the cap' => array( 101, 100 ),
			'string above'  => array( '150', 100 ),
			'far above'     => array( 500, 100 ),
		);
	}

	/**
	 * @dataProvider limit_provider
	 */
	public function test_get_episodes_queries_capped_posts_per_page( $limit, $expected ) {
		$this->assertSame( $expected, $this->queried_posts_per_page( $limit ) );
	}

	public function test_get_episodes_allows_a_raised_max_limit_from_filter() {
		$this->set_max_limit( 250 );

		$this->assertSame( 250, $this->queried_posts_per_page( 500 ) );
		$this->assertSame( 200, $this->queried_posts_per_page( 200 ) );
	}

	public function test_get_episodes_uses_a_lowered_max_limit_from_filter() {
		$this->set_max_limit( 20 );

		$this->assertSame( 20, $this->queried_posts_per_page( 50 ) );
		$this->assertSame( 5, $this->queried_posts_per_page( 5 ) );
	}

	public function test_get_episodes_accepts_a_numeric_string_max_limit_from_filter() {
		$this->set_max_limit( '30' );

		$this->assertSame( 30, $this->queried_posts_per_page( 50 ) );
	}

	public function default_max_limit_provider() {
		return array(
			'zero'     => array( 0 ),
			'negative' => array( -1 ),
			'text'     => array( 'abc' ),
			'decimal'  => array( 1.5 ),
			'list'     => array( array( 200 ) ),
			'empty'    => array( null ),
			'yes'      => array( true ),
			'no'       => array( false ),
		);
	}

	/**
	 * @dataProvider default_max_limit_provider
	 */
	public function test_get_episodes_uses_default_max_limit_for_unusable_filter_result( $filter_result ) {
		$this->set_max_limit( $filter_result );

		$this->assertSame( 100, $this->queried_posts_per_page( 500 ) );
	}

	public function test_get_episodes_caps_fallback_limit_at_a_lower_max_limit() {
		$this->set_max_limit( 5 );

		$this->assertSame( 5, $this->queried_posts_per_page( 0 ) );
	}

	public function test_get_episodes_accepts_a_max_limit_of_one_from_filter() {
		$this->set_max_limit( 1 );

		$this->assertSame( 1, $this->queried_posts_per_page( 50 ) );
	}

	protected function tearDown(): void {
		if ( $this->max_limit_filter ) {
			remove_filter( 'ssp_podcast_playlist_max_limit', $this->max_limit_filter );
			$this->max_limit_filter = null;
		}

		parent::tearDown();
	}

	/**
	 * Makes the ssp_podcast_playlist_max_limit filter return the given value.
	 */
	private function set_max_limit( $value ) {
		$this->max_limit_filter = function () use ( $value ) {
			return $value;
		};

		add_filter( 'ssp_podcast_playlist_max_limit', $this->max_limit_filter );
	}

	/**
	 * Runs get_episodes() and returns the posts_per_page the query used.
	 */
	private function queried_posts_per_page( $limit ) {
		$captured = null;
		$capture  = function ( $query ) use ( &$captured ) {
			$captured = $query->get( 'posts_per_page' );
		};

		add_action( 'pre_get_posts', $capture );
		$this->episode_repository->get_episodes(
			array(
				'limit'   => $limit,
				'order'   => 'ASC',
				'orderby' => 'menu_order ID',
				'include' => '',
				'exclude' => '',
				'page'    => 1,
				'series'  => '',
				'tag'     => '',
			)
		);
		remove_action( 'pre_get_posts', $capture );

		return (int) $captured;
	}
}
