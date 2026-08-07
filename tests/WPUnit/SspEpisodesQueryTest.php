<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Feed_Handler;

/**
 * Covers ssp_episodes() query building and the feed query it feeds into (#865):
 * the query must scale to any episode count without a post__in ID list, while
 * preserving the exact set, ordering, series filtering and audio_file behavior.
 */
class SspEpisodesQueryTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Feed_Handler
	 */
	protected $feed_handler;

	protected function setUp(): void {
		parent::setUp();

		delete_option( 'ss_podcasting_use_post_types' );
		$this->reset_ssp_cache();

		$app      = new \ReflectionClass( 'SeriouslySimplePodcasting\Controllers\App_Controller' );
		$property = $app->getProperty( 'feed_handler' );
		$property->setAccessible( true );
		$this->feed_handler = $property->getValue( ssp_app() );
	}

	/* -------------------------------------------------------------------------
	 * Helpers
	 * ---------------------------------------------------------------------- */

	/**
	 * Drop the ssp episode-id object cache so freshly created fixtures are seen.
	 */
	protected function reset_ssp_cache() {
		wp_cache_flush();
	}

	/**
	 * Create a published podcast episode, optionally with audio, date and series.
	 */
	protected function create_podcast_episode( $args = array() ) {
		$defaults = array(
			'post_type'   => SSP_CPT_PODCAST,
			'post_status' => 'publish',
		);
		$post_args = array_merge( $defaults, array_diff_key( $args, array_flip( array( 'audio_file', 'date_recorded', 'series' ) ) ) );
		$post_id   = $this->factory()->post->create( $post_args );

		if ( ! empty( $args['audio_file'] ) ) {
			update_post_meta( $post_id, 'audio_file', $args['audio_file'] );
		}
		if ( ! empty( $args['date_recorded'] ) ) {
			update_post_meta( $post_id, 'date_recorded', $args['date_recorded'] );
		}
		if ( ! empty( $args['series'] ) ) {
			wp_set_object_terms( $post_id, $args['series'], ssp_series_taxonomy() );
		}

		return $post_id;
	}

	/**
	 * Create a plain post (used to exercise the additional-episode-post-type path).
	 */
	protected function create_plain_post( $audio_file = '' ) {
		$post_id = $this->factory()->post->create( array(
			'post_type'   => 'post',
			'post_status' => 'publish',
		) );

		if ( '' !== $audio_file ) {
			update_post_meta( $post_id, 'audio_file', $audio_file );
		}

		return $post_id;
	}

	protected function create_series( $slug ) {
		$term = wp_insert_term( ucfirst( $slug ), ssp_series_taxonomy(), array( 'slug' => $slug ) );
		if ( is_wp_error( $term ) ) {
			$existing = get_term_by( 'slug', $slug, ssp_series_taxonomy() );
			return $existing ? $existing->term_id : 0;
		}
		return $term['term_id'];
	}

	protected function enable_extra_post_types( array $types = array( 'post' ) ) {
		update_option( 'ss_podcasting_use_post_types', $types );
		$this->reset_ssp_cache();
	}

	/**
	 * Run WP_Query with the given args and return the matched post IDs.
	 */
	protected function run_query( $args ) {
		$query = new \WP_Query( $args );
		return array_map( 'intval', wp_list_pluck( $query->posts, 'ID' ) );
	}

	/**
	 * IDs the feed would output for the given series/exclusion/pub-date type.
	 */
	protected function feed_ids( $series_slug = '', $exclude = array(), $pub = 'published' ) {
		$this->reset_ssp_cache();
		$query = $this->feed_handler->get_feed_query( $series_slug, $exclude, $pub );
		return array_map( 'intval', wp_list_pluck( $query->posts, 'ID' ) );
	}

	/* -------------------------------------------------------------------------
	 * Scaling / no post__in  (the regression the fix targets)
	 * ---------------------------------------------------------------------- */

	public function test_feed_args_contain_no_post_in() {
		$this->create_podcast_episode();
		$this->reset_ssp_cache();

		$args = ssp_episodes( 10, '', true, 'feed' );

		$this->assertArrayNotHasKey( 'post__in', $args );
	}

	public function test_feed_args_have_no_post_in_with_many_episodes() {
		$created = array();
		for ( $i = 0; $i < 25; $i ++ ) {
			$created[] = $this->create_podcast_episode();
		}
		$this->reset_ssp_cache();

		$args = ssp_episodes( -1, '', true, 'feed' );
		$this->assertArrayNotHasKey( 'post__in', $args );

		// The query still returns every published episode.
		$ids = $this->run_query( $args );
		foreach ( $created as $id ) {
			$this->assertContains( $id, $ids );
		}
	}

	public function test_generated_sql_has_no_id_in_list() {
		for ( $i = 0; $i < 20; $i ++ ) {
			$this->create_podcast_episode();
		}
		$this->reset_ssp_cache();

		$query = $this->feed_handler->get_feed_query( '', array(), 'published' );

		$this->assertStringNotContainsStringIgnoringCase( 'ID IN (', $query->request );
	}

	/* -------------------------------------------------------------------------
	 * Args shape
	 * ---------------------------------------------------------------------- */

	public function test_args_have_core_keys() {
		$args = ssp_episodes( 7, '', true, 'feed' );

		$this->assertContains( SSP_CPT_PODCAST, (array) $args['post_type'] );
		$this->assertEquals( 'publish', $args['post_status'] );
		$this->assertEquals( 7, $args['posts_per_page'] );
	}

	public function test_no_meta_query_without_extra_post_types() {
		$args = ssp_episodes( 10, '', true, 'feed' );

		$this->assertArrayNotHasKey( 'meta_query', $args );
	}

	public function test_meta_query_present_with_extra_post_types() {
		$this->enable_extra_post_types();

		$args = ssp_episodes( 10, '', true, 'feed' );

		$this->assertArrayHasKey( 'meta_query', $args );
		$keys = wp_list_pluck( $args['meta_query'], 'key' );
		$this->assertContains( 'audio_file', $keys );
	}

	public function test_series_slug_adds_inclusive_tax_query() {
		$this->create_series( 'season-one' );

		$args = ssp_episodes( 10, 'season-one', true, 'feed' );

		$this->assertArrayHasKey( 'tax_query', $args );
		$this->assertEquals( 'season-one', $args['tax_query'][0]['terms'] );
		$this->assertArrayNotHasKey( 'operator', $args['tax_query'][0] );
	}

	public function test_exclude_series_adds_not_in_tax_query() {
		$args = ssp_episodes( 10, '', true, 'feed', array( 'skip-me' ) );

		$this->assertArrayHasKey( 'tax_query', $args );
		$this->assertEquals( 'NOT IN', $args['tax_query'][0]['operator'] );
		$this->assertEquals( array( 'skip-me' ), $args['tax_query'][0]['terms'] );
	}

	public function test_default_series_slug_adds_no_tax_query() {
		$series_id = $this->create_series( 'the-default' );
		ssp_update_option( 'default_series', $series_id );

		$args = ssp_episodes( 10, 'the-default', true, 'feed' );

		$this->assertArrayNotHasKey( 'tax_query', $args );
	}

	/* -------------------------------------------------------------------------
	 * Membership
	 * ---------------------------------------------------------------------- */

	public function test_feed_returns_published_excludes_draft_and_private() {
		$publish = $this->create_podcast_episode();
		$draft   = $this->create_podcast_episode( array( 'post_status' => 'draft' ) );
		$private = $this->create_podcast_episode( array( 'post_status' => 'private' ) );

		$ids = $this->feed_ids();

		$this->assertContains( $publish, $ids );
		$this->assertNotContains( $draft, $ids );
		$this->assertNotContains( $private, $ids );
	}

	public function test_feed_respects_max_episodes_cap() {
		update_option( 'posts_per_rss', 10 );
		for ( $i = 0; $i < 12; $i ++ ) {
			$this->create_podcast_episode();
		}

		$ids = $this->feed_ids();

		$this->assertCount( 10, $ids );
	}

	public function test_explicit_limit_caps_result_count() {
		for ( $i = 0; $i < 8; $i ++ ) {
			$this->create_podcast_episode();
		}
		$this->reset_ssp_cache();

		$ids = $this->run_query( ssp_episodes( 5, '', true, 'feed' ) );

		$this->assertCount( 5, $ids );
	}

	/* -------------------------------------------------------------------------
	 * Ordering
	 * ---------------------------------------------------------------------- */

	public function test_default_order_is_post_date_desc() {
		$older = $this->create_podcast_episode( array(
			'post_date'     => '2025-01-01 09:00:00',
			'post_date_gmt' => '2025-01-01 09:00:00',
		) );
		$newer = $this->create_podcast_episode( array(
			'post_date'     => '2025-06-01 09:00:00',
			'post_date_gmt' => '2025-06-01 09:00:00',
		) );

		$ids = $this->feed_ids();

		$this->assertLessThan(
			array_search( $older, $ids, true ),
			array_search( $newer, $ids, true ),
			'Newer episode should sort before older by post date'
		);
	}

	public function test_recorded_order_uses_date_recorded_desc() {
		// post_date order is the inverse of date_recorded order to prove sorting is by the meta.
		$a = $this->create_podcast_episode( array(
			'post_date'     => '2025-01-01 09:00:00',
			'post_date_gmt' => '2025-01-01 09:00:00',
			'date_recorded' => '2025-06-01 09:00:00',
		) );
		$b = $this->create_podcast_episode( array(
			'post_date'     => '2025-06-01 09:00:00',
			'post_date_gmt' => '2025-06-01 09:00:00',
			'date_recorded' => '2025-01-01 09:00:00',
		) );

		$ids = $this->feed_ids( '', array(), 'recorded' );

		$this->assertLessThan(
			array_search( $b, $ids, true ),
			array_search( $a, $ids, true ),
			'Episode with the later date_recorded should sort first'
		);
	}

	/* -------------------------------------------------------------------------
	 * Series filtering (real membership)
	 * ---------------------------------------------------------------------- */

	public function test_series_feed_returns_only_that_series() {
		$series_a = $this->create_series( 'series-a' );
		$series_b = $this->create_series( 'series-b' );
		$in_a     = $this->create_podcast_episode( array( 'series' => $series_a ) );
		$in_b     = $this->create_podcast_episode( array( 'series' => $series_b ) );

		$ids = $this->feed_ids( 'series-a' );

		$this->assertContains( $in_a, $ids );
		$this->assertNotContains( $in_b, $ids );
	}

	public function test_excluded_series_feed_omits_that_series() {
		$series_a = $this->create_series( 'series-a' );
		$series_b = $this->create_series( 'series-b' );
		$in_a     = $this->create_podcast_episode( array( 'series' => $series_a ) );
		$in_b     = $this->create_podcast_episode( array( 'series' => $series_b ) );

		$ids = $this->feed_ids( '', array( 'series-a' ) );

		$this->assertNotContains( $in_a, $ids );
		$this->assertContains( $in_b, $ids );
	}

	/* -------------------------------------------------------------------------
	 * Additional post types + audio_file filter
	 * ---------------------------------------------------------------------- */

	public function test_extra_post_types_include_only_audio_posts() {
		$this->enable_extra_post_types();

		$podcast_with_audio = $this->create_podcast_episode( array( 'audio_file' => 'https://example.com/a.mp3' ) );
		$post_with_audio    = $this->create_plain_post( 'https://example.com/b.mp3' );
		$post_without_audio = $this->create_plain_post();

		$ids = $this->feed_ids();

		$this->assertContains( $podcast_with_audio, $ids );
		$this->assertContains( $post_with_audio, $ids );
		$this->assertNotContains( $post_without_audio, $ids );
	}

	public function test_extra_post_types_exclude_audioless_podcast_when_audio_exists() {
		$this->enable_extra_post_types();

		$with_audio       = $this->create_podcast_episode( array( 'audio_file' => 'https://example.com/a.mp3' ) );
		$podcast_no_audio = $this->create_podcast_episode(); // no audio_file meta

		$ids = $this->feed_ids();

		$this->assertContains( $with_audio, $ids );
		$this->assertNotContains( $podcast_no_audio, $ids );
	}

	/**
	 * Empty-post__in filter-drop defect (#865): with extra post types on and no item
	 * carrying audio, the current code returns everything (WP ignores an empty post__in).
	 * The meta_query fix must keep the audio filter on → empty feed. Fails pre-fix.
	 */
	public function test_extra_post_types_audioless_only_yields_empty_feed() {
		$this->enable_extra_post_types();

		$podcast_no_audio = $this->create_podcast_episode(); // no audio anywhere
		$post_no_audio    = $this->create_plain_post();

		$ids = $this->feed_ids();

		$this->assertNotContains( $podcast_no_audio, $ids );
		$this->assertNotContains( $post_no_audio, $ids );
		$this->assertEmpty( $ids );
	}

	public function test_no_extra_post_types_includes_audioless_podcast() {
		$podcast_no_audio = $this->create_podcast_episode(); // no audio_file meta, no extra types

		$ids = $this->feed_ids();

		$this->assertContains( $podcast_no_audio, $ids );
	}

	public function test_extra_post_types_with_recorded_ordering() {
		$this->enable_extra_post_types();

		$early = $this->create_podcast_episode( array(
			'audio_file'    => 'https://example.com/1.mp3',
			'date_recorded' => '2025-01-01 09:00:00',
		) );
		$late  = $this->create_plain_post( 'https://example.com/2.mp3' );
		update_post_meta( $late, 'date_recorded', '2025-06-01 09:00:00' );
		$no_audio = $this->create_plain_post(); // must stay excluded

		$ids = $this->feed_ids( '', array(), 'recorded' );

		$this->assertNotContains( $no_audio, $ids, 'audio_file filter still applies under recorded ordering' );
		$this->assertLessThan(
			array_search( $early, $ids, true ),
			array_search( $late, $ids, true ),
			'Later date_recorded should sort first, not ordered by audio_file'
		);
	}

	/* -------------------------------------------------------------------------
	 * Empty state + other callers
	 * ---------------------------------------------------------------------- */

	public function test_empty_feed_when_no_episodes() {
		$ids = $this->feed_ids();

		$this->assertIsArray( $ids );
		$this->assertEmpty( $ids );
	}

	public function test_glance_context_still_returns_ids() {
		$episode = $this->create_podcast_episode();
		$this->reset_ssp_cache();

		$ids = ssp_episodes( -1, '', false, 'glance' );

		$this->assertContains( $episode, array_map( 'intval', $ids ) );
	}
}
