<?php

namespace SeriouslySimplePodcasting\Handlers;


use SeriouslySimplePodcasting\Entities\API_File_Data;
use SeriouslySimplePodcasting\Entities\API_Podcast;
use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Entities\Episode_File_Data;
use SeriouslySimplePodcasting\Helpers\Log_Helper;
use SeriouslySimplePodcasting\Interfaces\Service;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Castos_Handler implements Service {

	/**
	 * @const int
	 */
	const MIN_IMG_SIZE = 300;

	/**
	 * @const int
	 * */
	const TIMEOUT = 45;

	/**
	 * @var string
	 */
	protected $api_token;

	/**
	 * Response array
	 *
	 * @var array
	 *
	 * Todo: get rid of storing response?
	 */
	public $response = array();

	/**
	 * @var Log_Helper
	 */
	public $logger;

	/**
	 * @var Feed_Handler
	 * */
	protected $feed_handler;


	/**
	 * @var Sync_Status[] $cached_podcast_statuses
	 * */
	protected $cached_podcast_statuses;

	/**
	 * @var array $cached_podcasts_response
	 * */
	protected $cached_podcasts_response;


	/**
	 * Castos_Handler constructor.
	 */
	public function __construct() {
		$this->feed_handler = new Feed_Handler();
		$this->logger       = new Log_Helper();
		$this->api_token    = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
	}

	/**
	 * Sets up the response array
	 */
	protected function setup_default_response() {
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
	 * Connect to Castos API and validate API credentials
	 *
	 * @param string $account_api_token
	 * @param string $account_email
	 *
	 * @return array
	 */
	public function validate_api_credentials( $account_api_token = '', $account_email = '' ) {

		$this->setup_default_response();

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

	public function trigger_podcast_sync( $series_id ) {
		$this->logger->log( __METHOD__, compact( 'series_id' ) );
		$endpoint = sprintf( 'api/v2/ssp/podcast-sync/%d', intval( $series_id ) );

		$res = $this->send_request( $endpoint, array(), 'POST' );

		return $res;
	}

	/**
	 * Triggers the podcast import fom the Import settings screen
	 * @deprecated After the new sync process was made in 2.23.0
	 * @todo: remove after 3.0.0
	 */
	public function trigger_podcast_import() {
		$this->setup_default_response();

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
	public function upload_episode_to_castos( $post ) {

		$this->setup_default_response();

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
		if ( $podmotor_episode_id ) {
			$api_url = SSP_CASTOS_APP_URL . 'api/v2/posts/update';
		}

		$series_id = ssp_get_episode_series_id( $post->ID );

		$post_body = array(
			'token'          => $this->api_token,
			'post_id'        => $post->ID,
			'post_title'     => $post->post_title,
			'post_content'   => $this->get_episode_content( $post->ID, $series_id ),
			'keywords'       => get_keywords_for_episode( $post->ID ),
			'series_number'  => get_post_meta( $post->ID, 'itunes_season_number', true ),
			'episode_number' => get_post_meta( $post->ID, 'itunes_episode_number', true ),
			'episode_type'   => get_post_meta( $post->ID, 'itunes_episode_type', true ),
			'post_date'      => $post->post_date,
			'post_date_gmt'  => $post->post_date_gmt,
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
			'body'    => $post_body,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'timeout' => 60,
		);

		$app_response = wp_remote_post( $api_url, $options );

		if ( isset( $app_response['response']['code'] ) ) {
			$this->update_response( 'code', intval( $app_response['response']['code'] ) );
		}

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

		$this->logger->log( 'Podcast episode successfully uploaded to Castos with episode id ' . $response_object->episode->id );
		$this->update_response( 'status', 'success' );
		$this->update_response( 'message', 'Podcast episode successfully uploaded to Castos' );
		$this->update_response( 'episode_id', $response_object->episode->id );

		return $this->response;
	}


	/**
	 * Get episode content.
	 *
	 * @param int $episode_id
	 * @param int $series_id
	 *
	 * @return string
	 * @since 2.11.0
	 *
	 */
	public function get_episode_content( $episode_id, $series_id ) {
		$is_excerpt_mode = $this->feed_handler->is_excerpt_mode( $series_id );

		return $this->feed_handler->get_feed_item_description( $episode_id, $is_excerpt_mode );
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
			$episode_image = get_post_meta( $post->ID, $key, true );
			$attachment_id = get_post_meta( $post->ID, $id_key, true );
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
	 * @param string $url
	 *
	 * @return API_File_Data
	 * @throws \Exception
	 */
	public function get_file_data( $url ) {
		$this->logger->log( __METHOD__ );

		$url = esc_url_raw( $url );

		$res = $this->send_request( sprintf( 'api/v2/ssp/files?file_path=%s', $url ) );

		return new API_File_Data( $res );
	}


	/**
	 * Delete a post from Castos when it's trashed in WordPress
	 *
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public function delete_episode( $post ) {
		$this->setup_default_response();

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

		$this->logger->log( 'Delete Episode api_response', $api_response );

		return true;
	}

	/**
	 * Upload series data to Castos
	 *
	 * @param array $podcast_data
	 *
	 * @return array
	 */
	public function update_podcast_data( $podcast_data ) {
		$this->setup_default_response();

		if ( empty( $podcast_data ) ) {
			$this->update_response( 'message', 'Invalid Podcast data' );
			$this->logger->log( 'Invalid Podcast data when uploading' );

			return $this->response;
		}

		$api_url = SSP_CASTOS_APP_URL . 'api/v2/series/create';

		$this->logger->log( 'API URL', $api_url );

		$podcast_data['token'] = $this->api_token;

		$app_response = wp_remote_post(
			$api_url,
			array(
				'timeout' => 45,
				'body'    => $podcast_data,
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

		$this->logger->log( 'Podcast data successfully uploaded to Castos' );
		$this->update_response( 'status', 'success' );
		$this->update_response( 'message', 'Podcast data successfully uploaded to Castos' );

		return $this->response;
	}


	/**
	 * @return API_Podcast[]
	 */
	public function get_podcast_items(){
		$podcasts              = $this->get_podcasts();
		$items = array();
		if( ! isset( $podcasts['data']['podcast_list'] ) || !is_array($podcasts['data']['podcast_list']) ) {
			return $items;
		}

		foreach ( $podcasts['data']['podcast_list'] as $data ) {
			$items[] = new API_Podcast( $data );
		}
		return $items;
	}

	public function get_podcasts() {
		if ( $this->cached_podcasts_response ) {
			return $this->cached_podcasts_response;
		}

		$this->setup_default_response();

		$api_url = SSP_CASTOS_APP_URL . 'api/v2/podcasts';

		$this->logger->log( 'Get podcasts', $api_url );

		$api_payload = array(
			'timeout' => 45,
			'body'    => array(
				'token'        => $this->api_token,
				'show_details' => true,
			),
		);

		$app_response = wp_remote_get( $api_url, $api_payload );

		if ( is_wp_error( $app_response ) ) {
			$this->update_response( 'message', 'An error occurred connecting to the Castos server to get podcasts lists.' );
			$this->logger->log( 'response', $this->response );

			return $this->response;
		}

		$this->update_response( 'status', 'success' );
		$this->update_response( 'message', 'Successfully retrieved podcasts.' );

		$podcasts      = isset( $app_response['body'] ) ? json_decode( $app_response['body'], true ) : array();
		$podcasts_data = isset( $podcasts['data'] ) ? $podcasts['data'] : array();

		$this->update_response( 'data', $podcasts_data );

		$this->cached_podcasts_response = $this->response;

		return $this->response;
	}

	/**
	 * @param $series_id
	 *
	 * @return Sync_Status
	 * @throws \Exception
	 */
	public function get_podcast_sync_status( $series_id ) {
		if ( ! empty( $this->cached_podcast_statuses[ $series_id ] ) ) {
			return $this->cached_podcast_statuses[ $series_id ];
		}
		$status = new Sync_Status( Sync_Status::SYNC_STATUS_NONE );
		$res    = $this->get_podcasts();
		if ( ! empty( $res['data']['podcast_list'] ) ) {
			foreach ( $res['data']['podcast_list'] as $podcast ) {
				if ( isset( $podcast['series_id'] ) && $podcast['series_id'] === $series_id ) {
					$status = $this->retrieve_sync_status_by_podcast_data( $podcast );
					break;
				}
			}
		}
		$this->cached_podcast_statuses[ $series_id ] = $status;

		return $status;
	}


	/**
	 * @param array $castos_podcast
	 *
	 * @return Sync_Status
	 * @throws \Exception
	 */
	public function retrieve_sync_status_by_podcast_data( $castos_podcast ) {
		$map    = array(
			'none'                  => Sync_Status::SYNC_STATUS_NONE,
			'in_progress'           => Sync_Status::SYNC_STATUS_SYNCING,
			'completed'             => Sync_Status::SYNC_STATUS_SYNCED,
			'completed_with_errors' => Sync_Status::SYNC_STATUS_SYNCED_WITH_ERRORS,
			'failed'                => Sync_Status::SYNC_STATUS_FAILED,
		);
		$status = new Sync_Status( Sync_Status::SYNC_STATUS_NONE );
		if ( isset( $castos_podcast['ssp_import_status'] ) && array_key_exists( $castos_podcast['ssp_import_status'], $map ) ) {
			$status = new Sync_Status( $map[ $castos_podcast['ssp_import_status'] ] );
		}

		return $status;
	}


	/**
	 * Gets podcast subscribers.
	 *
	 * @param int $podcast_id
	 *
	 * @return array
	 */
	public function get_podcast_subscribers( $podcast_id ) {
		$this->logger->log( __METHOD__ );

		$res = $this->send_request( 'api/v2/private-subscribers', [ 'podcast_id' => $podcast_id ] );

		return ! empty( $res['subscribers'] ) ? $res['subscribers'] : array();
	}


	/**
	 * Gets subscriber subscriptions by email.
	 *
	 * @param string $email
	 *
	 * @return array
	 */
	public function get_subscriptions_by_email( $email ) {
		$this->logger->log( __METHOD__ );

		$cache_key = 'ssp_castos_api_subscriptions_' . $email;

		$subscriptions = wp_cache_get( $cache_key );

		if ( $subscriptions && is_array( $subscriptions ) ) {
			return $subscriptions;
		}

		$res = $this->send_request( "api/v2/private-subscribers/email/$email" );

		if ( isset( $res['subscriptions'] ) ) {
			wp_cache_add( $cache_key, $res['subscriptions'] );
		}

		return ! empty( $res['subscriptions'] ) ? $res['subscriptions'] : array();
	}


	/**
	 * @param $castos_episode_id
	 *
	 * @return Episode_File_Data
	 * @throws \Exception
	 */
	public function get_episode_file_data( $castos_episode_id ) {
		$this->logger->log( __METHOD__ );

		$cache_key = 'ssp_castos_api_episode_ads_' . $castos_episode_id;

		$file_data = wp_cache_get( $cache_key );

		if ( $file_data ) {
			return $file_data;
		}

		$res = $this->send_request( sprintf( 'api/v2/ssp/episodes/%d/file', $castos_episode_id ) );

		$file_data = new Episode_File_Data( $res );

		wp_cache_add( $cache_key, $file_data, '', MINUTE_IN_SECONDS );

		return $file_data;
	}


	/**
	 * Add single podcast subscriber.
	 *
	 * @param int $podcast_id
	 * @param string $email
	 * @param string $name
	 *
	 * @throws \Exception
	 */
	public function add_podcast_subscriber( $podcast_id, $email, $name ) {
		$this->logger->log( __METHOD__, compact( 'podcast_id', 'email', 'name' ) );

		if ( empty( $podcast_id ) || empty( $email ) ) {
			throw new \Exception( __METHOD__ . ': Wrong arguments!' );
		}

		return $this->send_request( 'api/v2/private-subscribers', compact( 'podcast_id', 'email', 'name' ), 'POST' );
	}


	/**
	 * Add subscriber to multiple podcasts.
	 *
	 * @param array $podcast_ids
	 * @param string $email
	 * @param string $name
	 */
	public function add_subscriber_to_podcasts( $podcast_ids, $email, $name ) {
		$this->logger->log( __METHOD__, compact( 'podcast_ids', 'email', 'name' ) );

		$podcasts = array();

		foreach ( $podcast_ids as $podcast_id ) {
			$podcasts[] = array( 'id' => $podcast_id );
		}

		$subscribers = array(
			array(
				'email' => $email,
				'name'  => $name,
			),
		);

		return $this->send_request( 'api/v2/create-private-subscribers', compact( 'podcasts', 'subscribers' ), 'POST' );
	}


	/**
	 * Add subscriber to multiple podcasts.
	 *
	 * @param array $podcast_ids
	 * @param array $subscribers {
	 *  array(
	 *     Subscriber data
	 *
	 * @type string $email User email.
	 * @type string $name User name.
	 *  )
	 * }
	 *
	 * @return int Number of added subscribers sent to all podcasts
	 */
	public function add_subscribers_to_podcasts( $podcast_ids, $subscribers ) {
		$count = 0;
		$this->logger->log(
			__METHOD__,
			array( 'podcast_ids' => $podcast_ids, 'subscribers' => array_keys( $subscribers ) )
		);

		$podcasts = array();

		foreach ( $podcast_ids as $podcast_id ) {
			$podcasts[] = array( 'id' => $podcast_id );
		}

		// If there's a lot of subscribers, API might fail, so let's chunk it and send 100 users per request
		$subscribers_groups = array_chunk( $subscribers, 100 );

		foreach ( $subscribers_groups as $subscribers_group ) {

			// Make sure that all subscribers are valid (have email and name);
			$subscribers_to_send = array_map( function ( $s ) {
				if ( empty( $s['email'] ) || empty( $s['name'] ) ) {
					$this->logger->log( __METHOD__, 'Error: wrong subscriber data: ' . print_r( $s, true ) );

					return null;
				}

				return array(
					'email' => $s['email'],
					'name'  => $s['name'],
				);
			}, $subscribers_group );

			$subscribers_to_send = array_filter( $subscribers_to_send );

			$res = $this->send_request(
				'api/v2/create-private-subscribers',
				array(
					'podcasts'    => $podcasts,
					'subscribers' => $subscribers_to_send,
				),
				'POST'
			);

			if ( ! empty( $res['success'] ) ) {
				$count += count( $subscribers_to_send );
			} else {
				$this->logger->log( __METHOD__, 'API response error!' );
			}
		}

		return $count;
	}

	/**
	 * Revoke subscriber from multiple podcasts.
	 *
	 * @param array $podcast_ids
	 * @param string[] $emails
	 *
	 * @return int Number of revoked subscribers from all podcasts
	 */
	public function revoke_subscribers_from_podcasts( $podcast_ids, $emails ) {
		$count = 0;
		$this->logger->log( __METHOD__, compact( 'podcast_ids', 'emails' ) );

		// If there's a lot of emails, API might fail, so let's chunk it and send 100 per request
		$email_groups = array_chunk( $emails, 100 );

		foreach ( $podcast_ids as $podcast_id ) {
			foreach ( $email_groups as $email_group ) {
				$subscribers = array();
				foreach ( $email_group as $email ) {
					$subscribers[] = array(
						'email'      => $email,
						'podcast_id' => $podcast_id,
					);
				}
				$res = $this->send_request( 'api/v2/revoke-private-subscribers', compact( 'subscribers' ), 'POST' );

				if ( ! empty( $res['success'] ) ) {
					$count += count( $subscribers );
				} else {
					$this->logger->log( __METHOD__, 'API response error!' );
				}
			}
		}

		return $count;
	}


	/**
	 * Revoke subscriber from multiple podcasts.
	 *
	 * @param array $podcast_ids
	 * @param string $email
	 *
	 * @return array|null
	 */
	public function revoke_subscriber_from_podcasts( $podcast_ids, $email ) {
		$this->logger->log( __METHOD__, compact( 'podcast_ids', 'email' ) );

		$subscribers = array();

		foreach ( $podcast_ids as $podcast_id ) {
			$subscribers[] = array(
				'email'      => $email,
				'podcast_id' => $podcast_id,
			);
		}

		return $this->send_request( 'api/v2/revoke-private-subscribers', compact( 'subscribers' ), 'POST' );
	}


	/**
	 * Sends request to Castos API.
	 *
	 * @param string $api_url
	 * @param array $args
	 *
	 * @return array Response object or the default errors array.
	 */
	protected function send_request( $api_url, $args = array(), $method = 'GET' ) {

		$token = apply_filters( 'ssp_castos_api_token', $this->api_token, $api_url, $args, $method );

		if ( empty( $this->api_token ) ) {
			throw new \Exception( __( 'Castos arguments not set', 'seriously-simple-podcasting' ) );
		}

		$this->setup_default_response();

		$api_url = SSP_CASTOS_APP_URL . $api_url;

		$this->logger->log( sprintf( 'Sending %s request to: ', $method ), compact( 'api_url', 'args', 'method' ) );

		// Some endpoints ask for token, some - for api_token. Let's provide both.
		$default_args = array(
			'token'     => $token,
			'api_token' => $token,
		);

		$body = array_merge( $default_args, $args );

		$app_response = wp_remote_request(
			$api_url,
			array(
				'timeout' => self::TIMEOUT,
				'method'  => $method,
				'body'    => $body,
			)
		);

		$this->logger->log( 'Response:', $app_response );

		if ( is_wp_error( $app_response ) ) {
			$this->logger->log( 'Response error: ' . $app_response->get_error_message() );
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );

			return null;
		}

		$res = json_decode( wp_remote_retrieve_body( $app_response ), true );
		$res = is_array( $res ) ? $res : array();

		if ( isset( $app_response['response'] ) && is_array( $app_response['response'] ) ) {
			$res = array_merge( $app_response['response'], $res );
		}

		return $res;
	}


	/**
	 * Get series data for Castos.
	 *
	 * @param int $series_id
	 *
	 * @return array
	 */
	public function generate_series_data_for_castos( $series_id ) {

		$podcast = array();

		// Podcast title
		$title = ssp_get_option( 'data_title', get_bloginfo( 'name' ) );

		$series_title = ssp_get_option( 'data_title', '', $series_id );
		if ( $series_title ) {
			$title = $series_title;
		}
		$podcast['podcast_title'] = $title;

		// Podcast description
		$description        = ssp_get_option( 'data_description', get_bloginfo( 'description' ) );
		$series_description = ssp_get_option( 'data_description', '', $series_id );
		if ( $series_description ) {
			$description = $series_description;
		}
		$podcast_description            = mb_substr( wp_strip_all_tags( $description ), 0, 3999 );
		$podcast['podcast_description'] = $podcast_description;

		// Podcast author
		$author        = ssp_get_option( 'data_author', get_bloginfo( 'name' ) );
		$series_author = ssp_get_option( 'data_author', '', $series_id );
		if ( $series_author ) {
			$author = $series_author;
		}
		$podcast['author_name'] = $author;

		// Podcast owner name
		$owner_name        = ssp_get_option( 'data_owner_name', get_bloginfo( 'name' ) );
		$series_owner_name = ssp_get_option( 'data_owner_name', '', $series_id );
		if ( $series_owner_name ) {
			$owner_name = $series_owner_name;
		}
		$podcast['podcast_owner'] = $owner_name;

		// Podcast owner email address
		$owner_email        = ssp_get_option( 'data_owner_email', get_bloginfo( 'admin_email' ) );
		$series_owner_email = ssp_get_option( 'data_owner_email', '', $series_id );
		if ( $series_owner_email ) {
			$owner_email = $series_owner_email;
		}
		$podcast['owner_email'] = $owner_email;

		// Podcast explicit setting
		$explicit_option = ssp_get_option( 'ss_podcasting_explicit', '', $series_id );
		if ( 'on' === $explicit_option ) {
			$podcast['explicit'] = 1;
		} else {
			$podcast['explicit'] = 0;
		}

		// Podcast language
		$language        = ssp_get_option( 'data_language', get_bloginfo( 'language' ) );
		$series_language = ssp_get_option( 'data_language', '', $series_id );
		if ( $series_language ) {
			$language = $series_language;
		}
		$podcast['language'] = $language;

		// Podcast cover image
		$image        = ssp_get_option( 'data_image' );
		$series_image = ssp_get_option( 'data_image', 'no-image', $series_id );
		if ( 'no-image' !== $series_image ) {
			$image = $series_image;
		}
		$podcast['cover_image'] = $image;

		// Podcast copyright string
		$copyright        = ssp_get_option( 'data_copyright', '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) );
		$series_copyright = ssp_get_option( 'data_copyright', '', $series_id );
		if ( $series_copyright ) {
			$copyright = $series_copyright;
		}
		$podcast['copyright'] = $copyright;

		// Podcast Categories
		$itunes_category1            = ssp_get_feed_category_output( 1, $series_id );
		$itunes_category2            = ssp_get_feed_category_output( 2, $series_id );
		$itunes_category3            = ssp_get_feed_category_output( 3, $series_id );
		$podcast['itunes_category1'] = $itunes_category1['category'];
		$podcast['itunes_category2'] = $itunes_category2['category'];
		$podcast['itunes_category3'] = $itunes_category3['category'];
		$podcast['itunes']           = ssp_get_option( 'itunes_url', '', $series_id );
		$podcast['google_play']      = ssp_get_option( 'google_play_url', '', $series_id );
		$guid                        = ssp_get_option( 'data_guid', '', $series_id );

		if ( $guid ) {
			$podcast['guid'] = $guid;
		}

		$itunes_type = ssp_get_option( 'consume_order', '', $series_id );
		if ( $itunes_type ) {
			$podcast['itunes_type'] = $itunes_type;
		}

		return $podcast;
	}
}
