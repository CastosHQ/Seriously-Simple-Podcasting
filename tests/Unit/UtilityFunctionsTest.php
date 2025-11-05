<?php


namespace Tests\Unit;

use Tests\Support\UnitTester;

class UtilityFunctionsTest extends \Codeception\Test\Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testVersionComparison()
    {
        // Test version comparison logic (from SSP_Functions_Test)
        $version1 = '1.0.0';
        $version2 = '1.0.1';
        $version3 = '1.1.0';
        $beta_version = '1.0.0-beta';
        $alpha_version = '1.0.0-alpha';
        
        // Test version string parsing
        $this->assertStringContainsString('1.0.0', $version1);
        $this->assertStringContainsString('beta', $beta_version);
        $this->assertStringContainsString('alpha', $alpha_version);
        
        // Test version comparison logic
        $this->assertTrue(version_compare($version2, $version1, '>'));
        $this->assertTrue(version_compare($version3, $version1, '>'));
        $this->assertFalse(version_compare($version1, $version2, '>'));
    }
    
    public function testStringUtilities()
    {
        // Test string manipulation that might be used in the plugin
        $podcast_title = "My Awesome Podcast";
        $slug = strtolower(str_replace(' ', '-', $podcast_title));
        
        $this->assertEquals('my-awesome-podcast', $slug);
        $this->assertStringNotContainsString(' ', $slug);
        $this->assertStringNotContainsString(strtoupper($podcast_title[0]), $slug);
    }
    
    public function testArrayUtilities()
    {
        // Test array manipulation that might be used for episode data
        $episode_data = [
            'title' => 'Episode 1',
            'duration' => '00:45:30',
            'file_size' => 25600000,
            'file_type' => 'audio/mpeg'
        ];
        
        // Test data validation
        $this->assertArrayHasKey('title', $episode_data);
        $this->assertArrayHasKey('duration', $episode_data);
        $this->assertIsString($episode_data['title']);
        $this->assertIsInt($episode_data['file_size']);
        
        // Test data transformation
        $file_size_mb = round($episode_data['file_size'] / 1024 / 1024, 2);
        $this->assertEquals(24.41, $file_size_mb);
    }
}
