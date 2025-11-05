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
            sprintf('<?xml-stylesheet type="text/xsl" href="%s/wp-content/plugins/seriously-simple-podcasting/templates/feed-stylesheet.xsl"?>', $site_url),
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
            '<channel>',
            '<title>WordPress Test</title>',
            sprintf('<atom:link href="%s" rel="self" type="application/rss+xml"/>', $site_url),
            sprintf('<link>%s</link>', get_term_link($series_id, ssp_series_taxonomy())),
            '<description>',
            '<lastBuildDate>',
            '<language>en-US</language>',
            '<copyright>&#xA9; 2025 WordPress Test</copyright>',
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

