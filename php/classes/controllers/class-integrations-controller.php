<?php
/**
 * Integrations controller class file.
 *
 * @package Seriously Simple Podcasting
 */

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;
use SeriouslySimplePodcasting\Integrations\LifterLMS\LifterLMS_Integrator;
use SeriouslySimplePodcasting\Integrations\Memberpress\Memberpress_Integrator;
use SeriouslySimplePodcasting\Integrations\Paid_Memberships_Pro\Paid_Memberships_Pro_Integrator;
use SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrations controller. Controls all membership integrations.
 *
 * @since 3.7.0
 * @package SeriouslySimplePodcasting
 * @author Sergiy Zakharchenko
 */
class Integrations_Controller {

	/**
	 * Feed handler instance.
	 *
	 * @var Feed_Handler
	 */
	protected $feed_handler;

	/**
	 * Castos handler instance.
	 *
	 * @var Castos_Handler
	 */
	protected $castos_handler;

	/**
	 * Admin notifications handler instance.
	 *
	 * @var Admin_Notifications_Handler
	 */
	protected $notices_handler;

	/**
	 * Logger helper instance.
	 *
	 * @var Log_Helper
	 */
	protected $logger;


	/**
	 * Integrations_Controller constructor.
	 *
	 * @param Feed_Handler                $feed_handler    Handler for feed operations.
	 * @param Castos_Handler              $castos_handler  Handler for Castos API interactions.
	 * @param Log_Helper                  $logger          Logger helper instance.
	 * @param Admin_Notifications_Handler $notices_handler Handler for admin notifications.
	 */
	public function __construct( $feed_handler, $castos_handler, $logger, $notices_handler ) {
		$this->feed_handler    = $feed_handler;
		$this->castos_handler  = $castos_handler;
		$this->logger          = $logger;
		$this->notices_handler = $notices_handler;

		$this->init_integrations();

		// Disable private podcast option if integration is enabled.
		add_filter( 'ssp_get_setting', array( $this, 'maybe_disable_private_option' ), 10, 2 );
	}

	/**
	 * Initializes all available integrations.
	 *
	 * @return void
	 */
	public function init_integrations() {
		// Paid Memberships Pro integration.
		Paid_Memberships_Pro_Integrator::instance()->init( $this->feed_handler, $this->castos_handler, $this->logger, $this->notices_handler );

		// Lifter LMS integration.
		LifterLMS_Integrator::instance()->init( $this->feed_handler, $this->castos_handler, $this->logger );

		// MemberPress integration.
		Memberpress_Integrator::instance()->init( $this->feed_handler, $this->castos_handler, $this->logger, $this->notices_handler );

		// Woocommerce Memberships integration.
		WC_Memberships_Integrator::instance()->init( $this->feed_handler, $this->castos_handler, $this->logger, $this->notices_handler );
	}

	/**
	 * Disables the private option if integration is enabled.
	 *
	 * @param mixed $value Setting value.
	 * @param array $data  Setting data.
	 *
	 * @return mixed Modified setting value.
	 */
	public function maybe_disable_private_option( $value, $data ) {
		$option = Settings_Controller::SETTINGS_BASE . 'is_podcast_private';

		if ( false === strpos( $data['option'], $option ) ) {
			return $value;
		}

		return $this->is_any_integration_enabled() ? 'no' : $value;
	}

	/**
	 * Checks if any of the available integrations is enabled.
	 *
	 * @return bool
	 */
	public function is_any_integration_enabled() {
		return Paid_Memberships_Pro_Integrator::integration_enabled() ||
				LifterLMS_Integrator::integration_enabled() ||
				Memberpress_Integrator::integration_enabled() ||
				WC_Memberships_Integrator::integration_enabled();
	}
}
