<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Podping_Handler;
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
	 * @param CPT_Podcast_Handler $cpt_podcast_handler
	 * @param Castos_Handler $castos_handler
	 * @param Admin_Notifications_Handler $admin_notices_handler
	 */
	public function __construct(
		$cpt_podcast_handler,
		$castos_handler,
		$admin_notices_handler,
		$podping_handler
	) {
		$this->cpt_podcast_handler   = $cpt_podcast_handler;
		$this->castos_handler        = $castos_handler;
		$this->admin_notices_handler = $admin_notices_handler;
		$this->podping_handler       = $podping_handler;


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

		// Update podcast details to Castos when a post is updated or saved
		add_action( 'save_post', array( $this, 'update_podcast_details' ), 20, 2 );

		// Clear the cache on post save.
		add_action( 'save_post', array( $this, 'invalidate_cache' ), 10, 2 );

		// Notify Podping if new episode has been published, or if new series is assigned to the episode
		add_action( 'wp_after_insert_post', array( $this, 'notify_podping' ), 10, 4 );
		add_action( 'added_term_relationship', array( $this, 'notify_podping_on_series_added' ), 10, 3 );

		// Delete podcast from Castos
		add_action( 'trashed_post', array( $this, 'delete_post' ), 11, 1 );

		// Episode edit screen.
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		// Episodes list table.
		add_filter( 'manage_edit-' . $this->token . '_columns', array(
			$this,
			'register_custom_column_headings',
		), 10, 1 );
		add_action( 'manage_posts_custom_column', array( $this, 'register_custom_columns' ), 10, 2 );
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
		if ( 'series' !== $taxonomy ) {
			return false;
		}

		$post = get_post( $post_id );

		if ( 'publish' !== $post->post_status ) {
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

		$is_just_published = 'publish' !== $post_before->post_status && 'publish' === $post->post_status;

		if ( ! $is_just_published ) {
			return;
		}

		if ( ! in_array( $post->post_type, ssp_post_types( true ), true ) ) {
			return;
		}

		$series_terms = wp_get_post_terms( $post->ID, 'series' );
		$feed_urls    = array();

		/**
		 * Episode can belong to multiple series feeds, so let's notify all of them.
		 * If episode doesn't belong to any series, it belongs to the main feed.
		 * */
		if ( is_array( $series_terms ) && $series_terms ) {
			// This is the case when episode with series was saved first as draft and then published.
			foreach ( $series_terms as $term ) {
				/**
				 * @var \WP_Term $term
				 * */
				$feed_urls[] = ssp_get_feed_url( $term->slug );
			}
		} else {
			// This is the case when a new episode without series was published.
			$feed_urls[] = ssp_get_feed_url();
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

		/**
		 * Only trigger this when the post type is podcast
		 */
		if ( ! in_array( $post->post_type, ssp_post_types( true ), true ) ) {
			return;
		}

		/**
		 * Don't trigger this if we're not connected to Podcast Motor
		 */
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}

		$this->castos_handler->delete_podcast( $post );

		delete_post_meta( $post_id, 'podmotor_file_id' );
		delete_post_meta( $post_id, 'podmotor_episode_id' );
	}


	/**
	 * Prevents copying some podcast meta fields
	 */
	public function prevent_copy_meta() {
		add_action( 'wp_insert_post', function ( $post_id, $post, $update ) {
			if ( $update || $this->token != $post->post_type ) {
				return;
			}

			// All the main copy plugins use redirection after creating the post and it's meta
			add_filter( 'wp_redirect', function ( $location ) use ( $post_id ) {
				$exclusions = [
					'podmotor_file_id',
					'podmotor_episode_id',
					'audio_file',
					'enclosure'
				];

				foreach ( $exclusions as $exclusion ) {
					delete_post_meta( $post_id, $exclusion );
				}

				return $location;
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
	 * Save episode meta box content
	 *
	 * @param integer $post_id ID of post
	 * @param \WP_Post $post
	 *
	 * @return mixed
	 */
	public function meta_box_save( $post_id, $post ) {
		global $ss_podcasting;

		$podcast_post_types = ssp_post_types( true );

		// Post type check
		if ( ! in_array( get_post_type(), $podcast_post_types ) ) {
			return false;
		}

		// Security check
		if ( ! isset( $_POST[ 'seriouslysimple_' . $this->token . '_nonce' ] ) || ! ( isset( $_POST[ 'seriouslysimple_' . $this->token . '_nonce' ] ) && wp_verify_nonce( $_POST[ 'seriouslysimple_' . $this->token . '_nonce' ], plugin_basename( $this->dir ) ) ) ) {
			return $post_id;
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

		$enclosure = '';

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
			}

			update_post_meta( $post_id, $k, $val );
		}

		if ( $enclosure ) {

			if ( get_post_meta( $post_id, 'date_recorded', true ) == '' ) {
				update_post_meta( $post_id, 'date_recorded', $post->post_date );
			}

			if ( ! ssp_is_connected_to_castos() ) {
				// Get file duration
				if ( get_post_meta( $post_id, 'duration', true ) == '' ) {
					$duration = $ss_podcasting->get_file_duration( $enclosure );
					if ( $duration ) {
						update_post_meta( $post_id, 'duration', $duration );
					}
				}

				// Get file size
				if ( get_post_meta( $post_id, 'filesize', true ) == '' ) {
					$filesize = $ss_podcasting->get_file_size( $enclosure );
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

		$html = '';

		$html .= '<input type="hidden" name="seriouslysimple_' . $this->token . '_nonce" id="seriouslysimple_' . $this->token . '_nonce" value="' . wp_create_nonce( plugin_basename( $this->dir ) ) . '" />';

		if ( 0 < count( $field_data ) ) {

			$html .= '<input id="seriouslysimple_post_id" type="hidden" value="' . $post_id . '" />';

			foreach ( $field_data as $k => $v ) {
				$data  = $v['default'];
				$saved = get_post_meta( $post_id, $k, true );
				if ( $saved ) {
					$data = $saved;
				}

				$class = '';
				if ( isset( $v['class'] ) ) {
					$class = $v['class'];
				}

				$disabled = false;
				if ( isset( $v['disabled'] ) && $v['disabled'] ) {
					$disabled = true;
				}

				switch ( $v['type'] ) {
					case 'file':
						$upload_button = '<input type="button" class="button" id="upload_' . esc_attr( $k ) . '_button" value="' . __( 'Upload File', 'seriously-simple-podcasting' ) . '" data-uploader_title="' . __( 'Choose a file', 'seriously-simple-podcasting' ) . '" data-uploader_button_text="' . __( 'Insert podcast file', 'seriously-simple-podcasting' ) . '" />';

						$html .= '<p>
									<label class="ssp-episode-details-label" for="' . esc_attr( $k ) . '">' . wp_kses_post( $v['name'] ) . '</label>';


						$html .= '<input name="' . esc_attr( $k ) . '" type="text" id="upload_' . esc_attr( $k ) . '" value="' . esc_attr( $data ) . '" />
									' . $upload_button . '
									<br/>
									<span class="description">' . wp_kses_post( $v['description'] ) . '</span>
								</p>' . "\n";
						break;
					case 'episode_file':
						$upload_button = '<input type="button" class="button" id="upload_' . esc_attr( $k ) . '_button" value="' . __( 'Upload File', 'seriously-simple-podcasting' ) . '" data-uploader_title="' . __( 'Choose a file', 'seriously-simple-podcasting' ) . '" data-uploader_button_text="' . __( 'Insert podcast file', 'seriously-simple-podcasting' ) . '" />';
						if ( ssp_is_connected_to_castos() ) {
							$upload_button = '<div id="ssp_upload_container" style="display: inline;">';
							$upload_button .= '  <button id="ssp_select_file" href="javascript:">Select file</button>';
							$upload_button .= '</div>';
						}

						$html .= '<p>
									<label class="ssp-episode-details-label" for="' . esc_attr( $k ) . '">' . wp_kses_post( $v['name'] ) . '</label>';

						if ( ssp_is_connected_to_castos() ) {
							$html .= '<div id="ssp_upload_notification">' . __( 'An error has occurred with the file upload functionality. Please check your site for any plugin or theme conflicts.', 'seriously-simple-podcasting' ) . '</div>';
						}

						$html .= '<input name="' . esc_attr( $k ) . '" type="text" id="upload_' . esc_attr( $k ) . '" value="' . esc_attr( $data ) . '" />
									' . $upload_button . '
									<br/>
									<span class="description">' . wp_kses_post( $v['description'] ) . '</span>
								</p>' . "\n";
						break;
					case 'image':
						$html .= '<p>
									<span class="ssp-episode-details-label">' . wp_kses_post( $v['name'] ) . '</span><br/>
									<img id="' . esc_attr( $k ) . '_preview" src="' . esc_attr( $data ) . '" style="max-width:200px;height:auto;margin:20px 0;" />
									<br/>
									<input id="' . esc_attr( $k ) . '_button" type="button" class="button" value="' . __( 'Upload new image', 'seriously-simple-podcasting' ) . '" />
									<input id="' . esc_attr( $k ) . '_delete" type="button" class="button" value="' . __( 'Remove image', 'seriously-simple-podcasting' ) . '" />
									<input id="' . esc_attr( $k ) . '" type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $data ) . '"/>
									<br/>
									<span class="description">' . wp_kses_post( $v['description'] ) . '</span>
								<p/>' . "\n";
						break;
					case 'checkbox':
						$html .= '<p><input name="' . esc_attr( $k ) . '" type="checkbox" class="' . esc_attr( $class ) . '" id="' . esc_attr( $k ) . '" ' . checked( 'on', $data, false ) . ' /> <label for="' . esc_attr( $k ) . '"><span>' . wp_kses_post( $v['description'] ) . '</span></label></p>' . "\n";
						break;

					case 'radio':
						$html .= '<p>
									<span class="ssp-episode-details-label">' . wp_kses_post( $v['name'] ) . '</span><br/>';
						foreach ( $v['options'] as $option => $label ) {
							$html .= '<input style="vertical-align: bottom;" name="' . esc_attr( $k ) . '" type="radio" class="' . esc_attr( $class ) . '" id="' . esc_attr( $k ) . '_' . esc_attr( $option ) . '" ' . checked( $option, $data, false ) . ' value="' . esc_attr( $option ) . '" />
										<label style="margin-right:10px;" for="' . esc_attr( $k ) . '_' . esc_attr( $option ) . '">' . esc_html( $label ) . '</label>' . "\n";
						}
						$html .= '<span class="description">' . wp_kses_post( $v['description'] ) . '</span>
								</p>' . "\n";
						break;

					case 'select':
						$html .= '<p>
									<span class="ssp-episode-details-label">' . wp_kses_post( $v['name'] ) . '</span><br/>';
						$html .= '<select name="' . esc_attr( $k ) . '" class="' . esc_attr( $class ) . '" id="' . esc_attr( $k ) . '_' . esc_attr( $option ) . '">';
						foreach ( $v['options'] as $option => $label ) {
							$html .= '<option ' . selected( $option, $data, false ) . ' value="' . esc_attr( $option ) . '">' . esc_attr( $label ) . '</option>';
						}
						$html .= '</select>';
						$html .= '<span class="description">' . wp_kses_post( $v['description'] ) . '</span>
								</p>' . "\n";
						break;

					case 'datepicker':
						$display_date = '';
						if ( $data ) {
							$display_date = date( 'j F, Y', strtotime( $data ) );
						}
						$html .= '<p class="hasDatepicker">
									<label class="ssp-episode-details-label" for="' . esc_attr( $k ) . '_display">' . wp_kses_post( $v['name'] ) . '</label>
									<br/>
									<input type="text" id="' . esc_attr( $k ) . '_display" class="ssp-datepicker ' . esc_attr( $class ) . '" value="' . esc_attr( $display_date ) . '" />
									<input name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" type="hidden" value="' . esc_attr( $data ) . '" />
									<br/>
									<span class="description">' . wp_kses_post( $v['description'] ) . '</span>
								</p>' . "\n";
						break;

					case 'textarea':
						ob_start();
						echo '<p><label class="ssp-episode-details-label" for="' . esc_attr( $k ) . '">' . wp_kses_post( $v['name'] ) . '</label><br/>';
						wp_editor( $data, $k, array( 'editor_class' => esc_attr( $class ) ) );
						echo '<br/><span class="description">' . wp_kses_post( $v['description'] ) . '</span></p>' . "\n";
						$html .= ob_get_clean();

						break;

					case 'hidden':
						$html .= '<p>
									<input name="' . esc_attr( $k ) . '" type="hidden" id="' . esc_attr( $k ) . '" value="' . esc_attr( $data ) . '" />
								</p>' . "\n";
						break;

					case 'number':
						$html .= '<p>
									<label class="ssp-episode-details-label" for="' . esc_attr( $k ) . '">' . wp_kses_post( $v['name'] ) . '</label>
									<br/>
									<input name="' . esc_attr( $k ) . '" type="number" min="0" id="' . esc_attr( $k ) . '" class="' . esc_attr( $class ) . '" value="' . esc_attr( $data ) . '" />
									<br/>
									<span class="description">' . wp_kses_post( $v['description'] ) . '</span>
								</p>' . "\n";
						break;

					default:
						$html .= '<p>
									<label class="ssp-episode-details-label" for="' . esc_attr( $k ) . '">' . wp_kses_post( $v['name'] ) . '</label>
									<br/>
									<input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="' . esc_attr( $class ) . '" value="' . esc_attr( $data ) . '" />
									<br/>
									<span class="description">' . wp_kses_post( $v['description'] ) . '</span>
								</p>' . "\n";
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
	public function update_podcast_details( $id, $post ) {
		/**
		 * Don't trigger this if we're not connected to Castos
		 */
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}

		/**
		 * Only trigger this when the post type is podcast
		 */
		if ( ! in_array( $post->post_type, ssp_post_types( true ), true ) ) {
			return;
		}

		/**
		 * Don't trigger this when the post is trashed
		 */
		if ( 'trash' === $post->post_status ) {
			return;
		}

		/**
		 * Only trigger this if the post is published or scheduled
		 */
		$disallowed_statuses = array( 'draft', 'pending', 'private', 'trash', 'auto-draft' );
		if ( in_array( $post->post_status, $disallowed_statuses, true ) ) {
			return;
		}

		/**
		 * Don't trigger this unless we have a valid castos file id
		 */
		$file_id = get_post_meta( $post->ID, 'podmotor_file_id', true );
		if ( empty( $file_id ) ) {
			return;
		}

		/**
		 * Don't trigger this if we've just updated the post
		 * This is because both actions we're hooking into get triggered in a post update
		 * So this is to prevent this method from being called twice during a post update.
		 */
		$cache_key     = 'ssp_podcast_updated';
		$podcast_saved = get_transient( $cache_key );
		if ( false !== $podcast_saved ) {
			delete_transient( $cache_key );

			return;
		}

		$response = $this->castos_handler->upload_episode_to_castos( $post );

		if ( 'success' === $response['status'] ) {
			set_transient( $cache_key, true, 30 );
			$podmotor_episode_id = $response['episode_id'];
			if ( $podmotor_episode_id ) {
				update_post_meta( $id, 'podmotor_episode_id', $podmotor_episode_id );
			}
			$this->admin_notices_handler->add_predefined_flash_notice(
				Admin_Notifications_Handler::NOTICE_API_EPISODE_SUCCESS
			);

			// if uploading was scheduled before, lets unschedule it
			delete_post_meta( $id, 'podmotor_schedule_upload' );
		} else {
			// schedule uploading with a cronjob
			update_post_meta( $id, 'podmotor_schedule_upload', true );
			$this->admin_notices_handler->add_predefined_flash_notice(
				Admin_Notifications_Handler::NOTICE_API_EPISODE_ERROR
			);
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
		$new_columns = apply_filters( 'ssp_admin_columns_episodes', array(
			'series' => __( 'Series', 'seriously-simple-podcasting' ),
			'image'  => __( 'Image', 'seriously-simple-podcasting' ),
		) );

		// remove date column
		unset( $defaults['date'] );

		// add new columns before last default one
		$columns = array_slice( $defaults, 0, - 1 ) + $new_columns + array_slice( $defaults, - 1 );

		return $columns;
	}

	/**
	 * Display column data in podcast list table
	 *
	 * @param string $column_name Name of current column
	 * @param integer $id ID of episode
	 *
	 * @return void
	 */
	public function register_custom_columns( $column_name, $id ) {
		global $ss_podcasting;

		switch ( $column_name ) {

			case 'series':
				$terms      = wp_get_post_terms( $id, 'series' );
				$term_names = wp_list_pluck( $terms, 'name' );
				echo join( ', ', $term_names );
				break;

			case 'image':
				$value = $ss_podcasting->get_image( $id, 40 );
				echo $value;
				break;

			default:
				break;

		}
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
