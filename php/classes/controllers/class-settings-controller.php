<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Renderers\Settings_Renderer;

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
 * @author      Hugh Lashbrooke, Sergey Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.0
 */
class Settings_Controller extends Controller {

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
	protected $settings_handler;

	/**
	 * @var Series_Handler
	 * */
	protected $series_handler;

	/**
	 * @var Renderer
	 * */
	protected $renderer;

	/**
	 * @var Settings_Renderer
	 * */
	protected $settings_renderer;


	/**
	 * Constructor
	 *
	 * @param string $file Plugin base file.
	 * @param string $version Plugin version
	 */
	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );

		$this->settings_base = self::SETTINGS_BASE;

		$this->settings_handler  = new Settings_Handler();
		$this->series_handler    = new Series_Handler();
		$this->renderer          = new Renderer();
		$this->settings_renderer = Settings_Renderer::instance();

		$this->register_hooks_and_filters();
	}

	/**
	 * Set up all hooks and filters
	 */
	public function register_hooks_and_filters() {

		add_action( 'init', array( $this, 'load_settings' ), 15 );

		//Todo: Can we use pre_update_option_ss_podcasting_data_title action instead?
		add_action( 'admin_init', array( $this, 'maybe_feed_saved' ), 11 );

		// Exclude series feed from the default feed
		add_action( 'create_series', array( $this, 'exclude_feed_from_default' ) );

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
		add_action( 'update_option_' . $this->settings_base . 'podmotor_disconnect', array(
			$this,
			'maybe_disconnect_from_castos'
		), 10, 2 );

		// Quick and dirty colour picker implementation
		// If we do not have the WordPress core colour picker field, then we don't break anything
		add_action( 'admin_footer', function () {
			?>
			<script>
				jQuery(document).ready(function ($) {
					if ("function" === typeof $.fn.wpColorPicker) {
						$('.ssp-color-picker').wpColorPicker();
					}
				});
			</script>
			<?php
		}, 99 );
	}

	/**
	 * Triggers after a feed/series is saved, attempts to push the data to Castos
	 */
	public function maybe_feed_saved() {
		$this->series_handler->maybe_save_series();
	}

	/**
	 * Adding it here, and not via default settings for the backward compatibility.
	 * So if users have their old series included in the default feed, it should not affect them.
	 * */
	public function exclude_feed_from_default( $term_id ) {
		$option_name = 'ss_podcasting_exclude_feed_' . $term_id;

		update_option( $option_name, 'on' );
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

		/* @todo Add Back In When Doing New Analytics Pages */
		/* add_submenu_page( 'edit.php?post_type=podcast', __( 'Analytics', 'seriously-simple-podcasting' ), __( 'Analytics', 'seriously-simple-podcasting' ), 'manage_podcast', 'podcast_settings&view=analytics', array(
			 $this,
			 'settings_page',
		 ) );*/
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
		array_push( $links, $settings_link );

		return $links;
	}

	/**
	 * Load admin javascript
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		global $pagenow;
		$page  = ( isset( $_GET['page'] ) ? filter_var( $_GET['page'], FILTER_SANITIZE_STRING ) : '' );
		$pages = array( 'post-new.php', 'post.php' );
		if ( in_array( $pagenow, $pages, true ) || ( ! empty( $page ) && 'podcast_settings' === $page ) ) {
			wp_enqueue_media();
		}

		// // @todo add back for analytics launch
		// wp_enqueue_script( 'jquery-ui-datepicker' );
		// wp_register_style( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' );
		// wp_enqueue_style( 'jquery-ui' );

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// wp_enqueue_script( 'plotly', 'https://cdn.plot.ly/plotly-latest.min.js', SSP_VERSION, true );

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

		$section = $this->get_settings_section();
		$data    = $this->get_settings_data( $section );

		if ( ! $data ) {
			return;
		}

		// Get data for specific feed series.
		$series_id  = 0;
		$feed_series = '';
		$section_title = $data['title'];
		if ( 'feed-details' === $section ) {
			$feed_series = ( isset( $_REQUEST['feed-series'] ) ? filter_var( $_REQUEST['feed-series'], FILTER_SANITIZE_STRING ) : '' );
			if ( $feed_series && 'default' !== $feed_series ) {

				// Get selected series.
				$series = get_term_by( 'slug', esc_attr( $feed_series ), 'series' );

				// Store series ID for later use.
				$series_id = $series->term_id;

				// Append series name to section title.
				if ( $series ) {
					$section_title .= ': ' . $series->name;
				}
			}
		}

		// Add section to page.
		add_settings_section( $section, $section_title, array( $this, 'settings_section' ), 'ss_podcasting' );

		if ( empty( $data['fields'] ) ) {
			return;
		}

		foreach ( $data['fields'] as $field ) {
			$this->register_settings_field( $section, $field, $feed_series, $series_id );
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
	protected function get_settings_section(){
		$tab = ( isset( $_POST['tab'] ) ? filter_var( $_POST['tab'], FILTER_SANITIZE_STRING ) : '' );
		if ( ! $tab ) {
			$tab = ( isset( $_GET['tab'] ) ? filter_var( $_GET['tab'], FILTER_SANITIZE_STRING ) : '' );
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
			$html = '<p>' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		}

		switch ( $section['id'] ) {
			case 'feed-details':
				$feed_series = isset( $_GET['feed-series'] ) ? esc_attr( $_GET['feed-series'] ) : 'default';

				$feed_url = ssp_get_feed_url( $feed_series );

				if ( $feed_url ) {
					$html .= '<p><a class="view-feed-link" href="' . esc_url( $feed_url ) . '" target="_blank"><span class="dashicons dashicons-rss"></span>' . __( 'View feed', 'seriously-simple-podcasting' ) . '</a></p>' . "\n";
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

		$field = $args['field'];

		$option_name = $this->settings_base . $field['id'];

		$default = isset( $field['default'] ) ? $field['default'] : '';

		$default_option_name = $option_name;

		// Get option value
		$data = get_option( $option_name, $default );

		// Get specific series data if applicable
		if ( isset( $args['feed-series'] ) && $args['feed-series'] ) {

			// Set placeholder to default feed option with specified default fallback
			if ( $data ) {
				$field['placeholder'] = $data;

				if ( in_array( $field['type'], array( 'checkbox', 'select', 'image' ), true ) ) {
					$default = $data;
				}
			}

			// Append series ID to option name
			$option_name .= '_' . $args['feed-series'];

			// Get series-specific option
			$data = get_option( $option_name, $default );
		}

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

		$html = '<div class="wrap" id="podcast_settings">' . "\n";

		$html .= '<h1>' . __( 'Podcast Settings', 'seriously-simple-podcasting' ) . '</h1>' . "\n";

		$tab = empty( $q_args['tab'] ) ? 'general' : $q_args['tab'];

		$html .= $this->show_page_messages();
		$html .= '<div id="main-settings">' . "\n";
		$html .= $this->show_page_tabs();
		$html .= $this->show_tab_before_settings( $tab );
		$html .= $this->show_tab_settings( $tab );
		$html .= $this->show_tab_after_settings( $tab );

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

				if ( isset( $_GET['feed-series'] ) ) {
					$tab_link = remove_query_arg( 'feed-series', $tab_link );
				}

				// Output tab
				$html .= '<a href="' . esc_url( $tab_link ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

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
		if ( isset( $tab ) && 'castos-hosting' === $tab ) {
			// Validate button
			$html .= '<p class="submit">' . "\n";
			$html .= '<input id="validate_api_credentials" type="button" class="button-primary" value="' . esc_attr( __( 'Validate Credentials', 'seriously-simple-podcasting' ) ) . '" />' . "\n";
			$html .= '<span class="validate-api-credentials-message"></span>' . "\n";
			$html .= '</p>' . "\n";
		}

		$disable_save_button_on_tabs = array( 'extensions', 'import' );

		if ( ! in_array( $tab, $disable_save_button_on_tabs ) ) {
			// Submit button
			$html .= '<p class="submit">' . "\n";
			$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
			$html .= '<input id="ssp-settings-submit" name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'seriously-simple-podcasting' ) ) . '" />' . "\n";
			$html .= '</p>' . "\n";
		}

		if ( 'import' === $tab ) {
			// Custom submits for Imports
			if ( ssp_is_connected_to_castos() ) {
				$html .= '<p class="submit">' . "\n";
				$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
				$html .= '<input id="ssp-settings-submit" name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Trigger import', 'seriously-simple-podcasting' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";
			}

			if ( ssp_get_external_rss_being_imported() ) {
				$html .= $this->render_external_import_process();
			} else {
				$html .= $this->render_external_import_form();
			}
		}

		$html .= '</form>' . "\n";

		$html .= '</div>' . "\n";

		$html .= $this->render_seriously_simple_sidebar();

		$html .= '</div>' . "\n";

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
						'<a href="http://www.seriouslysimplepodcasting.com/documentation/why-does-the-feed-password-protection-feature-not-work/" target="_blank">', '</a>' ) . '</p>';
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

		// Series submenu for feed details
		$series = get_terms( 'series', array( 'hide_empty' => false ) );

		if ( empty( $series ) ) {
			return $html;
		}

		$current_series = $this->get_current_series();
		$series_class   = 'default' === $current_series ? 'current' : '';

		$html .= '<div class="feed-series-list-container">' . "\n";
		$html .= '<span id="feed-series-toggle" class="series-open" title="' . __( 'Toggle series list display', 'seriously-simple-podcasting' ) . '"></span>' . "\n";

		$html .= '<ul id="feed-series-list" class="subsubsub series-open">' . "\n";
		$html .= '<li><a href="' . add_query_arg( array(
				'feed-series'      => 'default',
				'settings-updated' => false
			) ) . '" class="' . $series_class . '">' . __( 'Default feed', 'seriously-simple-podcasting' ) . '</a></li>';

		foreach ( $series as $s ) {
			$series_class = $current_series === $s->slug ? 'current' : '';

			$html .= '<li>' . "\n";
			$html .= ' | <a href="' . esc_url( add_query_arg( array(
					'feed-series'      => $s->slug,
					'settings-updated' => false
				) ) ) . '" class="' . $series_class . '">' . $s->name . '</a>' . "\n";
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
	public function maybe_disconnect_from_castos( $old_value, $new_value ) {
		if ( 'on' !== $new_value ) {
			return;
		}
		delete_option( $this->settings_base . 'podmotor_account_email' );
		delete_option( $this->settings_base . 'podmotor_account_api_token' );
		delete_option( $this->settings_base . 'podmotor_account_id' );
		delete_option( $this->settings_base . 'podmotor_disconnect' );
	}


	public function render_seriously_simple_sidebar() {
		$image_dir = $this->assets_url . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
		ob_start();
		include $this->template_path . DIRECTORY_SEPARATOR . 'settings-sidebar.php';

		return ob_get_clean();
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
			'second-line'          => array(
				'title'       => __( 'Second Line Themes', 'seriously-simple-podcasting' ),
				'image'       => $image_dir . 'second-line-themes.png',
				'url'         => 'https://secondlinethemes.com/?utm_source=ssp-settings',
				'description' => __( 'Looking for a dedicated podcast theme to use with Seriously Simple Podcasting? Check out SecondLineThemes!', 'seriously-simple-podcasting' ),
				'new_window'  => true,
				'button_text' => __( 'Get Second Line Themes', 'seriously-simple-podcasting' ),
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
	 * @return false|string
	 */
	public function render_external_import_process() {
		ob_start();
		?>
		<h3 class="ssp-ssp-external-feed-message">Your external RSS feed is being imported. Please leave this window open until it completes</h3>
		<div id="ssp-external-feed-progress"></div>
		<div id="ssp-external-feed-status"><p>Commencing feed import</p></div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Render the form to enable importing an external RSS feed
	 *
	 * @return false|string
	 */
	public function render_external_import_form() {
		$post_types = ssp_post_types( true );
		$series = get_terms( 'series', array( 'hide_empty' => false ) );
		ob_start();
		?>
		<p>If you have a podcast hosted on an external service (like Libsyn, Soundcloud or Simplecast) enter the url to
			the RSS Feed in the form below and the plugin will import the episodes for you.</p>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">RSS feed</th>
				<td>
					<input id="external_rss" name="external_rss" type="text" placeholder="https://externalservice.com/rss" value="" class="regular-text">
				</td>
			</tr>
			<?php if ( count( $post_types ) > 1 ) { ?>
				<tr>
					<th scope="row">Post Type</th>
					<td>
						<select id="import_post_type" name="import_post_type">
							<?php foreach ( $post_types as $post_type ) { ?>
								<option value="<?php echo $post_type; ?>"><?php echo ucfirst( $post_type ); ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			<?php } ?>
			<?php if ( count( $series ) > 1 ) { ?>
				<tr>
					<th scope="row">Series</th>
					<td>
						<select id="import_series" name="import_series">
							<?php foreach ( $series as $series_item ) { ?>
								<option value="<?php echo $series_item->term_id; ?>"><?php echo $series_item->name; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<p class="submit">
			<input id="ssp-settings-submit" name="Submit" type="submit" class="button-primary" value="<?php echo esc_attr( __( 'Begin Import Now', 'seriously-simple-podcasting' ) ) ?>"/>
		</p>
		<?php
		$html = ob_get_clean();

		return $html;
	}
}
