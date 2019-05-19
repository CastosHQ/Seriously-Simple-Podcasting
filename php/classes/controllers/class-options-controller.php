<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Options_Handler;

class Options_Controller extends Controller {

	protected $options_base;

	protected $options;

	/**
	 * Constructor
	 *
	 * @param string $file Plugin base file.
	 * @param string $version Plugin version
	 */
	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );

		$this->options_base = 'ss_podcasting_';

		$this->register_hooks_and_filters();
	}

	public function register_hooks_and_filters() {
		add_action( 'init', array( $this, 'load_options' ), 11 );

		// Register podcast options.
		add_action( 'admin_init', array( $this, 'register_options' ) );

		// Add options page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
	}

	/**
	 * Load settings
	 */
	public function load_options() {
		$options_handler = new Options_Handler();
		$this->options   = $options_handler->options_fields();
	}

	/**
	 * Register plugin options
	 *
	 * @return void
	 */
	public function register_options() {
		if ( is_array( $this->options ) ) {
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

			foreach ( $this->options as $section => $data ) {

				if ( $current_section && $current_section !== $section ) {
					continue;
				}

				// Get data for specific feed series.
				$title_tail = '';
				$series_id  = 0;

				$section_title = $data['title'] . $title_tail;

				// Add section to page.
				add_settings_section( $section, $section_title, array( $this, 'options_section' ), 'ss_podcasting' );

				if ( ! empty( $data['fields'] ) ) {

					foreach ( $data['fields'] as $field ) {

						// Validation callback for field.
						$validation = '';
						if ( isset( $field['callback'] ) ) {
							$validation = $field['callback'];
						}

						// Get field option name.
						$option_name = $this->options_base . $field['id'];

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
						add_settings_field(
							$field['id'],
							$field['label'],
							array(
								$this,
								'display_field',
							),
							'ss_podcasting',
							$section,
							array(
								'field'       => $field,
								'prefix'      => $this->options_base,
								'feed-series' => $series_id,
								'class'       => $container_class,
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Options Section
	 *
	 * @param string $section section.
	 */
	public function options_section( $section ) {
		$html = '<p>' . $this->options[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Add options page to menu
	 *
	 * @return void
	 */
	public function add_menu_item() {
		add_submenu_page(
			'edit.php?post_type=podcast',
			__( 'Podcast Options', 'seriously-simple-podcasting' ),
			__( 'Options', 'seriously-simple-podcasting' ),
			'manage_podcast',
			'podcast_options',
			array(
				$this,
				'options_page',
			)
		);
	}

	/**
	 * Generate HTML for options page
	 * @return void
	 */
	public function options_page() {

		$q_args = wp_parse_args(
			$_GET,
			array(
				'post_type' => null,
				'page'      => null,
				'view'      => null,
				'tab'       => null,
			)
		);

		array_walk(
			$q_args,
			function ( &$entry ) {
				$entry = sanitize_title( $entry );
			}
		);

		// Build page HTML
		$html = '<div class="wrap" id="podcast_options">' . "\n";

		$html .= '<h1>' . __( 'Podcast Options', 'seriously-simple-podcasting' ) . '</h1>' . "\n";

		$tab = 'general';

		$html .= '<div id="main-settings">' . "\n";

		// Show page tabs
		if ( is_array( $this->options ) && 1 < count( $this->options ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;

			foreach ( $this->options as $section => $data ) {

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

		if ( isset( $_GET['options-updated'] ) ) {
			$html .= '<br/>
						<div class="updated notice notice-success is-dismissible">
							<p>' . sprintf( __( '%1$s Options updated.', 'seriously-simple-podcasting' ), '<b>' . str_replace( '-', ' ', ucwords( $tab ) ) . '</b>' ) . '</p>
						</div>';
		}

		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

		// Get options fields
		ob_start();
		if ( isset( $tab ) && 'import' !== $tab ) {
			settings_fields( 'ss_podcasting' );
		}
		do_settings_sections( 'ss_podcasting' );
		$html .= ob_get_clean();

		// Submit button
		$html .= '<p class="submit">' . "\n";
		$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
		$html .= '<input id="ssp-options-submit" name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Options', 'seriously-simple-podcasting' ) ) . '" />' . "\n";
		$html .= '</p>' . "\n";

		$html .= '</form>' . "\n";

		$html .= '</div>' . "\n";

		$html .= '</div>' . "\n";

		echo $html;
	}
}
