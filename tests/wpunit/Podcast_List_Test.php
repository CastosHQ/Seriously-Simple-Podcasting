<?php

namespace wpunit;

use Codeception\TestCase\WPTestCase;
use SeriouslySimplePodcasting\Controllers\Shortcodes_Controller;
use SeriouslySimplePodcasting\ShortCodes\Podcast_List;

class Podcast_List_Test extends WPTestCase {
	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @var Shortcodes_Controller
	 */
	protected $shortcodes_controller;

	public function setUp(): void {
		parent::setUp();
		
		// Initialize the shortcodes controller
		$this->shortcodes_controller = new Shortcodes_Controller( 
			dirname( dirname( __DIR__ ) ) . '/seriously-simple-podcasting.php', 
			'3.13.0' 
		);
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @covers Shortcodes_Controller::register_shortcodes()
	 * Test that ssp_podcasts shortcode is registered
	 */
	public function test_ssp_podcasts_shortcode_registration() {
		// Trigger the init action to register shortcodes
		do_action( 'init' );
		
		// Check that the shortcode is registered
		global $shortcode_tags;
		$this->assertArrayHasKey( 'ssp_podcasts', $shortcode_tags );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test shortcode output with all podcasts
	 */
	public function test_ssp_podcasts_shortcode_output_all_podcasts() {
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

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output
		$output = $podcast_list->shortcode( array() );
		
		// Verify output contains expected elements
		$this->assertStringContainsString( 'ssp-podcasts', $output );
		$this->assertStringContainsString( 'Test Podcast 1', $output );
		$this->assertStringContainsString( 'Test Podcast 2', $output );
		$this->assertStringContainsString( '1 episode', $output );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test shortcode output with specific podcast IDs
	 */
	public function test_ssp_podcasts_shortcode_output_specific_ids() {
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

		// Create test episodes
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

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with specific IDs
		$output = $podcast_list->shortcode( array( 'ids' => $series1 ) );
		
		// Verify output contains only the specified podcast
		$this->assertStringContainsString( 'ssp-podcasts', $output );
		$this->assertStringContainsString( 'Test Podcast 1', $output );
		$this->assertStringNotContainsString( 'Test Podcast 2', $output );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test episode count calculation for published episodes only
	 */
	public function test_episode_count_calculation_published_only() {
		// Create test podcast series
		$series = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast',
			'slug'     => 'test-podcast'
		) );

		// Create published episode
		$published_episode = $this->factory->post->create( array(
			'post_type'   => 'podcast',
			'post_status' => 'publish',
			'post_title'  => 'Published Episode'
		) );
		wp_set_post_terms( $published_episode, array( $series ), 'series' );

		// Create draft episode (should not be counted)
		$draft_episode = $this->factory->post->create( array(
			'post_type'   => 'podcast',
			'post_status' => 'draft',
			'post_title'  => 'Draft Episode'
		) );
		wp_set_post_terms( $draft_episode, array( $series ), 'series' );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output
		$output = $podcast_list->shortcode( array( 'ids' => $series ) );
		
		// Verify only published episodes are counted
		$this->assertStringContainsString( '1 episode', $output );
		$this->assertStringNotContainsString( '2 episodes', $output );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test podcast data retrieval (title, description, cover image)
	 */
	public function test_podcast_data_retrieval() {
		// Create test podcast series with description
		$series = $this->factory->term->create( array(
			'taxonomy'    => 'series',
			'name'        => 'Test Podcast with Description',
			'slug'        => 'test-podcast-description',
			'description' => 'This is a test podcast description'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output
		$output = $podcast_list->shortcode( array( 'ids' => $series ) );
		
		// Verify podcast data is retrieved and displayed
		$this->assertStringContainsString( 'Test Podcast with Description', $output );
		$this->assertStringContainsString( 'This is a test podcast description', $output );
		$this->assertStringContainsString( '0 episodes', $output ); // No episodes created
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test shortcode with invalid IDs parameter
	 */
	public function test_ssp_podcasts_shortcode_invalid_ids() {
		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with invalid IDs
		$output = $podcast_list->shortcode( array( 'ids' => '999,998' ) );
		
		// Should return empty wrapper for consistency
		$this->assertStringContainsString( 'ssp-podcasts', $output );
		// Should not contain any podcast data
		$this->assertStringNotContainsString( 'episode', $output );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test columns parameter with valid values
	 */
	public function test_ssp_podcasts_shortcode_columns_parameter() {
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

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with different column values
		$output_1 = $podcast_list->shortcode( array( 'columns' => 1 ) );
		$output_2 = $podcast_list->shortcode( array( 'columns' => 2 ) );
		$output_3 = $podcast_list->shortcode( array( 'columns' => 3 ) );
		
		// Verify CSS classes are applied correctly
		$this->assertStringContainsString( 'ssp-podcasts-columns-1', $output_1 );
		$this->assertStringContainsString( 'ssp-podcasts-columns-2', $output_2 );
		$this->assertStringContainsString( 'ssp-podcasts-columns-3', $output_3 );
	}

	/**
	 * @covers Podcast_List::validate_columns_parameter()
	 * Test columns parameter validation with invalid values
	 */
	public function test_ssp_podcasts_shortcode_columns_validation() {
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

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with invalid column values (should be clamped to valid range)
		$output_negative = $podcast_list->shortcode( array( 'columns' => -1 ) );
		$output_zero = $podcast_list->shortcode( array( 'columns' => 0 ) );
		$output_high = $podcast_list->shortcode( array( 'columns' => 10 ) );
		$output_string = $podcast_list->shortcode( array( 'columns' => 'invalid' ) );
		
		// Negative and zero values should default to 1 column, high values should be clamped to 3
		$this->assertStringContainsString( 'ssp-podcasts-columns-1', $output_negative );
		$this->assertStringContainsString( 'ssp-podcasts-columns-1', $output_zero );
		$this->assertStringContainsString( 'ssp-podcasts-columns-3', $output_high );
		$this->assertStringContainsString( 'ssp-podcasts-columns-1', $output_string );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test columns parameter with both ids and columns
	 */
	public function test_ssp_podcasts_shortcode_ids_and_columns() {
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

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with both parameters
		$output = $podcast_list->shortcode( array( 
			'ids' => $series1 . ',' . $series2,
			'columns' => 2
		) );
		
		// Verify both parameters work together
		$this->assertStringContainsString( 'ssp-podcasts-columns-2', $output );
		$this->assertStringContainsString( 'Test Podcast 1', $output );
		$this->assertStringContainsString( 'Test Podcast 2', $output );
	}

	/**
	 * @covers Podcast_List::validate_sort_by_parameter()
	 * Test sort_by parameter validation with valid values
	 */
	public function test_sort_by_parameter_validation() {
		// Create test podcast series
		$series = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast',
			'slug'     => 'test-podcast'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with valid sort_by values
		$output_id = $podcast_list->shortcode( array( 'sort_by' => 'id' ) );
		$output_name = $podcast_list->shortcode( array( 'sort_by' => 'name' ) );
		$output_episode_count = $podcast_list->shortcode( array( 'sort_by' => 'episode_count' ) );
		
		// All should work without errors
		$this->assertStringContainsString( 'ssp-podcasts', $output_id );
		$this->assertStringContainsString( 'ssp-podcasts', $output_name );
		$this->assertStringContainsString( 'ssp-podcasts', $output_episode_count );
	}

	/**
	 * @covers Podcast_List::validate_sort_by_parameter()
	 * Test sort_by parameter validation with invalid values (should fall back to default)
	 */
	public function test_sort_by_parameter_invalid_values() {
		// Create test podcast series
		$series = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast',
			'slug'     => 'test-podcast'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with invalid sort_by values (should fall back to 'id')
		$output_invalid = $podcast_list->shortcode( array( 'sort_by' => 'invalid' ) );
		$output_empty = $podcast_list->shortcode( array( 'sort_by' => '' ) );
		$output_number = $podcast_list->shortcode( array( 'sort_by' => '123' ) );
		
		// All should work without errors (fallback to default)
		$this->assertStringContainsString( 'ssp-podcasts', $output_invalid );
		$this->assertStringContainsString( 'ssp-podcasts', $output_empty );
		$this->assertStringContainsString( 'ssp-podcasts', $output_number );
	}

	/**
	 * @covers Podcast_List::validate_sort_parameter()
	 * Test sort parameter validation with valid values
	 */
	public function test_sort_parameter_validation() {
		// Create test podcast series
		$series = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast',
			'slug'     => 'test-podcast'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with valid sort values
		$output_asc = $podcast_list->shortcode( array( 'sort' => 'asc' ) );
		$output_desc = $podcast_list->shortcode( array( 'sort' => 'desc' ) );
		
		// All should work without errors
		$this->assertStringContainsString( 'ssp-podcasts', $output_asc );
		$this->assertStringContainsString( 'ssp-podcasts', $output_desc );
	}

	/**
	 * @covers Podcast_List::validate_sort_parameter()
	 * Test sort parameter validation with invalid values (should fall back to default)
	 */
	public function test_sort_parameter_invalid_values() {
		// Create test podcast series
		$series = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast',
			'slug'     => 'test-podcast'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with invalid sort values (should fall back to 'asc')
		$output_invalid = $podcast_list->shortcode( array( 'sort' => 'invalid' ) );
		$output_empty = $podcast_list->shortcode( array( 'sort' => '' ) );
		$output_number = $podcast_list->shortcode( array( 'sort' => '123' ) );
		
		// All should work without errors (fallback to default)
		$this->assertStringContainsString( 'ssp-podcasts', $output_invalid );
		$this->assertStringContainsString( 'ssp-podcasts', $output_empty );
		$this->assertStringContainsString( 'ssp-podcasts', $output_number );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test sorting is ignored when ids parameter is specified
	 */
	public function test_sorting_ignored_with_ids() {
		// Create test podcast series with different names for sorting
		$series1 = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Zebra Podcast',
			'slug'     => 'zebra-podcast'
		) );
		
		$series2 = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Apple Podcast',
			'slug'     => 'apple-podcast'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test that when ids are specified, sorting is ignored (order should be by ids order)
		$output = $podcast_list->shortcode( array( 
			'ids' => $series1 . ',' . $series2,
			'sort_by' => 'name',
			'sort' => 'asc'
		) );
		
		// Should contain both podcasts
		$this->assertStringContainsString( 'Zebra Podcast', $output );
		$this->assertStringContainsString( 'Apple Podcast', $output );
		// The exact order depends on the IDs parameter, not the sorting
	}

	/**
	 * @covers Podcast_List::validate_clickable_parameter()
	 * Test clickable parameter validation with valid values
	 */
	public function test_clickable_parameter_validation() {
		// Create test podcast series
		$series = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast',
			'slug'     => 'test-podcast'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with valid clickable values
		$output_button = $podcast_list->shortcode( array( 'clickable' => 'button' ) );
		$output_card = $podcast_list->shortcode( array( 'clickable' => 'card' ) );
		$output_title = $podcast_list->shortcode( array( 'clickable' => 'title' ) );
		
		// All should work without errors
		$this->assertStringContainsString( 'ssp-podcasts', $output_button );
		$this->assertStringContainsString( 'ssp-podcasts', $output_card );
		$this->assertStringContainsString( 'ssp-podcasts', $output_title );
	}

	/**
	 * @covers Podcast_List::validate_clickable_parameter()
	 * Test clickable parameter validation with invalid values (should fall back to default)
	 */
	public function test_clickable_parameter_invalid_values() {
		// Create test podcast series
		$series = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast',
			'slug'     => 'test-podcast'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with invalid clickable values (should fall back to 'button')
		$output_invalid = $podcast_list->shortcode( array( 'clickable' => 'invalid' ) );
		$output_empty = $podcast_list->shortcode( array( 'clickable' => '' ) );
		$output_number = $podcast_list->shortcode( array( 'clickable' => '123' ) );
		
		// All should work without errors (fallback to default)
		$this->assertStringContainsString( 'ssp-podcasts', $output_invalid );
		$this->assertStringContainsString( 'ssp-podcasts', $output_empty );
		$this->assertStringContainsString( 'ssp-podcasts', $output_number );
	}

	/**
	 * @covers Podcast_List::validate_hide_button_parameter()
	 * Test hide_button parameter validation with various boolean values
	 */
	public function test_hide_button_parameter_validation() {
		// Create test podcast series
		$series = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast',
			'slug'     => 'test-podcast'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with various boolean values
		$output_true = $podcast_list->shortcode( array( 'hide_button' => 'true' ) );
		$output_false = $podcast_list->shortcode( array( 'hide_button' => 'false' ) );
		$output_1 = $podcast_list->shortcode( array( 'hide_button' => '1' ) );
		$output_0 = $podcast_list->shortcode( array( 'hide_button' => '0' ) );
		
		// All should work without errors
		$this->assertStringContainsString( 'ssp-podcasts', $output_true );
		$this->assertStringContainsString( 'ssp-podcasts', $output_false );
		$this->assertStringContainsString( 'ssp-podcasts', $output_1 );
		$this->assertStringContainsString( 'ssp-podcasts', $output_0 );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test auto-adjustment: if show_button=false and clickable=button, set clickable=title
	 */
	public function test_auto_adjustment_show_button_false_and_clickable_button() {
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

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test auto-adjustment when show_button=false and clickable=button
		$output = $podcast_list->shortcode( array( 
			'ids' => $series,
			'clickable' => 'button',
			'show_button' => 'false'
		) );
		
		// Should not contain the actual listen now button element (because show_button=false)
		$this->assertStringNotContainsString( 'Listen Now →', $output );
		// Should contain title clickability instead
		$this->assertStringContainsString( 'ssp-podcast-title-link', $output );
		// Should work without errors
		$this->assertStringContainsString( 'ssp-podcasts', $output );
	}

	/**
	 * @covers Podcast_List::validate_show_description_parameter()
	 * Test show_description parameter validation with various boolean values
	 */
	public function test_show_description_parameter_validation() {
		// Create test podcast series with description
		$series = $this->factory->term->create( array(
			'taxonomy'    => 'series',
			'name'        => 'Test Podcast',
			'slug'        => 'test-podcast',
			'description' => 'This is a test podcast description'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with various boolean values
		$output_true = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_description' => 'true'
		) );
		$output_false = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_description' => 'false'
		) );
		$output_1 = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_description' => '1'
		) );
		$output_0 = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_description' => '0'
		) );
		
		// Test that description is shown when true
		$this->assertStringContainsString( 'This is a test podcast description', $output_true );
		$this->assertStringContainsString( 'This is a test podcast description', $output_1 );
		
		// Test that description is hidden when false
		$this->assertStringNotContainsString( 'This is a test podcast description', $output_false );
		$this->assertStringNotContainsString( 'This is a test podcast description', $output_0 );
	}

	/**
	 * @covers Podcast_List::validate_show_episode_count_parameter()
	 * Test show_episode_count parameter validation with various boolean values
	 */
	public function test_show_episode_count_parameter_validation() {
		// Create test podcast series
		$series = $this->factory->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Test Podcast',
			'slug'     => 'test-podcast'
		) );

		// Create an episode for the series
		$episode = $this->factory->post->create( array(
			'post_type'   => 'podcast',
			'post_status' => 'publish',
			'post_title'  => 'Test Episode'
		) );
		wp_set_post_terms( $episode, array( $series ), 'series' );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with various boolean values
		$output_true = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_episode_count' => 'true'
		) );
		$output_false = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_episode_count' => 'false'
		) );
		$output_1 = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_episode_count' => '1'
		) );
		$output_0 = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_episode_count' => '0'
		) );
		
		// Test that episode count is shown when true
		$this->assertStringContainsString( '1 episode', $output_true );
		$this->assertStringContainsString( '1 episode', $output_1 );
		
		// Test that episode count is hidden when false
		$this->assertStringNotContainsString( '1 episode', $output_false );
		$this->assertStringNotContainsString( '1 episode', $output_0 );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test display controls with various combinations
	 */
	public function test_display_controls_combinations() {
		// Create test podcast series with description
		$series = $this->factory->term->create( array(
			'taxonomy'    => 'series',
			'name'        => 'Test Podcast',
			'slug'        => 'test-podcast',
			'description' => 'This is a test podcast description'
		) );

		// Create an episode for the series
		$episode = $this->factory->post->create( array(
			'post_type'   => 'podcast',
			'post_status' => 'publish',
			'post_title'  => 'Test Episode'
		) );
		wp_set_post_terms( $episode, array( $series ), 'series' );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test with both description and episode count hidden
		$output_both_hidden = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_description' => 'false',
			'show_episode_count' => 'false'
		) );
		
		// Test with description hidden but episode count shown
		$output_desc_hidden = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_description' => 'false',
			'show_episode_count' => 'true'
		) );
		
		// Test with episode count hidden but description shown
		$output_count_hidden = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_description' => 'true',
			'show_episode_count' => 'false'
		) );
		
		// Test with both shown (default behavior)
		$output_both_shown = $podcast_list->shortcode( array( 
			'ids' => $series,
			'show_description' => 'true',
			'show_episode_count' => 'true'
		) );
		
		// Verify both hidden
		$this->assertStringNotContainsString( 'This is a test podcast description', $output_both_hidden );
		$this->assertStringNotContainsString( '1 episode', $output_both_hidden );
		
		// Verify description hidden, episode count shown
		$this->assertStringNotContainsString( 'This is a test podcast description', $output_desc_hidden );
		$this->assertStringContainsString( '1 episode', $output_desc_hidden );
		
		// Verify episode count hidden, description shown
		$this->assertStringContainsString( 'This is a test podcast description', $output_count_hidden );
		$this->assertStringNotContainsString( '1 episode', $output_count_hidden );
		
		// Verify both shown
		$this->assertStringContainsString( 'This is a test podcast description', $output_both_shown );
		$this->assertStringContainsString( '1 episode', $output_both_shown );
	}

	/**
	 * @covers Podcast_List::validate_description_words_parameter()
	 * Test description_words parameter validation with valid values
	 */
	public function test_description_words_parameter_validation() {
		// Create test podcast series with long description
		$series = $this->factory->term->create( array(
			'taxonomy'    => 'series',
			'name'        => 'Test Podcast',
			'slug'        => 'test-podcast',
			'description' => 'This is a very long test podcast description that contains many words to test the word truncation functionality properly'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with different word limits
		$output_5_words = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_words' => 5
		) );
		$output_10_words = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_words' => 10
		) );
		$output_0_words = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_words' => 0
		) );
		
		// Test that word truncation works correctly
		$this->assertStringContainsString( 'This is a very long…', $output_5_words );
		$this->assertStringContainsString( 'This is a very long test podcast description that contains…', $output_10_words );
		$this->assertStringContainsString( 'This is a very long test podcast description that contains many words to test the word truncation functionality properly', $output_0_words );
	}

	/**
	 * @covers Podcast_List::validate_description_words_parameter()
	 * Test description_words parameter validation with invalid values (should be clamped to non-negative)
	 */
	public function test_description_words_parameter_invalid_values() {
		// Create test podcast series with description
		$series = $this->factory->term->create( array(
			'taxonomy'    => 'series',
			'name'        => 'Test Podcast',
			'slug'        => 'test-podcast',
			'description' => 'This is a test podcast description'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with invalid word values (should be clamped to 0)
		$output_negative = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_words' => -5
		) );
		$output_string = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_words' => 'invalid'
		) );
		
		// Should show full description (no truncation) when invalid values are provided
		$this->assertStringContainsString( 'This is a test podcast description', $output_negative );
		$this->assertStringContainsString( 'This is a test podcast description', $output_string );
	}

	/**
	 * @covers Podcast_List::validate_description_chars_parameter()
	 * Test description_chars parameter validation with valid values
	 */
	public function test_description_chars_parameter_validation() {
		// Create test podcast series with long description
		$series = $this->factory->term->create( array(
			'taxonomy'    => 'series',
			'name'        => 'Test Podcast',
			'slug'        => 'test-podcast',
			'description' => 'This is a very long test podcast description that contains many characters to test the character truncation functionality properly'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with different character limits
		$output_50_chars = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_chars' => 50
		) );
		$output_100_chars = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_chars' => 100
		) );
		$output_0_chars = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_chars' => 0
		) );
		
		// Test that character truncation works correctly
		$this->assertStringContainsString( 'This is a very long test podcast description that…', $output_50_chars );
		$this->assertStringContainsString( 'This is a very long test podcast description that contains many characters to test the character tr…', $output_100_chars );
		$this->assertStringContainsString( 'This is a very long test podcast description that contains many characters to test the character truncation functionality properly', $output_0_chars );
	}

	/**
	 * @covers Podcast_List::validate_description_chars_parameter()
	 * Test description_chars parameter validation with invalid values (should be clamped to non-negative)
	 */
	public function test_description_chars_parameter_invalid_values() {
		// Create test podcast series with description
		$series = $this->factory->term->create( array(
			'taxonomy'    => 'series',
			'name'        => 'Test Podcast',
			'slug'        => 'test-podcast',
			'description' => 'This is a test podcast description'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with invalid character values (should be clamped to 0)
		$output_negative = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_chars' => -10
		) );
		$output_string = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_chars' => 'invalid'
		) );
		
		// Should show full description (no truncation) when invalid values are provided
		$this->assertStringContainsString( 'This is a test podcast description', $output_negative );
		$this->assertStringContainsString( 'This is a test podcast description', $output_string );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test description truncation priority: description_chars > description_words
	 */
	public function test_description_truncation_priority() {
		// Create test podcast series with long description
		$series = $this->factory->term->create( array(
			'taxonomy'    => 'series',
			'name'        => 'Test Podcast',
			'slug'        => 'test-podcast',
			'description' => 'This is a very long test podcast description that contains many words and characters to test the truncation priority functionality properly'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test that when both parameters are provided, character limit takes priority
		$output_both_params = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_words' => 5,
			'description_chars' => 50
		) );
		
		// Should be truncated by characters (50 chars), not by words (5 words)
		$this->assertStringContainsString( 'This is a very long test podcast description that…', $output_both_params );
		// Should not contain the full text that would result from 5-word truncation
		$this->assertStringNotContainsString( 'This is a very long…', $output_both_params );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test description truncation with HTML content
	 */
	public function test_description_truncation_with_html() {
		// Create test podcast series with HTML description
		$series = $this->factory->term->create( array(
			'taxonomy'    => 'series',
			'name'        => 'Test Podcast',
			'slug'        => 'test-podcast',
			'description' => '<p>This is a <strong>very long</strong> test podcast description with <em>HTML tags</em> that should be stripped during truncation</p>'
		) );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with word truncation
		$output_words = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_words' => 5
		) );
		
		// Test shortcode output with character truncation
		$output_chars = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_chars' => 30
		) );
		
		// Should strip HTML tags and truncate properly
		$this->assertStringContainsString( 'This is a very long…', $output_words );
		$this->assertStringContainsString( 'This is a very long test podc…', $output_chars );
		// Should not contain HTML tags in output
		$this->assertStringNotContainsString( '<p>', $output_words );
		$this->assertStringNotContainsString( '<strong>', $output_words );
		$this->assertStringNotContainsString( '<em>', $output_words );
	}

	/**
	 * @covers Podcast_List::shortcode()
	 * Test description truncation with UTF-8 content
	 */
	public function test_description_truncation_with_utf8() {
		// Create test podcast series with UTF-8 description (using accented characters)
		$series = $this->factory->term->create( array(
			'taxonomy'    => 'series',
			'name'        => 'Test Podcast',
			'slug'        => 'test-podcast',
			'description' => 'This is a très long podcast description with spécial characters like café, naïve, and résumé for testing UTF-8 handling'
		) );

		// Create test episode for the series
		$episode = $this->factory->post->create( array(
			'post_type'   => 'podcast',
			'post_status' => 'publish',
			'post_title'  => 'Test Episode'
		) );
		wp_set_post_terms( $episode, array( $series ), 'series' );

		// Create the shortcode instance
		$podcast_list = new Podcast_List();
		
		// Test shortcode output with character truncation
		$output_chars = $podcast_list->shortcode( array( 
			'ids' => $series,
			'description_chars' => 50
		) );
		
		// Should handle UTF-8 characters properly
		$this->assertStringContainsString( 'This is a très long podcast description with spéc…', $output_chars );
	}
}
