<?php

namespace SeriouslySimplePodcasting\Handlers;

class Ajax_Handler {

	/**
	 * @var Castos_Handler $castos_handler
	 *
	 * */
	protected $castos_handler;

	/**
	 * Ajax_Handler constructor.
	 *
	 * @param Castos_Handler $castos_handler
	 */
	public function __construct( $castos_handler ) {
		$this->castos_handler = $castos_handler;

		$this->bootstrap();
	}

	/**
	 * Runs any functionality to be included in the object instantiation
	 */
	public function bootstrap() {
		// Add ajax action for plugin rating
		add_action( 'wp_ajax_ssp_rated', array( $this, 'rated' ) );

		// Add ajax action for plugin rating.
		add_action( 'wp_ajax_validate_castos_credentials', array( $this, 'validate_podmotor_api_credentials' ) );

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

			$results = array();

			$has_syncing = false;
			$has_errors = false;

			foreach ( $podcast_ids as $podcast_id ) {
				$response = $this->castos_handler->trigger_podcast_sync( $podcast_id );

				$status   = array();

				if ( empty( $response['code'] ) || ! in_array( $response['code'], array( 200 ) ) ) {
					$error = isset( $response['error'] ) ? $response['error'] : '';

					// Try to add translation
					$error = ( $error && array_key_exists( $error, $msgs_map ) ) ? $msgs_map[ $error ] : $error;

					// If there is no error message, add the default one.
					$error = $error ?: __( 'Could not trigger podcast sync', 'seriously-simple-podcasting' );

					$status = array(
						'msg'    => $error,
					);
				}

				if ( 409 === $response['code'] || 200 === $response['code'] ) {
					$status['status'] = 'syncing';
					$status['title']  = __( 'Syncing', 'seriously-simple-podcasting' );
					$has_syncing = true;
				} else {
					$status['status'] = 'failed';
					$status['title']  = __( 'Failed', 'seriously-simple-podcasting' );
					$has_errors = true;
				}

				$results [ $podcast_id ] = $status;
			}

			$msgs = array(
				'success'             => __( 'Successfully started the sync process', 'seriously-simple-podcasting' ),
				'success_with_errors' => __( 'Started the sync process with errors', 'seriously-simple-podcasting' ),
				'failed'              => __( 'Failed to start the sync process', 'seriously-simple-podcasting' ),
			);

			$results['status'] = ! $has_errors ? 'success' : ( $has_syncing ? 'success_with_errors' : 'failed' );
			$results['msg'] = $msgs[ $results['status'] ];

			if( 'failed' === $results['status'] ){
				wp_send_json_error( $results );
			} else {
				wp_send_json_success( $results );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Validate the Seriously Simple Hosting api credentials
	 */
	public function validate_podmotor_api_credentials() {
		try {
			$this->nonce_check('ss_podcasting_castos-hosting');
			$this->user_capability_check();

			if ( ! isset( $_GET['api_token'] ) || ! isset( $_GET['email'] ) ) {
				throw new \Exception( __('Castos arguments not set', 'seriously-simple-podcasting') );
			}

			$account_api_token = sanitize_text_field( $_GET['api_token'] );
			$account_email     = sanitize_text_field( $_GET['email'] );

			$response       = $this->castos_handler->validate_api_credentials( $account_api_token, $account_email );
			wp_send_json( $response );
		} catch ( \Exception $e ) {
			$this->send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Update the epiaode embed code via ajax
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
