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

		$args = array(
			'post_type'  => 'elementor_library',
			'tabs_group' => 'library'
		);

		$existing_templates = get_posts( $args );

		foreach ($existing_templates as $template) {
			echo print_r($existing_templates, true);
		}

		return $this->import_template();
	}

	public function nonce_admin_message() {
		$class   = 'notice notice-error';
		$message = __( 'Irks! Request validation error.', 'sample-text-domain' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );

	}

	public function import_template() {
		$source = $this->template_library_manager->get_source( 'local' );

		// @todo replace this with a loop over the templates directory, once we have all the templates.
		return $source->import_template( $this->template_name, $this->template_path . $this->template_name );
	}

}
