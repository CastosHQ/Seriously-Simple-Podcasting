<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Settings class
 *
 * Handles plugin settings page
 *
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Classes
 * @since       2.0
 */
class SSP_Settings {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $home_url;
	private $token;
	private $settings_base;
	private $settings;

	/**
	 * Constructor
	 * @param 	string $file Plugin base file
	 * @return 	void
	 */
	public function __construct( $file ) {
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
		$this->home_url = trailingslashit( home_url() );
		$this->token = 'podcast';
		$this->settings_base = 'ss_podcasting_';

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		add_action( 'init', array( $this, 'load_settings' ), 11 );

		// Register podcast settings
		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu', array( $this , 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array( $this , 'add_plugin_links' ) );

		// Load scripts for settings page
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) , 10 );

		// Mark date on which feed redirection was activated
		add_action( 'update_option', array( $this, 'mark_feed_redirect_date' ) , 10 , 3 );

	}

	public function load_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to menu
	 * @return  void
	 */
	public function add_menu_item() {
		add_submenu_page( 'edit.php?post_type=podcast' , __( 'Podcast Settings', 'ss-podcasting' ) , __( 'Settings', 'ss-podcasting' ), 'manage_options' , 'podcast_settings' , array( $this , 'settings_page' ) );
	}

	/**
	 * Add links to plugin list table
	 * @param  array $links Default links
	 * @return array $links Modified links
	 */
	public function add_plugin_links( $links ) {
		$settings_link = '<a href="edit.php?post_type=podcast&page=podcast_settings">' . __( 'Settings', 'ss-podcasting' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Load admin javascript
	 * @return void
	 */
	public function enqueue_scripts() {

		// Admin JS
		wp_register_script( 'ss_podcasting-admin', esc_url( $this->assets_url . 'js/admin' . $this->script_suffix . '.js' ), array( 'jquery' ), '1.8.0' );
		wp_enqueue_script( 'ss_podcasting-admin' );

		wp_enqueue_media();

	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {
		global $wp_post_types;

		// Set options for post type selection
		foreach( $wp_post_types as $post_type => $data ) {

			if( in_array( $post_type, array( 'page', 'attachment', 'revision', 'nav_menu_item', 'wooframework', 'podcast' ) ) ){
				continue;
			}

			$post_type_options[ $post_type ] = $data->labels->name;
		}

		$settings['general'] = array(
			'title'					=> __( 'General', 'ss-podcasting' ),
			'description'			=> __( '', 'ss-podcasting' ),
			'fields'				=> array(
				array(
					'id' 			=> 'use_post_types',
					'label'			=> __( 'Podcast post types', 'ss-podcasting' ),
					'description'	=> __( 'Use this setting to enable podcast functions on any post type - this will add all podcast posts from the specified types to your podcast feed.', 'ss-podcasting' ),
					'type'			=> 'checkbox_multi',
					'options'		=> $post_type_options,
					'default'		=> array(),
				),
				array(
					'id' 			=> 'include_in_main_query',
					'label'			=> __( 'Include podcast in main blog', 'ss-podcasting' ),
					'description'	=> __( 'This setting may behave differently in each theme, so test it carefully after activation - it will add the \'podcast\' post type to your site\'s main query so that your podcast episodes appear on your home page along with your blog posts.', 'ss-podcasting' ),
					'type'			=> 'checkbox',
					'default'		=> '',
				),
				array(
					'id' 			=> 'player_locations',
					'label'			=> __( 'Audio player locations', 'ss-podcasting' ),
					'description'	=> __( 'Select where to show the podcast audio player along with the episode data (download link, duration and file size)', 'ss-podcasting' ),
					'type'			=> 'checkbox_multi',
					'options'		=> array( 'content' => __( 'Full content', 'ss-podcasting' ), 'excerpt' => __( 'Excerpt', 'ss-podcasting' ) ),
					'default'		=> array(),
				),
				array(
					'id' 			=> 'player_content_location',
					'label'			=> __( 'Audio player location in content', 'ss-podcasting' ),
					'description'	=> __( 'Select whether to display the audio player above or below the full content.', 'ss-podcasting' ),
					'type'			=> 'radio',
					'options'		=> array( 'above' => __( 'Above content', 'ss-podcasting' ), 'below' => __( 'Below content', 'ss-podcasting' ) ),
					'default'		=> 'above',
				),
			),
		);

		$settings['feed-details'] = array(
			'title'					=> __( 'Feed details', 'ss-podcasting' ),
			'description'			=> sprintf( __( 'This data will be used in the feed for your podcast so your listeners will know more about it before they subscribe.%1$sAll of these fields are optional, but it is recommended that you fill in as many of them as possible. Blank fields will use the assigned defaults in the feed.%2$s', 'ss-podcasting' ), '<br/><em>', '</em>' ),
			'fields'				=> array(
				array(
					'id' 			=> 'data_title',
					'label'			=> __( 'Title' , 'ss-podcasting' ),
					'description'	=> __( 'Your podcast title.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'name' ),
					'placeholder'	=> __( 'Podcast title', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'data_subtitle',
					'label'			=> __( 'Subtitle' , 'ss-podcasting' ),
					'description'	=> __( 'Your podcast subtitle.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'description' ),
					'placeholder'	=> __( 'Podcast subtitle', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'data_author',
					'label'			=> __( 'Author' , 'ss-podcasting' ),
					'description'	=> __( 'Your podcast author.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'name' ),
					'placeholder'	=> __( 'Podcast author', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'data_category',
					'label'			=> __( 'Category' , 'ss-podcasting' ),
					'description'	=> sprintf( __( 'Your podcast\'s category - use one of the first-tier categories from %1$sthis list%2$s.', 'ss-podcasting' ), '<a href="' . esc_url( 'http://www.apple.com/itunes/podcasts/specs.html#categories' ) . '" target="' . esc_attr( '_blank' ) . '">', '</a>' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'Podcast category', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'data_subcategory',
					'label'			=> __( 'Sub-Category' , 'ss-podcasting' ),
					'description'	=> sprintf( __( 'Your podcast\'s sub-category - use one of the second-tier categories from %1$sthis list%2$s (must be a sub-category of your selected primary category).', 'ss-podcasting' ), '<a href="' . esc_url( 'http://www.apple.com/itunes/podcasts/specs.html#categories' ) . '" target="' . esc_attr( '_blank' ) . '">', '</a>' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'Podcast sub-category', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'data_description',
					'label'			=> __( 'Description/Summary' , 'ss-podcasting' ),
					'description'	=> __( 'A description/summary of your podcast - no HTML allowed.', 'ss-podcasting' ),
					'type'			=> 'textarea',
					'default'		=> get_bloginfo( 'description' ),
					'placeholder'	=> __( 'Podcast description', 'ss-podcasting' ),
					'callback'		=> 'strip_tags'
				),
				array(
					'id' 			=> 'data_image',
					'label'			=> __( 'Image' , 'ss-podcasting' ),
					'description'	=> __( 'Your primary podcast image - must have a minimum size of 1400x1400 px.', 'ss-podcasting' ),
					'type'			=> 'image',
					'default'		=> '',
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'data_owner_name',
					'label'			=> __( 'Owner name' , 'ss-podcasting' ),
					'description'	=> __( 'Podcast owner\'s name.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'name' ),
					'placeholder'	=> __( 'Name', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'data_owner_email',
					'label'			=> __( 'Owner email address' , 'ss-podcasting' ),
					'description'	=> __( 'Podcast owner\'s email address.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'admin_email' ),
					'placeholder'	=> __( 'Email address', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'data_language',
					'label'			=> __( 'Language' , 'ss-podcasting' ),
					'description'	=> sprintf( __( 'Your podcast\'s language in %1$sISO-639-1 format%2$s.', 'ss-podcasting' ), '<a href="' . esc_url( 'http://www.loc.gov/standards/iso639-2/php/code_list.php' ) . '" target="' . esc_attr( '_blank' ) . '">', '</a>' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'language' ),
					'placeholder'	=> __( 'Language', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'data_copyright',
					'label'			=> __( 'Copyright' , 'ss-podcasting' ),
					'description'	=> __( 'Copyright line for your podcast.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'language' ),
					'placeholder'	=> __( 'Language', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'explicit',
					'label'			=> __( 'Explicit', 'ss-podcasting' ),
					'description'	=> __( 'Mark if your podcast is explicit or not.', 'ss-podcasting' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
			)
		);

		$settings['security'] = array(
			'title'					=> __( 'Security', 'ss-podcasting' ),
			'description'			=> __( 'Change these settings to ensure that your podcast feed remains private. This will block feed readers (including iTunes) from accessing your feed.', 'ss-podcasting' ),
			'fields'				=> array(
				array(
					'id' 			=> 'protect',
					'label'			=> __( 'Password protect your podcast feed', 'ss-podcasting' ),
					'description'	=> __( 'Mark if you would like to password protect your podcast feed - you can set the username and password below. This will block all feed readers (including iTunes) from accessing your feed.', 'ss-podcasting' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'protection_username',
					'label'			=> __( 'Username' , 'ss-podcasting' ),
					'description'	=> __( 'Username for your podcast feed.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'Feed username', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'protection_password',
					'label'			=> __( 'Password' , 'ss-podcasting' ),
					'description'	=> __( 'Password for your podcast feed. Once saved, the password is encoded and secured so it will not be visible on this page again.', 'ss-podcasting' ),
					'type'			=> 'text_secret',
					'default'		=> '',
					'placeholder'	=> '',
					'callback'		=> array( $this, 'encode_password' )
				),
				array(
					'id' 			=> 'protection_no_access_message',
					'label'			=> __( 'No access message' , 'ss-podcasting' ),
					'description'	=> __( 'This message will be displayed to people who are not allowed access to your podcast feed. Limited HTML allowed.', 'ss-podcasting' ),
					'type'			=> 'textarea',
					'default'		=> __( 'You are not permitted to view this podcast feed.', 'ss-podcasting' ),
					'placeholder'	=> __( 'Message displayed to users who do not have access', 'ss-podcasting' ),
					'callback'		=> array( $this, 'validate_message' )
				),
			)
		);

		$settings['redirection'] = array(
			'title'					=> __( 'Redirection', 'ss-podcasting' ),
			'description'			=> __( 'Use these settings to safely move your podcast to a different location. Only do this once your new podcast is setup and active.', 'ss-podcasting' ),
			'fields'				=> array(
				array(
					'id' 			=> 'redirect_feed',
					'label'			=> __( 'Redirect podcast feed to new URL', 'ss-podcasting' ),
					'description'	=> sprintf( __( 'Redirect your feed to a new URL (specified below).%1$sThis will inform all podcasting services that your podcast has moved and 48 hours after you have saved this option it will permanently redirect your feed to the new URL.', 'ss-podcasting' ) , '<br/>' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'new_feed_url',
					'label'			=> __( 'New podcast feed URL', 'ss-podcasting' ),
					'description'	=> __( 'Your podcast feed\'s new URL.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> '',
					'callback'		=> 'esc_url'
				),
			)
		);

		$settings['publishing'] = array(
			'title'					=> __( 'Publishing' , 'ss-podcasting' ),
			'description'			=> __( 'Use these URLs to share and publish your podcast feed. These URLs will work with any podcasting service (including iTunes).' , 'ss-podcasting' ),
			'fields'				=> array(
				array(
					'id' 			=> 'feed_url',
					'label'			=> __( 'External feed URL', 'ss-podcasting' ),
					'description'	=> __( 'If you are syndicating your podcast using a third-party service (like Feedburner) you can insert the URL here, otherwise this must be left blank.' , 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'External feed URL', 'ss-podcasting' ),
					'callback'		=> 'esc_url'
				),
				array(
					'id' 			=> 'feed_link',
					'label'			=> __( 'Complete feed', 'ss-podcasting' ),
					'description'	=> '',
					'type'			=> 'feed_link'
				),
				array(
					'id' 			=> 'feed_link_series',
					'label'			=> __( 'Feed for a specific series', 'ss-podcasting' ),
					'description'	=> '',
					'type'			=> 'feed_link_series'
				),
				array(
					'id' 			=> 'podcast_url',
					'label'			=> __( 'Podcast page', 'ss-podcasting' ),
					'description'	=> '',
					'type'			=> 'podcast_url'
				)
			)
		);

		$settings = apply_filters( 'ssp_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings() {
		if( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = 'general';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}

			foreach( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) continue;

				// Add section to page
				add_settings_section( $section, $data['title'], '', 'ss_podcasting' );
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), 'ss_podcasting' );
				foreach( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->settings_base . $field['id'];
					register_setting( 'ss_podcasting', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this, 'display_field' ), 'ss_podcasting', $section, array( 'field' => $field, 'prefix' => $this->settings_base ) );
				}
			}
		}
	}

	public function settings_section ( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Generate HTML for displaying fields
	 * @param  array $args Field data
	 * @return void
	 */
	public function display_field( $args ) {

		$field = $args['field'];

		$html = '';

		$option_name = $this->settings_base . $field['id'];
		$option = get_option( $option_name );

		$data = '';
		if( isset( $field['default'] ) ) {
			$data = $field['default'];
			if( $option ) {
				$data = $option;
			}
		}

		switch( $field['type'] ) {
			case 'text':
			case 'password':
			case 'number':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '"/>' . "\n";
			break;

			case 'text_secret':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value=""/>' . "\n";
			break;

			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">' . $data . '</textarea><br/>'. "\n";
			break;

			case 'checkbox':
				$checked = '';
				if( $option && 'on' == $option ){
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" ' . $checked . '/>' . "\n";
			break;

			case 'checkbox_multi':
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( in_array( $k, (array) $data ) ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label><br/>';
				}
			break;

			case 'radio':
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label><br/>';
				}
			break;

			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
				foreach( $field['options'] as $k => $v ) {
					$selected = false;
					if( $k == $data ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
			break;

			case 'image':
				$html .= '<img id="' . $option_name . '_preview" src="' . $data . '" style="max-width:400px;height:auto;" /><br/>' . "\n";
				$html .= '<input id="' . $option_name . '_button" type="button" class="button" value="'. __( 'Upload new image' , 'ss-podcasting' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '_delete" type="button" class="button" value="'. __( 'Remove image' , 'ss-podcasting' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>' . "\n";
			break;

			case 'feed_link':
				$url = $this->home_url . 'feed/' . $this->token;
				$html .= '<a href="' . esc_url( $url ) . '" target="_blank">' . $url . '</a>';
			break;

			case 'feed_link_series':
				$html .= $this->home_url . 'feed/' . $this->token . '/?podcast_series=series-slug' . "\n";
			break;

			case 'podcast_url';
				$podcast_url = $this->home_url;

				$slug = get_option('ss_podcasting_slug');
				if( $slug ) {
					$podcast_url .= $slug;
				} else {
					$podcast_url .= '?post_type=podcast';
				}

				$html .= '<a href="' . esc_url( $podcast_url ) . '" target="_blank">' . $podcast_url . '</a>';
			break;

		}

		if( ! in_array( $field['type'], array( 'feed_link', 'feed_link_series', 'podcast_url' ) ) ) {
			switch( $field['type'] ) {

				case 'checkbox_multi':
				case 'radio':
				case 'select_multi':
					$html .= '<br/><span class="description">' . $field['description'] . '</span>';
				break;

				default:
					$html .= '<label for="' . esc_attr( $field['id'] ) . '"><span class="description">' . $field['description'] . '</span></label>' . "\n";
				break;
			}
		}

		echo $html;
	}

	/**
	 * Validate URL slug
	 * @param  string $slug User input
	 * @return string       Validated string
	 */
	public function validate_slug( $slug ) {
		if( $slug && strlen( $slug ) > 0 && $slug != '' ) {
			$slug = urlencode( strtolower( str_replace( ' ' , '-' , $slug ) ) );
		}
		return $slug;
	}

	/**
	 * Encode feed password
	 * @param  string $password User input
	 * @return string           Encoded password
	 */
	public function encode_password( $password ) {

		if( $password && strlen( $password ) > 0 && $password != '' ) {
			$password = md5( $password );
		} else {
			$option = get_option( 'ss_podcasting_protection_password' );
			$password = $option;
		}

		return $password;
	}

	/**
	 * Validate protectino message
	 * @param  string $message User input
	 * @return string          Validated message
	 */
	public function validate_message( $message ) {

		if( $message ) {

			$allowed = array(
			    'a' => array(
			        'href' => array(),
			        'title' => array(),
			        'target' => array()
			    ),
			    'br' => array(),
			    'em' => array(),
			    'strong' => array(),
			    'p' => array()
			);

			$message = wp_kses( $message, $allowed );
		}

		return $message;
	}

	/**
	 * Mark redirect date for feed
	 * @param  string $option    Name of option being updated
	 * @param  mixed  $old_value Old value of option
	 * @param  mixed  $new_value New value of option
	 * @return void
	 */
	public function mark_feed_redirect_date( $option, $old_value, $new_value ) {

		if( $option == 'ss_podcasting_redirect_feed' ) {
			if( ( $new_value != $old_value ) && $new_value == 'on' ) {
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

		// Build page HTML
		$html = '<div class="wrap" id="podcast_settings">' . "\n";
			$html .= '<h2>' . __( 'Podcast Settings' , 'ss-podcasting' ) . '</h2>' . "\n";

			$tab = 'general';
			if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
				$tab = $_GET['tab'];
			}

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				foreach ( $this->settings as $section => $data ) {

					// Set tab class
					$class = 'nav-tab';
					if ( ! isset( $_GET['tab'] ) ) {
						if ( 0 == $c ) {
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
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}

			if ( isset( $_GET['settings-updated'] ) ) {
				$html .= '<br/><div class="updated">
					        <p>' . sprintf( __( '%1$s settings updated.', 'ss-podcasting' ), '<b>' . str_replace( '-', ' ', ucfirst( $tab ) ) . '</b>' ) . '</p>
					    </div>';
			}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields
				ob_start();
				settings_fields( 'ss_podcasting' );
				do_settings_sections( 'ss_podcasting' );
				$html .= ob_get_clean();

				// Submit button
				$html .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'ss-podcasting' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";

			$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

	  	echo $html;
	}

}