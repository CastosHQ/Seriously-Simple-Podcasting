<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Settings_Controller;
use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Handlers\Ajax_Handler;
use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Renderers\Settings_Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Repositories\Sync_Refusal_Repository;

class CastosConnectCredentialsTest extends \Codeception\TestCase\WPTestCase {

	const TOKEN_OPTION = 'ss_podcasting_podmotor_account_api_token';

	private $handler;
	private $settings_controller;
	private $requests;
	private $connect_code;
	private $podcast_code;
	private $podcast_list;
	private $me_disconnected;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}
	}

	protected function setUp(): void {
		parent::setUp();
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		if ( ! taxonomy_exists( ssp_series_taxonomy() ) ) {
			ssp_app()->series_controller->register_taxonomy();
		}
		$this->handler = ssp_get_service( 'castos_handler' );
		$this->settings_controller = $this->getMockBuilder( Settings_Controller::class )
			->onlyMethods( array( 'register_hooks_and_filters' ) )
			->setConstructorArgs( array(
				new Settings_Handler(),
				Settings_Renderer::instance(),
				new Renderer(),
				$this->createMock( Series_Handler::class ),
				$this->handler,
				$this->createMock( Episode_Repository::class ),
				new Sync_Refusal_Repository(),
			) )
			->getMock();
		$this->requests     = array();
		$this->connect_code = 200;
		$this->podcast_code = 200;
		$this->podcast_list = array();
		$this->me_disconnected = false;
		$this->handler->remove_api_credentials();
		add_filter( 'pre_http_request', array( $this, 'intercept_request' ), 10, 3 );
	}

	protected function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'intercept_request' ), 10 );
		$this->handler->remove_api_credentials();
		unset( $_GET['api_token'], $_REQUEST['nonce'] );
		parent::tearDown();
	}

	public function intercept_request( $preempt, $args, $url ) {
		$this->requests[] = array( 'url' => $url, 'args' => $args );
		$code = 200;
		$body = array( 'status' => 'success', 'message' => 'Connected', 'email' => 'test@example.com' );
		if ( false !== strpos( $url, 'api/v2/ssp/connect' ) && 'POST' === $args['method'] ) {
			$code = $this->connect_code;
		} elseif ( false !== strpos( $url, 'api/v2/podcasts' ) ) {
			$code = $this->podcast_code;
			$body = array( 'data' => array( 'podcast_list' => $this->podcast_list ) );
		} elseif ( $this->me_disconnected && rtrim( $url, '/' ) === rtrim( SSP_CASTOS_APP_URL, '/' ) . '/api/v2' ) {
			$code = 400;
			$body = array( 'message' => 'Account disconnected' );
		}
		return array(
			'response' => array( 'code' => $code, 'message' => '' ),
			'body'     => wp_json_encode( $body ),
			'headers'  => array(),
			'cookies'  => array(),
		);
	}

	private function requests_for( $path ) {
		return array_values( array_filter( $this->requests, function ( $request ) use ( $path ) {
			return false !== strpos( $request['url'], $path );
		} ) );
	}

	private function connect_via_ajax( $token ) {
		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		wp_get_current_user()->add_cap( 'manage_podcast' );
		$_REQUEST['nonce'] = wp_create_nonce( 'ss_podcasting_castos-hosting' );
		$_GET['api_token'] = $token;

		$ajax = new Ajax_Handler(
			$this->handler,
			$this->createMock( Admin_Notifications_Handler::class ),
			$this->settings_controller,
			new Sync_Refusal_Repository(),
			$this->createMock( Feed_Handler::class )
		);
		$die_filter = function () {
			return function () {
				throw new \Error( 'Intercepted wp_die' );
			};
		};
		add_filter( 'wp_die_ajax_handler', $die_filter );
		add_filter( 'wp_die_json_handler', $die_filter );
		ob_start();
		try {
			$ajax->connect_castos();
		} catch ( \Error $error ) {
			if ( 'Intercepted wp_die' !== $error->getMessage() ) {
				throw $error;
			}
		} finally {
			$output = ob_get_clean();
			remove_filter( 'wp_die_ajax_handler', $die_filter );
			remove_filter( 'wp_die_json_handler', $die_filter );
		}
		return json_decode( $output, true );
	}

	private function assert_credentials( $request, $token ) {
		$this->assertSame( 'Bearer ' . $token, $request['args']['headers']['Authorization'] );
		$this->assertSame( $token, $request['args']['body']['token'] );
	}

	private function create_series_and_get_ids( $name_prefix ) {
		$this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy(), 'name' => $name_prefix . ' series one' ) );
		$this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy(), 'name' => $name_prefix . ' series two' ) );
		wp_cache_delete( 'ssp_podcasts_' . ssp_series_taxonomy() . '_' . md5( serialize( array( 'hide_empty' => false ) ) ), 'ssp' );
		return wp_list_pluck( ssp_get_podcasts(), 'term_id' );
	}

	public function testFreshConnectAuthenticatesEverySynchronousSeriesPush() {
		$series_ids = $this->create_series_and_get_ids( 'Connect' );
		$this->assertGreaterThanOrEqual( 2, count( $series_ids ) );
		$this->handler->authorization_headers( array( 'headers' => array() ), SSP_CASTOS_APP_URL );

		$response = $this->connect_via_ajax( 'new-token' );
		$this->assertSame( 'success', $response['status'] );
		$connect = $this->requests_for( 'api/v2/ssp/connect' );
		$this->assertCount( 1, $connect );
		$this->assertSame( 'Bearer new-token', $connect[0]['args']['headers']['Authorization'] );
		$pushes = $this->requests_for( 'api/v2/series/create' );
		$this->assertCount( count( $series_ids ), $pushes );
		$pushed_ids = array();
		foreach ( $pushes as $push ) {
			$this->assert_credentials( $push, 'new-token' );
			$pushed_ids[] = $push['args']['body']['series_id'];
		}
		$this->assertEqualsCanonicalizing( $series_ids, $pushed_ids );
	}

	public function testDirectTokenUpdateRefreshesCredentialsAndPodcastCaches() {
		$series_id = (int) $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy(), 'name' => 'Direct update status' ) );
		$this->handler->set_token( 'old-token' );
		$this->podcast_list = array( array( 'id' => 123, 'series_id' => $series_id, 'ssp_import_status' => 'failed' ) );
		$this->assertSame( Sync_Status::SYNC_STATUS_FAILED, $this->handler->get_podcast_sync_status( $series_id )->status );
		$this->assertSame( 'Bearer old-token', $this->handler->authorization_headers( array( 'headers' => array() ), SSP_CASTOS_APP_URL )['headers']['Authorization'] );

		$this->podcast_list = array( array( 'id' => 123, 'series_id' => $series_id, 'ssp_import_status' => 'completed' ) );
		$this->requests = array();
		update_option( self::TOKEN_OPTION, 'new-token' );
		$this->assertSame( Sync_Status::SYNC_STATUS_SYNCED, $this->handler->get_podcast_sync_status( $series_id )->status );
		$requests = $this->requests_for( 'api/v2/podcasts' );
		$this->assertCount( 1, $requests );
		$this->assert_credentials( $requests[0], 'new-token' );
	}

	public function testDirectFirstTokenAddAuthenticatesSynchronousSeriesPushes() {
		$series_ids = $this->create_series_and_get_ids( 'Direct add' );
		$this->assertGreaterThanOrEqual( 2, count( $series_ids ) );
		$this->assertFalse( get_option( self::TOKEN_OPTION ) );
		$headers = $this->handler->authorization_headers( array( 'headers' => array() ), SSP_CASTOS_APP_URL );
		$this->assertSame( 'Bearer ', $headers['headers']['Authorization'] );

		$this->requests = array();
		$this->assertTrue( add_option( self::TOKEN_OPTION, 'new-token' ) );
		$pushes = $this->requests_for( 'api/v2/series/create' );
		$this->assertCount( count( $series_ids ), $pushes );
		$pushed_ids = array();
		foreach ( $pushes as $push ) {
			$this->assert_credentials( $push, 'new-token' );
			$pushed_ids[] = $push['args']['body']['series_id'];
		}
		$this->assertEqualsCanonicalizing( $series_ids, $pushed_ids );
	}

	public function testDirectTokenDeletionClearsActiveCredentials() {
		$this->handler->set_token( 'old-token' );
		$this->assertNotNull( $this->handler->me() );
		$this->assertTrue( delete_option( self::TOKEN_OPTION ) );
		$headers = $this->handler->authorization_headers( array( 'headers' => array() ), SSP_CASTOS_APP_URL );
		$this->assertSame( 'Bearer ', $headers['headers']['Authorization'] );
		$this->requests = array();
		$this->assertNull( $this->handler->me() );
		$this->handler->get_podcasts( true );
		$this->assertEmpty( $this->requests );
	}

	public function testFailedConnectDoesNotActivateSubmittedToken() {
		$this->handler->set_token( 'old-token' );
		$this->connect_code = 401;
		$response = $this->connect_via_ajax( 'rejected-token' );
		$this->assertNotSame( 'success', $response['status'] );
		$connect = $this->requests_for( 'api/v2/ssp/connect' );
		$this->assertSame( 'Bearer rejected-token', $connect[0]['args']['headers']['Authorization'] );
		$this->assertSame( '', get_option( self::TOKEN_OPTION, '' ) );
		$headers = $this->handler->authorization_headers( array( 'headers' => array() ), SSP_CASTOS_APP_URL );
		$this->assertSame( 'Bearer ', $headers['headers']['Authorization'] );
		$this->requests = array();
		$this->handler->get_podcasts( true );
		$this->assertEmpty( $this->requests );
	}

	/**
	 * @dataProvider podcast_refresh_modes
	 */
	public function testPodcastListWithoutTokenDoesNotSendOrCache( $force_refresh ) {
		$this->assertSame( '', get_option( self::TOKEN_OPTION, '' ) );
		$this->assertFalse( get_transient( 'ssp_castos_podcasts' ) );
		$cached = array( 'status' => 'success', 'data' => array( 'podcast_list' => array( array( 'id' => 42 ) ) ) );
		if ( ! $force_refresh ) {
			set_transient( 'ssp_castos_podcasts', $cached, MINUTE_IN_SECONDS );
		}
		$this->requests = array();
		$result = $this->handler->get_podcasts( $force_refresh );
		$this->assertIsArray( $result );
		$this->assertNotSame( $cached, $result );
		$this->assertNotSame( 'success', $result['status'] );
		$this->assertEmpty( $this->requests );
		if ( $force_refresh ) {
			$this->assertFalse( get_transient( 'ssp_castos_podcasts' ) );
		}
	}

	public function podcast_refresh_modes() {
		return array( 'cached mode' => array( false ), 'forced refresh' => array( true ) );
	}

	public function testConnectClearsErrorAndRequestLocalStatusForHosting() {
		$series_id = (int) $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy(), 'name' => 'Hosting status' ) );
		$this->handler->set_token( 'old-token' );
		$this->podcast_code = 401;
		$this->assertNotSame( 'success', $this->handler->get_podcasts()['status'] );
		$this->assertSame( Sync_Status::SYNC_STATUS_NONE, $this->handler->get_podcast_sync_status( $series_id )->status );
		$this->assertNull( $this->settings_controller->get_series_sync_statuses( array( $series_id ) ) );
		$this->podcast_code = 200;
		$this->podcast_list = array( array( 'id' => 123, 'series_id' => $series_id, 'ssp_import_status' => 'completed' ) );
		$this->connect_via_ajax( 'new-token' );
		$this->assertSame( 'new-token', get_option( self::TOKEN_OPTION ) );
		$hosting = $this->settings_controller->get_series_sync_statuses( array( $series_id ) );
		$this->assertNotNull( $hosting );
		$this->assertSame( Sync_Status::SYNC_STATUS_SYNCED, $hosting['statuses'][ $series_id ]->status );
		$this->assertSame( Sync_Status::SYNC_STATUS_SYNCED, $this->handler->get_podcast_sync_status( $series_id )->status );
		$requests = $this->requests_for( 'api/v2/podcasts' );
		$this->assertGreaterThanOrEqual( 2, count( $requests ) );
		$this->assert_credentials( end( $requests ), 'new-token' );
	}

	/**
	 * @dataProvider persistence_filter_values
	 */
	public function testActiveTokenMatchesFilteredOrUnchangedStoredOption( $filtered ) {
		$this->handler->set_token( 'old-token' );
		$filter = function ( $value, $old_value ) use ( $filtered ) {
			return 'unchanged' === $filtered ? $old_value : $filtered;
		};
		add_filter( 'pre_update_option_' . self::TOKEN_OPTION, $filter, 10, 2 );
		try {
			$this->handler->set_token( 'submitted-token' );
		} finally {
			remove_filter( 'pre_update_option_' . self::TOKEN_OPTION, $filter, 10 );
		}
		$stored = get_option( self::TOKEN_OPTION );
		$this->assertSame( 'unchanged' === $filtered ? 'old-token' : $filtered, $stored );
		$this->handler->get_podcasts( true );
		$requests = $this->requests_for( 'api/v2/podcasts' );
		$this->assert_credentials( end( $requests ), $stored );
	}

	public function persistence_filter_values() {
		return array( 'unchanged update' => array( 'unchanged' ), 'filtered update' => array( 'filtered-token' ) );
	}

	/**
	 * @dataProvider credential_removal_methods
	 */
	public function testRemovedCredentialsDoNotReuseCachedResponses( $method ) {
		$this->handler->set_token( 'old-token' );
		$this->assertNotNull( $this->handler->me() );
		$this->handler->$method();
		$this->requests = array();
		$this->assertNull( $this->handler->me() );
		$this->handler->get_podcasts( true );
		$this->assertEmpty( $this->requests );
		$this->handler->set_token( 'new-token' );
		$this->requests = array();
		$this->assertNotNull( $this->handler->me() );
		$this->assertCount( 1, $this->requests );
		$this->assert_credentials( $this->requests[0], 'new-token' );
	}

	public function credential_removal_methods() {
		return array( 'remove credentials' => array( 'remove_api_credentials' ), 'disconnect' => array( 'disconnect' ) );
	}

	public function testDisconnectedResponseIsNotCachedAfterCredentialsAreRemoved() {
		$this->handler->set_token( 'old-token' );
		$this->requests = array();
		$this->me_disconnected = true;
		$response = $this->handler->me();
		$this->assertSame( 400, $response['code'] );
		$this->assertCount( 1, $this->requests );
		$this->assert_credentials( $this->requests[0], 'old-token' );
		$this->assertSame( '', get_option( self::TOKEN_OPTION, '' ) );

		$this->requests = array();
		$this->assertNull( $this->handler->me() );
		$this->assertEmpty( $this->requests );
	}

	public function testTokenTransitionInvalidatesCachedEmail() {
		$this->handler->set_token( 'old-token' );
		set_transient( 'ssp_castos_api_email', 'old@example.com', MINUTE_IN_SECONDS );
		$this->handler->set_token( 'new-token' );
		$this->requests = array();
		$this->assertSame( 'test@example.com', $this->handler->get_email() );
		$this->assertCount( 1, $this->requests );
		$this->assert_credentials( $this->requests[0], 'new-token' );
	}
}
