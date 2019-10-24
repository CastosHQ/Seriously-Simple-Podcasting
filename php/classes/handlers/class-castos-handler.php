<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Helpers\Log_Helper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Castos_Handler {

	/**
	 * Array of config settings
	 *
	 * @var array
	 */
	private $podmotor_config = array();

	/**
	 * S3 bucket
	 *
	 * @var string
	 */
	private $podmotor_bucket = '';

	/**
	 * User's show slug
	 *
	 * @var string
	 */
	private $podmotor_show_slug = '';

	/**
	 * Response array
	 *
	 * @var array
	 */
	public $response = array();

	/**
	 * @var Log_Helper
	 */
	public $logger;

	/**
	 * Castos_Handler constructor.
	 */
	public function __construct() {
		$this->logger        = new Log_Helper();
		$podmotor_account_id = get_option( 'ss_podcasting_podmotor_account_id', '' );
		if ( ! empty( $podmotor_account_id ) ) {
			$this->init_podmotor_handler();
		}
	}

	/**
	 * Sets up the Castos_Handler
	 */
	private function init_podmotor_handler() {
		$podmotor_account_id      = get_option( 'ss_podcasting_podmotor_account_id', '' );
		$podmotor_account_email   = get_option( 'ss_podcasting_podmotor_account_email', '' );
		$response                 = $this->get_podmotor_bucket_credentials( $podmotor_account_id, $podmotor_account_email );
		$this->podmotor_config    = $response['config'];
		$this->podmotor_bucket    = $response['bucket'];
		$this->podmotor_show_slug = $response['show_slug'];
	}

	/**
	 * Sets up the response array
	 */
	private function setup_response() {
		$this->response = array(
			'status'  => 'error',
			'message' => 'An error occurred.',
		);
	}

	/**
	 * Updates the response array
	 *
	 * @param $key
	 * @param $value
	 */
	private function update_response( $key, $value ) {
		$this->response[ $key ] = $value;
	}

	/**
	 * Get the Castos_Handler credentials from the Castos API
	 *
	 * @param $podmotor_account_id
	 * @param $podmotor_account_email
	 *
	 * @return array
	 */
	public function get_podmotor_bucket_credentials( $podmotor_account_id, $podmotor_account_email ) {

		$podmotor_array = ssp_podmotor_decrypt_config( $podmotor_account_id, $podmotor_account_email );

		$config = array(
			'version'     => $podmotor_array['version'],
			'region'      => $podmotor_array['region'],
			'credentials' => array(
				'key'    => $podmotor_array['credentials_key'],
				'secret' => $podmotor_array['credentials_secret'],
			),
		);

		$response = array(
			'config'    => $config,
			'bucket'    => $podmotor_array['bucket'],
			'show_slug' => $podmotor_array['show_slug'],
		);

		return $response;
	}

	/**
	 * Connect to Castos API and validate API credentials
	 *
	 * @param string $podmotor_account_api_token
	 * @param string $podmotor_account_email
	 *
	 * @return array
	 */
	public function validate_api_credentials( $podmotor_account_api_token = '', $podmotor_account_email = '' ) {

		$this->setup_response();

		if ( empty( $podmotor_account_api_token ) || empty( $podmotor_account_email ) ) {
			$this->update_response( 'message', 'Invalid API Token or email.' );
		}

		$api_url = SSP_CASTOS_APP_URL . 'api/users/validate';

		$this->logger->log( 'Validate Credentials : API URL', $api_url );

		$api_payload = array(
			'timeout' => 45,
			'body'    => array(
				'api_token' => $podmotor_account_api_token,
				'email'     => $podmotor_account_email,
				'website'   => get_site_url(),
			),
		);

		$this->logger->log( 'Validate Credentials : Api Payload', $api_payload );

		$app_response = wp_remote_get( $api_url, $api_payload );

		$this->logger->log( 'Validate Credentials : App Response', $app_response );

		if ( ! is_wp_error( $app_response ) ) {

			$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );

			if ( ! empty( $response_object ) ) {

				if ( 'success' === $response_object->status ) {
					$this->update_response( 'status', 'success' );
					$this->update_response( 'message', 'API Credentials Validated.' );
					$this->update_response( 'podmotor_id', $response_object->podmotor_id );
				} else {
					$this->update_response( 'message', 'An error occurred validating the credentials.' );
				}
			} else {
				$this->update_response( 'message', 'An error occurred retrieving the credential validation.' );
			}
		} else {
			$this->update_response( 'message', 'An error occurred connecting to the server for validation.' );
		}

		return $this->response;
	}

	/**
	 * Triggers the podcast import fom the Import settings screen
	 */
	public function trigger_podcast_import() {
		$this->setup_response();

		$api_url = SSP_CASTOS_APP_URL . 'api/user/import';
		$this->logger->log( $api_url );

		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
		$this->logger->log( $podmotor_api_token );

		$post_body = array(
			'api_token' => $podmotor_api_token,
		);
		$this->logger->log( $post_body );

		$app_response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 45,
				'body'    => $post_body,
			)
		);

		if ( is_wp_error( $app_response ) ) {
			$this->update_response( 'message', 'An error occurred connecting to the Castos server to trigger the podcast import.' );
			$this->logger->log( $this->response );
			return $this->response;
		}

		$this->update_response( 'status', 'success' );
		$this->update_response( 'message', 'Podcast import started successfully.' );

		return $this->response;

	}

	/**
	 * Upload PodcastMotor file stored in offsite hosting to Castos database
	 *
	 * @param string $podmotor_file_path
	 *
	 * @return array|mixed|object
	 */
	public function upload_podmotor_storage_file_data_to_podmotor( $podmotor_file_path = '' ) {

		$this->setup_response();
		if ( empty( $podmotor_file_path ) ) {
			$this->update_response( 'message', 'No file to upload' );

			return $this->response;
		}
		$api_url = SSP_CASTOS_APP_URL . 'api/file';
		$this->logger->log( $api_url );

		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
		$this->logger->log( $podmotor_api_token );

		$post_body = array(
			'api_token'          => $podmotor_api_token,
			'podmotor_file_path' => $podmotor_file_path,
		);
		$this->logger->log( $post_body );

		$app_response = wp_remote_post(
			$api_url,
			array(
				'timeout' => 45,
				'body'    => $post_body,
			)
		);
		$this->logger->log( $app_response );

		if ( ! is_wp_error( $app_response ) ) {
			$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );
			if ( ! empty( $response_object ) ) {
				if ( 'success' === $response_object->status ) {
					$this->update_response( 'status', 'success' );
					$this->update_response( 'message', 'File successfully uploaded.' );
					$this->update_response( 'file_id', $response_object->file_id );
					$this->update_response( 'file_path', $response_object->file_path );
					$this->update_response( 'file_duration', $response_object->file_duration );
				} else {
					if ( isset( $response_object->message ) ) {
						$this->update_response( 'message', $response_object->message );
					} else {
						$this->update_response( 'message', 'An error occurred uploading the file data to Castos.' );
					}
				}
			} else {
				$this->update_response( 'message', 'An unknown error occurred uploading the file data to Castos.' );
			}
		} else {
			$this->update_response( 'message', $app_response->get_error_message() );
		}

		return $this->response;
	}

	/**
	 * Upload Podcast episode data to Seriously Simple Hosting
	 * Should only happen once the file has been uploaded to Seriously Simple Hosting Storage
	 *
	 * @param $post
	 *
	 * @return array
	 */
	public function upload_podcast_to_podmotor( $post ) {

		$this->setup_response();

		if ( empty( $post ) ) {
			$this->update_response( 'message', 'Invalid Podcast data' );
			$this->logger->log( 'Invalid Podcast data when uploading podcast data' );

			return $this->response;
		}

		/**
		 * Don't trigger this unless we have a valid PodcastMotor file id
		 */
		$podmotor_file_id = get_post_meta( $post->ID, 'podmotor_file_id', true );
		if ( empty( $podmotor_file_id ) ) {
			$this->update_response( 'message', 'Invalid Podcast file data' );
			$this->logger->log( 'Invalid Podcast file data when uploading podcast data' );
			return $this->response;
		}

		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );

		$api_url = SSP_CASTOS_APP_URL . 'api/episode';

		$this->logger->log( 'API URL', $api_url );

		$series_id = ssp_get_episode_series_id( $post->ID );

		$fields = array(
			'api_token'    => $podmotor_api_token,
			'post_id'      => $post->ID,
			'post_title'   => $post->post_title,
			'post_content' => $post->post_content,
			'post_date'    => $post->post_date,
			'file_id'      => $podmotor_file_id,
			'series_id'    => $series_id,
		);

		$podmotor_episode_id = get_post_meta( $post->ID, 'podmotor_episode_id', true );

		if ( ! empty( $podmotor_episode_id ) ) {
			$fields['id'] = $podmotor_episode_id;
		}

		$boundary   = wp_generate_password( 24 );
		$post_body  = $this->prepare_post_body( $fields, $boundary );
		$post_body  = $this->prepare_featured_image( $post, $post_body, $boundary );
		$post_body .= '--' . $boundary . '--';

		$headers = array(
			'content-type' => 'multipart/form-data; boundary=' . $boundary,
		);

		$post_arguments = array(
			'timeout' => 45,
			'headers' => $headers,
			'body'    => $post_body,
		);

		$this->logger->log( 'Post Body', $post_arguments );

		$app_response = wp_remote_post(
			$api_url,
			$post_arguments
		);

		$this->logger->log( 'Upload Podcast app_response', $app_response );

		if ( ! is_wp_error( $app_response ) ) {
			$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );

			$this->logger->log( 'Upload Podcast Response', $response_object );

			if ( 'success' === $response_object->status ) {
				$this->logger->log( 'Pocast episode successfully uploaded to Castos with episode id ' . $response_object->episode_id );
				$this->update_response( 'status', 'success' );
				$this->update_response( 'message', 'Pocast episode successfully uploaded to Castos' );
				$this->update_response( 'episode_id', $response_object->episode_id );
			} else {
				$this->logger->log( 'An error occurred uploading the episode data to Castos', $response_object );
				$this->update_response( 'message', 'An error occurred uploading the episode data to Castos' );
			}
		} else {
			$this->logger->log( 'An unknown error occurred sending podcast data to castos: ' . $app_response->get_error_message() );
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );
		}

		return $this->response;
	}

	/**
	 * Prepare the post body fields
	 *
	 * @param $fields
	 * @param $boundary
	 *
	 * @return string
	 */
	public function prepare_post_body( $fields, $boundary ) {
		$post_body = '';
		foreach ( $fields as $name => $value ) {
			$post_body .= '--' . $boundary;
			$post_body .= "\r\n";
			$post_body .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
			$post_body .= $value;
			$post_body .= "\r\n";
		}

		return $post_body;
	}

	/**
	 * Checks if there is a featured image on the post
	 * If so, reads the data to a file pointer and returns it in the post body
	 *
	 * @param $post
	 * @param $post_body
	 *
	 * @return mixed
	 */
	public function prepare_featured_image( $post, $post_body, $boundary ) {
		$featured_image_id = get_post_thumbnail_id( $post->ID );
		if ( empty( $featured_image_id ) ) {
			return $post_body;
		}
		$image_file = get_attached_file( $featured_image_id );
		$file       = fopen( $image_file, 'rb' ); //phpcs:ignore
		if ( ! $file ) {
			$this->logger->log( 'Could not read featured image for post:' . $post->ID );

			return $post_body;
		}

		$mime_type = get_post_mime_type( $featured_image_id );

		$post_body .= '--' . $boundary;
		$post_body .= "\r\n";
		$post_body .= 'Content-Disposition: form-data; name="episode_file"; filename="' . basename( $image_file ) . '"' . "\r\n";
		if ( ! empty( $mime_type ) ) {
			$post_body .= 'Content-Type: ' . $mime_type . "\r\n";
		}
		$post_body .= "\r\n";
		$post_body .= file_get_contents( $image_file ); //phpcs:ignore
		$post_body .= "\r\n";

		return $post_body;
	}

	/**
	 * Upload Podcasts episode data to Castos
	 *
	 * @param $podcast_data array of post values
	 *
	 * @return array
	 */
	public function upload_podcasts_to_podmotor( $podcast_data ) {

		$this->setup_response();

		if ( empty( $podcast_data ) ) {
			$this->update_response( 'message', 'Invalid Podcast data' );

			return $this->response;
		}

		$podcast_data_json = wp_json_encode( $podcast_data );

		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );

		$api_url = SSP_CASTOS_APP_URL . 'api/import_episodes';

		$post_body = array(
			'api_token'    => $podmotor_api_token,
			'podcast_data' => $podcast_data_json,
		);

		$this->logger->log( $post_body );

		$app_response = wp_remote_post(
			$api_url,
			array(
				'timeout' => 45,
				'body'    => $post_body,
			)
		);

		$this->logger->log( $app_response );

		if ( ! is_wp_error( $app_response ) ) {
			$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );
			if ( 'success' == $response_object->status ) {
				$this->update_response( 'status', 'success' );
				$this->update_response( 'message', 'Pocast episode data successfully uploaded to Castos' );
			} else {
				$this->update_response( 'message', 'An error occurred uploading the episode data to Castos' );
			}
		} else {
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );
		}

		return $this->response;
	}

	/**
	 * Upload series data to Castos
	 *
	 * @param $series
	 *
	 * @return array
	 */
	public function upload_series_to_podmotor( $series_data ) {
		$this->setup_response();

		if ( empty( $series_data ) ) {
			$this->update_response( 'message', 'Invalid Series data' );
			$this->logger->log( 'Invalid Series data when uploading series data' );

			return $this->response;
		}

		$this->logger->log( 'Series Object', $series_data );

		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );

		$api_url = SSP_CASTOS_APP_URL . 'api/series';

		$this->logger->log( 'API URL', $api_url );

		$series_data['api_token'] = $podmotor_api_token;

		$this->logger->log( 'Parameter series_data Contents', $series_data );

		$app_response = wp_remote_post(
			$api_url,
			array(
				'timeout' => 45,
				'body'    => $series_data,
			)
		);

		$this->logger->log( 'app_response', $app_response );

		if ( is_wp_error( $app_response ) ) {
			$this->logger->log( 'An unknown error occurred sending series data to castos: ' . $app_response->get_error_message() );
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );

			return $this->response;
		}

		$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );
		$this->logger->log( 'Response Object', $response_object );

		if ( ! isset( $response_object->status ) || 'success' !== $response_object->status ) {
			$this->logger->log( 'An error occurred uploading the series data to Castos', $response_object );
			$this->update_response( 'message', 'An error occurred uploading the series data to Castos' );

			return $this->response;
		}

		$this->logger->log( 'Series data successfully uploaded to Castos' );
		$this->update_response( 'status', 'success' );
		$this->update_response( 'message', 'Series data successfully uploaded to Castos' );

		return $this->response;
	}

	/**
	 * Creates the podcast import queue with Castos
	 *
	 * @return array
	 */
	public function insert_podmotor_queue() {

		$this->setup_response();

		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
		$this->logger->log( $podmotor_api_token );

		$api_url = SSP_CASTOS_APP_URL . 'api/insert_queue';
		$this->logger->log( $api_url );

		$post_body = array(
			'api_token'   => $podmotor_api_token,
			'site_name'   => get_bloginfo( 'name' ),
			'site_action' => add_query_arg( 'podcast_importer', 'true', trailingslashit( site_url() ) ),
		);
		$this->logger->log( $post_body );

		$app_response = wp_remote_post(
			$api_url,
			array(
				'timeout' => 45,
				'body'    => $post_body,
			)
		);
		$this->logger->log( $app_response );

		if ( ! is_wp_error( $app_response ) ) {
			$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );
			$this->logger->log( $response_object );

			if ( 'success' === $response_object->status ) {
				$this->update_response( 'status', $response_object->status );
				$this->update_response( 'message', $response_object->message );
				$this->update_response( 'queue_id', $response_object->queue_id );
			} else {
				$this->update_response( 'message', 'An error occurred uploading the episode data to Castos' );
			}
		} else {
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );
		}

		return $this->response;

	}
}
