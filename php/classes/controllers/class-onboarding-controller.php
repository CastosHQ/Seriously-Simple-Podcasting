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

	const STEPS_NUMBER = 5;

	const ONBOARDING_BASE_SLUG = 'ssp-onboarding';

	protected $renderer;

	/**
	 * @var Settings_Handler
	 * */
	protected $settings_handler;

	/**
	 * Onboarding_Controller constructor.
	 *
	 * @param $file
	 * @param $version
	 * @param Renderer $renderer
	 * @param Settings_Handler $settings_handler
	 */
	public function __construct( $file, $version, $renderer, $settings_handler ) {
		parent::__construct( $file, $version );

		$this->renderer         = $renderer;
		$this->settings_handler = $settings_handler;

		add_action( 'admin_menu', array( $this, 'register_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'activated_plugin', array( $this, 'maybe_start_onboarding' ) );
	}

	public function maybe_start_onboarding( $plugin ) {
		if ( $plugin !== plugin_basename( $this->file ) ) {
			return;
		}
		$title = $this->get_field( 'data_title' );
		if ( ! $title ) {
			wp_redirect( admin_url( sprintf( 'admin.php?page=%s-1', self::ONBOARDING_BASE_SLUG ) ) );
			exit();
		}
	}

	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( false !== strpos( $screen->base, self::ONBOARDING_BASE_SLUG ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'ssp-onboarding', esc_url( $this->assets_url . 'admin/js/onboarding' . $this->script_suffix . '.js' ), array(
				'jquery'
			), $this->version );
		}
	}

	public function register_pages() {
		for ( $page_number = 1; $page_number <= self::STEPS_NUMBER; $page_number ++ ) {
			$this->register_page( 'Onboarding wizzard', $this->get_page_slug( $page_number ), array(
				$this,
				sprintf( 'step_%s', $page_number )
			) );
		}
	}

	/**
	 * @param string $title
	 * @param string $slug
	 * @param callable $callable
	 */
	protected function register_page( $title, $slug, $callable ) {
		add_submenu_page( '', __( $title, SSP_DOMAIN ), __( $title, SSP_DOMAIN ), Roles_Handler::MANAGE_PODCAST, $slug, $callable );
	}

	public function step_1() {
		$this->render( $this->get_step_data( 1 ), 'onboarding/step-1' );
	}

	public function step_2() {
		$this->save_step( 1 );
		$this->render( $this->get_step_data( 2 ), 'onboarding/step-2' );
	}

	public function step_3() {
		$this->save_step( 2 );

		$categories    = $this->get_feed_settings( 'data_category' );
		$subcategories = $this->get_feed_settings( 'data_subcategory' );

		$data = compact( 'categories', 'subcategories' );

		$this->render( array_merge( $data, $this->get_step_data( 3 ) ), 'onboarding/step-3' );
	}

	public function step_4() {
		$this->save_step( 3 );
		$this->render( $this->get_step_data( 4 ), 'onboarding/step-4' );
	}

	public function step_5() {
		$this->save_step( 4 );
		$this->render( $this->get_step_data( 5 ), 'onboarding/step-5' );
	}

	/**
	 * @param string $settings_id
	 *
	 * @return array
	 */
	protected function get_feed_settings( $settings_id ) {
		$settings = $this->settings_handler->settings_fields();
		$fields   = $settings['feed-details']['fields'];

		foreach ( $fields as $field ) {
			if ( $settings_id == $field['id'] ) {
				return $field;
			}
		}

		return [];
	}

	/**
	 * @param $step_number
	 *
	 * @return array
	 */
	protected function get_step_data( $step_number ) {

		$data = array(
			'step_number' => $step_number,
		);

		$step_urls = array();
		for ( $page_number = 1; $page_number <= self::STEPS_NUMBER; $page_number ++ ) {
			$step_urls[ $page_number ] = $this->get_step_url( $page_number );
		}
		$data['step_urls'] = $step_urls;

		foreach ( $this->get_step_fields( $step_number ) as $field_name ) {
			$data[ $field_name ] = $this->get_field( $field_name );
		}

		return $data;
	}


	/**
	 * @param int $step_number
	 *
	 * @return string
	 */
	protected function get_step_url( $step_number ) {
		return admin_url( 'admin.php?page=' . $this->get_page_slug( $step_number ) );
	}

	/**
	 * @param $step_number
	 *
	 * @return string[]
	 */
	protected function get_step_fields( $step_number ) {
		$map = $this->get_step_fields_map();

		return $map[ $step_number ];
	}

	/**
	 * @return \string[][]
	 */
	protected function get_step_fields_map() {
		return array(
			1 => array( 'data_title', 'data_description' ),
			2 => array( 'data_image' ),
			3 => array( 'data_category', 'data_subcategory' ),
			4 => array( 'podmotor_account_email', 'podmotor_account_api_token' ),
			5 => array(),
		);
	}

	/**
	 * @param int $step_number
	 */
	protected function save_step( $step_number ) {
		foreach ( $this->get_step_fields( $step_number ) as $field_id ) {
			$val = filter_input( INPUT_POST, $field_id );
			if ( $val ) {
				$this->set_field( $field_id, $val );
			}
		}
	}

	/**
	 * @param int $page_number
	 *
	 * @return string
	 */
	protected function get_page_slug( $page_number ) {
		return sprintf( '%s-%d', self::ONBOARDING_BASE_SLUG, $page_number );
	}

	/**
	 * @param $field_id
	 *
	 * @return false|mixed|void
	 */
	protected function get_field( $field_id ) {
		return $this->settings_handler->get_field( $field_id );
	}

	/**
	 * @param $field_id
	 * @param $value
	 *
	 * @return bool
	 */
	protected function set_field( $field_id, $value ) {
		return $this->settings_handler->set_field( $field_id, $value );
	}

	/**
	 * @param $data
	 * @param $template
	 */
	protected function render( $data, $template ) {
		echo $this->renderer->render( $data, $template );
	}
}
