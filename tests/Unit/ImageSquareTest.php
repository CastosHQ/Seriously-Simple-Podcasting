<?php

namespace Tests\Unit;

use Tests\Support\UnitTester;
use SeriouslySimplePodcasting\Handlers\Images_Handler;

class ImageSquareTest extends \Codeception\Test\Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testIsImageSquareWithSquareImage()
    {
        $images_handler = new Images_Handler();
        
        $square_image_data = [
            'width'  => 1500,
            'height' => 1500,
        ];

        $result = $images_handler->is_image_square($square_image_data);
        
        $this->assertTrue($result);
    }

    public function testIsImageSquareWithRectangularImage()
    {
        $images_handler = new Images_Handler();
        
        $rectangular_image_data = [
            'width'  => 1500,
            'height' => 1000,
        ];

        $result = $images_handler->is_image_square($rectangular_image_data);
        
        $this->assertFalse($result);
    }

    public function testIsImageSquareWithEmptyArray()
    {
        $images_handler = new Images_Handler();
        
        $result = $images_handler->is_image_square([]);
        
        $this->assertFalse($result);
    }

    public function testIsImageSquareWithMissingDimensions()
    {
        $images_handler = new Images_Handler();
        
        // Test with missing width
        $missing_width = ['height' => 1500];
        $result = $images_handler->is_image_square($missing_width);
        $this->assertFalse($result);
        
        // Test with missing height
        $missing_height = ['width' => 1500];
        $result = $images_handler->is_image_square($missing_height);
        $this->assertFalse($result);
    }

    public function testIsImageSquareWithZeroDimensions()
    {
        $images_handler = new Images_Handler();
        
        $zero_dimensions = [
            'width'  => 0,
            'height' => 0,
        ];

        $result = $images_handler->is_image_square($zero_dimensions);
        
        $this->assertTrue($result); // 0 === 0 is true
    }

    public function testIsImageSquareWithStringDimensions()
    {
        $images_handler = new Images_Handler();
        
        $string_dimensions = [
            'width'  => '1500',
            'height' => '1500',
        ];

        $result = $images_handler->is_image_square($string_dimensions);
        
        $this->assertTrue($result); // '1500' === '1500' is true
    }
}
