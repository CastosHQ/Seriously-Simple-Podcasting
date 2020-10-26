<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor;

use Elementor\TemplateLibrary\Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if Elementor not installed and activated
if ( ! did_action( 'elementor/loaded' ) ) {
	return false;
}

/**
 * Template Importer for Elementor
 *
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Classes
 * @since       1.19.18
 */
class Elementor_Template_Importer {

	protected $template_path = SSP_PLUGIN_PATH . 'templates/elementor/';

	protected $template_library_manager;

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action( 'init', array( $this, 'process_template_import' ) );
	}

	public function process_template_import() {
		if ( ! is_admin() ) {
			return;
		}
		if ( ! isset( $_GET['import_template_nonce'] ) || ! wp_verify_nonce( $_GET['import_template_nonce'], '' ) ) {
			return;
		}
		// @todo add user caps checks
		if ( ! isset( $_GET['elementor_import_templates'] ) ) {
			return;
		}
		if ( 'true' != $_GET['elementor_import_templates'] ) {
			return;
		}

		$elementor_template_files          = glob( $this->template_path . '*.json', GLOB_NOSORT );
		$templates_for_import              = array();
		$ss_podcasting_elementor_templates = get_option( 'ss_podcasting_elementor_templates', array() );
		foreach ( $elementor_template_files as $elementor_template_file ) {
			$elementor_template_file_name = basename( $elementor_template_file );
			if ( ! in_array( $elementor_template_file_name, $ss_podcasting_elementor_templates ) ) {
				$ss_podcasting_elementor_templates[] = $elementor_template_file_name;
				$templates_for_import[]              = $elementor_template_file_name;
			}
		}
		update_option( 'ss_podcasting_elementor_templates', $ss_podcasting_elementor_templates );

		return $this->import_template( $templates_for_import );
	}

	public function import_template( $templates_for_import ) {
		$this->template_library_manager = new Manager();
		$source = $this->template_library_manager->get_source( 'local' );
		if ( ! empty( $templates_for_import ) ) {
			foreach ( $templates_for_import as $file_name ) {
				$source->import_template( $file_name, $this->template_path . $file_name );
			}
			add_action( 'admin_notices', array( $this, 'templates_imported_notice' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'no_new_templates_to_import' ) );
		}
	}

	public function templates_imported_notice() {
		$template_link = admin_url( 'edit.php?post_type=elementor_library&tabs_group=library' );
		// @todo convert this into a translatable and escapable notice
		$message       = '
			<div class="notice notice-success is-dismissible">
          		<p>Great Job, the Elementor templates have been imported. You can view the list of templates in your <a href="' . $template_link . '">Elementor Template Library</a>.</p>
         	</div>';
		echo $message;
	}

	public function no_new_templates_to_import() {
		$class   = 'notice notice-success is-dismissible';
		$message = __( 'There are no new templates to be imported!', 'seriously-simple-podcasting' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public function nonce_admin_message() {
		$class   = 'notice notice-error';
		$message = __( 'Irks! Request validation error.', 'seriously-simple-podcasting' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

}
