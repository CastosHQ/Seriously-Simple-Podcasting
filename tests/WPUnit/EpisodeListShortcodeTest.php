<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Shortcodes_Controller;
use SeriouslySimplePodcasting\Presenters\Episode_List_Presenter;
use SeriouslySimplePodcasting\ShortCodes\Episode_List;

/**
 * Tests for the [ssp_episode_list] shortcode.
 *
 * Focuses on shortcode-specific behavior: registration, attribute mapping
 * (snake_case → camelCase), boolean casting, and CSS enqueuing.
 * HTML output correctness is already covered by PodcastListBlockTest
 * since both consumers share Episode_List_Presenter.
 *
 * @package SeriouslySimplePodcasting\Tests
 * @since 3.15.0
 */
class EpisodeListShortcodeTest extends \Codeception\TestCase\WPTestCase
{
	/**
	 * @var Episode_List_Presenter
	 */
	protected $presenter;

	/**
	 * @var Episode_List
	 */
	protected $shortcode;

	protected function setUp(): void
	{
		parent::setUp();

		$this->presenter = new Episode_List_Presenter(
			ssp_get_service( 'episode_repository' ),
			ssp_get_service( 'players_controller' ),
			ssp_get_service( 'renderer' )
		);

		$this->shortcode = new Episode_List( $this->presenter );
	}

	/**
	 * Helper: create a published podcast episode with an audio file.
	 */
	protected function create_episode( $title = 'Test Episode', $series = array() ) {
		$episode_id = $this->factory()->post->create( array(
			'post_type'    => 'podcast',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => 'Excerpt for ' . $title,
		) );

		update_post_meta( $episode_id, 'audio_file', 'https://example.com/episode.mp3' );

		if ( ! empty( $series ) ) {
			wp_set_post_terms( $episode_id, $series, 'series' );
		}

		return $episode_id;
	}

	// =========================================================================
	// Registration
	// =========================================================================

	// =========================================================================
	// Attribute mapping
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testDefaultAttributesProduceValidOutput()
	{
		$this->create_episode( 'Default Attrs Episode' );

		$output = $this->shortcode->shortcode( array() );

		$this->assertStringContainsString( 'ssp-podcast-list', $output );
		$this->assertStringContainsString( 'Default Attrs Episode', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testPodcastIdAttributeFiltersSeries()
	{
		$series = $this->factory()->term->create( array(
			'taxonomy' => 'series',
			'name'     => 'Filtered Series',
		) );

		$this->create_episode( 'In Series', array( $series ) );
		$this->create_episode( 'Not In Series' );

		$output = $this->shortcode->shortcode( array( 'podcast_id' => $series ) );

		$this->assertStringContainsString( 'In Series', $output );
		$this->assertStringNotContainsString( 'Not In Series', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testBooleanAttributesCastFromStrings()
	{
		$this->create_episode( 'Bool Test Episode' );

		// display_title="false" should hide titles
		$output = $this->shortcode->shortcode( array(
			'display_title' => 'false',
		) );

		$this->assertStringNotContainsString( 'entry-title-link', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testBooleanAttributesAcceptNumericStrings()
	{
		$this->create_episode( 'Numeric Bool Episode' );

		// display_title="1" should show titles
		$output_with = $this->shortcode->shortcode( array(
			'display_title' => '1',
		) );
		$this->assertStringContainsString( 'entry-title-link', $output_with );

		// display_title="0" should hide titles
		$output_without = $this->shortcode->shortcode( array(
			'display_title' => '0',
		) );
		$this->assertStringNotContainsString( 'entry-title-link', $output_without );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testColumnsAttribute()
	{
		$this->create_episode( 'Columns Test' );

		$output = $this->shortcode->shortcode( array( 'columns' => '3' ) );

		$this->assertStringContainsString( 'ssp-podcast-list', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testOrderByAttribute()
	{
		$this->create_episode( 'Episode A' );
		$this->create_episode( 'Episode B' );

		$output_asc = $this->shortcode->shortcode( array(
			'order_by' => 'title',
			'order'    => 'asc',
		) );

		$pos_a = strpos( $output_asc, 'Episode A' );
		$pos_b = strpos( $output_asc, 'Episode B' );

		$this->assertNotFalse( $pos_a );
		$this->assertNotFalse( $pos_b );
		$this->assertLessThan( $pos_b, $pos_a, 'Episode A should appear before Episode B in ASC title order' );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testPostsPerPageAttribute()
	{
		$this->create_episode( 'Episode 1' );
		$this->create_episode( 'Episode 2' );
		$this->create_episode( 'Episode 3' );

		$output = $this->shortcode->shortcode( array( 'posts_per_page' => '2' ) );

		// Should contain pagination since 3 episodes > 2 per page
		$this->assertStringContainsString( 'ssp-podcast-list__pagination', $output );
	}

	// =========================================================================
	// CSS enqueuing
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testCssEnqueuedOnRender()
	{
		$this->create_episode( 'CSS Test Episode' );

		// Register the style first (normally done by the block)
		wp_register_style( 'ssp-podcast-list', 'https://example.com/podcast-list.css' );

		$this->shortcode->shortcode( array() );

		$this->assertTrue( wp_style_is( 'ssp-podcast-list', 'enqueued' ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testCssFallbackRegistrationWithoutBlock()
	{
		$this->create_episode( 'CSS Fallback Episode' );

		// Deregister to simulate Classic Editor (block system not loaded).
		wp_deregister_style( 'ssp-podcast-list' );

		$this->shortcode->shortcode( array() );

		$this->assertTrue( wp_style_is( 'ssp-podcast-list', 'registered' ), 'Style should be registered by fallback' );
		$this->assertTrue( wp_style_is( 'ssp-podcast-list', 'enqueued' ), 'Style should be enqueued after fallback registration' );
	}

	// =========================================================================
	// Output parity with block
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testShortcodeOutputMatchesPresenterOutput()
	{
		$this->create_episode( 'Parity Test Episode' );

		$shortcode_output = $this->shortcode->shortcode( array() );

		// Same defaults via presenter directly
		$presenter_output = $this->presenter->render( array(
			'selectedPodcast'    => '-1',
			'postsPerPage'       => '0',
			'orderBy'            => 'date',
			'order'              => 'desc',
			'columnsPerRow'      => '1',
			'player'             => false,
			'excerpt'            => false,
			'showTitle'          => true,
			'featuredImage'      => true,
			'featuredImageSize'  => 'full',
			'titleSize'          => '16',
			'titleUnderImage'    => false,
			'playerBelowExcerpt' => false,
		) );

		$this->assertEquals( $presenter_output, $shortcode_output );
	}

	// =========================================================================
	// Edge cases
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testEmptyEpisodesReturnsNotFoundMessage()
	{
		$output = $this->shortcode->shortcode( array() );

		$this->assertStringContainsString( 'Sorry, episodes not found', $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\ShortCodes\Episode_List::shortcode()
	 */
	public function testUnknownAttributesAreIgnored()
	{
		$this->create_episode( 'Unknown Attrs Episode' );

		$output = $this->shortcode->shortcode( array(
			'nonexistent_attr' => 'value',
			'display_title'    => 'true',
		) );

		$this->assertStringContainsString( 'ssp-podcast-list', $output );
		$this->assertStringContainsString( 'Unknown Attrs Episode', $output );
	}

	// =========================================================================
	// Registration (last — fires init which triggers block re-registration notices)
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Controllers\Shortcodes_Controller::register_shortcodes()
	 */
	public function testShortcodeIsRegistered()
	{
		$this->setExpectedIncorrectUsage( 'WP_Block_Type_Registry::register' );
		$this->setExpectedIncorrectUsage( 'WP_Block_Bindings_Registry::register' );

		$controller = new Shortcodes_Controller(
			dirname( dirname( __DIR__ ) ) . '/seriously-simple-podcasting.php',
			'3.15.0',
			$this->presenter
		);

		@do_action( 'init' );

		global $shortcode_tags;
		$this->assertArrayHasKey( 'ssp_episode_list', $shortcode_tags );
	}
}
