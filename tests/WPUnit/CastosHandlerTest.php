<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Repositories\Sync_Refusal_Repository;

class CastosHandlerTest extends \Codeception\TestCase\WPTestCase {

	const SYNC_REFUSAL_META_KEY = 'castos_sync_refusal';

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

	/**
	 * Canned reply for the next Castos podcast sync request.
	 *
	 * @var array|\WP_Error|null
	 */
	private $sync_reply;

	/**
	 * The last Initial Sync request intercepted by the HTTP test seam.
	 *
	 * @var array|null
	 */
	private $sync_request;

	/**
	 * Canned reply for the next Castos series push request.
	 *
	 * @var array|\WP_Error|null
	 */
	private $series_reply;

	protected function setUp(): void {
		parent::setUp();

		$this->castos_handler = ssp_get_service( 'castos_handler' );
		$this->castos_handler->clear_podcasts_cache();
		$this->sync_request    = null;
		update_option( 'ss_podcasting_podmotor_account_api_token', 'test-token' );

		add_filter( 'pre_http_request', array( $this, 'reply_to_castos_request' ), 10, 3 );
	}

	protected function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'reply_to_castos_request' ) );
		$this->castos_handler->clear_podcasts_cache();
		delete_option( 'ss_podcasting_podmotor_account_api_token' );
		parent::tearDown();
	}

	public function reply_to_castos_request( $preempt, $args, $url ) {
		if ( false !== strpos( $url, 'api/v2/podcasts' ) ) {
			return $this->podcasts_reply;
		}

		if ( false !== strpos( $url, 'api/v2/ssp/podcast-sync/' ) ) {
			$this->sync_request = array(
				'url'  => $url,
				'args' => $args,
			);

			return $this->sync_reply;
		}

		if ( false !== strpos( $url, 'api/v2/series/create' ) ) {
			return $this->series_reply;
		}

		return $preempt;
	}

	private function create_series() {
		return $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
	}

	private function refusal_repository() {
		return new Sync_Refusal_Repository();
	}

	private function existing_refusal() {
		return array(
			'code'        => 'guid_match_details_differ',
			'podcast_id'  => 1234,
			'differences' => array( 'website' ),
			'guid'        => 'guid-original',
			'error'       => 'Update Seriously Simple Podcasting to the latest version to proceed.',
		);
	}

	private function assert_refusal_survives_sync_reply( $reply ) {
		$series_id = $this->create_series();
		$refusal   = $this->existing_refusal();
		ssp_update_option( 'data_guid', 'guid-original', $series_id );
		$this->refusal_repository()->record( $series_id, $refusal );
		$this->sync_reply = $reply;

		$this->castos_handler->trigger_podcast_sync( $series_id );

		$this->assertSame( $refusal, $this->refusal_repository()->get( $series_id ) );
	}

	private function push_data( $series_id ) {
		return array(
			'series_id'           => $series_id,
			'podcast_title'       => 'WordPress title',
			'podcast_description' => 'WordPress description',
			'itunes_category1'   => 'Arts',
			'guid'                => ssp_get_podcast_guid( $series_id ),
		);
	}

	private function assert_refusal_survives_push_reply( $reply ) {
		$series_id = $this->create_series();
		$refusal   = $this->existing_refusal();
		ssp_update_option( 'data_guid', 'guid-original', $series_id );
		$this->refusal_repository()->record( $series_id, $refusal );
		$this->series_reply = $reply;

		$this->castos_handler->update_podcast_data( $this->push_data( $series_id ) );

		$this->assertSame( $refusal, $this->refusal_repository()->get( $series_id ) );
	}

	/**
	 * A coded details-difference series push stores only the fields Normal Sync
	 * compares.
	 */
	public function testUpdatePodcastDataRecordsGuidMatchRefusal() {
		$series_id = $this->create_series();
		ssp_update_option( 'data_guid', 'guid-push', $series_id );
		$this->series_reply = array(
			'response' => array( 'code' => 409 ),
			'body'     => wp_json_encode(
				array(
					'success'     => false,
					'code'        => 'guid_match_details_differ',
					'error'       => 'Update Seriously Simple Podcasting to the latest version to proceed.',
					'podcast_id'  => 1234,
					'differences' => array(
						'podcast_title'       => 'The Castos title',
						'podcast_description' => 'The Castos description',
						'itunes_category1'   => 'Arts: Books',
					),
				)
			),
		);

		$this->castos_handler->update_podcast_data( $this->push_data( $series_id ) );

		$refusal = $this->refusal_repository()->get( $series_id );
		$this->assertIsArray( $refusal );
		$this->assertSame( 'guid_match_details_differ', $refusal['code'] );
		$this->assertSame( 1234, $refusal['podcast_id'] );
		$this->assertSame( 'guid-push', $refusal['guid'] );
		$difference_keys = $refusal['differences'];
		sort( $difference_keys );
		$this->assertSame(
			array( 'itunes_category1', 'podcast_description', 'podcast_title' ),
			$difference_keys
		);
	}

	/**
	 * A terminal GUID conflict from a series push is recorded.
	 */
	public function testUpdatePodcastDataRecordsGuidAlreadyInUseRefusal() {
		$series_id = $this->create_series();
		ssp_update_option( 'data_guid', 'guid-terminal-push', $series_id );
		$this->series_reply = array(
			'response' => array( 'code' => 409 ),
			'body'     => wp_json_encode(
				array(
					'success'    => false,
					'code'       => 'guid_already_in_use',
					'error'      => 'This GUID already belongs to another podcast in this account.',
					'podcast_id' => 5678,
				)
			),
		);

		$this->castos_handler->update_podcast_data( $this->push_data( $series_id ) );

		$refusal = $this->refusal_repository()->get( $series_id );
		$this->assertIsArray( $refusal );
		$this->assertSame( 'guid_already_in_use', $refusal['code'] );
		$this->assertSame( 5678, $refusal['podcast_id'] );
		$this->assertSame( array(), $refusal['differences'] );
		$this->assertSame( 'guid-terminal-push', $refusal['guid'] );
	}

	/**
	 * An unknown refusal from a series/create push stores Castos's error.
	 */
	public function testUpdatePodcastDataRecordsUnknownRefusalError() {
		$series_id = $this->create_series();
		$guid      = 'guid-unknown-push';
		$error     = 'A future Castos push refusal.';
		ssp_update_option( 'data_guid', $guid, $series_id );
		$this->series_reply = array(
			'response' => array( 'code' => 409 ),
			'body'     => wp_json_encode(
				array(
					'code'  => 'future_push_refusal',
					'error' => $error,
				)
			),
		);

		$this->castos_handler->update_podcast_data( $this->push_data( $series_id ) );

		$refusal = $this->refusal_repository()->get( $series_id );
		$this->assertIsArray( $refusal );
		$this->assertSame( 'future_push_refusal', $refusal['code'] );
		$this->assertSame( $error, $refusal['error'] );
		$this->assertSame( 0, $refusal['podcast_id'] );
		$this->assertSame( array(), $refusal['differences'] );
		$this->assertSame( $guid, $refusal['guid'] );
	}

	/**
	 * A refusal for the legacy no-term series is not written or readable.
	 */
	public function testUpdatePodcastDataIgnoresLegacySeriesRefusal() {
		$real_series_id = $this->create_series();
		$refusal        = $this->existing_refusal();
		ssp_update_option( 'data_guid', 'guid-original', $real_series_id );
		$this->refusal_repository()->record( $real_series_id, $refusal );
		$this->assertSame( $refusal, get_term_meta( $real_series_id, self::SYNC_REFUSAL_META_KEY, true ) );

		$this->series_reply = array(
			'response' => array( 'code' => 409 ),
			'body'     => wp_json_encode(
				array(
					'success'     => false,
					'code'        => 'guid_match_details_differ',
					'error'       => 'Update Seriously Simple Podcasting to the latest version to proceed.',
					'podcast_id'  => 1234,
					'differences' => array( 'podcast_title' => 'The Castos title' ),
				)
			),
		);

		$this->castos_handler->update_podcast_data( $this->push_data( 0 ) );

		$this->assertSame( $refusal, get_term_meta( $real_series_id, self::SYNC_REFUSAL_META_KEY, true ) );
		$this->assertFalse( metadata_exists( 'term', 0, self::SYNC_REFUSAL_META_KEY ) );
		$this->assertNull( $this->refusal_repository()->get( 0 ) );
	}

	/**
	 * A code-less series push error preserves an existing refusal.
	 */
	public function testUpdatePodcastDataIgnoresCodeLessError() {
		$this->assert_refusal_survives_push_reply(
			array(
				'response' => array( 'code' => 409 ),
				'body'     => wp_json_encode(
					array( 'error' => 'A sync is already in progress for this podcast.' )
				),
			)
		);
	}

	/**
	 * A code-less series push does not create a refusal when none exists.
	 */
	public function testUpdatePodcastDataDoesNotRecordCodeLessErrorWithoutExistingRefusal() {
		$series_id = $this->create_series();
		ssp_update_option( 'data_guid', 'guid-no-refusal-push', $series_id );
		$this->series_reply = array(
			'response' => array( 'code' => 409 ),
			'body'     => wp_json_encode(
				array( 'error' => 'A sync is already in progress for this podcast.' )
			),
		);

		$this->castos_handler->update_podcast_data( $this->push_data( $series_id ) );

		$this->assertNull( $this->refusal_repository()->get( $series_id ) );
		$this->assertFalse( metadata_exists( 'term', $series_id, self::SYNC_REFUSAL_META_KEY ) );
	}

	/**
	 * A successful series push clears an existing refusal.
	 */
	public function testUpdatePodcastDataClearsRefusalOnSuccess() {
		$series_id = $this->create_series();
		ssp_update_option( 'data_guid', 'guid-original', $series_id );
		$this->refusal_repository()->record( $series_id, $this->existing_refusal() );
		$this->series_reply = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'status' => 'success' ) ),
		);

		$this->castos_handler->update_podcast_data( $this->push_data( $series_id ) );

		$this->assertNull( $this->refusal_repository()->get( $series_id ) );
	}

	/**
	 * An HTTP 200 without a success status preserves an existing push refusal.
	 */
	public function testUpdatePodcastDataPreservesRefusalWhenHttp200BodyHasNoStatus() {
		$series_id = $this->create_series();
		ssp_update_option( 'data_guid', 'guid-malformed-success', $series_id );
		$refusal         = $this->existing_refusal();
		$refusal['guid'] = 'guid-malformed-success';
		$this->refusal_repository()->record( $series_id, $refusal );
		$this->series_reply = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'error' => 'Malformed success body.' ) ),
		);

		$this->castos_handler->update_podcast_data( $this->push_data( $series_id ) );

		$this->assertSame( $refusal, $this->refusal_repository()->get( $series_id ) );
	}

	/**
	 * An unavailable series/create reply leaves an existing push refusal intact.
	 *
	 * @dataProvider unavailable_replies
	 */
	public function testUpdatePodcastDataPreservesRefusalOnUnavailableReply( $reply ) {
		$this->assert_refusal_survives_push_reply( $reply );
	}

	public function unavailable_replies() {
		return array(
			'transport error' => array( new \WP_Error( 'http_request_failed', 'Connection failed.' ) ),
			'null response'   => array( null ),
		);
	}

	/**
	 * A coded details-difference reply is stored against the series term.
	 */
	public function testTriggerPodcastSyncRecordsGuidMatchRefusal() {
		$series_id = $this->create_series();
		ssp_update_option( 'data_guid', 'guid-original', $series_id );
		$this->sync_reply = array(
			'response' => array( 'code' => 409 ),
			'body'     => wp_json_encode(
				array(
					'success'     => false,
					'code'        => 'guid_match_details_differ',
					'error'       => 'Update Seriously Simple Podcasting to the latest version to proceed.',
					'podcast_id'  => 1234,
					'differences' => array(
						'podcast_title' => 'The Castos title',
						'website'       => 'https://example.com',
					),
				)
			),
		);

		$this->castos_handler->trigger_podcast_sync( $series_id );

		$refusal = get_term_meta( $series_id, self::SYNC_REFUSAL_META_KEY, true );

		$this->assertIsArray( $refusal );
		$this->assertSame( 'guid_match_details_differ', $refusal['code'] );
		$this->assertSame( 1234, $refusal['podcast_id'] );
		$this->assertSame( 'guid-original', $refusal['guid'] );
		$difference_keys = $refusal['differences'];
		sort( $difference_keys );
		$this->assertSame( array( 'podcast_title', 'website' ), $difference_keys );
	}

	/**
	 * A terminal GUID conflict is recorded with its Castos reason.
	 */
	public function testTriggerPodcastSyncRecordsGuidAlreadyInUseRefusal() {
		$series_id = $this->create_series();
		ssp_update_option( 'data_guid', 'guid-terminal', $series_id );
		$this->sync_reply = array(
			'response' => array( 'code' => 409 ),
			'body'     => wp_json_encode(
				array(
					'success'    => false,
					'code'       => 'guid_already_in_use',
					'error'      => 'This GUID already belongs to another podcast in this account.',
					'podcast_id' => 5678,
				)
			),
		);

		$this->castos_handler->trigger_podcast_sync( $series_id );

		$refusal = $this->refusal_repository()->get( $series_id );
		$this->assertIsArray( $refusal );
		$this->assertSame( 'guid_already_in_use', $refusal['code'] );
		$this->assertSame( 5678, $refusal['podcast_id'] );
		$this->assertSame( array(), $refusal['differences'] );
		$this->assertSame( 'guid-terminal', $refusal['guid'] );
	}

	/**
	 * Unknown refusal codes keep Castos's error for the Hosting fallback.
	 */
	public function testTriggerPodcastSyncRecordsUnknownRefusalError() {
		$series_id = $this->create_series();
		$guid      = 'guid-unknown-refusal';
		$error     = 'A future Castos refusal reason.';
		ssp_update_option( 'data_guid', $guid, $series_id );
		$this->sync_reply = array(
			'response' => array( 'code' => 409 ),
			'body'     => wp_json_encode(
				array(
					'code'  => 'future_refusal_code',
					'error' => $error,
				)
			),
		);

		$this->castos_handler->trigger_podcast_sync( $series_id );

		$refusal = $this->refusal_repository()->get( $series_id );
		$this->assertIsArray( $refusal );
		$this->assertSame( 'future_refusal_code', $refusal['code'] );
		$this->assertSame( $error, $refusal['error'] );
		$this->assertSame( $guid, $refusal['guid'] );
	}

	/**
	 * Refusal records are scoped to their series, including when one series is
	 * the default podcast.
	 */
	public function testRefusalRepositoryDoesNotFallBackToDefaultSeries() {
		$default_series_id = $this->create_series();
		$other_series_id   = $this->create_series();
		$previous_default  = ssp_get_option( 'default_series', 0 );
		$refusal           = $this->existing_refusal();

		ssp_update_option( 'default_series', $default_series_id );
		ssp_update_option( 'data_guid', 'guid-original', $default_series_id );
		ssp_update_option( 'data_guid', 'guid-original', $other_series_id );

		try {
			$this->refusal_repository()->record( $default_series_id, $refusal );

			$this->assertSame( $refusal, get_term_meta( $default_series_id, self::SYNC_REFUSAL_META_KEY, true ) );
			$this->assertSame( $refusal, $this->refusal_repository()->get( $default_series_id ) );
			$this->assertNull( $this->refusal_repository()->get( $other_series_id ) );
			$this->assertFalse( metadata_exists( 'term', $other_series_id, self::SYNC_REFUSAL_META_KEY ) );
		} finally {
			ssp_update_option( 'default_series', $previous_default );
		}
	}

	/**
	 * A refusal no longer applies after the podcast GUID changes.
	 */
	public function testRefusalRepositoryIgnoresRecordAfterGuidChanges() {
		$series_id = $this->create_series();
		ssp_update_option( 'data_guid', 'guid-original', $series_id );
		$this->refusal_repository()->record( $series_id, $this->existing_refusal() );

		ssp_update_option( 'data_guid', 'guid-replaced', $series_id );

		$this->assertNull( $this->refusal_repository()->get( $series_id ) );
	}

	/**
	 * An unavailable Initial Sync reply leaves an existing refusal intact.
	 *
	 * @dataProvider unavailable_replies
	 */
	public function testTriggerPodcastSyncPreservesRefusalOnUnavailableReply( $reply ) {
		$this->assert_refusal_survives_sync_reply( $reply );
	}

	/**
	 * A plain 409 for an in-progress sync does not create or clear a refusal record.
	 */
	public function testTriggerPodcastSyncIgnoresPlainConflict() {
		$series_id       = $this->create_series();
		$existing_refusal = array(
			'code'        => 'guid_match_details_differ',
			'podcast_id'  => 1234,
			'differences' => array( 'website' ),
			'guid'        => 'guid-original',
		);
		ssp_update_option( 'data_guid', 'guid-original', $series_id );
		update_term_meta( $series_id, self::SYNC_REFUSAL_META_KEY, $existing_refusal );
		$this->sync_reply = array(
			'response' => array( 'code' => 409 ),
			'body'     => wp_json_encode(
				array( 'error' => 'A sync is already in progress for this podcast.' )
			),
		);

		$this->castos_handler->trigger_podcast_sync( $series_id );

		$this->assertSame( $existing_refusal, get_term_meta( $series_id, self::SYNC_REFUSAL_META_KEY, true ) );
	}

	/**
	 * A plain 409 does not create a refusal when none exists, including series 0.
	 */
	public function testTriggerPodcastSyncIgnoresPlainConflictWithoutExistingRefusal() {
		$series_id = $this->create_series();
		ssp_update_option( 'data_guid', 'guid-no-refusal-sync', $series_id );

		foreach ( array( $series_id, 0 ) as $sync_series_id ) {
			$this->sync_reply = array(
				'response' => array( 'code' => 409 ),
				'body'     => wp_json_encode(
					array( 'error' => 'A sync is already in progress for this podcast.' )
				),
			);

			$this->castos_handler->trigger_podcast_sync( $sync_series_id );

			$this->assertNull( $this->refusal_repository()->get( $sync_series_id ) );
			$this->assertFalse( metadata_exists( 'term', $sync_series_id, self::SYNC_REFUSAL_META_KEY ) );
		}
	}

	/**
	 * A successful confirmation sends only the connect action, accepts Castos's
	 * string success value, and clears the refusal.
	 */
	public function testTriggerPodcastSyncConfirmationClearsRefusal() {
		$series_id = $this->create_series();
		ssp_update_option( 'data_guid', 'guid-original', $series_id );
		$this->refusal_repository()->record( $series_id, $this->existing_refusal() );
		$this->sync_reply = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'success' => 'true' ) ),
		);

		$response = $this->castos_handler->trigger_podcast_sync( $series_id, Sync_Refusal_Repository::ACTION_CONNECT );

		$this->assertSame( 200, $response['code'] );
		$this->assertSame( 'true', $response['success'] );
		$this->assertSame(
			trailingslashit( SSP_CASTOS_APP_URL ) . 'api/v2/ssp/podcast-sync/' . $series_id,
			$this->sync_request['url']
		);

		$request_body = $this->sync_request['args']['body'];
		$this->assertSame( Sync_Refusal_Repository::ACTION_CONNECT, $request_body['confirm_action'] );
		$this->assertArrayNotHasKey( 'guid', $request_body );
		$this->assertArrayNotHasKey( 'create_new', $request_body );
		unset( $request_body['confirm_action'], $request_body['token'], $request_body['api_token'] );
		$this->assertSame( array(), $request_body );
		$this->assertNull( $this->refusal_repository()->get( $series_id ) );
	}

	/**
	 * An ordinary successful sync clears an existing refusal without a confirm action.
	 */
	public function testTriggerPodcastSyncClearsRefusalOnOrdinarySuccess() {
		$series_id = $this->create_series();
		ssp_update_option( 'data_guid', 'guid-original', $series_id );
		$this->refusal_repository()->record( $series_id, $this->existing_refusal() );
		$this->sync_reply = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'success' => 'true' ) ),
		);

		$response = $this->castos_handler->trigger_podcast_sync( $series_id );

		$this->assertSame( 200, $response['code'] );
		$request_body = $this->sync_request['args']['body'];
		$this->assertArrayNotHasKey( 'confirm_action', $request_body );
		unset( $request_body['token'], $request_body['api_token'] );
		$this->assertSame( array(), $request_body );
		$this->assertNull( $this->refusal_repository()->get( $series_id ) );
	}

	/**
	 * A coded refusal returned for a confirmed request replaces the old
	 * snapshot instead of leaving stale podcast details in the record.
	 */
	public function testConfirmedPodcastSyncReplacesRefusal() {
		$series_id = $this->create_series();
		$guid      = 'guid-repeat-refusal';
		$old       = array(
			'code'        => 'guid_match_details_differ',
			'podcast_id'  => 1234,
			'differences' => array( 'podcast_title' ),
			'guid'        => $guid,
		);
		ssp_update_option( 'data_guid', $guid, $series_id );
		$this->refusal_repository()->record( $series_id, $old );
		$this->sync_reply = array(
			'response' => array( 'code' => 409 ),
			'body'     => wp_json_encode(
				array(
					'success'     => false,
					'code'        => 'guid_match_details_differ',
					'error'       => 'The Castos values changed before confirmation.',
					'podcast_id'  => 9876,
					'differences' => array(
						'website'          => 'https://new.example.test',
						'itunes_category2' => 'Arts: Books',
					),
				)
			),
		);

		$this->castos_handler->trigger_podcast_sync( $series_id, Sync_Refusal_Repository::ACTION_CONNECT );

		$replacement = $this->refusal_repository()->get( $series_id );
		$this->assertIsArray( $replacement );
		$this->assertSame( 'guid_match_details_differ', $replacement['code'] );
		$this->assertSame( 9876, $replacement['podcast_id'] );
		$difference_keys = $replacement['differences'];
		sort( $difference_keys );
		$this->assertSame( array( 'itunes_category2', 'website' ), $difference_keys );
		$this->assertSame( $guid, $replacement['guid'] );
	}

	/**
	 * A successful HTTP response clears an existing refusal instead of recording
	 * a stale body code.
	 */
	public function testSuccessfulPodcastSyncClearsRefusalInsteadOfRecordingBodyCode() {
		$series_id = $this->create_series();
		$guid      = 'guid-success-with-code';
		ssp_update_option( 'data_guid', $guid, $series_id );
		$refusal         = $this->existing_refusal();
		$refusal['guid'] = $guid;
		$this->refusal_repository()->record( $series_id, $refusal );
		$this->sync_reply = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode(
				array(
					'success'     => 'true',
					'code'        => 'guid_match_details_differ',
					'podcast_id'  => 1234,
					'differences' => array( 'podcast_title' => 'Stale refusal' ),
				)
			),
		);

		$this->castos_handler->trigger_podcast_sync( $series_id );

		$this->assertNull( $this->refusal_repository()->get( $series_id ) );
		$this->assertFalse( metadata_exists( 'term', $series_id, self::SYNC_REFUSAL_META_KEY ) );
	}

	/**
	 * Initial Sync must not create or read refusal state for the legacy series.
	 */
	public function testConfirmedLegacySeriesDoesNotUseRefusalRepository() {
		$real_series_id = $this->create_series();
		$refusal        = $this->existing_refusal();
		ssp_update_option( 'data_guid', 'guid-original', $real_series_id );
		$this->refusal_repository()->record( $real_series_id, $refusal );
		$this->sync_reply = array(
			'response' => array( 'code' => 409 ),
			'body'     => wp_json_encode(
				array(
					'success'     => false,
					'code'        => 'guid_match_details_differ',
					'podcast_id'  => 2468,
					'differences' => array( 'podcast_title' => 'A Castos title' ),
				)
			),
		);

		$this->castos_handler->trigger_podcast_sync( 0, Sync_Refusal_Repository::ACTION_CONNECT );

		$this->assertSame( $refusal, $this->refusal_repository()->get( $real_series_id ) );
		$this->assertFalse( metadata_exists( 'term', 0, self::SYNC_REFUSAL_META_KEY ) );
		$this->assertNull( $this->refusal_repository()->get( 0 ) );
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
