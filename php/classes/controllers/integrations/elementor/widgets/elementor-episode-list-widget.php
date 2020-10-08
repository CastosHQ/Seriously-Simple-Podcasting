<?php

namespace SeriouslySimplePodcasting\Controllers\Integrations\Elementor\Widgets;

use SeriouslySimplePodcasting\Controllers\Episode_Controller;
use WP_Query;

class Elementor_Episode_List_Widget extends \Elementor\Widget_Base {
	public function get_name() {
		return 'Episode List';
	}

	public function get_title() {
		return __( 'Episode List', 'seriously-simple-podcasting' );
	}

	public function get_icon() {
		return 'fa fa-list';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	public function get_episodes() {
		$paged = ( get_query_var('paged') ? get_query_var('paged') : 1 );

		$args = array(
			'fields'         => array( 'post_title, id' ),
			'posts_per_page' => -1,
			'post_type'      => 'podcast'
		);

		$episodes = new \WP_Query($args);

		$episodeOptions = [];
		foreach($episodes as $episode) {
			$episodeOptions[$episode->ID] = $episode->post_title;
		}

		return $episodeOptions;
	}

	protected function _register_controls() {}

	protected function render() {
		$episodeController = new Episode_Controller(__FILE__, SSP_VERSION);

		echo $episodeController->all_episodes();
	}
}
