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
}
