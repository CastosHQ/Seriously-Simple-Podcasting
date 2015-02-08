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

		// Set up available category options
		$category_options = array(
			'' => __( '-- None --', 'ss-podcasting' ),
			'Arts' => __( 'Arts', 'ss-podcasting' ),
			'Business' => __( 'Business', 'ss-podcasting' ),
			'Comedy' => __( 'Comedy', 'ss-podcasting' ),
			'Education' => __( 'Education', 'ss-podcasting' ),
			'Games & Hobbies' => __( 'Games & Hobbies', 'ss-podcasting' ),
			'Government & Organizations' => __( 'Government & Organizations', 'ss-podcasting' ),
			'Health' => __( 'Health', 'ss-podcasting' ),
			'Kids & Family' => __( 'Kids & Family', 'ss-podcasting' ),
			'Music' => __( 'Music', 'ss-podcasting' ),
			'News & Politics' => __( 'News & Politics', 'ss-podcasting' ),
			'Religion & Spirituality' => __( 'Religion & Spirituality', 'ss-podcasting' ),
			'Science & Medicine' => __( 'Science & Medicine', 'ss-podcasting' ),
			'Society & Culture' => __( 'Society & Culture', 'ss-podcasting' ),
			'Sports & Recreation' => __( 'Sports & Recreation', 'ss-podcasting' ),
			'Technology' => __( 'Technology', 'ss-podcasting' ),
			'TV & Film' => __( 'TV & Film', 'ss-podcasting' ),
		);

		// Set up available sub-category options
		$subcategory_options = array(

			'' => __( '-- None --', 'ss-podcasting' ),

			'Design' => array( 'label' => __( 'Design', 'ss-podcasting' ), 'group' => __( 'Arts', 'ss-podcasting' ) ),
			'Fashion & Beauty' => array( 'label' => __( 'Fashion & Beauty', 'ss-podcasting' ), 'group' => __( 'Arts', 'ss-podcasting' ) ),
			'Food' => array( 'label' => __( 'Food', 'ss-podcasting' ), 'group' => __( 'Arts', 'ss-podcasting' ) ),
			'Literature' => array( 'label' => __( 'Literature', 'ss-podcasting' ), 'group' => __( 'Arts', 'ss-podcasting' ) ),
			'Performing Arts' => array( 'label' => __( 'Performing Arts', 'ss-podcasting' ), 'group' => __( 'Arts', 'ss-podcasting' ) ),
			'Visual Arts' => array( 'label' => __( 'Visual Arts', 'ss-podcasting' ), 'group' => __( 'Arts', 'ss-podcasting' ) ),

			'Business News' => array( 'label' => __( 'Business News', 'ss-podcasting' ), 'group' => __( 'Business', 'ss-podcasting' ) ),
			'Careers' => array( 'label' => __( 'Careers', 'ss-podcasting' ), 'group' => __( 'Business', 'ss-podcasting' ) ),
			'Investing' => array( 'label' => __( 'Investing', 'ss-podcasting' ), 'group' => __( 'Business', 'ss-podcasting' ) ),
			'Management & Marketing' => array( 'label' => __( 'Management & Marketing', 'ss-podcasting' ), 'group' => __( 'Business', 'ss-podcasting' ) ),
			'Shopping' => array( 'label' => __( 'Shopping', 'ss-podcasting' ), 'group' => __( 'Business', 'ss-podcasting' ) ),

			'Education' => array( 'label' => __( 'Education', 'ss-podcasting' ), 'group' => __( 'Education', 'ss-podcasting' ) ),
			'Education Technology' => array( 'label' => __( 'Education Technology', 'ss-podcasting' ), 'group' => __( 'Education', 'ss-podcasting' ) ),
			'Higher Education' => array( 'label' => __( 'Higher Education', 'ss-podcasting' ), 'group' => __( 'Education', 'ss-podcasting' ) ),
			'K-12' => array( 'label' => __( 'K-12', 'ss-podcasting' ), 'group' => __( 'Education', 'ss-podcasting' ) ),
			'Language Courses' => array( 'label' => __( 'Language Courses', 'ss-podcasting' ), 'group' => __( 'Education', 'ss-podcasting' ) ),
			'Training' => array( 'label' => __( 'Training', 'ss-podcasting' ), 'group' => __( 'Education', 'ss-podcasting' ) ),

			'Automotive' => array( 'label' => __( 'Automotive', 'ss-podcasting' ), 'group' => __( 'Games & Hobbies', 'ss-podcasting' ) ),
			'Aviation' => array( 'label' => __( 'Aviation', 'ss-podcasting' ), 'group' => __( 'Games & Hobbies', 'ss-podcasting' ) ),
			'Hobbies' => array( 'label' => __( 'Hobbies', 'ss-podcasting' ), 'group' => __( 'Games & Hobbies', 'ss-podcasting' ) ),
			'Other Games' => array( 'label' => __( 'Other Games', 'ss-podcasting' ), 'group' => __( 'Games & Hobbies', 'ss-podcasting' ) ),
			'Video Games' => array( 'label' => __( 'Video Games', 'ss-podcasting' ), 'group' => __( 'Games & Hobbies', 'ss-podcasting' ) ),

			'Local' => array( 'label' => __( 'Local', 'ss-podcasting' ), 'group' => __( 'Government & Organizations', 'ss-podcasting' ) ),
			'National' => array( 'label' => __( 'National', 'ss-podcasting' ), 'group' => __( 'Government & Organizations', 'ss-podcasting' ) ),
			'Non-Profit' => array( 'label' => __( 'Non-Profit', 'ss-podcasting' ), 'group' => __( 'Government & Organizations', 'ss-podcasting' ) ),
			'Regional' => array( 'label' => __( 'Regional', 'ss-podcasting' ), 'group' => __( 'Government & Organizations', 'ss-podcasting' ) ),

			'Alternative Health' => array( 'label' => __( 'Alternative Health', 'ss-podcasting' ), 'group' => __( 'Health', 'ss-podcasting' ) ),
			'Fitness & Nutrition' => array( 'label' => __( 'Fitness & Nutrition', 'ss-podcasting' ), 'group' => __( 'Health', 'ss-podcasting' ) ),
			'Self-Help' => array( 'label' => __( 'Self-Help', 'ss-podcasting' ), 'group' => __( 'Health', 'ss-podcasting' ) ),
			'Sexuality' => array( 'label' => __( 'Sexuality', 'ss-podcasting' ), 'group' => __( 'Health', 'ss-podcasting' ) ),

			'Buddhism' => array( 'label' => __( 'Buddhism', 'ss-podcasting' ), 'group' => __( 'Religion & Spirituality', 'ss-podcasting' ) ),
			'Christianity' => array( 'label' => __( 'Christianity', 'ss-podcasting' ), 'group' => __( 'Religion & Spirituality', 'ss-podcasting' ) ),
			'Hinduism' => array( 'label' => __( 'Hinduism', 'ss-podcasting' ), 'group' => __( 'Religion & Spirituality', 'ss-podcasting' ) ),
			'Islam' => array( 'label' => __( 'Islam', 'ss-podcasting' ), 'group' => __( 'Religion & Spirituality', 'ss-podcasting' ) ),
			'Judaism' => array( 'label' => __( 'Judaism', 'ss-podcasting' ), 'group' => __( 'Religion & Spirituality', 'ss-podcasting' ) ),
			'Other' => array( 'label' => __( 'Other', 'ss-podcasting' ), 'group' => __( 'Religion & Spirituality', 'ss-podcasting' ) ),
			'Spirituality' => array( 'label' => __( 'Spirituality', 'ss-podcasting' ), 'group' => __( 'Religion & Spirituality', 'ss-podcasting' ) ),

			'History' => array( 'label' => __( 'History', 'ss-podcasting' ), 'group' => __( 'Society & Culture', 'ss-podcasting' ) ),
			'Personal Journals' => array( 'label' => __( 'Personal Journals', 'ss-podcasting' ), 'group' => __( 'Society & Culture', 'ss-podcasting' ) ),
			'Philosophy' => array( 'label' => __( 'Philosophy', 'ss-podcasting' ), 'group' => __( 'Society & Culture', 'ss-podcasting' ) ),
			'Places & Travel' => array( 'label' => __( 'Places & Travel', 'ss-podcasting' ), 'group' => __( 'Society & Culture', 'ss-podcasting' ) ),

			'Amateur' => array( 'label' => __( 'Amateur', 'ss-podcasting' ), 'group' => __( 'Sports & Recreation', 'ss-podcasting' ) ),
			'College & High School' => array( 'label' => __( 'College & High School', 'ss-podcasting' ), 'group' => __( 'Sports & Recreation', 'ss-podcasting' ) ),
			'Outdoor' => array( 'label' => __( 'Outdoor', 'ss-podcasting' ), 'group' => __( 'Sports & Recreation', 'ss-podcasting' ) ),
			'Professional' => array( 'label' => __( 'Professional', 'ss-podcasting' ), 'group' => __( 'Sports & Recreation', 'ss-podcasting' ) ),

			'Gadgets' => array( 'label' => __( 'Gadgets', 'ss-podcasting' ), 'group' => __( 'Technology', 'ss-podcasting' ) ),
			'Tech News' => array( 'label' => __( 'Tech News', 'ss-podcasting' ), 'group' => __( 'Technology', 'ss-podcasting' ) ),
			'Podcasting' => array( 'label' => __( 'Podcasting', 'ss-podcasting' ), 'group' => __( 'Technology', 'ss-podcasting' ) ),
			'Software How-To' => array( 'label' => __( 'Software How-To', 'ss-podcasting' ), 'group' => __( 'Technology', 'ss-podcasting' ) ),

		);

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
					'label'			=> __( 'Audio player position', 'ss-podcasting' ),
					'description'	=> __( 'Select whether to display the audio player above or below the full post content.', 'ss-podcasting' ),
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
					'placeholder'	=> get_bloginfo( 'name' ),
					'class'			=> 'large-text',
				),
				array(
					'id' 			=> 'data_subtitle',
					'label'			=> __( 'Subtitle' , 'ss-podcasting' ),
					'description'	=> __( 'Your podcast subtitle.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'description' ),
					'placeholder'	=> get_bloginfo( 'description' ),
					'class'			=> 'large-text',
				),
				array(
					'id' 			=> 'data_author',
					'label'			=> __( 'Author' , 'ss-podcasting' ),
					'description'	=> __( 'Your podcast author.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'name' ),
					'placeholder'	=> get_bloginfo( 'name' ),
					'class'			=> 'large-text',
				),
				array(
					'id' 			=> 'data_category',
					'label'			=> __( 'Category' , 'ss-podcasting' ),
					'description'	=> __( 'Your podcast\'s category.', 'ss-podcasting' ),
					'type'			=> 'select',
					'options'		=> $category_options,
					'default'		=> '',
				),
				array(
					'id' 			=> 'data_subcategory',
					'label'			=> __( 'Sub-Category' , 'ss-podcasting' ),
					'description'	=> __( 'Your podcast\'s sub-category (if available) - must be a sub-category of the category selected above.', 'ss-podcasting' ),
					'type'			=> 'select',
					'options'		=> $subcategory_options,
					'default'		=> '',
				),
				array(
					'id' 			=> 'data_description',
					'label'			=> __( 'Description/Summary' , 'ss-podcasting' ),
					'description'	=> __( 'A description/summary of your podcast - no HTML allowed.', 'ss-podcasting' ),
					'type'			=> 'textarea',
					'default'		=> get_bloginfo( 'description' ),
					'placeholder'	=> get_bloginfo( 'description' ),
					'callback'		=> 'strip_tags',
					'class'			=> 'large-text',
				),
				array(
					'id' 			=> 'data_image',
					'label'			=> __( 'Cover Image' , 'ss-podcasting' ),
					'description'	=> __( 'Your podcast cover image - must have a minimum size of 1400x1400 px.', 'ss-podcasting' ),
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
					'placeholder'	=> get_bloginfo( 'name' ),
					'class'			=> 'large-text',
				),
				array(
					'id' 			=> 'data_owner_email',
					'label'			=> __( 'Owner email address' , 'ss-podcasting' ),
					'description'	=> __( 'Podcast owner\'s email address.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'admin_email' ),
					'placeholder'	=> get_bloginfo( 'admin_email' ),
					'class'			=> 'large-text',
				),
				array(
					'id' 			=> 'data_language',
					'label'			=> __( 'Language' , 'ss-podcasting' ),
					'description'	=> sprintf( __( 'Your podcast\'s language in %1$sISO-639-1 format%2$s.', 'ss-podcasting' ), '<a href="' . esc_url( 'http://www.loc.gov/standards/iso639-2/php/code_list.php' ) . '" target="' . esc_attr( '_blank' ) . '">', '</a>' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'language' ),
					'placeholder'	=> get_bloginfo( 'language' ),
					'class'			=> 'all-options',
				),
				array(
					'id' 			=> 'data_copyright',
					'label'			=> __( 'Copyright' , 'ss-podcasting' ),
					'description'	=> __( 'Copyright line for your podcast.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ),
					'placeholder'	=> '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ),
					'class'			=> 'large-text',
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
					'placeholder'	=> __( 'Feed username', 'ss-podcasting' ),
					'class'			=> 'regular-text',
				),
				array(
					'id' 			=> 'protection_password',
					'label'			=> __( 'Password' , 'ss-podcasting' ),
					'description'	=> __( 'Password for your podcast feed. Once saved, the password is encoded and secured so it will not be visible on this page again.', 'ss-podcasting' ),
					'type'			=> 'text_secret',
					'default'		=> '',
					'placeholder'	=> __( 'Feed password', 'ss-podcasting' ),
					'callback'		=> array( $this, 'encode_password' ),
					'class'			=> 'regular-text',
				),
				array(
					'id' 			=> 'protection_no_access_message',
					'label'			=> __( 'No access message' , 'ss-podcasting' ),
					'description'	=> __( 'This message will be displayed to people who are not allowed access to your podcast feed. Limited HTML allowed.', 'ss-podcasting' ),
					'type'			=> 'textarea',
					'default'		=> __( 'You are not permitted to view this podcast feed.', 'ss-podcasting' ),
					'placeholder'	=> __( 'Message displayed to users who do not have access to the podcast feed', 'ss-podcasting' ),
					'callback'		=> array( $this, 'validate_message' ),
					'class'			=> 'large-text',
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
					'default'		=> '',
				),
				array(
					'id' 			=> 'new_feed_url',
					'label'			=> __( 'New podcast feed URL', 'ss-podcasting' ),
					'description'	=> __( 'Your podcast feed\'s new URL.', 'ss-podcasting' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'New feed URL', 'ss-podcasting' ),
					'callback'		=> 'esc_url',
					'class'			=> 'regular-text',
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
					'callback'		=> 'esc_url',
					'class'			=> 'regular-text',
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

		$class = '';
		if( isset( $field['class'] ) ) {
			$class = $field['class'];
		}

		switch( $field['type'] ) {
			case 'text':
			case 'password':
			case 'number':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '" class="' . $class . '"/>' . "\n";
			break;

			case 'text_secret':
				$placeholder = $field['placeholder'];
				if( $data ) {
					$placeholder = __( 'Password stored securely', 'ss-podcasting' );
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="" class="' . $class . '"/>' . "\n";
			break;

			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" class="' . $class . '">' . $data . '</textarea><br/>'. "\n";
			break;

			case 'checkbox':
				$checked = '';
				if( $option && 'on' == $option ){
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" ' . $checked . ' class="' . $class . '"/>' . "\n";
			break;

			case 'checkbox_multi':
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( in_array( $k, (array) $data ) ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" class="' . $class . '" /> ' . $v . '</label><br/>';
				}
			break;

			case 'radio':
				foreach( $field['options'] as $k => $v ) {
					$checked = false;
					if( $k == $data ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" class="' . $class . '" /> ' . $v . '</label><br/>';
				}
			break;

			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '" class="' . $class . '">';
				$prev_group = '';
				foreach( $field['options'] as $k => $v ) {

					$group = '';
					if( is_array( $v ) ) {
						if( isset( $v['group'] ) ) {
							$group = $v['group'];
						}
						$v = $v['label'];
					}

					if( $prev_group && $group != $prev_group ) {
						$html .= '</optgroup>';
					}

					$selected = false;
					if( $k == $data ) {
						$selected = true;
					}

					if( $group && $group != $prev_group ) {
						$html .= '<optgroup label="' . $group . '">';
					}

					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';

					$prev_group = $group;
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

				$slug = apply_filters( 'ssp_archive_slug', __( 'podcast' , 'ss-podcasting' ) );
				$podcast_url = $this->home_url . $slug;

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