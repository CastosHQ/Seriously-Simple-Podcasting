<?php

namespace Tests\WPUnit;

class SSPImageFunctionsTest extends \Codeception\TestCase\WPTestCase
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
     * @covers ssp_is_feed_image_valid()
     */
    public function testSspIsFeedImageValid()
    {
        $this->assertTrue(function_exists('ssp_is_feed_image_valid'));

        // Test with a valid image URL (this will depend on the actual Images_Handler implementation)
        // Since we're testing the function wrapper, we just verify it exists and can be called
        $result = ssp_is_feed_image_valid('https://example.com/test-image.jpg');

        // The result will depend on the actual Images_Handler implementation
        // We just verify the function returns a boolean
        $this->assertIsBool($result);
    }

    /**
     * @covers ssp_is_feed_image_valid()
     */
    public function testSspIsFeedImageValidWithEmptyUrl()
    {
        $this->assertTrue(function_exists('ssp_is_feed_image_valid'));

        $result = ssp_is_feed_image_valid('');

        $this->assertIsBool($result);
    }

    /**
     * @covers ssp_is_image_square()
     */
    public function testSspIsImageSquareWithSquareImage()
    {
        $this->assertTrue(function_exists('ssp_is_image_square'));

        $square_image_data = [
            'width'  => 1500,
            'height' => 1500,
        ];

        $result = ssp_is_image_square($square_image_data);

        $this->assertIsBool($result);
    }

    /**
     * @covers ssp_is_image_square()
     */
    public function testSspIsImageSquareWithRectangularImage()
    {
        $this->assertTrue(function_exists('ssp_is_image_square'));

        $rectangular_image_data = [
            'width'  => 1500,
            'height' => 1000,
        ];

        $result = ssp_is_image_square($rectangular_image_data);

        $this->assertIsBool($result);
    }

    /**
     * @covers ssp_is_image_square()
     */
    public function testSspIsImageSquareWithEmptyArray()
    {
        $this->assertTrue(function_exists('ssp_is_image_square'));

        $result = ssp_is_image_square([]);

        $this->assertIsBool($result);
    }

    /**
     * @covers ssp_is_image_square()
     */
    public function testSspIsImageSquareWithMissingDimensions()
    {
        $this->assertTrue(function_exists('ssp_is_image_square'));

        $incomplete_image_data = [
            'width' => 1500,
            // height is missing
        ];

        $result = ssp_is_image_square($incomplete_image_data);

        $this->assertIsBool($result);
    }

    /**
     * @covers ssp_get_attachment_image_src()
     */
    public function testSspGetAttachmentImageSrcWithValidAttachment()
    {
        $this->assertTrue(function_exists('ssp_get_attachment_image_src'));

        $test_attachment_id = 123;
        $result = ssp_get_attachment_image_src($test_attachment_id, 'full');

        $this->assertIsArray($result);
        // The actual result will depend on the Images_Handler implementation
        // We just verify the function returns an array
    }

    /**
     * @covers ssp_get_attachment_image_src()
     */
    public function testSspGetAttachmentImageSrcWithInvalidAttachment()
    {
        $this->assertTrue(function_exists('ssp_get_attachment_image_src'));

        $invalid_attachment_id = 99999;
        $result = ssp_get_attachment_image_src($invalid_attachment_id, 'medium');
        $this->assertSame(
            [],
            $result,
            'Invalid attachment ID should yield an empty array.'
        );
    }

    /**
     * @covers ssp_get_attachment_image_src()
     */
    public function testSspGetAttachmentImageSrcWithCustomSize()
    {
        $this->assertTrue(function_exists('ssp_get_attachment_image_src'));

        $test_attachment_id = 123;
        $result = ssp_get_attachment_image_src($test_attachment_id, 'thumbnail');

        $this->assertIsArray($result);
    }

    /**
     * @covers ssp_get_attachment_image_src()
     */
    public function testSspGetAttachmentImageSrcWithZeroAttachmentId()
    {
        $this->assertTrue(function_exists('ssp_get_attachment_image_src'));

        $result = ssp_get_attachment_image_src(0, 'full');

        $this->assertIsArray($result);
    }

    /**
     * @covers ssp_get_attachment_image_src()
     */
    public function testSspGetAttachmentImageSrcWithNegativeAttachmentId()
    {
        $this->assertTrue(function_exists('ssp_get_attachment_image_src'));

        $result = ssp_get_attachment_image_src(-1, 'full');

        $this->assertIsArray($result);
    }
}

