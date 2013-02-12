<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class SeriouslySimplePodcasting_Admin {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $site_url;
	private $token;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->site_url = trailingslashit( site_url() );
		$this->token = 'podcast';

		// Register podcast settings
		add_action( 'admin_init' , array( &$this , 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( &$this , 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ) , array( &$this , 'add_settings_link' ) );

		// Load scripts for settings page
		global $pagenow;
		if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == $this->token ) {
			add_action( 'admin_enqueue_scripts' , array( &$this, 'enqueue_admin_scripts' ) , 10 );
		}

		// Mark date on which feed redirection was activated
		add_action( 'update_option' , array( &$this, 'mark_feed_redirect_date' ) , 10 , 3 );

	}

	public function add_menu_item() {
		add_submenu_page( 'edit.php?post_type=podcast' , 'Podcast Settings' , 'Settings', 'manage_options' , 'settings' , array( &$this , 'settings_page' ) );
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="edit.php?post_type=podcast&page=settings">Settings</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	public function enqueue_admin_scripts () {

		// Admin JS
		wp_register_script( 'ss_podcasting-admin', esc_url( $this->assets_url . 'js/admin.js' ), array( 'jquery' , 'media-upload' , 'thickbox' ), '1.1' );

		// JS & CSS for media uploader
		wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'thickbox' );
        wp_enqueue_style( 'thickbox' );
        wp_enqueue_script( 'media-upload' );
        wp_enqueue_script( 'ss_podcasting-admin' );

	}

	public function register_settings() {
		
		// Add settings section
		add_settings_section( 'main_settings' , __( 'Customise your podcast' , 'ss-podcasting' ) , array( &$this , 'main_settings' ) , 'ss_podcasting' );
		add_settings_section( 'podcast_data' , __( 'Describe your podcast' , 'ss-podcasting' ) , array( &$this , 'podcast_data' ) , 'ss_podcasting' );
		add_settings_section( 'feed_info' , __( 'Share your podcast' , 'ss-podcasting' ) , array( &$this , 'feed_info' ) , 'ss_podcasting' );
		add_settings_section( 'redirect_settings' , __( 'Redirect your podcast' , 'ss-podcasting' ) , array( &$this , 'redirect_settings' ) , 'ss_podcasting' );

		// Add settings fields
		add_settings_field( 'ss_podcasting_use_templates' , __( 'Use built-in plugin templates:' , 'ss-podcasting' ) , array( &$this , 'use_templates_field' )  , 'ss_podcasting' , 'main_settings' );
		add_settings_field( 'ss_podcasting_slug' , __( 'URL slug for podcast pages:' , 'ss-podcasting' ) , array( &$this , 'slug_field' )  , 'ss_podcasting' , 'main_settings' );

		// Add data fields
		add_settings_field( 'ss_podcasting_data_title' , __( 'Title:' , 'ss-podcasting' ) , array( &$this , 'data_title' )  , 'ss_podcasting' , 'podcast_data' );
		add_settings_field( 'ss_podcasting_data_subtitle' , __( 'Subtitle:' , 'ss-podcasting' ) , array( &$this , 'data_subtitle' )  , 'ss_podcasting' , 'podcast_data' );
		add_settings_field( 'ss_podcasting_data_author' , __( 'Author:' , 'ss-podcasting' ) , array( &$this , 'data_author' )  , 'ss_podcasting' , 'podcast_data' );
		add_settings_field( 'ss_podcasting_data_category' , __( 'Category:' , 'ss-podcasting' ) , array( &$this , 'data_category' )  , 'ss_podcasting' , 'podcast_data' );
		add_settings_field( 'ss_podcasting_data_subcategory' , __( 'Sub-Category:' , 'ss-podcasting' ) , array( &$this , 'data_subcategory' )  , 'ss_podcasting' , 'podcast_data' );
		add_settings_field( 'ss_podcasting_data_description' , __( 'Description/Summary:' , 'ss-podcasting' ) , array( &$this , 'data_description' )  , 'ss_podcasting' , 'podcast_data' );
		add_settings_field( 'ss_podcasting_data_image' , __( 'Image:' , 'ss-podcasting' ) , array( &$this , 'data_image' )  , 'ss_podcasting' , 'podcast_data' );
		add_settings_field( 'ss_podcasting_data_owner_name' , __( 'Owner:' , 'ss-podcasting' ) , array( &$this , 'data_owner' )  , 'ss_podcasting' , 'podcast_data' );
		add_settings_field( 'ss_podcasting_data_language' , __( 'Language:' , 'ss-podcasting' ) , array( &$this , 'data_language' )  , 'ss_podcasting' , 'podcast_data' );
		add_settings_field( 'ss_podcasting_data_copyright' , __( 'Copyright:' , 'ss-podcasting' ) , array( &$this , 'data_copyright' )  , 'ss_podcasting' , 'podcast_data' );
		add_settings_field( 'ss_podcasting_data_explicit' , __( 'Explicit:' , 'ss-podcasting' ) , array( &$this , 'data_explicit' )  , 'ss_podcasting' , 'podcast_data' );

		// Add feed info fields
		add_settings_field( 'ss_podcasting_feed_standard' , __( 'Standard RSS feed:' , 'ss-podcasting' ) , array( &$this , 'feed_standard' )  , 'ss_podcasting' , 'feed_info' );
		add_settings_field( 'ss_podcasting_feed_standard_series' , __( 'Standard RSS feed (specific series):' , 'ss-podcasting' ) , array( &$this , 'feed_standard_series' )  , 'ss_podcasting' , 'feed_info' );
		add_settings_field( 'ss_podcasting_feed_itunes' , __( 'iTunes feed:' , 'ss-podcasting' ) , array( &$this , 'feed_itunes' )  , 'ss_podcasting' , 'feed_info' );
		add_settings_field( 'ss_podcasting_feed_itunes_series' , __( 'iTunes feed (specific series):' , 'ss-podcasting' ) , array( &$this , 'feed_itunes_series' )  , 'ss_podcasting' , 'feed_info' );

		// Add redirect settings fields
		add_settings_field( 'ss_podcasting_redirect_feed' , __( 'Redirect podcast feed to new URL:' , 'ss-podcasting' ) , array( &$this , 'redirect_feed' )  , 'ss_podcasting' , 'redirect_settings' );
		add_settings_field( 'ss_podcasting_new_feed_url' , __( 'New podcast URL:' , 'ss-podcasting' ) , array( &$this , 'new_feed_url' )  , 'ss_podcasting' , 'redirect_settings' );

		// Register settings fields
		register_setting( 'ss_podcasting' , 'ss_podcasting_use_templates' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_slug' , array( &$this , 'validate_slug' ) );

		// Register data fields
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_title' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_subtitle' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_author' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_category' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_subcategory' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_description' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_image' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_owner_name' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_owner_email' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_language' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_copyright' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_data_explicit' );

		// Register redirect settings fields
		register_setting( 'ss_podcasting' , 'ss_podcasting_redirect_feed' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_new_feed_url' );

	}

	public function main_settings() { echo '<p>' . __( 'These are a few simple settings to make your podcast work the way you want it to work.' , 'ss-podcasting' ) . '</p>'; }

	public function podcast_data() { echo '<p>' . sprintf( __( 'This data will be used in the RSS feed for your podcast so your listeners will know more about it before they subscribe.%sAll of these fields are optional, but it is recommended that you fill in as many of them as possible. Blank fields will use the assigned defaults in the feed.' , 'ss-podcasting' ) , '<br/><em>' ) . '</em></p>'; }

	public function feed_info() { echo '<p>' . __( 'Use these URLs to share and publish your podcast RSS feed. If you are submitting your podcast to iTunes make sure to use the correct URL.' , 'ss-podcasting' ) . '</p>'; }

	public function redirect_settings() { echo '<p>' . sprintf( __( 'Use these settings to safely move your podcast to a different location. Only do this once your new podcast is setup and active.%sThis is also useful if you syndicate your podcast through a third-party service (such as Feedburner).' , 'ss-podcasting' ) , '<br/><em>' ) . '</em></p>'; }

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

		$slug = 'podcast';
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$slug = $option;
		}

		echo '<input id="slug" type="text" name="ss_podcasting_slug" value="' . $slug . '"/>
				<label for="slug"><span class="description">' . sprintf( __( 'Provide a custom URL slug for the podcast archive and single pages. You must re-save your %1$spermalinks%2$s after changing this setting.' , 'ss-podcasting' ) , '<a href="' . esc_attr( 'options-permalink.php' ) . '">' , '</a>' ) . '</span></label>';
	}

	public function validate_slug( $slug ) {
		if( $slug && strlen( $slug ) > 0 && $slug != '' ) {
			$slug = urlencode( strtolower( str_replace( ' ' , '-' , $slug ) ) );
		}
		return $slug;
	}

	public function redirect_feed() {

		$option = get_option('ss_podcasting_redirect_feed');

		$data = '';
		if( $option && $option == 'on' ) {
			$data = $option;
		}

		echo '<input id="redirect_feed" type="checkbox" name="ss_podcasting_redirect_feed" ' . checked( 'on' , $data , false ) . ' />
				<label for="redirect_feed"><span class="description">' . sprintf( __( 'Redirect your feed to a new URL (specified below).%1$sThis will inform iTunes that your podcast has moved and in 48 hours from the time that you save this option it will permanently redirect your iTunes feed to the new URL (as per iTunes\' requirements). Your standard RSS feed address will be redirected immediately.' , 'ss-podcasting' ) , '<br/>' ) . '</span></label>';

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
				<label for="data_description"><span class="description">' . __( 'A description/summary of your podcast - defaults to your site\'s tag line.' , 'ss-podcasting' ) . '</span></label>';
	}
	
	public function data_image() {

		$option = get_option('ss_podcasting_data_image');

		$data = '';
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<img id="ss_podcasting_data_image_preview" src="' . $data . '" /><br/>
				<input id="ss_podcasting_upload_image" type="button" class="button" value="'. __( 'Upload new image' , 'ss-podcasting' ) . '" />
			  	<input id="ss_podcasting_delete_image" type="button" class="button" value="'. __( 'Remove image' , 'ss-podcasting' ) . '" />
				<input id="ss_podcasting_data_image" type="hidden" name="ss_podcasting_data_image" value="' . $data . '"/>
				<br/><span class="description">'. __( 'Your primary podcast image.' , 'ss-podcasting' ) . '</span>';
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

	public function feed_standard() {
		$rss_url = $this->site_url . '?feed=podcast';
		echo $rss_url;
	}

	public function feed_standard_series() {
		$rss_url = $this->site_url . '?feed=podcast&series=series-slug';
		echo $rss_url;
	}

	public function feed_itunes() {
		$rss_url = $this->site_url . '?feed=itunes';
		echo $rss_url;
	}

	public function feed_itunes_series() {
		$rss_url = $this->site_url . '?feed=itunes&series=series-slug';
		echo $rss_url;
	}

	public function settings_page() {

		$settings = get_option('ss_podcasting_allow_download');

		echo '<div class="wrap">
				<div class="icon32" id="ss_podcasting-icon"><br/></div>
				<h2>Podcast Settings</h2>
				<form method="post" action="options.php" enctype="multipart/form-data">';

				settings_fields( 'ss_podcasting' );
				do_settings_sections( 'ss_podcasting' );

			  echo '<p class="submit">
						<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'ss-podcasting' ) ) . '" />
					</p>
				</form>
			  </div>';
	}

}