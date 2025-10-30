<?php

namespace wpunit;

use Codeception\TestCase\WPTestCase;
use SeriouslySimplePodcasting\Integrations\Blocks\Castos_Blocks;
use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Controllers\Players_Controller;
use SeriouslySimplePodcasting\Renderers\Renderer;

/**
 * Test class for SSP Podcasts Block.
 *
 * @package SeriouslySimplePodcasting\Tests
 * @since 3.13.1
 */
class SSP_Podcasts_Block_Test extends WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @var Castos_Blocks
	 */
	protected $castos_blocks;

	/**
	 * @var Admin_Notifications_Handler
	 */
	protected $admin_notices_handler;

	/**
	 * @var Episode_Repository
	 */
	protected $episode_repository;

	/**
	 * @var Players_Controller
	 */
	protected $players_controller;

	/**
	 * @var Renderer
	 */
	protected $renderer;

	public function setUp(): void {
		parent::setUp();
		
		// Initialize required dependencies using the service pattern
		$this->admin_notices_handler = ssp_get_service( 'admin_notices_handler' );
		$this->episode_repository = ssp_get_service( 'episode_repository' );
		$this->players_controller = ssp_get_service( 'players_controller' );
		$this->renderer = ssp_get_service( 'renderer' );
		
		// Initialize the Castos_Blocks class
		$this->castos_blocks = new Castos_Blocks(
			$this->admin_notices_handler,
			$this->episode_repository,
			$this->players_controller,
			$this->renderer
		);
	}

	public function tearDown(): void {
		// Dequeue CSS to prevent interference between tests
		wp_dequeue_style( 'ssp-podcast-list-shortcode' );
		parent::tearDown();
	}

	/**
	 * @covers Castos_Blocks::register_ssp_podcasts_block()
	 * Test that ssp-podcasts block is registered
	 */
	public function test_ssp_podcasts_block_registration() {
		// Trigger the init action to register blocks
		do_action( 'init' );
		
		// Check that the block is registered
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$this->assertArrayHasKey( 'seriously-simple-podcasting/ssp-podcasts', $registered_blocks );
		
		$block = $registered_blocks['seriously-simple-podcasting/ssp-podcasts'];
		$this->assertInstanceOf( 'WP_Block_Type', $block );
		$this->assertEquals( 'seriously-simple-podcasting/ssp-podcasts', $block->name );
	}

	/**
	 * @covers Castos_Blocks::ssp_podcasts_block_render_callback()
	 * Test block attributes and default values
	 */
	public function test_ssp_podcasts_block_attributes() {
		// Trigger the init action to register blocks
		do_action( 'init' );
		
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$block = $registered_blocks['seriously-simple-podcasting/ssp-podcasts'];
		
		// Test that all required attributes are defined
		$expected_attributes = array(
			'ids',
			'availablePodcasts',
			'columns',
			'sort_by',
			'sort',
			'clickable',
			'show_button',
			'show_description',
			'show_episode_count',
			'description_words',
			'description_chars',
			'background',
			'background_hover',
			'button_color',
			'button_hover_color',
			'button_text_color',
			'button_text',
			'title_color',
			'episode_count_color',
			'description_color',
		);
		
		foreach ( $expected_attributes as $attribute ) {
			$this->assertArrayHasKey( $attribute, $block->attributes, "Attribute '{$attribute}' should be defined" );
		}

		// availablePodcasts should default to an array of options
		$this->assertIsArray( $block->attributes['availablePodcasts']['default'] );
		
		// Test default values match shortcode defaults
		$this->assertEquals( array( '-1' ), $block->attributes['ids']['default'] );
		$this->assertEquals( 1, $block->attributes['columns']['default'] );
		$this->assertEquals( 'id', $block->attributes['sort_by']['default'] );
		$this->assertEquals( 'asc', $block->attributes['sort']['default'] );
		$this->assertEquals( 'button', $block->attributes['clickable']['default'] );
		$this->assertEquals( 'true', $block->attributes['show_button']['default'] );
		$this->assertEquals( 'true', $block->attributes['show_description']['default'] );
		$this->assertEquals( 'true', $block->attributes['show_episode_count']['default'] );
		$this->assertEquals( 0, $block->attributes['description_words']['default'] );
		$this->assertEquals( 0, $block->attributes['description_chars']['default'] );
		$this->assertEquals( '#f8f9fa', $block->attributes['background']['default'] );
		$this->assertEquals( '#e9ecef', $block->attributes['background_hover']['default'] );
		$this->assertEquals( '#343a40', $block->attributes['button_color']['default'] );
		$this->assertEquals( '#495057', $block->attributes['button_hover_color']['default'] );
		$this->assertEquals( '#ffffff', $block->attributes['button_text_color']['default'] );
		$this->assertEquals( '#6c5ce7', $block->attributes['title_color']['default'] );
		$this->assertEquals( '#6c757d', $block->attributes['episode_count_color']['default'] );
		$this->assertEquals( '#6c757d', $block->attributes['description_color']['default'] );
	}

	/**
	 * @covers Castos_Blocks::ssp_podcasts_block_render_callback()
	 * Test block render callback functionality
	 */
	public function test_ssp_podcasts_block_render_callback() {
		// Create test podcast series
		$series1 = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast 1',
			'slug'     => 'test-podcast-1'
		) );
		
		$series2 = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast 2',
			'slug'     => 'test-podcast-2'
		) );

		// Create test episodes for each series
		$episode1 = $this->factory->post->create( array(
			'post_type'   => 'podcast',
			'post_status' => 'publish',
			'post_title'  => 'Episode 1'
		) );
		wp_set_post_terms( $episode1, array( $series1 ), 'series' );

		$episode2 = $this->factory->post->create( array(
			'post_type'   => 'podcast',
			'post_status' => 'publish',
			'post_title'  => 'Episode 2'
		) );
		wp_set_post_terms( $episode2, array( $series2 ), 'series' );

		// Trigger the init action to register blocks
		do_action( 'init' );
		
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$block = $registered_blocks['seriously-simple-podcasting/ssp-podcasts'];
		
		// Test render callback with default attributes
		$attributes = array();
		$output = call_user_func( $block->render_callback, $attributes );
		
		// Verify output contains expected elements
		$this->assertStringContainsString( 'ssp-podcasts', $output );
		$this->assertStringContainsString( 'Test Podcast 1', $output );
		$this->assertStringContainsString( 'Test Podcast 2', $output );
	}

	/**
	 * @covers Castos_Blocks::ssp_podcasts_block_render_callback()
	 * Test block render callback with specific attributes
	 */
	public function test_ssp_podcasts_block_render_callback_with_attributes() {
		// Create test podcast series
		$series = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast',
			'slug'     => 'test-podcast'
		) );

		// Create test episode for the series
		$episode = $this->factory->post->create( array(
			'post_type'   => 'podcast',
			'post_status' => 'publish',
			'post_title'  => 'Test Episode'
		) );
		wp_set_post_terms( $episode, array( $series ), 'series' );

		// Trigger the init action to register blocks
		do_action( 'init' );
		
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$block = $registered_blocks['seriously-simple-podcasting/ssp-podcasts'];
		
		// Test render callback with specific attributes
		$attributes = array(
			'ids' => array( strval( $series ) ),
			'columns' => 2,
			'sort_by' => 'name',
			'clickable' => 'card',
			'show_button' => 'false',
			'button_text' => 'Custom Button Text'
		);
		
		$output = call_user_func( $block->render_callback, $attributes );
		
		// Verify output contains expected elements and attributes
		$this->assertStringContainsString( 'ssp-podcasts', $output );
		$this->assertStringContainsString( 'Test Podcast', $output );
		$this->assertStringContainsString( 'ssp-podcasts-columns-2', $output );
		$this->assertStringContainsString( 'ssp-podcast-card-clickable', $output );
	}

	/**
	 * @covers Castos_Blocks::ssp_podcasts_block_render_callback()
	 * Test block render callback with color attributes
	 */
	public function test_ssp_podcasts_block_render_callback_with_colors() {
		// Create test podcast series
		$series = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast',
			'slug'     => 'test-podcast'
		) );

		// Trigger the init action to register blocks
		do_action( 'init' );
		
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$block = $registered_blocks['seriously-simple-podcasting/ssp-podcasts'];
		
		// Test render callback with color attributes
		$attributes = array(
			'ids' => array( strval( $series ) ),
			'background' => '#ff0000',
			'button_color' => '#00ff00',
			'title_color' => '#0000ff',
			'button_text' => 'Listen Now'
		);
		
		$output = call_user_func( $block->render_callback, $attributes );
		
		// Verify output contains CSS variables for colors
		$this->assertStringContainsString( 'ssp-podcasts', $output );
		$this->assertStringContainsString( '--ssp-podcast-card-bg: #ff0000', $output );
		$this->assertStringContainsString( '--ssp-button-bg: #00ff00', $output );
		$this->assertStringContainsString( '--ssp-title-color: #0000ff', $output );
	}

	/**
	 * @covers Castos_Blocks::ssp_podcasts_block_render_callback()
	 * Test block render callback with description truncation
	 */
	public function test_ssp_podcasts_block_render_callback_with_description_truncation() {
		// Create test podcast series with long description
		$series = $this->factory->term->create( array(
			'taxonomy'    => 'series',
			'name'        => 'Test Podcast',
			'slug'        => 'test-podcast',
			'description' => 'This is a very long test podcast description that contains many words to test the truncation functionality properly'
		) );

		// Trigger the init action to register blocks
		do_action( 'init' );
		
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$block = $registered_blocks['seriously-simple-podcasting/ssp-podcasts'];
		
		// Test render callback with word truncation
		$attributes = array(
			'ids' => array( strval( $series ) ),
			'description_words' => 5
		);
		
		$output = call_user_func( $block->render_callback, $attributes );
		
		// Verify description is truncated
		$this->assertStringContainsString( 'ssp-podcasts', $output );
		$this->assertStringContainsString( 'This is a very long…', $output );
		
		// Test render callback with character truncation
		$attributes_chars = array(
			'ids' => array( strval( $series ) ),
			'description_chars' => 30
		);
		
		$output_chars = call_user_func( $block->render_callback, $attributes_chars );
		
		// Verify description is truncated by characters
		$this->assertStringContainsString( 'This is a very long test podc…', $output_chars );
	}

	/**
	 * @covers Castos_Blocks::register_ssp_podcasts_block()
	 * Test block JavaScript component loading
	 */
	public function test_ssp_podcasts_block_editor_script() {
		// Trigger the init action to register blocks
		do_action( 'init' );
		
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$block = $registered_blocks['seriously-simple-podcasting/ssp-podcasts'];
		
		// Test that editor script is registered
		$this->assertEquals( 'ssp-block-script', $block->editor_script );
	}

	/**
	 * @covers Castos_Blocks::register_ssp_podcasts_block()
	 * Test block CSS asset registration
	 */
	public function test_ssp_podcasts_block_css_assets() {
		// Check that CSS is registered
		$this->assertTrue( wp_style_is( 'ssp-podcast-list-shortcode', 'registered' ) );
		
		// Test that CSS is not enqueued by default
		$this->assertFalse( wp_style_is( 'ssp-podcast-list-shortcode', 'enqueued' ) );
	}

	/**
	 * @covers Castos_Blocks::ssp_podcasts_block_render_callback()
	 * Test block render callback with invalid parameters
	 */
	public function test_ssp_podcasts_block_render_callback_invalid_parameters() {
		// Trigger the init action to register blocks
		do_action( 'init' );
		
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$block = $registered_blocks['seriously-simple-podcasting/ssp-podcasts'];
		
		// Test render callback with invalid attributes
		$attributes = array(
			'columns' => 10, // Invalid: should be clamped to 3
			'sort_by' => 'invalid', // Invalid: should fallback to 'id'
			'clickable' => 'invalid', // Invalid: should fallback to 'button'
			'background' => 'invalid-color', // Invalid: should fallback to default
		);
		
		$output = call_user_func( $block->render_callback, $attributes );
		
		// Should still render without errors
		$this->assertStringContainsString( 'ssp-podcasts', $output );
		$this->assertStringContainsString( 'ssp-podcasts-columns-3', $output ); // Should be clamped to 3
	}

	/**
	 * @covers Castos_Blocks::ssp_podcasts_block_render_callback()
	 * Test block render callback with empty results
	 */
	public function test_ssp_podcasts_block_render_callback_empty_results() {
		// Trigger the init action to register blocks
		do_action( 'init' );
		
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$block = $registered_blocks['seriously-simple-podcasting/ssp-podcasts'];
		
		// Test render callback with invalid podcast IDs
		$attributes = array(
			'ids' => array( '999', '998' ) // Non-existent podcast IDs
		);
		
		$output = call_user_func( $block->render_callback, $attributes );
		
		// Should return empty wrapper for consistency
		$this->assertStringContainsString( 'ssp-podcasts', $output );
		// Should not contain any podcast data
		$this->assertStringNotContainsString( 'episode', $output );
	}
}
