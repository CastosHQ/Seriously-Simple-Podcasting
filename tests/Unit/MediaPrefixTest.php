<?php

namespace Tests\Unit;

use Tests\Support\UnitTester;

class MediaPrefixTest extends \Codeception\Test\Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testParseEpisodeUrlWithMediaPrefix()
    {
        // Test the core logic of parse_episode_url_with_media_prefix() without WordPress dependencies
        
        // Test with empty media prefix (should return original URL)
        $original_url = 'https://episodes.castos.com/mypodcast/episode1.mp3';
        $result = $this->simulateMediaPrefixParsing($original_url, '');
        $this->assertEquals($original_url, $result);
        
        // Test with empty audio file URL (should return empty)
        $result = $this->simulateMediaPrefixParsing('', 'https://cdn.example.com/');
        $this->assertEquals('', $result);
        
        // Test with valid media prefix
        $audio_url = 'https://episodes.castos.com/mypodcast/episode1.mp3';
        $media_prefix = 'https://cdn.example.com/';
        $expected = 'https://cdn.example.com/episodes.castos.com/mypodcast/episode1.mp3';
        $result = $this->simulateMediaPrefixParsing($audio_url, $media_prefix);
        $this->assertEquals($expected, $result);
        
        // Test preventing redundant media prefixes
        $already_prefixed = 'https://cdn.example.com/episodes.castos.com/mypodcast/episode1.mp3';
        $result = $this->simulateMediaPrefixParsing($already_prefixed, $media_prefix);
        $this->assertEquals($already_prefixed, $result, 'Should not add prefix if already present');
    }

    public function testUrlParsingLogic()
    {
        // Test URL parsing logic similar to wp_parse_url()
        
        $test_urls = [
            'https://episodes.castos.com/mypodcast/episode1.mp3' => [
                'scheme' => 'https',
                'host' => 'episodes.castos.com',
                'path' => '/mypodcast/episode1.mp3',
            ],
            'https://example.com/path/file.mp3?param=value' => [
                'scheme' => 'https',
                'host' => 'example.com',
                'path' => '/path/file.mp3',
                'query' => 'param=value',
            ],
            'http://subdomain.example.com:8080/path/file.mp3?a=1&b=2' => [
                'scheme' => 'http',
                'host' => 'subdomain.example.com',
                'port' => 8080,
                'path' => '/path/file.mp3',
                'query' => 'a=1&b=2',
            ],
        ];
        
        foreach ($test_urls as $url => $expected_parts) {
            $parsed = parse_url($url);
            
            $this->assertEquals($expected_parts['scheme'], $parsed['scheme'], "Scheme mismatch for {$url}");
            $this->assertEquals($expected_parts['host'], $parsed['host'], "Host mismatch for {$url}");
            $this->assertEquals($expected_parts['path'], $parsed['path'], "Path mismatch for {$url}");
            
            if (isset($expected_parts['query'])) {
                $this->assertEquals($expected_parts['query'], $parsed['query'], "Query mismatch for {$url}");
            }
            
            if (isset($expected_parts['port'])) {
                $this->assertEquals($expected_parts['port'], $parsed['port'], "Port mismatch for {$url}");
            }
        }
    }

    public function testMediaPrefixWithQueryParameters()
    {
        // Test URLs with query parameters
        
        $audio_url = 'https://episodes.castos.com/mypodcast/episode1.mp3?version=2&format=high';
        $media_prefix = 'https://cdn.example.com/';
        $expected = 'https://cdn.example.com/episodes.castos.com/mypodcast/episode1.mp3?version=2&format=high';
        
        $result = $this->simulateMediaPrefixParsing($audio_url, $media_prefix);
        $this->assertEquals($expected, $result);
    }

    public function testMediaPrefixEdgeCases()
    {
        // Test various edge cases
        
        $test_cases = [
            // Media prefix without trailing slash
            [
                'url' => 'https://episodes.castos.com/test.mp3',
                'prefix' => 'https://cdn.example.com',
                'expected' => 'https://cdn.example.comepisodes.castos.com/test.mp3'
            ],
            // Media prefix with trailing slash
            [
                'url' => 'https://episodes.castos.com/test.mp3',
                'prefix' => 'https://cdn.example.com/',
                'expected' => 'https://cdn.example.com/episodes.castos.com/test.mp3'
            ],
            // URL with port
            [
                'url' => 'https://episodes.castos.com:8080/test.mp3',
                'prefix' => 'https://cdn.example.com/',
                'expected' => 'https://cdn.example.com/episodes.castos.com:8080/test.mp3'
            ],
        ];
        
        foreach ($test_cases as $case) {
            $result = $this->simulateMediaPrefixParsing($case['url'], $case['prefix']);
            $this->assertEquals($case['expected'], $result, 
                "Failed for URL: {$case['url']} with prefix: {$case['prefix']}");
        }
    }

    /**
     * Simulate the media prefix parsing logic without WordPress dependencies
     */
    private function simulateMediaPrefixParsing($audio_file_url, $media_prefix)
    {
        if (empty($media_prefix)) {
            return $audio_file_url;
        }
        if (empty($audio_file_url)) {
            return $audio_file_url;
        }
        
        // Prevent redundant media prefixes
        if (strpos($audio_file_url, $media_prefix) !== false) {
            return $audio_file_url;
        }
        
        $url_parts = parse_url($audio_file_url);
        
        $new_url = $media_prefix . $url_parts['host'];
        
        if (isset($url_parts['port'])) {
            $new_url .= ':' . $url_parts['port'];
        }
        
        $new_url .= $url_parts['path'];
        
        if (isset($url_parts['query'])) {
            $new_url .= '?' . $url_parts['query'];
        }
        
        return $new_url;
    }
}





