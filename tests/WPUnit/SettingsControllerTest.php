<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Settings_Controller;
use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Renderers\Settings_Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Repositories\Sync_Refusal_Repository;

class SettingsControllerTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Settings_Controller
	 */
	private $settings_controller;

	/**
	 * @var Castos_Handler|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $castos_handler;

	/**
	 * @var Episode_Repository|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $episode_repository;

	/**
	 * @var Sync_Refusal_Repository
	 */
	private $refusal_repository;

	protected function setUp(): void {
		parent::setUp();

		$this->castos_handler     = $this->createMock( Castos_Handler::class );
		$this->episode_repository = $this->createMock( Episode_Repository::class );
		$this->episode_repository->method( 'get_podcast_episodes' )->willReturn( array() );
		$this->refusal_repository = new Sync_Refusal_Repository();

		$this->settings_controller = new Settings_Controller(
			new Settings_Handler(),
			Settings_Renderer::instance(),
			new Renderer(),
			$this->createMock( Series_Handler::class ),
			$this->castos_handler,
			$this->episode_repository,
			$this->refusal_repository
		);
	}

	protected function tearDown(): void {
		remove_filter( 'ssp_field_data', array( $this->settings_controller, 'provide_podcasts_sync_status' ) );
		parent::tearDown();
	}

	/**
	 * A stored details refusal overrides a local synced guess and renders a
	 * labelled modal with only field names and one confirmation action.
	 */
	public function testStoredDetailsRefusalRendersConfirmationModal() {
		$series_id = $this->create_series( 'Needs Confirmation Podcast' );
		$guid      = 'step-four-details-guid';
		$differences = array(
			'podcast_title',
			'podcast_description',
			'website',
			'itunes_category1',
			'itunes_category2',
			'itunes_category3',
		);

		ssp_update_option( 'data_guid', $guid, $series_id );
		$this->configure_castos_podcast( null, 'none', Sync_Status::SYNC_STATUS_NONE );

		$local_status = $this->assemble_status_data( $series_id );
		$this->assertNotSame(
			Sync_Status::SYNC_STATUS_NEEDS_CONFIRMATION,
			$local_status['statuses'][ $series_id ]->status,
			'The unconnected fixture must not report confirmation before a refusal is stored'
		);

		$this->refusal_repository->record(
			$series_id,
			array(
				'code'        => 'guid_match_details_differ',
				'podcast_id'  => 1234,
				'differences' => array_merge( $differences, array( 'unknown_castos_field' ) ),
				'guid'        => $guid,
			)
		);

		$data = $this->assemble_status_data( $series_id );
		$this->assertSame( Sync_Status::SYNC_STATUS_NEEDS_CONFIRMATION, $data['statuses'][ $series_id ]->status );

		$translate = $this->add_difference_label_translations();
		try {
			$html = $this->render_status( $series_id, $data );
		} finally {
			remove_filter( 'gettext', $translate, 10 );
		}

		$dialog_id = 'ssp-sync-refusal-' . $series_id;
		$this->assertStringContainsString( 'needs_confirmation', $html );
		$this->assertStringContainsString( 'data-ssp-modal-open="' . esc_attr( $dialog_id ) . '"', $html );
		$this->assertStringContainsString( 'aria-controls="' . esc_attr( $dialog_id ) . '"', $html );
		$this->assertStringContainsString( 'id="' . esc_attr( $dialog_id ) . '"', $html );
		$this->assertStringContainsString( 'js-sync-refusal-connect', $html );
		$this->assertStringContainsString( 'Translated podcast title', $html );
		$this->assertStringContainsString( 'Translated podcast description', $html );
		$this->assertStringContainsString( 'Translated podcast URL', $html );
		$this->assertStringContainsString( 'Translated category one', $html );
		$this->assertStringContainsString( 'Translated category two', $html );
		$this->assertStringContainsString( 'Translated category three', $html );
		$this->assertStringContainsString( 'unknown_castos_field', $html );
		$edit_link = get_edit_term_link( $series_id, ssp_series_taxonomy(), SSP_CPT_PODCAST );
		$this->assertNotWPError( $edit_link );
		$this->assertTrue( $this->html_contains_href( $html, $edit_link ) );
	}

	/**
	 * Each podcast row gets one modal containing only that row's refusal fields.
	 */
	public function testRefusalRowsHaveDistinctOwnModals() {
		$first_id  = $this->create_series( 'First Modal Podcast' );
		$second_id = $this->create_series( 'Second Modal Podcast' );
		$first_guid  = 'first-modal-guid';
		$second_guid = 'second-modal-guid';
		ssp_update_option( 'data_guid', $first_guid, $first_id );
		ssp_update_option( 'data_guid', $second_guid, $second_id );
		$this->configure_castos_podcast( null, 'none', Sync_Status::SYNC_STATUS_NONE );

		$this->refusal_repository->record(
			$first_id,
			array(
				'code'        => 'guid_match_details_differ',
				'podcast_id'  => 1111,
				'differences' => array( 'podcast_title' ),
				'guid'        => $first_guid,
			)
		);
		$this->refusal_repository->record(
			$second_id,
			array(
				'code'        => 'guid_match_details_differ',
				'podcast_id'  => 2222,
				'differences' => array( 'website' ),
				'guid'        => $second_guid,
			)
		);

		$data = $this->assemble_status_data_for_series( array( $first_id, $second_id ) );
		$html = $this->render_statuses( array( $first_id, $second_id ), $data );
		$first_dialog  = $this->dialog_for_series( $html, $first_id );
		$second_dialog = $this->dialog_for_series( $html, $second_id );

		$this->assertSame( Sync_Status::SYNC_STATUS_NEEDS_CONFIRMATION, $data['statuses'][ $first_id ]->status );
		$this->assertSame( Sync_Status::SYNC_STATUS_NEEDS_CONFIRMATION, $data['statuses'][ $second_id ]->status );
		$this->assertStringContainsString( 'id="ssp-sync-refusal-' . $first_id . '"', $html );
		$this->assertStringContainsString( 'id="ssp-sync-refusal-' . $second_id . '"', $html );
		$this->assertStringContainsString( 'data-ssp-modal-open="ssp-sync-refusal-' . $first_id . '"', $html );
		$this->assertStringContainsString( 'data-ssp-modal-open="ssp-sync-refusal-' . $second_id . '"', $html );
		$this->assertStringContainsString( 'aria-controls="ssp-sync-refusal-' . $first_id . '"', $html );
		$this->assertStringContainsString( 'aria-controls="ssp-sync-refusal-' . $second_id . '"', $html );
		$this->assertStringContainsString( 'Podcast title', $first_dialog->textContent );
		$this->assertStringNotContainsString( 'Podcast URL', $first_dialog->textContent );
		$this->assertStringContainsString( 'Podcast URL', $second_dialog->textContent );
		$this->assertStringNotContainsString( 'Podcast title', $second_dialog->textContent );
		$this->assertTrue( $this->dialog_has_connect_action( $first_dialog ) );
		$this->assertTrue( $this->dialog_has_connect_action( $second_dialog ) );
	}

	/**
	 * A completed Castos connection wins over a stored refusal in the display.
	 */
	public function testCastosConnectionWinsInDisplay() {
		$series_id = $this->create_series( 'Connected Podcast' );
		$guid      = 'step-four-connected-guid';
		ssp_update_option( 'data_guid', $guid, $series_id );
		$refusal = array(
			'code'        => 'guid_match_details_differ',
			'podcast_id'  => 1234,
			'differences' => array( 'podcast_title' ),
			'guid'        => $guid,
		);
		$this->refusal_repository->record( $series_id, $refusal );
		$this->configure_castos_podcast( $series_id, 'in_progress', Sync_Status::SYNC_STATUS_SYNCING );

		$data = $this->assemble_status_data( $series_id );
		$html = $this->render_status( $series_id, $data );

		$this->assertSame( Sync_Status::SYNC_STATUS_SYNCING, $data['statuses'][ $series_id ]->status );
		$this->assertStringContainsString( 'syncing', $html );
		$this->assertStringNotContainsString( 'needs_confirmation', $html );
		$this->assertStringNotContainsString( 'js-sync-refusal-connect', $html );
		$this->assertStringNotContainsString( 'data-ssp-modal-open', $html );
		$this->assertSame( $refusal, $this->refusal_repository->get( $series_id ) );
	}

	/**
	 * A cached status read uses the cached response, while a live read uses the
	 * refreshed Castos response.
	 */
	public function testGetSeriesSyncStatusesUsesCachedAndLiveReads() {
		$series_id      = $this->create_series( 'Live Status Podcast' );
		$cached_podcast = array(
			'series_id'         => $series_id,
			'ssp_import_status' => 'completed',
		);
		$live_podcast   = array(
			'series_id'         => $series_id,
			'ssp_import_status' => 'in_progress',
		);
		$cached_response = array(
			'status' => 'success',
			'data'   => array( 'podcast_list' => array( $cached_podcast ) ),
		);
		$live_response = array(
			'status' => 'success',
			'data'   => array( 'podcast_list' => array( $live_podcast ) ),
		);

		$this->castos_handler->expects( $this->exactly( 2 ) )
			->method( 'get_podcasts' )
			->withConsecutive( array( false ), array( true ) )
			->willReturnOnConsecutiveCalls( $cached_response, $live_response );
		$this->castos_handler->expects( $this->exactly( 2 ) )
			->method( 'retrieve_sync_status_by_podcast_data' )
			->withConsecutive( array( $cached_podcast ), array( $live_podcast ) )
			->willReturnOnConsecutiveCalls(
				new Sync_Status( Sync_Status::SYNC_STATUS_SYNCED ),
				new Sync_Status( Sync_Status::SYNC_STATUS_SYNCING )
			);

		$cached_data = $this->settings_controller->get_series_sync_statuses( array( $series_id ) );
		$live_data   = $this->settings_controller->get_series_sync_statuses( array( $series_id ), true );

		$this->assertSame( Sync_Status::SYNC_STATUS_SYNCED, $cached_data['statuses'][ $series_id ]->status );
		$this->assertSame( Sync_Status::SYNC_STATUS_SYNCING, $live_data['statuses'][ $series_id ]->status );
	}

	/**
	 * A terminal GUID conflict renders as Failed, with a translated reason
	 * and an optional link to the conflicting podcast, but no confirmation
	 * action.
	 *
	 * @dataProvider terminal_refusal_cases
	 */
	public function testGuidAlreadyInUseRendersFailedWithCastosLink( $podcast_id, $expected_link ) {
		$series_id         = $this->create_series( 'Terminal Conflict Podcast' );
		$guid              = 'step-four-terminal-guid';
		$castos_error      = 'Castos <strong>terminal reason</strong>';
		$translated_reason = 'Translated terminal refusal reason';
		ssp_update_option( 'data_guid', $guid, $series_id );
		$this->refusal_repository->record(
			$series_id,
			array(
				'code'        => 'guid_already_in_use',
				'podcast_id'  => $podcast_id,
				'differences' => array(),
				'guid'        => $guid,
				'error'       => $castos_error,
			)
		);
		$this->configure_castos_podcast( null, 'none', Sync_Status::SYNC_STATUS_NONE );

		$data      = $this->assemble_status_data( $series_id );
		$translate = $this->add_terminal_reason_translation( $translated_reason );
		try {
			$html = $this->render_status( $series_id, $data );
		} finally {
			remove_filter( 'gettext', $translate, 10 );
		}

		$this->assertSame( Sync_Status::SYNC_STATUS_FAILED, $data['statuses'][ $series_id ]->status );
		$this->assertStringContainsString( 'failed', $html );
		$this->assertStringContainsString( esc_html( $translated_reason ), $html );
		$this->assertStringNotContainsString( $castos_error, $html );
		$this->assertStringNotContainsString( esc_html( $castos_error ), $html );
		$this->assertStringNotContainsString( 'data-ssp-modal-open', $html );
		$this->assertStringNotContainsString( 'js-sync-refusal-connect', $html );

		if ( $expected_link ) {
			$this->assertTrue( $this->html_contains_href( $html, $expected_link ) );
		} else {
			$this->assertStringNotContainsString( trailingslashit( SSP_CASTOS_APP_URL ) . 'podcasts/', $html );
		}
	}

	public function terminal_refusal_cases() {
		return array(
			'with Castos ID' => array(
				5678,
				trailingslashit( SSP_CASTOS_APP_URL ) . 'podcasts/5678/edit/settings/overview',
			),
			'empty ID'      => array( '', '' ),
			'zero ID'       => array( 0, '' ),
		);
	}

	/**
	 * An unknown refusal code falls back to Castos's error, escaped for the
	 * HTML sink used by settings.js.
	 */
	public function testUnknownRefusalEscapesCastosErrorFallback() {
		$series_id    = $this->create_series( 'Unknown Refusal Podcast' );
		$guid         = 'step-four-unknown-guid';
		$castos_error = 'Castos <strong>future refusal reason</strong>';
		ssp_update_option( 'data_guid', $guid, $series_id );
		$this->refusal_repository->record(
			$series_id,
			array(
				'code'        => 'future_refusal_code',
				'podcast_id'  => 5678,
				'differences' => array(),
				'guid'        => $guid,
				'error'       => $castos_error,
			)
		);
		$this->configure_castos_podcast( null, 'none', Sync_Status::SYNC_STATUS_NONE );

		$data = $this->assemble_status_data( $series_id );
		$html = $this->render_status( $series_id, $data );

		$this->assertSame( Sync_Status::SYNC_STATUS_FAILED, $data['statuses'][ $series_id ]->status );
		$this->assertStringContainsString( 'failed', $html );
		$this->assertStringContainsString( esc_html( $castos_error ), $html );
		$this->assertStringNotContainsString( $castos_error, $html );
		$this->assertStringNotContainsString( 'data-ssp-modal-open', $html );
		$this->assertStringNotContainsString( 'js-sync-refusal-connect', $html );
	}

	/**
	 * Configure the Castos report used by the settings status assembly.
	 *
	 * @param int|null $castos_series_id Castos series association; null is unconnected.
	 * @param string   $castos_status    Castos import status.
	 * @param string   $sync_status      SSP status to return for the Castos report.
	 * @param array    $details          Optional Castos podcast detail values.
	 */
	private function configure_castos_podcast( $castos_series_id, $castos_status, $sync_status, $details = array() ) {
		$podcast = array_merge(
			array(
				'series_id'         => $castos_series_id,
				'ssp_import_status' => $castos_status,
			),
			$details
		);

		$this->castos_handler->method( 'get_podcasts' )->willReturn(
			array(
				'status' => 'success',
				'data'   => array( 'podcast_list' => array( $podcast ) ),
			)
		);
		$this->castos_handler
			->method( 'retrieve_sync_status_by_podcast_data' )
			->willReturn( new Sync_Status( $sync_status ) );
	}

	/**
	 * Build the field data passed to the Hosting field's status filter.
	 */
	private function assemble_status_data( $series_id ) {
		return $this->assemble_status_data_for_series( array( $series_id ) );
	}

	/**
	 * Build status data for one or more Hosting rows.
	 *
	 * @param int[] $series_ids
	 * @return array
	 */
	private function assemble_status_data_for_series( $series_ids ) {
		$field = $this->sync_field( $series_ids );

		return $this->settings_controller->provide_podcasts_sync_status(
			array(),
			array( 'field' => $field )
		);
	}

	/**
	 * Render one Hosting sync row from assembled field data.
	 */
	private function render_status( $series_id, $data ) {
		return $this->render_statuses( array( $series_id ), $data );
	}

	/**
	 * Render one or more Hosting sync rows from assembled field data.
	 *
	 * @param int[] $series_ids
	 * @param array $data
	 * @return string
	 */
	private function render_statuses( $series_ids, $data ) {
		return Settings_Renderer::instance()->render_field(
			$this->sync_field( $series_ids ),
			$data,
			'ss_podcasting_podcasts_sync'
		);
	}

	/**
	 * @param int|int[] $series_ids
	 * @return array
	 */
	private function sync_field( $series_ids ) {
		$options = array();
		foreach ( (array) $series_ids as $series_id ) {
			$options[ $series_id ] = 'Test podcast';
		}

		return array(
			'id'      => 'podcasts_sync',
			'type'    => 'podcasts_sync',
			'options' => $options,
		);
	}

	/**
	 * Check whether rendered HTML contains an anchor with the expected href.
	 *
	 * @param string $html Rendered HTML.
	 * @param string $href Expected href.
	 * @return bool
	 */
	private function html_contains_href( $html, $href ) {
		$document = $this->html_document( $html );
		$expected = html_entity_decode( $href, ENT_QUOTES, 'UTF-8' );

		foreach ( $document->getElementsByTagName( 'a' ) as $link ) {
			$actual = html_entity_decode( $link->getAttribute( 'href' ), ENT_QUOTES, 'UTF-8' );
			if ( $expected === $actual ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Parse a rendered HTML fragment for semantic assertions.
	 *
	 * @param string $html Rendered HTML.
	 * @return \DOMDocument
	 */
	private function html_document( $html ) {
		$document = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$document->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		return $document;
	}

	/**
	 * Extract one modal from the rendered rows by its semantic ID.
	 *
	 * @param string $html
	 * @param int    $series_id
	 * @return \DOMElement
	 */
	private function dialog_for_series( $html, $series_id ) {
		$document = $this->html_document( $html );
		$dialog_id = 'ssp-sync-refusal-' . $series_id;
		$dialogs   = array();

		foreach ( $document->getElementsByTagName( 'dialog' ) as $dialog ) {
			if ( $dialog_id === $dialog->getAttribute( 'id' ) ) {
				$dialogs[] = $dialog;
			}
		}

		$this->assertCount( 1, $dialogs, 'Each refusal row must render one dialog with its own ID.' );

		return $dialogs[0];
	}

	/**
	 * Check whether a modal contains the confirmation action hook.
	 *
	 * @param \DOMElement $dialog Modal element.
	 * @return bool
	 */
	private function dialog_has_connect_action( $dialog ) {
		foreach ( $dialog->getElementsByTagName( '*' ) as $element ) {
			$classes = preg_split( '/\s+/', trim( $element->getAttribute( 'class' ) ) );
			if ( in_array( 'js-sync-refusal-connect', $classes, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Translate the known terminal refusal reason to a marker.
	 *
	 * @param string $translated_reason Marker returned by the translation.
	 * @return callable
	 */
	private function add_terminal_reason_translation( $translated_reason ) {
		$translate = function ( $translation, $text, $domain ) use ( $translated_reason ) {
			if (
				'seriously-simple-podcasting' !== $domain
				|| false === strpos( $text, 'This GUID already belongs to another podcast' )
			) {
				return $translation;
			}

			return $translated_reason;
		};

		add_filter( 'gettext', $translate, 10, 3 );

		return $translate;
	}

	/**
	 * Translate the field labels to markers so the test checks that labels use
	 * WordPress translation calls rather than raw API keys.
	 *
	 * @return callable
	 */
	private function add_difference_label_translations() {
		$translations = array(
			'Podcast title'       => 'Translated podcast title',
			'Podcast description' => 'Translated podcast description',
			'Podcast URL'         => 'Translated podcast URL',
			'iTunes category 1'   => 'Translated category one',
			'iTunes category 2'   => 'Translated category two',
			'iTunes category 3'   => 'Translated category three',
		);
		$translate = function ( $translation, $text, $domain ) use ( $translations ) {
			if ( 'seriously-simple-podcasting' !== $domain || ! isset( $translations[ $text ] ) ) {
				return $translation;
			}

			return $translations[ $text ];
		};

		add_filter( 'gettext', $translate, 10, 3 );

		return $translate;
	}

	private function create_series( $name ) {
		return $this->factory()->term->create(
			array(
				'taxonomy' => ssp_series_taxonomy(),
				'name'     => $name,
			)
		);
	}
}
