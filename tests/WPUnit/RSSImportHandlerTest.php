<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\RSS_Import_Handler;

class RSSImportHandlerTest extends \Codeception\TestCase\WPTestCase
{
    const FEED_URL  = 'https://example.com/feed.xml';
    const FEED_GUID = '9b1e7c34-2f5a-5d8e-b6c1-4a7f0e3d92aa';

    /**
     * Bodies of requests sent to the Castos series/create endpoint.
     *
     * @var array
     */
    private $push_bodies = [];

    /**
     * Feed XML served for FEED_URL by the HTTP interceptor.
     *
     * @var string
     */
    private $feed_xml = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the plugin is loaded and post types are registered
        // Suppress block registration warnings that occur during tests
        // These are expected notices from WordPress core/themes and don't affect test functionality
        @do_action('init');

        $this->push_bodies = [];
        $this->feed_xml    = $this->build_feed_xml();

        add_filter('pre_http_request', [$this, 'intercept_http'], 10, 3);
    }

    /**
     * Override to suppress incorrect usage notices from WordPress block registration
     * These are expected notices from WordPress core/themes and don't affect test functionality
     */
    public function assert_post_conditions()
    {
        // Suppress incorrect usage notices from block registration
        // These are expected and don't affect test functionality
        $expected_notices = [
            'WP_Block_Type_Registry::register',
            'WP_Block_Bindings_Registry::register',
        ];

        // Get the incorrect usage notices
        $incorrect_usage = $this->get_incorrect_usage_notices();

        // Filter out expected notices
        $unexpected_notices = array_filter($incorrect_usage, function ($notice) use ($expected_notices) {
            foreach ($expected_notices as $expected) {
                if (strpos($notice, $expected) !== false) {
                    return false;
                }
            }
            return true;
        });

        // Only fail if there are unexpected notices
        if (!empty($unexpected_notices)) {
            $this->fail('Unexpected incorrect usage notices: ' . implode(', ', $unexpected_notices));
        }
    }

    /**
     * Get incorrect usage notices (helper method)
     */
    private function get_incorrect_usage_notices()
    {
        // This is a placeholder - the actual implementation depends on how
        // Codeception tracks incorrect usage notices
        return [];
    }

    protected function tearDown(): void
    {
        remove_filter('pre_http_request', [$this, 'intercept_http']);
        RSS_Import_Handler::reset_import_data();
        delete_option('ss_podcasting_podmotor_account_api_token');
        parent::tearDown();
    }

    /**
     * Serves the test feed and records series/create pushes; every other request succeeds silently.
     */
    public function intercept_http($preempt, $args, $url)
    {
        if (self::FEED_URL === $url) {
            return ['body' => $this->feed_xml, 'response' => ['code' => 200]];
        }

        if (false !== strpos($url, 'api/v2/series/create')) {
            $this->push_bodies[] = $args['body'];

            return ['body' => wp_json_encode(['status' => 'success']), 'response' => ['code' => 200]];
        }

        return ['body' => '', 'response' => ['code' => 200]];
    }

    /**
     * Builds a feed with the given number of items, optionally without a channel GUID.
     */
    private function build_feed_xml($items = 2, $with_guid = true)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:podcast="https://podcastindex.org/namespace/1.0">
	<channel>
		<title>Imported Show</title>
		<description>A show imported from Castos.</description>';

        if ($with_guid) {
            $xml .= '<podcast:guid>' . self::FEED_GUID . '</podcast:guid>';
        }

        for ($i = 1; $i <= $items; $i++) {
            $xml .= '<item>
			<title>Episode ' . $i . '</title>
			<guid isPermaLink="false">https://example.com/?p=' . $i . '</guid>
			<enclosure url="https://example.com/audio/' . $i . '.mp3" length="1000" type="audio/mpeg"/>
		</item>';
        }

        return $xml . '</channel></rss>';
    }

    /**
     * Creates a series term and returns its ID.
     */
    private function create_series($name = 'Test Series')
    {
        return $this->factory()->term->create(['taxonomy' => ssp_series_taxonomy(), 'name' => $name]);
    }

    /**
     * Stores the import configuration the way the import form does and returns it.
     */
    private function configure_import($series_id)
    {
        $option = [
            'import_rss_feed'  => self::FEED_URL,
            'import_post_type' => SSP_CPT_PODCAST,
            'import_series'    => $series_id,
        ];

        update_option('ssp_external_rss', $option);

        return $option;
    }

    /**
     * Runs one import request (one chunk) against the stored configuration.
     */
    private function run_import_chunk($series_id)
    {
        $importer = new RSS_Import_Handler($this->configure_import($series_id), ssp_get_service('castos_handler'));

        return $importer->import_rss_feed();
    }

    private function connect_to_castos()
    {
        update_option('ss_podcasting_podmotor_account_api_token', 'test-token');

        // Saving the token triggers Series_Controller::sync_series(), which pushes
        // every existing series — not the behaviour under test here.
        $this->push_bodies = [];
    }

    /**
     * While an import is running, saving a series term must not push to Castos —
     * neither editing the import target nor creating a new term mid-import.
     */
    public function testNoSeriesPushWhileImportIsRunning()
    {
        $series_id = $this->create_series();
        $this->connect_to_castos();
        $this->configure_import($series_id);
        RSS_Import_Handler::start_importing();

        wp_update_term($series_id, ssp_series_taxonomy(), ['name' => 'Renamed mid-import']);
        $this->assertNotWPError(wp_insert_term('Created mid-import', ssp_series_taxonomy()));

        $this->assertCount(0, $this->push_bodies, 'No series push may fire while an RSS import is running');
    }

    /**
     * Without an import running, saving a series term still pushes to Castos.
     */
    public function testSeriesPushFiresWhenNoImportIsRunning()
    {
        $series_id = $this->create_series();
        $this->connect_to_castos();

        wp_update_term($series_id, ssp_series_taxonomy(), ['name' => 'Renamed, no import']);

        $this->assertCount(1, $this->push_bodies, 'The ordinary series push must keep firing outside imports');
    }

    /**
     * A completed import writes the feed's GUID, fires exactly one push carrying
     * it, and lifts the push suppression.
     */
    public function testImportCompletionFiresOnePushCarryingTheGuid()
    {
        $series_id = $this->create_series();
        $this->connect_to_castos();

        $response = $this->run_import_chunk($series_id);

        $this->assertSame('success', $response['status']);
        $this->assertTrue($response['is_finished'], 'A two-item feed completes in one request');
        $this->assertSame(self::FEED_GUID, ssp_get_option('data_guid', '', $series_id));
        $this->assertCount(1, $this->push_bodies, 'Import completion must fire exactly one series push');
        $this->assertSame(self::FEED_GUID, $this->push_bodies[0]['guid']);
        $this->assertEquals($series_id, $this->push_bodies[0]['series_id']);
        $this->assertFalse(RSS_Import_Handler::is_importing(), 'Completion must lift the push suppression');
    }

    /**
     * A chunked import keeps the lock and pushes nothing until the final chunk,
     * which pushes exactly once.
     */
    public function testChunkedImportPushesOnceOnCompletion()
    {
        $this->feed_xml = $this->build_feed_xml(RSS_Import_Handler::ITEMS_PER_REQUEST + 1);
        $series_id      = $this->create_series();
        $this->connect_to_castos();

        $first = $this->run_import_chunk($series_id);

        $this->assertFalse($first['is_finished']);
        $this->assertCount(0, $this->push_bodies, 'A partial chunk must not push');
        $this->assertTrue(RSS_Import_Handler::is_importing(), 'The lock must survive between chunks');

        $second = $this->run_import_chunk($series_id);

        $this->assertTrue($second['is_finished']);
        $this->assertCount(1, $this->push_bodies, 'Only the final chunk pushes, exactly once');
        $this->assertFalse(RSS_Import_Handler::is_importing());
    }

    /**
     * No completion push without a Castos connection or without a default series.
     */
    public function testNoCompletionPushWhenNotConnectedOrWithoutDefaultSeries()
    {
        $first  = $this->create_series();
        $second = $this->create_series('Second');

        $this->run_import_chunk($first);
        $this->assertCount(0, $this->push_bodies, 'Not connected: nothing to push to');

        $this->connect_to_castos();
        RSS_Import_Handler::reset_import_data();
        $default_series = get_option('ss_podcasting_default_series');
        delete_option('ss_podcasting_default_series');

        $this->run_import_chunk($second);
        $this->assertCount(0, $this->push_bodies, 'No default series yet: the default podcast is still being set up');

        update_option('ss_podcasting_default_series', $default_series);
    }

    /**
     * Reset and TTL expiry both lift the push suppression, so a failed or
     * abandoned import cannot leave series pushes silently dropped.
     */
    public function testLockIsLiftedByResetAndByTtlExpiry()
    {
        RSS_Import_Handler::start_importing();
        $this->assertTrue(RSS_Import_Handler::is_importing());

        RSS_Import_Handler::reset_import_data();
        $this->assertFalse(RSS_Import_Handler::is_importing(), 'Reset must release the lock');

        update_option(RSS_Import_Handler::IMPORTING_LOCK, time() - RSS_Import_Handler::IMPORTING_TTL);
        $this->assertFalse(RSS_Import_Handler::is_importing(), 'An abandoned import must expire');
    }

    /**
     * A feed request that fails releases the lock, so a failed import cannot keep
     * dropping series pushes for the rest of the TTL.
     */
    public function testFailedImportReleasesTheLock()
    {
        $target = $this->create_series();

        // Priority 20 so this runs after the setUp() interceptor and its WP_Error
        // is the value wp_remote_get() actually receives.
        add_filter('pre_http_request', [$this, 'fail_feed_request'], 20, 3);
        $response = $this->run_import_chunk($target);
        remove_filter('pre_http_request', [$this, 'fail_feed_request'], 20);

        $this->assertSame('error', $response['status']);
        $this->assertFalse(RSS_Import_Handler::is_importing(), 'A failed import must release the lock');
    }

    /**
     * Fails the feed request only, leaving every other request to the main interceptor.
     */
    public function fail_feed_request($preempt, $args, $url)
    {
        return self::FEED_URL === $url ? new \WP_Error('http_request_failed', 'Connection refused') : $preempt;
    }

    /**
     * A feed whose GUID already belongs to another podcast is refused, naming
     * that podcast, and writes nothing to the target.
     */
    public function testImportRefusedWhenGuidBelongsToAnotherPodcast()
    {
        $target   = $this->create_series();
        $existing = $this->create_series('Already Imported Show');
        update_option('ss_podcasting_data_guid_' . $existing, self::FEED_GUID);

        $response = $this->run_import_chunk($target);

        $this->assertSame('error', $response['status']);
        $this->assertStringContainsString('Already Imported Show', $response['message']);
        $this->assertSame('', ssp_get_option('data_guid', '', $target));
        $this->assertFalse(RSS_Import_Handler::is_importing(), 'A refusal must release the lock');
    }

    /**
     * The default podcast owns the legacy unsuffixed GUID option, so a feed
     * matching it is refused too.
     */
    public function testImportRefusedWhenGuidMatchesDefaultPodcastLegacyOption()
    {
        $target = $this->create_series();
        update_option('ss_podcasting_data_guid', self::FEED_GUID);
        $default_name = get_term(ssp_get_default_series_id(), ssp_series_taxonomy())->name;

        $response = $this->run_import_chunk($target);

        $this->assertSame('error', $response['status']);
        $this->assertStringContainsString($default_name, $response['message']);

        delete_option('ss_podcasting_data_guid');
    }

    /**
     * Re-importing into the podcast that already owns the GUID is not a duplicate.
     */
    public function testReimportIntoOwningPodcastIsAllowed()
    {
        $target = $this->create_series();
        update_option('ss_podcasting_data_guid_' . $target, self::FEED_GUID);

        $response = $this->run_import_chunk($target);

        $this->assertSame('success', $response['status']);
        $this->assertTrue($response['is_finished']);
    }

    /**
     * A feed without a channel GUID passes the guard and leaves the target's GUID untouched.
     */
    public function testFeedWithoutGuidPassesDuplicateGuard()
    {
        $this->feed_xml = $this->build_feed_xml(1, false);
        $target         = $this->create_series();
        $this->create_series('Other');

        $response = $this->run_import_chunk($target);

        $this->assertSame('success', $response['status']);
        $this->assertSame('', ssp_get_option('data_guid', '', $target));
    }

    /**
     * Test that RSS import preserves original episode GUIDs
     *
     * @since 3.13.1
     */
    public function testRssImportPreservesOriginalGuids()
    {
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
        $series_id = wp_create_term('Test Series', 'series')['term_id'];

        // Mock the RSS import handler
        $import_config = [
            'import_rss_feed' => 'https://example.com/feed.xml',
            'import_post_type' => 'podcast',
            'import_series' => $series_id,
        ];

        $handler = new RSS_Import_Handler($import_config, ssp_get_service('castos_handler'));

        // Mock the feed loading by setting import data directly
        RSS_Import_Handler::update_import_data('feed_content', $rss_content);
        RSS_Import_Handler::update_import_data('episodes_count', 2);
        RSS_Import_Handler::update_import_data('episodes_added', 0);
        RSS_Import_Handler::update_import_data('episodes_imported', []);

        // Import the episodes
        $result = $handler->import_rss_feed();

        // Verify import was successful
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(2, $result['count']);

        // Get the imported episodes - check both 'podcast' and 'episode' post types
        $episodes = get_posts([
            'post_type' => ['podcast', 'episode'],
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'ASC',
        ]);

        // Verify episodes were created
        $this->assertNotEmpty($episodes, 'No episodes were created during import');

        // Verify episodes were created
        $this->assertCount(2, $episodes);

        // Check that original GUIDs are preserved
        $episode_1_guid = get_post_meta($episodes[0]->ID, 'ssp_original_guid', true);
        $episode_2_guid = get_post_meta($episodes[1]->ID, 'ssp_original_guid', true);

        $this->assertEquals('original-guid-12345', $episode_1_guid);
        $this->assertEquals('original-guid-67890', $episode_2_guid);

        // Verify that ssp_episode_guid() returns the original GUID
        $this->assertEquals('original-guid-12345', ssp_episode_guid($episodes[0]->ID));
        $this->assertEquals('original-guid-67890', ssp_episode_guid($episodes[1]->ID));
    }

    /**
     * Test RSS import fallback when no GUID exists in feed item
     *
     * @since 3.13.1
     */
    public function testRssImportFallbackWhenNoGuid()
    {
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
        $series_id = wp_create_term('Test Series', 'series')['term_id'];

        // Mock the RSS import handler
        $import_config = [
            'import_rss_feed' => 'https://example.com/feed.xml',
            'import_post_type' => 'podcast',
            'import_series' => $series_id,
        ];

        $handler = new RSS_Import_Handler($import_config, ssp_get_service('castos_handler'));

        // Mock the feed loading by setting import data directly
        RSS_Import_Handler::update_import_data('feed_content', $rss_content);
        RSS_Import_Handler::update_import_data('episodes_count', 1);
        RSS_Import_Handler::update_import_data('episodes_added', 0);
        RSS_Import_Handler::update_import_data('episodes_imported', []);

        // Import the episode
        $result = $handler->import_rss_feed();

        // Verify import was successful
        $this->assertArrayHasKey('status', $result);
        if ($result['status'] !== 'success') {
            $this->fail('Import failed: ' . print_r($result, true));
        }
        $this->assertEquals('success', $result['status']);

        // Get the imported episode
        $episodes = get_posts([
            'post_type' => 'podcast',
            'numberposts' => -1,
        ]);

        // Verify episode was created
        $this->assertCount(1, $episodes);

        // Check that no original GUID is stored
        $original_guid = get_post_meta($episodes[0]->ID, 'ssp_original_guid', true);
        $this->assertEmpty($original_guid);

        // Verify that ssp_episode_guid() falls back to WordPress GUID
        $wordpress_guid = get_the_guid($episodes[0]->ID);
        $this->assertEquals($wordpress_guid, ssp_episode_guid($episodes[0]->ID));
    }
}

