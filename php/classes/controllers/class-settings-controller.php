<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\RSS_Import_Handler;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Renderers\Settings_Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

/**
 * SSP Settings
 *
 * @package Seriously Simple Podcasting
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SettingsController class
 *
 * Handles plugin settings page
 *
 * @author      Hugh Lashbrooke, Serhiy Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.0
 */
class Settings_Controller {

	use Useful_Variables;

	const SETTINGS_BASE = 'ss_podcasting_';

	/**
	 * Base string for option name keys
	 *
	 * @var string
	 */
	protected $settings_base;

	/**
	 * Settings Fields
	 * Created in Settings_Handler
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * @var Settings_Handler
	 * */
	public $settings_handler;

	/**
	 * @var Series_Handler
	 * */
	protected $series_handler;

	/**
	 * @var Castos_Handler
	 * */
	public $castos_handler;

	/**
	 * @var Renderer
	 * */
	protected $renderer;

	/**
	 * @var Settings_Renderer
	 * */
	protected $settings_renderer;

	/**
	 * @var Episode_Repository
	 * */
	protected $episode_repository;


	/**
	 * Constructor
	 *
	 * @param Settings_Handler $settings_handler
	 * @param Settings_Renderer $settings_renderer
	 * @param Renderer $renderer
	 * @param Series_Handler $series_handler
	 * @param Castos_Handler $castos_handler
	 * @param Episode_Repository $episode_repository
	 */
	public function __construct( $settings_handler, $settings_renderer, $renderer, $series_handler, $castos_handler, $episode_repository ) {
		$this->init_useful_variables();

		$this->settings_base = self::SETTINGS_BASE;

		$this->settings_handler   = $settings_handler;
		$this->settings_renderer  = $settings_renderer;
		$this->renderer           = $renderer;
		$this->series_handler     = $series_handler;
		$this->castos_handler     = $castos_handler;
		$this->episode_repository = $episode_repository;

		$this->register_hooks_and_filters();
	}

	/**
	 * Set up all hooks and filters
	 */
	public function register_hooks_and_filters() {

		add_action( 'init', array( $this, 'load_settings' ), 15 );

		//Todo: Can we use pre_update_option_ss_podcasting_data_title action instead?
		add_action( 'admin_init', array( $this, 'maybe_feed_saved' ), 11 );

		// Register podcast settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page.
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array( $this, 'add_plugin_links' ) );

		// Load scripts and styles for settings page.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );

		// Trigger the disconnect action
		add_filter( 'pre_update_option_' . $this->settings_base . 'podmotor_disconnect', array(
			$this,
			'maybe_disconnect_from_castos'
		), 10, 2 );

		// Add podcasts sync status to the sync settings
		add_filter( 'ssp_field_data', array( $this, 'provide_podcasts_sync_status' ), 10, 2 );

		$this->generate_dynamic_color_scheme();
	}

	/**
	 * @param $data
	 * @param $args
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function provide_podcasts_sync_status( $data, $args ) {
		if ( isset( $args['field']['id'] ) && 'podcasts_sync' === $args['field']['id'] ) {
			$data = (array) $data;
			$res = $this->castos_handler->get_podcasts();
			if ( empty( $res['status'] ) || 'success' !== $res['status'] || ! isset( $res['data']['podcast_list'] ) ) {
				$data['statuses'] = null;

				return $data;
			}

			// First, prepare all SSP podcasts with a "none" status, and after, update them with the data retrieved from Castos.
			$statuses     = array();
			foreach ( (array) $args['field']['options'] as $series_id => $v ) {
				$statuses[ $series_id ] = new Sync_Status( Sync_Status::SYNC_STATUS_NONE );
			}

			$castos_podcasts = (array) $res['data']['podcast_list'];

			// Update statuses with the data retrieved from Castos.
			foreach ( $castos_podcasts as $podcast ) {
				if ( ! isset( $podcast['series_id'] ) || ! array_key_exists( $podcast['series_id'], $statuses ) ) {
					continue;
				}

				$status = $this->castos_handler->retrieve_sync_status_by_podcast_data( $podcast );

				// If status is none, let's try to guess the sync status
				if( Sync_Status::SYNC_STATUS_NONE === $status->status ){
					$status = $this->guess_podcast_sync_status( $podcast );
				}

				$statuses[ $podcast['series_id'] ] = $status;
			}

			$data['statuses'] = $statuses;
		}

		return $data;
	}

	/**
	 * @param array $podcast
	 *
	 * @return Sync_Status
	 */
	protected function guess_podcast_sync_status( $podcast ) {
		$episodes = $this->episode_repository->get_podcast_episodes( $podcast['series_id'], 10 );

		foreach ( $episodes as $episode ) {
			$episode_id = get_post_meta( $episode->ID, 'podmotor_episode_id', true );
			if ( ! $episode_id ) {
				return new Sync_Status( Sync_Status::SYNC_STATUS_NONE );
			}
		}

		return new Sync_Status( Sync_Status::SYNC_STATUS_SYNCED );
	}

	protected function generate_dynamic_color_scheme() {
		$color_settings = $this->settings_handler->get_player_color_settings();
		foreach ( $color_settings as $color_setting ) {
			add_action( 'update_option_' . $this->settings_base . $color_setting['id'], function () {
				$dynamic_style_path = $this->get_dynamic_style_path();
				wp_mkdir_p( dirname( $dynamic_style_path ) );
				file_put_contents( $dynamic_style_path, $this->generate_player_css() );
				update_option( self::SETTINGS_BASE . 'dynamic_style_version', wp_generate_password( 6, false ) );
			}, 10, 2 );
		}
	}

	/**
	 * @return string
	 */
	protected function generate_player_css() {
		$color_settings = $this->settings_handler->get_player_color_settings();

		$css = '';
		foreach ( $color_settings as $color_setting ) {
			if ( ! empty( $color_setting['css_var'] ) ) {
				$default = empty( $color_setting['default'] ) ? '' : $color_setting['default'];

				$value = ssp_get_option( $color_setting['id'], $default );
				if ( $value ) {
					foreach ( (array) $color_setting['css_var'] as $var ) {
						$css .= sprintf( '%s:%s;', $var, $value );
					}
				}
			}
		}

		return sprintf( ':root {%s}', $css );
	}

	protected function get_dynamic_style_path(){
		$upload_dir = wp_upload_dir()['basedir'];
		return $upload_dir . '/ssp/css/ssp-dynamic-style.css';
	}

	/**
	 * Triggers after a feed/series is saved, attempts to push the data to Castos
	 */
	public function maybe_feed_saved() {
		$this->series_handler->maybe_save_series();
	}

	/**
	 * Add settings page to menu
	 *
	 * @return void
	 */
	public function add_menu_item() {
		add_submenu_page( 'edit.php?post_type=' . SSP_CPT_PODCAST, __( 'Podcast Settings', 'seriously-simple-podcasting' ), __( 'Settings', 'seriously-simple-podcasting' ), 'manage_podcast', 'podcast_settings', array(
			$this,
			'settings_page',
		) );

		add_submenu_page( 'edit.php?post_type=podcast' . SSP_CPT_PODCAST, __( 'Extensions', 'seriously-simple-podcasting' ), __( 'Extensions', 'seriously-simple-podcasting' ), 'manage_podcast', 'podcast_settings&tab=extensions', array(
			$this,
			'settings_page',
		) );
	}

	/**
	 * Add links to plugin list table
	 *
	 * @param array $links Default links.
	 *
	 * @return array $links Modified links
	 */
	public function add_plugin_links( $links ) {
		$settings_link = '<a href="edit.php?post_type=' . SSP_CPT_PODCAST . '&page=podcast_settings">' . __( 'Settings', 'seriously-simple-podcasting' ) . '</a>';
		$upgrade_link = '<a href="https://castos.com/podcast-hosting-wordpress/?utm_source=ssp&utm_medium=plugin-settings&utm_campaign=upgrade">' . __( 'Upgrade', 'seriously-simple-podcasting' ) . '</a>';

		array_unshift( $links, $settings_link );
		array_push( $links, $upgrade_link );

		return $links;
	}

	/**
	 * Load admin javascript
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		global $pagenow;
		$page  = ( isset( $_GET['page'] ) ? filter_var( $_GET['page'], FILTER_DEFAULT ) : '' );
		$pages = array( 'post-new.php', 'post.php' );
		if ( in_array( $pagenow, $pages, true ) || ( ! empty( $page ) && 'podcast_settings' === $page ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/**
	 * Enqueue Styles
	 */
	public function enqueue_styles() {
		wp_register_style( 'ssp-settings', esc_url( $this->assets_url . 'css/settings.css' ), array(), $this->version );
		wp_enqueue_style( 'ssp-settings' );
	}

	/**
	 * Load settings
	 */
	public function load_settings() {
		$this->settings = $this->settings_handler->settings_fields();
	}


	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( 'podcast_settings' !== filter_input( INPUT_GET, 'page' ) &&
		     'ss_podcasting' !== filter_input( INPUT_POST, 'option_page' ) ) {
			return;
		}

		$tab = $this->get_current_tab();

		$data = $this->get_settings_data( $tab );

		if ( ! $data ) {
			return;
		}

		if ( isset( $data['sections'] ) ) {
			foreach ( $data['sections'] as $section_id => $section_data ) {
				$is_section_valid = true;
				if ( isset( $section_data['condition_callback'] ) ) {
					$callback = $section_data['condition_callback'];
					if ( is_string( $callback ) && function_exists( $callback ) ) {
						$is_section_valid = call_user_func( $callback );
					}
				}

				if ( $is_section_valid ) {
					$this->register_settings_section( $section_id, $section_data );
				}
			}

			return;
		}

		// Get data for specific feed series.
		$series_id   = 0;
		$feed_series = '';
		if ( 'feed-details' === $tab ) {
			$feed_series = ( isset( $_REQUEST['feed-series'] ) ? filter_var( $_REQUEST['feed-series'], FILTER_DEFAULT ) : '' );
			if ( $feed_series && 'default' !== $feed_series ) {
				$series = get_term_by( 'slug', esc_attr( $feed_series ), 'series' );
				$series_id = $series->term_id;

				// Append series name to section title.
				if ( $series ) {
					$data['title'] .= ': ' . $series->name;
				}
			}
		}

		$this->register_settings_section( $tab, $data, $feed_series, $series_id );
	}

	/**
	 * @param string $section_id
	 * @param array $section_data
	 * @param string $feed_series
	 * @param int $series_id
	 *
	 * @return void
	 */
	protected function register_settings_section( $section_id, $section_data, $feed_series = '', $series_id = 0 ) {
		$section_title = isset( $section_data['title'] ) ? $section_data['title'] : '';

		$default_section_args = $section_data['fields'] ? array(
			'before_section' => sprintf( '<div class="ssp-settings ssp-settings-%s">', esc_attr( $section_id ) ),
			'after_section'  => '</div><!--ssp-settings section-->',
			'section_class'  => '',
		) : array();

		// Override default args with args from settings if they exist.
		$args = array_merge( $default_section_args, array_intersect_key( $section_data, $default_section_args ) );

		// Add section to page.
		add_settings_section( $section_id, $section_title, array( $this, 'settings_section' ), 'ss_podcasting', $args );

		if ( empty( $section_data['fields'] ) ) {
			return;
		}

		foreach ( $section_data['fields'] as $field ) {
			$this->register_settings_field( $section_id, $field, $feed_series, $series_id );
		}
	}

	/**
	 * @param string $section
	 *
	 * @return array|null
	 */
	protected function get_settings_data( $section ) {
		$data = isset( $this->settings[ $section ] ) ? $this->settings[ $section ] : null;

		if ( 'integrations' === $section ) {
			$integration = $this->get_current_integration();

			foreach ( $data['items'] as $item ) {
				if ( $integration === $item['id'] ) {
					$data = $item;
					break;
				}
			}
		}

		return $data;
	}

	/**
	 * @return string
	 */
	protected function get_current_tab(){
		$tab = ( isset( $_POST['tab'] ) ? filter_var( $_POST['tab'], FILTER_DEFAULT ) : '' );
		if ( ! $tab ) {
			$tab = ( isset( $_GET['tab'] ) ? filter_var( $_GET['tab'], FILTER_DEFAULT ) : '' );
		}

		return $tab ?: 'general';
	}

	/**
	 * @param string $section
	 * @param array $field
	 * @param string $feed_series
	 * @param int $series_id
	 */
	protected function register_settings_field( $section, $field, $feed_series, $series_id ){
		// only show the exclude_feed field on the non default feed settings
		if ( 'exclude_feed' === $field['id'] ) {
			if ( empty( $feed_series ) || 'default' === $feed_series ) {
				return;
			}
		}

		// Validation callback for field.
		$validation = '';
		if ( isset( $field['callback'] ) ) {
			$validation = $field['callback'];
		}

		// Get field option name.
		$option_name = $this->settings_base . $field['id'];

		// Append series ID if selected.
		if ( $series_id ) {
			$option_name .= '_' . $series_id;
		}

		// Register setting.
		register_setting( 'ss_podcasting', $option_name, $validation );

		// If field is hidden, lets hide the settings parent <tr>, otherwise it shows redundant empty space
		if ( 'hidden' === $field['type'] ) {
			$field['container_class'] = isset( $field['container_class'] ) ? $field['container_class'] : '';
			$field['container_class'] .= ' hidden';
			$field['label'] = '';
		}

		$container_class = '';
		if ( isset( $field['container_class'] ) && ! empty( $field['container_class'] ) ) {
			$container_class = $field['container_class'];
		}

		// Add field to page.
		add_settings_field( $field['id'], $field['label'],
			array(
				$this,
				'display_field',
			),
			'ss_podcasting',
			$section,
			array(
				'field'       => $field,
				'prefix'      => $this->settings_base,
				'feed-series' => $series_id,
				'class'       => $container_class
			)
		);
	}

	/**
	 * Settings Section
	 *
	 * @param array $section section.
	 */
	public function settings_section( $section ) {
		$html = '';

		if ( ! empty( $this->settings[ $section['id'] ]['description'] ) ) {
			$html .= '<p>' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		}

		switch ( $section['id'] ) {
			case 'feed-details':
				$feed_series = isset( $_GET['feed-series'] ) ? esc_attr( $_GET['feed-series'] ) : 'default';

				$term = get_term_by( 'slug', $feed_series, 'series' );

				if ( $term ) {
					$edit_podcast_url = sprintf( 'term.php?taxonomy=series&tag_ID=%d&post_type=%s', $term->term_id, SSP_CPT_PODCAST );

					$html .= '<p><a class="view-feed-link" href="' . esc_url( $edit_podcast_url ) . '">
								<span class="dashicons dashicons-edit"></span>' . __( 'Edit Podcast Settings', 'seriously-simple-podcasting' ) .
							 '</a></p>' . "\n";
				}

				$feed_url = ssp_get_feed_url( $feed_series );

				if ( $feed_url ) {
					$html .= '<p><a class="view-feed-link" href="' . esc_url( $feed_url ) . '" target="_blank"><span class="dashicons dashicons-rss"></span>' . __( 'View feed', 'seriously-simple-podcasting' ) . '</a></p>' . "\n";
				}
				break;

			case 'import':
				if ( ssp_get_external_rss_being_imported() ) {
					$progress = RSS_Import_Handler::get_import_data( 'import_progress', 0 );
					$html     .= $this->render_external_import_process( $progress );
				} else {
					$html .= $this->render_external_import_form();
				}
				break;

			case 'extensions':
				$html .= $this->render_seriously_simple_extensions();
				break;

			case 'integrations':
				$integration = $this->get_current_integration();
				if ( ! empty( $this->settings['integrations']['items'][ $integration ]['description'] ) ) {
					$html = '<p>' . $this->settings['integrations']['items'][ $integration ]['description'] . '</p>' . "\n";
				}
				break;
		}

		echo $html;
	}

	/**
	 * Generate HTML for displaying fields
	 *
	 * @param array $args Field data
	 *
	 * @return void
	 */
	public function display_field( $args ) {

		$field         = $args['field'];
		$option_name   = $default_option_name = $this->settings_base . $field['id'];
		$is_feed_field = isset( $args['feed-series'] ) && $args['feed-series'];

		if ( $is_feed_field ) {
			$series_id   = $args['feed-series'];
			$option_name .= '_' . $series_id;
			$data = $this->settings_handler->get_feed_option( $field, $series_id );
		} else {
			$data = get_option( $option_name, isset( $field['default'] ) ? $field['default'] : '' );
		}

		$data = apply_filters( 'ssp_field_data', $data, $args );

		echo $this->settings_renderer->render_field( $field, $data, $option_name, $default_option_name );
	}

	/**
	 * Validate URL slug
	 *
	 * @param string $slug User input
	 *
	 * @return string       Validated string
	 */
	public function validate_slug( $slug ) {
		if ( $slug && strlen( $slug ) > 0 && '' !== $slug ) {
			$slug = urlencode( strtolower( str_replace( ' ', '-', $slug ) ) );
		}

		return $slug;
	}

	/**
	 * Generate HTML for settings page
	 * @return void
	 */
	public function settings_page() {

		$q_args = $this->get_query_args();

		$html = '<div class="wrap" id="ssp-settings-page">' . "\n";

		$html .= '<h1>' . __( 'Podcast Settings', 'seriously-simple-podcasting' ) . '</h1>' . "\n";

		$tab = empty( $q_args['tab'] ) ? 'general' : $q_args['tab'];

		$html .= $this->show_page_messages();
		$html .= '<div id="ssp-main-settings">' . "\n";
		$html .= $this->show_page_tabs();
		$html .= $this->show_tab_before_settings( $tab );
		$html .= $this->show_tab_settings( $tab );
		$html .= $this->show_tab_after_settings( $tab );
		$html .= '</div><!--ssp-main-settings-->' . "\n";
		$html .= $this->render_seriously_simple_sidebar();
		$html .= '</div><!--ssp-settings-page-->' . "\n";

		echo $html;
	}

	/**
	 * @return string
	 */
	protected function show_page_messages() {
		$html = '';
		if ( isset( $_GET['settings-updated'] ) ) {
			$tab = filter_input( INPUT_GET, 'tab' );
			$msg = $tab ?
				sprintf( __( '%1$s settings updated', 'seriously-simple-podcasting' ),  str_replace( '-', ' ', ucwords( $tab ) ) ) :
				__( 'Settings updated', 'seriously-simple-podcasting' );
			$html .= '<br/><div class="updated notice notice-success is-dismissible"><p><b>' . $msg . '</b></p></div>';
		}

		return apply_filters( 'ssp_settings_show_page_tabs', $html );
	}

	/**
	 * @return array
	 */
	protected function get_query_args() {
		$q_args = wp_parse_args( $_GET,
			array(
				'post_type' => null,
				'page'      => null,
				'view'      => null,
				'tab'       => null,
			)
		);

		array_walk( $q_args, function ( &$entry ) {
			$entry = sanitize_title( $entry );
		} );

		return $q_args;
	}

	/**
	 * @return string
	 */
	protected function show_page_tabs() {
		$html = '';
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;

			foreach ( $this->settings as $section => $data ) {

				// Set tab class
				$class = 'nav-tab';
				$tab_defined = !empty( $_GET['tab'] );

				if ( ( $tab_defined && $section === $_GET['tab'] ) || ( ! $tab_defined && 0 === $c ) ) {
					$class .= ' nav-tab-active';
				}

				// Set tab link
				$tab_link = add_query_arg( 'tab', $section );

				if ( 'integrations' === $section ) {
					$tab_link = add_query_arg( 'integration', $this->get_current_integration(), $tab_link );
				}

				if ( isset( $_GET['settings-updated'] ) ) {
					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				if ( 'feed-details' === $section ) {
					$default_series_id   = ssp_get_default_series_id();
					$default_series      = get_term_by( 'id', $default_series_id, ssp_series_taxonomy() );
					$default_series_slug = $default_series ? $default_series->slug : 'default';
					$tab_link            = add_query_arg( 'feed-series', $default_series_slug, $tab_link );
				} else {
					$tab_link = remove_query_arg( 'feed-series', $tab_link );
				}

				$title = isset( $data['tab_title'] ) ? $data['tab_title'] : $data['title'];

				// Output tab
				$html .= '<a href="' . esc_url( $tab_link ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $title ) . '</a>' . "\n";

				++ $c;
			}

			$html .= '</h2>' . "\n";
		}

		return apply_filters( 'ssp_settings_show_page_tabs', $html );
	}

	/**
	 * @param string $tab
	 *
	 * @return string
	 */
	protected function show_tab_before_settings( $tab ) {
		$html = '';

		switch ( $tab ) {
			case 'security':
				$html .= $this->show_tab_security_content();
				break;
			case 'feed-details':
				$html .= $this->show_tab_feed_details_subtabs();
				break;
			case 'import':
				$current_admin_url = add_query_arg(
					array(
						'post_type' => SSP_CPT_PODCAST,
						'page'      => 'podcast_settings',
						'tab'       => 'import',
					),
					admin_url( 'edit.php' )
				);
				$html              .= '<form method="post" action="' . esc_url_raw( $current_admin_url ) . '" enctype="multipart/form-data">' . "\n";
				$html              .= '<input type="hidden" name="action" value="post_import_form" />';
				$html              .= wp_nonce_field( 'ss_podcasting_import', '_wpnonce', true, false );
				$html              .= wp_nonce_field( 'ss_podcasting_import', 'podcast_settings_tab_nonce', false, false );
				break;
			case 'integrations':
				$html .= $this->show_tab_integrations_subtabs();
				break;
		}

		if ( 'import' !== $tab ) {
			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

			// Add current series to posted data
			if ( 'feed-details' === $tab ) {
				$current_series = $this->get_current_series();
				$html           .= '<input type="hidden" name="feed-series" value="' . esc_attr( $current_series ) . '" />' . "\n";
			}

			// Add current integration to posted data
			if ( 'integrations' === $tab ) {
				$current_integration = $this->get_current_integration();
				$html .= '<input type="hidden" name="ssp_integration" value="' . esc_attr( $current_integration ) . '" />' . "\n";
			}
		}

		return apply_filters( sprintf( 'ssp_settings_show_tab_%s_before_settings', $tab ), $html );
	}

	/**
	 * Get settings fields
	 *
	 * @param string $tab
	 *
	 * @return mixed|void
	 */
	protected function show_tab_settings( $tab ) {
		ob_start();
		if ( isset( $tab ) && 'import' !== $tab ) {
			settings_fields( 'ss_podcasting' );
			wp_nonce_field( 'ss_podcasting_' . $tab, 'podcast_settings_tab_nonce', false );
		}
		do_settings_sections( 'ss_podcasting' );
		$html = ob_get_clean();

		return apply_filters( sprintf( 'ssp_settings_show_tab_%s_settings', $tab ), $html );
	}

	/**
	 * @param string $tab
	 *
	 * @return string
	 */
	protected function show_tab_after_settings( $tab ) {
		$html = '';

		$disable_save_button_on_tabs = array( 'extensions', 'import' );

		if ( ! in_array( $tab, $disable_save_button_on_tabs ) ) {
			// Submit button
			$html .= '<p class="submit">' . "\n";
			$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
			$html .= '<input id="ssp-settings-submit" name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'seriously-simple-podcasting' ) ) . '" />' . "\n";
			$html .= '</p>' . "\n";
		}

		$html .= '</form>' . "\n";

		return apply_filters( sprintf( 'ssp_settings_show_tab_%s_after_settings', $tab ), $html );
	}

	/**
	 * @return string
	 */
	protected function show_tab_security_content() {
		$html = '';
		if ( function_exists( 'php_sapi_name' ) ) {
			$sapi_type = php_sapi_name();
			if ( strpos( $sapi_type, 'fcgi' ) !== false ) {
				$html .= '<br/><div class="update-nag">';
				$html .= '<p>' . sprintf( __( 'It looks like your server has FastCGI enabled, which will prevent the feed password protection feature from working. You can fix this by following %1$sthis quick guide%2$s.', 'seriously-simple-podcasting' ),
						'<a href="https://support.castos.com/article/147-why-wont-the-password-i-set-for-my-rss-feed-in-wordpress-save" target="_blank">', '</a>' ) . '</p>';
				$html .= '</div>';
			}
		}

		return $html;
	}

	/**
	 * @return string
	 */
	protected function show_tab_feed_details_subtabs() {

		$html = '';

		$series = $this->series_handler->get_feed_details_series();

		if ( empty( $series ) ) {
			return $html;
		}

		$current_series = $this->get_current_series();

		$html .= '<div class="feed-series-list-container">' . "\n";
		$html .= '<span id="feed-series-toggle" class="series-open" title="' . __( 'Toggle series list display', 'seriously-simple-podcasting' ) . '"></span>' . "\n";

		$html .= '<ul id="feed-series-list" class="subsubsub series-open">' . "\n";

		foreach ( $series as $k => $s ) {
			$slug = $s ? $s->slug : 'default';
			$series_class = $current_series === $slug ? 'current' : '';

			$html .= '<li>' . "\n";
			if( 0 !== $k ){
				$html .= ' | ';
			}

			$podcast_name = $s ? $s->name : 'Default';

			$name = 0 === $k ? $this->series_handler->default_series_name( $podcast_name ) : $podcast_name;

			$html .= '<a href="' . esc_url( add_query_arg( array(
					'feed-series'      => $s ? $s->slug : 'default',
					'settings-updated' => false
				) ) ) . '" class="' . $series_class . '">' . $name . '</a>' . "\n";
			$html .= '</li>' . "\n";
		}

		$html .= '</ul>' . "\n";
		$html .= '<br class="clear" />' . "\n";
		$html .= '</div>' . "\n";

		return $html;
	}

	/**
	 * @return string
	 */
	protected function show_tab_integrations_subtabs() {
		if ( empty( $this->settings['integrations']['items'] ) ) {
			return '<h2>' . __( 'No integrations found', 'seriously-simple-podcasting' ) . '</h2>';
		}

		$integrations = $this->settings['integrations']['items'];
		$current = $this->get_current_integration();

		return $this->renderer->fetch( 'settings/integrations-subtabs', compact( 'integrations', 'current' ) );
	}

	/**
	 * @return string
	 */
	protected function get_current_integration() {
		$integration = $this->get_current_parameter( 'integration' );
		if ( 'default' === $integration && ! empty( $_POST['ssp_integration'] ) ) {
			$integration = $_POST['ssp_integration'];
		}

		// If no integration provided, let's get the first one.
		if ( 'default' === $integration ) {
			$item        = reset( $this->settings['integrations']['items'] );
			$integration = isset( $item['id'] ) ? $item['id'] : '';
		}

		return $integration;
	}

	/**
	 * @return string
	 */
	protected function get_current_series() {
		return $this->get_current_parameter( 'feed-series' );
	}

	/**
	 * @return string
	 */
	protected function get_current_parameter( $param ) {
		$current = 'default';

		if ( ! empty( $_GET[ $param ] ) ) {
			$current = esc_attr( $_GET[ $param ] );
		}

		return $current;
	}

	/**
	 * Disconnects a user from the Castos Hosting service by deleting their API keys
	 * Triggered by the update_option_ss_podcasting_podmotor_disconnect action hook
	 */
	public function maybe_disconnect_from_castos( $new_value ) {
		if ( 'on' === $new_value ) {
			delete_option( $this->settings_base . 'podmotor_account_email' );
			delete_option( $this->settings_base . 'podmotor_account_api_token' );
			delete_option( $this->settings_base . 'podmotor_account_id' );
			delete_option( $this->settings_base . 'podmotor_disconnect' );
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function render_seriously_simple_sidebar() {
		$image_dir = $this->assets_url . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
		$link      = 'https://castos.com/1ksubs?utm_source=WordPress&utm_medium=Settings&utm_campaign=Banner';
		$is_connected = ssp_is_connected_to_castos();
		$img = $is_connected ?
			'<a href="' . $link . '" target="_blank"><img src="' . $image_dir . 'castos-connected-banner.jpg"></a>' :
			'<img src="' . $image_dir . 'castos-plugin-settings-banner.jpg">';

		return $this->renderer->fetch( 'settings-sidebar', compact( 'img', 'is_connected' ) );
	}

	public function render_seriously_simple_extensions() {
		add_thickbox();

		$image_dir  = $this->assets_url . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;

		$extensions = array(
			'connect'              => array(
				'title'       => __( 'Castos Podcast Hosting', 'seriously-simple-podcasting' ),
				'image'       => $image_dir . 'castos-icon-extension.jpg',
				'url'         => SSP_CASTOS_APP_URL,
				'description' => __( 'Host your podcast media files safely and securely in a CDN-powered cloud platform designed specifically to connect beautifully with Seriously Simple Podcasting.  Faster downloads, better live streaming, and take back security for your web server with Castos.', 'seriously-simple-podcasting' ),
				'button_text' => __( 'Get Castos Hosting', 'seriously-simple-podcasting' ),
				'new_window'  => true,
			),
			'stats'                => array(
				'title'       => __( 'Seriously Simple Podcasting Stats', 'seriously-simple-podcasting' ),
				'image'       => $image_dir . 'ssp-stats.jpg',
				'url'         => add_query_arg(
					array(
						'tab'       => 'plugin-information',
						'plugin'    => 'seriously-simple-stats',
						'TB_iframe' => 'true',
						'width'     => '772',
						'height'    => '859',
					),
					admin_url(
						'plugin-install.php'
					)
				),
				'thickbox'    => true,
				'description' => __( 'Seriously Simple Stats offers integrated analytics for your podcast, giving you access to incredibly useful information about who is listening to your podcast and how they are accessing it.', 'seriously-simple-podcasting' ),
			),
			'transcripts'          => array(
				'title'       => __( 'Seriously Simple Podcasting Transcripts', 'seriously-simple-podcasting' ),
				'image'       => $image_dir . 'ssp-transcripts.jpg',
				'url'         => add_query_arg(
					array(
						'tab'       => 'plugin-information',
						'plugin'    => 'seriously-simple-transcripts',
						'TB_iframe' => 'true',
						'width'     => '772',
						'height'    => '859',
					),
					admin_url(
						'plugin-install.php'
					)
				),
				'thickbox'    => true,
				'description' => __( 'Seriously Simple Transcripts gives you a simple and automated way for you to add downloadable transcripts to your podcast episodes. It’s an easy way for you to provide episode transcripts to your listeners without taking up valuable space in your episode content.', 'seriously-simple-podcasting' ),
			),
			'speakers'             => array(
				'title'       => __( 'Seriously Simple Podcasting Speakers', 'seriously-simple-podcasting' ),
				'image'       => $image_dir . 'ssp-speakers.jpg',
				'url'         => add_query_arg(
					array(
						'tab'       => 'plugin-information',
						'plugin'    => 'seriously-simple-speakers',
						'TB_iframe' => 'true',
						'width'     => '772',
						'height'    => '859',
					),
					admin_url(
						'plugin-install.php'
					)
				),
				'thickbox'    => true,
				'description' => __( 'Does your podcast have a number of different speakers? Or maybe a different guest each week? Perhaps you have unique hosts for each episode? If any of those options describe your podcast then Seriously Simple Speakers is the add-on for you!', 'seriously-simple-podcasting' ),
			),
			'genesis'              => array(
				'title'       => __( 'Seriously Simple Podcasting Genesis Support ', 'seriously-simple-podcasting' ),
				'image'       => $image_dir . 'ssp-genesis.jpg',
				'url'         => add_query_arg(
					array(
						'tab'       => 'plugin-information',
						'plugin'    => 'seriously-simple-podcasting-genesis-support',
						'TB_iframe' => 'true',
						'width'     => '772',
						'height'    => '859',
					),
					admin_url(
						'plugin-install.php'
					)
				),
				'thickbox'    => true,
				'description' => __( 'The Genesis compatibility add-on for Seriously Simple Podcasting gives you full support for the Genesis theme framework. It adds support to the podcast post type for the features that Genesis requires. If you are using Genesis and Seriously Simple Podcasting together then this plugin will make your website look and work much more smoothly.', 'seriously-simple-podcasting' ),
			),
			'paid-memberships-pro' => array(
				'title'       => __( 'Paid Memberships Pro', 'seriously-simple-podcasting' ),
				'image'       => $image_dir . 'pmpro.jpg',
				'url'         => 'https://www.paidmembershipspro.com/',
				'description' => __( 'Connect with your membership site participants by automatically sending new member signups from Paid Memberships Pro to Castos as Private Podcast Subscribers. This native integration automates the entire process of adding (and removing) members from your private podcast to create another great way to engage your members.', 'seriously-simple-podcasting' ),
				'new_window'  => true,
				'button_text' => __( 'Get Paid Memberships Pro', 'seriously-simple-podcasting' ),
			),
		);

		if ( ssp_is_elementor_ok() ) {
			$elementor_templates = array(
				'title'       => __( 'Elementor Templates', 'seriously-simple-podcasting' ),
				'image'       => $image_dir . 'elementor.jpg',
				'url'         => wp_nonce_url( admin_url( 'edit.php?post_type=' . SSP_CPT_PODCAST . '&page=podcast_settings&tab=extensions&elementor_import_templates=true' ), '', 'import_template_nonce' ),
				'description' => __( 'Looking for a custom elementor template to use with Seriously Simple Podcasting? Click here to import all of them righ now!', 'seriously-simple-podcasting' ),
				'button_text' => __( 'Import Templates', 'seriously-simple-podcasting' ),
			);
			$extensions = array_slice($extensions, 0, 1, true) + array("elementor-templates" =>  $elementor_templates) + array_slice($extensions, 1, count($extensions)-1, true);
		}

		$html = '<div id="ssp-extensions">';
		foreach ( $extensions as $extension ) {
			$html .= '<div class="ssp-extension"><h3 class="ssp-extension-title">' . $extension['title'] . '</h3>';
			$html .= $this->render_extension_link( $extension, true );
			$html .= '<p></p>';
			$html .= '<p>' . $extension['description'] . '</p>';
			$html .= '<p></p>';
			$html .= $this->render_extension_link( $extension, false );
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}


	/**
	 * Render extension link.
	 *
	 * @return string
	 * *@since 2.10.0
	 *
	 * @var array $args
	 */
	protected function render_extension_link( $args, $is_image ) {
		$defaults = array(
			'title'       => '',
			'image'       => '',
			'url'         => '',
			'description' => '',
			'button_text' => __( 'Get this Extension' ),
			'new_window'  => false,
			'thickbox'    => false,
		);

		$args = wp_parse_args( $args, $defaults );

		$inner = $args['button_text'];

		if ( $is_image ) {
			$inner = sprintf( '<img width="880" height="440" src="%s"
								class="attachment-showcase size-showcase wp-post-image" alt="" title="%s">',
				$args['image'], $args['title'] );
		}

		$target = $args['new_window'] ? ' target="_blank" ' : '';

		if ( $args['thickbox'] ) {
			$classes[] = 'thickbox';
		}

		if ( ! $is_image ) {
			$classes[] = 'button-secondary';
		}

		$class = isset( $classes ) ? implode( ' ', $classes ) : '';

		return sprintf(
			'<a href="%s" title="%s" class="%s"%s>%s</a>',
			$args['url'], $args['title'], $class, $target, $inner
		);
	}

	/**
	 * Render the progress bar to show the importing RSS feed progress
	 *
	 * @return string
	 */
	public function render_external_import_process( $progress ) {
		return $this->renderer->fetch( 'settings/import-rss-info', compact( 'progress' ) );
	}

	/**
	 * Render the form to enable importing an external RSS feed
	 *
	 * @return string
	 */
	public function render_external_import_form() {
		$post_types = ssp_post_types( true );
		$series     = get_terms( 'series', array( 'hide_empty' => false ) );

		return $this->renderer->fetch( 'settings/import-rss-form', compact( 'post_types', 'series' ) );
	}
}
