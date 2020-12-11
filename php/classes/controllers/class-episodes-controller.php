<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Renderers\Renderer;
use WP_Query;

/**
 * SSP Episodes Controller
 *
 * To be used when rendering lists of episode data
 * Currently used by the Elementor Widgets,
 * but eventually should also power the Castos Blocks,
 * and the SSP widgets and shortcodes
 *
 * @package Seriously Simple Podcasting
 */
class Episodes_Controller extends Controller {

	public $renderer = null;

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->renderer = new Renderer();
		$this->init();
	}

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_recent_episodes_assets' ) );
	}

	public function load_recent_episodes_assets() {
		wp_register_style( 'ssp-recent-episodes', $this->assets_url . 'css/recent-episodes.css', array(), $this->version );
	}

	/**
	 * Get Episode List
	 *
	 * @param array $episode_ids , array of episode ids being loaded into the player
	 * @param $include_title
	 * @param $include_excerpt
	 * @param $include_player
	 * @param $include_subscribe_links
	 *
	 * @return array [ $src, $width, $height ]
	 *
	 * @since 2.2.3
	 */
	public function episode_list( $episode_ids, $include_title = false, $include_excerpt = false, $include_player = false, $include_subscribe_links = false ) {
		$episodes = null;

		if ( ! empty( $episode_ids ) ) {
			$args = array(
				'include'        => array_values( $episode_ids ),
				'post_type'      => 'podcast',
				'numberposts'    => -1
			);

			$episodes = get_posts( $args );
		}

		$episodes_template_data = array(
			'episodes'       => $episodes,
		);

		$episodes_template_data = apply_filters( 'episode_list_data', $episodes_template_data );

		return $this->renderer->render( $episodes_template_data, 'episodes/episode-list' );
	}

	/**
	 * Render a list of all episodes, based on settings sent
	 * @todo, currently used for Elementor, update to use for the Block editor as well.
	 *
	 * @param $settings
	 *
	 * @return mixed|void
	 */
	public function render_episodes($settings) {
		$player       = new Players_Controller( __FILE__, SSP_VERSION );
		$args  = array(
			'post_type'      => 'podcast',
			'posts_per_page' => 10,
		);

		$episodes               = new WP_Query( $args );
		$episodes_template_data = array(
			'player' => $player,
			'episodes' => $episodes,
			'settings' => $settings,
		);

		$episodes_template_data = apply_filters( 'episode_list_data', $episodes_template_data );

		return $this->renderer->render( $episodes_template_data, 'episodes/all-episodes-list' );
	}

	/**
	 * Gather a list of the last 3 episodes for the Elementor Recent Episodes Widget
	 *
	 * @return mixed|void
	 */
	public function recent_episodes() {
		$args = array(
			'posts_per_page' => 3,
			'offset'         => 1,
			'post_type'      => ssp_post_types( true ),
			'post_status'    => array( 'publish' ),
		);

		$episodes_query      = new WP_Query( $args );
		$template_data = array(
			'episodes' => $episodes_query->get_posts(),
		);

		return apply_filters( 'recent_episodes_template_data', $template_data );
	}

	/**
	 * Render the template for the Elementor Recent Episodes Widget
	 *
	 * @return mixed|void
	 */
	public function render_recent_episodes() {
		if ( wp_style_is( 'ssp-recent-episodes', 'registered' ) && ! wp_style_is( 'ssp-recent-episodes', 'enqueued' ) ) {
			wp_enqueue_style( 'ssp-recent-episodes' );
		}
		$template_data = $this->recent_episodes();

		return $this->renderer->render( $template_data, 'episodes/recent-episodes' );
	}

}
