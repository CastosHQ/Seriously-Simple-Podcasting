<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Entities\Castos_File_Data;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\URL_Helper;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

use WP_Term;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Controller
 *
 * @category    Class
 */
class Admin_Controller {

	use Useful_Variables;

	use URL_Helper;

	/**
	 * @var Renderer
	 */
	public $renderer;

	/**
	 * Admin_Controller constructor.
	 *
	 */
	public function __construct( $renderer ) {
		$this->init_useful_variables();
		$this->register_hooks();

		$this->renderer = $renderer;
	}

	/**
	 * Register all relevant front end hooks and filters
	 */
	public function register_hooks() {
		add_action( 'in_admin_header', [ $this, 'render_ssp_info_section' ]);
	}

	public function render_ssp_info_section(): void {
		if ( ! $this->is_ssp_admin_page() || ! $this->is_ssp_post_page() ) {
			return;
		}

		$this->renderer->render('admin/ssp-info-section');
	}
}
