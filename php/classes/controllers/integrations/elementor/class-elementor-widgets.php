<?php

namespace SeriouslySimplePodcasting\Controllers\Integrations\Elementor;

use SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets\Elementor_Episode_List_Widget;
use SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets\Elementor_Select_Episode_Widget;
use SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets\Elementor_Html_Player_Widget;
use SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets\Elementor_Media_Player_Widget;
use SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets\Elementor_Subscribe_Links;

final class Elementor_Widgets {

	public function __construct() {
		$this->init();
	}

	public function init() {

		// Add Plugin actions
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
	}

	public function init_widgets() {

		// Include Widget files
		require_once __DIR__ . '/widgets/elementor-media-player-widget.php';
		require_once __DIR__ . '/widgets/elementor-html-player-widget.php';
		require_once __DIR__ . '/widgets/elementor-select-episode-widget.php';
		require_once __DIR__ . '/widgets/elementor-subscriber-links-widget.php';
		require_once __DIR__ . '/widgets/elementor-episode-list-widget.php';

		// Register widget
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Media_Player_Widget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Html_Player_Widget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Select_Episode_Widget() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Subscribe_Links() );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Elementor_Episode_List_Widget() );
	}
}
