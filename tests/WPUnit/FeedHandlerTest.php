<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Handlers\UUID_Handler;

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
	 * The feed's <podcast:guid> is the podcast's own GUID; the default podcast
	 * keeps its legacy one, and a podcast with none gets one derived and persisted.
	 */
	public function testGetGuidUsesOwnGuidOrDerivesAndPersists() {
		$legacy  = '9b1e7c34-2f5a-5d8e-b6c1-4a7f0e3d92aa';
		$default = get_term( $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) ), ssp_series_taxonomy() );
		$other   = get_term( $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) ), ssp_series_taxonomy() );

		// Model pre-update series to retain coverage of legacy fallback and lazy storage.
		delete_option( 'ss_podcasting_data_guid_' . $default->term_id );
		delete_option( 'ss_podcasting_data_guid_' . $other->term_id );

		update_option( 'ss_podcasting_default_series', $default->term_id );
		update_option( 'ss_podcasting_data_guid', $legacy );

		$this->assertSame( $legacy, $this->feed_handler->get_guid( $default->slug ), 'Default podcast renders its legacy GUID' );
		$this->assertFalse( get_option( 'ss_podcasting_data_guid_' . $default->term_id ), 'The legacy fallback is not copied into the suffixed option' );

		$derived = $this->feed_handler->get_guid( $other->slug );

		$this->assertNotSame( $legacy, $derived, 'Another podcast must not render the legacy GUID' );
		$this->assertMatchesRegularExpression( '/^[0-9a-f-]{36}$/', $derived );
		$this->assertSame( $derived, get_option( 'ss_podcasting_data_guid_' . $other->term_id ), 'A derived GUID is persisted' );
		$this->assertSame( $derived, $this->feed_handler->get_guid( $other->slug ), 'And reused on the next render' );

		delete_option( 'ss_podcasting_default_series' );
		delete_option( 'ss_podcasting_data_guid' );
		delete_option( 'ss_podcasting_data_guid_' . $other->term_id );
	}

	public function testPlainPermalinkNamedSeriesDeriveDistinctGuidsIncludingDefault() {
		update_option( 'home', 'https://guid.example.test' );
		update_option( 'permalink_structure', '' );
		$default = get_term( $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy(), 'slug' => 'plain-default' ) ), ssp_series_taxonomy() );
		$other   = get_term( $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy(), 'slug' => 'plain-other' ) ), ssp_series_taxonomy() );

		$default_guid = $this->feed_handler->get_derived_guid( $default->slug );
		$other_guid   = $this->feed_handler->get_derived_guid( $other->slug );
		$legacy_guid  = $this->feed_handler->get_derived_guid( '' );

		// Only the named-series query value joins the host, without a trailing slash.
		$expected_default = UUID_Handler::v5( Feed_Handler::PODCAST_NAMESPACE_UUID, 'guid.example.test?podcast_series=plain-default' );
		$expected_other   = UUID_Handler::v5( Feed_Handler::PODCAST_NAMESPACE_UUID, 'guid.example.test?podcast_series=plain-other' );
		$this->assertSame( 'https://guid.example.test/?feed=podcast&podcast_series=plain-other/', ssp_get_feed_url( $other->slug ) );
		$this->assertSame( $expected_default, $default_guid );
		$this->assertSame( $expected_other, $other_guid );
		$this->assertNotSame( $default_guid, $other_guid );
		$this->assertNotSame( $legacy_guid, $default_guid, 'A named default feed must not derive from the no-series feed' );
		$this->assertNotSame( $legacy_guid, $other_guid );
	}

	public function testPlainPermalinkNoSeriesDerivationKeepsHostOnlyGuid() {
		update_option( 'home', 'https://guid.example.test' );
		update_option( 'permalink_structure', '' );
		$expected = UUID_Handler::v5( Feed_Handler::PODCAST_NAMESPACE_UUID, 'guid.example.test' );

		parse_str( wp_parse_url( ssp_get_feed_url( '' ), PHP_URL_QUERY ), $query );
		$this->assertSame( 'podcast', rtrim( $query['feed'], '/' ) );
		$this->assertArrayNotHasKey( 'podcast_series', $query );
		$this->assertSame( $expected, $this->feed_handler->get_derived_guid( '' ) );
		$this->assertSame( $expected, $this->feed_handler->get_derived_guid( 'default' ) );
	}

	public function testPrettyPermalinkDerivationKeepsPreviousGuids() {
		update_option( 'home', 'https://guid.example.test/site' );
		update_option( 'permalink_structure', '/%postname%/' );

		// Explicit pre-change host + path inputs, without a scheme or trailing slash.
		$expected_default = UUID_Handler::v5( Feed_Handler::PODCAST_NAMESPACE_UUID, 'guid.example.test/site/feed/podcast' );
		$expected_first   = UUID_Handler::v5( Feed_Handler::PODCAST_NAMESPACE_UUID, 'guid.example.test/site/feed/podcast/pretty-first' );
		$expected_second  = UUID_Handler::v5( Feed_Handler::PODCAST_NAMESPACE_UUID, 'guid.example.test/site/feed/podcast/pretty-second' );

		$this->assertSame( $expected_default, $this->feed_handler->get_derived_guid( '' ) );
		$this->assertSame( $expected_default, $this->feed_handler->get_derived_guid( 'default' ) );
		$this->assertSame( $expected_first, $this->feed_handler->get_derived_guid( 'pretty-first' ) );
		$this->assertSame( $expected_second, $this->feed_handler->get_derived_guid( 'pretty-second' ) );
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
