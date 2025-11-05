<?php

namespace Tests\Unit;

use Tests\Support\UnitTester;

class FeedUrlTest extends \Codeception\Test\Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testFeedUrlGenerationLogic()
    {
        // Test the core logic of ssp_get_feed_url() without WordPress dependencies
        
        $base_url = 'https://example.com/';
        $feed_slug = 'podcast';
        
        // Test with pretty permalinks
        $pretty_permalink_cases = [
            // Default series
            ['series' => '', 'expected' => 'https://example.com/feed/podcast/'],
            ['series' => 'default', 'expected' => 'https://example.com/feed/podcast/'],
            // Custom series
            ['series' => 'my-series', 'expected' => 'https://example.com/feed/podcast/my-series/'],
            ['series' => 'tech-talk', 'expected' => 'https://example.com/feed/podcast/tech-talk/'],
        ];
        
        foreach ($pretty_permalink_cases as $case) {
            $result = $this->simulateFeedUrlGeneration($base_url, $feed_slug, $case['series'], true);
            $this->assertEquals($case['expected'], $result, 
                "Pretty permalink failed for series: '{$case['series']}'");
        }
        
        // Test without pretty permalinks
        $plain_permalink_cases = [
            // Default series
            ['series' => '', 'expected' => 'https://example.com/?feed=podcast'],
            ['series' => 'default', 'expected' => 'https://example.com/?feed=podcast'],
            // Custom series
            ['series' => 'my-series', 'expected' => 'https://example.com/?feed=podcast&podcast_series=my-series'],
            ['series' => 'tech-talk', 'expected' => 'https://example.com/?feed=podcast&podcast_series=tech-talk'],
        ];
        
        foreach ($plain_permalink_cases as $case) {
            $result = $this->simulateFeedUrlGeneration($base_url, $feed_slug, $case['series'], false);
            $this->assertEquals($case['expected'], $result, 
                "Plain permalink failed for series: '{$case['series']}'");
        }
    }

    public function testUrlTrailingSlashLogic()
    {
        // Test the trailingslashit() equivalent logic
        
        $test_cases = [
            'https://example.com' => 'https://example.com/',
            'https://example.com/' => 'https://example.com/',
            'https://example.com/path' => 'https://example.com/path/',
            'https://example.com/path/' => 'https://example.com/path/',
            '' => '/',
            '/' => '/',
        ];
        
        foreach ($test_cases as $input => $expected) {
            $result = $this->addTrailingSlash($input);
            $this->assertEquals($expected, $result, "Trailing slash failed for: '{$input}'");
        }
    }

    public function testFeedUrlWithDifferentBaseUrls()
    {
        // Test with various base URL formats
        
        $base_urls = [
            'https://example.com',
            'https://example.com/',
            'https://subdomain.example.com/',
            'https://example.com:8080/',
            'http://localhost/',
        ];
        
        foreach ($base_urls as $base_url) {
            $result = $this->simulateFeedUrlGeneration($base_url, 'podcast', 'my-series', true);
            
            // Should always start with the base URL (normalized)
            $normalized_base = $this->addTrailingSlash($base_url);
            $this->assertStringStartsWith($normalized_base, $result, 
                "Feed URL should start with base URL: {$base_url}");
            
            // Should contain the series
            $this->assertStringContainsString('my-series', $result, 
                "Feed URL should contain series: {$base_url}");
        }
    }

    public function testSeriesSlugSanitization()
    {
        // Test how different series slugs are handled
        
        $series_test_cases = [
            'simple' => 'simple',
            'with-dashes' => 'with-dashes',
            'with_underscores' => 'with_underscores',
            'MixedCase' => 'MixedCase', // Should preserve case in URL generation
            '123numeric' => '123numeric',
        ];
        
        foreach ($series_test_cases as $input => $expected_in_url) {
            $result = $this->simulateFeedUrlGeneration('https://example.com/', 'podcast', $input, true);
            $this->assertStringContainsString($expected_in_url, $result, 
                "Series slug '{$input}' not properly handled");
        }
    }

    public function testFeedSlugVariations()
    {
        // Test with different feed slugs
        
        $feed_slugs = ['podcast', 'episodes', 'feed', 'rss'];
        
        foreach ($feed_slugs as $slug) {
            $result = $this->simulateFeedUrlGeneration('https://example.com/', $slug, '', true);
            $expected = "https://example.com/feed/{$slug}/";
            $this->assertEquals($expected, $result, "Feed slug '{$slug}' not properly handled");
        }
    }

    /**
     * Simulate the feed URL generation logic without WordPress dependencies
     */
    private function simulateFeedUrlGeneration($home_url, $feed_slug, $series_slug = '', $has_pretty_permalinks = true)
    {
        $feed_series = $series_slug ?: 'default';
        $home_url = $this->addTrailingSlash($home_url);
        
        if ($has_pretty_permalinks) {
            $feed_url = $home_url . 'feed/' . $feed_slug;
        } else {
            $feed_url = $home_url . '?feed=' . $feed_slug;
        }
        
        if ($feed_series && 'default' !== $feed_series) {
            if ($has_pretty_permalinks) {
                $feed_url .= '/' . $feed_series;
            } else {
                $feed_url .= '&podcast_series=' . $feed_series;
            }
        }
        
        if ($has_pretty_permalinks) {
            $feed_url = $this->addTrailingSlash($feed_url);
        }
        
        return $feed_url;
    }

    /**
     * Simulate trailingslashit() function
     */
    private function addTrailingSlash($string)
    {
        if (empty($string)) {
            return '/';
        }
        
        return rtrim($string, '/') . '/';
    }
}





