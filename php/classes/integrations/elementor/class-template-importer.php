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
		// @todo add user caps checks
		// @todo add nonce checking
		$action = ( isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '' );

		if ( empty( $action ) || 'process_template_import' !== sanitize_text_field( $action ) ) {
			return;
		}

		return $this->import_template();
	}

	public function import_template() {
		$source = $this->template_library_manager->get_source( 'local' );
		// @todo replace this with a loop over the templates directory, once we have all the templates.
		return $source->import_template( $this->template_name, $this->template_path . $this->template_name );
	}

}
