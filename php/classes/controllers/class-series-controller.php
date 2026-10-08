<?php

namespace SeriouslySimplePodcasting\Controllers;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Handlers\RSS_Import_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Walker;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Repositories\Series_Repository;
use SeriouslySimplePodcasting\Repositories\Sync_Refusal_Repository;
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
	 * @var Sync_Refusal_Repository
	 */
	private $sync_refusal_repository;

	/**
	 * @var Feed_Handler
	 */
	private $feed_handler;

	/**
	 * Initialize series services and register taxonomy management hooks.
	 *
	 * @param Series_Handler               $series_handler
	 * @param Castos_Handler               $castos_handler
	 * @param Settings_Handler             $settings_handler
	 * @param Admin_Notifications_Handler  $notice_handler
	 * @param Sync_Refusal_Repository      $sync_refusal_repository
	 * @param Feed_Handler                 $feed_handler
	 */
	public function __construct( $series_handler, $castos_handler, $settings_handler, $notice_handler, $sync_refusal_repository, $feed_handler ) {
		$this->series_handler          = $series_handler;
		$this->castos_handler          = $castos_handler;
		$this->settings_handler        = $settings_handler;
		$this->notice_handler          = $notice_handler;
		$this->series_repository       = ssp_series_repository();
		$this->sync_refusal_repository = $sync_refusal_repository;
		$this->feed_handler            = $feed_handler;

		$this->init_useful_variables();

		$taxonomy = ssp_series_taxonomy();

		add_action( 'init', array( $this, 'register_taxonomy' ), 11 );
		add_filter( "{$taxonomy}_row_actions", array( $this, 'add_term_actions' ), 10, 2 );
		add_action( 'ssp_triggered_podcast_sync', array( $this, 'update_series_sync_status' ), 10, 3 );

		add_action( 'created_series', array( $this, 'maybe_store_series_guid' ), 5 );
		add_action( 'created_series', array( $this, 'save_series_meta' ), 10, 2 );
		add_action( 'edited_series', array( $this, 'save_series_meta' ), 10, 2 );

		add_action( 'add_option_ss_podcasting_podmotor_account_api_token', array( $this, 'sync_series' ) );

		// Series list table.
		add_filter( 'manage_edit-series_columns', array( $this, 'edit_series_columns' ) );
		add_filter( 'manage_series_custom_column', array( $this, 'add_series_columns' ), 1, 3 );

		// Series term meta forms
		add_action( 'series_add_form_fields', array( $this, 'add_series_term_meta_fields' ), 10, 2 );
		add_action( 'series_edit_form_fields', array( $this, 'edit_series_term_meta_fields' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_series_guid_scripts' ) );

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
					__(
						'The Default Podcast was not found! <br />
			Please try to disable and then re-enable the Seriously Simple Podcasting plugin. <br />
			If this message persists, kindly reach out to us via the <a target="_blank" href="%s">plugin forum</a> for further assistance.',
						'seriously-simple-podcasting'
					),
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
		$this->show_default_series_toggle( $term );
		$this->show_feed_info( $term );
		$this->show_series_guid( $term );
	}

	/**
	 * Renders the GUID at the end of the series term edit form.
	 *
	 * @since 3.18.0
	 *
	 * @param \WP_Term $term Series term.
	 *
	 * @return void
	 */
	protected function show_series_guid( $term ) {
		$term_id                         = (int) $term->term_id;
		$guid                            = ssp_get_podcast_guid( $term_id );
		$derived_guid                    = $this->get_derived_series_guid( $term_id );
		$is_series_connected_to_castos = ssp_is_connected_to_castos()
			&& null !== $this->castos_handler->get_podcast_by_series( $term_id );
		$show_generate_guid             = ! $is_series_connected_to_castos && $guid !== $derived_guid;
		$modal_id                       = 'ssp-generate-series-guid-' . $term_id;
		$series_id                      = $term_id;

		ssp_renderer()->render(
			'settings/series-guid-update',
			compact( 'guid', 'derived_guid', 'is_series_connected_to_castos', 'show_generate_guid', 'modal_id', 'series_id' )
		);
	}

	/**
	 * Enqueue GUID scripts for series term edit screens.
	 *
	 * @since 3.18.0
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_series_guid_scripts( $hook ) {
		if ( 'term.php' !== $hook || ssp_series_taxonomy() !== filter_input( INPUT_GET, 'taxonomy' ) ) {
			return;
		}

		wp_enqueue_script( 'ssp-series-guid' );
		wp_localize_script(
			'ssp-series-guid',
			'ssp_series_guid',
			array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'ssp_generate_series_guid' ),
				'action'        => 'ssp_generate_series_guid',
				'saved_message' => __( '✓ New GUID saved.', 'seriously-simple-podcasting' ),
				'error_message' => __( 'The podcast GUID could not be saved. Please try again.', 'seriously-simple-podcasting' ),
			)
		);
	}

	/**
	 * Renders the "Set as default podcast" toggle or a label if already default.
	 *
	 * @param \WP_Term $term
	 */
	protected function show_default_series_toggle( $term ) {
		if ( ! current_user_can( 'manage_podcast' ) ) {
			return;
		}

		$is_default = (int) $term->term_id === ssp_get_default_series_id();

		ssp_renderer()->render(
			'settings/series-default-toggle',
			compact( 'is_default' )
		);
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

		$feed_fields      = $this->settings_handler->get_feed_fields();
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
		$image_width   = 'auto';
		$image_height  = 'auto';

		$series_img_title = __( 'Podcast Image', 'seriously-simple-podcasting' );
		$upload_btn_text  = __( 'Choose podcast image', 'seriously-simple-podcasting' );
		$upload_btn_value = __( 'Add Image', 'seriously-simple-podcasting' );
		$upload_btn_title = __( 'Choose an image file', 'seriously-simple-podcasting' );
		$series_img_desc  = __(
			'Set an image as the artwork for the podcast page. No image will be set if not provided.',
			'seriously-simple-podcasting'
		);

		$upload_image = ssp_renderer()->fetch(
			'settings/podcast-upload-image',
			compact(
				'series_img_title',
				'taxonomy',
				'default_image',
				'src',
				'image_width',
				'image_height',
				'series_settings',
				'media_id',
				'upload_btn_title',
				'upload_btn_text',
				'upload_btn_value',
				'series_img_desc'
			)
		);

		$mode = 'create' === strtolower( $mode ) ? 'create' : 'update';
		ssp_renderer()->render( "settings/podcast-image-$mode", compact( 'series_img_title', 'upload_image' ) );
	}

	/**
	 * @param \WP_Term $term
	 *
	 * @return int|null
	 * @since 2.7.3
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

		$columns['series_id']       = __( 'ID', 'seriously-simple-podcasting' );
		$columns['series_image']    = __( 'Podcast Image', 'seriously-simple-podcasting' );
		$columns['series_feed_url'] = __( 'Podcast feed URL', 'seriously-simple-podcasting' );
		$columns['posts']           = __( 'Episodes', 'seriously-simple-podcasting' );
		$columns                    = apply_filters( 'ssp_admin_columns_series', $columns );

		return $columns;
	}

	/**
	 * Display column data in series list table
	 *
	 * @param string  $column_data Default column content
	 * @param string  $column_name Name of current column
	 * @param integer $term_id ID of term
	 *
	 * @return string
	 * Todo: get rid of HTML
	 */
	public function add_series_columns( $column_data, $column_name, $term_id ) {

		switch ( $column_name ) {
			case 'series_feed_url':
				$series   = get_term( $term_id, ssp_series_taxonomy() );
				$feed_url = $this->get_series_feed_url( $series );

				$column_data = '<a href="' . esc_attr( $feed_url ) . '" target="_blank">' . esc_html( $feed_url ) . '</a>';
				break;
			case 'series_image':
				$series      = get_term( $term_id, ssp_series_taxonomy() );
				$source      = $this->get_series_image_src( $series );
				$column_data = <<<HTML
<img id="{$series->name}_image_preview" src="{$source}" width="auto" height="auto" style="max-width:50px;" />
HTML;
				break;
			case 'series_id':
				$column_data = esc_html( $term_id );
				break;
		}

		return $column_data;
	}


	/**
	 * @param \WP_Term $term
	 *
	 * @return string
	 * @since 2.7.3
	 */
	public function get_series_feed_url( $term ) {
		return $this->series_repository->get_feed_url( $term );
	}

	/**
	 * Hook to allow saving series metadata.
	 */
	public function save_series_meta( $term_id, $tt_id ) {
		$this->insert_update_series_meta( $term_id, $tt_id );
		$this->maybe_set_default_series( $term_id );

		$this->save_series_data_to_castos( $term_id );
	}

	/**
	 * Store a newly created series GUID before its first Castos push.
	 *
	 * @since 3.18.0
	 *
	 * @param int $series_id Series term ID.
	 */
	public function maybe_store_series_guid( $series_id ) {
		if ( RSS_Import_Handler::is_importing() ) {
			return;
		}

		if ( $this->series_handler->is_creating_default_series() ) {
			$this->adopt_legacy_guid( $series_id );
		}

		$term = get_term( $series_id, ssp_series_taxonomy() );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		$this->feed_handler->ensure_stored_guid( $term->slug, (int) $term->term_id );
	}

	/**
	 * Store the legacy GUID as the new default series' own GUID.
	 *
	 * The default series ID is assigned only after its term is created, so the
	 * legacy GUID can't resolve for it yet and a derived GUID would shadow it.
	 *
	 * @since 3.18.0
	 *
	 * @param int $series_id Default series term ID.
	 */
	protected function adopt_legacy_guid( $series_id ) {
		$legacy_guid = get_option( 'ss_podcasting_data_guid', '' );
		if ( ! $legacy_guid || ssp_get_podcast_guid( $series_id ) ) {
			return;
		}

		ssp_update_option( 'data_guid', $legacy_guid, $series_id );
	}

	/**
	 * Derive this site's GUID for a series from its feed URL.
	 *
	 * @since 3.18.0
	 *
	 * @param int $series_id Series term ID.
	 *
	 * @return string Derived GUID, or an empty string when the series is unavailable.
	 */
	protected function get_derived_series_guid( $series_id ) {
		$term = get_term( $series_id, ssp_series_taxonomy() );
		if ( ! $term || is_wp_error( $term ) ) {
			return '';
		}

		return $this->feed_handler->get_derived_guid( $term->slug );
	}

	/**
	 * Sets this series as the default podcast if the toggle was checked.
	 *
	 * @param int $term_id
	 */
	protected function maybe_set_default_series( $term_id ) {
		if ( empty( $_POST['ssp_default_series'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_podcast' ) ) {
			return;
		}

		if ( (int) $term_id === ssp_get_default_series_id() ) {
			return;
		}

		ssp_update_option( 'default_series', (int) $term_id );
	}

	/**
	 * Store the Series Feed title as the Series name
	 *
	 * @param int $term_id Term ID.
	 */
	public function save_series_data_to_castos( $term_id ) {
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}

		// During an RSS import the podcast's real data and GUID don't exist yet;
		// one push fires on import completion instead.
		if ( RSS_Import_Handler::is_importing() ) {
			return;
		}

		$default_series_id = $this->series_handler->default_series_id();
		if ( ! $default_series_id ) {
			/**
			 * It means we're creating the default series now,
			 * and we'll update the default Podcast series ID instead of creating the new one.
			 *
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
	 * @param int    $term_id
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
				$error = new \WP_Error();
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
	 * @param string   $name
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
	 * @param array      $term_links
	 * @param string     $taxonomy
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
	 * @param array  $actions Actions array.
	 * @param string $tag Tag name.
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
	 * @param array    $actions
	 * @param \WP_Term $term
	 *
	 * @return array
	 */
	public function add_term_actions( $actions, $term ) {

		$link = '<a href="%s">' . __( 'Edit&nbsp;Feed&nbsp;Details', 'seriously-simple-podcasting' ) . '</a>';
		$link = sprintf(
			$link,
			sprintf(
				'edit.php?post_type=%s&page=podcast_settings&tab=feed-details&feed-series=%s',
				SSP_CPT_PODCAST,
				$term->slug
			)
		);

		$actions['edit_feed_details'] = $link;

		return $actions;
	}

	/**
	 * @param int    $series_id
	 * @param array  $response
	 * @param string $status
	 *
	 * @return void
	 */
	public function update_series_sync_status( $series_id, $response, $status ) {

		$this->series_handler->update_sync_status( $series_id, $status );
	}
}
