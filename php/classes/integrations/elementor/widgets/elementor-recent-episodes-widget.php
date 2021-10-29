<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use SeriouslySimplePodcasting\Controllers\Episode_Controller;

class Elementor_Recent_Episodes_Widget extends Widget_Base {
	public function get_name() {
		return 'Recent Episodes';
	}

	public function get_title() {
		return __( 'Recent Episodes', 'seriously-simple-podcasting' );
	}

	public function get_icon() {
		return 'eicon-archive-posts';
	}

	public function get_categories() {
		return [ 'podcasting' ];
	}

	protected function render() {
		global $ss_podcasting;
		echo $ss_podcasting->episode_controller->render_recent_episodes();
	}
}
