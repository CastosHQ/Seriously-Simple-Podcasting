<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor;

use Elementor\TemplateLibrary\Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template Importer for Elementor
 *
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Classes
 * @since       1.19.18
 */
class Template_Importer {

	protected $template_name = 'ssp-elementor-template-2020-09-30.json';
	protected $template_path = SSP_PLUGIN_PATH . 'templates/elementor/';

	protected $template_library_manager;

	public function __construct() {
		$this->template_library_manager = new Manager();
		$this->init();
	}

	public function init() {
		add_action( 'init', array( $this, 'process_template_import' ) );
	}

	public function process_template_import() {
		// @todo add is_admin checking

		if ( ! is_admin() ) {
			return;
		}

		// @todo add user caps checks
		// @todo add nonce checking
		if ( ! isset( $_GET['elementor_import_templates'] ) ) {
			return;
		}

		if ( ! $_GET['elementor_import_templates'] == true ) {
			return;
		}

		if ( $_GET['tab'] != 'extensions' ) {
			return;
		}

		// verify template import nonce
		if ( ! isset( $_GET['import_template_nonce'] ) || ! wp_verify_nonce( $_GET['import_template_nonce'], '' ) ) {
			add_action( 'admin_notices', array( $this, 'nonce_admin_message' ) );

			return;
		}

		$elementor_template_files      = glob( $this->template_path . "*.json" );
		$elementor_template_file_names = array();
		if ( ! empty( $elementor_template_files ) ) {
			$i = 0;
			foreach ( $elementor_template_files as $file ) {
				$elementor_template_file_names[ $i ] = substr( $file, strrpos( $file, '/' ) + 1 );
				$i ++;
			}
		}

		$elementor_template_options = get_option( 'ss_podcasting_elementor_templates' );
		if ( $elementor_template_options == false ) {
			$elementor_template_options = array();
			add_option( 'ss_podcasting_elementor_templates', $elementor_template_options );
		}

		$templates_for_import = array();
		if ( ! empty( $elementor_template_file_names ) ) {
			foreach ( $elementor_template_file_names as $template ) {
				if ( ! in_array( $template, $elementor_template_options ) ) {
					$templates_for_import[] = $elementor_template_options[] = $template;
				}
			}
		}

		update_option( 'ss_podcasting_elementor_templates', $elementor_template_options );

		return $this->import_template( $templates_for_import );
	}

	public function import_template( $templates_for_import ) {
		$source = $this->template_library_manager->get_source( 'local' );

		// @todo replace this with a loop over the templates directory, once we have all the templates.
		if ( ! empty( $templates_for_import ) ) {
			foreach ( $templates_for_import as $file_name ) {
				$template = $source->import_template( $file_name, $this->template_path . $file_name );
			}
			add_action( 'admin_notices', array( $this, 'templates_imported_notice' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'no_new_templates_to_import' ) );
		}
	}

	public function templates_imported_notice() {
		$class   = 'notice notice-success is-dismissible';
		$message = __( 'Great job! Templates imported!', 'sample-text-domain' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public function no_new_templates_to_import() {
		$class   = 'notice notice-success is-dismissible';
		$message = __( "There are no new templates to be imported!", 'sample-text-domain' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public function nonce_admin_message() {
		$class   = 'notice notice-error';
		$message = __( 'Irks! Request validation error.', 'sample-text-domain' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );

	}

}
