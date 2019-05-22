<?php

namespace SeriouslySimplePodcasting\Ajax;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Importers\Rss_Importer;
use SeriouslySimplePodcasting\Handlers\Options_Handler;

class Ajax_Handler {

	/**
	 * Ajax_Handler constructor.
	 */
	public function __construct() {
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
		add_action( 'wp_ajax_reset_external_rss_feed_progress', array( $this, 'reset_external_rss_feed_progress' ) );
	}

	/**
	 * Indicate that plugin has been rated
	 * @return void
	 */
	public function rated() {
		update_option( 'ssp_admin_footer_text_rated', 1 );
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
		// @todo add nonces
		if ( ! current_user_can( 'manage_podcast' ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => 'Current user doesn\'t have correct permissions',
				)
			);
		}

		if ( ! isset( $_GET['api_token'] ) || ! isset( $_GET['email'] ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => 'Castos arguments not set',
				)
			);
		}

		$account_api_token = ( sanitize_text_field( $_GET['api_token'] ) );
		$account_email     = ( sanitize_text_field( $_GET['email'] ) );

		$castos_handler = new Castos_Handler();
		$response       = $castos_handler->validate_api_credentials( $account_api_token, $account_email );
		wp_send_json( $response );
	}

	/**
	 * Store the file uploaded via plupload to the Castos account
	 */
	public function store_castos_file() {
		// @todo add nonces
		if ( ! isset( $_GET['podmotor_file_path'] ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => 'An error occurred storing your file to your Castos account, please contact hello@castos.com for assistance.',
				)
			);
		}

		$podmotor_file_path = filter_var( $_GET['podmotor_file_path'], FILTER_SANITIZE_STRING );

		$response = array(
			'status'  => 'error',
			'message' => 'Error storing file to offsite storage account',
		);

		try {
			$castos_handler  = new Castos_Handler();
			$castos_response = $castos_handler->upload_podmotor_storage_file_data_to_podmotor( $podmotor_file_path );
		} catch ( Exception $e ) {
			$response['status']  = 'error';
			$response['message'] = 'An unknown error occurred: ' . $e->getMessage();
			wp_send_json( $response );
		}

		if ( $castos_response ) {
			$response = $castos_response;
		}
		wp_send_json( $response );
	}

	/**
	 * Update the epiaode embed code via ajax
	 * @return void
	 */
	public function update_episode_embed_code() {
		// @todo add nonces
		// Make sure we have a valid post ID
		if ( empty( $_POST['post_id'] ) ) {
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
		// @todo add nonces, add user caps check, validate inputs

		update_option( 'ssp_rss_import', 0 );

		$ssp_external_rss = get_option( 'ssp_external_rss', '' );
		if ( empty( $ssp_external_rss ) ) {
			$response = array(
				'status'  => 'error',
				'message' => 'No feed to process',
			);
			wp_send_json( $response );

			return;
		}

		$rss_importer = new Rss_Importer( $ssp_external_rss );
		$response     = $rss_importer->import_rss_feed();

		wp_send_json( $response );
	}

	/**
	 * Get the progress of an external RSS feed import
	 */
	public function get_external_rss_feed_progress() {
		// @todo add nonces, add user caps check
		$progress = (int) get_option( 'ssp_rss_import', 0 );
		wp_send_json( $progress );
	}

	/**
	 * Reset external RSS feed import
	 */
	public function reset_external_rss_feed_progress() {
		// @todo add nonces, add user caps check
		delete_option( 'ssp_external_rss' );
		delete_option( 'ssp_rss_import' );
		wp_send_json( 'success' );
	}

}
