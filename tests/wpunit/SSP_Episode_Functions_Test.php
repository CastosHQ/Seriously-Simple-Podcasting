<?php

use Codeception\TestCase\WPTestCase;

/**
 * Test class for SSP Episode Management Functions.
 *
 * @package SeriouslySimplePodcasting\Tests
 * @since 3.12.0
 */
class SSP_Episode_Functions_Test extends WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @var int
	 */
	protected $test_episode_id;

	/**
	 * @var int
	 */
	protected $test_series_id;

	public function setUp(): void {
		parent::setUp();

		// Create a test episode
		$this->test_episode_id = $this->factory()->post->create( array(
			'post_type'   => SSP_CPT_PODCAST,
			'post_status' => 'publish',
			'post_title'  => 'Test Episode',
			'post_content' => 'This is a test episode content.',
		) );

		// Create a test series
		$this->test_series_id = $this->factory()->term->create( array(
			'taxonomy' => ssp_series_taxonomy(),
			'name'     => 'Test Series',
			'slug'     => 'test-series',
		) );

		// Assign episode to series
		wp_set_object_terms( $this->test_episode_id, $this->test_series_id, ssp_series_taxonomy() );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @covers ssp_episode_ids()
	 */
	public function test_ssp_episode_ids() {
		$this->assertTrue( function_exists( 'ssp_episode_ids' ) );

		$episode_ids = ssp_episode_ids();

		$this->assertIsArray( $episode_ids );
		$this->assertContains( $this->test_episode_id, $episode_ids );
	}

	/**
	 * @covers ssp_episodes()
	 */
	public function test_ssp_episodes_default() {
		$this->assertTrue( function_exists( 'ssp_episodes' ) );

		$episodes = ssp_episodes();

		$this->assertIsArray( $episodes );
		$this->assertLessThanOrEqual( 10, count( $episodes ) );
	}

	/**
	 * @covers ssp_episodes()
	 */
	public function test_ssp_episodes_with_custom_limit() {
		$this->assertTrue( function_exists( 'ssp_episodes' ) );

		$episodes = ssp_episodes( 5 );

		$this->assertIsArray( $episodes );
		$this->assertLessThanOrEqual( 5, count( $episodes ) );
	}

	/**
	 * @covers ssp_episodes()
	 */
	public function test_ssp_episodes_with_series() {
		$this->assertTrue( function_exists( 'ssp_episodes' ) );

		$episodes = ssp_episodes( 10, 'test-series' );

		$this->assertIsArray( $episodes );
	}

	/**
	 * @covers ssp_episodes()
	 */
	public function test_ssp_episodes_return_args() {
		$this->assertTrue( function_exists( 'ssp_episodes' ) );

		$args = ssp_episodes( 10, '', true );

		$this->assertIsArray( $args );
		$this->assertArrayHasKey( 'post_type', $args );
		$this->assertArrayHasKey( 'post_status', $args );
		$this->assertArrayHasKey( 'posts_per_page', $args );
	}

	/**
	 * @covers ssp_episodes()
	 */
	public function test_ssp_episodes_with_context() {
		$this->assertTrue( function_exists( 'ssp_episodes' ) );

		$episode_ids = ssp_episodes( 10, '', false, 'glance' );

		$this->assertIsArray( $episode_ids );
		$this->assertContains( $this->test_episode_id, $episode_ids );
	}

	/**
	 * @covers ssp_get_episode_series_id()
	 */
	public function test_ssp_get_episode_series_id() {
		$this->assertTrue( function_exists( 'ssp_get_episode_series_id' ) );

		$series_id = ssp_get_episode_series_id( $this->test_episode_id );

		$this->assertIsInt( $series_id );
		$this->assertEquals( $this->test_series_id, $series_id );
	}

	/**
	 * @covers ssp_get_episode_series_id()
	 */
	public function test_ssp_get_episode_series_id_with_default() {
		$this->assertTrue( function_exists( 'ssp_get_episode_series_id' ) );

		$default_series_id = 999;
		$series_id = ssp_get_episode_series_id( $this->test_episode_id, $default_series_id );

		$this->assertIsInt( $series_id );
		$this->assertEquals( $this->test_series_id, $series_id );
	}

	/**
	 * @covers ssp_get_episode_series_id()
	 */
	public function test_ssp_get_episode_series_id_with_invalid_episode() {
		$this->assertTrue( function_exists( 'ssp_get_episode_series_id' ) );

		$series_id = ssp_get_episode_series_id( 99999 );

		$this->assertIsInt( $series_id );
		// Should return default series ID for invalid episode
	}

	/**
	 * @covers ssp_get_episode_excerpt()
	 */
	public function test_ssp_get_episode_excerpt() {
		$this->assertTrue( function_exists( 'ssp_get_episode_excerpt' ) );

		$excerpt = ssp_get_episode_excerpt( $this->test_episode_id );

		$this->assertIsString( $excerpt );
		$this->assertNotEmpty( $excerpt );
	}

	/**
	 * @covers ssp_get_episode_excerpt()
	 */
	public function test_ssp_get_episode_excerpt_with_post_object() {
		$this->assertTrue( function_exists( 'ssp_get_episode_excerpt' ) );

		$post = get_post( $this->test_episode_id );
		$excerpt = ssp_get_episode_excerpt( $post );

		$this->assertIsString( $excerpt );
		$this->assertNotEmpty( $excerpt );
	}

	/**
	 * @covers ssp_get_episode_excerpt()
	 */
	public function test_ssp_get_episode_excerpt_with_invalid_episode() {
		$this->assertTrue( function_exists( 'ssp_get_episode_excerpt' ) );

		$excerpt = ssp_get_episode_excerpt( 99999 );

		$this->assertIsString( $excerpt );
	}

	/**
	 * @covers ssp_episode_image()
	 */
	public function test_ssp_episode_image() {
		$this->assertTrue( function_exists( 'ssp_episode_image' ) );

		// The result depends on the frontend controller implementation
		// We just verify the function exists and can be called
		ssp_episode_image( $this->test_episode_id );
  		$this->addToAssertionCount(1);
	}

	/**
	 * @covers ssp_episode_image()
	 */
	public function test_ssp_episode_image_with_custom_size() {
		$this->assertTrue( function_exists( 'ssp_episode_image' ) );

		// The result depends on the frontend controller implementation
		// We just verify the function exists and can be called
		ssp_episode_image( $this->test_episode_id, 'thumbnail' );
  		$this->addToAssertionCount(1);
	}

	/**
	 * @covers ssp_episode_image()
	 */
	public function test_ssp_episode_image_with_invalid_episode() {
		$this->assertTrue( function_exists( 'ssp_episode_image' ) );

		// The result depends on the frontend controller implementation
		// We just verify the function exists and can be called
		ssp_episode_image( 99999 );
  		$this->addToAssertionCount(1);
	}

	/**
	 * @covers ssp_get_episode_podcasts()
	 */
	public function test_ssp_get_episode_podcasts() {
		$this->assertTrue( function_exists( 'ssp_get_episode_podcasts' ) );

		$podcasts = ssp_get_episode_podcasts( $this->test_episode_id );

		$this->assertIsArray( $podcasts );
		$this->assertNotEmpty( $podcasts );
		$this->assertInstanceOf( 'WP_Term', $podcasts[0] );
		$this->assertEquals( $this->test_series_id, $podcasts[0]->term_id );
	}

	/**
	 * @covers ssp_get_episode_podcasts()
	 */
	public function test_ssp_get_episode_podcasts_with_invalid_episode() {
		$this->assertTrue( function_exists( 'ssp_get_episode_podcasts' ) );

		$podcasts = ssp_get_episode_podcasts( 99999 );

		$this->assertIsArray( $podcasts );
		$this->assertEmpty( $podcasts );
	}

	/**
	 * @covers ssp_episode_sync_status()
	 */
	public function test_ssp_episode_sync_status() {
		$this->assertTrue( function_exists( 'ssp_episode_sync_status' ) );

		// The result depends on the episode repository implementation
		// We just verify the function exists and can be called
		ssp_episode_sync_status( $this->test_episode_id );
		$this->addToAssertionCount(1);
	}

	/**
	 * @covers ssp_episode_sync_status()
	 */
	public function test_ssp_episode_sync_status_with_invalid_episode() {
		$this->assertTrue( function_exists( 'ssp_episode_sync_status' ) );

		$sync_status = ssp_episode_sync_status( 99999 );

		// The result depends on the episode repository implementation
		// We just verify the function exists and can be called
		$this->assertTrue( true );
	}

	/**
	 * @covers ssp_episode_passthrough_required()
	 */
	public function test_ssp_episode_passthrough_required() {
		$this->assertTrue( function_exists( 'ssp_episode_passthrough_required' ) );

		$required = ssp_episode_passthrough_required( $this->test_episode_id );

		$this->assertIsBool( $required );
	}

	/**
	 * @covers ssp_episode_passthrough_required()
	 */
	public function test_ssp_episode_passthrough_required_with_invalid_episode() {
		$this->assertTrue( function_exists( 'ssp_episode_passthrough_required' ) );

		$required = ssp_episode_passthrough_required( 99999 );

		$this->assertIsBool( $required );
	}

	/**
	 * @covers ssp_episode_passthrough_required()
	 */
	public function test_ssp_episode_passthrough_required_with_zero_episode() {
		$this->assertTrue( function_exists( 'ssp_episode_passthrough_required' ) );

		$required = ssp_episode_passthrough_required( 0 );

		$this->assertIsBool( $required );
	}

	/**
	 * @covers ssp_episode_passthrough_required()
	 */
	public function test_ssp_episode_passthrough_required_with_negative_episode() {
		$this->assertTrue( function_exists( 'ssp_episode_passthrough_required' ) );

		$required = ssp_episode_passthrough_required( -1 );

		$this->assertIsBool( $required );
	}
}


