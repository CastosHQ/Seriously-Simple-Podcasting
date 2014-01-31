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
	 * @param string $file Plugin base file
	 */
	public function __construct( $file ) {
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
		$this->home_url = trailingslashit( home_url() );
		$this->token = 'podcast';
		$this->settings_base = 'ss_podcasting_';
		$this->settings = $this->settings_fields();

		// Register podcast settings
		add_action( 'admin_init', array( $this , 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu', array( $this , 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array( $this , 'add_plugin_links' ) );

		// Load scripts for settings page
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) , 10 );

		// Mark date on which feed redirection was activated
		add_action( 'update_option', array( $this, 'mark_feed_redirect_date' ) , 10 , 3 );

		// Display notices in the WP admin
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 10 );

	}

	/**
	 * Add settings page to menu
	 * @return  void
	 */
	public function add_menu_item() {
		add_submenu_page( 'edit.php?post_type=podcast' , 'Podcast Settings' , 'Settings', 'manage_options' , 'podcast_settings' , array( $this , 'settings_page' ) );
	}

	/**
	 * Add links to plugin list table
	 * @param  array $links Default links
	 * @return array $links Modified links
	 */
	public function add_plugin_links( $links ) {
		$settings_link = '<a href="edit.php?post_type=podcast&page=podcast_settings">Settings</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Load admin javascript
	 * @return void
	 */
	public function enqueue_admin_scripts () {
		global $wp_version;

		// Admin JS
		wp_register_script( 'ss_podcasting-admin', esc_url( $this->assets_url . 'js/admin.js' ), array( 'jquery' ), '2.0.0' );
		wp_enqueue_script( 'ss_podcasting-admin' );

		if( $wp_version >= 3.5 ) {
			// Media uploader scripts
			wp_enqueue_media();
		}

	}

	/**
	 * Display notices in the WordPress dashboard
	 * @return void
	 */
	public function admin_notices() {
		global $current_user, $wp_version;
        $user_id = $current_user->ID;

        // Version notice
        if( $wp_version < 3.6 ) {
			?>
			<div class="error">
		        <p><?php printf( __( '%1$sSeriously Simple Podcasting%2$s requires WordPress 3.6 or above in order to function correctly. You are running v%3$s - please update now.', 'ss-podcasting' ), '<strong>', '</strong>', $wp_version ); ?></p>
		    </div>
		    <?php
		}
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$settings['customise'] = array(
			'title'					=> __( 'Customise', 'ss-podcasting' ),
			'description'			=> __( 'These are a few simple settings to make your podcast work the way you want it to work.', 'ss-podcasting' ),
			'fields'				=> array(
				array(
					'id' 			=> 'use_templates',
					'label'			=> __( 'Use built-in plugin templates', 'ss-podcasting' ),
					'description'	=> sprintf( __( 'Select this to use the built-in templates for the podcast archive and single pages. If you leave this disabled then your theme\'s default post templates will be used unless you %1$screate your own%2$s', 'ss-podcasting' ), '<a href="' . esc_url( 'http://codex.wordpress.org/Post_Type_Templates' ) . '" target="' . esc_attr( '_blank' ) . '">', '</a>' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'slug',
					'label'			=> __( 'URL slug for podcast pages', 'ss-podcasting' ),
					'description'	=> sprintf( __( 'Provide a custom URL slug for the podcast archive and single pages. You must re-save your %1$spermalinks%2$s after changing this setting. No matter what you put here your podcast will always be visible at %3$s.', 'ss-podcasting' ), '<a href="' . esc_attr( 'options-permalink.php' ) . '">', '</a>', '<a href="' . esc_url( $this->home_url . '?post_type=podcast' ) . '">' . $this->home_url . '?post_type=podcast</a>' ),
					'type'			=> 'text',
					'default'		=> 'podcast',
					'placeholder'	=> '',
					'callback'		=> array( $this, 'validate_slug' )
				),
				array(
					'id' 			=> 'feed_url',
					'label'			=> __( 'URL for your podcast', 'ss-podcasting' ),
					'description'	=> __( 'If you are using Feedburner (or a similar service) to syndicate your podcast feed you can insert the URL here, otherwise this must be left blank.' , 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'External feed URL', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'include_in_main_query',
					'label'			=> __( 'Include podcast episodes in home page blog listing', 'ss-podcasting' ),
					'description'	=> __( 'This setting may behave differently in each theme, so test it carefully after activation - it will add the \'podcast\' post type to your site\'s main query so that your podcast episodes appear on your home page along with your blog posts.', 'ss-podcasting' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'hide_content_meta',
					'label'			=> __( 'Prevent audio player and podcast data from showing above episode content', 'ss-podcasting' ),
					'description'	=> sprintf( __( 'Select this to %1$shide%2$s the podcast audio player along with the episode data (download link, duration and file size) wherever the full content of the episode is displayed.', 'ss-podcasting' ), '<em>', '</em>' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				)
			)
		);

		$settings['describe'] = array(
			'title'					=> __( 'Describe', 'ss-podcasting' ),
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

		$settings['protect'] = array(
			'title'					=> __( 'Protect', 'ss-podcasting' ),
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
					'description'	=> __( 'Login username for your podcast feed.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'Feed username', 'ss-podcasting' )
				),
				array(
					'id' 			=> 'protection_password',
					'label'			=> __( 'Username' , 'ss-podcasting' ),
					'description'	=> __( 'Login password for your podcast feed. Once saved, the password is encoded and secured so it will not be visible on this page again. If you leave this field blank than the password will not be updated.', 'ss-podcasting' ),
					'type'			=> 'text_hidden',
					'default'		=> '',
					'placeholder'	=> '',
					'callback'		=> array( $this, 'encode_password' )
				),
				array(
					'id' 			=> 'protection_no_access_message',
					'label'			=> __( 'No access message' , 'ss-podcasting' ),
					'description'	=> __( 'This will be the message displayed to people who are not allowed access to your podcast feed. Limited HTML allowed.', 'ss-podcasting' ),
					'type'			=> 'textarea',
					'default'		=> __( 'You are not permitted to view this podcast feed.', 'ss-podcasting' ),
					'placeholder'	=> __( 'Message displayed to users who do not have access', 'ss-podcasting' ),
					'callback'		=> array( $this, 'validate_message' )
				),
			)
		);

		$settings['redirect'] = array(
			'title'					=> __( 'Redirect', 'ss-podcasting' ),
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
				),
			)
		);

		$settings['publish'] = array(
			'title'					=> __( 'Publish' , 'ss-podcasting' ),
			'description'			=> __( 'Use these URLs to share and publish your podcast feed. These URLs will work with any podcasting service (including iTunes).' , 'ss-podcasting' ),
			'fields'				=> array(
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
				),
				array(
					'id' 			=> 'social_sharing',
					'label'			=> __( 'Share online', 'ss-podcasting' ),
					'description'	=> '',
					'type'			=> 'social_sharing'
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
			foreach( $this->settings as $section => $data ) {

				// Add section to page
				add_settings_section( $section, $data['title'], '', 'ss_podcasting' );

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
					add_settings_field( $field['id'], $field['label'], array( $this, 'display_field' ), 'ss_podcasting', $section, array( 'field' => $field ) );
				}
			}
		}
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

		$data = $field['default'];
		if( $option  ) {
			$data = $option;
		}

		switch( $field['type'] ) {
			case 'text':
			case 'password':
			case 'number':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '"/>' . "\n";
			break;

			case 'text_hidden':
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

			case 'select':
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

			case 'social_sharing':
				$html .= $this->social_sharing();
			break;

		}

		if( ! in_array( $field['type'], array( 'feed_link', 'feed_link_series', 'podcast_url' ) ) ) {
			$html .= '<label for="' . esc_attr( $field['id'] ) . '"><span class="description">' . $field['description'] . '</span></label>' . "\n";
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
	 * Generate HTML for social sharing section
	 * @return void
	 */
	private function social_sharing() {

		$share_url = $this->home_url;
		$custom_title = get_option('ss_podcasting_data_title');
		$share_title = sprintf( __( 'Podcast on %s', 'ss-podcasting' ), get_bloginfo( 'name' ) );
		if( $custom_title ) {
			$share_title = $custom_title;
		}

		$slug = get_option('ss_podcasting_slug');
		if( $slug ) {
			$share_url .= $slug;
		} else {
			$share_url .= '?post_type=podcast';
		}

		printf( __( 'Use this to quickly share a link to your site\'s podcast directory page - this is currently %s, but can be customised using the \'URL slug\' option in the \'Customise\' settings section.', 'ss-podcasting' ), '<a href="' . esc_url( $share_url ) . '">' . $share_url . '</a>' );

		?>
		<br/>

		<!-- Twitter -->
		<a href="https://twitter.com/share" class="twitter-share-button" data-url="<?php echo esc_url( $share_url ); ?>" data-text="<?php echo esc_attr( $share_title ); ?>" data-count="none">Tweet</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
		&nbsp;&nbsp;&nbsp;
		<!-- Facebook -->
		<a id="fb-share" style='text-decoration:none;' type="icon_link" onClick="window.open('http://www.facebook.com/sharer.php?s=100&amp;p[title]=<?php echo esc_attr( $share_title ); ?>&amp;p[url]=<?php echo esc_url( $share_url ); ?>','sharer','toolbar=0,status=0,width=580,height=325');" href="javascript: void(0)">
		    <img src="<?php echo $this->assets_url; ?>images/fb_share.gif" />
		</a>
		&nbsp;&nbsp;&nbsp;
		<!-- Google+ -->
		<div class="g-plus" data-action="share" data-annotation="none" href="<?php echo esc_url( $share_url ); ?>"></div>
		<script type="text/javascript">
		  (function() {
		    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
		    po.src = 'https://apis.google.com/js/plusone.js';
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
		  })();
		</script>
		<?php
	}

	/**
	 * Generate HTML for settings page
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML
		$html .= '<div class="wrap" id="podcast_settings">' . "\n";
			$html .= '<h2>' . __( 'Podcast Settings' , 'ss-podcasting' ) . '</h2>' . "\n";

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Setup navigation
				$html .= '<ul id="settings-sections" class="subsubsub hide-if-no-js">' . "\n";
					$html .= '<li><a class="tab all current" href="#all">' . __( 'All' , 'ss-podcasting' ) . '</a></li>' . "\n";

					foreach( $this->settings as $section => $data ) {
						$html .= '<li>| <a class="tab" href="#' . $section . '">' . $data['title'] . '</a></li>' . "\n";
					}

				$html .= '</ul>' . "\n";

				$html .= '<div class="clear"></div>' . "\n";

				// Get settings fields
				ob_start();
				settings_fields( 'ss_podcasting' );
				do_settings_sections( 'ss_podcasting' );
				$html .= ob_get_clean();

				// Submit button
				$html .= '<p class="submit">' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'ss-podcasting' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";

			$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

	  	echo $html;
	}

}