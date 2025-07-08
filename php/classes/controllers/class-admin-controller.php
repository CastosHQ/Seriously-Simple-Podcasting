<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Traits\URL_Helper;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

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
	 * @var Castos_Handler
	 */
	private $castos_handler;

	/**
	 * Admin_Controller constructor.
	 *
	 */
	public function __construct( $renderer, $castos_handler ) {
		$this->init_useful_variables();
		$this->register_hooks();

		$this->renderer = $renderer;
		$this->castos_handler = $castos_handler;
	}

	/**
	 * Register all relevant front end hooks and filters
	 */
	public function register_hooks() {
		add_action( 'in_admin_header', [ $this, 'render_ssp_info_section' ]);
		add_action( 'current_screen', [ $this, 'disable_notices' ], 99 );
	}

	/**
	 * Disables redundant notices on SSP pages.
	 *
	 * @since %ver%
	 *
	 * @return void
	 */
	public function disable_notices() {
		if ( ! $this->is_ssp_admin_page() || ! $this->is_ssp_podcast_page() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', function () {
			$this->remove_notice_actions();
		} );
	}

	/**
	 * Remove all admin notices except the priority 12 that is used by SSP.
	 *
	 * @param $except_priority
	 *
	 * @return void
	 */
	protected function remove_notice_actions( $except_priority = 12 ){
		// Remove all admin notices except SSP that uses 12 priority level.
		$priorities = range( 1, 99 );
		foreach ( $priorities as $priority ) {
			if ( $except_priority == $priority ) {
				continue;
			}
			remove_all_actions( 'admin_notices', $priority );
		}
	}

	/**
	 * Checks if this is a podcast page or not.
	 *
	 * @return bool
	 */
	protected function is_ssp_podcast_page() {
		$current_screen = get_current_screen();
		if( ! $current_screen ) {
			return false;
		}

		return in_array( $current_screen->post_type, [ SSP_CPT_PODCAST ] );
	}

	/**
	 * Renders the SSP info section on the admin page.
	 *
	 * @return void
	 */
	public function render_ssp_info_section(): void {
		if ( ! $this->is_ssp_admin_page() || ! $this->is_ssp_post_page() ) {
			return;
		}

		$me = $this->castos_handler->me();
		$plan = $me['plan'] ?? '';

		$this->renderer->render('admin/ssp-info-section', compact('plan'));
	}
}
