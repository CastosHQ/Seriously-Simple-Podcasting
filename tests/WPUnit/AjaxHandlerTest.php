<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Ajax_Handler;

class AjaxHandlerTest extends \Codeception\TestCase\WPTestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		// wp_send_json uses wp_die() only when DOING_AJAX is true; otherwise it calls bare die().
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}
	}

	protected function tearDown(): void {
		unset( $_POST['post_id'], $_POST['width'], $_POST['height'], $_REQUEST['nonce'], $_GET['api_token'], $_GET['podcasts'] );
		parent::tearDown();
	}

	/**
	 * Test that update_episode_embed_code rejects requests with an invalid nonce.
	 */
	public function testUpdateEpisodeEmbedCodeRejectsWithInvalidNonce() {
		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$user = wp_get_current_user();
		$user->add_cap( 'manage_podcast' );

		$post_id = $this->factory()->post->create();

		$_REQUEST['nonce'] = 'invalid_nonce_value';
		$_POST['post_id']  = $post_id;
		$_POST['width']    = 500;
		$_POST['height']   = 350;

		$handler  = $this->make_handler();
		$response = $this->capture_json_response( array( $handler, 'update_episode_embed_code' ) );

		$this->assertSame( 'error', $response['status'], 'Should return error status with invalid nonce' );
	}

	/**
	 * Test that update_episode_embed_code rejects requests from users without manage_podcast capability.
	 */
	public function testUpdateEpisodeEmbedCodeRejectsWithoutCapability() {
		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$post_id = $this->factory()->post->create();

		$_REQUEST['nonce'] = wp_create_nonce( 'update_episode_embed_code' );
		$_POST['post_id']  = $post_id;
		$_POST['width']    = 500;
		$_POST['height']   = 350;

		$handler  = $this->make_handler();
		$response = $this->capture_json_response( array( $handler, 'update_episode_embed_code' ) );

		$this->assertSame( 'error', $response['status'], 'Should return error status without capability' );
	}

	/**
	 * Test that update_episode_embed_code succeeds with valid nonce and capability.
	 */
	public function testUpdateEpisodeEmbedCodeSucceedsWithValidNonceAndCapability() {
		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$user = wp_get_current_user();
		$user->add_cap( 'manage_podcast' );

		$post_id = $this->factory()->post->create( array(
			'post_title'  => 'Test Episode',
			'post_status' => 'publish',
		) );

		$_REQUEST['nonce'] = wp_create_nonce( 'update_episode_embed_code' );
		$_POST['post_id']  = $post_id;
		$_POST['width']    = 500;
		$_POST['height']   = 350;

		$handler  = $this->make_handler();
		$response = $this->capture_json_response( array( $handler, 'update_episode_embed_code' ) );

		$this->assertTrue( $response['success'], 'Response should indicate success' );
	}

	/**
	 * Capture JSON output from an AJAX handler that calls wp_send_json / wp_die.
	 *
	 * Hooks into wp_die to prevent process exit and captures the JSON output.
	 *
	 * @param callable $callback The AJAX handler to invoke.
	 * @return array Decoded JSON response.
	 */
	private function capture_json_response( callable $callback ) {
		// Override wp_die handler to throw instead of exiting.
		// Use \Error (not \Exception) so it won't be caught by the handler's catch (\Exception) block.
		add_filter( 'wp_die_ajax_handler', function () {
			return function ( $message ) {
				throw new \Error( 'wp_die_intercepted' );
			};
		} );

		ob_start();
		try {
			call_user_func( $callback );
		} catch ( \Error $e ) {
			// Expected — wp_send_json triggers wp_die.
		}
		$output = ob_get_clean();

		// Remove the filter.
		remove_all_filters( 'wp_die_ajax_handler' );

		$decoded = json_decode( $output, true );
		$this->assertNotNull( $decoded, 'Response should be valid JSON. Got: ' . $output );

		return $decoded;
	}

	/**
	 * Test that connect_castos removes the disconnect notice on successful reconnection.
	 */
	public function testConnectCastosRemovesDisconnectNoticeOnSuccess() {
		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$user = wp_get_current_user();
		$user->add_cap( 'manage_podcast' );

		$_REQUEST['nonce'] = wp_create_nonce( 'ss_podcasting_castos-hosting' );
		$_GET['api_token'] = 'test_token_123';

		$response          = new \SeriouslySimplePodcasting\Entities\Castos_Response();
		$response->success = true;
		$response->message = 'Connected successfully.';
		$response->status  = 'success';

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->method( 'connect' )->willReturn( $response );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );
		$admin_notices_handler->expects( $this->once() )
			->method( 'remove_constant_notice' )
			->with( \SeriouslySimplePodcasting\Handlers\Castos_Handler::DISCONNECT_NOTICE_KEY );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler );
		$json_response = $this->capture_json_response( array( $handler, 'connect_castos' ) );

		$this->assertSame( 'success', $json_response['status'] );
	}

	private function make_handler() {
		$castos_handler        = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		return new Ajax_Handler( $castos_handler, $admin_notices_handler );
	}

	private function authorize_sync_request() {
		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$user = wp_get_current_user();
		$user->add_cap( 'manage_podcast' );

		$_REQUEST['nonce'] = wp_create_nonce( 'ss_podcasting_castos-hosting' );
	}

	/**
	 * A sync Castos refuses (HTTP 409 with the refusal body code) surfaces as
	 * failed with Castos's reason — never as in progress.
	 */
	public function testSyncCastosShowsRefusalAsFailed() {
		$this->authorize_sync_request();
		$_GET['podcasts'] = array( '7' );

		$refusal_message = 'This podcast is already connected to a different WordPress podcast in your Castos account.';

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->expects( $this->once() )
			->method( 'trigger_podcast_sync' )
			->with( 7 )
			->willReturn( array(
				'code'  => \SeriouslySimplePodcasting\Handlers\Castos_Handler::SYNC_REFUSED_CODE,
				'error' => $refusal_message,
			) );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler );
		$json_response = $this->capture_json_response( array( $handler, 'sync_castos' ) );

		$this->assertFalse( $json_response['success'] );
		$this->assertSame( 'failed', $json_response['data']['status'] );
		$this->assertSame( 'failed', $json_response['data']['podcasts'][7]['status'] );
		$this->assertStringContainsString( $refusal_message, $json_response['data']['podcasts'][7]['msg'] );
	}

	/**
	 * A plain 409 — no refusal code in the body — still means a sync is
	 * already in progress and keeps reporting Syncing.
	 */
	public function testSyncCastosShowsPlainConflictAsSyncing() {
		$this->authorize_sync_request();
		$_GET['podcasts'] = array( '7' );

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->expects( $this->once() )
			->method( 'trigger_podcast_sync' )
			->with( 7 )
			->willReturn( array(
				'code'  => 409,
				'error' => 'A sync is already in progress for this podcast.',
			) );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler );
		$json_response = $this->capture_json_response( array( $handler, 'sync_castos' ) );

		$this->assertTrue( $json_response['success'] );
		$this->assertSame( 'syncing', $json_response['data']['status'] );
		$this->assertSame( 'syncing', $json_response['data']['podcasts'][7]['status'] );
	}

	/**
	 * Test that an ordinary sync starts and reports the syncing status.
	 */
	public function testSyncCastosStartsSync() {
		$this->authorize_sync_request();
		$_GET['podcasts'] = array( '7' );

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->expects( $this->once() )
			->method( 'trigger_podcast_sync' )
			->with( 7 )
			->willReturn( array( 'code' => 200 ) );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler );
		$json_response = $this->capture_json_response( array( $handler, 'sync_castos' ) );

		$this->assertTrue( $json_response['success'] );
		$this->assertSame( 'syncing', $json_response['data']['status'] );
		$this->assertSame( 'syncing', $json_response['data']['podcasts'][7]['status'] );
	}
}
