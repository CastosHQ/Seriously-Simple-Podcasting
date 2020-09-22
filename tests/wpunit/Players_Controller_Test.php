<?php

use SeriouslySimplePodcasting\Controllers\Players_Controller;

class Players_Controller_Test extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var \WpunitTester
     */
    protected $tester;

    public function setUp(): void
    {
        // Before...
        parent::setUp();

        // Your set up methods here.
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    // Tests
    public function testItWorks()
    {
        $post = static::factory()->post->create_and_get();

        $this->assertInstanceOf(\WP_Post::class, $post);
    }

	public function test_player_controller_is_the_correct_type() {
		$this->players_controller = new Players_Controller( __FILE__, '1.0.0' );
		$this->assertInstanceOf( Players_Controller::class, $this->players_controller );
	}

	public function test_player_controller_html_player_method() {
		$this->players_controller = new Players_Controller( __FILE__, '1.0.0' );
		$podcast = $this->factory->post->create(
			[ 'post_type' => 'podcast' ]
		);
		$return = $this->players_controller->html_player();
		//$this->assertEquals( 2, $return  );
	}

}
