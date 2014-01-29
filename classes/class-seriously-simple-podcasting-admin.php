<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class SeriouslySimplePodcasting_Admin {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $home_url;
	private $token;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->home_url = trailingslashit( home_url() );
		$this->token = 'podcast';

		// Register podcast settings
		add_action( 'admin_init' , array( $this , 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this , 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ) , array( $this , 'add_settings_link' ) );

		// Load scripts for settings page
		add_action( 'admin_enqueue_scripts' , array( $this, 'enqueue_admin_scripts' ) , 10 );

		// Mark date on which feed redirection was activated
		add_action( 'update_option' , array( $this, 'mark_feed_redirect_date' ) , 10 , 3 );

		// Display notices in the WP admin
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 10 );
		add_action( 'admin_init', array( $this, 'admin_notice_actions'), 1 );

	}

	public function add_menu_item() {
		add_submenu_page( 'edit.php?post_type=podcast' , 'Podcast Settings' , 'Settings', 'manage_options' , 'podcast_settings' , array( $this , 'settings_page' ) );
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="edit.php?post_type=podcast&page=podcast_settings">Settings</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	public function enqueue_admin_scripts () {
		global $wp_version;
		// Admin JS
		wp_register_script( 'ss_podcasting-admin', esc_url( $this->assets_url . 'js/admin.js' ), array( 'jquery' ), '1.7.5' );
		wp_enqueue_script( 'ss_podcasting-admin' );

		if( $wp_version >= 3.5 ) {
			// Media uploader scripts
			wp_enqueue_media();
		}

	}

	public function admin_notices() {
		global $current_user, $wp_version;
        $user_id = $current_user->ID;

        // Version notice
        if( $wp_version < 3.5 ) {
			?>
			<div class="error">
		        <p><?php printf( __( '%1$sSeriously Simple Podcasting%2$s requires WordPress 3.5 or above in order to function correctly. You are running v%3$s - please update now.', 'ss-podcasting' ), '<strong>', '</strong>', $wp_version ); ?></p>
		    </div>
		    <?php
		}

		// Survey notice
        $hide_survey_notice = get_user_meta( $user_id, 'ss_podcasting_hide_survey_notice', true );
        if( ! $hide_survey_notice ) {
			?>
			<div class="updated">
		        <p><?php printf( __( 'Got some ideas on how to improve your podcasting experience? %1$sClick here%2$s to take a quick Seriously Simple Podcasting user survey. %3$s%4$sHide this notice%5$s', 'ss-podcasting' ), '<a href="https://docs.google.com/forms/d/1PbMBocuGZq4K_LV2dL-GfmAJwNlsT76HUbr5fgRZxfo/viewform" target="_blank">', '</a>', '<br/>', '<em><a href="' . add_query_arg( 'ssp_hide_notice', 'survey' ) . '">', '</a></em>' ); ?></p>
		    </div>
		    <?php
		}
	}

	public function admin_notice_actions() {
		if( isset( $_GET['ssp_hide_notice'] ) ) {
			global $current_user ;
        	$user_id = $current_user->ID;

			switch( esc_attr( $_GET['ssp_hide_notice'] ) ) {
				case 'survey': add_user_meta( $user_id, 'ss_podcasting_hide_survey_notice', true ); break;
			}

		}
	}

	public function register_settings() {

		// Add settings section
		add_settings_section( 'customise' , __( 'Customise' , 'ss-podcasting' ) , array( $this , 'main_settings' ) , 'ss_podcasting' );
		add_settings_section( 'describe' , __( 'Describe' , 'ss-podcasting' ) , array( $this , 'podcast_data' ) , 'ss_podcasting' );
		add_settings_section( 'protect' , __( 'Protect' , 'ss-podcasting' ) , array( $this , 'protection_settings' ) , 'ss_podcasting' );
		add_settings_section( 'redirect' , __( 'Redirect' , 'ss-podcasting' ) , array( $this , 'redirect_settings' ) , 'ss_podcasting' );
		add_settings_section( 'share' , __( 'Share' , 'ss-podcasting' ) , array( $this , 'feed_info' ) , 'ss_podcasting' );

		// Add settings fields
		add_settings_field( 'ss_podcasting_use_templates' , __( 'Use built-in plugin templates:' , 'ss-podcasting' ) , array( $this , 'use_templates_field' )  , 'ss_podcasting' , 'customise' );
		add_settings_field( 'ss_podcasting_slug' , __( 'URL slug for podcast pages:' , 'ss-podcasting' ) , array( $this , 'slug_field' )  , 'ss_podcasting' , 'customise' );
		add_settings_field( 'ss_podcasting_feed_url' , __( 'URL for your podcast:' , 'ss-podcasting' ) , array( $this , 'feed_url_field' )  , 'ss_podcasting' , 'customise' );
		add_settings_field( 'ss_podcasting_include_in_main_query' , __( 'Include podcast episodes in home page blog listing:' , 'ss-podcasting' ) , array( $this , 'include_in_main_query' )  , 'ss_podcasting' , 'customise' );
		add_settings_field( 'ss_podcasting_hide_content_meta' , __( 'Prevent audio player and podcast data from showing above episode content:' , 'ss-podcasting' ) , array( $this , 'content_meta' )  , 'ss_podcasting' , 'customise' );

		// Add data fields
		add_settings_field( 'ss_podcasting_data_title' , __( 'Title:' , 'ss-podcasting' ) , array( $this , 'data_title' )  , 'ss_podcasting' , 'describe' );
		add_settings_field( 'ss_podcasting_data_subtitle' , __( 'Subtitle:' , 'ss-podcasting' ) , array( $this , 'data_subtitle' )  , 'ss_podcasting' , 'describe' );
		add_settings_field( 'ss_podcasting_data_author' , __( 'Author:' , 'ss-podcasting' ) , array( $this , 'data_author' )  , 'ss_podcasting' , 'describe' );
		add_settings_field( 'ss_podcasting_data_category' , __( 'Category:' , 'ss-podcasting' ) , array( $this , 'data_category' )  , 'ss_podcasting' , 'describe' );
		add_settings_field( 'ss_podcasting_data_subcategory' , __( 'Sub-Category:' , 'ss-podcasting' ) , array( $this , 'data_subcategory' )  , 'ss_podcasting' , 'describe' );
		add_settings_field( 'ss_podcasting_data_description' , __( 'Description/Summary:' , 'ss-podcasting' ) , array( $this , 'data_description' )  , 'ss_podcasting' , 'describe' );
		add_settings_field( 'ss_podcasting_data_image' , __( 'Image:' , 'ss-podcasting' ) , array( $this , 'data_image' )  , 'ss_podcasting' , 'describe' );
		add_settings_field( 'ss_podcasting_data_owner_name' , __( 'Owner:' , 'ss-podcasting' ) , array( $this , 'data_owner' )  , 'ss_podcasting' , 'describe' );
		add_settings_field( 'ss_podcasting_data_language' , __( 'Language:' , 'ss-podcasting' ) , array( $this , 'data_language' )  , 'ss_podcasting' , 'describe' );
		add_settings_field( 'ss_podcasting_data_copyright' , __( 'Copyright:' , 'ss-podcasting' ) , array( $this , 'data_copyright' )  , 'ss_podcasting' , 'describe' );
		add_settings_field( 'ss_podcasting_data_explicit' , __( 'Explicit:' , 'ss-podcasting' ) , array( $this , 'data_explicit' )  , 'ss_podcasting' , 'describe' );

		// Add sharing fields
		add_settings_field( 'ss_podcasting_feed_standard' , __( 'Complete feed:' , 'ss-podcasting' ) , array( $this , 'feed_standard' )  , 'ss_podcasting' , 'share' );
		add_settings_field( 'ss_podcasting_feed_standard_series' , __( 'Feed for a specific series:' , 'ss-podcasting' ) , array( $this , 'feed_standard_series' )  , 'ss_podcasting' , 'share' );
		add_settings_field( 'ss_podcasting_podcast_url' , __( 'Podcast page:' , 'ss-podcasting' ) , array( $this , 'podcast_url' )  , 'ss_podcasting' , 'share' );
		add_settings_field( 'ss_podcasting_social_sharing' , __( 'Share online:' , 'ss-podcasting' ) , array( $this , 'social_sharing' )  , 'ss_podcasting' , 'share' );

		// Add redirect settings fields
		add_settings_field( 'ss_podcasting_redirect_feed' , __( 'Redirect podcast feed to new URL:' , 'ss-podcasting' ) , array( $this , 'redirect_feed' )  , 'ss_podcasting' , 'redirect' );
		add_settings_field( 'ss_podcasting_new_feed_url' , __( 'New podcast URL:' , 'ss-podcasting' ) , array( $this , 'new_feed_url' )  , 'ss_podcasting' , 'redirect' );

		// Add protection settings fields
		add_settings_field( 'ss_podcasting_protect_feed' , __( 'Password protect your podcast feed:' , 'ss-podcasting' ) , array( $this , 'protect_feed' )  , 'ss_podcasting' , 'protect' );
		add_settings_field( 'ss_podcasting_protection_username' , __( 'Username:' , 'ss-podcasting' ) , array( $this , 'protection_username' )  , 'ss_podcasting' , 'protect' );
		add_settings_field( 'ss_podcasting_protection_password' , __( 'Password:' , 'ss-podcasting' ) , array( $this , 'protection_password' )  , 'ss_podcasting' , 'protect' );
		add_settings_field( 'ss_podcasting_protection_no_access_message' , __( 'No access message:' , 'ss-podcasting' ) , array( $this , 'protection_no_access_message' )  , 'ss_podcasting' , 'protect' );

		// Register settings fields
		register_setting( 'ss_podcasting' , 'ss_podcasting_use_templates' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_slug' , array( $this , 'validate_slug' ) );
		register_setting( 'ss_podcasting' , 'ss_podcasting_feed_url' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_include_in_main_query' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_hide_content_meta' );

		// Register data fields
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_title' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_subtitle' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_author' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_category' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_subcategory' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_description' , array( $this , 'validate_description' ) );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_image' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_owner_name' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_owner_email' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_language' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_copyright' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_explicit' );

		// Register redirect settings fields
		register_setting( 'ss_podcasting' , 'ss_podcasting_redirect_feed' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_new_feed_url' );

		// Register protection settings fields
		register_setting( 'ss_podcasting' , 'ss_podcasting_protect_feed' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_protection_username' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_protection_password' , array( $this , 'encode_password' ) );
		register_setting( 'ss_podcasting' , 'ss_podcasting_protection_no_access_message' , array( $this , 'validate_message' ) );

		// Allow plugins to add more settings fields
		do_action( 'ss_podcasting_settings_fields' );

	}

	public function main_settings() { echo '<p>' . __( 'These are a few simple settings to make your podcast work the way you want it to work.' , 'ss-podcasting' ) . '</p>'; }

	public function podcast_data() { echo '<p>' . sprintf( __( 'This data will be used in the feed for your podcast so your listeners will know more about it before they subscribe.%sAll of these fields are optional, but it is recommended that you fill in as many of them as possible. Blank fields will use the assigned defaults in the feed.' , 'ss-podcasting' ) , '<br/><em>' ) . '</em></p>'; }

	public function feed_info() { echo '<p>' . __( 'Use these URLs to share and publish your podcast feed. These URLs will work with any podcasting service (including iTunes).' , 'ss-podcasting' ) . '</p>'; }

	public function redirect_settings() { echo '<p>' . __( 'Use these settings to safely move your podcast to a different location. Only do this once your new podcast is setup and active.' , 'ss-podcasting' ) . '</p>'; }

	public function protection_settings() { echo '<p>' . __( 'Change these settings to ensure that your podcast feed remains private. This will block feed readers (including iTunes) from accessing your feed.' , 'ss-podcasting' ) . '</p>'; }

	public function use_templates_field() {

		$option = get_option('ss_podcasting_use_templates');

		$checked = '';
		if( $option && $option == 'on' ){
			$checked = 'checked="checked"';
		}

		echo '<input id="use_templates" type="checkbox" name="ss_podcasting_use_templates" ' . $checked . '/>
				<label for="use_templates"><span class="description">' . sprintf( __( 'Select this to use the built-in templates for the podcast archive and single pages. If you leave this disabled then your theme\'s default post templates will be used unless you %1$screate your own%2$s' , 'ss-podcasting' ) , '<a href="' . esc_url( 'http://codex.wordpress.org/Post_Type_Templates' ) . '" target="' . esc_attr( '_blank' ) . '">' , '</a>' ) . '.</span></label>';
	}

	public function slug_field() {

		$option = get_option('ss_podcasting_slug');

		$data = 'podcast';
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		$default_url = $this->home_url . '?post_type=podcast';

		echo '<input id="slug" type="text" name="ss_podcasting_slug" value="' . $data . '"/>
				<label for="slug"><span class="description">' . sprintf( __( 'Provide a custom URL slug for the podcast archive and single pages. You must re-save your %1$spermalinks%2$s after changing this setting. No matter what you put here your podcast will always be visible at %3$s.' , 'ss-podcasting' ) , '<a href="' . esc_attr( 'options-permalink.php' ) . '">' , '</a>' , '<a href="' . esc_url( $default_url ) . '">' . $default_url . '</a>' ) . '</span></label>';
	}

	public function validate_slug( $slug ) {
		if( $slug && strlen( $slug ) > 0 && $slug != '' ) {
			$slug = urlencode( strtolower( str_replace( ' ' , '-' , $slug ) ) );
		}
		return $slug;
	}

	public function feed_url_field() {

		$option = get_option('ss_podcasting_feed_url');

		$data = '';
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input id="feed_url" type="text" name="ss_podcasting_feed_url" value="' . $data . '"/>
				<label for="feed_url"><span class="description">' . __( 'If you are using Feedburner (or a similar service) to syndicate your podcast feed you can insert the URL here, otherwise this must be left blank.' , 'ss-podcasting' ) . '</span></label>';

	}

	public function include_in_main_query() {

		$option = get_option('ss_podcasting_include_in_main_query');

		$checked = '';
		if( $option && $option == 'on' ){
			$checked = 'checked="checked"';
		}

		echo '<input id="include_in_main_query" type="checkbox" name="ss_podcasting_include_in_main_query" ' . $checked . '/>
				<label for="include_in_main_query"><span class="description">' . __( 'This setting may behave differently in each theme, so test it carefully after activation - it will add the \'podcast\' post type to your site\'s main query so that your podcast episodes appear on your home page along with your blog posts.' , 'ss-podcasting' ) . '</span></label>';
	}

	public function content_meta() {

		$option = get_option('ss_podcasting_hide_content_meta');

		$checked = '';
		if( $option && $option == 'on' ) {
			$checked = 'checked="checked"';
		}

		echo '<input id="content_meta" type="checkbox" name="ss_podcasting_hide_content_meta" ' . $checked . '/>
				<label for="content_meta"><span class="description">' . sprintf( __( 'Select this to %1$shide%1$s the podcast audio player along with the episode data (download link, duration and file size) wherever the full content of the episode is displayed.' , 'ss-podcasting' ), '<em>', '</em>' ) . '</span></label>';
	}

	public function redirect_feed() {

		$option = get_option('ss_podcasting_redirect_feed');

		$data = '';
		if( $option && $option == 'on' ) {
			$data = $option;
		}

		echo '<input id="redirect_feed" type="checkbox" name="ss_podcasting_redirect_feed" ' . checked( 'on' , $data , false ) . ' />
				<label for="redirect_feed"><span class="description">' . sprintf( __( 'Redirect your feed to a new URL (specified below).%1$sThis will inform all podcasting services that your podcast has moved and 48 hours after you have saved this option it will permanently redirect your feed to the new URL.' , 'ss-podcasting' ) , '<br/>' ) . '</span></label>';

	}

	public function mark_feed_redirect_date( $option , $old_value , $new_value ) {

		if( $option == 'ss_podcasting_redirect_feed' ) {
			if( $new_value && $new_value == 'on' ) {
				$date = time();
				update_option( 'ss_podcasting_redirect_feed_date' , $date );
			}
		}

	}

	public function new_feed_url() {

		$option = get_option('ss_podcasting_new_feed_url');

		$data = '';
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input id="new_feed_url" type="text" name="ss_podcasting_new_feed_url" value="' . $data . '"/>
				<label for="new_feed_url"><span class="description">' . __( 'Your podcast feed\'s new URL.' , 'ss-podcasting' ) . '</span></label>';
	}

	public function data_title() {

		$option = get_option('ss_podcasting_data_title');

		$data = get_bloginfo( 'name' );
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input id="data_title" type="text" name="ss_podcasting_data_title" value="' . $data . '"/>
				<label for="data_title"><span class="description">' . __( 'Your podcast title - defaults to your site\'s name.' , 'ss-podcasting' ) . '</span></label>';
	}

	public function data_subtitle() {

		$option = get_option('ss_podcasting_data_subtitle');

		$data = get_bloginfo( 'description' );
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input id="data_subtitle" type="text" name="ss_podcasting_data_subtitle" value="' . $data . '"/>
				<label for="data_subtitle"><span class="description">' . __( 'Your podcast subtitle - defaults to your site\'s tag line.' , 'ss-podcasting' ) . '</span></label>';
	}

	public function data_author() {
		global $current_user;
		wp_get_current_user();

		$option = get_option('ss_podcasting_data_author');

		$data = get_bloginfo( 'name' );
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input id="data_author" type="text" name="ss_podcasting_data_author" value="' . $data . '"/>
				<label for="data_author"><span class="description">' . __( 'Your podcast author - defaults to your site\'s name.' , 'ss-podcasting' ) . '</span></label>';
	}

	public function data_category() {

		$option = get_option('ss_podcasting_data_category');

		$data = '';
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input id="data_category" type="text" name="ss_podcasting_data_category" value="' . $data . '"/>
				<label for="data_category"><span class="description">' . sprintf( __( 'Your podcast\'s category - use one of the first-tier categories from %1$sthis list%2$s.' , 'ss-podcasting' ) , '<a href="' . esc_url( 'http://www.apple.com/itunes/podcasts/specs.html#categories' ) . '" target="' . esc_attr( '_blank' ) . '">' , '</a>' ) . '</span></label>';
	}

	public function data_subcategory() {

		$option = get_option('ss_podcasting_data_subcategory');

		$data = '';
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input id="data_subcategory" type="text" name="ss_podcasting_data_subcategory" value="' . $data . '"/>
				<label for="data_subcategory"><span class="description">' . sprintf( __( 'Your podcast\'s sub-category - use one of the second-tier categories from %1$sthis list%2$s (must be a sub-category of your selected primary category).' , 'ss-podcasting' ) , '<a href="' . esc_url( 'http://www.apple.com/itunes/podcasts/specs.html#categories' ) . '" target="' . esc_attr( '_blank' ) . '">' , '</a>' ) . '</span></label>';
	}

	public function data_description() {

		$option = get_option('ss_podcasting_data_description');

		$data = get_bloginfo( 'description' );
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<textarea id="data_description" rows="5" cols="50" name="ss_podcasting_data_description">' . $data . '</textarea><br/>
				<label for="data_description"><span class="description">' . __( 'A description/summary of your podcast - defaults to your site\'s tag line. No HTML allowed.' , 'ss-podcasting' ) . '</span></label>';
	}

	public function validate_description( $description ) {

		if( $description && strlen( $description ) > 0 && $description != '' ) {
			$description = strip_tags( $description );
		}

		return $description;
	}

	public function data_image() {

		$option = get_option('ss_podcasting_data_image');

		$data = '';
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<img id="ss_podcasting_data_image_preview" src="' . $data . '" style="max-width:400px;height:auto;" /><br/>
				<input id="ss_podcasting_data_image_button" type="button" class="button" value="'. __( 'Upload new image' , 'ss-podcasting' ) . '" />
			  	<input id="ss_podcasting_delete_image" type="button" class="button" value="'. __( 'Remove image' , 'ss-podcasting' ) . '" />
				<input id="ss_podcasting_data_image" type="hidden" name="ss_podcasting_data_image" value="' . $data . '"/>
				<br/><span class="description">'. __( 'Your primary podcast image. iTunes now requires that this image has a minimum size of 1400x1400 px. Image preview shown here will be smaller than actual image size.' , 'ss-podcasting' ) . '</span>';
	}

	public function data_owner() {
		global $current_user;
		wp_get_current_user();

		$name_option = get_option('ss_podcasting_data_owner_name');
		$email_option = get_option('ss_podcasting_data_owner_email');

		$name = get_bloginfo( 'name' );
		if( $name_option && strlen( $name_option ) > 0 && $name_option != '' ) {
			$name = $name_option;
		}

		$email = get_bloginfo( 'admin_email' );
		if( $email_option && strlen( $email_option ) > 0 && $email_option != '' ) {
			$email = $email_option;
		}

		echo '<p>Name: <input id="data_owner_name" type="text" name="ss_podcasting_data_owner_name" value="' . $name . '"/>
				<label for="data_owner_name"><span class="description">' . __( 'Podcast owner name - defaults to your site\'s name.' , 'ss-podcasting' ) . '</span></label></p>
			  <p>Email address: <input id="data_owner_email" type="text" name="ss_podcasting_data_owner_email" value="' . $email . '"/>
				<label for="data_owner_email"><span class="description">' . __( 'Podcast owner email address - defaults to site\'s admin email address.' , 'ss-podcasting' ) . '</span></label></p>';
	}

	public function data_language() {

		$option = get_option('ss_podcasting_data_language');

		$data = get_bloginfo( 'language' );
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input id="data_language" type="text" name="ss_podcasting_data_language" value="' . $data . '"/>
				<label for="data_language"><span class="description">' . sprintf( __( 'Your site\'s language in %1$sISO-639-1 format%2$s - defaults to your site\'s language setting.' , 'ss-podcasting' ) , '<a href="' . esc_url( 'http://www.loc.gov/standards/iso639-2/php/code_list.php' ) . '" target="' . esc_attr( '_blank' ) . '">' , '</a>' ) . '</span></label>';
	}

	public function data_copyright() {

		$option = get_option('ss_podcasting_data_copyright');

		$data = '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' );
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input id="data_copyright" type="text" name="ss_podcasting_data_copyright" value="' . $data . '"/>
				<label for="data_copyright"><span class="description">' . __( 'Copyright line for your podcast - defaults to the current year with you your site\'s name.' , 'ss-podcasting' ) . '</span></label>';
	}

	public function data_explicit() {

		$option = get_option('ss_podcasting_data_explicit');

		$data = '';
		if( $option && $option == 'on' ) {
			$data = $option;
		}

		echo '<input id="data_explicit" type="checkbox" name="ss_podcasting_data_explicit" ' . checked( 'on' , $data , false ) . ' />
				<label for="data_explicit"><span class="description">' . __( 'Mark if your podcast is explicit or not - defaults to \'No\'.' , 'ss-podcasting' ) . '</span></label>';

	}

	public function protect_feed() {

		$option = get_option('ss_podcasting_protect_feed');

		$data = '';
		if( $option && $option == 'on' ) {
			$data = $option;
		}

		echo '<input id="protect_feed" type="checkbox" name="ss_podcasting_protect_feed" ' . checked( 'on' , $data , false ) . ' />
				<label for="protect_feed"><span class="description">' . __( 'Mark if you would like to password protect your podcast feed - you can set the username and password below.' , 'ss-podcasting' ) . '</span></label>';

	}

	public function protection_username() {

		$option = get_option('ss_podcasting_protection_username');

		$data = '';
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input id="protection_username" type="text" name="ss_podcasting_protection_username" value="' . $data . '"/>
				<label for="protection_username"><span class="description">' . __( 'Login username for your podcast feed.' , 'ss-podcasting' ) . '</span></label>';

	}

	public function protection_password() {

		echo '<input id="protection_password" type="text" name="ss_podcasting_protection_password" value=""/>
				<label for="protection_password"><span class="description">' . __( 'Login password for your podcast feed. Once saved, the password is encoded and secured so it will not be visible on this page again. If you leave this field blank than the password will not be updated.' , 'ss-podcasting' ) . '</span></label>';

	}

	public function encode_password( $password ) {

		$option = get_option('ss_podcasting_protection_password');

		if( $password && strlen( $password ) > 0 && $password != '' ) {
			$password = md5( $password );
		} else {
			$password = $option;
		}

		return $password;
	}

	public function protection_no_access_message() {

		$option = get_option('ss_podcasting_protection_no_access_message');

		$data = __( 'You are not permitted to view this podcast feed.' , 'ss-podcasting' );
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<textarea rows="2" cols="50" id="protection_no_access_message" name="ss_podcasting_protection_no_access_message">' . $data . '</textarea><br/>
				<label for="protection_no_access_message"><span class="description">' . __( 'This will be the message displayed to people who are not allowed access to your podcast feed. Limited HTML allowed.' , 'ss-podcasting' ) . '</span></label>';

	}

	public function validate_message( $message ) {

		if( $message && strlen( $message ) > 0 && $message != '' ) {

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

			$message = wp_kses( $message , $allowed );
		}

		return $message;
	}

	public function feed_standard() {
		$rss_url = $this->home_url . '?feed=podcast';
		echo $rss_url;
	}

	public function feed_standard_series() {
		$rss_url = $this->home_url . '?feed=podcast&podcast_series=series-slug';
		echo $rss_url;
	}

	public function podcast_url() {

		$podcast_url = $this->home_url;

		$slug = get_option('ss_podcasting_slug');
		if( $slug && strlen( $slug ) > 0 && $slug != '' ) {
			$podcast_url .= $slug;
		} else {
			$podcast_url .= '?post_type=podcast';
		}

		echo '<a href="' . esc_url( $podcast_url ) . '" target="_blank">' . $podcast_url . '</a>';

	}

	public function social_sharing() {

		$share_url = $this->home_url;
		$custom_title = get_option('ss_podcasting_data_title');
		$share_title = sprintf( __( 'Podcast on %s', 'ss-podcasting' ), get_bloginfo( 'name' ) );
		if( $custom_title && strlen( $custom_title ) > 0 && $custom_title != '' ) {
			$share_title = $custom_title;
		}

		$slug = get_option('ss_podcasting_slug');
		if( $slug && strlen( $slug ) > 0 && $slug != '' ) {
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

	public function settings_page() {

		$settings = get_option('ss_podcasting_allow_download');

		echo '<div class="wrap" id="podcast_settings">
				<div class="icon32" id="ss_podcasting-icon"><br/></div>
				<h2>' . __( 'Podcast Settings' , 'ss-podcasting' ) . '</h2>
				<form method="post" action="options.php" enctype="multipart/form-data">
					<ul id="settings-sections" class="subsubsub hide-if-no-js">
						<li><a class="tab all current" href="#all">All</a> |</li>
						<li><a class="tab" href="#customise">' . __( 'Customise' , 'ss-podcasting' ) . '</a> |</li>
						<li><a class="tab" href="#describe">' . __( 'Describe' , 'ss-podcasting' ) . '</a> |</li>
						<li><a class="tab" href="#protect">' . __( 'Protect' , 'ss-podcasting' ) . '</a> |</li>
						<li><a class="tab" href="#redirect">' . __( 'Redirect' , 'ss-podcasting' ) . '</a> |</li>
						<li><a class="tab" href="#share">' . __( 'Share' , 'ss-podcasting' ) . '</a></li>
					</ul>
					<div class="clear"></div>';

				settings_fields( 'ss_podcasting' );
				do_settings_sections( 'ss_podcasting' );

			  echo '<p class="submit">
						<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'ss-podcasting' ) ) . '" />
					</p>
				</form>
			  </div>';
	}

}