<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Helpers\Log_Helper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Castos_Handler {

	/**
	 * @const int
	 */
	const MIN_IMG_SIZE = 300;

	/**
	 * @var string
	 */
	protected $api_token;

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
		$this->logger    = new Log_Helper();
		$this->api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
	}

	/**
	 * Sets up the response array
	 */
	protected function setup_response() {
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
	protected function update_response( $key, $value ) {
		$this->response[ $key ] = $value;
	}

	/**
	 * Takes the raw content from the post object, and runs it through the the_content filters
	 * Effectively replicates the the_content function, but for a specific podcast
	 *
	 * @param $post
	 * @return string|string[]
	 */
	protected function get_rendered_post_content( $post ) {
		global $ss_podcasting;
		/**
		 * Remove the filter that adds our media player to the post_content
		 */
		remove_filter( 'the_content', array( $ss_podcasting, 'content_meta_data' ) );
		/**
		 * Get the post content and run it through the rest of the registered 'the_content' filters
		 */
		$post_content = get_the_content( null, false, $post );
		$post_content = apply_filters( 'the_content', $post_content );
		$post_content = str_replace( ']]>', ']]&gt;', $post_content );

		return $post_content;
	}

	/**
	 * Connect to Castos API and validate API credentials
	 *
	 * @param string $account_api_token
	 * @param string $account_email
	 *
	 * @return array
	 */
	public function validate_api_credentials( $account_api_token = '', $account_email = '' ) {

		$this->setup_response();

		if ( empty( $account_api_token ) || empty( $account_email ) ) {
			$this->update_response( 'message', 'Invalid API Token or email.' );

			return $this->response;
		}

		/**
		 * Clear out existing values
		 */
		delete_option( 'ss_podcasting_podmotor_account_email' );
		delete_option( 'ss_podcasting_podmotor_account_api_token' );
		delete_option( 'ss_podcasting_podmotor_account_id' );

		$api_url = SSP_CASTOS_APP_URL . 'api/v2/users/validate';

		$this->logger->log( 'Validate Credentials : API URL', $api_url );

		$api_payload = array(
			'timeout' => 45,
			'body'    => array(
				'api_token' => $account_api_token,
				'email'     => $account_email,
				'website'   => get_site_url(),
			),
		);

		$this->logger->log( 'Validate Credentials : Api Payload', $api_payload );

		$app_response = wp_remote_get( $api_url, $api_payload );

		$this->logger->log( 'Validate Credentials : App Response', $app_response );

		if ( is_wp_error( $app_response ) ) {
			$this->update_response( 'message', 'An error occurred connecting to the server for validation.' );

			return $this->response;
		}

		$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );

		if ( empty( $response_object ) ) {
			$this->update_response( 'message', 'An error occurred retrieving the credential validation.' );

			return $this->response;
		}

		if ( ! $response_object->success ) {
			$this->update_response( 'message', 'An error occurred validating the credentials.' );

			return $this->response;
		}

		$this->update_response( 'status', 'success' );
		$this->update_response( 'message', 'API Credentials Validated.' );

		return $this->response;
	}

	/**
	 * Triggers the podcast import fom the Import settings screen
	 */
	public function trigger_podcast_import() {
		$this->setup_response();

		$api_url = SSP_CASTOS_APP_URL . 'api/user/import';
		$this->logger->log( $api_url );

		$post_body = array(
			'api_token' => $this->api_token,
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

		$api_url             = SSP_CASTOS_APP_URL . 'api/v2/posts/create';
		$podmotor_episode_id = get_post_meta( $post->ID, 'podmotor_episode_id', true );
		if ( ! empty( $podmotor_episode_id ) ) {
			$api_url = SSP_CASTOS_APP_URL . 'api/v2/posts/update';
		}

		$series_id = ssp_get_episode_series_id( $post->ID );

		$post_body = array(
			'token'          => $this->api_token,
			'post_id'        => $post->ID,
			'post_title'     => $post->post_title,
			'post_content'   => $this->get_rendered_post_content( $post ),
			'keywords'       => get_keywords_for_episode( $post->ID ),
			'series_number'  => get_post_meta( $post->ID, 'itunes_season_number', true ),
			'episode_number' => get_post_meta( $post->ID, 'itunes_episode_number', true ),
			'episode_type'   => get_post_meta( $post->ID, 'itunes_episode_type', true ),
			'post_date'      => $post->post_date,
			'file_id'        => $podmotor_file_id,
			'series_id'      => $series_id,
		);

		if ( ! empty( $podmotor_episode_id ) ) {
			$post_body['id'] = $podmotor_episode_id;
		}

		$this->logger->log( 'API URL', $api_url );

		$episode_image_url = $this->get_episode_image_url( $post );
		if ( ! empty( $episode_image_url ) ) {
			// Todo: change 'featured_image_url' to 'cover_image_url' after API update
			$post_body['featured_image_url'] = $episode_image_url;
		}

		$this->logger->log( 'Parameter post_body Contents', $post_body );

		/**
		 * Convert to JSON so that we send it with the Content-Type of application/json
		 * On some WordPress installs the Content-Type defaults to text/html
		 * Just setting the Content-Type to application/json was not enough, so the post_body has to be converted
		 * to JSON as well.
		 */
		$post_body = wp_json_encode( $post_body );

		$options = array(
			'body'        => $post_body,
			'headers'     => array(
				'Content-Type' => 'application/json',
			),
			'timeout'     => 60,
		);

		$app_response = wp_remote_post( $api_url, $options );

		$this->logger->log( 'Upload Podcast app_response', $app_response );

		if ( is_wp_error( $app_response ) ) {
			$this->logger->log( 'An unknown error occurred sending podcast data to castos: ' . $app_response->get_error_message() );
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );

			return $this->response;
		}

		$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );

		$this->logger->log( 'Upload Podcast Response', $response_object );

		if ( ! isset( $response_object->status ) || ! $response_object->status || empty( $response_object->success ) ) {
			$this->logger->log( 'An error occurred uploading the episode data to Castos', $response_object );
			$this->update_response( 'message', 'An error occurred uploading the episode data to Castos' );

			return $this->response;
		}

		$this->logger->log( 'Pocast episode successfully uploaded to Castos with episode id ' . $response_object->episode->id );
		$this->update_response( 'status', 'success' );
		$this->update_response( 'message', 'Pocast episode successfully uploaded to Castos' );
		$this->update_response( 'episode_id', $response_object->episode->id );

		return $this->response;
	}

	/**
	 * Gets cover image url
	 *
	 * @param $post
	 *
	 * @return string
	 */
	public function get_episode_image_url( $post ) {
		$key    = 'cover_image';
		$id_key = 'cover_image_id';

		$episode_image = filter_input( INPUT_POST, $key, FILTER_VALIDATE_URL );
		$attachment_id = filter_input( INPUT_POST, $id_key );

		if ( ! $episode_image || ! $this->is_valid_episode_image( $attachment_id ) ) {
			$episode_image = get_post_meta( $post->ID, $key );
			$attachment_id = get_post_meta( $post->ID, $id_key );
		}

		if ( ! $episode_image || ! $this->is_valid_episode_image( $attachment_id ) ) {
			$episode_image = get_the_post_thumbnail_url( $post, 'full' );
			$attachment_id = get_post_thumbnail_id( $post );
		}

		if ( ! $episode_image || ! $this->is_valid_episode_image( $attachment_id ) ) {
			$episode_image = '';
		}

		return $episode_image;
	}

	/**
	 * @param int $attachment_id
	 *
	 * @return bool
	 */
	public function is_valid_episode_image( $attachment_id ) {
		if ( empty( $attachment_id ) ) {
			return false;
		}

		$image = wp_get_attachment_image_src( $attachment_id, 'full' );

		if ( empty( $image[1] ) || empty( $image[2] ) ) {
			return false;
		}

		$width  = $image[1];
		$height = $image[2];

		return ( $width === $height ) && $width >= self::MIN_IMG_SIZE;
	}


	/**
	 * Delete a post from Castos when it's trashed in WordPress
	 *
	 * @param $post
	 *
	 * @return bool
	 */
	public function delete_podcast( $post ) {
		$this->setup_response();

		if ( empty( $post ) ) {
			$this->logger->log( 'Post to trash empty', array( 'post', $post ) );
			return false;
		}

		$episode_id = get_post_meta( $post->ID, 'podmotor_episode_id', true );
		if ( empty( $episode_id ) ) {
			$this->logger->log( 'Episode ID to trash empty', array( 'episode_id', $episode_id ) );
			return false;
		}

		$api_url = SSP_CASTOS_APP_URL . 'api/v2/posts/delete';

		$post_body = array(
			'token' => $this->api_token,
			'id'    => $episode_id,
		);

		$api_response = wp_remote_request(
			$api_url,
			array(
				'method'  => 'DELETE',
				'timeout' => 45,
				'body'    => $post_body,
			)
		);

		$this->logger->log( 'Delete Podcast api_response', $api_response );

		return true;
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

		$api_url = SSP_CASTOS_APP_URL . 'api/import_episodes';

		$post_body = array(
			'api_token'    => $this->api_token,
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

		if ( is_wp_error( $app_response ) ) {
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );

			return $this->response;
		}

		$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );
		if ( 'success' !== $response_object->status ) {
			$this->update_response( 'message', 'An error occurred uploading the episode data to Castos' );

			return $this->response;
		}

		$this->update_response( 'status', 'success' );
		$this->update_response( 'message', 'Pocast episode data successfully uploaded to Castos' );

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

		$api_url = SSP_CASTOS_APP_URL . 'api/v2/series/create';

		$this->logger->log( 'API URL', $api_url );

		$series_data['token'] = $this->api_token;

		$app_response = wp_remote_post(
			$api_url,
			array(
				'timeout' => 45,
				'body'    => $series_data,
			)
		);

		if ( is_wp_error( $app_response ) ) {
			$this->logger->log( 'An unknown error occurred sending series data to castos: ' . $app_response->get_error_message() );
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );

			return $this->response;
		}

		$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );
		$this->logger->log( 'Response Object', $response_object );

		if ( empty( $response_object->status ) ) {
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

		$api_url = SSP_CASTOS_APP_URL . 'api/insert_queue';
		$this->logger->log( $api_url );

		$post_body = array(
			'api_token'   => $this->api_token,
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

		if ( is_wp_error( $app_response ) ) {
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );

			return $this->response;
		}

		$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );
		$this->logger->log( $response_object );

		if ( 'success' !== $response_object->status ) {
			$this->update_response( 'message', 'An error occurred uploading the episode data to Castos' );

			return $this->response;
		}

		$this->update_response( 'status', $response_object->status );
		$this->update_response( 'message', $response_object->message );
		$this->update_response( 'queue_id', $response_object->queue_id );

		return $this->response;
	}
}
