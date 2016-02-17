<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 */
	public function __construct( $file ) {
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
		$this->home_url = trailingslashit( home_url() );
		$this->token = 'podcast';
		$this->settings_base = 'ss_podcasting_';

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

		// New caps for editors and above.
		add_action( 'admin_init', array( $this, 'add_caps' ), 1 );

	}

	public function load_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to menu
	 * @return  void
	 */
	public function add_menu_item() {
		add_submenu_page( 'edit.php?post_type=podcast' , __( 'Podcast Settings', 'seriously-simple-podcasting' ) , __( 'Settings', 'seriously-simple-podcasting' ), 'manage_podcast' , 'podcast_settings' , array( $this , 'settings_page' ) );
	}

	/**
	 * Add cabilities to edit podcast settings to admins, and editors.
	 */
	public function add_caps() {

		// Roles you'd like to have administer the podcast settings page.
		// Admin and Editor, as default.
		$roles = apply_filters( 'ssp_manage_podcast', array( 'administrator', 'editor' ) );

		// Loop through each role and assign capabilities
		foreach( $roles as $the_role ) {

			$role = get_role( $the_role );
			$caps = array(
				'manage_podcast',
			);

			// Add the caps.
			foreach ( $caps as $cap ) {
				$this->maybe_add_cap( $role, $cap );
			}
		}
	}

	/**
	 * Check to see if the given role has a cap, and add if it doesn't exist.
	 *
	 * @param  object $role User Cap object, part of WP_User.
	 * @param  string $cap  Cap to test against.
	 * @return void
	 */
	public function maybe_add_cap( $role, $cap ) {
		// Update the roles, if needed.
		if ( ! $role->has_cap( $cap ) ) {
			$role->add_cap( $cap );
		}
	}

	/**
	 * Add links to plugin list table
	 * @param  array $links Default links
	 * @return array $links Modified links
	 */
	public function add_plugin_links( $links ) {
		$settings_link = '<a href="edit.php?post_type=podcast&page=podcast_settings">' . __( 'Settings', 'seriously-simple-podcasting' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Load admin javascript
	 * @return void
	 */
	public function enqueue_scripts() {
		global $pagenow;
		if ( in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) || ( isset( $_GET['page'] ) && 'podcast_settings' == $_GET['page'] ) ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {
		global $wp_post_types;

		$post_type_options = array();

		// Set options for post type selection
		foreach ( $wp_post_types as $post_type => $data ) {

			if ( in_array( $post_type, array( 'page', 'attachment', 'revision', 'nav_menu_item', 'wooframework', 'podcast' ) ) ){
				continue;
			}

			$post_type_options[ $post_type ] = $data->labels->name;
		}

		// Set up available category options
		$category_options = array(
			'' => __( '-- None --', 'seriously-simple-podcasting' ),
			'Arts' => __( 'Arts', 'seriously-simple-podcasting' ),
			'Business' => __( 'Business', 'seriously-simple-podcasting' ),
			'Comedy' => __( 'Comedy', 'seriously-simple-podcasting' ),
			'Education' => __( 'Education', 'seriously-simple-podcasting' ),
			'Games & Hobbies' => __( 'Games & Hobbies', 'seriously-simple-podcasting' ),
			'Government & Organizations' => __( 'Government & Organizations', 'seriously-simple-podcasting' ),
			'Health' => __( 'Health', 'seriously-simple-podcasting' ),
			'Kids & Family' => __( 'Kids & Family', 'seriously-simple-podcasting' ),
			'Music' => __( 'Music', 'seriously-simple-podcasting' ),
			'News & Politics' => __( 'News & Politics', 'seriously-simple-podcasting' ),
			'Religion & Spirituality' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			'Science & Medicine' => __( 'Science & Medicine', 'seriously-simple-podcasting' ),
			'Society & Culture' => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			'Sports & Recreation' => __( 'Sports & Recreation', 'seriously-simple-podcasting' ),
			'Technology' => __( 'Technology', 'seriously-simple-podcasting' ),
			'TV & Film' => __( 'TV & Film', 'seriously-simple-podcasting' ),
		);

		// Set up available sub-category options
		$subcategory_options = array(

			'' => __( '-- None --', 'seriously-simple-podcasting' ),

			'Design' => array( 'label' => __( 'Design', 'seriously-simple-podcasting' ), 'group' => __( 'Arts', 'seriously-simple-podcasting' ) ),
			'Fashion & Beauty' => array( 'label' => __( 'Fashion & Beauty', 'seriously-simple-podcasting' ), 'group' => __( 'Arts', 'seriously-simple-podcasting' ) ),
			'Food' => array( 'label' => __( 'Food', 'seriously-simple-podcasting' ), 'group' => __( 'Arts', 'seriously-simple-podcasting' ) ),
			'Literature' => array( 'label' => __( 'Literature', 'seriously-simple-podcasting' ), 'group' => __( 'Arts', 'seriously-simple-podcasting' ) ),
			'Performing Arts' => array( 'label' => __( 'Performing Arts', 'seriously-simple-podcasting' ), 'group' => __( 'Arts', 'seriously-simple-podcasting' ) ),
			'Visual Arts' => array( 'label' => __( 'Visual Arts', 'seriously-simple-podcasting' ), 'group' => __( 'Arts', 'seriously-simple-podcasting' ) ),

			'Business News' => array( 'label' => __( 'Business News', 'seriously-simple-podcasting' ), 'group' => __( 'Business', 'seriously-simple-podcasting' ) ),
			'Careers' => array( 'label' => __( 'Careers', 'seriously-simple-podcasting' ), 'group' => __( 'Business', 'seriously-simple-podcasting' ) ),
			'Investing' => array( 'label' => __( 'Investing', 'seriously-simple-podcasting' ), 'group' => __( 'Business', 'seriously-simple-podcasting' ) ),
			'Management & Marketing' => array( 'label' => __( 'Management & Marketing', 'seriously-simple-podcasting' ), 'group' => __( 'Business', 'seriously-simple-podcasting' ) ),
			'Shopping' => array( 'label' => __( 'Shopping', 'seriously-simple-podcasting' ), 'group' => __( 'Business', 'seriously-simple-podcasting' ) ),

			'Education' => array( 'label' => __( 'Education', 'seriously-simple-podcasting' ), 'group' => __( 'Education', 'seriously-simple-podcasting' ) ),
			'Education Technology' => array( 'label' => __( 'Education Technology', 'seriously-simple-podcasting' ), 'group' => __( 'Education', 'seriously-simple-podcasting' ) ),
			'Higher Education' => array( 'label' => __( 'Higher Education', 'seriously-simple-podcasting' ), 'group' => __( 'Education', 'seriously-simple-podcasting' ) ),
			'K-12' => array( 'label' => __( 'K-12', 'seriously-simple-podcasting' ), 'group' => __( 'Education', 'seriously-simple-podcasting' ) ),
			'Language Courses' => array( 'label' => __( 'Language Courses', 'seriously-simple-podcasting' ), 'group' => __( 'Education', 'seriously-simple-podcasting' ) ),
			'Training' => array( 'label' => __( 'Training', 'seriously-simple-podcasting' ), 'group' => __( 'Education', 'seriously-simple-podcasting' ) ),

			'Automotive' => array( 'label' => __( 'Automotive', 'seriously-simple-podcasting' ), 'group' => __( 'Games & Hobbies', 'seriously-simple-podcasting' ) ),
			'Aviation' => array( 'label' => __( 'Aviation', 'seriously-simple-podcasting' ), 'group' => __( 'Games & Hobbies', 'seriously-simple-podcasting' ) ),
			'Hobbies' => array( 'label' => __( 'Hobbies', 'seriously-simple-podcasting' ), 'group' => __( 'Games & Hobbies', 'seriously-simple-podcasting' ) ),
			'Other Games' => array( 'label' => __( 'Other Games', 'seriously-simple-podcasting' ), 'group' => __( 'Games & Hobbies', 'seriously-simple-podcasting' ) ),
			'Video Games' => array( 'label' => __( 'Video Games', 'seriously-simple-podcasting' ), 'group' => __( 'Games & Hobbies', 'seriously-simple-podcasting' ) ),

			'Local' => array( 'label' => __( 'Local', 'seriously-simple-podcasting' ), 'group' => __( 'Government & Organizations', 'seriously-simple-podcasting' ) ),
			'National' => array( 'label' => __( 'National', 'seriously-simple-podcasting' ), 'group' => __( 'Government & Organizations', 'seriously-simple-podcasting' ) ),
			'Non-Profit' => array( 'label' => __( 'Non-Profit', 'seriously-simple-podcasting' ), 'group' => __( 'Government & Organizations', 'seriously-simple-podcasting' ) ),
			'Regional' => array( 'label' => __( 'Regional', 'seriously-simple-podcasting' ), 'group' => __( 'Government & Organizations', 'seriously-simple-podcasting' ) ),

			'Alternative Health' => array( 'label' => __( 'Alternative Health', 'seriously-simple-podcasting' ), 'group' => __( 'Health', 'seriously-simple-podcasting' ) ),
			'Fitness & Nutrition' => array( 'label' => __( 'Fitness & Nutrition', 'seriously-simple-podcasting' ), 'group' => __( 'Health', 'seriously-simple-podcasting' ) ),
			'Self-Help' => array( 'label' => __( 'Self-Help', 'seriously-simple-podcasting' ), 'group' => __( 'Health', 'seriously-simple-podcasting' ) ),
			'Sexuality' => array( 'label' => __( 'Sexuality', 'seriously-simple-podcasting' ), 'group' => __( 'Health', 'seriously-simple-podcasting' ) ),

			'Buddhism' => array( 'label' => __( 'Buddhism', 'seriously-simple-podcasting' ), 'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ) ),
			'Christianity' => array( 'label' => __( 'Christianity', 'seriously-simple-podcasting' ), 'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ) ),
			'Hinduism' => array( 'label' => __( 'Hinduism', 'seriously-simple-podcasting' ), 'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ) ),
			'Islam' => array( 'label' => __( 'Islam', 'seriously-simple-podcasting' ), 'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ) ),
			'Judaism' => array( 'label' => __( 'Judaism', 'seriously-simple-podcasting' ), 'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ) ),
			'Other' => array( 'label' => __( 'Other', 'seriously-simple-podcasting' ), 'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ) ),
			'Spirituality' => array( 'label' => __( 'Spirituality', 'seriously-simple-podcasting' ), 'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ) ),

			'Medicine' => array( 'label' => __( 'Medicine', 'seriously-simple-podcasting' ), 'group' => __( 'Science & Medicine', 'seriously-simple-podcasting' ) ),
			'Natural Sciences' => array( 'label' => __( 'Natural Sciences', 'seriously-simple-podcasting' ), 'group' => __( 'Science & Medicine', 'seriously-simple-podcasting' ) ),
			'Social Sciences' => array( 'label' => __( 'Social Sciences', 'seriously-simple-podcasting' ), 'group' => __( 'Science & Medicine', 'seriously-simple-podcasting' ) ),

			'History' => array( 'label' => __( 'History', 'seriously-simple-podcasting' ), 'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ) ),
			'Personal Journals' => array( 'label' => __( 'Personal Journals', 'seriously-simple-podcasting' ), 'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ) ),
			'Philosophy' => array( 'label' => __( 'Philosophy', 'seriously-simple-podcasting' ), 'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ) ),
			'Places & Travel' => array( 'label' => __( 'Places & Travel', 'seriously-simple-podcasting' ), 'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ) ),

			'Amateur' => array( 'label' => __( 'Amateur', 'seriously-simple-podcasting' ), 'group' => __( 'Sports & Recreation', 'seriously-simple-podcasting' ) ),
			'College & High School' => array( 'label' => __( 'College & High School', 'seriously-simple-podcasting' ), 'group' => __( 'Sports & Recreation', 'seriously-simple-podcasting' ) ),
			'Outdoor' => array( 'label' => __( 'Outdoor', 'seriously-simple-podcasting' ), 'group' => __( 'Sports & Recreation', 'seriously-simple-podcasting' ) ),
			'Professional' => array( 'label' => __( 'Professional', 'seriously-simple-podcasting' ), 'group' => __( 'Sports & Recreation', 'seriously-simple-podcasting' ) ),

			'Gadgets' => array( 'label' => __( 'Gadgets', 'seriously-simple-podcasting' ), 'group' => __( 'Technology', 'seriously-simple-podcasting' ) ),
			'Tech News' => array( 'label' => __( 'Tech News', 'seriously-simple-podcasting' ), 'group' => __( 'Technology', 'seriously-simple-podcasting' ) ),
			'Podcasting' => array( 'label' => __( 'Podcasting', 'seriously-simple-podcasting' ), 'group' => __( 'Technology', 'seriously-simple-podcasting' ) ),
			'Software How-To' => array( 'label' => __( 'Software How-To', 'seriously-simple-podcasting' ), 'group' => __( 'Technology', 'seriously-simple-podcasting' ) ),

		);

		$settings = array();

		$settings['general'] = array(
			'title'					=> __( 'General', 'seriously-simple-podcasting' ),
			'description'			=> __( '', 'seriously-simple-podcasting' ),
			'fields'				=> array(
				array(
					'id' 			=> 'use_post_types',
					'label'			=> __( 'Podcast post types', 'seriously-simple-podcasting' ),
					'description'	=> __( 'Use this setting to enable podcast functions on any post type - this will add all podcast posts from the specified types to your podcast feed.', 'seriously-simple-podcasting' ),
					'type'			=> 'checkbox_multi',
					'options'		=> $post_type_options,
					'default'		=> array(),
				),
				array(
					'id' 			=> 'include_in_main_query',
					'label'			=> __( 'Include podcast in main blog', 'seriously-simple-podcasting' ),
					'description'	=> __( 'This setting may behave differently in each theme, so test it carefully after activation - it will add the \'podcast\' post type to your site\'s main query so that your podcast episodes appear on your home page along with your blog posts.', 'seriously-simple-podcasting' ),
					'type'			=> 'checkbox',
					'default'		=> '',
				),
				array(
					'id' 			=> 'player_locations',
					'label'			=> __( 'Media player locations', 'seriously-simple-podcasting' ),
					'description'	=> __( 'Select where to show the podcast media player along with the episode data (download link, duration and file size)', 'seriously-simple-podcasting' ),
					'type'			=> 'checkbox_multi',
					'options'		=> array( 'content' => __( 'Full content', 'seriously-simple-podcasting' ), 'excerpt' => __( 'Excerpt', 'seriously-simple-podcasting' ),  'excerpt_embed' => __( 'oEmbed Excerpt', 'seriously-simple-podcasting' ) ),
					'default'		=> array(),
				),
				array(
					'id' 			=> 'player_content_location',
					'label'			=> __( 'Media player position', 'seriously-simple-podcasting' ),
					'description'	=> __( 'Select whether to display the media player above or below the full post content.', 'seriously-simple-podcasting' ),
					'type'			=> 'radio',
					'options'		=> array( 'above' => __( 'Above content', 'seriously-simple-podcasting' ), 'below' => __( 'Below content', 'seriously-simple-podcasting' ) ),
					'default'		=> 'above',
				),
			),
		);

		$settings['feed-details'] = array(
			'title'					=> __( 'Feed details', 'seriously-simple-podcasting' ),
			'description'			=> sprintf( __( 'This data will be used in the feed for your podcast so your listeners will know more about it before they subscribe.%1$sAll of these fields are optional, but it is recommended that you fill in as many of them as possible. Blank fields will use the assigned defaults in the feed.%2$s', 'seriously-simple-podcasting' ), '<br/><em>', '</em>' ),
			'fields'				=> array(
				array(
					'id' 			=> 'data_title',
					'label'			=> __( 'Title' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast title.', 'seriously-simple-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'name' ),
					'placeholder'	=> get_bloginfo( 'name' ),
					'class'			=> 'large-text',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_subtitle',
					'label'			=> __( 'Subtitle' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast subtitle.', 'seriously-simple-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'description' ),
					'placeholder'	=> get_bloginfo( 'description' ),
					'class'			=> 'large-text',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_author',
					'label'			=> __( 'Author' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast author.', 'seriously-simple-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'name' ),
					'placeholder'	=> get_bloginfo( 'name' ),
					'class'			=> 'large-text',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_category',
					'label'			=> __( 'Primary Category' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast\'s primary category.', 'seriously-simple-podcasting' ),
					'type'			=> 'select',
					'options'		=> $category_options,
					'default'		=> '',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_subcategory',
					'label'			=> __( 'Primary Sub-Category' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast\'s primary sub-category (if available) - must be a sub-category of the primary category selected above.', 'seriously-simple-podcasting' ),
					'type'			=> 'select',
					'options'		=> $subcategory_options,
					'default'		=> '',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_category2',
					'label'			=> __( 'Secondary Category' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast\'s secondary category.', 'seriously-simple-podcasting' ),
					'type'			=> 'select',
					'options'		=> $category_options,
					'default'		=> '',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_subcategory2',
					'label'			=> __( 'Secondary Sub-Category' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast\'s secondary sub-category (if available) - must be a sub-category of the secondary category selected above.', 'seriously-simple-podcasting' ),
					'type'			=> 'select',
					'options'		=> $subcategory_options,
					'default'		=> '',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_category3',
					'label'			=> __( 'Tertiary Category' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast\'s tertiary category.', 'seriously-simple-podcasting' ),
					'type'			=> 'select',
					'options'		=> $category_options,
					'default'		=> '',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_subcategory3',
					'label'			=> __( 'Tertiary Sub-Category' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast\'s tertiary sub-category (if available) - must be a sub-category of the tertiary category selected above.', 'seriously-simple-podcasting' ),
					'type'			=> 'select',
					'options'		=> $subcategory_options,
					'default'		=> '',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_description',
					'label'			=> __( 'Description/Summary' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'A description/summary of your podcast - no HTML allowed.', 'seriously-simple-podcasting' ),
					'type'			=> 'textarea',
					'default'		=> get_bloginfo( 'description' ),
					'placeholder'	=> get_bloginfo( 'description' ),
					'callback'		=> 'wp_strip_all_tags',
					'class'			=> 'large-text',
				),
				array(
					'id' 			=> 'data_image',
					'label'			=> __( 'Cover Image' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast cover image - must have a minimum size of 1400x1400 px.', 'seriously-simple-podcasting' ),
					'type'			=> 'image',
					'default'		=> '',
					'placeholder'	=> '',
					'callback'		=> 'esc_url_raw',
				),
				array(
					'id' 			=> 'data_owner_name',
					'label'			=> __( 'Owner name' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Podcast owner\'s name.', 'seriously-simple-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'name' ),
					'placeholder'	=> get_bloginfo( 'name' ),
					'class'			=> 'large-text',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_owner_email',
					'label'			=> __( 'Owner email address' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Podcast owner\'s email address.', 'seriously-simple-podcasting' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'admin_email' ),
					'placeholder'	=> get_bloginfo( 'admin_email' ),
					'class'			=> 'large-text',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_language',
					'label'			=> __( 'Language' , 'seriously-simple-podcasting' ),
					'description'	=> sprintf( __( 'Your podcast\'s language in %1$sISO-639-1 format%2$s.', 'seriously-simple-podcasting' ), '<a href="' . esc_url( 'http://www.loc.gov/standards/iso639-2/php/code_list.php' ) . '" target="' . wp_strip_all_tags( '_blank' ) . '">', '</a>' ),
					'type'			=> 'text',
					'default'		=> get_bloginfo( 'language' ),
					'placeholder'	=> get_bloginfo( 'language' ),
					'class'			=> 'all-options',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'data_copyright',
					'label'			=> __( 'Copyright' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Copyright line for your podcast.', 'seriously-simple-podcasting' ),
					'type'			=> 'text',
					'default'		=> '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ),
					'placeholder'	=> '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ),
					'class'			=> 'large-text',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'explicit',
					'label'			=> __( 'Explicit', 'seriously-simple-podcasting' ),
					'description'	=> __( 'Mark if your podcast is explicit or not.', 'seriously-simple-podcasting' ),
					'type'			=> 'checkbox',
					'default'		=> '',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'complete',
					'label'			=> __( 'Complete', 'seriously-simple-podcasting' ),
					'description'	=> __( 'Mark if this podcast is complete or not. Only do this if no more episodes are going to be added to this feed.', 'seriously-simple-podcasting' ),
					'type'			=> 'checkbox',
					'default'		=> '',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'redirect_feed',
					'label'			=> __( 'Redirect this feed to new URL', 'seriously-simple-podcasting' ),
					'description'	=> sprintf( __( 'Redirect your feed to a new URL (specified below).', 'seriously-simple-podcasting' ) , '<br/>' ),
					'type'			=> 'checkbox',
					'default'		=> '',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'new_feed_url',
					'label'			=> __( 'New podcast feed URL', 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast feed\'s new URL.', 'seriously-simple-podcasting' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'New feed URL', 'seriously-simple-podcasting' ),
					'callback'		=> 'esc_url_raw',
					'class'			=> 'regular-text',
				),
			)
		);

		$settings['security'] = array(
			'title'					=> __( 'Security', 'seriously-simple-podcasting' ),
			'description'			=> __( 'Change these settings to ensure that your podcast feed remains private. This will block feed readers (including iTunes) from accessing your feed.', 'seriously-simple-podcasting' ),
			'fields'				=> array(
				array(
					'id' 			=> 'protect',
					'label'			=> __( 'Password protect your podcast feed', 'seriously-simple-podcasting' ),
					'description'	=> __( 'Mark if you would like to password protect your podcast feed - you can set the username and password below. This will block all feed readers (including iTunes) from accessing your feed.', 'seriously-simple-podcasting' ),
					'type'			=> 'checkbox',
					'default'		=> '',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'protection_username',
					'label'			=> __( 'Username' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Username for your podcast feed.', 'seriously-simple-podcasting' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'Feed username', 'seriously-simple-podcasting' ),
					'class'			=> 'regular-text',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'protection_password',
					'label'			=> __( 'Password' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'Password for your podcast feed. Once saved, the password is encoded and secured so it will not be visible on this page again.', 'seriously-simple-podcasting' ),
					'type'			=> 'text_secret',
					'default'		=> '',
					'placeholder'	=> __( 'Feed password', 'seriously-simple-podcasting' ),
					'callback'		=> array( $this, 'encode_password' ),
					'class'			=> 'regular-text',
				),
				array(
					'id' 			=> 'protection_no_access_message',
					'label'			=> __( 'No access message' , 'seriously-simple-podcasting' ),
					'description'	=> __( 'This message will be displayed to people who are not allowed access to your podcast feed. Limited HTML allowed.', 'seriously-simple-podcasting' ),
					'type'			=> 'textarea',
					'default'		=> __( 'You are not permitted to view this podcast feed.', 'seriously-simple-podcasting' ),
					'placeholder'	=> __( 'Message displayed to users who do not have access to the podcast feed', 'seriously-simple-podcasting' ),
					'callback'		=> array( $this, 'validate_message' ),
					'class'			=> 'large-text',
				),
			)
		);

		$settings['redirection'] = array(
			'title'					=> __( 'Redirection', 'seriously-simple-podcasting' ),
			'description'			=> __( 'Use these settings to safely move your podcast to a different location. Only do this once your new podcast is setup and active.', 'seriously-simple-podcasting' ),
			'fields'				=> array(
				array(
					'id' 			=> 'redirect_feed',
					'label'			=> __( 'Redirect podcast feed to new URL', 'seriously-simple-podcasting' ),
					'description'	=> sprintf( __( 'Redirect your feed to a new URL (specified below).%1$sThis will inform all podcasting services that your podcast has moved and 48 hours after you have saved this option it will permanently redirect your feed to the new URL.', 'seriously-simple-podcasting' ) , '<br/>' ),
					'type'			=> 'checkbox',
					'default'		=> '',
					'callback'		=> 'wp_strip_all_tags',
				),
				array(
					'id' 			=> 'new_feed_url',
					'label'			=> __( 'New podcast feed URL', 'seriously-simple-podcasting' ),
					'description'	=> __( 'Your podcast feed\'s new URL.', 'seriously-simple-podcasting' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'New feed URL', 'seriously-simple-podcasting' ),
					'callback'		=> 'esc_url_raw',
					'class'			=> 'regular-text',
				),
			)
		);

		$settings['publishing'] = array(
			'title'					=> __( 'Publishing' , 'seriously-simple-podcasting' ),
			'description'			=> __( 'Use these URLs to share and publish your podcast feed. These URLs will work with any podcasting service (including iTunes).' , 'seriously-simple-podcasting' ),
			'fields'				=> array(
				array(
					'id' 			=> 'feed_url',
					'label'			=> __( 'External feed URL', 'seriously-simple-podcasting' ),
					'description'	=> __( 'If you are syndicating your podcast using a third-party service (like Feedburner) you can insert the URL here, otherwise this must be left blank.' , 'seriously-simple-podcasting' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'External feed URL', 'seriously-simple-podcasting' ),
					'callback'		=> 'esc_url_raw',
					'class'			=> 'regular-text',
				),
				array(
					'id' 			=> 'feed_link',
					'label'			=> __( 'Complete feed', 'seriously-simple-podcasting' ),
					'description'	=> '',
					'type'			=> 'feed_link',
					'callback'		=> 'esc_url_raw',
				),
				array(
					'id' 			=> 'feed_link_series',
					'label'			=> __( 'Feed for a specific series', 'seriously-simple-podcasting' ),
					'description'	=> '',
					'type'			=> 'feed_link_series',
					'callback'		=> 'esc_url_raw',
				),
				array(
					'id' 			=> 'podcast_url',
					'label'			=> __( 'Podcast page', 'seriously-simple-podcasting' ),
					'description'	=> '',
					'type'			=> 'podcast_url',
					'callback'		=> 'esc_url_raw',
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
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = 'general';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) {
					continue;
				}

				// Get data for specific feed series
				$title_tail = '';
				$series_id = 0;
				if ( 'feed-details' == $section ) {

					if ( isset( $_REQUEST['feed-series'] ) && $_REQUEST['feed-series'] && 'default' != $_REQUEST['feed-series'] ) {

						// Get selected series
						$series = get_term_by( 'slug', esc_attr( $_REQUEST['feed-series'] ), 'series' );

						// Store series ID for later use
						$series_id = $series->term_id;

						// Append series name to section title
						if ( $series ) {
							$title_tail = ': ' . $series->name;
						}
					}
				}

				$section_title = $data['title'] . $title_tail;

				// Add section to page
				add_settings_section( $section, $section_title, array( $this, 'settings_section' ), 'ss_podcasting' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Get field option name
					$option_name = $this->settings_base . $field['id'];

					// Append series ID if selected
					if ( $series_id ) {
						$option_name .= '_' . $series_id;
					}

					// Register setting
					register_setting( 'ss_podcasting', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this, 'display_field' ), 'ss_podcasting', $section, array( 'field' => $field, 'prefix' => $this->settings_base, 'feed-series' => $series_id ) );
				}
			}
		}
	}

	public function settings_section( $section ) {
		$html = '<p>' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";

		if( 'feed-details' == $section['id'] ) {

			$feed_series = 'default';
			if( isset( $_GET['feed-series'] ) ) {
				$feed_series = esc_attr( $_GET['feed-series'] );
			}

			$permalink_structure = get_option( 'permalink_structure' );

			if ( $permalink_structure ) {
				$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
				$feed_url = $this->home_url . 'feed/' . $feed_slug;
			} else {
				$feed_url = $this->home_url . '?feed=' . $this->token;
			}

			if( $feed_series && $feed_series != 'default' ) {
				if ( $permalink_structure ) {
					$feed_url .= '/' . $feed_series;
				} else {
					$feed_url .= '&podcast_series=' . $feed_series;
				}
			}

			if( $feed_url ) {
				$html .= '<p><a class="view-feed-link" href="' . esc_url( $feed_url ) . '" target="_blank"><span class="dashicons dashicons-rss"></span>' . __( 'View feed', 'seriously-simple-podcasting' ) . '</a></p>' . "\n";
			}

		}

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

		// Get option name
		$option_name = $this->settings_base . $field['id'];
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

				if ( in_array( $field['type'], array( 'checkbox', 'select', 'image' ) ) ) {
					$option_default = $data;
				}
			}

			// Append series ID to option name
			$option_name .= '_' . $args['feed-series'];

			// Get series-sepcific option
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

		switch( $field['type'] ) {
			case 'text':
			case 'password':
			case 'number':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '" class="' . $class . '"/>' . "\n";
			break;

			case 'text_secret':
				$placeholder = $field['placeholder'];
				if ( $data ) {
					$placeholder = __( 'Password stored securely', 'seriously-simple-podcasting' );
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="" class="' . $class . '"/>' . "\n";
			break;

			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" class="' . $class . '">' . $data . '</textarea><br/>'. "\n";
			break;

			case 'checkbox':
				$checked = '';
				if ( $data && 'on' == $data ){
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" ' . $checked . ' class="' . $class . '"/>' . "\n";
			break;

			case 'checkbox_multi':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( in_array( $k, (array) $data ) ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" class="' . $class . '" /> ' . $v . '</label><br/>';
				}
			break;

			case 'radio':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( $k == $data ) {
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

					if ( $prev_group && $group != $prev_group ) {
						$html .= '</optgroup>';
					}

					$selected = false;
					if ( $k == $data ) {
						$selected = true;
					}

					if ( $group && $group != $prev_group ) {
						$html .= '<optgroup label="' . esc_attr( $group ) . '">';
					}

					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . esc_html( $v ) . '</option>';

					$prev_group = $group;
				}
				$html .= '</select> ';
			break;

			case 'image':
				$html .= '<img id="' . esc_attr( $default_option_name ) . '_preview" src="' . esc_attr( $data ) . '" style="max-width:400px;height:auto;" /><br/>' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '_button" type="button" class="button" value="'. __( 'Upload new image' , 'seriously-simple-podcasting' ) . '" />' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '_delete" type="button" class="button" value="'. __( 'Remove image' , 'seriously-simple-podcasting' ) . '" />' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '" type="hidden" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $data ) . '"/><br/>' . "\n";
			break;

			case 'feed_link':

				// Set feed URL based on site's permalink structure
				if ( get_option( 'permalink_structure' ) ) {
					$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
					$url = $this->home_url . 'feed/' . $feed_slug;
				} else {
					$url = $this->home_url . '?feed=' . $this->token;
				}

				$html .= '<a href="' . esc_url( $url ) . '" target="_blank">' . $url . '</a>';
			break;

			case 'feed_link_series':

				// Set feed URL based on site's permalink structure
				if ( get_option( 'permalink_structure' ) ) {
					$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
					$url = $this->home_url . 'feed/' . $feed_slug . '/series-slug';
				} else {
					$url = $this->home_url . '?feed=' . $this->token . '&podcast_series=series-slug';
				}

				$html .= esc_url( $url ) . "\n";
			break;

			case 'podcast_url';

				$slug = apply_filters( 'ssp_archive_slug', __( 'podcast' , 'seriously-simple-podcasting' ) );
				$podcast_url = $this->home_url . $slug;

				$html .= '<a href="' . esc_url( $podcast_url ) . '" target="_blank">' . $podcast_url . '</a>';
			break;

		}

		if ( ! in_array( $field['type'], array( 'feed_link', 'feed_link_series', 'podcast_url' ) ) ) {
			switch( $field['type'] ) {

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

		if( $parent_class ) {
			$html = '<div class="' . $parent_class . '">' . $html . '</div>';
		}

		echo $html;
	}

	/**
	 * Validate URL slug
	 * @param  string $slug User input
	 * @return string       Validated string
	 */
	public function validate_slug( $slug ) {
		if ( $slug && strlen( $slug ) > 0 && $slug != '' ) {
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

		if ( $password && strlen( $password ) > 0 && $password != '' ) {
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

		if ( $message ) {

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

		if ( $option == 'ss_podcasting_redirect_feed' ) {
			if ( ( $new_value != $old_value ) && $new_value == 'on' ) {
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
			$html .= '<h1>' . __( 'Podcast Settings' , 'seriously-simple-podcasting' ) . '</h1>' . "\n";

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

					if ( isset( $_GET['feed-series'] ) ) {
						$tab_link = remove_query_arg( 'feed-series', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . esc_url( $tab_link ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}

			if ( isset( $_GET['settings-updated'] ) ) {
				$html .= '<br/><div class="updated notice notice-success is-dismissible">
					        <p>' . sprintf( __( '%1$s settings updated.', 'seriously-simple-podcasting' ), '<b>' . str_replace( '-', ' ', ucfirst( $tab ) ) . '</b>' ) . '</p>
					    </div>';
			}

			if( function_exists( 'php_sapi_name') && 'security' == $tab ) {
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
						$series_class = '';
					} else {
						$current_series = 'default';
						$series_class = 'current';
					}

					$html .= '<div class="feed-series-list-container">' . "\n";
						$html .= '<span id="feed-series-toggle" class="series-open" title="' . __( 'Toggle series list display', 'seriously-simple-podcasting' ) . '"></span>' . "\n";

						$html .= '<ul id="feed-series-list" class="subsubsub series-open">' . "\n";
							$html .= '<li><a href="' . add_query_arg( array( 'feed-series' => 'default', 'settings-updated' => false ) ) . '" class="' . $series_class . '">' . __( 'Default feed', 'seriously-simple-podcasting' ) . '</a></li>';

							foreach ( $series as $s ) {

								if ( $current_series == $s->slug ) {
									$series_class = 'current';
								} else {
									$series_class = '';
								}

								$html .= '<li>' . "\n";
									$html .= ' | <a href="' . esc_url( add_query_arg( array( 'feed-series' => $s->slug, 'settings-updated' => false ) ) ) . '" class="' . $series_class . '">' . $s->name . '</a>' . "\n";
								$html .= '</li>' . "\n";
							}

						$html .= '</ul>' . "\n";
						$html .= '<br class="clear" />' . "\n";
					$html .= '</div>' . "\n";

				}
			}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Add current series to posted data
				if ( $current_series ) {
					$html .= '<input type="hidden" name="feed-series" value="' . esc_attr( $current_series ) . '" />' . "\n";
				}

				// Get settings fields
				ob_start();
				settings_fields( 'ss_podcasting' );
				do_settings_sections( 'ss_podcasting' );
				$html .= ob_get_clean();

				// Submit button
				$html .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'seriously-simple-podcasting' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";

			$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

	  	echo $html;
	}

}