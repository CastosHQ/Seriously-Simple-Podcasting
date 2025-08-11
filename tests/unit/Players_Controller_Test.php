<?php

use SeriouslySimplePodcasting\Controllers\Players_Controller;

class Players_Controller_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function setUp(): void {
		// Before...
		parent::setUp();

		// Your set up methods here.
	}

	public function tearDown(): void {
		// Your tear down methods here.

		// Then...
		parent::tearDown();
	}

	/**
	 * Tests that the Players_Controller::html_player method returns the new html player code
	 *
	 * @covers Players_Controller::html_player
	 * @group player-controller-html-player
	 */
	public function test_player_controller_html_player_method() {
		$this->players_controller = new Players_Controller( __FILE__, '1.0.0' );
		$episode_id               = $this->factory->post->create(
			array(
				'title'       => 'My Custom Podcast',
				'post_status' => 'publish',
				'post_type'   => 'podcast',
			)
		);
		$episode                  = get_post( $episode_id );
		$html_player_content      = $this->players_controller->html_player( $episode->ID );

		$this->assertStringContainsString( '<div id="embed-app" class="dark-mode">', $html_player_content );
		$this->assertStringContainsString( 'Your browser does not support the audio tag.', $html_player_content );
		$this->assertStringContainsString( $episode->post_title, $html_player_content );
	}
}
