<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;

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
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.0
 */
class Settings_Controller extends Controller {

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
	 * @var Import_Controller
	 * */
	protected $import_controller;

	/**
	 * @var Extensions_Controller
	 * */
	protected $extensions_controller;

	/**
	 * Constructor
	 *
	 * @param string $file Plugin base file.
	 * @param string $version Plugin version
	 */
	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );

		$this->settings_base = 'ss_podcasting_';

		$this->settings_handler      = new Settings_Handler();
		$this->series_handler        = new Series_Handler();
		$this->import_controller     = new Import_Controller();
		$this->extensions_controller = new Extensions_Controller( $file, $version );

		$this->register_hooks_and_filters();
	}

	/**
	 * Set up all hooks and filters
	 */
	public function register_hooks_and_filters() {

		add_action( 'init', array( $this, 'load_settings' ), 11 );

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

		// Mark date on which feed redirection was activated.
		add_action( 'update_option', array( $this, 'mark_feed_redirect_date' ), 10, 3 );

		// Trigger the disconnect action
		add_action( 'update_option_' . $this->settings_base . 'podmotor_disconnect', array( $this, 'maybe_disconnect_from_castos' ), 10, 2 );

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
	 * Add settings page to menu
	 *
	 * @return void
	 */
	public function add_menu_item() {
		add_submenu_page( 'edit.php?post_type=podcast', __( 'Podcast Settings', 'seriously-simple-podcasting' ), __( 'Settings', 'seriously-simple-podcasting' ), 'manage_podcast', 'podcast_settings', array(
			$this,
			'settings_page',
		) );

		add_submenu_page( 'edit.php?post_type=podcast', __( 'Extensions', 'seriously-simple-podcasting' ), __( 'Extensions', 'seriously-simple-podcasting' ), 'manage_podcast', 'podcast_settings&tab=extensions', array(
			$this,
			'settings_page',
		) );

		/* @todo Add Back In When Doing New Analytics Pages */
		/* add_submenu_page( 'edit.php?post_type=podcast', __( 'Analytics', 'seriously-simple-podcasting' ), __( 'Analytics', 'seriously-simple-podcasting' ), 'manage_podcast', 'podcast_settings&view=analytics', array(
			 $this,
			 'settings_page',
		 ) );*/

		add_submenu_page( null, __( 'Upgrade', 'seriously-simple-podcasting' ), __( 'Upgrade', 'seriously-simple-podcasting' ), 'manage_podcast', 'upgrade', array(
			$this,
			'show_upgrade_page',
		) );
	}

	/**
	 * Show the upgrade page
	 */
	public function show_upgrade_page() {
		$ssp_redirect = ( isset( $_GET['ssp_redirect'] ) ? filter_var( $_GET['ssp_redirect'], FILTER_SANITIZE_STRING ) : '' );
		$ssp_dismiss_url = add_query_arg( array(
				'ssp_dismiss_upgrade' => 'dismiss',
				'ssp_redirect'        => rawurlencode( $ssp_redirect ),
				'nonce'               => wp_create_nonce( 'ssp_dismiss_upgrade' ),
			), admin_url( 'index.php' ) );
		include( $this->template_path . DIRECTORY_SEPARATOR . 'settings-upgrade-page.php' );
	}

	/**
	 * Add links to plugin list table
	 *
	 * @param  array $links Default links.
	 *
	 * @return array $links Modified links
	 */
	public function add_plugin_links( $links ) {
		$settings_link = '<a href="edit.php?post_type=podcast&page=podcast_settings">' . __( 'Settings', 'seriously-simple-podcasting' ) . '</a>';
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
		$page = ( isset( $_GET['page'] ) ? filter_var( $_GET['page'], FILTER_SANITIZE_STRING ) : '' );
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
		$this->settings   = $this->settings_handler->settings_fields();
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {
			$tab = ( isset( $_POST['tab'] ) ? filter_var( $_POST['tab'], FILTER_SANITIZE_STRING ) : '' );
			// Check posted/selected tab.
			$current_section = 'general';
			if ( ! empty( $tab ) ) {
				$current_section = $tab;
			} else {
				$tab = ( isset( $_GET['tab'] ) ? filter_var( $_GET['tab'], FILTER_SANITIZE_STRING ) : '' );
				if ( ! empty( $tab ) ) {
					$current_section = $tab;
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section !== $section ) {
					continue;
				}

				// Get data for specific feed series.
				$title_tail = '';
				$series_id  = 0;
				if ( 'feed-details' === $section ) {
					$feed_series = ( isset( $_REQUEST['feed-series'] ) ? filter_var( $_REQUEST['feed-series'], FILTER_SANITIZE_STRING ) : '' );
					if ( ! empty( $feed_series ) && 'default' !== $feed_series ) {

						// Get selected series.
						$series = get_term_by( 'slug', esc_attr( $feed_series ), 'series' );

						// Store series ID for later use.
						$series_id = $series->term_id;

						// Append series name to section title.
						if ( $series ) {
							$title_tail = ': ' . $series->name;
						}
					}
				}

				$section_title = $data['title'] . $title_tail;

				// Add section to page.
				add_settings_section( $section, $section_title, array( $this, 'settings_section' ), 'ss_podcasting' );

				if ( ! empty( $data['fields'] ) ) {

					foreach ( $data['fields'] as $field ) {

						// only show the exclude_feed field on the non default feed settings
						if ( ( 'exclude_feed' === $field['id'] ) ) {
							if ( empty( $feed_series ) || 'default' === $feed_series ) {
								continue;
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

						if ( 'hidden' === $field['type'] ) {
							continue;
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
				}
			}
		}
	}

	/**
	 * Settings Section
	 *
	 * @param string $section section.
	 */
	public function settings_section( $section ) {
		$html = '<p>' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";

		if ( 'feed-details' === $section['id'] ) {

			$feed_series = 'default';
			if ( isset( $_GET['feed-series'] ) ) {
				$feed_series = esc_attr( $_GET['feed-series'] );
			}

			$permalink_structure = get_option( 'permalink_structure' );

			if ( $permalink_structure ) {
				$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
				$feed_url  = $this->home_url . 'feed/' . $feed_slug;
			} else {
				$feed_url = $this->home_url . '?feed=' . $this->token;
			}

			if ( $feed_series && 'default' !== $feed_series ) {
				if ( $permalink_structure ) {
					$feed_url .= '/' . $feed_series;
				} else {
					$feed_url .= '&podcast_series=' . $feed_series;
				}
			}

			if ( $feed_url ) {
				$html .= '<p><a class="view-feed-link" href="' . esc_url( $feed_url ) . '" target="_blank"><span class="dashicons dashicons-rss"></span>' . __( 'View feed', 'seriously-simple-podcasting' ) . '</a></p>' . "\n";
			}
		}

		if ( 'extensions' === $section['id'] ) {
			$html .= $this->extensions_controller->render_seriously_simple_extensions();
		}

		echo $html;
	}

	/**
	 * Generate HTML for displaying fields
	 *
	 * @param  array $args Field data
	 *
	 * @return void
	 */
	public function display_field( $args ) {

		$field = $args['field'];

		$html = '';

		// Get option name
		$option_name         = $this->settings_base . $field['id'];
		$default_option_name = $option_name;

		// Get field default
		$default = '';
		if ( isset( $field['default'] ) ) {
			$default = $field['default'];
		}

		// Get option value
		$data = get_option( $option_name, $default );

		// Get specific series data if applicable
		if ( isset( $args['feed-series'] ) && $args['feed-series'] ) {

			$option_default = '';

			// Set placeholder to default feed option with specified default fallback
			if ( $data ) {
				$field['placeholder'] = $data;

				if ( in_array( $field['type'], array( 'checkbox', 'select', 'image' ), true ) ) {
					$option_default = $data;
				}
			}

			// Append series ID to option name
			$option_name .= '_' . $args['feed-series'];

			// Get series-specific option
			$data = get_option( $option_name, $option_default );

		}

		// Get field class if supplied
		$class = '';
		if ( isset( $field['class'] ) ) {
			$class = $field['class'];
		}

		// Get parent class if supplied
		$parent_class = '';
		if ( isset( $field['parent_class'] ) ) {
			$parent_class = $field['parent_class'];
		}

		// Get data attributes if supplied
		$data_attrs = '';
		if ( ! empty( $field['data'] ) && is_array( $field['data'] ) ) {
			foreach ( $field['data'] as $k => $v ) {
				$data_attrs .= sprintf( ' data-%s="%s" ', $k, $v );
			}
		}

		switch ( $field['type'] ) {
			case 'text':
			case 'password':
			case 'number':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '" class="' . $class . '"' . $data_attrs . '/>' . "\n";
				break;
			case 'colour-picker':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $data ) . '" class="' . $class . '"' . $data_attrs . '/>' . "\n";
				break;
			case 'text_secret':
				$placeholder = $field['placeholder'];
				if ( $data ) {
					$placeholder = __( 'Password stored securely', 'seriously-simple-podcasting' );
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="" class="' . $class . '"' . $data_attrs . '/>' . "\n";
				break;
			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" class="' . $class . '"' . $data_attrs . '>' . $data . '</textarea><br/>' . "\n";
				break;
			case 'checkbox':
				$checked = '';
				if ( $data && 'on' === $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" ' . $checked . ' class="' . $class . '"' . $data_attrs . '/>' . "\n";
				break;
			case 'checkbox_multi':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( in_array( $k, (array) $data, true ) ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" class="' . $class . '" /> ' . $v . '</label><br/>';
				}
				break;
			case 'radio':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( $k === $data ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" class="' . $class . '"' . $data_attrs . ' /> ' . $v . '</label><br/>';
				}
				break;
			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '" class="' . $class . '"' . $data_attrs . '>';
				$prev_group = '';
				foreach ( $field['options'] as $k => $v ) {

					$group = '';
					if ( is_array( $v ) ) {
						if ( isset( $v['group'] ) ) {
							$group = $v['group'];
						}
						$v = $v['label'];
					}

					if ( $prev_group && $group !== $prev_group ) {
						$html .= '</optgroup>';
					}

					$selected = false;
					if ( $k === $data ) {
						$selected = true;
					}

					if ( $group && $group !== $prev_group ) {
						$html .= '<optgroup label="' . esc_attr( $group ) . '">';
					}

					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . esc_html( $v ) . '</option>';

					$prev_group = $group;
				}
				$html .= '</select> ';
				break;
			case 'image':
				$html .= '<img id="' . esc_attr( $default_option_name ) . '_preview" src="' . esc_attr( $data ) . '" style="max-width:400px;height:auto;"' . $data_attrs . ' /><br/>' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '_button" type="button" class="button" value="' . __( 'Upload new image', 'seriously-simple-podcasting' ) . '" />' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '_delete" type="button" class="button" value="' . __( 'Remove image', 'seriously-simple-podcasting' ) . '" />' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '" type="hidden" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $data ) . '"/><br/>' . "\n";
				break;
			case 'feed_link':
				// Set feed URL based on site's permalink structure
				if ( get_option( 'permalink_structure' ) ) {
					$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
					$url       = $this->home_url . 'feed/' . $feed_slug;
				} else {
					$url = $this->home_url . '?feed=' . $this->token;
				}

				$html .= '<a href="' . esc_url( $url ) . '" target="_blank">' . $url . '</a>';
				break;
			case 'feed_link_series':
				// Set feed URL based on site's permalink structure
				if ( get_option( 'permalink_structure' ) ) {
					$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
					$url       = $this->home_url . 'feed/' . $feed_slug . '/series-slug';
				} else {
					$url = $this->home_url . '?feed=' . $this->token . '&podcast_series=series-slug';
				}

				$html .= esc_url( $url ) . "\n";
				break;
			case 'podcast_url':
				$slug        = apply_filters( 'ssp_archive_slug', _x( SSP_CPT_PODCAST, 'Podcast URL slug', 'seriously-simple-podcasting' ) );
				$podcast_url = $this->home_url . $slug;

				$html .= '<a href="' . esc_url( $podcast_url ) . '" target="_blank">' . $podcast_url . '</a>';
				break;
			case 'importing_podcasts':
				$data = ssp_get_importing_podcasts_count();
				$html .= '<input type="input" value="' . esc_attr( $data ) . '" class="' . $class . '" disabled' . $data_attrs . '/>' . "\n";
				break;
		}

		if ( ! in_array( $field['type'], array( 'feed_link', 'feed_link_series', 'podcast_url', 'hidden' ), true ) ) {
			switch ( $field['type'] ) {
				case 'checkbox_multi':
				case 'radio':
				case 'select_multi':
					$html .= '<br/><span class="description">' . esc_attr( $field['description'] ) . '</span>';
					break;
				default:
					$html .= '<label for="' . esc_attr( $field['id'] ) . '"><span class="description">' . wp_kses_post( $field['description'] ) . '</span></label>' . "\n";
					break;
			}
		}

		if ( $parent_class ) {
			$html = '<div class="' . $parent_class . '">' . $html . '</div>';
		}

		echo $html;
	}

	/**
	 * Validate URL slug
	 *
	 * @param  string $slug User input
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
	 * Mark redirect date for feed
	 *
	 * @param  string $option Name of option being updated
	 * @param  mixed $old_value Old value of option
	 * @param  mixed $new_value New value of option
	 *
	 * @return void
	 */
	public function mark_feed_redirect_date( $option, $old_value, $new_value ) {
		if ( 'ss_podcasting_redirect_feed' === $option ) {
			if ( ( $new_value != $old_value ) && 'on' === $new_value ) {
				$date = time();
				update_option( 'ss_podcasting_redirect_feed_date', $date );
			}
		}
	}

	/**
	 * Generate HTML for settings page
	 * @return void
	 */
	public function settings_page() {

		$q_args = wp_parse_args(
			$_GET,
			array(
				'post_type' => null,
				'page'      => null,
				'view'      => null,
				'tab'       => null
			)
		);

		array_walk( $q_args, function ( &$entry ) {
			$entry = sanitize_title( $entry );
		} );

		// Build page HTML
		$html = '<div class="wrap" id="podcast_settings">' . "\n";

		$html .= '<h1>' . __( 'Podcast Settings', 'seriously-simple-podcasting' ) . '</h1>' . "\n";

		$tab = 'general';
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$tab = $_GET['tab'];
		}

		$html .= '<div id="main-settings">' . "\n";

		// Show page tabs
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;

			foreach ( $this->settings as $section => $data ) {

				// Set tab class
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) {
					if ( 0 === $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link
				$tab_link = add_query_arg( array( 'tab' => $section ) );
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

		if ( isset( $_GET['settings-updated'] ) ) {
			$html .= '<br/><div class="updated notice notice-success is-dismissible">
									<p>' . sprintf( __( '%1$s settings updated.', 'seriously-simple-podcasting' ), '<b>' . str_replace( '-', ' ', ucwords( $tab ) ) . '</b>' ) . '</p>
								</div>';
		}

		if ( function_exists( 'php_sapi_name' ) && 'security' == $tab ) {
			$sapi_type = php_sapi_name();
			if ( strpos( $sapi_type, 'fcgi' ) !== false ) {
				$html .= '<br/><div class="update-nag">
									<p>' . sprintf( __( 'It looks like your server has FastCGI enabled, which will prevent the feed password protection feature from working. You can fix this by following %1$sthis quick guide%2$s.', 'seriously-simple-podcasting' ), '<a href="http://www.seriouslysimplepodcasting.com/documentation/why-does-the-feed-password-protection-feature-not-work/" target="_blank">', '</a>' ) . '</p>
								</div>';
			}
		}

		$current_series = '';

		// Series submenu for feed details
		if ( 'feed-details' == $tab ) {
			$series = get_terms( 'series', array( 'hide_empty' => false ) );

			if ( ! empty( $series ) ) {

				if ( isset( $_GET['feed-series'] ) && $_GET['feed-series'] && 'default' != $_GET['feed-series'] ) {
					$current_series = esc_attr( $_GET['feed-series'] );
					$series_class   = '';
				} else {
					$current_series = 'default';
					$series_class   = 'current';
				}

				$html .= '<div class="feed-series-list-container">' . "\n";
				$html .= '<span id="feed-series-toggle" class="series-open" title="' . __( 'Toggle series list display', 'seriously-simple-podcasting' ) . '"></span>' . "\n";

				$html .= '<ul id="feed-series-list" class="subsubsub series-open">' . "\n";
				$html .= '<li><a href="' . add_query_arg( array(
						'feed-series'      => 'default',
						'settings-updated' => false
					) ) . '" class="' . $series_class . '">' . __( 'Default feed', 'seriously-simple-podcasting' ) . '</a></li>';

				foreach ( $series as $s ) {

					if ( $current_series == $s->slug ) {
						$series_class = 'current';
					} else {
						$series_class = '';
					}

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

			}
		}

		if ( isset( $tab ) && 'import' == $tab ) {
			$current_admin_url = add_query_arg(
				array(
					'post_type' => SSP_CPT_PODCAST,
					'page'      => 'podcast_settings',
					'tab'       => 'import',
				),
				admin_url( 'edit.php' )
			);
			$html .= '<form method="post" action="' . esc_url_raw( $current_admin_url ) . '" enctype="multipart/form-data">' . "\n";
			$html .= '<input type="hidden" name="action" value="post_import_form" />';
			$html .= wp_nonce_field( 'ss_podcasting_import', '_wpnonce', true, false );
			$html .= wp_nonce_field( 'ss_podcasting_import', 'podcast_settings_tab_nonce', false, false );
		} else {
			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";
		}


		// Add current series to posted data
		if ( $current_series ) {
			$html .= '<input type="hidden" name="feed-series" value="' . esc_attr( $current_series ) . '" />' . "\n";
		}

		// Get settings fields
		// Get settings fields
		ob_start();
		if ( isset( $tab ) && 'import' !== $tab ) {
			settings_fields( 'ss_podcasting' );
			wp_nonce_field( 'ss_podcasting_' . $tab, 'podcast_settings_tab_nonce', false );
		}
		do_settings_sections( 'ss_podcasting' );
		$html .= ob_get_clean();

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
				$html .= $this->import_controller->render_external_import_process();
			} else {
				$html .= $this->import_controller->render_external_import_form();
			}
		}

		$html .= '</form>' . "\n";

		$html .= '</div>' . "\n";

		$html .= $this->extensions_controller->render_seriously_simple_sidebar();

		$html .= '</div>' . "\n";

		echo $html;
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
}
