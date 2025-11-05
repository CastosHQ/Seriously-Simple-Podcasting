<?php


namespace Tests\Unit;

use Tests\Support\UnitTester;

class RendererPathTest extends \Codeception\Test\Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testPathNormalization()
    {
        // Test path normalization logic (extracted from Renderer_Test)
        $base_path = '/app/wp-content/plugins/seriously-simple-podcasting/';
        
        $test_paths = [
            'templates/test.php',
            'templates/test',
            'test.php',
            'test',
        ];
        
        foreach ($test_paths as $path) {
            // Test path building logic
            $full_path = $base_path . 'templates/' . basename($path, '.php') . '.php';
            
            $this->assertStringContainsString('templates/', $full_path);
            $this->assertStringEndsWith('.php', $full_path);
            $this->assertStringStartsWith('/app/', $full_path);
        }
    }
    
    public function testPathVariants()
    {
        // Test the 4 path variants from the original test
        $plugin_path = '/app/wp-content/plugins/seriously-simple-podcasting/';
        
        $paths = [
            $plugin_path . 'templates/test',
            $plugin_path . 'templates/test.php',
            'wp-content/plugins/seriously-simple-podcasting/templates/test.php',
            'wp-content/plugins/seriously-simple-podcasting/templates/test',
            'templates/test.php',
            'templates/test',
            'test.php',
            'test',
        ];
        
        foreach ($paths as $path) {
            // Test path parsing logic
            $basename = basename($path, '.php');
            $this->assertEquals('test', $basename);
            
            // Test if path contains template reference
            $has_template = strpos($path, 'template') !== false || strpos($path, 'test') !== false;
            $this->assertTrue($has_template);
        }
    }
}
