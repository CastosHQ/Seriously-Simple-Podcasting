<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class SeriouslySimplePodcasting_Admin {
	private $dir;
	private $file;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;

		add_action( 'admin_init' , array( &$this , 'register_settings' ) );
		add_action( 'admin_menu' , array( &$this , 'add_menu_item' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ) , array( &$this , 'add_settings_link' ) );

	}

	public function add_menu_item() {
		add_options_page('Seriously Simple Podcasting', 'Podcast', 'manage_options', 'ss_podcasting', array( &$this , 'settings_page' ) );
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=ss_podcasting">Settings</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	public function register_settings() {
		
		//Add settings section
		add_settings_section( 'main_settings' , 'A few simple settings to customize your podcast:' , array( &$this , 'main_settings' ) , 'ss_podcasting' );

		//Add settings fields
		add_settings_field( 'ss_podcasting_use_templates' , 'Use built-in plugin templates:' , array( &$this , 'use_templates_field' )  , 'ss_podcasting' , 'main_settings' );
		add_settings_field( 'ss_podcasting_slug' , 'URL slug for podcast pages:' , array( &$this , 'slug_field' )  , 'ss_podcasting' , 'main_settings' );

		//Register settings fields
		register_setting( 'ss_podcasting' , 'ss_podcasting_use_templates' );
		register_setting( 'ss_podcasting' , 'ss_podcasting_slug' , array( &$this , 'validate_slug' ) );

	}

	public function main_settings() {}

	public function use_templates_field() {

		$option = get_option('ss_podcasting_use_templates');

		$checked = '';
		if( $option && $option == 'on' ){
			$checked = 'checked="checked"';
		}

		echo '<input id="use_templates" type="checkbox" name="ss_podcasting_use_templates" ' . $checked . '/>
				<label for="use_templates"><span class="description">Select this to use the built-in templates for the podcast archive and single pages. If you leave this disabled then your theme\'s default post templates will be used unless you <a href="http://codex.wordpress.org/Post_Type_Templates" target="_blank">create your own</a>.</span></label>';
	}

	public function slug_field() {

		$option = get_option('ss_podcasting_slug');

		$slug = __( 'podcast' , 'ss-podcasting' );
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$slug = $option;
		}

		echo '<input id="slug" type="text" name="ss_podcasting_slug" value="' . $slug . '"/>
				<label for="slug"><span class="description">Provide a custom URL slug for the podcast archive and single pages. You must re-save your <a href="options-permalink.php">permalinks</a> after changing this setting.</span></label>';
	}

	public function validate_slug( $slug ) {
		if( $slug && strlen( $slug ) > 0 && $slug != '' ) {
			$slug = urlencode( strtolower( str_replace( ' ' , '-' , $slug ) ) );
		}
		return $slug;
	}

	public function settings_page() {

		$settings = get_option('ss_podcasting_allow_download');

		echo '<div class="wrap">
				<div class="icon32" id="ss_podcasting-icon"><br/></div>
				<h2>Seriously Simple Podcasting</h2>
				<form method="post" action="options.php" enctype="multipart/form-data">';

				settings_fields( 'ss_podcasting' );
				do_settings_sections( 'ss_podcasting' );

			  echo '<p class="submit">
						<input name="Submit" type="submit" class="button-primary" value="' . esc_attr('Save Settings') . '" />
					</p>
				</form>
			  </div>';
	}

}