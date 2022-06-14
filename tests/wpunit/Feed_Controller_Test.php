<?php

use Codeception\TestCase\WPTestCase;
use SeriouslySimplePodcasting\Controllers\Feed_Controller;
use SeriouslySimplePodcasting\Controllers\Players_Controller;
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;

class Feed_Controller_Test extends WPTestCase {
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
	 * Tests Feed_Controller::load_feed_template()
	 */
	public function test_load_feed_template() {

		$this->factory->post->create(
			array(
				'title'       => 'My Custom Podcast',
				'post_status' => 'publish',
				'post_type'   => SSP_CPT_PODCAST,
			)
		);

		$feed_controller = new Feed_Controller( new Feed_Handler(), new Renderer() );

		ob_start();
		$feed_controller->load_feed_template();
		$results = ob_end_flush();

		$this->assertNotEmpty( $results );
	}
}
