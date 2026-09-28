<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Feed_Controller;

class FeedControllerTest extends \Codeception\TestCase\WPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers \SeriouslySimplePodcasting\Controllers\Feed_Controller::get_podcast_feed
     */
    public function testGetPodcastFeed()
    {
        $episode_id = $this->factory()->post->create([
            'post_title'  => 'My Test Episode',
            'post_status' => 'publish',
            'post_type'   => SSP_CPT_PODCAST,
        ]);

        update_post_meta($episode_id, 'audio_file', site_url('test.mp3'));

        $excerpt = get_the_excerpt($episode_id);

        $feed_controller = $this->getFeedController();

        $series_id = ssp_get_default_series_id();

        $feed = $feed_controller->get_podcast_feed($series_id);
        $site_url = site_url();
        global $wp_version;

        $test_parts = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            sprintf('<?xml-stylesheet type="text/xsl" href="%s/wp-content/plugins/seriously-simple-podcasting/templates/feed-stylesheet.xsl?v=2"?>', $site_url),
            '<rss version="2.0"',
            'xmlns:content="http://purl.org/rss/1.0/modules/content/"',
            'xmlns:wfw="http://wellformedweb.org/CommentAPI/"',
            'xmlns:dc="http://purl.org/dc/elements/1.1/"',
            'xmlns:atom="http://www.w3.org/2005/Atom"',
            'xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"',
            'xmlns:slash="http://purl.org/rss/1.0/modules/slash/"',
            'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"',
            'xmlns:googleplay="http://www.google.com/schemas/play-podcasts/1.0"',
            'xmlns:podcast="https://podcastindex.org/namespace/1.0"',
            'xmlns:ssp="https://castos.com/seriously-simple-podcasting/namespace/1.0"',
            '<channel>',
            '<title>WordPress Test</title>',
            sprintf('<atom:link href="%s" rel="self" type="application/rss+xml"/>', trailingslashit( $site_url )),
            sprintf('<link>%s</link>', get_term_link($series_id, ssp_series_taxonomy())),
            '<description>',
            '<lastBuildDate>',
            '<language>en-US</language>',
            '<copyright>&#xA9; ' . date( 'Y' ) . ' WordPress Test</copyright>',
            '<itunes:subtitle>',
            '<itunes:author>WordPress Test</itunes:author>',
            '<itunes:summary>',
            '<itunes:owner>',
            '<itunes:name>WordPress Test</itunes:name>',
            '<itunes:explicit>false</itunes:explicit>',
            '<googleplay:author><![CDATA[WordPress Test]]></googleplay:author>',
            '<googleplay:description></googleplay:description>',
            '<googleplay:explicit>No</googleplay:explicit>',
            '<podcast:guid>',
            sprintf('<!-- podcast_generator="SSP by Castos/%s" Seriously Simple Podcasting plugin for WordPress (https://wordpress.org/plugins/seriously-simple-podcasting/) -->', SSP_VERSION),
            sprintf('<generator>https://wordpress.org/?v=%s</generator>', $wp_version),

            // Test the item created
            '<item>',
            '<title>My Test Episode</title>',
            sprintf('<link>%s</link>', get_post_permalink($episode_id)),
            sprintf('<pubDate>%s</pubDate>', mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true, $episode_id))),
            '<dc:creator><![CDATA[WordPress Test]]></dc:creator>',
            sprintf('<guid isPermaLink="false">%s</guid>', ssp_episode_guid($episode_id)),
            sprintf('<description><![CDATA[%s]]></description>', $excerpt),
            sprintf('<itunes:subtitle><![CDATA[%s]]></itunes:subtitle>', $excerpt),
            sprintf('<content:encoded><![CDATA[%s]]></content:encoded>', $excerpt),
            sprintf('<enclosure url="%s" length="1" type="audio/mpeg"></enclosure>', site_url('test.mp3')),
            sprintf('<itunes:summary><![CDATA[%s]]></itunes:summary>', $excerpt),
            '<itunes:explicit>false</itunes:explicit>',
            '<itunes:block>no</itunes:block>',
            '<itunes:duration>0:00</itunes:duration>',
            '<itunes:author><![CDATA[WordPress Test]]></itunes:author>',
            sprintf('<googleplay:description><![CDATA[%s]]></googleplay:description>', $excerpt),
            '<googleplay:explicit>No</googleplay:explicit>',
            '<googleplay:block>no</googleplay:block>',
            '</item>',
        ];

        foreach ($test_parts as $test_part) {
            $this->assertStringContainsString($test_part, $feed);
        }
    }

    /**
     * A first feed render stores the legacy published value, never the stray native GUID.
     */
    public function testFeedStoresImportedOriginalWithoutWritingLegacyGuid()
    {
        $id = $this->createFeedEpisode();
        update_post_meta($id, 'ssp_original_guid', 'feed-original');
        update_post_meta($id, 'ssp_guid', 'stray-native');

        $this->assertSame('feed-original', $this->feedGuidFor($id));
        $this->assertSame('feed-original', get_post_meta($id, 'ssp_episode_guid', true));
        $this->assertSame('stray-native', get_post_meta($id, 'ssp_guid', true));

        update_post_meta($id, 'ssp_original_guid', 'changed-original');
        $this->assertSame('feed-original', $this->feedGuidFor($id));
    }

    /**
     * All legacy states publish the same GUID before and after the first feed render.
     */
    public function testFeedKeepsLegacyPublishedGuidForEveryFallback()
    {
        foreach (['original' => 'original-guid', 'native' => 'native-guid', 'post' => null] as $case => $guid) {
            $id = $this->createFeedEpisode();
            if ('original' === $case) {
                update_post_meta($id, 'ssp_original_guid', $guid);
                update_post_meta($id, 'ssp_guid', 'stray-guid');
            } elseif ('native' === $case) {
                update_post_meta($id, 'ssp_guid', $guid);
            } else {
                $guid = get_the_guid($id);
            }

            $this->assertSame($guid, ssp_episode_guid($id), $case . ' before storing');
            $this->assertSame($guid, $this->feedGuidFor($id), $case . ' first render');
            $this->assertSame($guid, get_post_meta($id, 'ssp_episode_guid', true), $case . ' stored');
            $this->assertSame($guid, $this->feedGuidFor($id), $case . ' second render');
            if ('post' === $case) {
                $this->assertFalse(metadata_exists('post', $id, 'ssp_guid'));
            }
        }
    }

    /**
     * Filtering only changes the output; storing retains the exact unfiltered bytes.
     */
    public function testFeedStoresUnfilteredGuidBytes()
    {
        $guids = [
            'backslash'    => 'legacy\\backslash',
            'single quote' => "legacy'quote",
            'double quote' => 'legacy"quote',
            'hex hash'     => 'd41d8cd98f00b204e9800998ecf8427e',
            'digits only'  => '01234567890123456789',
        ];
        $filter = function ($value) { return 'filtered-' . $value; };
        add_filter('ssp/episode/guid', $filter);
        try {
            foreach ($guids as $case => $guid) {
                $id = $this->createFeedEpisode();
                update_post_meta($id, 'ssp_original_guid', wp_slash($guid));
                $this->assertSame('filtered-' . $guid, $this->feedGuidFor($id), $case . ' feed output');
                $this->assertSame($guid, get_post_meta($id, 'ssp_episode_guid', true), $case . ' stored bytes');
                $this->assertFalse(metadata_exists('post', $id, 'ssp_guid'), $case);
            }
        } finally {
            remove_filter('ssp/episode/guid', $filter);
        }
    }

    private function createFeedEpisode()
    {
        $id = $this->factory()->post->create([
            'post_title' => 'GUID continuity ' . wp_generate_uuid4(),
            'post_status' => 'publish',
            'post_type' => SSP_CPT_PODCAST,
        ]);
        update_post_meta($id, 'audio_file', site_url('/episode.mp3'));
        wp_set_object_terms($id, [ssp_get_default_series_id()], ssp_series_taxonomy());
        return $id;
    }

    private function feedGuidFor($id)
    {
        $feed = $this->getFeedController()->get_podcast_feed(ssp_get_default_series_id());
        $this->assertMatchesRegularExpression('/<item>.*?<guid isPermaLink="false">/s', $feed);
        $item = get_post($id);
        $this->assertNotFalse(strpos($feed, '<title>' . esc_html($item->post_title) . '</title>'));
        $pattern = '/<item>.*?<title>' . preg_quote(esc_html($item->post_title), '/') . '<\/title>.*?<guid isPermaLink="false">(.*?)<\/guid>/s';
        $this->assertSame(1, preg_match($pattern, $feed, $match));
        return html_entity_decode($match[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * @return Feed_Controller
     */
    protected function getFeedController()
    {
        $ssp_app = new \ReflectionClass('SeriouslySimplePodcasting\Controllers\App_Controller');

        $property = $ssp_app->getProperty('feed_controller');

        $property->setAccessible(true);

        return $property->getValue(ssp_app());
    }
}

