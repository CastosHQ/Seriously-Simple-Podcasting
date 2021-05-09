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

		add_action( 'admin_menu', array( $this, 'register_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'ssp-onboarding', esc_url( $this->assets_url . 'admin/js/onboarding' . $this->script_suffix . '.js' ), array(
			'jquery',
		), $this->version );
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
		$step_number = 1;

		$this->render( compact( 'title', 'description', 'next_step', 'step_number' ), 'onboarding/step-1' );
	}

	public function step_2() {
		$this->save_fields( array( 'data_title', 'data_description' ) );

		$next_step = admin_url( 'admin.php?page=' . $this->get_page_slug( 3 ) );
		$img_url = $this->get_field('data_image');
		$step_number = 2;

		$this->render( compact( 'next_step', 'step_number', 'img_url' ), 'onboarding/step-2' );
	}

	public function step_3() {
		$this->save_fields( array( 'data_image' ) );
	}

	protected function save_fields( array $fields ) {
		foreach ( $fields as $field_id ) {
			$val = filter_input( INPUT_POST, $field_id );
			if ( $val ) {
				$this->set_field( $field_id, $val );
			}
		}
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
