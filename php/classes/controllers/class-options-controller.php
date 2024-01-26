<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Options_Handler;

class Options_Controller extends Controller {

	/**
	 * @var Options_Handler
	 */
	protected $options_handler;

	/**
	 * @var string
	 */
	protected $options_base;

	/**
	 * @var array
	 */
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

		// @todo inject via DI
		$this->options_handler = new Options_Handler();

		$this->register_hooks_and_filters();
	}

	public function register_hooks_and_filters() {

		// load the options from the Options Handler
		add_action( 'init', array( $this, 'load_options' ), 11 );

		// Register podcast options.
		add_action( 'admin_init', array( $this, 'register_options' ) );

		// Add options page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
	}

	/**
	 * Load Options
	 */
	public function load_options() {
		$this->options = $this->options_handler->options_fields();
	}


	/**
	 * Register plugin options
	 *
	 * @return void
	 */
	public function register_options() {
		if ( is_array( $this->options ) ) {
			$tab = ( isset( $_POST['tab'] ) ? filter_var( $_POST['tab'], FILTER_DEFAULT ) : '' );
			// Check posted/selected tab.
			$current_section = 'subscribe';
			if ( ! empty( $tab ) ) {
				$current_section = $tab;
			} else {
				$tab = ( isset( $_GET['tab'] ) ? filter_var( $_GET['tab'], FILTER_DEFAULT ) : '' );
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
				add_settings_section( $section, $section_title, array( $this, 'options_section' ), 'options_page' );

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
						register_setting( 'ssp_options', $option_name, $validation );

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
							'options_page',
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
			'edit.php?post_type=' . SSP_CPT_PODCAST,
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

		$tab = 'subscribe';

		$html .= '<div id="main-options">' . "\n";

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
			settings_fields( 'ssp_options' );
		}
		do_settings_sections( 'options_page' );
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
		$option_name         = $this->options_base . $field['id'];
		$default_option_name = $option_name;

		// Get field default
		$default = '';
		if ( isset( $field['default'] ) ) {
			$default = $field['default'];
		}

		// Get option value
		$data = get_option( $option_name, $default );

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

		// @todo this code is replicated from the Settings Controller and should be refactored somehow
		switch ( $field['type'] ) {
			case 'text':
			case 'password':
			case 'number':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '" class="' . $class . '"/>' . "\n";
				break;
			case 'colour-picker':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $data ) . '" class="' . $class . '"/>' . "\n";
				break;
			case 'text_secret':
				$placeholder = $field['placeholder'];
				if ( $data ) {
					$placeholder = __( 'Password stored securely', 'seriously-simple-podcasting' );
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="" class="' . $class . '"/>' . "\n";
				break;
			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" class="' . $class . '">' . $data . '</textarea><br/>' . "\n";
				break;
			case 'checkbox':
				$checked = '';
				if ( $data && 'on' === $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" ' . $checked . ' class="' . $class . '"/>' . "\n";
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
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" class="' . $class . '" /> ' . $v . '</label><br/>';
				}
				break;
			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '" class="' . $class . '">';
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
				$html .= '<img id="' . esc_attr( $default_option_name ) . '_preview" src="' . esc_attr( $data ) . '" style="max-width:400px;height:auto;" /><br/>' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '_button" type="button" class="button" value="' . __( 'Upload new image', 'seriously-simple-podcasting' ) . '" />' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '_delete" type="button" class="button" value="' . __( 'Remove image', 'seriously-simple-podcasting' ) . '" />' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '" type="hidden" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $data ) . '"/><br/>' . "\n";
				break;
			case 'feed_link_series':
				// Set feed URL based on site's permalink structure
				$url = ssp_get_feed_url( 'podcast-slug' );

				$html .= esc_url( $url ) . "\n";
				break;
			case 'podcast_url':
				$slug        = apply_filters( 'ssp_archive_slug', _x( SSP_CPT_PODCAST, 'Podcast URL slug', 'seriously-simple-podcasting' ) );
				$podcast_url = $this->home_url . $slug;

				$html .= '<a href="' . esc_url( $podcast_url ) . '" target="_blank">' . $podcast_url . '</a>';
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

}
