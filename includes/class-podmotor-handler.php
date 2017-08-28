<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Require SDK
 */
require_once( SSP_PLUGIN_PATH . 'includes/aws-sdk-2.0/aws-autoloader.php' );

use Aws\S3\S3Client;

class Podmotor_Handler {
	
	private $podmotor_config = array();
	private $podmotor_bucket = '';
	private $podmotor_show_slug = '';
	public $podmotor_client = null;
	
	public $response = array();
	
	/**
	 * Podmotor_Handler constructor.
	 */
	public function __construct() {
		$podmotor_account_id = get_option( 'ss_podcasting_podmotor_account_id', '' );
		if ( ! empty( $podmotor_account_id ) ) {
			$this->init_podmotor_client();
		}
	}
	
	/**
	 * Sets up the PodcastMotor AWS client
	 */
	private function init_podmotor_client() {
		$podmotor_account_id      = get_option( 'ss_podcasting_podmotor_account_id', '' );
		$podmotor_account_email   = get_option( 'ss_podcasting_podmotor_account_email', '' );
		$response                 = $this->get_podmotor_bucket_credentials( $podmotor_account_id, $podmotor_account_email );
		$this->podmotor_config    = $response['config'];
		$this->podmotor_bucket    = $response['bucket'];
		$this->podmotor_show_slug = $response['show_slug'];
		$this->podmotor_client    = S3Client::factory( $this->podmotor_config );
		
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
	 * Get the Handler credentials from the Seriously Simple Hosting API
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
	 * Connect to Seriously Simple Hosting API and validate API credentials
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
		
		$api_url      = SSP_PODMOTOR_APP_URL . 'api/users/validate';
		
		ssp_debug( 'Validate Credentials : API URL', $api_url );
		
		$app_response = wp_remote_get( $api_url, array(
				'timeout' => 45,
				'body'    => array(
					'api_token' => $podmotor_account_api_token,
					'email'     => $podmotor_account_email,
				),
			)
		);
		
		ssp_debug( 'Validate Credentials : App Response', $app_response );
		
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
	 * Get the duration of a local file before uploading it to S3
	 *
	 * @param $file
	 *
	 * @return bool|false|string
	 */
	public function get_local_file_duration( $file ) {
		$duration = false;
		if ( $file ) {
			// Include media functions if necessary
			if ( ! function_exists( 'wp_read_audio_metadata' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			$data = wp_read_audio_metadata( $file );

			if ( $data ) {
				if ( isset( $data['length_formatted'] ) && strlen( $data['length_formatted'] ) > 0 ) {
					$duration = $data['length_formatted'];
				} else {
					if ( isset( $data['length'] ) && strlen( $data['length'] ) > 0 ) {
						$duration = gmdate( 'H:i:s', $data['length'] );
					}
				}
			}
		}
		return $duration;
	}
	
	/**
	 * Takes an external file and downloads it to the server
	 * then uploads it to the PodcastMotor offsite storage
	 *
	 * @param string $remote_file
	 *
	 * @return bool|mixed
	 */
	public function upload_file_from_external_source( $remote_file = '' ) {
		$this->setup_response();
		if ( empty( $remote_file ) ) {
			$this->update_response( 'message', 'The remote file url is empty' );
		}
		$downloaded_file = ssp_download_remote_file( $remote_file );
		if ( $downloaded_file ) {
			$podmotor_response = $this->upload_file_to_podmotor_storage( $downloaded_file );
			if ( 'success' === $podmotor_response['status'] ) {
				$this->update_response( 'status', 'success' );
				$this->update_response( 'message', 'Remote file uploaded to Seriously Simple Hosting' );
				$this->update_response( 'podmotor_file', $podmotor_response['podmotor_file'] );
			} else {
				$this->update_response( 'message', 'An error occurred uploading the file to Seriously Simple Hosting' );
			}
			@unlink( $downloaded_file );
		} else {
			$this->update_response( 'message', 'An error occurred downloading the remote file' );
		}
		
		return $this->response;
	}
	
	/**
	 * Upload a local file to PodcastMotor offsite storage
	 *
	 * @param $file
	 *
	 * @return array
	 */
	public function upload_file_to_podmotor_storage( $file, $podcast_id = 0 ) {
		$this->setup_response();
		if ( ! is_file( $file ) ) {
			$this->update_response( 'message', 'File to upload is not valid' );
		} else {
			$duration = $this->get_local_file_duration( $file );
			$file_info      = pathinfo( $file );
			$base_file_name = $file_info['basename'];
			try {
				$result = $this->podmotor_client->putObject( array(
					'Bucket'       => $this->podmotor_bucket,
					'Key'          => $this->podmotor_show_slug . '/' . sanitize_file_name( $base_file_name ),
					'Body'         => fopen( $file, 'r' ),
					'ACL'          => 'public-read',
					'StorageClass' => 'REDUCED_REDUNDANCY',
				) );
				$podmotor_uploaded_file = $result['ObjectURL'];
				if ( ! empty( $podmotor_uploaded_file ) ) {
					$this->update_response( 'status', 'success' );
					$this->update_response( 'message', 'File uploaded to Seriously Simple Hosting successfully' );
					$this->update_response( 'podmotor_file', $podmotor_uploaded_file );
					if ( $duration ) {
						$this->update_response( 'podmotor_file_duration', $duration );
					}
					if ( ! empty( $podcast_id ) && $duration ) {
						$current_duration = get_post_meta( $podcast_id, 'duration', true );
						if ( empty( $current_duration ) ) {
							update_post_meta( $podcast_id, 'duration', $duration );
						}
					}
					$this->clear_local_podmotor_file( $file );
				} else {
					$this->update_response( 'message', 'An error occured uploading the file to Seriously Simple Hosting' );
				}
			} catch ( Exception $e ) {
				ssp_debug( 'An unknown error occurred uploading the file to S3:' . $e->getMessage() );
				$this->update_response( 'message', 'An unknown error occurred:' . $e->getMessage() );
			}
		}
		
		return $this->response;
	}
	
	/**
	 * Upload PodcastMotor file stored in offsite hosting to Seriously Simple Hosting database
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
		$api_url                    = SSP_PODMOTOR_APP_URL . 'api/file';
		ssp_debug($api_url);
		$podmotor_api_token         = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
		ssp_debug($podmotor_api_token);
		$post_body                  = array(
			'api_token'          => $podmotor_api_token,
			'podmotor_file_path' => $podmotor_file_path,
		);
		ssp_debug($post_body);
		$app_response               = wp_remote_post( $api_url, array(
				'timeout' => 45,
				'body'    => $post_body,
			)
		);
		ssp_debug($app_response);
		if ( ! is_wp_error( $app_response ) ) {
			$response_object = json_decode( wp_remote_retrieve_body( $app_response ) );
			if ( ! empty( $response_object ) ) {
				if ( 'success' == $response_object->status ) {
					$this->update_response( 'status', 'success' );
					$this->update_response( 'message', 'File successfully uploaded.' );
					$this->update_response( 'file_id', $response_object->file_id );
					$this->update_response( 'file_path', $response_object->file_path );
					$this->update_response( 'file_duration', $response_object->file_duration );
				} else {
					$this->update_response( 'message', 'An error occurred uploading the file data to Seriously Simple Hosting' );
				}
			} else {
				$this->update_response( 'message', 'An unknown error occurred uploading the file data to Seriously Simple Hosting' );
			}
		} else {
			$this->update_response( 'message', $app_response->get_error_message() );
		}
		
		return $this->response;
	}
	
	/**
	 * Clear out the file uploaded locally
	 *
	 * @param string $podmotor_file_path
	 */
	public function clear_local_podmotor_file( $podmotor_file_path = '' ) {
		$file_info          = pathinfo( $podmotor_file_path );
		$file_to_be_deleted = ssp_get_upload_directory() . $file_info['basename'];
		ssp_debug( $file_to_be_deleted );
		if ( is_file( $file_to_be_deleted ) ) {
			unlink( $file_to_be_deleted );
		}
	}
	
	/**
	 * Upload Podcast episode data to Seriously Simple Hosting
	 * Should only happen once the file has been uploaded to Seriously Simple Hosting Storage
	 *
	 * @param $post
	 *
	 * @return bool
	 */
	public function upload_podcast_to_podmotor( $post ) {
		
		$this->setup_response();
		
		if ( empty( $post ) ) {
			$this->update_response( 'message', 'Invalid Podcast data' );
			
			return $this->response;
		}
		
		/**
		 * Don't trigger this unless we have a valid PodcastMotor file id
		 */
		$podmotor_file_id = get_post_meta( $post->ID, 'podmotor_file_id', true );
		
		if ( empty( $podmotor_file_id ) ) {
			$this->update_response( 'message', 'Invalid Podcast file data' );
			return $this->response;
		}
		
		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
		
		$api_url = SSP_PODMOTOR_APP_URL . 'api/episode';
		
		$post_body = array(
			'api_token'    => $podmotor_api_token,
			'post_id'      => $post->ID,
			'post_title'   => $post->post_title,
			'post_content' => $post->post_content,
			'post_date'    => $post->post_date,
			'file_id'      => $podmotor_file_id
		);
		
		$podmotor_episode_id = get_post_meta( $post->ID, 'podmotor_episode_id', true );
		
		if ( ! empty( $podmotor_episode_id ) ) {
			$post_body['id'] = $podmotor_episode_id;
		}
		
		$app_response = wp_remote_post( $api_url, array(
				'timeout' => 45,
				'body'    => $post_body,
			)
		);
		
		if ( ! is_wp_error( $app_response ) ) {
			$responseObject = json_decode( wp_remote_retrieve_body( $app_response ) );
			if ( 'success' == $responseObject->status ) {
				$this->update_response( 'status', 'success' );
				$this->update_response( 'message', 'Pocast episode successfully uploaded to Seriously Simple Hosting' );
				$this->update_response( 'episode_id', $responseObject->episode_id );
			} else {
				$this->update_response( 'message', 'An error occurred uploading the episode data to Seriously Simple Hosting' );
			}
		} else {
			// $todo this should be logged somewhere
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );
		}
		
		return $this->response;
	}
	
	/**
	 * Upload Podcasts episode data to Seriously Simple Hosting
	 *
	 * @param $podcast_data array of post values
	 *
	 * @return bool
	 */
	public function upload_podcasts_to_podmotor( $podcast_data ) {
		
		$this->setup_response();
		
		if ( empty( $podcast_data ) ) {
			$this->update_response( 'message', 'Invalid Podcast data' );
			
			return $this->response;
		}
		
		$podcast_data_json = wp_json_encode( $podcast_data );
		
		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
		
		$api_url = SSP_PODMOTOR_APP_URL . 'api/import_episodes';
		
		$post_body = array(
			'api_token'    => $podmotor_api_token,
			'podcast_data' => $podcast_data_json,
		);
		
		ssp_debug( $post_body );
		
		$app_response = wp_remote_post( $api_url, array(
				'timeout' => 45,
				'body'    => $post_body,
			)
		);
		
		ssp_debug($app_response);
		
		if ( ! is_wp_error( $app_response ) ) {
			$responseObject = json_decode( wp_remote_retrieve_body( $app_response ) );
			if ( 'success' == $responseObject->status ) {
				$this->update_response( 'status', 'success' );
				$this->update_response( 'message', 'Pocast episode data successfully uploaded to Seriously Simple Hosting' );
			} else {
				$this->update_response( 'message', 'An error occurred uploading the episode data to Seriously Simple Hosting' );
			}
		} else {
			// $todo this should be logged somewhere
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );
		}
		
		return $this->response;
	}
	
	/**
	 * Creates the podcast import queue with Seriously Simple Hosting
	 *
	 * @param $post
	 *
	 * @return bool
	 */
	public function insert_podmotor_queue() {
		
		$this->setup_response();
		
		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
		ssp_debug($podmotor_api_token);
		
		$api_url = SSP_PODMOTOR_APP_URL . 'api/insert_queue';
		ssp_debug($api_url);
		
		$post_body = array(
			'api_token'   => $podmotor_api_token,
			'site_name'   => get_bloginfo( 'name' ),
			'site_action' => add_query_arg( 'podcast_importer', 'true', trailingslashit( site_url() ) ),
		);
		ssp_debug($post_body);
		
		$app_response = wp_remote_post( $api_url, array(
				'timeout' => 45,
				'body'    => $post_body,
			)
		);
		ssp_debug($app_response);
		
		if ( ! is_wp_error( $app_response ) ) {
			$responseObject = json_decode( wp_remote_retrieve_body( $app_response ) );
			ssp_debug( $responseObject );
			
			if ( 'success' == $responseObject->status ) {
				$this->update_response( 'status', $responseObject->status );
				$this->update_response( 'message', $responseObject->message );
				$this->update_response( 'queue_id', $responseObject->queue_id );
			} else {
				$this->update_response( 'message', 'An error occurred uploading the episode data to Seriously Simple Hosting' );
			}
		} else {
			// @todo this should be logged somewhere
			$this->update_response( 'message', 'An unknown error occurred: ' . $app_response->get_error_message() );
		}
		
		return $this->response;
		
	}
}