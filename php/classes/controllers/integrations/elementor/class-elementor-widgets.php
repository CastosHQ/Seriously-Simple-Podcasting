<?php

namespace SeriouslySimplePodcasting\Controllers\Integrations\Elementor;

use SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets\Elementor_Episode_List_Widget;
use SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets\Elementor_Select_Episode_Widget;
use SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets\Elementor_Html_Player_Widget;
use SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets\Elementor_Media_Player_Widget;
use SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets\Elementor_Subscribe_Buttons;
use SeriouslySimplePodcasting\Integrations\Elementor\Template_Importer;

final class Elementor_Widgets {

	public function __construct() {
		$this->init();
	}

	public function init() {
		// Add Plugin actions
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_template_importer' ] );
	}

	public function init_widgets() {
		// Register widgets
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Media_Player_Widget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Html_Player_Widget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Subscribe_Buttons() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Episode_List_Widget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Select_Episode_Widget() );
	}

	public function init_template_importer() {
		$elementor_template_importer = new Template_Importer();
	}
}
