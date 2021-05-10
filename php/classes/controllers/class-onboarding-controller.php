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
		$this->renderer         = new Renderer();
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
		foreach ( [ 1, 2, 3, 4, 5 ] as $page_number ) {
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
		$steps_data  = $this->get_steps_data( 1 );

		$this->render( array_merge( $steps_data, compact( 'title', 'description' ) ), 'onboarding/step-1' );
	}

	public function step_2() {
		$this->maybe_save_fields( array( 'data_title', 'data_description' ) );

		$steps_data = $this->get_steps_data( 2 );

		$img_url = $this->get_field( 'data_image' );

		$this->render( array_merge( $steps_data, compact( 'img_url' ) ), 'onboarding/step-2' );
	}

	public function step_3() {
		$this->maybe_save_fields( array( 'data_image' ) );

		$steps_data = $this->get_steps_data( 3 );

		$this->render( $steps_data, 'onboarding/step-3' );
	}

	public function step_4() {
		$this->maybe_save_fields( array( 'data_category', 'data_subcategory' ) );

		$steps_data = $this->get_steps_data( 4 );

		$this->render( $steps_data, 'onboarding/step-4' );
	}

	protected function get_steps_data( $step_number ) {
		$next_step = admin_url( 'admin.php?page=' . $this->get_page_slug( $step_number + 1 ) );

		return compact( 'step_number', 'next_step' );
	}

	protected function maybe_save_fields( array $fields ) {
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

	protected function get_field( $field_id ) {
		return $this->settings_handler->get_field( $field_id );
	}

	protected function set_field( $field_id, $value ) {
		return $this->settings_handler->set_field( $field_id, $value );
	}

	protected function render( $data, $template ) {
		echo $this->renderer->render( $data, $template );
	}
}
