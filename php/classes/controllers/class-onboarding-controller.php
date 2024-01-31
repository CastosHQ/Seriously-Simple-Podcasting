<?php

namespace SeriouslySimplePodcasting\Controllers;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler;
use SeriouslySimplePodcasting\Handlers\Roles_Handler;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Traits\Useful_Variables;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SSP Onboarding Controller
 *
 * @author      Serhiy Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       5.7.0
 */
class Onboarding_Controller {

	use Useful_Variables;

	const STEPS_NUMBER = 5;

	const ONBOARDING_BASE_SLUG = 'ssp-onboarding';

	/**
	 * @var Renderer
	 */
	protected $renderer;

	/**
	 * @var Settings_Handler
	 * */
	protected $settings_handler;

	/**
	 * Onboarding_Controller constructor.
	 *
	 * @param Renderer $renderer
	 * @param Settings_Handler $settings_handler
	 */
	public function __construct( $renderer, $settings_handler ) {
		$this->renderer         = $renderer;
		$this->settings_handler = $settings_handler;

		$this->init_useful_variables();

		add_action( 'admin_menu', array( $this, 'register_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'activated_plugin', array( $this, 'maybe_start_onboarding' ) );
		add_action( 'admin_init', array( $this, 'fix_deprecated_warning' ) );
	}

	/**
	 * Fix PHP deprecated warning for new WordPress installations on the first onboarding step.
	 * */
	public function fix_deprecated_warning() {
		$page = filter_input(INPUT_GET, 'page');
		if ( $page && ( false !== strpos( $page, self::ONBOARDING_BASE_SLUG ) ) ) {
			global $title;
			if ( ! isset( $title ) ) {
				$title = '';
			}
		}
	}

	public function maybe_start_onboarding( $plugin ) {
		if ( $plugin !== plugin_basename( $this->file ) ) {
			return;
		}
		$title = ssp_get_option( 'data_title', '', ssp_get_default_series_id() );
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
		add_submenu_page( '', __( $title, 'seriously-simple-podcasting' ), __( $title, 'seriously-simple-podcasting' ), Roles_Handler::MANAGE_PODCAST, $slug, $callable );
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
		$series_id = ( 4 === $step_number ) ? 0 : ssp_get_default_series_id();

		foreach ( $this->get_step_fields( $step_number ) as $field_name ) {
			$data[ $field_name ] = ssp_get_option( $field_name, '', $series_id );
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
		$nonce = filter_input( INPUT_POST, 'nonce' );
		if ( ! wp_verify_nonce( $nonce, 'ssp_onboarding_' . $step_number ) ) {
			return;
		}

		$default_series_id = ssp_get_default_series_id();

		$series_id = ( 4 === $step_number ) ? 0 : $default_series_id;
		foreach ( $this->get_step_fields( $step_number ) as $field_id ) {
			$val = filter_input( INPUT_POST, $field_id );
			if ( $val ) {
				ssp_update_option( $field_id, $val, $series_id );
			}
		}

		if( 1 === $step_number ){
			$this->update_default_series_name( $default_series_id );
			ssp_add_option( 'series_slug', CPT_Podcast_Handler::DEFAULT_SERIES_SLUG );
		}
	}

	/**
	 * @param int $series_id
	 *
	 * @return array|WP_Error
	 */
	protected function update_default_series_name( $series_id ) {
		$series = get_term_by( 'id', $series_id, ssp_series_taxonomy() );
		$name   = ssp_get_option( 'data_title', get_bloginfo('name'), $series_id );
		$slug   = wp_unique_term_slug( sanitize_title( $name ), $series );
		return wp_update_term( $series_id, ssp_series_taxonomy(), array(
			'name' => $name,
			'slug' => $slug,
		) );
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
	 * @param $data
	 * @param $template
	 */
	protected function render( $data, $template ) {
		echo $this->renderer->render_deprecated( $data, $template );
	}
}
