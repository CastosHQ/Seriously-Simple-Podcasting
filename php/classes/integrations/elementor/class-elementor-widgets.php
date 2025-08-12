<?php
/**
 * Elementor Widgets Integration
 *
 * Handles Elementor widgets integration for Seriously Simple Podcasting.
 *
 * @package Seriously Simple Podcasting
 * @since 2.4.0
 */

namespace SeriouslySimplePodcasting\Integrations\Elementor;

use Elementor\Controls_Manager;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Episode_List_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Recent_Episodes_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Html_Player_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Media_Player_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Subscribe_Buttons_Widget;

/**
 * Elementor Widgets Class
 *
 * Manages Elementor widgets for Seriously Simple Podcasting.
 *
 * @package SeriouslySimplePodcasting\Integrations\Elementor
 * @since 2.4.0
 */
final class Elementor_Widgets {

	/**
	 * Minimum Elementor Version
	 *
	 * @since 2.4
	 *
	 * @var string Minimum Elementor version required to run the plugin.
	 */
	const MINIMUM_ELEMENTOR_VERSION = '2.0.0';

	/**
	 * Minimum PHP Version
	 *
	 * @since 2.4
	 *
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '5.6';

	/**
	 * Template importer instance.
	 *
	 * @var Elementor_Template_Importer
	 */
	protected $template_importer;

	/**
	 * Settings extender instance.
	 *
	 * @var Settings_Extender
	 */
	protected $settings_extender;


	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
	}

	/**
	 * Initialize on plugins loaded.
	 */
	public function on_plugins_loaded() {
		if ( $this->is_compatible() ) {
			add_action( 'elementor/init', array( $this, 'init' ) );
		}
	}

	/**
	 * Check if Elementor is compatible.
	 *
	 * @return bool
	 */
	public function is_compatible() {
		// Check if Elementor installed and activated.
		if ( ! did_action( 'elementor/loaded' ) ) {
			return false;
		}
		// Check for required Elementor version.
		if ( ! version_compare( ELEMENTOR_VERSION, $this::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			return false;
		}
		// Check for required PHP version.
		if ( version_compare( PHP_VERSION, $this::MINIMUM_PHP_VERSION, '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Initialize Elementor widgets.
	 */
	public function init() {
		$this->template_importer = new Elementor_Template_Importer();
		$this->settings_extender = new Settings_Extender();

		add_action( 'elementor/widgets/register', array( $this, 'init_widgets' ) );
	}

	/**
	 * Initialize and register widgets.
	 */
	public function init_widgets() {
		$manager = \Elementor\Plugin::instance()->widgets_manager;
		$manager->register( new Elementor_Media_Player_Widget() );
		$manager->register( new Elementor_Html_Player_Widget() );
		$manager->register( new Elementor_Subscribe_Buttons_Widget() );
		$manager->register( new Elementor_Recent_Episodes_Widget() );
		$manager->register( new Elementor_Episode_List_Widget() );
	}
}
