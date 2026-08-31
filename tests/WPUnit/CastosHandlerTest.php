<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;

class CastosHandlerTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Castos_Handler
	 */
	private $castos_handler;

	/**
	 * Canned reply for the next Castos podcasts request.
	 *
	 * @var array
	 */
	private $podcasts_reply;

	protected function setUp(): void {
		parent::setUp();

		$this->castos_handler = ssp_get_service( 'castos_handler' );
		$this->castos_handler->clear_podcasts_cache();
		update_option( 'ss_podcasting_podmotor_account_api_token', 'test-token' );

		add_filter( 'pre_http_request', array( $this, 'reply_to_podcasts_request' ), 10, 3 );
	}

	protected function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'reply_to_podcasts_request' ) );
		$this->castos_handler->clear_podcasts_cache();
		delete_option( 'ss_podcasting_podmotor_account_api_token' );
		parent::tearDown();
	}

	public function reply_to_podcasts_request( $preempt, $args, $url ) {
		return false !== strpos( $url, 'api/v2/podcasts' ) ? $this->podcasts_reply : $preempt;
	}

	/**
	 * A well-formed reply is reported as success with its data.
	 */
	public function testGetPodcastsReturnsListOnSuccess() {
		$this->podcasts_reply = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'data' => array( 'podcast_list' => array( array( 'id' => 43, 'series_id' => 2 ) ) ) ) ),
		);

		$res = $this->castos_handler->get_podcasts();

		$this->assertSame( 'success', $res['status'] );
		$this->assertSame( 43, $res['data']['podcast_list'][0]['id'] );
	}

	/**
	 * An error page or a non-200 reply must not be reported as success — the
	 * Hosting tab used to read a missing podcast_list from it.
	 *
	 * @dataProvider bad_replies
	 */
	public function testGetPodcastsRejectsMalformedReplies( $code, $body ) {
		$this->podcasts_reply = array(
			'response' => array( 'code' => $code ),
			'body'     => $body,
		);

		$res = $this->castos_handler->get_podcasts();

		$this->assertNotSame( 'success', $res['status'] );
		$this->assertTrue( empty( $res['data']['podcast_list'] ), 'No podcast list may be reported for a malformed reply' );
	}

	public function bad_replies() {
		return array(
			'html error page'        => array( 502, '<html>Bad Gateway</html>' ),
			'json error'             => array( 401, wp_json_encode( array( 'success' => false, 'message' => 'Unauthenticated.' ) ) ),
			'200 without the list'   => array( 200, wp_json_encode( array( 'data' => array() ) ) ),
		);
	}
}
