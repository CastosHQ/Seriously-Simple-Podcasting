<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Entities\Sync_Status;

class Ajax_Handler {

	/**
	 * @var Castos_Handler $castos_handler
	 *
	 * */
	protected $castos_handler;

	/**
	 * @var Admin_Notifications_Handler $admin_notices_handler
	 * */
	protected $admin_notices_handler;

	/**
	 * Ajax_Handler constructor.
	 *
	 * @param Castos_Handler $castos_handler
	 * @param Admin_Notifications_Handler $admin_notices_handler
	 */
	public function __construct( $castos_handler, $admin_notices_handler ) {
		$this->castos_handler = $castos_handler;
		$this->admin_notices_handler = $admin_notices_handler;

		$this->bootstrap();
	}

	/**
	 * Runs any functionality to be included in the object instantiation
	 */
	public function bootstrap() {
		// Add ajax action for plugin rating
		add_action( 'wp_ajax_ssp_rated', array( $this, 'rated' ) );

		add_action( 'wp_ajax_connect_castos', array( $this, 'connect_castos' ) );

		add_action( 'wp_ajax_disconnect_castos', array( $this, 'disconnect_castos' ) );

		// Add ajax action for customising episode embed code
		add_action( 'wp_ajax_update_episode_embed_code', array( $this, 'update_episode_embed_code' ) );

		// Add ajax action for importing external rss feed
		add_action( 'wp_ajax_import_external_rss_feed', array( $this, 'import_external_rss_feed' ) );

		// Add ajax action for getting external rss feed progress
		add_action( 'wp_ajax_get_external_rss_feed_progress', array( $this, 'get_external_rss_feed_progress' ) );

		// Add ajax action to reset external feed options
		add_action( 'wp_ajax_reset_rss_feed_data', array( $this, 'reset_rss_feed_data' ) );

		// Add ajax action to the Castos sync process
		add_action( 'wp_ajax_sync_castos', array( $this, 'sync_castos' ) );

		// Ajax action to removing the constant notice
		add_action( 'wp_ajax_remove_constant_notice', array( $this, 'remove_constant_notice' ) );
	}

	/**
	 * Removes constant notice
	 *
	 * @return void
	 */
	public function remove_constant_notice() {
		try {
			$id = $_POST['id'];

			if ( empty( $_POST['nonce'] ) && ! wp_verify_nonce( 'nonce-' . $_POST['id'] ) ) {
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
	 * @return void
	 */
	public function rated() {
		if ( wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ), 'ssp_rated' ) && current_user_can( 'manage_podcast' ) ) {
			update_option( 'ssp_admin_footer_text_rated', 1 );
		}
		die();
	}

	/**
	 * Sync podcasts with Castos
	 */
	public function sync_castos() {
		try {
			$this->nonce_check('ss_podcasting_castos-hosting');
			$this->user_capability_check();

			$podcast_ids = filter_input( INPUT_GET, 'podcasts', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );

			// Provide possible errors for translation purposes.
			$msgs_map = array(
				'Failed to connect to SSP API.'                   => __( 'Failed to connect to SSP API.', 'seriously-simple-podcasting' ),
				'A sync is already in progress for this podcast.' => __( 'A sync is already in progress for this podcast.', 'seriously-simple-podcasting' ),
			);

			$podcast_statuses = array();

			$has_syncing = false;
			$has_errors = false;

			foreach ( $podcast_ids as $podcast_id ) {

				$podcast_status = array();

				$response = $this->castos_handler->trigger_podcast_sync( $podcast_id );

				if ( isset( $response['code'] ) && in_array( $response['code'], array( 200, 409 ) ) ) {
					$podcast_status['status'] = Sync_Status::SYNC_STATUS_SYNCING;
					$podcast_status['title']  = __( 'Syncing', 'seriously-simple-podcasting' );
					$has_syncing              = true;
				} else {
					$podcast_status['status'] = Sync_Status::SYNC_STATUS_FAILED;
					$podcast_status['title']  = __( 'Failed', 'seriously-simple-podcasting' );
					$has_errors               = true;
				}

				do_action( 'ssp_triggered_podcast_sync', $podcast_id, $response, $podcast_status['status'] );

				$msg = isset( $response['error'] ) ? $response['error'] : '';

				// Try to translate the response message.
				$msg = ( $msg && array_key_exists( $msg, $msgs_map ) ) ? $msgs_map[ $msg ] : $msg;

				// If there is an error but got no error message, add the default one.
				if ( Sync_Status::SYNC_STATUS_FAILED === $podcast_status['status'] && empty( $msg ) ) {
					$msg = __( 'Could not trigger podcast sync', 'seriously-simple-podcasting' );
				}

				$msg_template = _x( '%s: %s', 'podcast-sync-error-message', 'seriously-simple-podcasting' );

				$podcast_status['msg'] = $msg ? sprintf( $msg_template, $this->get_podcast_name( $podcast_id ), $msg ) : '';

				$podcast_statuses [ $podcast_id ] = $podcast_status;
			}

			// We use SYNC_STATUS_ constants for both episode sync statuses and podcast sync statuses. Might be changed in the future.
			$msgs = array(
				Sync_Status::SYNC_STATUS_SYNCING             => __(
					'Seriously Simple Podcasting is updating episode data to your Castos account. You can refresh this page to view the updated status in a few minutes.',
					'seriously-simple-podcasting'
				),
				Sync_Status::SYNC_STATUS_SYNCED_WITH_ERRORS => __( 'Started the sync process with errors', 'seriously-simple-podcasting' ),
				Sync_Status::SYNC_STATUS_FAILED             => __( 'Failed to start the sync process', 'seriously-simple-podcasting' ),
			);

			$results_status = ! $has_errors ?
				Sync_Status::SYNC_STATUS_SYNCING :
				( $has_syncing ? Sync_Status::SYNC_STATUS_SYNCED_WITH_ERRORS : Sync_Status::SYNC_STATUS_FAILED );

			$results = array(
				'status'   => $results_status,
				'msg'      => $msgs[ $results_status ],
				'podcasts' => $podcast_statuses
			);

			if ( Sync_Status::SYNC_STATUS_SYNCING === $results['status'] ) {
				wp_send_json_success( $results );
			} else {
				wp_send_json_error( $results );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * @param int $podcast_id
	 *
	 * @return string
	 */
	protected function get_podcast_name( $podcast_id ) {
		// 0 is the default podcast.
		if ( ! $podcast_id ) {
			return __( 'Default Podcast', 'seriously-simple-podcasting' );
		}

		$podcast = ( $podcast_id > 0 ) ? get_term( $podcast_id, 'series' ) : null;

		if ( ! is_wp_error( $podcast ) && isset( $podcast->name ) ) {
			return $podcast->name;
		}

		return __( 'Error', 'seriously-simple-podcasting' );
	}

	public function disconnect_castos() {
		$this->castos_handler->remove_api_credentials();
		$this->admin_notices_handler->add_flash_notice(
			__( 'Castos account successfully disconnected.', 'seriously-simple-podcasting' )
		);
		wp_send_json_success();
	}

	/**
	 * Validate the Seriously Simple Hosting api credentials
	 */
	public function connect_castos() {
		try {
			$this->nonce_check('ss_podcasting_castos-hosting');
			$this->user_capability_check();

			if ( ! isset( $_GET['api_token'] ) ) {
				throw new \Exception( __('Castos arguments not set', 'seriously-simple-podcasting') );
			}

			$account_api_token = sanitize_text_field( $_GET['api_token'] );

			$response = $this->castos_handler->connect( $account_api_token );
			if ( ! $response->success ) {
				throw new \Exception( $response->message );
			}

			$this->castos_handler->set_token( $account_api_token );

			$this->admin_notices_handler->add_flash_notice( $response->message, Admin_Notifications_Handler::SUCCESS );

			wp_send_json( [
				'status'  => $response->status,
				'message' => $response->message,
			] );
		} catch ( \Exception $e ) {
			usleep( 500000 ); // Add a 0.5s delay to ensure smoother transitions on the frontend.
			$this->castos_handler->remove_api_credentials();
			$this->send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Update the episode embed code via ajax
	 * @return void
	 */
	public function update_episode_embed_code() {
		// @todo Investigate if this function is used
		// Make sure we have a valid post ID
		if ( empty( $_POST['post_id'] || ! current_user_can( 'manage_podcast' ) ) ) {
			return;
		}

		// Get info for embed code
		$post_id = (int) $_POST['post_id'];
		$width   = (int) $_POST['width'];
		$height  = (int) $_POST['height'];

		// Generate embed code
		echo get_post_embed_html( $width, $height, $post_id );

		// Exit after ajax request
		exit;
	}


	/**
	 * Import an external RSS feed via ajax
	 */
	public function import_external_rss_feed() {
		$this->import_security_check();

		$ssp_external_rss = get_option( 'ssp_external_rss', '' );
		if ( empty( $ssp_external_rss ) ) {
			wp_send_json(
				[
					'status'        => 'error',
					'message'       => __( 'No feed to process', 'seriously-simple-podcasting' ),
					'can_try_again' => false,
				]
			);
		}

		$rss_importer = new RSS_Import_Handler( $ssp_external_rss );
		$response     = $rss_importer->import_rss_feed();

		wp_send_json( $response );
	}

	/**
	 * Get the progress of an external RSS feed import
	 */
	public function get_external_rss_feed_progress() {
		$this->import_security_check();
		$progress = RSS_Import_Handler::get_import_data( 'import_progress', 0 );
		$episodes = RSS_Import_Handler::get_import_data( 'episodes_imported', array() );
		wp_send_json( compact('progress', 'episodes') );
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
	 * Throws exception if nonce is not valid
	 *
	 * @param string $action
	 * @param string $nonce_key
	 *
	 * @throws \Exception
	 */
	protected function nonce_check( $action, $nonce_key = 'nonce' ) {
		if ( ! wp_verify_nonce( $_REQUEST[ $nonce_key ], $action ) ) {
			throw new \Exception( 'Security error!' );
		}
	}

	/**
	 * Throws exception if user cannot manage podcast
	 *
	 * @throws \Exception
	 */
	protected function user_capability_check() {
		if ( ! current_user_can( 'manage_podcast' ) ) {
			throw new \Exception( 'Current user doesn\'t have correct permissions' );
		}
	}

	/**
	 * @param string $message
	 */
	protected function send_json_error( $message ) {
		wp_send_json(
			[
				'status'  => 'error',
				'message' => $message,
			]
		);
	}

}
