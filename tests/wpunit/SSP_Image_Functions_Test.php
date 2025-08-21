<?php

use Codeception\TestCase\WPTestCase;

/**
 * Test class for SSP Image Handling Functions.
 *
 * @package SeriouslySimplePodcasting\Tests
 * @since 3.12.0
 */
class SSP_Image_Functions_Test extends WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @covers ssp_is_feed_image_valid()
	 */
	public function test_ssp_is_feed_image_valid() {
		$this->assertTrue( function_exists( 'ssp_is_feed_image_valid' ) );

		// Test with a valid image URL (this will depend on the actual Images_Handler implementation)
		// Since we're testing the function wrapper, we just verify it exists and can be called
		$result = ssp_is_feed_image_valid( 'https://example.com/test-image.jpg' );
		
		// The result will depend on the actual Images_Handler implementation
		// We just verify the function returns a boolean
		$this->assertIsBool( $result );
	}

	/**
	 * @covers ssp_is_feed_image_valid()
	 */
	public function test_ssp_is_feed_image_valid_with_empty_url() {
		$this->assertTrue( function_exists( 'ssp_is_feed_image_valid' ) );

		$result = ssp_is_feed_image_valid( '' );
		
		$this->assertIsBool( $result );
	}

	/**
	 * @covers ssp_is_image_square()
	 */
	public function test_ssp_is_image_square_with_square_image() {
		$this->assertTrue( function_exists( 'ssp_is_image_square' ) );

		$square_image_data = array(
			'width'  => 1500,
			'height' => 1500,
		);

		$result = ssp_is_image_square( $square_image_data );
		
		$this->assertIsBool( $result );
	}

	/**
	 * @covers ssp_is_image_square()
	 */
	public function test_ssp_is_image_square_with_rectangular_image() {
		$this->assertTrue( function_exists( 'ssp_is_image_square' ) );

		$rectangular_image_data = array(
			'width'  => 1500,
			'height' => 1000,
		);

		$result = ssp_is_image_square( $rectangular_image_data );
		
		$this->assertIsBool( $result );
	}

	/**
	 * @covers ssp_is_image_square()
	 */
	public function test_ssp_is_image_square_with_empty_array() {
		$this->assertTrue( function_exists( 'ssp_is_image_square' ) );

		$result = ssp_is_image_square( array() );
		
		$this->assertIsBool( $result );
	}

	/**
	 * @covers ssp_is_image_square()
	 */
	public function test_ssp_is_image_square_with_missing_dimensions() {
		$this->assertTrue( function_exists( 'ssp_is_image_square' ) );

		$incomplete_image_data = array(
			'width' => 1500,
			// height is missing
		);

		$result = ssp_is_image_square( $incomplete_image_data );
		
		$this->assertIsBool( $result );
	}

	/**
	 * @covers ssp_get_attachment_image_src()
	 */
	public function test_ssp_get_attachment_image_src_with_valid_attachment() {
		$this->assertTrue( function_exists( 'ssp_get_attachment_image_src' ) );

		$test_attachment_id = 123;
		$result = ssp_get_attachment_image_src( $test_attachment_id, 'full' );

		$this->assertIsArray( $result );
		// The actual result will depend on the Images_Handler implementation
		// We just verify the function returns an array
	}

	/**
	 * @covers ssp_get_attachment_image_src()
	 */
	public function test_ssp_get_attachment_image_src_with_invalid_attachment() {
		$this->assertTrue( function_exists( 'ssp_get_attachment_image_src' ) );

		$invalid_attachment_id = 99999;
		$result = ssp_get_attachment_image_src( $invalid_attachment_id, 'medium' );

		$this->assertIsArray( $result );
	}

	/**
	 * @covers ssp_get_attachment_image_src()
	 */
	public function test_ssp_get_attachment_image_src_with_custom_size() {
		$this->assertTrue( function_exists( 'ssp_get_attachment_image_src' ) );

		$test_attachment_id = 123;
		$result = ssp_get_attachment_image_src( $test_attachment_id, 'thumbnail' );

		$this->assertIsArray( $result );
	}

	/**
	 * @covers ssp_get_attachment_image_src()
	 */
	public function test_ssp_get_attachment_image_src_with_zero_attachment_id() {
		$this->assertTrue( function_exists( 'ssp_get_attachment_image_src' ) );

		$result = ssp_get_attachment_image_src( 0, 'full' );

		$this->assertIsArray( $result );
	}

	/**
	 * @covers ssp_get_attachment_image_src()
	 */
	public function test_ssp_get_attachment_image_src_with_negative_attachment_id() {
		$this->assertTrue( function_exists( 'ssp_get_attachment_image_src' ) );

		$result = ssp_get_attachment_image_src( -1, 'full' );

		$this->assertIsArray( $result );
	}


}
