<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Onboarding_Import_Handler;
use SeriouslySimplePodcasting\Handlers\RSS_Import_Handler;

class OnboardingImportTest extends \Codeception\TestCase\WPTestCase
{
    const FEED_URL  = 'https://example.com/feed.xml';
    const FEED_GUID = '9b1e7c34-2f5a-5d8e-b6c1-4a7f0e3d92aa';

    /** @var string Body served for the feed URL. */
    private $feed_body = '';

    /** @var int|null HTTP status served for the feed URL, or null for a transport error. */
    private $feed_status = 200;

    /** @var Onboarding_Import_Handler */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->feed_body   = $this->build_feed_xml();
        $this->feed_status = 200;
        $this->handler     = new Onboarding_Import_Handler();

        add_filter('pre_http_request', [$this, 'intercept_http'], 10, 3);
    }

    protected function tearDown(): void
    {
        remove_filter('pre_http_request', [$this, 'intercept_http']);
        RSS_Import_Handler::reset_import_data();
        RSS_Import_Handler::stop_importing();
        Onboarding_Import_Handler::forget_target_series();
        parent::tearDown();
    }

    public function intercept_http($preempt, $args, $url)
    {
        if (self::FEED_URL !== $url) {
            return ['body' => '', 'response' => ['code' => 200]];
        }

        if (null === $this->feed_status) {
            return new \WP_Error('http_request_failed', 'Could not resolve host');
        }

        return ['body' => $this->feed_body, 'response' => ['code' => $this->feed_status]];
    }

    private function build_feed_xml($items = 3, $with_guid = true, $title = 'The Audience Show')
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:podcast="https://podcastindex.org/namespace/1.0">
	<channel>
		<description>A show about audiences.</description>
		<generator>Castos</generator>
		<itunes:author>Craig Hewitt</itunes:author>
		<itunes:image href="https://example.com/cover.jpg"/>';

        if (null !== $title) {
            $xml .= '<title>' . $title . '</title>';
        }

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

    private function create_default_series($name = 'My Site')
    {
        $series_id = $this->factory()->term->create(['taxonomy' => ssp_series_taxonomy(), 'name' => $name]);
        ssp_update_option('default_series', $series_id);

        return $series_id;
    }

    private function create_episode_in($series_id, $status = 'publish')
    {
        $post_id = $this->factory()->post->create(['post_type' => SSP_CPT_PODCAST, 'post_status' => $status]);
        wp_set_post_terms($post_id, [$series_id], ssp_series_taxonomy());

        return $post_id;
    }

    private function series_name($series_id)
    {
        return get_term($series_id, ssp_series_taxonomy())->name;
    }

    /**
     * The confirmation screen describes the feed the user just typed in.
     */
    public function testPreviewDescribesTheFeed()
    {
        $summary = $this->handler->preview(self::FEED_URL);

        $this->assertNotWPError($summary);
        $this->assertSame('The Audience Show', $summary['title']);
        $this->assertSame('Craig Hewitt', $summary['author']);
        $this->assertSame('Castos', $summary['host']);
        $this->assertSame('https://example.com/cover.jpg', $summary['image']);
        $this->assertSame(3, $summary['episodes']);
    }

    /**
     * A feed with no channel title is named after its URL, as the import does —
     * here the host, because "feed.xml" is too generic to name a podcast after.
     */
    public function testPreviewNamesATitlelessFeedFromItsUrl()
    {
        $this->feed_body = $this->build_feed_xml(2, true, null);

        $summary = $this->handler->preview(self::FEED_URL);

        $this->assertNotWPError($summary);
        $this->assertSame('example.com', $summary['title']);
    }

    /**
     * Each way a feed can be unusable is reported as its own error.
     *
     * @dataProvider feedFailureProvider
     */
    public function testPreviewReportsFeedFailures($body, $status, $expected_code)
    {
        $this->feed_body   = $body;
        $this->feed_status = $status;

        $result = $this->handler->preview(self::FEED_URL);

        $this->assertWPError($result);
        $this->assertSame($expected_code, $result->get_error_code());
        $this->assertNotEmpty($result->get_error_message());
    }

    public function feedFailureProvider()
    {
        return [
            'transport error'   => ['', null, 'ssp_feed_unreachable'],
            'http error'        => ['<rss/>', 500, 'ssp_feed_unreachable'],
            'a web page'        => ['<!DOCTYPE html><html><body>Hello</body></html>', 200, 'ssp_not_a_feed'],
            'malformed xml'     => ['<rss><channel><title>Broken</channel></rss>', 200, 'ssp_not_a_feed'],
            'empty body'        => ['', 200, 'ssp_not_a_feed'],
        ];
    }

    /**
     * A valid feed carrying no episodes has nothing to import.
     */
    public function testPreviewRejectsAFeedWithoutEpisodes()
    {
        $this->feed_body = $this->build_feed_xml(0);

        $result = $this->handler->preview(self::FEED_URL);

        $this->assertWPError($result);
        $this->assertSame('ssp_empty_feed', $result->get_error_code());
    }

    /**
     * Something that isn't a URL is refused before any request is made.
     */
    public function testPreviewRejectsSomethingThatIsNotAUrl()
    {
        $result = $this->handler->preview('my podcast');

        $this->assertWPError($result);
        $this->assertSame('ssp_invalid_url', $result->get_error_code());
    }

    /**
     * A feed already imported into another podcast is refused by name.
     */
    public function testPreviewRefusesAFeedAlreadyImported()
    {
        $existing = $this->factory()->term->create(['taxonomy' => ssp_series_taxonomy(), 'name' => 'Already Here']);
        ssp_update_option('data_guid', self::FEED_GUID, $existing);

        $result = $this->handler->preview(self::FEED_URL);

        $this->assertWPError($result);
        $this->assertSame('ssp_duplicate_feed', $result->get_error_code());
        $this->assertStringContainsString('Already Here', $result->get_error_message());
    }

    /**
     * An untouched default podcast is adopted and renamed after the feed.
     */
    public function testStartImportsIntoAnEmptyDefaultPodcast()
    {
        $default_id = $this->create_default_series();

        $result = $this->handler->start(self::FEED_URL);

        $this->assertNotWPError($result);
        $this->assertSame($default_id, $result['series_id']);
        $this->assertSame('The Audience Show', $this->series_name($default_id));

        $config = get_option('ssp_external_rss');
        $this->assertSame($default_id, $config['import_series']);
        $this->assertSame(self::FEED_URL, $config['import_rss_feed']);
    }

    /**
     * A default podcast that already holds episodes is left exactly as it is.
     */
    public function testStartCreatesANewPodcastWhenTheDefaultHasEpisodes()
    {
        $default_id = $this->create_default_series('My Site');
        $this->create_episode_in($default_id);

        $result = $this->handler->start(self::FEED_URL);

        $this->assertNotWPError($result);
        $this->assertNotSame($default_id, $result['series_id']);
        $this->assertSame('My Site', $this->series_name($default_id));
        $this->assertSame('The Audience Show', $this->series_name($result['series_id']));
    }

    /**
     * A draft episode still counts — the default podcast is not empty.
     */
    public function testADraftEpisodeCountsAsAnEpisode()
    {
        $default_id = $this->create_default_series('My Site');
        $this->create_episode_in($default_id, 'draft');

        $result = $this->handler->start(self::FEED_URL);

        $this->assertNotWPError($result);
        $this->assertNotSame($default_id, $result['series_id']);
        $this->assertSame('My Site', $this->series_name($default_id));
    }

    /**
     * A feed whose title collides with an existing podcast is disambiguated.
     */
    public function testACollidingPodcastNameIsDisambiguated()
    {
        $this->factory()->term->create(['taxonomy' => ssp_series_taxonomy(), 'name' => 'The Audience Show']);
        $default_id = $this->create_default_series('My Site');
        $this->create_episode_in($default_id);

        $result = $this->handler->start(self::FEED_URL);

        $this->assertNotWPError($result);
        $this->assertSame('The Audience Show (2)', $this->series_name($result['series_id']));
    }

    /**
     * The wizard's later steps follow the imported podcast, not the site default.
     */
    public function testTargetSeriesFollowsTheImport()
    {
        $default_id = $this->create_default_series('My Site');
        $this->create_episode_in($default_id);

        $this->assertSame($default_id, $this->handler->get_target_series_id());

        $result = $this->handler->start(self::FEED_URL);

        $this->assertSame($result['series_id'], $this->handler->get_target_series_id());
    }

    /**
     * A target podcast that no longer exists falls back to the site default.
     */
    public function testTargetSeriesFallsBackWhenThePodcastIsGone()
    {
        $default_id = $this->create_default_series('My Site');
        $this->create_episode_in($default_id);

        $result = $this->handler->start(self::FEED_URL);
        wp_delete_term($result['series_id'], ssp_series_taxonomy());

        $this->assertSame($default_id, $this->handler->get_target_series_id());
    }

    /**
     * The import lock is held across podcast creation, so the Castos push for a
     * podcast that has no GUID yet is suppressed exactly as it is mid-import.
     */
    public function testStartHoldsTheImportLockWhileCreatingThePodcast()
    {
        $default_id = $this->create_default_series('My Site');
        $this->create_episode_in($default_id);

        $created = [];
        add_action('created_series', function ($term_id) use (&$created) {
            $created[] = RSS_Import_Handler::is_importing();
        });

        $this->handler->start(self::FEED_URL);

        $this->assertSame([true], $created);
        $this->assertTrue(RSS_Import_Handler::is_importing());
    }

    /**
     * Re-entering the same feed after a reload is not mistaken for a clash with
     * the podcast this wizard is already importing into.
     */
    public function testPreviewDoesNotRefuseTheWizardsOwnTarget()
    {
        $this->create_default_series();

        $first = $this->handler->start(self::FEED_URL);
        $this->assertNotWPError($first);

        // The import writes the feed's GUID onto the target podcast.
        ssp_update_option('data_guid', self::FEED_GUID, $first['series_id']);

        $again = $this->handler->preview(self::FEED_URL);

        $this->assertNotWPError($again);
    }

    /**
     * A site with no default podcast gets one, or the import could never sync.
     */
    public function testAnImportedPodcastBecomesDefaultWhenThereIsNone()
    {
        ssp_update_option('default_series', 0);

        $result = $this->handler->start(self::FEED_URL);

        $this->assertNotWPError($result);
        $this->assertSame($result['series_id'], ssp_get_default_series_id());
    }

    /**
     * Starting an import clears whatever a previous, abandoned import left behind.
     */
    public function testStartClearsPreviousImportProgress()
    {
        $this->create_default_series();
        RSS_Import_Handler::update_import_data('import_progress', 40);

        $this->handler->start(self::FEED_URL);

        $this->assertSame(0, RSS_Import_Handler::get_import_data('import_progress', 0));
    }
}
