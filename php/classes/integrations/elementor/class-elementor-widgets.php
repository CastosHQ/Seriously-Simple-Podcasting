<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor;

use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Episode_List_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Recent_Episodes_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Html_Player_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Media_Player_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Subscribe_Buttons_Widget;

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
	const MINIMUM_PHP_VERSION = '7.0';

	protected $template_importer;

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
	}

	public function on_plugins_loaded() {
		if ( $this->is_compatible() ) {
			add_action( 'elementor/init', [ $this, 'init' ] );
		}
	}

	public function is_compatible() {
		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			return false;
		}
		// Check for required Elementor version
		if ( ! version_compare( ELEMENTOR_VERSION, $this::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			return false;
		}
		// Check for required PHP version
		if ( version_compare( PHP_VERSION, $this::MINIMUM_PHP_VERSION, '<' ) ) {
			return false;
		}

		return true;
	}

	public function init() {
		$this->template_importer = new Elementor_Template_Importer();
		add_action( 'elementor/elements/categories_registered', [ $this, 'add_elementor_widget_categories' ] );
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
	}

	public function add_elementor_widget_categories( $elements_manager ) {
		$elements_manager->add_category(
			'podcasting',
			[
				'title' => __( 'Podcasting', 'plugin-name' ),
				'icon'  => 'fa fa-microphone',
			]
		);
	}

	public function init_widgets() {
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Media_Player_Widget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Html_Player_Widget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Subscribe_Buttons_Widget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Recent_Episodes_Widget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Episode_List_Widget() );
		//\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Select_Episode_Widget() );
	}
}
