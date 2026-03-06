<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Feed_Handler;

class FeedHandlerTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Feed_Handler
	 */
	protected $feed_handler;

	protected function setUp(): void {
		parent::setUp();

		$app      = new \ReflectionClass( 'SeriouslySimplePodcasting\Controllers\App_Controller' );
		$property = $app->getProperty( 'feed_handler' );
		$property->setAccessible( true );
		$this->feed_handler = $property->getValue( ssp_app() );
	}

	/**
	 * Create an episode post with explicit GMT date to avoid timezone issues in assertions.
	 *
	 * @param string $gmt_date GMT datetime string (Y-m-d H:i:s).
	 *
	 * @return int Post ID.
	 */
	protected function create_episode( $gmt_date ) {
		return $this->factory()->post->create( [
			'post_type'     => SSP_CPT_PODCAST,
			'post_status'   => 'publish',
			'post_date_gmt' => $gmt_date,
			'post_date'     => $gmt_date,
		] );
	}

	/**
	 * Set up the global $post so get_post_time() works for a given post.
	 *
	 * @param int $post_id
	 */
	protected function setup_global_post( $post_id ) {
		$this->go_to( '/?p=' . $post_id );
		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );
	}

	/**
	 * Test that full datetime in date_recorded is used as-is for pubDate.
	 */
	public function testPubDateUsesFullDatetimeFromDateRecorded() {
		$post_id = $this->create_episode( '2025-06-15 09:30:00' );
		update_post_meta( $post_id, 'date_recorded', '2025-03-10 14:25:00' );

		$this->setup_global_post( $post_id );

		$pub_date = $this->feed_handler->get_feed_item_pub_date( 'recorded', $post_id );

		$this->assertStringContainsString( '14:25:00', $pub_date );
		$this->assertStringContainsString( '10 Mar 2025', $pub_date );

		wp_reset_postdata();
	}

	/**
	 * Test that date-only date_recorded gets time from post_date (GMT).
	 */
	public function testPubDateAppendsPostTimeWhenDateRecordedHasNoTime() {
		$post_id = $this->create_episode( '2025-06-15 09:30:00' );
		update_post_meta( $post_id, 'date_recorded', '2025-03-10' );

		$this->setup_global_post( $post_id );

		$pub_date = $this->feed_handler->get_feed_item_pub_date( 'recorded', $post_id );

		$this->assertStringContainsString( '10 Mar 2025', $pub_date );
		$this->assertStringContainsString( '09:30:00', $pub_date );

		wp_reset_postdata();
	}

	/**
	 * Test that 'published' pub_date_type uses post_date regardless of date_recorded.
	 */
	public function testPubDateUsesPostDateWhenTypeIsPublished() {
		$post_id = $this->create_episode( '2025-06-15 09:30:00' );
		update_post_meta( $post_id, 'date_recorded', '2025-03-10' );

		$this->setup_global_post( $post_id );

		$pub_date = $this->feed_handler->get_feed_item_pub_date( 'published', $post_id );

		$this->assertStringContainsString( '15 Jun 2025', $pub_date );
		$this->assertStringContainsString( '09:30:00', $pub_date );

		wp_reset_postdata();
	}

	/**
	 * Test that two episodes with the same date-only date_recorded but different post times
	 * produce different pubDates in the feed.
	 */
	public function testSameDateRecordedDifferentPostTimesProduceUniquePubDates() {
		$post_id_1 = $this->create_episode( '2025-06-15 10:00:00' );
		$post_id_2 = $this->create_episode( '2025-06-15 14:30:00' );

		update_post_meta( $post_id_1, 'date_recorded', '2025-03-10' );
		update_post_meta( $post_id_2, 'date_recorded', '2025-03-10' );

		$this->setup_global_post( $post_id_1 );
		$pub_date_1 = $this->feed_handler->get_feed_item_pub_date( 'recorded', $post_id_1 );

		$this->setup_global_post( $post_id_2 );
		$pub_date_2 = $this->feed_handler->get_feed_item_pub_date( 'recorded', $post_id_2 );

		wp_reset_postdata();

		$this->assertNotEquals( $pub_date_1, $pub_date_2 );
		$this->assertStringContainsString( '10:00:00', $pub_date_1 );
		$this->assertStringContainsString( '14:30:00', $pub_date_2 );
	}
}
