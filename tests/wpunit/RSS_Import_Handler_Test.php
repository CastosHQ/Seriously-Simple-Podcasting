<?php

use Codeception\TestCase\WPTestCase;
use SeriouslySimplePodcasting\Handlers\RSS_Import_Handler;

class RSS_Import_Handler_Test extends WPTestCase {
	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function setUp(): void {
		parent::setUp();
		
		// Ensure the plugin is loaded and post types are registered
		do_action( 'init' );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test that RSS import preserves original episode GUIDs
	 *
	 * @since 3.13.1
	 */
	public function test_rss_import_preserves_original_guids() {
		// Create a mock RSS feed with GUIDs
		$rss_content = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
	<channel>
		<title>Test Podcast</title>
		<item>
			<title>Test Episode 1</title>
			<description>Test episode description</description>
			<pubDate>Wed, 18 Sep 2024 12:00:00 GMT</pubDate>
			<guid isPermaLink="false">original-guid-12345</guid>
			<enclosure url="https://example.com/episode1.mp3" type="audio/mpeg" length="1024"/>
		</item>
		<item>
			<title>Test Episode 2</title>
			<description>Test episode description 2</description>
			<pubDate>Wed, 18 Sep 2024 13:00:00 GMT</pubDate>
			<guid isPermaLink="false">original-guid-67890</guid>
			<enclosure url="https://example.com/episode2.mp3" type="audio/mpeg" length="2048"/>
		</item>
	</channel>
</rss>';

		// Create a series for import
		$series_id = wp_create_term( 'Test Series', 'series' )['term_id'];

		// Mock the RSS import handler
		$import_config = array(
			'import_rss_feed'  => 'https://example.com/feed.xml',
			'import_post_type' => 'podcast',
			'import_series'    => $series_id,
		);

		$handler = new RSS_Import_Handler( $import_config );

		// Mock the feed loading by setting import data directly
		RSS_Import_Handler::update_import_data( 'feed_content', $rss_content );
		RSS_Import_Handler::update_import_data( 'episodes_count', 2 );
		RSS_Import_Handler::update_import_data( 'episodes_added', 0 );
		RSS_Import_Handler::update_import_data( 'episodes_imported', array() );

		// Import the episodes
		$result = $handler->import_rss_feed();

		// Verify import was successful
		$this->assertArrayHasKey( 'status', $result );
		$this->assertEquals( 'success', $result['status'] );
		$this->assertEquals( 2, $result['count'] );

		// Get the imported episodes - check both 'podcast' and 'episode' post types
		$episodes = get_posts( array(
			'post_type'   => array( 'podcast', 'episode' ),
			'numberposts' => -1,
			'orderby'     => 'date',
			'order'       => 'ASC',
		) );

		// Verify episodes were created
		$this->assertNotEmpty( $episodes, 'No episodes were created during import' );

		// Verify episodes were created
		$this->assertCount( 2, $episodes );

		// Check that original GUIDs are preserved
		$episode_1_guid = get_post_meta( $episodes[0]->ID, 'ssp_original_guid', true );
		$episode_2_guid = get_post_meta( $episodes[1]->ID, 'ssp_original_guid', true );

		$this->assertEquals( 'original-guid-12345', $episode_1_guid );
		$this->assertEquals( 'original-guid-67890', $episode_2_guid );

		// Verify that ssp_episode_guid() returns the original GUID
		$this->assertEquals( 'original-guid-12345', ssp_episode_guid( $episodes[0]->ID ) );
		$this->assertEquals( 'original-guid-67890', ssp_episode_guid( $episodes[1]->ID ) );
	}

	/**
	 * Test RSS import fallback when no GUID exists in feed item
	 *
	 * @since 3.13.1
	 */
	public function test_rss_import_fallback_when_no_guid() {
		// Create a mock RSS feed without GUIDs
		$rss_content = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
	<channel>
		<title>Test Podcast</title>
		<item>
			<title>Test Episode Without GUID</title>
			<description>Test episode description</description>
			<pubDate>Wed, 18 Sep 2024 12:00:00 GMT</pubDate>
			<enclosure url="https://example.com/episode1.mp3" type="audio/mpeg" length="1024"/>
		</item>
	</channel>
</rss>';

		// Create a series for import
		$series_id = wp_create_term( 'Test Series', 'series' )['term_id'];

		// Mock the RSS import handler
		$import_config = array(
			'import_rss_feed'  => 'https://example.com/feed.xml',
			'import_post_type' => 'podcast',
			'import_series'    => $series_id,
		);

		$handler = new RSS_Import_Handler( $import_config );

		// Mock the feed loading by setting import data directly
		RSS_Import_Handler::update_import_data( 'feed_content', $rss_content );
		RSS_Import_Handler::update_import_data( 'episodes_count', 1 );
		RSS_Import_Handler::update_import_data( 'episodes_added', 0 );
		RSS_Import_Handler::update_import_data( 'episodes_imported', array() );

		// Import the episode
		$result = $handler->import_rss_feed();

		// Verify import was successful
		$this->assertArrayHasKey( 'status', $result );
		if ( $result['status'] !== 'success' ) {
			$this->fail( 'Import failed: ' . print_r( $result, true ) );
		}
		$this->assertEquals( 'success', $result['status'] );

		// Get the imported episode
		$episodes = get_posts( array(
			'post_type'   => 'podcast',
			'numberposts' => -1,
		) );

		// Verify episode was created
		$this->assertCount( 1, $episodes );

		// Check that no original GUID is stored
		$original_guid = get_post_meta( $episodes[0]->ID, 'ssp_original_guid', true );
		$this->assertEmpty( $original_guid );

		// Verify that ssp_episode_guid() falls back to WordPress GUID
		$wordpress_guid = get_the_guid( $episodes[0]->ID );
		$this->assertEquals( $wordpress_guid, ssp_episode_guid( $episodes[0]->ID ) );
	}
}
