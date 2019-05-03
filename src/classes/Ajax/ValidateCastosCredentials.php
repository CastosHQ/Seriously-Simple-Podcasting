<?php

namespace SeriouslySimplePodcasting\Ajax;

use SeriouslySimplePodcasting\Handlers\CastosHandler;

class ValidateCastosCredentials {

	public function __construct() {
		// Add ajax action for plugin rating.
		add_action( 'wp_ajax_validate_castos_credentials', array( $this, 'validate_podmotor_api_credentials' ) );
	}

	/**
	 * Validate the Seriously Simple Hosting api credentials
	 */
	public function validate_podmotor_api_credentials() {
		// @todo add nonces

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => 'Current user doesn\t have correct permissions',
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

		$castos_handler = new CastosHandler();
		$response       = $castos_handler->validate_api_credentials( $account_api_token, $account_email );
		wp_send_json( $response );
	}

}
