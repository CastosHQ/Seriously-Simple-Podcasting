<?php

use Codeception\TestCase\WPTestCase;

class Upgrade_Handler_Test extends WPTestCase {
	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @var \SeriouslySimplePodcasting\Handlers\Upgrade_Handler
	 */
	protected $upgrade_handler;

	public function setUp(): void {
		parent::setUp();

		$this->upgrade_handler = ssp_app()->get_service( 'upgrade_handler' );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Upgrade_Handler::get_updated_enclosure_url
	 */
	public function test_player_controller_html_player_method() {
		$variants = array(
			'https://seriouslysimplepodcasting.s3.amazonaws.com/One-Sensitive/Intro.m4a'                                   => 'https://episodes.castos.com/One-Sensitive/Intro.m4a',
			'https://s3.amazonaws.com/seriouslysimplepodcasting/spotfight/WWE-SmackDown-Review-ABSTURZ-18.10.19.mp3'       => 'https://episodes.castos.com/spotfight/WWE-SmackDown-Review-ABSTURZ-18.10.19.mp3',
			'https://s3.us-west-001.backblazeb2.com/seriouslysimplepodcasting/thegatheringpodcast/In-suffering-take-2.mp3' => 'https://episodes.castos.com/thegatheringpodcast/In-suffering-take-2.mp3',
			'https://episodes.seriouslysimplepodcasting.com/djreecepodcast/9PMCheckIn5-22-2017.mp3'                        => 'https://episodes.castos.com/djreecepodcast/9PMCheckIn5-22-2017.mp3',
			'https://episodes.castos.com/djreecepodcast/9PMCheckIn5-22-2017.mp3'                                           => 'https://episodes.castos.com/djreecepodcast/9PMCheckIn5-22-2017.mp3',
		);

		foreach ( $variants as $url => $expected ) {
			$updated = $this->upgrade_handler->get_updated_enclosure_url( $url );
			$this->assertEquals( $expected, $updated );
		}
	}
}
