<?php
/**
 * Castos_Response Entity.
 *
 * @package SeriouslySimplePodcasting
 * */

namespace SeriouslySimplePodcasting\Entities;

/**
 * Class Castos_Response. Is used instead of response array in Castos_Handler class.
 *
 * @since 3.5.0
 */
class Castos_Response extends Abstract_API_Entity {

	/**
	 * Error message.
	 *
	 * @var string
	 */
	public $message = 'An unknown error occurred. Please try again later.';

	/**
	 * Response status.
	 *
	 * @var string
	 */
	public $status = 'error';

	/**
	 * Response body.
	 *
	 * @var array
	 */
	protected $body;

	/**
	 * Updates the response with raw data.
	 *
	 * @param array $raw_response Raw response data.
	 * @return void
	 */
	public function update( $raw_response ) {
		if ( ! is_array( $raw_response ) || ! isset( $raw_response['response']['code'] ) ) {
			return;
		}
		$this->code    = wp_remote_retrieve_response_code( $raw_response );
		$this->body    = json_decode( wp_remote_retrieve_body( $raw_response ), true );
		$this->message = isset( $this->body['message'] ) ? $this->translate( $this->body['message'] ) : '';
		$this->success = 200 === $this->code ? true : $this->success;
		$this->status  = 200 === $this->code ? 'success' : $this->status;
	}

	/**
	 * Translates response messages.
	 *
	 * @param string $text Text to translate.
	 * @return string Translated text.
	 * @throws \Exception When text is not translatable.
	 */
	public function translate( $text ) {
		try {
			$translations = array(
				wp_hash( 'Authentication failed! Invalid or missing Access Token!' ) => __( 'Authentication failed! Invalid or missing Access Token!', 'seriously-simple-podcasting' ),
				wp_hash( 'Seriously Simple Podcasting has successfully connected to your Castos account.' ) => __( 'Seriously Simple Podcasting has successfully connected to your Castos account.', 'seriously-simple-podcasting' ),
			);
			$msg_key      = wp_hash( $text );
			if ( ! array_key_exists( $msg_key, $translations ) ) {
				throw new \Exception( 'Text is not translatable' );
			}

			return $translations[ $msg_key ];
		} catch ( \Exception $e ) {
			return $text;
		}
	}
}
