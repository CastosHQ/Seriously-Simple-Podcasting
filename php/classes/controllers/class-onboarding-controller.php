<?php

namespace SeriouslySimplePodcasting\Controllers;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Handlers\Roles_Handler;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SSP Onboarding Controller
 *
 * @author      Sergey Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       5.7.0
 */
class Onboarding_Controller extends Controller {

	protected $renderer;

	protected $settings_handler;

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );

		//todo: dependency injection or facade
		$this->renderer = new Renderer();
		$this->settings_handler = new Settings_Handler();

		add_action( 'admin_menu', [ $this, 'register_pages' ] );
	}

	public function register_pages() {
		foreach ( [ 1, 2, 3 ] as $page_number ) {
			$this->register_page( 'Onboarding wizzard', $this->get_page_slug( $page_number ), array(
				$this,
				sprintf( 'step_%s', $page_number )
			) );
		}
	}

	protected function register_page( $title, $slug, $callable ) {
		add_submenu_page( '', __( $title, SSP_DOMAIN ), __( $title, SSP_DOMAIN ), Roles_Handler::MANAGE_PODCAST, $slug, $callable );
	}

	public function step_1() {
		$title       = $this->get_field( 'data_title' );
		$description = $this->get_field( 'data_description' );

		$next_step = admin_url( 'admin.php?page=' . $this->get_page_slug( 2 ) );

		$this->render( compact( 'title', 'description', 'next_step' ), 'onboarding/step-1' );
	}

	public function step_2() {
		foreach ( array( 'data_title', 'data_description' ) as $field_id ) {
			$val = filter_input( INPUT_POST, $field_id );
			if ( $val ) {
				$this->set_field( $field_id, $val );
			}
		}

		$next_step = admin_url( 'admin.php?page=' . $this->get_page_slug( 3 ) );

		$this->render( compact( 'next_step' ), 'onboarding/step-2' );
	}

	protected function get_page_slug( $page_number ) {
		return sprintf( 'ssp-onboarding-%d', $page_number );
	}

	protected function get_field( $field_id ){
		return $this->settings_handler->get_field( $field_id );
	}

	protected function set_field( $field_id, $value ){
		return $this->settings_handler->set_field( $field_id, $value );
	}

	protected function render( $data, $template ){
		echo $this->renderer->render( $data, $template );
	}
}
