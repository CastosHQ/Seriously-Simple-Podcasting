<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Settings_Controller;
use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Handlers\Ajax_Handler;
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Repositories\Sync_Refusal_Repository;

class AjaxHandlerTest extends \Codeception\TestCase\WPTestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		// wp_send_json uses wp_die() only when DOING_AJAX is true; otherwise it calls bare die().
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}
	}

	protected function tearDown(): void {
		unset(
			$_POST['post_id'],
			$_POST['series_id'],
			$_POST['width'],
			$_POST['height'],
			$_REQUEST['nonce'],
			$_GET['api_token'],
			$_GET['podcasts'],
			$_GET['series'],
			$_GET['confirm_action'],
			$_GET['live']
		);
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

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler, $this->createMock( Settings_Controller::class ), new Sync_Refusal_Repository(), $this->createMock( Feed_Handler::class ) );
		$json_response = $this->capture_json_response( array( $handler, 'connect_castos' ) );

		$this->assertSame( 'success', $json_response['status'] );
	}

	private function make_handler() {
		$castos_handler        = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		return new Ajax_Handler( $castos_handler, $admin_notices_handler, $this->createMock( Settings_Controller::class ), new Sync_Refusal_Repository(), $this->createMock( Feed_Handler::class ) );
	}

	private function authorize_sync_request() {
		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$user = wp_get_current_user();
		$user->add_cap( 'manage_podcast' );

		$_REQUEST['nonce'] = wp_create_nonce( 'ss_podcasting_castos-hosting' );
	}

	/**
	 * A terminal GUID conflict is failed, recorded, and never treated as an
	 * in-progress sync.
	 */
	public function testSyncCastosShowsGuidAlreadyInUseAsFailed() {
		$this->authorize_sync_request();
		$series_name = 'Terminal Conflict Podcast';
		$series_id   = $this->factory()->term->create(
			array(
				'taxonomy' => ssp_series_taxonomy(),
				'name'     => $series_name,
			)
		);
		$_GET['podcasts'] = array( $series_id );

		$refusal_message   = 'Castos terminal refusal error.';
		$refusal_repository = new Sync_Refusal_Repository();

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->expects( $this->once() )
			->method( 'trigger_podcast_sync' )
			->with( $series_id, null )
			->willReturn( array(
				'code'        => 'guid_already_in_use',
				'error'       => $refusal_message,
				'podcast_id'  => 1234,
				'differences' => array(),
			) );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler, $this->createMock( Settings_Controller::class ), $refusal_repository, $this->createMock( Feed_Handler::class ) );
		$json_response = $this->capture_json_response( array( $handler, 'sync_castos' ) );

		$this->assertFalse( $json_response['success'] );
		$this->assertSame( 'failed', $json_response['data']['status'] );
		$this->assertSame( 'failed', $json_response['data']['podcasts'][ $series_id ]['status'] );
		$this->assertSame(
			$series_name . ': ' . Sync_Refusal_Repository::get_message( Sync_Refusal_Repository::CODE_ALREADY_IN_USE ),
			$json_response['data']['podcasts'][ $series_id ]['msg']
		);
		$this->assertStringNotContainsString( 'js-sync-refusal-connect', $json_response['data']['podcasts'][ $series_id ]['html'] );
	}

	/**
	 * A details-difference refusal surfaces as Needs confirmation rather than
	 * as a terminal failure.
	 */
	public function testSyncCastosShowsGuidMatchDetailsDifferAsNeedsConfirmation() {
		$this->authorize_sync_request();
		$series_name = 'Details Differ Podcast';
		$series_id   = $this->factory()->term->create(
			array(
				'taxonomy' => ssp_series_taxonomy(),
				'name'     => $series_name,
			)
		);
		$_GET['podcasts']  = array( $series_id );
		$refusal_repository = new Sync_Refusal_Repository();

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->expects( $this->once() )
			->method( 'trigger_podcast_sync' )
			->with( $series_id, null )
			->willReturn( array(
				'code'        => 'guid_match_details_differ',
				'error'       => 'Update Seriously Simple Podcasting to the latest version to proceed.',
				'podcast_id'  => 1234,
				'differences' => array( 'podcast_title', 'website' ),
			) );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler, $this->createMock( Settings_Controller::class ), $refusal_repository, $this->createMock( Feed_Handler::class ) );
		$json_response = $this->capture_json_response( array( $handler, 'sync_castos' ) );

		$this->assertSame( 'needs_confirmation', $json_response['data']['status'] );
		$this->assertSame( 'needs_confirmation', $json_response['data']['podcasts'][ $series_id ]['status'] );
		$this->assertSame(
			$series_name . ': ' . Sync_Refusal_Repository::get_message( Sync_Refusal_Repository::CODE_DETAILS_DIFFER ),
			$json_response['data']['podcasts'][ $series_id ]['msg']
		);
	}

	/**
	 * A confirmed Initial Sync reports a successful Castos response as Syncing.
	 */
	public function testConfirmedSyncReportsSyncing() {
		$this->authorize_sync_request();
		$series_id = $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		ssp_update_option( 'data_guid', 'guid-confirmed', $series_id );
		$_GET['podcasts']       = array( $series_id );
		$_GET['confirm_action'] = Sync_Refusal_Repository::ACTION_CONNECT;

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->expects( $this->once() )
			->method( 'trigger_podcast_sync' )
			->with( $series_id, Sync_Refusal_Repository::ACTION_CONNECT )
			->willReturn( array(
				'code'    => 200,
				'success' => 'true',
			) );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler, $this->createMock( Settings_Controller::class ), new Sync_Refusal_Repository(), $this->createMock( Feed_Handler::class ) );
		$json_response = $this->capture_json_response( array( $handler, 'sync_castos' ) );

		$this->assertTrue( $json_response['success'] );
		$this->assertSame( 'syncing', $json_response['data']['status'] );
		$this->assertSame( 'syncing', $json_response['data']['podcasts'][ $series_id ]['status'] );
	}

	/**
	 * A failed podcast keeps the overall result failed when another podcast
	 * needs confirmation.
	 */
	public function testSyncCastosKeepsFailureWhenAnotherPodcastNeedsConfirmation() {
		$this->authorize_sync_request();
		$failed_id       = $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy(), 'name' => 'Failed Podcast' ) );
		$confirmation_id = $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy(), 'name' => 'Confirmation Podcast' ) );
		$_GET['podcasts'] = array( $failed_id, $confirmation_id );

		$responses = array(
			$failed_id       => array(
				'code'  => 500,
				'error' => 'The Castos request failed.',
			),
			$confirmation_id => array(
				'code'        => Sync_Refusal_Repository::CODE_DETAILS_DIFFER,
				'error'       => 'Castos details differ.',
				'podcast_id'  => 1234,
				'differences' => array( 'podcast_title' => 'Castos title' ),
			),
		);

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->expects( $this->exactly( 2 ) )
			->method( 'trigger_podcast_sync' )
			->willReturnCallback(
				function ( $series_id, $confirm_action ) use ( $responses ) {
					$this->assertNull( $confirm_action );

					return $responses[ $series_id ];
				}
			);

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );
		$handler                = new Ajax_Handler(
			$castos_handler,
			$admin_notices_handler,
			$this->createMock( Settings_Controller::class ),
			new Sync_Refusal_Repository(),
			$this->createMock( Feed_Handler::class )
		);
		$json_response          = $this->capture_json_response( array( $handler, 'sync_castos' ) );

		$this->assertFalse( $json_response['success'] );
		$this->assertSame( Sync_Status::SYNC_STATUS_FAILED, $json_response['data']['status'] );
		$this->assertSame( Sync_Status::SYNC_STATUS_FAILED, $json_response['data']['podcasts'][ $failed_id ]['status'] );
		$this->assertSame( Sync_Status::SYNC_STATUS_NEEDS_CONFIRMATION, $json_response['data']['podcasts'][ $confirmation_id ]['status'] );
	}

	/**
	 * The renamed GUID AJAX action uses the required Feed_Handler and clears a
	 * refusal after saving the derived GUID.
	 */
	public function testGenerateSeriesGuidUsesRenamedAjaxAction() {
		$this->authorize_sync_request();
		$series_id = $this->factory()->term->create(
			array(
				'taxonomy' => ssp_series_taxonomy(),
				'name'     => 'Generate GUID Podcast',
			)
		);
		$term      = get_term( $series_id, ssp_series_taxonomy() );
		$old_guid  = 'old-guid';
		$new_guid  = 'new-derived-guid';
		ssp_update_option( 'data_guid', $old_guid, $series_id );

		$refusal_repository = new Sync_Refusal_Repository();
		$refusal_repository->record(
			$series_id,
			array(
				'code'        => Sync_Refusal_Repository::CODE_DETAILS_DIFFER,
				'podcast_id'  => 1234,
				'differences' => array( 'website' ),
				'guid'        => $old_guid,
			)
		);

		$feed_handler = $this->createMock( Feed_Handler::class );
		$feed_handler->expects( $this->once() )
			->method( 'get_derived_guid' )
			->with( $term->slug )
			->willReturn( $new_guid );
		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->method( 'get_podcast_by_series' )->willReturn( null );
		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );
		$handler              = new Ajax_Handler(
			$castos_handler,
			$admin_notices_handler,
			$this->createMock( Settings_Controller::class ),
			$refusal_repository,
			$feed_handler
		);

		$this->assertSame( 10, has_action( 'wp_ajax_ssp_generate_series_guid', array( $handler, 'generate_series_guid' ) ) );
		$this->assertFalse( has_action( 'wp_ajax_ssp_regenerate_series_guid', array( $handler, 'generate_series_guid' ) ) );

		$_REQUEST['nonce']   = wp_create_nonce( 'ssp_generate_series_guid' );
		$_POST['series_id'] = $series_id;
		$json_response       = $this->capture_json_response( array( $handler, 'generate_series_guid' ) );

		$this->assertTrue( $json_response['success'] );
		$this->assertSame( $new_guid, $json_response['data']['guid'] );
		$this->assertSame( $new_guid, ssp_get_podcast_guid( $series_id ) );
		$this->assertNull( $refusal_repository->get( $series_id ) );
	}

	/**
	 * The status endpoint passes the live flag through and renders the current
	 * status for each requested series.
	 */
	public function testGetSeriesSyncStatusesPassesLiveFlag() {
		$this->authorize_sync_request();
		$series_id = $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		$status    = new Sync_Status( Sync_Status::SYNC_STATUS_SYNCING );
		$_GET['series'] = array( $series_id );
		$_GET['live']   = '1';

		$settings_controller = $this->createMock( Settings_Controller::class );
		$settings_controller->expects( $this->once() )
			->method( 'get_series_sync_statuses' )
			->with( array( $series_id ), true )
			->willReturn(
				array(
					'statuses'      => array( $series_id => $status ),
					'sync_refusals' => array(),
				)
			);

		$handler = new Ajax_Handler(
			$this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class ),
			$this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class ),
			$settings_controller,
			new Sync_Refusal_Repository(),
			$this->createMock( Feed_Handler::class )
		);
		$json_response = $this->capture_json_response( array( $handler, 'get_series_sync_statuses' ) );

		$this->assertTrue( $json_response['success'] );
		$this->assertSame( Sync_Status::SYNC_STATUS_SYNCING, $json_response['data']['series'][ $series_id ]['status'] );
		$this->assertSame( 'Syncing', $json_response['data']['series'][ $series_id ]['title'] );
	}

	/**
	 * Both documented refusal codes use translated SSP messages.
	 *
	 * @dataProvider known_refusal_codes
	 */
	public function testSyncCastosTranslatesKnownRefusalCodes( $code ) {
		$this->authorize_sync_request();
		$podcast_name = 'Known Podcast';
		$series_id    = $this->factory()->term->create(
			array(
				'taxonomy' => ssp_series_taxonomy(),
				'name'     => $podcast_name,
			)
		);
		$_GET['podcasts'] = array( $series_id );
		$translated       = 'Translated refusal message.';
		$translate        = function ( $translation, $text, $domain ) use ( $translated ) {
			if ( 'seriously-simple-podcasting' !== $domain || false !== strpos( $text, '%1$s' ) || false !== strpos( $text, '%2$s' ) ) {
				return $translation;
			}

			return $translated;
		};
		add_filter( 'gettext', $translate, 10, 3 );

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->expects( $this->once() )
			->method( 'trigger_podcast_sync' )
			->with( $series_id, null )
			->willReturn( array(
				'code'        => $code,
				'error'       => 'Castos refusal reason.',
				'podcast_id'  => 1234,
				'differences' => array(),
			) );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler = new Ajax_Handler( $castos_handler, $admin_notices_handler, $this->createMock( Settings_Controller::class ), new Sync_Refusal_Repository(), $this->createMock( Feed_Handler::class ) );
		try {
			$json_response = $this->capture_json_response( array( $handler, 'sync_castos' ) );
		} finally {
			remove_filter( 'gettext', $translate, 10 );
		}

		$this->assertSame(
			$podcast_name . ': ' . $translated,
			$json_response['data']['podcasts'][ $series_id ]['msg']
		);
		$this->assertStringNotContainsString(
			'Castos refusal reason.',
			$json_response['data']['podcasts'][ $series_id ]['msg']
		);
	}

	public function known_refusal_codes() {
		return array(
			'details differ' => array( 'guid_match_details_differ' ),
			'already in use' => array( 'guid_already_in_use' ),
		);
	}

	/**
	 * An unknown refusal code keeps Castos's message, escaped for the
	 * settings.js HTML sink.
	 */
	public function testSyncCastosEscapesCastosErrorForUnknownRefusalCode() {
		$this->authorize_sync_request();
		$podcast_name = 'Unknown Code Podcast';
		$series_id    = $this->factory()->term->create(
			array(
				'taxonomy' => ssp_series_taxonomy(),
				'name'     => $podcast_name,
			)
		);
		$_GET['podcasts'] = array( $series_id );
		$refusal_message = 'A newer Castos refusal: <strong>do not trust this</strong> & '
			. '<script>alert(1)</script>.';

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->expects( $this->once() )
			->method( 'trigger_podcast_sync' )
			->with( $series_id, null )
			->willReturn( array(
				'code'  => 'future_refusal_code',
				'error' => $refusal_message,
			) );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler, $this->createMock( Settings_Controller::class ), new Sync_Refusal_Repository(), $this->createMock( Feed_Handler::class ) );
		$json_response = $this->capture_json_response( array( $handler, 'sync_castos' ) );
		$message       = $json_response['data']['podcasts'][ $series_id ]['msg'];

		$this->assertSame( $podcast_name . ': ' . esc_html( $refusal_message ), $message );
		$this->assertStringNotContainsString( $refusal_message, $message );
	}

	public function testLegacySyncRefusedCodeIsNotDefined() {
		$this->assertFalse( defined( 'SeriouslySimplePodcasting\\Handlers\\Castos_Handler::SYNC_REFUSED_CODE' ) );
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
			->with( 7, null )
			->willReturn( array(
				'code'  => 409,
				'error' => 'A sync is already in progress for this podcast.',
			) );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler, $this->createMock( Settings_Controller::class ), new Sync_Refusal_Repository(), $this->createMock( Feed_Handler::class ) );
		$json_response = $this->capture_json_response( array( $handler, 'sync_castos' ) );

		$this->assertTrue( $json_response['success'] );
		$this->assertSame( 'syncing', $json_response['data']['status'] );
		$this->assertSame( 'syncing', $json_response['data']['podcasts'][7]['status'] );
	}

	/**
	 * A request that never reaches Castos reports Failed with the default
	 * message, even when an unanswered refusal is stored.
	 */
	public function testSyncCastosReportsTransportFailureAsFailedWithStoredRefusal() {
		$this->authorize_sync_request();
		$series_name = 'Transport Failure Podcast';
		$series_id   = $this->factory()->term->create(
			array(
				'taxonomy' => ssp_series_taxonomy(),
				'name'     => $series_name,
			)
		);
		$_GET['podcasts']       = array( $series_id );
		$_GET['confirm_action'] = Sync_Refusal_Repository::ACTION_CONNECT;

		$refusal_repository = new Sync_Refusal_Repository();
		$refusal_repository->record(
			$series_id,
			array(
				'code'        => Sync_Refusal_Repository::CODE_DETAILS_DIFFER,
				'podcast_id'  => 1234,
				'differences' => array( 'podcast_title' ),
			)
		);

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->expects( $this->once() )
			->method( 'trigger_podcast_sync' )
			->with( $series_id, Sync_Refusal_Repository::ACTION_CONNECT )
			->willReturn( null );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler, $this->createMock( Settings_Controller::class ), $refusal_repository, $this->createMock( Feed_Handler::class ) );
		$json_response = $this->capture_json_response( array( $handler, 'sync_castos' ) );

		$this->assertFalse( $json_response['success'] );
		$this->assertSame( Sync_Status::SYNC_STATUS_FAILED, $json_response['data']['podcasts'][ $series_id ]['status'] );
		$this->assertSame(
			$series_name . ': Could not trigger podcast sync',
			$json_response['data']['podcasts'][ $series_id ]['msg']
		);
		$this->assertNotNull( $refusal_repository->get( $series_id ) );
	}

	/**
	 * Disconnecting through AJAX goes through Castos_Handler::disconnect(),
	 * which clears stored refusals along with the credentials.
	 */
	public function testDisconnectCastosClearsRefusalsThroughCastosHandler() {
		$this->authorize_sync_request();

		$castos_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class );
		$castos_handler->expects( $this->once() )->method( 'disconnect' );
		$castos_handler->expects( $this->never() )->method( 'remove_api_credentials' );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler, $this->createMock( Settings_Controller::class ), new Sync_Refusal_Repository(), $this->createMock( Feed_Handler::class ) );
		$json_response = $this->capture_json_response( array( $handler, 'disconnect_castos' ) );

		$this->assertTrue( $json_response['success'] );
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
			->with( 7, null )
			->willReturn( array( 'code' => 200 ) );

		$admin_notices_handler = $this->createMock( \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::class );

		$handler       = new Ajax_Handler( $castos_handler, $admin_notices_handler, $this->createMock( Settings_Controller::class ), new Sync_Refusal_Repository(), $this->createMock( Feed_Handler::class ) );
		$json_response = $this->capture_json_response( array( $handler, 'sync_castos' ) );

		$this->assertTrue( $json_response['success'] );
		$this->assertSame( 'syncing', $json_response['data']['status'] );
		$this->assertSame( 'syncing', $json_response['data']['podcasts'][7]['status'] );
	}
}
