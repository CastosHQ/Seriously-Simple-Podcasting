<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler;
use SeriouslySimplePodcasting\Handlers\Roles_Handler;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Handlers\Upgrade_Handler;
use SeriouslySimplePodcasting\Ajax\Ajax_Handler;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;
use SeriouslySimplePodcasting\Renderers\Renderer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.0
 */
class Admin_Controller extends Controller {

	/**
	 * @var Ajax_Handler
	 */
	protected $ajax_handler;

	/**
	 * @var Upgrade_Handler
	 */
	protected $upgrade_handler;

	/**
	 * @var Admin_Notifications_Handler
	 */
	protected $admin_notices_handler;

	/**
	 * @var Feed_Controller
	 */
	protected $feed_controller;

	/**
	 * @var Onboarding_Controller
	 */
	protected $onboarding_controller;

	/**
	 * @var Log_Helper
	 * */
	protected $logger;

	/**
	 * @var Cron_Controller
	 */
	protected $cron_controller;

	/**
	 * @var CPT_Podcast_Handler
	 */
	protected $cpt_podcast_handler;

	/**
	 * @var Roles_Handler
	 */
	protected $roles_handler;


	/**
	 * Admin_Controller constructor.
	 *
	 * @param $file string main plugin file
	 * @param $version string plugin version
	 */
	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->bootstrap();
	}

	/**
	 * Set up all hooks and filters for this class
	 */
	public function bootstrap() {

		$this->ajax_handler = new Ajax_Handler();

		$this->upgrade_handler = new Upgrade_Handler();

		$this->feed_controller = new Feed_Controller( $this->file, $this->version );

		// Todo: dependency injection for other controllers as well
		$this->onboarding_controller = new Onboarding_Controller( $this->file, $this->version, new Renderer(), new Settings_Handler() );

		$this->roles_handler = new Roles_Handler();

		$this->cpt_podcast_handler = new CPT_Podcast_Handler( $this->roles_handler );

		$this->logger = new Log_Helper();

		$this->cron_controller = new Cron_Controller();

		if ( is_admin() ) {
			$this->admin_notices_handler = ( new Admin_Notifications_Handler( $this->token ) )->bootstrap();
		}

		// Handle localisation.
		$this->load_plugin_textdomain();

		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Regsiter podcast post type, taxonomies and meta fields.
		add_action( 'init', array( $this, 'register_post_type' ), 11 );

		// Setup custom permalink structures.
		add_action( 'init', array( $this, 'setup_permastruct' ), 10 );

		// Run any updates required
		add_action( 'init', array( $this, 'update' ), 11 );

		// Dismiss the categories update screen
		add_action( 'init', array( $this, 'dismiss_categories_update' ) ); //todo: can we move it to 'admin_init'?

		// Dismiss the categories update screen
		add_action( 'init', array( $this, 'disable_elementor_template_notice' ) );

		// Hide WP SEO footer text for podcast RSS feed.
		add_filter( 'wpseo_include_rss_footer', array( $this, 'hide_wp_seo_rss_footer' ) );

		// Delete podcast from Castos
		add_action( 'trashed_post', array( $this, 'delete_post' ), 11, 1 );

		if ( is_admin() ) {

			add_action( 'admin_init', array( $this, 'update_enclosures' ) );

			// process the import form submission
			add_action( 'admin_init', array( $this, 'submit_import_form' ) );

			// prevent copying some meta fields
			add_action( 'admin_init', array( $this, 'prevent_copy_meta' ) );

			// Episode meta box.
			add_action( 'admin_init', array( $this, 'register_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'meta_box_save' ), 10, 1 );

			// Update podcast details to Castos when a post is updated or saved
			add_action( 'save_post', array( $this, 'update_podcast_details' ), 20, 2 );

			// Episode edit screen.
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
			add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

			// Admin JS & CSS.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 10, 1 );

			// Episodes list table.
			add_filter( 'manage_edit-' . $this->token . '_columns', array(
				$this,
				'register_custom_column_headings',
			), 10, 1 );
			add_action( 'manage_posts_custom_column', array( $this, 'register_custom_columns' ), 10, 2 );

			// Series list table.
			add_filter( 'manage_edit-series_columns', array( $this, 'edit_series_columns' ) );
			add_filter( 'manage_series_custom_column', array( $this, 'add_series_columns' ), 1, 3 );

			// Series term meta forms
			add_action( 'series_add_form_fields', array( $this, 'add_series_term_meta_fields' ), 10, 2 );
			add_action( 'series_edit_form_fields', array( $this, 'edit_series_term_meta_fields' ), 10, 2 );
			add_action( 'created_series', array( $this, 'save_series_meta' ), 10, 2 );
			add_action( 'edited_series', array( $this, 'update_series_meta' ), 10, 2 );

			// Dashboard widgets.
			add_action( 'wp_dashboard_setup', array( $this, 'ssp_dashboard_setup' ) );
			add_filter( 'dashboard_glance_items', array( $this, 'glance_items' ), 10, 1 );

			// Appreciation links.
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );

			// Add footer text to dashboard.
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

			// Clear the cache on post save.
			add_action( 'save_post', array( $this, 'invalidate_cache' ), 10, 2 );

			// Check for, setup or ignore import of existing podcasts.
			add_action( 'admin_init', array( $this, 'ignore_importing_existing_podcasts' ) );

			// Filter Embed HTML Code
			add_filter( 'embed_html', array( $this, 'ssp_filter_embed_code' ), 10, 1 );

		} // End if().

		// Setup activation and deactivation hooks
		register_activation_hook( $this->file, array( $this, 'activate' ) );
		register_deactivation_hook( $this->file, array( $this, 'deactivate' ) );
	}

	public function ssp_filter_embed_code( $code ) {
		return str_replace( 'sandbox="allow-scripts"', 'sandbox="allow-scripts allow-same-origin"', $code );
	}

	/**
	 * Setup custom permalink structures
	 * @return void
	 */
	public function setup_permastruct() {

		// Episode download & player URLs
		add_rewrite_rule( '^podcast-download/([^/]*)/([^/]*)/?', 'index.php?podcast_episode=$matches[1]', 'top' );
		add_rewrite_rule( '^podcast-player/([^/]*)/([^/]*)/?', 'index.php?podcast_episode=$matches[1]&podcast_ref=player', 'top' );

		// Custom query variables
		add_rewrite_tag( '%podcast_episode%', '([^&]+)' );
		add_rewrite_tag( '%podcast_ref%', '([^&]+)' );

		// Series feed URLs
		$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
		add_rewrite_rule( '^feed/' . $feed_slug . '/([^/]*)/?', 'index.php?feed=' . $feed_slug . '&podcast_series=$matches[1]', 'top' );
		add_rewrite_tag( '%podcast_series%', '([^&]+)' );
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
	 * Adds series term metaboxes to the new series form.
	 */
	public function add_series_term_meta_fields( $taxonomy ) {
		// Add series image upload metabox.
		$this->series_image_uploader( $taxonomy );
	}

	/**
	 * Adds series term metaboxes to the edit series form.
	 */
	public function edit_series_term_meta_fields( $term, $taxonomy ) {
		// Add series image edit/upload metabox.
		$this->series_image_uploader( $taxonomy, $mode = 'UPDATE', $term = $term );
	}

	/**
	 * Series Image Uploader metabox for add/edit.
	 */
	public function series_image_uploader( $taxonomy, $mode = 'CREATE', $term = null ) {
		$series_settings = $this->token . '_series_image_settings';
		// Define a default image.
		$default_image = esc_url( $this->assets_url . 'images/no-image.png' );
		if ( $term !== null ) {
			$media_id = get_term_meta( $term->term_id, $series_settings, true );
		}
		$image_width  = "auto";
		$image_height = "auto";

		if ( $mode == 'UPDATE' && ! empty( $media_id ) ) {
			$image_attributes = wp_get_attachment_image_src( $media_id, array( $image_width, $image_height ) );
			$src              = $image_attributes[0];
		} else {
			$src      = $default_image;
			$media_id = '';
		}

		$series_img_title = __( 'Series Image', 'seriously-simple-podcasting' );
		$upload_btn_text  = __( 'Choose series image', 'seriously-simple-podcasting' );
		$upload_btn_value = __( 'Add Image', 'seriously-simple-podcasting' );
		$upload_btn_title = __( 'Choose an image file', 'seriously-simple-podcasting' );
		$series_img_desc  = __( "Set an image as the artwork for the series. No image will be set if not provided.", 'seriously-simple-podcasting' );
		$series_img_form_label = <<<HTML
<label>{$series_img_title}</label>
HTML;

		$series_img_form_fields = <<<HTML
<img id="{$taxonomy}_image_preview" data-src="{$default_image}" src="$src" width="{$image_width}" height="{$image_height}" />
<div>
	<input type="hidden" id="{$taxonomy}_image_id" name="{$series_settings}" value="{$media_id}" />
	<button id="{$taxonomy}_upload_image_button" class="button" data-uploader_title="{$upload_btn_title}" data-uploader_button_text="{$upload_btn_text}"><span class="dashicons dashicons-format-image"></span> {$upload_btn_value}</button>
	<button id="{$taxonomy}_remove_image_button" class="button">&times;</button>
</div>
<p class="description">{$series_img_desc}</p>
HTML;

		if ( $mode == 'CREATE' ) {
			echo <<<HTML
<div class="form-field term-upload-wrap">
	{$series_img_form_label}
	{$series_img_form_fields}
</div>
HTML;
		} else if ( $mode == 'UPDATE' ) {
			echo <<<HTML
<tr class="form-field term-upload-wrap">
	<th scope="row">{$series_img_form_label}</th>
	<td>
		{$series_img_form_fields}
	</td>
</tr>
HTML;
		}
	}

	/**
	 * Hook to allow saving series meta data.
	 */
	public function save_series_meta( $term_id, $tt_id ) {
		$this->insert_update_series_meta( $term_id, $tt_id );
		$this->save_series_data_to_feed( $term_id );
	}

	/**
	 * Hook to allow updating the series meta data.
	 */
	public function update_series_meta( $term_id, $tt_id ) {
		$this->insert_update_series_meta( $term_id, $tt_id );
	}

	/**
	 * Main method for saving or updating Series data.
	 */
	public function insert_update_series_meta( $term_id, $tt_id ) {
		$series_settings = $this->token . '_series_image_settings';
		$prev_media_id   = get_term_meta( $term_id, $series_settings, true );
		$media_id        = sanitize_title( $_POST[ $series_settings ] );
		update_term_meta( $term_id, $series_settings, $media_id, $prev_media_id );
	}

	/**
	 * Store the Series Feed title as the Series name
	 *
	 * @param $term_id
	 */
	public function save_series_data_to_feed( $term_id ) {
		$term                    = get_term( $term_id );
		$title_option_name       = 'ss_podcasting_data_title_' . $term_id;
		$subtitle_option_name    = 'ss_podcasting_data_subtitle_' . $term_id;
		$description_option_name = 'ss_podcasting_data_description_' . $term_id;
		if ( ! empty( $term->name ) ) {
			update_option( $title_option_name, $term->name );
		}
		if ( ! empty( $term->description ) ) {
			update_option( $subtitle_option_name, $term->description );
			update_option( $description_option_name, $term->description );
		}
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}
		// push the series to Castos as a Podcast
		$series_data              = get_series_data_for_castos( $term_id );
		$series_data['series_id'] = $term_id;
		$castos_handler           = new Castos_Handler();
		$castos_handler->upload_series_to_podmotor( $series_data );
	}


	/**
	 * Register columns for podcast list table
	 *
	 * @param  array $defaults Default columns
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
	 * @param  string $column_name Name of current column
	 * @param  integer $id ID of episode
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
	 * Register columns for series list table
	 *
	 * @param  array $columns Default columns
	 *
	 * @return array          Modified columns
	 */
	public function edit_series_columns( $columns ) {

		unset( $columns['description'] );
		unset( $columns['posts'] );

		$columns['series_image']    = __( 'Series Image', 'seriously-simple-podcasting' );
		$columns['series_feed_url'] = __( 'Series feed URL', 'seriously-simple-podcasting' );
		$columns['posts']           = __( 'Episodes', 'seriously-simple-podcasting' );
		$columns = apply_filters( 'ssp_admin_columns_series', $columns );

		return $columns;
	}

	/**
	 * Display column data in series list table
	 *
	 * @param string $column_data Default column content
	 * @param string $column_name Name of current column
	 * @param integer $term_id ID of term
	 *
	 * @return string
	 */
	public function add_series_columns( $column_data, $column_name, $term_id ) {

		switch ( $column_name ) {
			case 'series_feed_url':
				$series      = get_term( $term_id, 'series' );
				$series_slug = $series->slug;

				if ( get_option( 'permalink_structure' ) ) {
					$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
					$feed_url  = $this->home_url . 'feed/' . $feed_slug . '/' . $series_slug;
				} else {
					$feed_url = add_query_arg(
						array(
							'feed'           => $this->token,
							'podcast_series' => $series_slug,
						),
						$this->home_url
					);
				}

				$column_data = '<a href="' . esc_attr( $feed_url ) . '" target="_blank">' . esc_html( $feed_url ) . '</a>';
				break;
			case 'series_image':
				$series           = get_term( $term_id, 'series' );
				$series_settings  = $this->token . '_series_image_settings';
				$default_image    = esc_url( $this->assets_url . 'images/no-image.png' );
				$media_id         = get_term_meta( $term_id, $series_settings, true );
				$image_attributes = wp_get_attachment_image_src( $media_id );
				$source           = ( isset( $image_attributes[0] ) ) ? $image_attributes[0] : $default_image;
				$column_data      = <<<HTML
<img id="{$series->name}_image_preview" src="{$source}" width="auto" height="auto" style="max-width:50px;" />
HTML;
				break;
		}

		return $column_data;
	}

	/**
	 * Create custom dashboard message
	 *
	 * @param  array $messages Default messages
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
	 * @param  object $post Current post object
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
	 * @return void
	 */
	public function meta_box_content() {
		//add_thickbox();
		global $post_id;

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
						if ( ssp_is_connected_to_castos() ) {
							$upload_button = '<div id="ssp_upload_container" style="display: inline;">';
							$upload_button .= '  <button id="ssp_select_file" href="javascript:">Select podcast file</button>';
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
	 * Save episode meta box content
	 *
	 * @param  integer $post_id ID of post
	 *
	 * @return mixed
	 */
	public function meta_box_save( $post_id ) {
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

	}

	/**
	 * Setup custom fields for episodes
	 * @return array Custom fields
	 */
	public function custom_fields() {
		return $this->cpt_podcast_handler->custom_fields();
	}

	/**
	 * Register the Castos Blog dashboard widget
	 * Hooks into the wp_dashboard_setup action hook
	 */
	public function ssp_dashboard_setup() {
		wp_add_dashboard_widget( 'ssp_castos_dashboard', __( 'Castos News' ), array( $this, 'ssp_castos_dashboard' ) );
	}

	/**
	 * Castos Blog dashboard widget callback
	 */
	public function ssp_castos_dashboard() {
		?>
		<div class="castos-news hide-if-no-js">
			<?php echo $this->ssp_castos_dashboard_render(); ?>
		</div>
		<?php
	}

	/**
	 * Render the dashboard widget data
	 *
	 * @return string
	 */
	public function ssp_castos_dashboard_render() {
		$feeds = array(
			'news' => array(
				'link'         => apply_filters( 'ssp_castos_dashboard_primary_link', __( 'https://castos.com/blog/' ) ),
				'url'          => apply_filters( 'ssp_castos_dashboard_secondary_feed', __( 'https://castos.com/blog/feed/' ) ),
				'title'        => apply_filters( 'ssp_castos_dashboard_primary_title', __( 'Castos Blog' ) ),
				'items'        => 4,
				'show_summary' => 0,
				'show_author'  => 0,
				'show_date'    => 0,
			),
		);

		return $this->ssp_castos_dashboard_output( 'ssp_castos_dashboard', $feeds );
	}

	/**
	 * Generate the dashboard widget content
	 *
	 * @param $widget_id
	 * @param $feeds
	 *
	 * @return string the RSS feed output
	 */
	public function ssp_castos_dashboard_output( $widget_id, $feeds ) {
		/**
		 * Check if there is a cached version of the RSS Feed and output it
		 */
		$locale    = get_user_locale();
		$cache_key = 'ssp_dash_v2_' . md5( $widget_id . '_' . $locale );
		$rss_output    = get_transient( $cache_key );
		if ( false !== $rss_output ) {
			return $rss_output;
		}
		/**
		 * Get the RSS Feed contents
		 */
		ob_start();
		foreach ( $feeds as $type => $args ) {
			$args['type'] = $type;
			echo '<div class="rss-widget">';
			wp_widget_rss_output( $args['url'], $args );
			echo '</div>';
		}
		$rss_output = ob_get_clean();
		/**
		 * Set up the cached version to expire in 12 hours and output the content
		 */
		set_transient( $cache_key, $rss_output, 12 * HOUR_IN_SECONDS );
		return $rss_output;
	}

	/**
	 * Adding podcast episodes to 'At a glance' dashboard widget
	 *
	 * @param  array $items Existing items
	 *
	 * @return array        Updated items
	 */
	public function glance_items( $items = array() ) {

		$num_posts = count( ssp_episodes( - 1, '', false, 'glance' ) );

		$post_type_object = get_post_type_object( $this->token );
		$text = _n( '%s Episode', '%s Episodes', $num_posts, 'seriously-simple-podcasting' );
		$text = sprintf( $text, number_format_i18n( $num_posts ) );

		if ( $post_type_object && current_user_can( $post_type_object->cap->edit_posts ) ) {
			$items[] = sprintf( '<a class="%1$s-count" href="edit.php?post_type=%1$s">%2$s</a>', $this->token, $text ) . "\n";
		} else {
			$items[] = sprintf( '<span class="%1$s-count">%2$s</span>', $this->token, $text ) . "\n";
		}

		return $items;
	}

	/**
	 * Adding appreciation links to the SSP record in the plugin list table
	 *
	 * @param  array $plugin_meta Default plugin meta links
	 * @param  string $plugin_file Plugin file
	 * @param  array $plugin_data Array of plugin data
	 * @param  string $status Plugin status
	 *
	 * @return array               Modified plugin meta links
	 */
	public function plugin_row_meta( $plugin_meta = array(), $plugin_file = '', $plugin_data = array(), $status = '' ) {

		if ( ! isset( $plugin_data['slug'] ) || $this->plugin_slug != $plugin_data['slug'] ) {
			return $plugin_meta;
		}
		$plugin_meta['docs']   = '<a href="https://support.castos.com/?utm_medium=sspodcasting&utm_source=wordpress&utm_campaign=wpplugin_08_2019" target="_blank">' . __( 'Documentation', 'seriously-simple-podcasting' ) . '</a>';
		$plugin_meta['addons'] = '<a href="https://castos.com/add-ons/?utm_medium=sspodcasting&utm_source=wordpress&utm_campaign=wpplugin_08_2019" target="_blank">' . __( 'Add-ons', 'seriously-simple-podcasting' ) . '</a>';
		$plugin_meta['review'] = '<a href="https://wordpress.org/support/view/plugin-reviews/' . $plugin_data['slug'] . '?rate=5#postform" target="_blank">' . __( 'Write a review', 'seriously-simple-podcasting' ) . '</a>';
		return $plugin_meta;
	}

	/**
	 * Modify the 'enter title here' text
	 *
	 * @param  string $title Default text
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
	 * Load admin CSS
	 * @return void
	 */
	public function enqueue_admin_styles( $hook ) {

		wp_register_style( 'ssp-admin', esc_url( $this->assets_url . 'admin/css/admin.css' ), array(), $this->version );
		wp_enqueue_style( 'ssp-admin' );

		// Datepicker
		wp_register_style( 'jquery-ui-datepicker-wp', esc_url( $this->assets_url . 'css/datepicker.css' ), array(), $this->version );
		wp_enqueue_style( 'jquery-ui-datepicker-wp' );

		/**
		 * Only load the peekabar styles when adding/editing podcasts
		 */
		if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
			global $post;
			if ( in_array( $post->post_type, ssp_post_types( true ) ) ) {
				wp_register_style( 'jquery-peekabar', esc_url( $this->assets_url . 'css/jquery-peekabar.css' ), array(), $this->version );
				wp_enqueue_style( 'jquery-peekabar' );
			}
		}

		/**
		 * Only load the jquery-ui CSS when the import settings screen is loaded
		 * @todo load this locally perhaps? and only the progress bar stuff?
		 */
		if ( 'podcast_page_podcast_settings' === $hook && isset( $_GET['tab'] ) && 'import' == $_GET['tab'] ) {
			//wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', array(), $this->version  );

			wp_register_style( 'jquery-ui-smoothness', esc_url( $this->assets_url . 'css/jquery-ui-smoothness.css' ), array(), $this->version );
			wp_enqueue_style( 'jquery-ui-smoothness' );

			wp_register_style( 'import-rss', esc_url( $this->assets_url . 'css/import-rss.css' ), array(), $this->version );
			wp_enqueue_style( 'import-rss' );

		}
	}

	/**
	 * Load admin JS
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {

		wp_register_script( 'ssp-admin', esc_url( $this->assets_url . 'js/admin' . $this->script_suffix . '.js' ), array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-datepicker'
		), $this->version );
		wp_enqueue_script( 'ssp-admin' );

		wp_register_script( 'ssp-settings', esc_url( $this->assets_url . 'js/settings' . $this->script_suffix . '.js' ), array( 'jquery' ), $this->version );
		wp_enqueue_script( 'ssp-settings' );

		// Only enqueue the WordPress Media Library picker for adding and editing SSP tags/terms post types.
		if ( 'edit-tags.php' === $hook || 'term.php' === $hook ) {
			if ( 'series' === $_REQUEST['taxonomy'] ) {
				wp_enqueue_media();
			}
		}

		/**
		 * Only load the upload scripts when adding/editing posts/podcasts
		 */
		if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
			global $post;
			if ( in_array( $post->post_type, ssp_post_types( true ) ) ) {
				wp_enqueue_script( 'plupload-all' );
				$upload_credentials = ssp_setup_upload_credentials();
				wp_register_script( 'ssp-fileupload', esc_url( $this->assets_url . 'js/fileupload' . $this->script_suffix . '.js' ), array(), $this->version );
				wp_localize_script( 'ssp-fileupload', 'upload_credentials', $upload_credentials );
				wp_enqueue_script( 'ssp-fileupload' );
				wp_register_script( 'jquery-peekabar', esc_url( $this->assets_url . 'js/jquery.peekabar' . $this->script_suffix . '.js' ), array( 'jquery' ), $this->version );
				wp_enqueue_script( 'jquery-peekabar' );
			}
		}

		/**
		 * Only load the import js when the import settings screen is loaded
		 */
		if ( 'podcast_page_podcast_settings' === $hook && isset( $_GET['tab'] ) && 'import' == $_GET['tab'] ) {
			wp_register_script( 'ssp-import-rss', esc_url( $this->assets_url . 'js/import.rss' . $this->script_suffix . '.js' ), array(
				'jquery',
				'jquery-ui-progressbar'
			), $this->version );
			wp_enqueue_script( 'ssp-import-rss' );
		}
	}

	/**
	 * Ensure thumbnail support on site
	 * @return void
	 */
	public function ensure_post_thumbnails_support() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' );
		}
	}

	/**
	 * Load plugin text domain
	 * @return void
	 */
	public function load_localisation() {
		load_plugin_textdomain( $this->plugin_slug, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Load localisation
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$domain = $this->plugin_slug;
		// The "plugin_locale" filter is also used in load_plugin_textdomain()
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Hide RSS footer created by WordPress SEO from podcast RSS feed
	 *
	 * @param  boolean $include_footer Default inclusion value
	 *
	 * @return boolean                 Modified inclusion value
	 */
	public function hide_wp_seo_rss_footer( $include_footer = true ) {

		if ( is_feed( $this->token ) ) {
			$include_footer = false;
		}

		return $include_footer;
	}

	/**
	 * All plugin activation functionality
	 * @return void
	 */
	public function activate() {
		// Setup all custom URL rules
		$this->register_post_type();
		// Setup feed
		$this->feed_controller->add_feed();
		// Setup permalink structure
		$this->setup_permastruct();
		// Flush permalinks
		flush_rewrite_rules( true );
	}

	/**
	 * All plugin deactivation functionality
	 * @return void
	 */
	public function deactivate() {
		flush_rewrite_rules();
		$this->roles_handler->remove_custom_roles();
	}

	/**
	 * Run functions on plugin update/activation
	 * @return void
	 */
	public function update() {

		$previous_version = get_option( 'ssp_version', '1.0' );

		$this->upgrade_handler->run_upgrades( $previous_version );

		// always just check if the directory is ok
		ssp_get_upload_directory( false );

		update_option( 'ssp_version', $this->version );

	}

	/**
	 * Update 'enclosure' meta field to 'audio_file' meta field
	 * Todo: I don't see any place where 'ssp_update_enclosures' query is generated. Is this function obsolete?
	 *
	 * @return void
	 */
	public function update_enclosures() {

		if ( ! current_user_can( 'manage_podcast' ) ) {
			return;
		}

		// Allow forced re-run of update if necessary
		if ( isset( $_GET['ssp_update_enclosures'] ) ) {
			delete_option( 'ssp_update_enclosures' );
		}

		// Check if update has been run
		$update_run = get_option( 'ssp_update_enclosures', false );

		if ( $update_run ) {
			return;
		}

		// Get IDs of all posts with enclosures
		$args = array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => 'enclosure',
					'compare' => '!=',
					'value'   => '',
				),
			),
			'fields'         => 'ids',
		);

		$posts_with_enclosures = get_posts( $args );

		if ( 0 == count( $posts_with_enclosures ) ) {
			return;
		}

		// Add `audio_file` meta field to all posts with enclosures
		foreach ( (array) $posts_with_enclosures as $post_id ) {

			// Get existing enclosure
			$enclosure = get_post_meta( $post_id, 'enclosure', true );

			// Add audio_file field
			if ( $enclosure ) {
				update_post_meta( $post_id, 'audio_file', $enclosure );
			}

		}

		// Mark update as having been run
		update_option( 'ssp_update_enclosures', 'run' );
	}

	/**
	 * Add rating link to admin footer on SSP settings pages
	 *
	 * @param  string $footer_text Default footer text
	 *
	 * @return string              Modified footer text
	 */
	public function admin_footer_text( $footer_text ) {

		// Check to make sure we're on a SSP settings page
		if ( ( isset( $_GET['page'] ) && 'podcast_settings' == esc_attr( $_GET['page'] ) ) && apply_filters( 'ssp_display_admin_footer_text', true ) ) {

			// Change the footer text
			if ( ! get_option( 'ssp_admin_footer_text_rated' ) ) {
				$footer_text = sprintf( __( 'If you like %1$sSeriously Simple Podcasting%2$s please leave a %3$s&#9733;&#9733;&#9733;&#9733;&#9733;%4$s rating. A huge thank you in advance!', 'seriously-simple-podcasting' ), '<strong>', '</strong>', '<a href="https://wordpress.org/support/plugin/seriously-simple-podcasting/reviews/?rate=5#new-post" target="_blank" class="ssp-rating-link" data-rated="' . __( 'Thanks!', 'seriously-simple-podcasting' ) . '">', '</a>' );
				$footer_text .= sprintf("<script type='text/javascript'>
					(function($){
					  $('a.ssp-rating-link').click(function() {
						$.post( '" . admin_url( 'admin-ajax.php' ) . "', { action: 'ssp_rated', nonce: '%s' } );
						$(this).parent().text( $(this).data( 'rated' ) );
					})})(jQuery);
				</script>", wp_create_nonce( 'ssp_rated' ) );
			} else {
				$footer_text = sprintf( __( '%1$sThank you for publishing with %2$sSeriously Simple Podcasting%3$s.%4$s', 'seriously-simple-podcasting' ), '<span id="footer-thankyou">', '<a href="http://www.seriouslysimplepodcasting.com/" target="_blank">', '</a>', '</span>' );
			}

		}

		return $footer_text;
	}

	/**
	 * Clear the cache on post save.
	 *
	 * @param  int $id POST ID
	 * @param  object $post WordPress Post Object
	 *
	 * @return void
	 */
	public function invalidate_cache( $id, $post ) {

		if ( in_array( $post->post_type, ssp_post_types( true ) ) ) {
			wp_cache_delete( 'episodes', 'ssp' );
			wp_cache_delete( 'episode_ids', 'ssp' );
		}

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

		$castos_handler = new Castos_Handler();
		$response       = $castos_handler->upload_podcast_to_podmotor( $post );

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

		$castos_handler = new Castos_Handler();

		$castos_handler->delete_podcast( $post );

		delete_post_meta( $post_id, 'podmotor_file_id' );
		delete_post_meta( $post_id, 'podmotor_episode_id' );
	}

	/**
	 * Ignore podcast import
	 */
	public function ignore_importing_existing_podcasts() {
		if ( 'ignore' === filter_input( INPUT_GET, 'podcast_import_action' ) &&
			 wp_verify_nonce( $_GET['nonce'], 'podcast_import_action' ) &&
			 current_user_can( 'manage_podcast' )
		) {
			update_option( 'ss_podcasting_podmotor_import_podcasts', 'false' );
		}
	}

	/**
	 * Processes the Import forms from the Import tab in the plugin settings
	 */
	public function submit_import_form() {

		$action = ( isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '' );

		if ( empty( $action ) || 'post_import_form' !== sanitize_text_field( $action ) ) {
			return;
		}

		check_admin_referer( 'ss_podcasting_import' );

		$submit = '';
		if ( isset( $_POST['Submit'] ) ) {
			$submit = sanitize_text_field( $_POST['Submit'] );
		}

		// The user has submitted the Import your podcast setting
		$trigger_import_submit = __( 'Trigger import', 'seriously-simple-podcasting' );
		if ( $trigger_import_submit === $submit ) {
			$import = sanitize_text_field( $_POST['ss_podcasting_podmotor_import'] );
			if ( 'on' === $import ) {
				$castos_handler = new Castos_Handler();
				$result         = $castos_handler->trigger_podcast_import();
				if ( 'success' !== $result['status'] ) {
					add_action( 'admin_notices', array( $this, 'trigger_import_error' ) );
				} else {
					add_action( 'admin_notices', array( $this, 'trigger_import_success' ) );
				}

				return;
			} else {
				update_option( 'ss_podcasting_podmotor_import', 'off' );
			}
		}

		// The user has submitted the external import form
		$begin_import_submit = __( 'Begin Import Now', 'seriously-simple-podcasting' );
		if ( $begin_import_submit === $submit ) {
			$external_rss = wp_strip_all_tags(
				stripslashes(
					esc_url_raw( $_POST['external_rss'] )
				)
			);
			if ( ! empty( $external_rss ) ) {
				$import_post_type = SSP_CPT_PODCAST;
				if ( isset( $_POST['import_post_type'] ) ) {
					$import_post_type = sanitize_text_field( $_POST['import_post_type'] );
				}
				$import_series = '';
				if ( isset( $_POST['import_series'] ) ) {
					$import_series = sanitize_text_field( $_POST['import_series'] );
				}
				$ssp_external_rss = array(
					'import_rss_feed'  => $external_rss,
					'import_post_type' => $import_post_type,
					'import_series'    => $import_series,
				);
				update_option( 'ssp_external_rss', $ssp_external_rss );
				add_action( 'admin_notices', array( $this, 'import_form_success' ) );
			}
		}
	}

	/**
	 * Admin error to display if the import trigger fails
	 */
	public function trigger_import_error() {
		?>
		<div class="notice notice-info is-dismissible">
			<p><?php esc_attr_e( 'An error occurred starting your podcast import. Please contact support at hello@castos.com.', 'seriously-simple-podcasting' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Admin error to display if the import trigger is successful
	 */
	public function trigger_import_success() {
		?>
		<div class="notice notice-info is-dismissible">
			<p><?php esc_attr_e( 'Your podcast import triggered successfully, please check your email for details.', 'seriously-simple-podcasting' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Displays an admin message if the Import form submission was successful
	 */
	public function import_form_success() {
		?>
		<div class="notice notice-info is-dismissible">
			<p><?php esc_attr_e( 'Thanks, your external RSS feed will start importing', 'seriously-simple-podcasting' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Dismiss categories update screen when user clicks 'Dismiss' link
	 */
	public function dismiss_categories_update() {
		// Check if the ssp_dismiss_categories_update variable exists
		$ssp_dismiss_categories_update = ( isset( $_GET['ssp_dismiss_categories_update'] ) ? sanitize_text_field( $_GET['ssp_dismiss_categories_update'] ) : '' );
		if ( ! $ssp_dismiss_categories_update ||
			 ! wp_verify_nonce( $_GET['nonce'], 'dismiss_categories_update' ) ||
			 ! current_user_can( 'manage_podcast' )
		) {
			return;
		}

		update_option( 'ssp_categories_update_dismissed', 'true' );
	}

	/**
	 * Dismiss Elementor templates message when user clicks 'Dismiss' link
	 */
	public function disable_elementor_template_notice() {
		// Check if the ssp_disable_elementor_template_notice variable exists
		$ssp_disable_elementor_template_notice = ( isset( $_GET['ssp_disable_elementor_template_notice'] ) ? sanitize_text_field( $_GET['ssp_disable_elementor_template_notice'] ) : '' );
		if ( empty( $ssp_disable_elementor_template_notice ) ) {
			return;
		}
		update_option( 'ss_podcasting_elementor_templates_disabled', 'true' );
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

}
