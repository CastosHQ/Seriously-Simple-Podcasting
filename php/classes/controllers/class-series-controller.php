<?php

namespace SeriouslySimplePodcasting\Controllers;


// Exit if accessed directly.
use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Walker;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Repositories\Series_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This is controller for Podcast and other SSP post types (which are enabled via settings) custom behavior.
 *
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       3.0.0
 */
class Series_Controller {

	use Useful_Variables;

	/**
	 * @var Series_Handler
	 * */
	private $series_handler;

	/**
	 * @var Castos_Handler
	 * */
	private $castos_handler;

	/**
	 * @var Settings_Handler
	 * */
	private $settings_handler;

	/**
	 * @var Admin_Notifications_Handler
	 * */
	private $notice_handler;

	/**
	 * @var Series_Repository
	 * */
	private $series_repository;

	/**
	 * @param Series_Handler $series_handler
	 * @param Castos_Handler $castos_handler
	 * @param Settings_Handler $settings_handler
	 * @param Admin_Notifications_Handler $notice_handler
	 */
	public function __construct( $series_handler, $castos_handler, $settings_handler, $notice_handler ) {
		$this->series_handler    = $series_handler;
		$this->castos_handler    = $castos_handler;
		$this->settings_handler  = $settings_handler;
		$this->notice_handler    = $notice_handler;
		$this->series_repository = ssp_series_repository();

		$this->init_useful_variables();

		$taxonomy = ssp_series_taxonomy();

		add_action( 'init', array( $this, 'register_taxonomy' ), 11 );
		add_filter( "{$taxonomy}_row_actions", array( $this, 'add_term_actions' ), 10, 2 );
		add_action( 'ssp_triggered_podcast_sync', array( $this, 'update_podcast_sync_status' ), 10, 3 );

		add_action( 'created_series', array( $this, 'save_series_meta' ), 10, 2 );
		add_action( 'edited_series', array( $this, 'save_series_meta' ), 10, 2 );

		add_action( 'add_option_ss_podcasting_podmotor_account_api_token', array( $this, 'sync_series' ) );

		// Series list table.
		add_filter( 'manage_edit-series_columns', array( $this, 'edit_series_columns' ) );
		add_filter( 'manage_series_custom_column', array( $this, 'add_series_columns' ), 1, 3 );

		// Series term meta forms
		add_action( 'series_add_form_fields', array( $this, 'add_series_term_meta_fields' ), 10, 2 );
		add_action( 'series_edit_form_fields', array( $this, 'edit_series_term_meta_fields' ), 10, 2 );

		// Exclude series feed from the default feed
		add_action( 'create_series', array( $this, 'exclude_feed_from_default' ) );

		$this->handle_default_series();
	}

	private function handle_default_series() {
		add_filter( 'term_name', array( $this, 'change_default_series_name' ), 10, 2 );
		add_filter( 'post_column_taxonomy_links', array( $this, 'change_column_default_series_name' ), 10, 3 );
		add_filter( 'wp_terms_checklist_args', array( $this, 'change_checklist_default_series_name' ) );
		add_action( 'admin_init', array( $this, 'check_default_series_existence' ), 20 );

		$this->prevent_deleting_default_series();
	}

	/**
	 *
	 * */
	public function check_default_series_existence() {
		if ( ! ssp_get_default_series_id() ) {
			$this->enable_default_series();
			if ( ! ssp_get_default_series_id() ) {
				$notice = sprintf(
					__( 'The Default Podcast was not found! <br />
			Please try to disable and then re-enable the Seriously Simple Podcasting plugin. <br />
			If this message persists, kindly reach out to us via the <a target="_blank" href="%s">plugin forum</a> for further assistance.',
						'seriously-simple-podcasting' ),
					'https://wordpress.org/support/plugin/seriously-simple-podcasting/'
				);
				$this->notice_handler->add_flash_notice( $notice );
			}
		}
	}

	/**
	 * Changes the default series name in the series checklist
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function change_checklist_default_series_name( $args ) {
		if ( empty( $args['taxonomy'] ) || ssp_series_taxonomy() != $args['taxonomy'] ) {
			return $args;
		}
		$args['walker'] = new Series_Walker( $this->series_handler );

		return $args;
	}

	public function sync_series() {
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}
		$terms = ssp_get_podcasts();
		foreach ( $terms as $term ) {
			$series_data              = $this->castos_handler->generate_series_data_for_castos( $term->term_id );
			$series_data['series_id'] = $term->term_id;
			$this->castos_handler->update_podcast_data( $series_data );
		}
	}

	/**
	 * Adding it here, and not via default settings for the backward compatibility.
	 * So if users have their old series included in the default feed, it should not affect them.
	 * */
	public function exclude_feed_from_default( $series_id ) {
		ssp_update_option( 'exclude_feed', 'on', $series_id );
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
		$this->series_image_uploader( $taxonomy, 'UPDATE', $term );
		$this->show_feed_info( $term );
	}

	/**
	 * @param \WP_Term $term
	 *
	 * @return void
	 */
	protected function show_feed_info( $term ) {
		$edit_feed_url = sprintf(
			'edit.php?post_type=%s&page=podcast_settings&tab=feed-details&feed-series=%s',
			SSP_CPT_PODCAST,
			$term->slug
		);
		$edit_feed_url = admin_url( $edit_feed_url );

		$feed_fields = $this->settings_handler->get_feed_fields();
		$settings_handler = $this->settings_handler;

		ssp_renderer()->render(
			'settings/podcast-feed-details',
			compact( 'edit_feed_url', 'term', 'settings_handler', 'feed_fields' )
		);
	}

	/**
	 * Series Image Uploader metabox for add/edit.
	 */
	public function series_image_uploader( $taxonomy, $mode = 'CREATE', $term = null ) {
		$series_settings = $this->token . '_series_image_settings';

		$default_image = esc_url( $this->assets_url . 'images/no-image.png' );
		$media_id      = $this->get_series_image_id( $term ) ?: '';
		$src           = $this->get_series_image_src( $term );
		$image_width   = "auto";
		$image_height  = "auto";

		$series_img_title = __( 'Podcast Image', 'seriously-simple-podcasting' );
		$upload_btn_text  = __( 'Choose podcast image', 'seriously-simple-podcasting' );
		$upload_btn_value = __( 'Add Image', 'seriously-simple-podcasting' );
		$upload_btn_title = __( 'Choose an image file', 'seriously-simple-podcasting' );
		$series_img_desc  = __(
			'Set an image as the artwork for the podcast page. No image will be set if not provided.',
			'seriously-simple-podcasting'
		);

		$upload_image = ssp_renderer()->fetch( 'settings/podcast-upload-image', compact(
			'series_img_title', 'taxonomy', 'default_image', 'src', 'image_width', 'image_height', 'series_settings',
			'media_id', 'upload_btn_title', 'upload_btn_text', 'upload_btn_value', 'series_img_desc'
		) );

		$mode = 'create' === strtolower( $mode ) ? 'create' : 'update';
		ssp_renderer()->render( "settings/podcast-image-$mode", compact( 'series_img_title', 'upload_image' ) );
	}

	/**
	 * @param \WP_Term $term
	 *
	 * @return int|null
	 * @since 2.7.3
	 *
	 */
	public function get_series_image_id( $term = null ) {
		if ( empty( $term ) ) {
			return null;
		}

		return get_term_meta( $term->term_id, $this->token . '_series_image_settings', true );
	}

	/**
	 * @param \WP_Term $term
	 *
	 * @return string
	 * @since 2.7.3
	 *
	 */
	public function get_series_image_src( $term ) {
		return $this->series_repository->get_image_src( $term );
	}

	/**
	 * Register columns for series list table
	 *
	 * @param array $columns Default columns
	 *
	 * @return array          Modified columns
	 */
	public function edit_series_columns( $columns ) {

		unset( $columns['description'] );
		unset( $columns['posts'] );

		$columns['series_image']    = __( 'Podcast Image', 'seriously-simple-podcasting' );
		$columns['series_feed_url'] = __( 'Podcast feed URL', 'seriously-simple-podcasting' );
		$columns['posts']           = __( 'Episodes', 'seriously-simple-podcasting' );
		$columns                    = apply_filters( 'ssp_admin_columns_series', $columns );

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
	 * Todo: get rid of HTML
	 */
	public function add_series_columns( $column_data, $column_name, $term_id ) {

		switch ( $column_name ) {
			case 'series_feed_url':
				$series   = get_term( $term_id, 'series' );
				$feed_url = $this->get_series_feed_url( $series );

				$column_data = '<a href="' . esc_attr( $feed_url ) . '" target="_blank">' . esc_html( $feed_url ) . '</a>';
				break;
			case 'series_image':
				$series      = get_term( $term_id, 'series' );
				$source      = $this->get_series_image_src( $series );
				$column_data = <<<HTML
<img id="{$series->name}_image_preview" src="{$source}" width="auto" height="auto" style="max-width:50px;" />
HTML;
				break;
		}

		return $column_data;
	}


	/**
	 * @param \WP_Term $term
	 *
	 * @return string
	 * @since 2.7.3
	 *
	 */
	public function get_series_feed_url( $term ) {
		return $this->series_repository->get_feed_url( $term );
	}

	/**
	 * Hook to allow saving series metadata.
	 */
	public function save_series_meta( $term_id, $tt_id ) {
		$this->insert_update_series_meta( $term_id, $tt_id );
		$this->save_series_data_to_castos( $term_id );
	}

	/**
	 * Store the Series Feed title as the Series name
	 *
	 * @param $term_id
	 */
	public function save_series_data_to_castos( $term_id ) {
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}

		$default_series_id = $this->series_handler->default_series_id();
		if ( ! $default_series_id ) {
			/**
			 * It means we're creating the default series now,
			 * and we'll update the default Podcast series ID instead of creating the new one.
			 * @see Castos_Handler::update_default_series_id()
			 * */
			return;
		}

		// push the series to Castos as a Podcast
		$series_data              = $this->castos_handler->generate_series_data_for_castos( $term_id );
		$series_data['series_id'] = $term_id;
		$this->castos_handler->update_podcast_data( $series_data );
	}

	/**
	 * Main method for saving or updating Series data.
	 */
	public function insert_update_series_meta( $term_id, $tt_id ) {
		$series_settings = SSP_CPT_PODCAST . '_series_image_settings';
		$prev_media_id   = get_term_meta( $term_id, $series_settings, true );
		$media_id        = isset( $_POST[ $series_settings ] ) ? sanitize_title( $_POST[ $series_settings ] ) : $prev_media_id;
		update_term_meta( $term_id, $series_settings, $media_id, $prev_media_id );
	}

	public function prevent_deleting_default_series() {
		add_filter( ssp_series_taxonomy() . '_row_actions', array( $this, 'disable_deleting_default' ), 10, 2 );
		add_action( 'pre_delete_term', array( $this, 'prevent_term_deletion' ), 10, 2 );
	}

	/**
	 * @param int $term_id
	 * @param string $taxonomy
	 *
	 * @return void
	 */
	public function prevent_term_deletion( $term_id, $taxonomy ) {
		if ( $taxonomy != ssp_series_taxonomy() ) {
			return;
		}
		if ( $term_id == ssp_get_default_series_id() ) {
			if ( isset( $_POST['action'] ) && 'delete-tag' === $_POST['action'] ) {
				$error = - 1; // it's an ajax action, just return -1
			} else {
				$error = new \WP_Error ();
				$error->add( 1, __( '<h2>You cannot delete the default podcast!', 'seriously-simple-podcasting' ) );
			}
			wp_die( $error );
		}
	}


	/**
	 * @return void
	 */
	public function register_taxonomy() {
		$this->series_handler->register_taxonomy();
	}

	/**
	 * @return void
	 */
	public function enable_default_series() {
		$this->series_handler->enable_default_series();
	}

	/**
	 * Changes the default series name in the terms list (All Podcasts page)
	 *
	 * @param string $name
	 * @param \WP_Term $tag
	 *
	 * @return string
	 */
	public function change_default_series_name( $name, $tag ) {
		if ( ! is_object( $tag ) || $tag->taxonomy != ssp_series_taxonomy() ) {
			return $name;
		}

		if ( $tag->term_id == $this->series_handler->default_series_id() ) {
			return $this->series_handler->default_series_name( $name );
		}

		return $name;
	}

	/**
	 * Changes the default series name in the post columns (All Episodes -> Podcasts)
	 *
	 * @param array $term_links
	 * @param string $taxonomy
	 * @param \WP_Term[] $terms
	 */
	public function change_column_default_series_name( $term_links, $taxonomy, $terms ) {
		if ( ssp_series_taxonomy() !== $taxonomy || ! $term_links ) {
			return $term_links;
		}

		foreach ( $terms as $k => $term ) {
			if ( $this->series_handler->default_series_id() === $term->term_id ) {
				$term_links[ $k ] = str_replace(
					$term->name,
					$this->series_handler->default_series_name( $term->name ),
					$term_links[ $k ]
				);
				break;
			}
		}

		return $term_links;
	}

	/**
	 * @param $actions
	 * @param $tag
	 *
	 * @return mixed
	 */
	public function disable_deleting_default( $actions, $tag ) {
		if ( ! is_object( $tag ) || $tag->term_id != ssp_get_default_series_id() ) {
			return $actions;
		}

		$title = __( "You can't delete the default podcast", 'seriously-simple-podcasting' );

		$actions['delete'] = '<span title="' . $title . '">' . __( 'Delete', 'seriously-simple-podcasting' ) . '</span>';

		return $actions;
	}

	/**
	 * @param array $actions
	 * @param \WP_Term $term
	 *
	 * @return array
	 */
	public function add_term_actions( $actions, $term ) {

		$link = '<a href="%s">' . __( 'Edit&nbsp;Feed&nbsp;Details', 'seriously-simple-podcasting' ) . '</a>';
		$link = sprintf( $link, sprintf(
			'edit.php?post_type=%s&page=podcast_settings&tab=feed-details&feed-series=%s',
			SSP_CPT_PODCAST,
			$term->slug
		) );

		$actions['edit_feed_details'] = $link;

		return $actions;
	}

	/**
	 * @param int $podcast_id
	 * @param array $response
	 * @param string $status
	 *
	 * @return void
	 */
	public function update_podcast_sync_status( $podcast_id, $response, $status ) {

		$this->series_handler->update_sync_status( $podcast_id, $status );
	}
}
