<?php
/**
 * Ajax Handler
 *
 * @package SeriouslySimplePodcasting
 * @category Handlers
 * @author Castos
 */

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Controllers\Settings_Controller;
use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Renderers\Settings_Renderer;
use SeriouslySimplePodcasting\Repositories\Sync_Refusal_Repository;

/**
 * Class Ajax_Handler
 *
 * Handles all AJAX requests for the plugin.
 *
 * @package SeriouslySimplePodcasting
 */
class Ajax_Handler {

	/**
	 * Castos handler instance.
	 *
	 * @var Castos_Handler
	 */
	protected $castos_handler;

	/**
	 * Admin notifications handler instance.
	 *
	 * @var Admin_Notifications_Handler
	 */
	protected $admin_notices_handler;

	/**
	 * Stores Castos sync refusals against podcast series terms.
	 *
	 * @var Sync_Refusal_Repository
	 */
	protected $sync_refusal_repository;

	/**
	 * Feed handler instance.
	 *
	 * @var Feed_Handler|null
	 */
	protected $feed_handler;

	/**
	 * Settings controller instance.
	 *
	 * @var Settings_Controller
	 */
	protected $settings_controller;

	/**
	 * Ajax_Handler constructor.
	 *
	 * @param Castos_Handler              $castos_handler          Castos handler.
	 * @param Admin_Notifications_Handler $admin_notices_handler   Admin notifications handler.
	 * @param Settings_Controller         $settings_controller     Settings controller.
	 * @param Sync_Refusal_Repository     $sync_refusal_repository Sync refusal repository.
	 * @param Feed_Handler                $feed_handler            Feed handler.
	 */
	public function __construct( $castos_handler, $admin_notices_handler, Settings_Controller $settings_controller, Sync_Refusal_Repository $sync_refusal_repository, Feed_Handler $feed_handler ) {
		$this->castos_handler          = $castos_handler;
		$this->admin_notices_handler   = $admin_notices_handler;
		$this->settings_controller     = $settings_controller;
		$this->sync_refusal_repository = $sync_refusal_repository;
		$this->feed_handler            = $feed_handler;

		$this->bootstrap();
	}

	/**
	 * Runs any functionality to be included in the object instantiation.
	 *
	 * @return void
	 */
	public function bootstrap() {
		// Add ajax action for plugin rating.
		add_action( 'wp_ajax_ssp_rated', array( $this, 'rated' ) );

		add_action( 'wp_ajax_connect_castos', array( $this, 'connect_castos' ) );

		add_action( 'wp_ajax_disconnect_castos', array( $this, 'disconnect_castos' ) );

		// Add ajax action for customising episode embed code.
		add_action( 'wp_ajax_update_episode_embed_code', array( $this, 'update_episode_embed_code' ) );

		// Add ajax action for importing external rss feed.
		add_action( 'wp_ajax_import_external_rss_feed', array( $this, 'import_external_rss_feed' ) );

		// Onboarding wizard: confirm a feed before importing it, then start the import.
		add_action( 'wp_ajax_ssp_preview_rss_feed', array( $this, 'preview_rss_feed' ) );
		add_action( 'wp_ajax_ssp_start_onboarding_import', array( $this, 'start_onboarding_import' ) );

		// Add ajax action for getting external rss feed progress.
		add_action( 'wp_ajax_get_external_rss_feed_progress', array( $this, 'get_external_rss_feed_progress' ) );

		// Add ajax action to reset external feed options.
		add_action( 'wp_ajax_reset_rss_feed_data', array( $this, 'reset_rss_feed_data' ) );

		// Add ajax action to the Castos sync process.
		add_action( 'wp_ajax_sync_castos', array( $this, 'sync_castos' ) );
		add_action( 'wp_ajax_ssp_get_series_sync_statuses', array( $this, 'get_series_sync_statuses' ) );

		// Ajax action to removing the constant notice.
		add_action( 'wp_ajax_remove_constant_notice', array( $this, 'remove_constant_notice' ) );

		// Ajax action to generate a podcast GUID.
		add_action( 'wp_ajax_ssp_generate_series_guid', array( $this, 'generate_series_guid' ) );
	}

	/**
	 * Removes constant notice.
	 *
	 * @return void
	 * @throws \Exception When the nonce is invalid.
	 */
	public function remove_constant_notice() {
		try {
			$id = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
			$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'notice-' . $id ) ) {
				throw new \Exception();
			}

			$this->admin_notices_handler->remove_constant_notice( $id );

			wp_send_json_success();
		} catch ( \Exception $e ) {
			wp_send_json_error();
		}
	}

	/**
	 * Indicate that plugin has been rated
	 *
	 * @return void
	 */
	public function rated() {
		if ( wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ), 'ssp_rated' ) && current_user_can( 'manage_podcast' ) ) {
			update_option( 'ssp_admin_footer_text_rated', 1 );
		}
		die();
	}

	/**
	 * Generate a podcast's GUID from its feed URL.
	 *
	 * @since 3.18.0
	 *
	 * @return void
	 * @throws \Exception When the podcast GUID can not be generated or saved.
	 */
	public function generate_series_guid() {
		try {
			$this->nonce_check( 'ssp_generate_series_guid' );
			$this->user_capability_check();

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce is verified above.
			$series_id = isset( $_POST['series_id'] ) && is_scalar( $_POST['series_id'] )
				? absint( wp_unslash( $_POST['series_id'] ) )
				: 0;
			$term = $series_id ? get_term( $series_id, ssp_series_taxonomy() ) : null;

			if ( ! $term || is_wp_error( $term ) ) {
				throw new \Exception( __( 'The podcast could not be found.', 'seriously-simple-podcasting' ) );
			}

			$is_podcast_connected_to_castos = ssp_is_connected_to_castos()
				&& null !== $this->castos_handler->get_podcast_by_series( $series_id );
			if ( $is_podcast_connected_to_castos ) {
				throw new \Exception( __( 'This podcast is connected to Castos. The GUID can\'t be changed while connected.', 'seriously-simple-podcasting' ) );
			}

			$guid = $this->feed_handler->get_derived_guid( $term->slug );
			if ( '' === $guid ) {
				throw new \Exception( __( 'The podcast GUID could not be generated. Please try again.', 'seriously-simple-podcasting' ) );
			}

			$stored_guid = ssp_get_option( 'data_guid', '', $series_id );
			if ( $guid !== $stored_guid && ! ssp_update_option( 'data_guid', $guid, $series_id ) ) {
				throw new \Exception( __( 'The podcast GUID could not be saved. Please try again.', 'seriously-simple-podcasting' ) );
			}

			$this->sync_refusal_repository->clear( $series_id );

			wp_send_json_success( array( 'guid' => $guid ) );
		} catch ( \Exception $e ) {
			$this->send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Sync podcasts with Castos
	 */
	public function sync_castos() {
		try {
			$this->nonce_check( 'ss_podcasting_castos-hosting' );
			$this->user_capability_check();

			$series_ids     = $this->int_array_from_get( 'podcasts' );
			$confirm_action = $this->confirm_action_from_get();

			$series_statuses = array();
			foreach ( $series_ids as $series_id ) {
				$series_statuses[ $series_id ] = $this->sync_series( $series_id, $confirm_action );
			}

			$status = $this->get_overall_sync_status( wp_list_pluck( $series_statuses, 'status' ) );

			$results = array(
				'status'   => $status,
				'msg'      => $this->get_overall_sync_message( $status ),
				'podcasts' => $series_statuses,
			);

			if ( Sync_Status::SYNC_STATUS_SYNCING === $status ) {
				wp_send_json_success( $results );
			} else {
				wp_send_json_error( $results );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Trigger the sync of one podcast and build its status for the response.
	 *
	 * @since 3.18.0
	 *
	 * @param int    $series_id      Podcast ID.
	 * @param string $confirm_action Confirmation action to send to Castos.
	 *
	 * @return array Status, title, message and rendered status label.
	 */
	protected function sync_series( $series_id, $confirm_action ) {
		$response      = $this->castos_handler->trigger_podcast_sync( $series_id, $confirm_action );
		$response_code = is_array( $response ) && isset( $response['code'] ) ? $response['code'] : null;
		$refusal       = $this->sync_refusal_repository->get( $series_id );
		$status        = $this->resolve_sync_status( $response_code, $refusal );

		do_action( 'ssp_triggered_podcast_sync', $series_id, $response, $status );

		$msg = $this->get_sync_message( $response, $response_code, $status );
		// translators: %1$s is the podcast name and %2$s is the sync error message.
		$msg_template = _x( '%1$s: %2$s', 'podcast-sync-error-message', 'seriously-simple-podcasting' );

		return array(
			'status' => $status,
			'title'  => $this->get_sync_status_title( $status ),
			'msg'    => $msg ? sprintf( $msg_template, $this->get_podcast_name( $series_id ), $msg ) : '',
			'html'   => Settings_Renderer::instance()->render_sync_status_label( $series_id, new Sync_Status( $status ), $refusal ),
		);
	}

	/**
	 * Resolve a podcast's sync status from its stored refusal and the Castos response code.
	 *
	 * @since 3.18.0
	 *
	 * @param int|string|null $response_code Castos response code.
	 * @param array|null      $refusal       Stored sync refusal, if any.
	 *
	 * @return string
	 */
	protected function resolve_sync_status( $response_code, $refusal ) {
		if ( null !== $refusal ) {
			$refusal_code = isset( $refusal['code'] ) ? $refusal['code'] : '';

			return Sync_Refusal_Repository::CODE_DETAILS_DIFFER === $refusal_code
				? Sync_Status::SYNC_STATUS_NEEDS_CONFIRMATION
				: Sync_Status::SYNC_STATUS_FAILED;
		}

		if ( Sync_Refusal_Repository::CODE_DETAILS_DIFFER === $response_code ) {
			return Sync_Status::SYNC_STATUS_NEEDS_CONFIRMATION;
		}

		if ( in_array( $response_code, array( 200, 409 ), true ) ) {
			return Sync_Status::SYNC_STATUS_SYNCING;
		}

		return Sync_Status::SYNC_STATUS_FAILED;
	}

	/**
	 * Get the label of a podcast sync status.
	 *
	 * @since 3.18.0
	 *
	 * @param string $status Sync status.
	 *
	 * @return string
	 */
	protected function get_sync_status_title( $status ) {
		$titles = array(
			Sync_Status::SYNC_STATUS_NEEDS_CONFIRMATION => __( 'Needs confirmation', 'seriously-simple-podcasting' ),
			Sync_Status::SYNC_STATUS_SYNCING            => __( 'Syncing', 'seriously-simple-podcasting' ),
			Sync_Status::SYNC_STATUS_FAILED             => __( 'Failed', 'seriously-simple-podcasting' ),
		);

		return isset( $titles[ $status ] ) ? $titles[ $status ] : $titles[ Sync_Status::SYNC_STATUS_FAILED ];
	}

	/**
	 * Get the message explaining a podcast's sync result.
	 *
	 * Known response codes use translated SSP copy, and unknown ones fall back to Castos's error.
	 *
	 * @since 3.18.0
	 *
	 * @param array|mixed     $response      Castos response.
	 * @param int|string|null $response_code Castos response code.
	 * @param string          $status        Resolved sync status.
	 *
	 * @return string
	 */
	protected function get_sync_message( $response, $response_code, $status ) {
		$msgs_map = array(
			Sync_Refusal_Repository::CODE_DETAILS_DIFFER => Sync_Refusal_Repository::get_message( Sync_Refusal_Repository::CODE_DETAILS_DIFFER ),
			Sync_Refusal_Repository::CODE_ALREADY_IN_USE => Sync_Refusal_Repository::get_message( Sync_Refusal_Repository::CODE_ALREADY_IN_USE ),
		);

		$legacy_msgs_map = array(
			'A sync is already in progress for this podcast.' => __( 'A sync is already in progress for this podcast.', 'seriously-simple-podcasting' ),
			'Failed to connect to SSP API.' => __( 'Failed to connect to SSP API.', 'seriously-simple-podcasting' ),
		);

		$msg = is_array( $response ) && isset( $response['error'] ) ? $response['error'] : '';

		// Try to translate the response code, then preserve known legacy messages.
		if ( isset( $msgs_map[ $response_code ] ) ) {
			$msg = $msgs_map[ $response_code ];
		} elseif ( isset( $legacy_msgs_map[ $msg ] ) ) {
			$msg = $legacy_msgs_map[ $msg ];
		} elseif ( ! empty( $response_code ) ) {
			// Castos error text is inserted with .html() by castos-sync.js.
			$msg = esc_html( $msg );
		}

		// If there is an error but got no error message, add the default one.
		if ( Sync_Status::SYNC_STATUS_FAILED === $status && empty( $msg ) ) {
			$msg = __( 'Could not trigger podcast sync', 'seriously-simple-podcasting' );
		}

		return $msg;
	}

	/**
	 * Get the overall sync status of all synced podcasts.
	 *
	 * @since 3.18.0
	 *
	 * @param string[] $statuses Sync status of each podcast.
	 *
	 * @return string
	 */
	protected function get_overall_sync_status( $statuses ) {
		if ( in_array( Sync_Status::SYNC_STATUS_FAILED, $statuses, true ) ) {
			return in_array( Sync_Status::SYNC_STATUS_SYNCING, $statuses, true )
				? Sync_Status::SYNC_STATUS_SYNCED_WITH_ERRORS
				: Sync_Status::SYNC_STATUS_FAILED;
		}

		return in_array( Sync_Status::SYNC_STATUS_NEEDS_CONFIRMATION, $statuses, true )
			? Sync_Status::SYNC_STATUS_NEEDS_CONFIRMATION
			: Sync_Status::SYNC_STATUS_SYNCING;
	}

	/**
	 * Get the message summarizing the sync of all podcasts.
	 *
	 * We use SYNC_STATUS_ constants for both episode sync statuses and podcast sync statuses. Might be changed in the future.
	 *
	 * @since 3.18.0
	 *
	 * @param string $status Overall sync status.
	 *
	 * @return string
	 */
	protected function get_overall_sync_message( $status ) {
		$msgs = array(
			Sync_Status::SYNC_STATUS_SYNCING            => __(
				'Seriously Simple Podcasting is updating episode data to your Castos account. You can refresh this page to view the updated status in a few minutes.',
				'seriously-simple-podcasting'
			),
			Sync_Status::SYNC_STATUS_SYNCED_WITH_ERRORS => __( 'Started the sync process with errors', 'seriously-simple-podcasting' ),
			Sync_Status::SYNC_STATUS_FAILED             => __( 'Failed to start the sync process', 'seriously-simple-podcasting' ),
			Sync_Status::SYNC_STATUS_NEEDS_CONFIRMATION => __( 'One or more podcasts need your confirmation before syncing.', 'seriously-simple-podcasting' ),
		);

		return isset( $msgs[ $status ] ) ? $msgs[ $status ] : $msgs[ Sync_Status::SYNC_STATUS_SYNCING ];
	}

	/**
	 * Get the current Hosting sync status for each requested podcast.
	 *
	 * A live request bypasses the podcasts transient and refreshes it with the
	 * response from Castos. Non-live requests use the current transient.
	 *
	 * @since 3.18.0
	 *
	 * @return void
	 * @throws \Exception When the sync statuses can not be retrieved.
	 */
	public function get_series_sync_statuses() {
		try {
			$this->nonce_check( 'ss_podcasting_castos-hosting' );
			$this->user_capability_check();

			$status_data = $this->settings_controller->get_series_sync_statuses(
				$this->int_array_from_get( 'series' ),
				$this->bool_from_get( 'live' )
			);
			if ( null === $status_data ) {
				throw new \Exception( __( 'Could not retrieve podcast sync statuses.', 'seriously-simple-podcasting' ) );
			}

			$series_statuses = array();
			foreach ( $status_data['statuses'] as $series_id => $status ) {
				$refusal = isset( $status_data['sync_refusals'][ $series_id ] ) ? $status_data['sync_refusals'][ $series_id ] : null;

				$series_statuses[ $series_id ] = array(
					'status' => $status->status,
					'title'  => $status->title,
					'html'   => Settings_Renderer::instance()->render_sync_status_label( $series_id, $status, $refusal ),
				);
			}

			wp_send_json_success( array( 'series' => $series_statuses ) );
		} catch ( \Exception $e ) {
			$this->send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Reads an array of integers from the request query.
	 *
	 * @since 3.18.0
	 *
	 * @param string $key Query key to read.
	 *
	 * @return int[]
	 */
	protected function int_array_from_get( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- callers verify the nonce via nonce_check(); every value is cast to int below.
		$values = isset( $_GET[ $key ] ) ? (array) wp_unslash( $_GET[ $key ] ) : array();

		return array_values( array_map( 'intval', $values ) );
	}

	/**
	 * Read a boolean flag from the request query.
	 *
	 * @since 3.18.0
	 *
	 * @param string $key Query key to read.
	 *
	 * @return bool
	 */
	protected function bool_from_get( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- callers verify the nonce before reading the request.
		$value = isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';

		return '1' === $value;
	}

	/**
	 * Read the one supported sync confirmation action.
	 *
	 * @since 3.18.0
	 *
	 * @return string|null
	 */
	protected function confirm_action_from_get() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce is checked by sync_castos() before this method runs.
		$action = isset( $_GET['confirm_action'] ) && is_string( $_GET['confirm_action'] )
			? sanitize_text_field( wp_unslash( $_GET['confirm_action'] ) )
			: '';

		return Sync_Refusal_Repository::ACTION_CONNECT === $action ? Sync_Refusal_Repository::ACTION_CONNECT : null;
	}

	/**
	 * Gets the podcast name.
	 *
	 * @param int $podcast_id Podcast ID.
	 *
	 * @return string
	 */
	protected function get_podcast_name( $podcast_id ) {
		// 0 is the default podcast.
		if ( ! $podcast_id ) {
			return __( 'Default Podcast', 'seriously-simple-podcasting' );
		}

		$podcast = ( $podcast_id > 0 ) ? get_term( $podcast_id, ssp_series_taxonomy() ) : null;

		if ( ! is_wp_error( $podcast ) && isset( $podcast->name ) ) {
			return $podcast->name;
		}

		return __( 'Error', 'seriously-simple-podcasting' );
	}

	/**
	 * Disconnects the Castos account.
	 *
	 * @return void
	 */
	public function disconnect_castos() {
		try {
			$this->nonce_check( 'ss_podcasting_castos-hosting' );
			$this->user_capability_check();

			$this->castos_handler->remove_api_credentials();
			$this->admin_notices_handler->add_flash_notice(
				__( 'Castos account successfully disconnected.', 'seriously-simple-podcasting' )
			);
			wp_send_json_success();
		} catch ( \Exception $e ) {
			$this->send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Validate the Seriously Simple Hosting api credentials.
	 *
	 * @throws \Exception When the credentials cannot be validated.
	 */
	public function connect_castos() {
		try {
			$this->nonce_check( 'ss_podcasting_castos-hosting' );
			$this->user_capability_check();

			if ( ! isset( $_GET['api_token'] ) ) {
				throw new \Exception( __( 'Castos arguments not set', 'seriously-simple-podcasting' ) );
			}

			$account_api_token = sanitize_text_field( $_GET['api_token'] );

			$response = $this->castos_handler->connect( $account_api_token );
			if ( ! $response->success ) {
				throw new \Exception( $response->message );
			}

			$this->castos_handler->set_token( $account_api_token );
			$this->admin_notices_handler->remove_constant_notice( Castos_Handler::DISCONNECT_NOTICE_KEY );

			$this->admin_notices_handler->add_flash_notice( $response->message, Admin_Notifications_Handler::SUCCESS );

			wp_send_json(
				array(
					'status'  => $response->status,
					'message' => $response->message,
				)
			);
		} catch ( \Exception $e ) {
			usleep( 500000 ); // Add a 0.5s delay to ensure smoother transitions on the frontend.
			$this->castos_handler->remove_api_credentials();
			$this->send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Update the episode embed code via ajax
	 *
	 * @return void
	 * @throws \Exception When the embed parameters are invalid or the embed cannot be generated.
	 */
	public function update_episode_embed_code() {
		try {
			$this->nonce_check( 'update_episode_embed_code' );
			$this->user_capability_check();

			if ( empty( $_POST['post_id'] ) || ! isset( $_POST['width'], $_POST['height'] ) ) {
				throw new \Exception( 'Missing embed parameters' );
			}

			$post_id = absint( $_POST['post_id'] );
			$width   = absint( $_POST['width'] );
			$height  = absint( $_POST['height'] );

			if ( $post_id < 1 || $width < 1 || $height < 1 ) {
				throw new \Exception( 'Invalid embed parameters' );
			}

			$html = get_post_embed_html( $width, $height, $post_id );
			if ( false === $html ) {
				throw new \Exception( 'Could not generate embed code.' );
			}

			wp_send_json_success( $html );
		} catch ( \Exception $e ) {
			$this->send_json_error( $e->getMessage() );
		}
	}


	/**
	 * Import an external RSS feed via ajax
	 */
	public function import_external_rss_feed() {
		$this->import_security_check();

		$ssp_external_rss = get_option( 'ssp_external_rss', '' );
		if ( empty( $ssp_external_rss ) ) {
			wp_send_json(
				array(
					'status'        => 'error',
					'message'       => __( 'No feed to process', 'seriously-simple-podcasting' ),
					'can_try_again' => false,
				)
			);
		}

		$rss_importer = new RSS_Import_Handler( $ssp_external_rss, $this->castos_handler );
		$response     = $rss_importer->import_rss_feed();

		wp_send_json( $response );
	}

	/**
	 * Describes the feed behind the URL the user entered in the onboarding wizard,
	 * so they can confirm it before anything is imported.
	 *
	 * @since 3.18.0
	 */
	public function preview_rss_feed() {
		$this->import_security_check();

		$handler = new Onboarding_Import_Handler();

		$this->send_feed_result( $handler->preview( $this->get_requested_feed_url() ) );
	}

	/**
	 * Resolves the target podcast and queues the confirmed feed for import.
	 *
	 * @since 3.18.0
	 */
	public function start_onboarding_import() {
		$this->import_security_check();

		$handler = new Onboarding_Import_Handler();

		$this->send_feed_result( $handler->start( $this->get_requested_feed_url() ) );
	}

	/**
	 * Returns the feed URL submitted with an onboarding import request.
	 *
	 * @since 3.18.0
	 *
	 * @return string
	 */
	protected function get_requested_feed_url() {
		// Nonce is verified by import_security_check() before this runs.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$feed_url = isset( $_REQUEST['feed_url'] ) && is_string( $_REQUEST['feed_url'] )
			? esc_url_raw( wp_unslash( $_REQUEST['feed_url'] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $feed_url;
	}

	/**
	 * Sends a feed summary, or the reason the feed cannot be imported, as JSON.
	 *
	 * @since 3.18.0
	 *
	 * @param array|\WP_Error $result Result from the onboarding import handler.
	 */
	protected function send_feed_result( $result ) {
		if ( is_wp_error( $result ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
				)
			);

			return;
		}

		wp_send_json( array_merge( array( 'status' => 'success' ), $result ) );
	}

	/**
	 * Get the progress of an external RSS feed import
	 */
	public function get_external_rss_feed_progress() {
		$this->import_security_check();
		$progress = RSS_Import_Handler::get_import_data( 'import_progress', 0 );
		$episodes = RSS_Import_Handler::get_import_data( 'episodes_imported', array() );
		wp_send_json( compact( 'progress', 'episodes' ) );
	}

	/**
	 * Reset external RSS feed import
	 */
	public function reset_rss_feed_data() {
		$this->import_security_check();

		RSS_Import_Handler::reset_import_data();
		wp_send_json( 'success' );
	}

	/**
	 * RSS feed import functions security check
	 */
	protected function import_security_check() {
		try {
			$this->user_capability_check();
			$this->nonce_check( 'ss_podcasting_import' );
		} catch ( \Exception $e ) {
			$this->send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Throws exception if nonce is not valid.
	 *
	 * @param string $action    Nonce action.
	 * @param string $nonce_key Request key containing the nonce.
	 *
	 * @throws \Exception When the nonce is invalid.
	 */
	protected function nonce_check( $action, $nonce_key = 'nonce' ) {
		$nonce = isset( $_REQUEST[ $nonce_key ] ) ? $_REQUEST[ $nonce_key ] : '';
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			throw new \Exception( 'Security error!' );
		}
	}

	/**
	 * Throws exception if user cannot manage podcast.
	 *
	 * @throws \Exception When the current user lacks the required capability.
	 */
	protected function user_capability_check() {
		if ( ! current_user_can( 'manage_podcast' ) ) {
			throw new \Exception( 'Current user doesn\'t have correct permissions' );
		}
	}

	/**
	 * Sends a JSON error response.
	 *
	 * @param string $message Error message.
	 */
	protected function send_json_error( $message ) {
		wp_send_json(
			array(
				'status'  => 'error',
				'message' => $message,
			)
		);
	}
}
