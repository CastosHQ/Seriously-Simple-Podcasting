<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use SeriouslySimplePodcasting\Controllers\Episodes_Controller;

class Elementor_Recent_Episodes_Widget extends \Elementor\Widget_Base {
	public function get_name() {
		return 'Recent Episodes';
	}

	public function get_title() {
		return __( 'Recent Episodes', 'seriously-simple-podcasting' );
	}

	public function get_icon() {
		return 'fa fa-broadcast-tower';
	}

	public function get_categories() {
		return [ 'podcasting' ];
	}

	protected function render() {
		$settings          = $this->get_settings_for_display();
		$episodes_controller = new Episodes_Controller();
		echo $episodes_controller->render_recent_episodes();
	}
}
