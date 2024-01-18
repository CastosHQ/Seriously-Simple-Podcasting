<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Entities\Castos_File_Data;
use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Podping_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This is controller for Podcast and other SSP post types (which are enabled via settings) custom behavior.
 *
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.13.0
 */
class Podcast_Post_Types_Controller {

	use Useful_Variables;


	/**
	 * @var CPT_Podcast_Handler
	 */
	protected $cpt_podcast_handler;

	/**
	 * @var Castos_Handler
	 * */
	protected $castos_handler;

	/**
	 * @var Admin_Notifications_Handler
	 */
	protected $admin_notices_handler;

	/**
	 * @var Podping_Handler
	 */
	protected $podping_handler;

	/**
	 * @var Episode_Repository
	 */
	protected $episode_repository;

	/**
	 * @var Series_Handler
	 */
	protected $series_handler;

	/**
	 * @param CPT_Podcast_Handler $cpt_podcast_handler
	 * @param Castos_Handler $castos_handler
	 * @param Admin_Notifications_Handler $admin_notices_handler
	 * @param Podping_Handler $podping_handler
	 * @param Episode_Repository $episode_repository
	 * @param Series_Handler $series_handler
	 */
	public function __construct(
		$cpt_podcast_handler,
		$castos_handler,
		$admin_notices_handler,
		$podping_handler,
		$episode_repository,
		$series_handler
	) {
		$this->cpt_podcast_handler   = $cpt_podcast_handler;
		$this->castos_handler        = $castos_handler;
		$this->admin_notices_handler = $admin_notices_handler;
		$this->podping_handler       = $podping_handler;
		$this->episode_repository    = $episode_repository;
		$this->series_handler        = $series_handler;

		$this->init_useful_variables();
		$this->register_hooks_and_filters();
	}

	/**
	 * Register all relevant front end hooks and filters
	 */
	protected function register_hooks_and_filters() {

		// Register podcast post type, taxonomies and meta fields.
		add_action( 'init', array( $this, 'register_post_type' ), 11 );

		// prevent copying some meta fields
		add_action( 'admin_init', array( $this, 'prevent_copy_meta' ) );

		// Episode meta box.
		add_action( 'admin_init', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'meta_box_save' ), 10, 2 );

		// Clear the cache on post save.
		add_action( 'save_post', array( $this, 'invalidate_cache' ), 10, 2 );

		// Update podcast details to Castos when a post is updated or saved
		add_action( 'save_post', array( $this, 'sync_episode' ), 20, 2 );

		// Assign default series if no series was specified
		add_action( 'save_post', array( $this, 'maybe_assign_default_series' ), 20 );

		// Notify Podping if new episode has been published, or if new series is assigned to the episode
		add_action( 'wp_after_insert_post', array( $this, 'notify_podping' ), 10, 4 );
		add_action( 'added_term_relationship', array( $this, 'notify_podping_on_series_added' ), 10, 3 );

		// Delete podcast from Castos
		add_action( 'trashed_post', array( $this, 'delete_post' ), 11 );

		// Episode edit screen.
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		// Episodes list table.
		add_action( 'admin_init', array( $this, 'add_custom_columns' ) );

		// Change the podcast episode statuses to sending after the sync has been triggered.
		add_action( 'ssp_triggered_podcast_sync', array( $this, 'update_podcast_episodes_status' ), 10, 2 );

		add_action( 'ssp_check_episode_sync_status', array( $this, 'maybe_update_sync_status' ), 10, 2 );
	}

	public function add_custom_columns(){
		$ssp_post_types = ssp_post_types();
		foreach ( $ssp_post_types as $post_type ) {
			add_filter( 'manage_edit-' . $post_type . '_columns', array( $this, 'register_custom_column_headings' ), 20, 2 );
		}
		add_action( 'manage_posts_custom_column', array( $this, 'manage_custom_columns' ), 10, 2 );
	}


	/**
	 * Register SSP_CPT_PODCAST post type
	 *
	 * @return void
	 */
	public function register_post_type() {
		$this->cpt_podcast_handler->register_post_type();
	}

	/**
	 * @param int $episode_id
	 * @param string $episode_status
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function maybe_update_sync_status( $episode_id, $episode_status ) {
		$series_id      = ssp_get_episode_series_id( $episode_id );
		$podcast_status = $this->series_handler->get_sync_status( $series_id );
		$syncing        = Sync_Status::SYNC_STATUS_SYNCING;

		// First, let's check if the podcast is still syncing and maybe update the status
		if ( $syncing === $podcast_status || ! $podcast_status ) {
			$sync_status = $this->castos_handler->get_podcast_sync_status( $series_id );
			$podcast_status = $sync_status->status;
			$this->series_handler->update_sync_status( $series_id, $podcast_status );
		}

		if ( $syncing == $episode_status && $podcast_status && $syncing != $podcast_status ) {
			// If the podcast status is synced_with_errors, and episode status is syncing, it's failed.
			$new_episode_status = Sync_Status::SYNC_STATUS_SYNCED_WITH_ERRORS === $podcast_status ?
				Sync_Status::SYNC_STATUS_FAILED : $podcast_status;
			$this->episode_repository->update_episode_sync_status( $episode_id, $new_episode_status );
		}
	}

	/**
	 * @param int $podcast_id
	 * @param array $response
	 */
	public function update_podcast_episodes_status( $podcast_id, $response ) {
		if ( isset( $response['code'] ) && 200 === $response['code'] ) {
			$episodes = $this->episode_repository->get_podcast_episodes( $podcast_id );

			foreach ( $episodes as $episode ) {
				$this->episode_repository->update_episode_sync_status(
					$episode->ID,
					Sync_Status::SYNC_STATUS_SYNCING
				);
			}
		}
	}

	/**
	 * This function fires in such cases:
	 *  - if a new episode with series is published
	 *  - if a new series is added to existing episode.
	 *
	 * @param int $post_id
	 * @param int $term_id
	 * @param string $taxonomy
	 *
	 * @return bool
	 */
	public function notify_podping_on_series_added( $post_id, $term_id, $taxonomy ) {

		$post = get_post( $post_id );

		if ( 'series' !== $taxonomy || 'publish' !== $post->post_status ) {
			return false;
		}

		if ( ! get_option( 'blog_public' ) || ! ssp_get_option( 'podping_notification', 'on', $term_id ) ) {
			return false;
		}

		// If this action was fired, it means that we don't need to fire notify_podping() anymore
		remove_action( 'wp_after_insert_post', array( $this, 'notify_podping' ) );

		if ( ! in_array( $post->post_type, ssp_post_types( true ), true ) ) {
			return false;
		}

		$term = get_term_by( 'id', $term_id, $taxonomy );

		if ( empty( $term->slug ) ) {
			return false;
		}

		$feed_url = ssp_get_feed_url( $term->slug );

		return $this->podping_handler->notify( $feed_url );
	}

	/**
	 * This function is needed for such cases:
	 *  - when a new episode without series is created
	 *  - when a new episode with series was first created as draft and then published.
	 * For all other cases, @see notify_podping_on_series_added()
	 *
	 * @param \WP_Post $post
	 */
	public function notify_podping( $post_id, $post, $update, $post_before ) {

		$is_just_published = isset( $post_before->post_status ) && isset( $post->post_status ) &&
		                     'publish' !== $post_before->post_status && 'publish' === $post->post_status;

		if ( ! $is_just_published ) {
			return;
		}

		if ( ! in_array( $post->post_type, ssp_post_types( true ), true ) || ! get_option( 'blog_public' ) ) {
			return;
		}

		$series_terms = wp_get_post_terms( $post->ID, 'series' );
		$feed_urls    = array();

		/**
		 * Episode can belong to multiple series feeds, so let's notify all of them.
		 * If episode doesn't belong to any series, it belongs to the main feed.
		 * @var \WP_Term[] $series_terms
		 * */
		if ( is_array( $series_terms ) && $series_terms ) {
			// This is the case when episode with series was saved first as draft and then published.
			foreach ( $series_terms as $term ) {
				$is_notification_enabled = ssp_get_option( 'podping_notification', 'on', $term->term_id );
				if ( $is_notification_enabled ) {
					$feed_urls[] = ssp_get_feed_url( $term->slug );
				}
			}
		} else {
			// This is the case when a new episode without series was published.
			$is_notification_enabled = ssp_get_option( 'podping_notification', 'on' );
			if ( $is_notification_enabled ) {
				$feed_urls[] = ssp_get_feed_url();
			}
		}

		foreach ( $feed_urls as $feed_url ) {
			$this->podping_handler->notify( $feed_url );
		}
	}


	/**
	 * Delete the podcast from Castos
	 *
	 * @param $post_id
	 */
	public function delete_post( $post_id ) {
		$post = get_post( $post_id );

		if ( ! in_array( $post->post_type, ssp_post_types(), true ) ) {
			return;
		}

		if ( ssp_is_connected_to_castos() ) {
			$this->castos_handler->delete_episode( $post );
		}

		$this->episode_repository->delete_sync_info( $post_id );
		$this->episode_repository->delete_audio_file( $post_id );
	}


	/**
	 * Prevents copying some podcast meta fields
	 */
	public function prevent_copy_meta() {
		add_action( 'wp_insert_post', function ( $post_id, $post, $update ) {
			if ( $update || $this->token != $post->post_type ) {
				return;
			}

			$remove_redundant_metas = function ( $post_id ){
				$exclusions = [
					'podmotor_file_id',
					'podmotor_episode_id',
					'audio_file',
					'enclosure'
				];

				foreach ( $exclusions as $exclusion ) {
					delete_post_meta( $post_id, $exclusion );
				}
			};

			// Most of the copy plugins use redirection after creating the post and it's meta
			add_filter( 'wp_redirect', function ( $location ) use ( $remove_redundant_metas, $post_id ) {
				$remove_redundant_metas( $post_id );

				return $location;
			} );

			// This is for Post Duplicator plugin
			add_action( 'mtphr_post_duplicator_created', function() use ( $remove_redundant_metas, $post_id ) {
				$remove_redundant_metas( $post_id );
			} );

		}, 10, 3 );
	}


	/**
	 * Register podcast episode details meta boxes
	 * @return void
	 */
	public function register_meta_boxes() {

		// Get all podcast post types
		$podcast_post_types = ssp_post_types( true );

		// Add meta box to each post type
		foreach ( (array) $podcast_post_types as $post_type ) {
			add_action( 'add_meta_boxes_' . $post_type, array( $this, 'meta_box_setup' ), 10, 1 );
		}
	}

	/**
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function maybe_assign_default_series( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$post = get_post( $post_id );
		if ( ! in_array( $post->post_type, ssp_post_types() ) ) {
			return;
		} elseif ( $post->post_type != SSP_CPT_PODCAST ) {
			// If it's a not a podcast post, make sure it has the enclosure file.
			if ( ! $this->episode_repository->get_enclosure( $post_id ) ) {
				return;
			}
		}

		$series_id = ssp_get_episode_series_id( $post_id, 0 );
		if ( ! $series_id ) {
			$default_series_id = ssp_get_default_series_id();
			wp_set_object_terms( $post_id, array( $default_series_id ), ssp_series_taxonomy() );
		}
	}

	/**
	 * Save episode meta box content
	 *
	 * @param integer $post_id ID of post
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public function meta_box_save( $post_id, $post ) {

		if ( ! $this->save_podcast_action_check( $post ) ) {
			return false;
		}

		// User capability check
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		$field_data = $this->custom_fields();

		$old_data = array();

		$enclosure = '';
		$old_enclosure = '';

		foreach ( $field_data as $k => $field ) {

			if ( 'embed_code' == $k ) {
				continue;
			}

			$val = '';
			if ( isset( $_POST[ $k ] ) ) {
				if ( isset( $field['callback'] ) ) {
					$val = call_user_func( $field['callback'], $_POST[ $k ] );
				} else {
					$val = strip_tags( trim( $_POST[ $k ] ) );
				}
			}

			if ( $k == 'audio_file' ) {
				$enclosure = $val;
				$old_enclosure = get_post_meta( $post_id, $k, true );
			}

			$old_data[ $k ] = get_post_meta( $post_id, $k, true );

			if ( $old_data[ $k ] !== $val ) {
				update_post_meta( $post_id, $k, $val );
			}
		}

		if ( $enclosure ) {

			$is_enclosure_updated = $old_enclosure !== $enclosure;

			if ( $is_enclosure_updated || get_post_meta( $post_id, 'date_recorded', true ) == '' ) {
				update_post_meta( $post_id, 'date_recorded', $post->post_date );
			}

			if ( ! ssp_is_connected_to_castos() ) {
				// Get file duration
				if ( $is_enclosure_updated || get_post_meta( $post_id, 'duration', true ) == '' ) {
					$duration = $this->episode_repository->get_file_duration( $enclosure );
					if ( $duration ) {
						update_post_meta( $post_id, 'duration', $duration );
					}
				}

				// Get file size
				if ( $is_enclosure_updated || get_post_meta( $post_id, 'filesize', true ) == '' ) {
					$filesize = $this->episode_repository->get_file_size( $enclosure );
					if ( $filesize ) {

						if ( isset( $filesize['formatted'] ) ) {
							update_post_meta( $post_id, 'filesize', $filesize['formatted'] );
						}

						if ( isset( $filesize['raw'] ) ) {
							update_post_meta( $post_id, 'filesize_raw', $filesize['raw'] );
						}

					}
				}
			}

			// Save podcast file to 'enclosure' meta field for standards-sake
			update_post_meta( $post_id, 'enclosure', $enclosure );
		}

		return true;
	}

	/**
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public function save_podcast_action_check( $post ){
		$podcast_post_types = ssp_post_types();

		// Post type check
		if (  ( 'trash' === $post->post_status ) || ! in_array( $post->post_type, $podcast_post_types ) ) {
			return false;
		}

		// Security check
		if ( ! isset( $_POST[ 'seriouslysimple_' . $this->token . '_nonce' ] ) ||
		     ! wp_verify_nonce( $_POST[ 'seriouslysimple_' . $this->token . '_nonce' ], plugin_basename( $this->dir ) )
		) {
			return false;
		}

		return true;
	}


	/**
	 * Create meta box on episode edit screen
	 * @return void
	 */
	public function meta_box_setup( $post ) {
		global $pagenow;
		add_meta_box( 'podcast-episode-data', __( 'Podcast Episode Details', 'seriously-simple-podcasting' ), array(
			$this,
			'meta_box_content'
		), $post->post_type, 'normal', 'high' );

		if ( 'post.php' == $pagenow && 'publish' == $post->post_status && function_exists( 'get_post_embed_html' ) ) {
			add_meta_box( 'episode-embed-code', __( 'Episode Embed Code', 'seriously-simple-podcasting' ), array(
				$this,
				'embed_code_meta_box_content'
			), $post->post_type, 'side', 'low' );
		}

		// Allow more metaboxes to be added
		do_action( 'ssp_meta_boxes', $post );

	}

	/**
	 * Get content for episode embed code meta box
	 *
	 * @param object $post Current post object
	 *
	 * @return void
	 */
	public function embed_code_meta_box_content( $post ) {

		// Get post embed code
		$embed_code = get_post_embed_html( 500, 350, $post );

		// Generate markup for meta box
		$html = '<p><em>' . __( 'Customise the size of your episode embed below, then copy the HTML to your clipboard.', 'seriously-simple-podcasting' ) . '</em></p>';
		$html .= '<p><label for="episode_embed_code_width">' . __( 'Width:', 'seriously-simple-podcasting' ) . '</label> <input id="episode_embed_code_width" class="episode_embed_code_size_option" type="number" value="500" length="3" min="0" step="1" /> &nbsp;&nbsp;&nbsp;&nbsp;<label for="episode_embed_code_height">' . __( 'Height:', 'seriously-simple-podcasting' ) . '</label> <input id="episode_embed_code_height" class="episode_embed_code_size_option" type="number" value="350" length="3" min="0" step="1" /></p>';
		$html .= '<p><textarea readonly id="episode_embed_code">' . esc_textarea( $embed_code ) . '</textarea></p>';

		echo $html;
	}

	/**
	 * Load content for episode meta box
	 *
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public function meta_box_content( $post ) {

		$post_id = $post->ID;

		$field_data = $this->custom_fields();

		$html = '<input type="hidden" name="seriouslysimple_' . $this->token . '_nonce" id="seriouslysimple_' . $this->token . '_nonce" value="' . wp_create_nonce( plugin_basename( $this->dir ) ) . '" />';

		if ( 0 < count( $field_data ) ) {

			$html .= '<input id="seriouslysimple_post_id" type="hidden" value="' . $post_id . '" />';
			$renderer = ssp_renderer();

			foreach ( $field_data as $k => $v ) {
				$data  = isset( $v['default'] ) ? $v['default'] : '';
				$saved = get_post_meta( $post_id, $k, true );
				if ( $saved ) {
					$data = $saved;
				}

				$class = '';
				if ( isset( $v['class'] ) ) {
					$class = $v['class'];
				}

				switch ( $v['type'] ) {
					case 'sync_status':
						try {
							$status = $this->episode_repository->get_episode_sync_status( $post_id );
						} catch ( \Exception $e ) {
							$status = Sync_Status::SYNC_STATUS_NONE;
						}
						$html .= $renderer->fetch( 'metafields/sync-status', compact( 'status' ) );
						break;

					case 'file':
						$html .= $renderer->fetch( 'metafields/file', compact( 'k', 'v', 'data' ) );
						break;

					case 'episode_file':
						$is_castos = ssp_is_connected_to_castos();
						$file_data = new Castos_File_Data(
							json_decode( get_post_meta( $post_id, 'castos_file_data', true ), true )
						);
						$html .= $renderer->fetch(
							'metafields/episode_file',
							compact( 'k', 'v', 'data', 'is_castos', 'file_data' )
						);
						break;

					case 'image':
						$label = $v['name'];
						$description = $v['description'];
						$html .= $renderer->fetch( 'metafields/image', compact( 'label', 'description', 'data', 'k' ) );
						break;

					case 'checkbox':
						$html .= $renderer->fetch( 'metafields/checkbox', compact( 'k', 'v', 'class', 'data' ) );
						break;

					case 'radio':
						$html .= $renderer->fetch( 'metafields/radio', compact( 'k', 'v', 'class', 'data' ) );
						break;

					case 'select':
						$html .= $renderer->fetch( 'metafields/select', compact( 'k', 'v', 'class', 'data' ) );
						break;

					case 'datepicker':
						$html .= $renderer->fetch( 'metafields/datepicker', compact( 'k', 'v', 'class', 'data' ) );
						break;

					case 'textarea':
						$html .= $renderer->fetch( 'metafields/textarea', compact( 'k', 'v', 'class', 'data' ) );
						break;

					case 'hidden':
						$html .= $renderer->fetch( 'metafields/hidden', compact( 'k', 'v', 'class', 'data' ) );
						break;

					case 'number':
						$html .= $renderer->fetch( 'metafields/number', compact( 'k', 'v', 'class', 'data' ) );
						break;

					default:
						$html .= $renderer->fetch( 'metafields/text', compact( 'k', 'v', 'class', 'data' ) );
						break;
				}

			}
		}

		echo $html;
	}

	/**
	 * Setup custom fields for episodes
	 * @return array Custom fields
	 */
	public function custom_fields() {
		return $this->cpt_podcast_handler->custom_fields();
	}

	/**
	 * Send the podcast details to Castos
	 *
	 * @param int $id
	 * @param \WP_Post $post
	 */
	public function sync_episode( $id, $post ) {
		/**
		 * Don't trigger this if we're not connected to Castos
		 */
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}

		if ( ! $this->save_podcast_action_check( $post ) ) {
			return;
		}

		/**
		 * Only trigger this if the post is published or scheduled
		 */
		$disallowed_statuses = array( 'draft', 'pending', 'private', 'trash', 'auto-draft' );
		if ( in_array( $post->post_status, $disallowed_statuses, true ) ) {
			return;
		}

		if ( empty( $post->ID ) || empty( $post->post_title ) ) {
			return;
		}

		/**
		 * Don't trigger this unless we have a valid castos file id
		 */
		$file_id = get_post_meta( $post->ID, 'podmotor_file_id', true );
		if ( empty( $file_id ) ) {
			return;
		}

		$response = $this->castos_handler->upload_episode_to_castos( $post );

		if ( 'success' === $response['status'] ) {
			$podmotor_episode_id = $response['episode_id'];
			if ( $podmotor_episode_id ) {
				update_post_meta( $id, 'podmotor_episode_id', $podmotor_episode_id );
			}
			$this->admin_notices_handler->add_predefined_flash_notice(
				Admin_Notifications_Handler::NOTICE_API_EPISODE_SUCCESS
			);

			// if uploading was scheduled before, lets unschedule it
			delete_post_meta( $id, 'podmotor_schedule_upload' );
			$this->episode_repository->update_episode_sync_status( $post->ID, Sync_Status::SYNC_STATUS_SYNCED );
			$this->episode_repository->delete_sync_error( $post->ID );
		} else {
			// Schedule uploading with a cronjob.1
			// If it's 404, something wrong with the file ID. We don't try to reupload it since result will be the same.
			if ( 404 != $response['code'] ) {
				update_post_meta( $id, 'podmotor_schedule_upload', true );
			}
			$this->admin_notices_handler->add_predefined_flash_notice(
				Admin_Notifications_Handler::NOTICE_API_EPISODE_ERROR
			);
			$this->episode_repository->update_episode_sync_status( $post->ID, Sync_Status::SYNC_STATUS_FAILED );
		}
	}

	/**
	 * Modify the 'enter title here' text
	 *
	 * @param string $title Default text
	 *
	 * @return string        Modified text
	 */
	public function enter_title_here( $title ) {
		if ( get_post_type() == $this->token ) {
			$title = __( 'Enter episode title here', 'seriously-simple-podcasting' );
		}

		return $title;
	}

	/**
	 * Create custom dashboard message
	 *
	 * @param array $messages Default messages
	 *
	 * @return array           Modified messages
	 */
	public function updated_messages( $messages ) {
		global $post, $post_ID;

		$messages[ $this->token ] = array(
			0  => '',
			1  => sprintf( __( 'Episode updated. %sView episode%s.', 'seriously-simple-podcasting' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
			2  => __( 'Custom field updated.', 'seriously-simple-podcasting' ),
			3  => __( 'Custom field deleted.', 'seriously-simple-podcasting' ),
			4  => __( 'Episode updated.', 'seriously-simple-podcasting' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Episode restored to revision from %s.', 'seriously-simple-podcasting' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( 'Episode published. %sView episode%s.', 'seriously-simple-podcasting' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
			7  => __( 'Episode saved.', 'seriously-simple-podcasting' ),
			8  => sprintf( __( 'Episode submitted. %sPreview episode%s.', 'seriously-simple-podcasting' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
			9  => sprintf( __( 'Episode scheduled for: %1$s. %2$sPreview episode%3$s.', 'seriously-simple-podcasting' ), '<strong>' . date_i18n( __( 'M j, Y @ G:i', 'seriously-simple-podcasting' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
			10 => sprintf( __( 'Episode draft updated. %sPreview episode%s.', 'seriously-simple-podcasting' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
		);

		return $messages;
	}

	/**
	 * Register columns for podcast list table
	 *
	 * @param array $defaults Default columns
	 *
	 * @return array           Modified columns
	 */
	public function register_custom_column_headings( $defaults ) {

		$columns_to_add = array();

		if ( ssp_is_connected_to_castos() ) {
			$columns_to_add['ssp_sync_status'] = __( 'Sync', 'seriously-simple-podcasting' );
		}

		$columns_to_add['ssp_cover'] = __( 'Cover', 'seriously-simple-podcasting' );

		$columns_to_add = apply_filters( 'ssp_admin_columns_episodes', $columns_to_add );

		$columns_to_unset = apply_filters( 'ssp_remove_admin_columns_episodes', array( 'comments' ) );

		// remove columns
		foreach ( $columns_to_unset as $column ) {
			if ( isset( $defaults[ $column ] ) ) {
				unset( $defaults[ $column ] );
			}
		}

		// add new columns before last default one
		$columns = array_slice( $defaults, 0, 1 ) + $columns_to_add + array_slice( $defaults, 1 );

		return $columns;
	}

	/**
	 * Display column data in podcast list table
	 *
	 * @param string $column_name Name of current column
	 * @param integer $post_id ID of episode
	 *
	 * @return void
	 */
	public function manage_custom_columns( $column_name, $post_id ) {
		if ( 0 !== strpos( $column_name, 'ssp_' ) ) {
			return;
		}

		switch ( $column_name ) {
			case 'ssp_cover':
				$value = ssp_episode_image( $post_id, 40 );
				$value = $value ?: '<span aria-hidden="true">—</span>';
				break;

			case 'ssp_sync_status':
				$status = $this->episode_repository->check_episode_sync_status( $post_id );
				$link   = get_edit_post_link( $post_id );
				$value  = ssp_renderer()->fetch( 'settings/sync-label', compact( 'status', 'link' ) );
				break;

			default:
				$value = '';
				break;
		}
		echo apply_filters( 'ssp_custom_column_value', $value, $column_name, $post_id );
	}

	/**
	 * Clear the cache on post save.
	 *
	 * @param int $id POST ID
	 * @param object $post WordPress Post Object
	 *
	 * @return void
	 */
	public function invalidate_cache( $id, $post ) {

		if ( in_array( $post->post_type, ssp_post_types( true ) ) ) {
			wp_cache_delete( 'episodes', 'ssp' );
			wp_cache_delete( 'episode_ids', 'ssp' );
		}

	}
}
