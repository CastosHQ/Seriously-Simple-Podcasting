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
		unset( $_POST['post_id'], $_POST['width'], $_POST['height'], $_REQUEST['nonce'] );
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

	private function make_handler() {
		$castos_handler        = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		return new Ajax_Handler( $castos_handler, $admin_notices_handler );
	}
}
