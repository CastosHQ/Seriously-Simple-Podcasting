<?php

namespace SeriouslySimplePodcasting\Ajax;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\RSS_Import_Handler;
use SeriouslySimplePodcasting\Handlers\Options_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;
use WpOrg\Requests\Exception;

class Ajax_Handler {

	protected $logger;

	/**
	 * Ajax_Handler constructor.
	 *
	 * @param Log_Helper $logger
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;

		$this->bootstrap();
	}

	/**
	 * Runs any functionality to be included in the object instantiation
	 */
	public function bootstrap() {
		// Add ajax action for plugin rating
		add_action( 'wp_ajax_ssp_rated', array( $this, 'rated' ) );

		// Insert a new subscribe option to the ss_podcasting_subscribe_options array.
		add_action( 'wp_ajax_insert_new_subscribe_option', array( $this, 'insert_new_subscribe_option' ) );

		// Deletes a subscribe option from the ss_podcasting_subscribe_options array.
		add_action( 'wp_ajax_delete_subscribe_option', array( $this, 'delete_subscribe_option' ) );

		// Add ajax action for plugin rating.
		add_action( 'wp_ajax_validate_castos_credentials', array( $this, 'validate_podmotor_api_credentials' ) );

		// Add ajax action for uploading file data to Castos that has been uploaded already via plupload
		add_action( 'wp_ajax_ssp_store_podmotor_file', array( $this, 'store_castos_file' ) );

		// Add ajax action for customising episode embed code
		add_action( 'wp_ajax_update_episode_embed_code', array( $this, 'update_episode_embed_code' ) );

		// Add ajax action for importing external rss feed
		add_action( 'wp_ajax_import_external_rss_feed', array( $this, 'import_external_rss_feed' ) );

		// Add ajax action for getting external rss feed progress
		add_action( 'wp_ajax_get_external_rss_feed_progress', array( $this, 'get_external_rss_feed_progress' ) );

		// Add ajax action to reset external feed options
		add_action( 'wp_ajax_reset_rss_feed_data', array( $this, 'reset_rss_feed_data' ) );

		// Add ajax action for importing Castos podcasts
		add_action( 'wp_ajax_import_castos_podcast', array( $this, 'import_castos_podcast' ) );
	}

	public function import_castos_podcast() {
		try {
			$podcast_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
			if ( ! check_ajax_referer( 'import_castos_podcast_' . $podcast_id, false, false ) ) {
				throw new \Exception( 'Bad request' );
			}

			$castos_handler = ssp_app()->get_castos_handler();
			$podcasts = $castos_handler->get_podcasts();
			$episodes = $castos_handler->get_podcast_episodes( $podcast_id );
			if ( empty( $podcasts['data']['podcast_list'] ) || ! isset( $episodes['data'] ) ) {
				throw new \Exception( 'Could not retrieve podcast data' );
			}

			foreach ( $podcasts['data']['podcast_list'] as $podcast ) {
				if ( $podcast_id === $podcast['id'] ) {
					$podcast_title = $podcast['podcast_title'];
					break;
				}
			}

			if ( empty( $podcast_title ) ) {
				throw new \Exception( 'Could not import the podcast.' );
			}

			// Creates term if it doesn't exist, otherwise just gets its data.
			$term_data = wp_create_term( $podcast_title, ssp_series_taxonomy() );

			if ( isset( $term_data['term_id'] ) && is_numeric( $term_data['term_id'] ) ) {
				$series_id = intval( $term_data['term_id'] );
			} else {
				throw new \Exception( 'Could not create the podcast, please try again later.' );
			}

			foreach ( $episodes['data'] as $episode ) {
				$args = array(
					'post_title'    => wp_strip_all_tags( $episode['post_title'] ),
//					'post_content'  => $_POST['post_content'], //Todo: Ask for this info
					'post_status'   => 'publish',
					'post_type'     => SSP_CPT_PODCAST,
					'post_category' => array( $podcast_id ),
					'tax_input' => array(
						ssp_series_taxonomy() => array( $series_id ),
					),
				);

				wp_insert_post( $args );
			}

			$res = $castos_handler->update_podcast( $podcast_id, array( 'series_id' => $series_id ) );

			if ( empty( $res['success'] ) ) {
				throw new \Exception( 'Could not fully sync the podcast' );
			}

			delete_transient( $castos_handler::TRANSIENT_PODCASTS );
			wp_send_json_success( array( 'btn' => esc_attr__( 'Imported', 'seriously-simple-podcasting' ) ) );
		} catch ( Exception $e ) {
			$this->logger->log( __METHOD__ . ': ' . $e->getMessage() );
			wp_send_json_error(
				array(
					'btn' => esc_attr__( 'Failed', 'seriously-simple-podcasting' ),
					'msg' => $e->getMessage(),
				)
			);
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
	 * Insert a new subscribe field option
	 */
	public function insert_new_subscribe_option() {
		check_ajax_referer( 'ssp_ajax_options_nonce' );
		if ( ! current_user_can( 'manage_podcast' ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => 'Current user doesn\'t have correct permissions',
				)
			);
			return;
		}
		$options_handler   = new Options_Handler();
		$subscribe_options = $options_handler->insert_subscribe_option();
		wp_send_json( $subscribe_options );
	}

	public function delete_subscribe_option() {
		check_ajax_referer( 'ssp_ajax_options_nonce' );
		if ( ! current_user_can( 'manage_podcast' ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => 'Current user doesn\'t have correct permissions',
				)
			);

			return;
		}
		if ( ! isset( $_POST['option'] ) || ! isset( $_POST['count'] ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => 'POSTed option or count not set',
				)
			);

			return;
		}
		$options_handler   = new Options_Handler();
		$subscribe_options = $options_handler->delete_subscribe_option( sanitize_text_field( $_POST['option'] ), sanitize_text_field( $_POST['count'] ) );
		wp_send_json( $subscribe_options );
	}

	/**
	 * Validate the Seriously Simple Hosting api credentials
	 */
	public function validate_podmotor_api_credentials() {
		try {
			$this->nonce_check('ss_podcasting_castos-hosting');
			$this->user_capability_check();

			if ( ! isset( $_GET['api_token'] ) || ! isset( $_GET['email'] ) ) {
				throw new \Exception( 'Castos arguments not set' );
			}

			$account_api_token = sanitize_text_field( $_GET['api_token'] );
			$account_email     = sanitize_text_field( $_GET['email'] );

			$castos_handler = new Castos_Handler();
			$response       = $castos_handler->validate_api_credentials( $account_api_token, $account_email );
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
