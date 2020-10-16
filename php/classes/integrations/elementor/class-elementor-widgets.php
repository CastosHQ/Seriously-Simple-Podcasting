<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor;

use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Episode_List_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Recent_Episodes_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Html_Player_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Media_Player_Widget;
use SeriouslySimplePodcasting\Integrations\Elementor\Widgets\Elementor_Subscribe_Buttons_Widget;

final class Elementor_Widgets {

	public function __construct() {
		$this->init();
	}

	public function init() {
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
