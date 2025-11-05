<?php

namespace Tests\WPUnit;

use Tests\Support\WPUnitTester;

class BasicWordPressTest extends \Codeception\TestCase\WPTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // tests
    public function testWordPressIsLoaded()
    {
        // Test that WordPress is properly loaded
        global $wp_version;
        $this->assertNotEmpty($wp_version, 'WordPress version should be available');
        $this->assertTrue(defined('ABSPATH'), 'ABSPATH should be defined');
        $this->assertTrue(function_exists('get_option'), 'WordPress functions should be available');
    }

    public function testPluginIsLoaded()
    {
        // Test that our plugin is loaded
        $this->assertTrue(defined('SSP_VERSION'), 'Plugin constants should be defined');
        $this->assertTrue(function_exists('ssp_version_check'), 'Plugin functions should be available');
        
        // Test plugin is active
        $active_plugins = get_option('active_plugins', []);
        $this->assertContains('seriously-simple-podcasting/seriously-simple-podcasting.php', $active_plugins, 'Plugin should be active');
    }

    public function testDatabaseConnection()
    {
        // Test that we can interact with the database
        global $wpdb;
        
        $this->assertNotNull($wpdb, 'WordPress database object should be available');
        
        // Test we can query the database
        $result = $wpdb->get_var("SELECT 1");
        $this->assertEquals(1, $result, 'Database should be accessible');
    }

    public function testPostCreation()
    {
        // Test that we can create WordPress posts (basic WordPress functionality)
        $post_id = $this->factory()->post->create([
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_status' => 'publish'
        ]);
        
        $this->assertIsInt($post_id, 'Post creation should return an integer ID');
        $this->assertGreaterThan(0, $post_id, 'Post ID should be positive');
        
        // Verify the post exists
        $post = get_post($post_id);
        $this->assertNotNull($post, 'Created post should be retrievable');
        $this->assertEquals('Test Post', $post->post_title, 'Post title should match');
    }
}
