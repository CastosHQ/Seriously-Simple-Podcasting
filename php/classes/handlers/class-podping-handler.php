<?php
/**
 * Podping Handler
 *
 * @package SeriouslySimplePodcasting
 * @category Handlers
 * @author Castos
 */

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Helpers\Log_Helper;
use SeriouslySimplePodcasting\Interfaces\Service;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Podping_Handler
 *
 * Handles notifications to Podping service when podcast feeds are updated.
 *
 * @package SeriouslySimplePodcasting
 */
class Podping_Handler implements Service {

	/**
	 * Base token for Podping authentication.
	 *
	 * @var string
	 */
	const BASE_TOKEN = 'fUak8QYUE67cT8gM5DxHhQ';

	/**
	 * Option name for storing the Podping token.
	 *
	 * @var string
	 */
	const TOKEN_NAME = 'podping_token';

	/**
	 * Podping service URL.
	 *
	 * @var string
	 */
	const PODPING_URL = 'https://podping.cloud';

	/**
	 * Logger instance.
	 *
	 * @var Log_Helper
	 */
	protected $logger;

	/**
	 * Podping_Handler constructor.
	 *
	 * @param Log_Helper $logger Logger instance.
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Notifies Podping service about a feed update.
	 *
	 * @param string $feed_url The URL of the podcast feed.
	 *
	 * @return bool True if notification was successful, false otherwise.
	 */
	public function notify( $feed_url ) {
		$this->logger->log( __METHOD__ . sprintf( ': Notify Podping. Feed URL: %s', $feed_url ) );

		$feed_url = filter_var( $feed_url, FILTER_VALIDATE_URL );

		if ( ! $feed_url ) {
			$this->logger->log( __METHOD__ . ': Error! Feed URL is not valid!' );

			return false;
		}

		// Now let's check if the feed URL is not protected.
		$result = wp_remote_get( $feed_url );

		if ( is_wp_error( $result ) ) {
			$this->logger->log( __METHOD__ . sprintf( ': Could not check the feed URL! Error: %s', $result->get_error_message() ) );
		}

		if ( is_array( $result ) && isset( $result['response']['code'] ) && 401 === $result['response']['code'] ) {
			$this->logger->log( __METHOD__ . sprintf( ': The feed %s is protected, skipped it from ping!', $feed_url ) );

			return false;
		}

		$options = array(
			'headers' => array(
				'Authorization' => $this->get_token(),
				'User-Agent'    => 'SeriouslySimple',
			),
			'timeout' => 60,
		);

		$api_url = add_query_arg( array( 'url' => $feed_url ), self::PODPING_URL );

		$app_response = wp_remote_get( $api_url, $options );

		if ( is_wp_error( $app_response ) ) {
			$this->logger->log( 'An unknown error occurred sending notification to Podping: ' . $app_response->get_error_message() );

			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $app_response );
		$response_body = wp_remote_retrieve_body( $app_response );

		$this->logger->log( __METHOD__ . sprintf( ': Response: %s, response code: %s', $response_body, $response_code ) );

		return 200 == $response_code;
	}

	/**
	 * Gets the Podping authentication token.
	 *
	 * @return string The authentication token.
	 */
	protected function get_token() {
		$token = get_option( self::TOKEN_NAME );
		if ( $token ) {
			return $token;
		}

		$token = $this->generate_token();

		update_option( self::TOKEN_NAME, $token, false );

		return $token;
	}

	/**
	 * Generates a new Podping authentication token.
	 *
	 * @return string The generated authentication token.
	 */
	protected function generate_token() {
		return self::BASE_TOKEN . wp_generate_password( 22, false );
	}
}
