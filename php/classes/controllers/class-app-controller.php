<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler;
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Handlers\Options_Handler;
use SeriouslySimplePodcasting\Handlers\Podping_Handler;
use SeriouslySimplePodcasting\Handlers\Roles_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Integrations\Memberpress\Memberpress_Integrator;
use SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator;
use SeriouslySimplePodcasting\Interfaces\Service;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Handlers\Upgrade_Handler;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Images_Handler;

use SeriouslySimplePodcasting\Ajax\Ajax_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;
use SeriouslySimplePodcasting\Integrations\LifterLMS\LifterLMS_Integrator;
use SeriouslySimplePodcasting\Integrations\Paid_Memberships_Pro\Paid_Memberships_Pro_Integrator;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Integrations\Blocks\Castos_Blocks;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Rest\Rest_Api_Controller;
use SeriouslySimplePodcasting\Integrations\Elementor\Elementor_Widgets;
use SeriouslySimplePodcasting\Traits\Useful_Variables;


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @author      Hugh Lashbrooke, Sergey Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.0
 */
class App_Controller {

	use Useful_Variables;

	// Controllers.
	/**
	 * @var Onboarding_Controller
	 */
	protected $onboarding_controller;

	/**
	 * @var Feed_Controller
	 */
	protected $feed_controller;

	/**
	 * @var Cron_Controller
	 */
	protected $cron_controller;

	/**
	 * @var Shortcodes_Controller
	 */
	protected $shortcodes_controller;

	/**
	 * @var Widgets_Controller
	 */
	protected $widgets_controller;

	/**
	 * @var DB_Migration_Controller
	 */
	protected $db_migration_controller;

	/**
	 * @var Episode_Controller
	 */
	public $episode_controller;

	/**
	 * @var Players_Controller
	 * */
	public $players_controller;

	/**
	 * @var Podcast_Post_Types_Controller
	 * */
	public $podcast_post_types_controller;

	/**
	 * @var Settings_Controller
	 * */
	public $settings_controller;

	/**
	 * @var Review_Controller
	 * */
	public $review_controller;


	// Handlers.

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
	 * @var Log_Helper
	 * */
	protected $logger;

	/**
	 * @var CPT_Podcast_Handler
	 */
	protected $cpt_podcast_handler;

	/**
	 * @var Roles_Handler
	 */
	protected $roles_handler;

	/**
	 * @var Feed_Handler
	 * */
	protected $feed_handler;

	/**
	 * @var Renderer
	 * */
	protected $renderer;

	/**
	 * @var Castos_Handler
	 * */
	protected $castos_handler;

	/**
	 * @var Podping_Handler
	 * */
	protected $podping_handler;

	/**
	 * @var Settings_Handler
	 * */
	protected $settings_handler;

	/**
	 * @var Settings_Handler
	 * */
	protected $options_handler;

	/**
	 * @var Images_Handler
	 * */
	protected $images_handler;

	/**
	 * @var Episode_Repository
	 * */
	protected $episode_repository;

	/**
	 * Admin_Controller constructor.
	 */
	public function __construct() {

		if ( ! ssp_is_php_version_ok() ) {
			return;
		}

		if ( ! ssp_is_vendor_ok() ) {
			return;
		}

		ssp_beta_check();

		$this->init_useful_variables();
		$this->bootstrap();
	}

	/**
	 * Set up all hooks and filters for this class
	 */
	protected function bootstrap() {

		global $images_handler; // Todo: get rid of global here
		$this->images_handler = $images_handler = new Images_Handler();

		$this->renderer = new Renderer();

		$this->ajax_handler = new Ajax_Handler();

		$this->upgrade_handler = new Upgrade_Handler();

		$this->feed_handler = new Feed_Handler();

		$this->settings_handler = new Settings_Handler();

		$this->options_handler = new Options_Handler();

		$this->episode_repository = new Episode_Repository();


		$this->feed_controller = new Feed_Controller( $this->feed_handler, $this->renderer );

		$this->onboarding_controller = new Onboarding_Controller( $this->renderer, $this->settings_handler );

		$this->db_migration_controller = DB_Migration_Controller::instance()->init();

		$this->roles_handler = new Roles_Handler();

		$this->cpt_podcast_handler = new CPT_Podcast_Handler( $this->roles_handler );

		$this->logger = new Log_Helper();

		$this->cron_controller = new Cron_Controller();

		$this->shortcodes_controller = new Shortcodes_Controller( $this->file, $this->version  );

		$this->widgets_controller = new Widgets_Controller( $this->file, $this->version );

		$this->castos_handler = new Castos_Handler();

		$this->podping_handler = new Podping_Handler( $this->logger );

		$this->admin_notices_handler = new Admin_Notifications_Handler( $this->token );

		if ( is_admin() ) {
			$this->admin_notices_handler->bootstrap();

			global $ssp_settings, $ssp_options;
			$ssp_settings = $this->settings_controller = new Settings_Controller( $this->file, SSP_VERSION );
			$ssp_options  = new Options_Controller( $this->file, SSP_VERSION );
		}

		$this->episode_controller            = new Episode_Controller( $this->renderer, $this->episode_repository );
		$this->players_controller            = new Players_Controller( $this->renderer, $this->options_handler, $this->episode_repository );
		$this->podcast_post_types_controller = new Podcast_Post_Types_Controller(
			$this->cpt_podcast_handler,
			$this->castos_handler,
			$this->admin_notices_handler,
			$this->podping_handler,
			$this->episode_repository
		);

		$this->review_controller = new Review_Controller( $this->admin_notices_handler, $this->renderer );


		// todo: further refactoring - get rid of global here
		global $ss_podcasting;
		$ss_podcasting = new Frontend_Controller( $this->episode_controller, $this->players_controller, $this->episode_repository );

		$this->init_integrations();
		$this->init_rest_api();
		$this->register_hooks_and_filters();

		// Handle localisation.
		$this->load_plugin_textdomain();
	}

	protected function init_integrations(){
		/*
		 * Gutenberg integration.
		 * Only load Blocks if the WordPress version is newer than 5.0.
		 */
		if ( version_compare( $this->get_wp_version(), '5.0', '>=' ) ) {
			new Castos_Blocks( $this->admin_notices_handler, $this->episode_repository, $this->players_controller, $this->renderer );
		}

		// Elementor integration.
		if ( ssp_is_elementor_ok() ) {
			new Elementor_Widgets();
		}

		// Yoast Schema integration.
		new Schema_Controller();

		// Paid Memberships Pro integration
		Paid_Memberships_Pro_Integrator::instance()->init( $this->feed_handler, $this->castos_handler, $this->logger, $this->admin_notices_handler );

		// Lifter LMS integration
		LifterLMS_Integrator::instance()->init( $this->feed_handler, $this->castos_handler, $this->logger );

		// Paid Memberships Pro integration
		Memberpress_Integrator::instance()->init( $this->feed_handler, $this->castos_handler, $this->logger, $this->admin_notices_handler );

		// Woocommerce Memberships integration
		WC_Memberships_Integrator::instance()->init( $this->feed_handler, $this->castos_handler, $this->logger, $this->admin_notices_handler );
	}

	/**
	 * Get any registered here services (handlers, helpers)
	 *
	 * @return Service|null
	 * */
	public function get_service( $id ) {
		$services = $this->get_available_services();

		return isset( $services[ $id ] ) ? $services[ $id ] : null;
	}

	/**
	 * @return Service[]
	 */
	public function get_available_services() {
		$properties = get_object_vars( $this );
		$services   = array();

		foreach ( $properties as $k => $v ) {
			if ( $v instanceof Service ) {
				$services[ $k ] = $v;
			}
		}

		return $services;
	}

	/**
	 * Gets current WP version.
	 *
	 * @return string
	 * */
	protected function get_wp_version(){
		global $wp_version;

		return $wp_version;
	}

	/**
	 * Init REST API
	 */
	protected function init_rest_api(){
		global $wp_version;

		// Only load WP REST API Endpoints if the WordPress version is newer than 4.7.
		if ( version_compare( $wp_version, '4.7', '>=' ) ) {
			global $ssp_wp_rest_api;
			$ssp_wp_rest_api = new Rest_Api_Controller();
		}
	}

	/**
	 * Register all relevant front end hooks and filters
	 */
	protected function register_hooks_and_filters() {
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Setup custom permalink structures.
		add_action( 'init', array( $this, 'setup_permastruct' ), 10 );

		// Run any updates required
		add_action( 'init', array( $this, 'maybe_run_plugin_updates' ), 11 );

		// Dismiss the categories update screen
		add_action( 'init', array( $this, 'dismiss_categories_update' ) ); //todo: can we move it to 'admin_init'?

		// Dismiss the categories update screen
		add_action( 'init', array( $this, 'disable_elementor_template_notice' ) );

		// Hide WP SEO footer text for podcast RSS feed.
		add_filter( 'wpseo_include_rss_footer', array( $this, 'hide_wp_seo_rss_footer' ) );

		if ( is_admin() ) {
			// process the import form submission
			add_action( 'admin_init', array( $this, 'submit_import_form' ) );

			// Admin JS & CSS.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 10, 1 );

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

			// Check for, setup or ignore import of existing podcasts.
			add_action( 'admin_init', array( $this, 'ignore_importing_existing_podcasts' ) );

			// Filter Embed HTML Code
			add_filter( 'embed_html', array( $this, 'ssp_filter_embed_code' ), 10, 1 );

		}

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
	 * Adds series term metaboxes to the new series form.
	 */
	public function add_series_term_meta_fields( $taxonomy ) {
		// Add series image upload metabox.
		$this->series_image_uploader( $taxonomy );
	}

	/**
	 * @return Settings_Handler|Service
	 */
	public function get_settings_handler() {
		return $this->get_service( 'settings_handler' );
	}

	/**
	 * @param \WP_Term $term
	 *
	 * @return void
	 *
	 * // Todo: move all the Series-related functions to the separate class
	 */
	protected function show_feed_info( $term ) {
		$edit_feed_url = sprintf(
			'edit.php?post_type=%s&page=podcast_settings&tab=feed-details&feed-series=%s',
			SSP_CPT_PODCAST,
			$term->slug
		);
		$edit_feed_url = admin_url( $edit_feed_url );

		$feed_fields = $this->get_settings_handler()->get_feed_fields();

		?>
		<tr class="form-field term-upload-wrap">
			<th scope="row">
				<label><?php echo __( 'Podcast Feed Details', 'seriously-simple-podcasting' ) ?></label>
				<p><a class="view-feed-link" href="<?php echo esc_url( $edit_feed_url ) ?>">
						<span class="dashicons dashicons-edit"></span>
						<?php echo __( 'Edit Feed Settings', 'seriously-simple-podcasting' ) ?></a></p>
				<p><a class="view-feed-link" href="<?php echo esc_url( ssp_get_feed_url( $term->slug ) ); ?>" target="_blank">
						<span class="dashicons dashicons-rss"></span>
						<?php echo __( 'View feed', 'seriously-simple-podcasting' ) ?>
					</a></p>
			</th>
			<td>
				<table style="border: 1px solid #ccc; width: 100%; padding: 0 10px;">
					<?php foreach ( $feed_fields as $field ) :
						$value = ssp_get_option( $field['id'], '', $term->term_id );
						if ( ! $value ) {
							$value = ssp_get_option( $field['id'] );
						}
						if ( ! $value || ! is_string( $value ) ) {
							continue;
						}
						if ( 'image' === $field['type'] ) {
							$value = sprintf('<img src="%s" style="width: 100px;">', $value );
						}
						?>
						<tr>
							<th><?php echo $field['label']; ?>:</th>
							<td><?php echo $value; ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
			</td>
		</tr>
		<?php
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
	 * @since 2.7.3
	 *
	 * @param \WP_Term $term
	 *
	 * @return int|null
	 */
	public function get_series_image_id( $term = null ) {
		if ( empty( $term ) ) {
			return null;
		}

		return get_term_meta( $term->term_id, $this->token . '_series_image_settings', true );
	}

	/**
	 * @since 2.7.3
	 *
	 * @param \WP_Term $term
	 *
	 * @return int|null
	 */
	public function get_series_image_src( $term ) {
		return ssp_get_podcast_image_src( $term );
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
		$series_img_desc  = __( "Set an image as the artwork for the podcast page. No image will be set if not provided.", 'seriously-simple-podcasting' );
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
		$this->save_series_data_to_feed( $term_id );
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
		$series_data              = $this->castos_handler->get_series_data_for_castos( $term_id );
		$series_data['series_id'] = $term_id;
		$this->castos_handler->update_podcast_data( $series_data );
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

		$columns['series_image']    = __( 'Podcast Image', 'seriously-simple-podcasting' );
		$columns['series_feed_url'] = __( 'Podcast feed URL', 'seriously-simple-podcasting' );
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
				$series   = get_term( $term_id, 'series' );
				$feed_url = $this->get_series_feed_url( $series );

				$column_data = '<a href="' . esc_attr( $feed_url ) . '" target="_blank">' . esc_html( $feed_url ) . '</a>';
				break;
			case 'series_image':
				$series = get_term( $term_id, 'series' );
				$source = $this->get_series_image_src( $series );
				$column_data      = <<<HTML
<img id="{$series->name}_image_preview" src="{$source}" width="auto" height="auto" style="max-width:50px;" />
HTML;
				break;
		}

		return $column_data;
	}

	/**
	 * @since 2.7.3
	 *
	 * @param \WP_Term $term
	 *
	 * @return string
	 */
	public function get_series_feed_url( $term ){
		$series_slug = $term->slug;

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

		return $feed_url;
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
	 * Load admin CSS
	 * @return void
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( ! $this->need_admin_scripts( $hook ) ) {
			return;
		}

		wp_register_style( 'ssp-admin', esc_url( $this->assets_url . 'admin/css/admin' . $this->script_suffix . '.css' ), array(), $this->version );
		wp_enqueue_style( 'ssp-admin' );

		// Datepicker
		wp_register_style( 'jquery-ui-datepicker-wp', esc_url( $this->assets_url . 'css/datepicker' . $this->script_suffix . '.css' ), array(), $this->version );
		wp_enqueue_style( 'jquery-ui-datepicker-wp' );

		wp_register_style( 'ssp-select2-css', esc_url( $this->assets_url . 'css/select2' . $this->script_suffix . '.css' ), array(), $this->version );
		wp_enqueue_style( 'ssp-select2-css' );

		/**
		 * Only load the peekabar styles when adding/editing podcasts
		 */
		if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
			global $post;
			if ( in_array( $post->post_type, ssp_post_types( true ) ) ) {
				wp_register_style( 'jquery-peekabar', esc_url( $this->assets_url . 'css/jquery-peekabar'. $this->script_suffix . '.css' ), array(), $this->version );
				wp_enqueue_style( 'jquery-peekabar' );
			}
		}

		/**
		 * Only load the jquery-ui CSS when the import settings screen is loaded
		 * @todo load this locally perhaps? and only the progress bar stuff?
		 */
		if ( 'podcast_page_podcast_settings' === $hook && isset( $_GET['tab'] ) && 'import' == $_GET['tab'] ) {
			//wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', array(), $this->version  );

			wp_register_style( 'jquery-ui-smoothness', esc_url( $this->assets_url . 'css/jquery-ui-smoothness'. $this->script_suffix . '.css' ), array(), $this->version );
			wp_enqueue_style( 'jquery-ui-smoothness' );

			wp_register_style( 'import-rss', esc_url( $this->assets_url . 'css/import-rss'. $this->script_suffix . '.css' ), array(), $this->version );
			wp_enqueue_style( 'import-rss' );

		}
	}

	protected function need_admin_scripts( $hook ) {
		return 'post.php' === $hook ||
			   'post-new.php' === $hook ||
			   strpos( $hook, 'ssp-onboarding' ) ||
			   $this->is_ssp_admin_page() ||
			   ( 'term.php' === $hook && Series_Handler::TAXONOMY === filter_input( INPUT_GET, 'taxonomy' ) );
	}

	/**
	 * Checks if it's an SSP admin page or not
	 *
	 * @return bool
	 */
	protected function is_ssp_admin_page() {
		return SSP_CPT_PODCAST === filter_input( INPUT_GET, 'post_type' );
	}

	/**
	 * Load admin JS
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {

		if ( ! $this->need_admin_scripts( $hook ) ) {
			return;
		}

		wp_register_script( 'ssp-admin', esc_url( $this->assets_url . 'js/admin' . $this->script_suffix . '.js' ), array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-datepicker'
		), $this->version );
		wp_enqueue_script( 'ssp-admin' );

		wp_register_script( 'ssp-settings', esc_url( $this->assets_url . 'js/settings' . $this->script_suffix . '.js' ), array( 'jquery' ), $this->version );
		wp_enqueue_script( 'ssp-settings' );

		wp_register_script( 'ssp-select2-js', esc_url( $this->assets_url . 'js/select2' . $this->script_suffix . '.js' ), array( 'jquery' ), $this->version );
		wp_enqueue_script( 'ssp-select2-js' );

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
		$this->podcast_post_types_controller->register_post_type();
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
	public function maybe_run_plugin_updates() {

		$previous_version = get_option( 'ssp_version', '1.0' );

		$this->upgrade_handler->run_upgrades( $previous_version );

		// always just check if the directory is ok
		ssp_get_upload_directory( false );

		update_option( 'ssp_version', $this->version );

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
				$footer_text = sprintf( __( '%1$sThank you for publishing with %2$sSeriously Simple Podcasting%3$s.%4$s', 'seriously-simple-podcasting' ), '<span id="footer-thankyou">', '<a href="https://castos.com/seriously-simple-podcasting/" target="_blank">', '</a>', '</span>' );
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
		$trigger_import_submit = __( 'Trigger sync', 'seriously-simple-podcasting' );
		if ( $trigger_import_submit === $submit ) {
			$import = sanitize_text_field( $_POST['ss_podcasting_podmotor_import'] );
			if ( 'on' === $import ) {
				$result         = $this->castos_handler->trigger_podcast_import();
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
				$import_podcast_data = false;
				if ( isset( $_POST['import_podcast_data'] ) ) {
					$import_podcast_data = filter_var( $_POST['import_podcast_data'], FILTER_VALIDATE_BOOLEAN );
				}
				$ssp_external_rss = array(
					'import_rss_feed'     => $external_rss,
					'import_post_type'    => $import_post_type,
					'import_series'       => $import_series,
					'import_podcast_data' => $import_podcast_data,
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
}
