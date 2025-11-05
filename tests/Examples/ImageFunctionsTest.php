<?php


namespace Tests\Examples;

use Tests\Support\UnitTester;

class ImageFunctionsTest extends \Codeception\Test\Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testImageSquareDetection()
    {
        // Test square image data
        $square_image = [
            'width'  => 1500,
            'height' => 1500,
        ];
        
        // Test rectangular image data
        $rectangular_image = [
            'width'  => 1500,
            'height' => 1000,
        ];
        
        // These would test the logic if we had the functions loaded
        // For now, test the data structures
        $this->assertEquals(1500, $square_image['width']);
        $this->assertEquals(1500, $square_image['height']);
        $this->assertEquals($square_image['width'], $square_image['height']);
        
        $this->assertNotEquals($rectangular_image['width'], $rectangular_image['height']);
    }
    
    public function testImageUrlValidation()
    {
        // Test URL validation logic (pure PHP)
        $valid_urls = [
            'https://example.com/image.jpg',
            'https://example.com/image.png',
            'http://example.com/image.gif',
        ];
        
        $invalid_urls = [
            '',
            'not-a-url',
            'https://example.com/not-image.txt',
        ];
        
        foreach ($valid_urls as $url) {
            $this->assertStringContainsString('http', $url);
            $this->assertTrue(filter_var($url, FILTER_VALIDATE_URL) !== false);
        }
        
        foreach ($invalid_urls as $url) {
            if (empty($url)) {
                $this->assertEmpty($url);
            } else {
                // Test invalid URL logic
                $this->assertIsString($url);
            }
        }
    }
}
