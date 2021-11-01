<?php
/**
 * Paid Memberships Pro controller
 */

namespace SeriouslySimplePodcasting\Integrations\Paid_Memberships_Pro;

use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Traits\Singleton;
use WP_Error;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paid Memberships Pro controller
 *
 * @author Sergey Zakharchenko
 * @package SeriouslySimplePodcasting
 * @since 2.9.3
 */
class Paid_Memberships_Pro_Integrator extends Abstract_Integrator {

	use Singleton;

	/**
	 * @var Feed_Handler
	 * */
	protected $feed_handler;

	/**
	 * @var Renderer
	 * */
	protected $renderer;

	/**
	 * Class Paid_Memberships_Pro_Integrator constructor.
	 *
	 * @param  Feed_Handler $feed_handler
	 */
	public function init( $feed_handler, $renderer ) {
		if ( ! class_exists( 'PMPro_Membership_Level' ) ) {
			return;
		}

		$this->feed_handler = $feed_handler;
		$this->renderer     = $renderer;

		if ( is_admin() && ! ssp_is_ajax() ) {
			$this->init_integration_settings();
		} else {
			$this->protect_private_series();
		}
	}

	/**
	 * Inits integration settings.
	 * */
	protected function init_integration_settings(){
		// Use 12 priority because Podcast and Series post types registered on 11.
		add_action( 'init', array( $this, 'integration_settings' ), 12 );
	}

	/**
	 * Protects private series.
	 * */
	protected function protect_private_series() {
		add_filter( 'pmpro_has_membership_access_filter', array( $this, 'access_filter' ), 10, 4 );
		add_action( 'ssp_before_feed', array( $this, 'protect_feed_access' ) );
	}

	/**
	 * Protects access to private feeds.
	 * */
	public function protect_feed_access() {
		$series = get_term_by( 'slug', $this->feed_handler->get_podcast_series(), 'series' );

		$series_levels = $this->get_series_level_ids( $series->term_id );
		$has_access    = $this->has_access( wp_get_current_user(), $series_levels );

		if ( ! $has_access ) {
			$stylesheet_url = $this->feed_handler->get_stylesheet_url();
			$title          = esc_html( get_the_title_rss() );
			$description    = wp_strip_all_tags( pmpro_get_no_access_message( '', $series_levels ) );
			$args           = apply_filters( 'ssp_feed_no_access_args', compact( 'stylesheet_url', 'title', 'description' ) );
			$path           = apply_filters( 'ssp_feed_no_access_path', 'feed/feed-no-access' );
			$this->renderer->render( $path, $args );
			exit();
		}
	}


	/**
	 * Protects access to private episodes.
	 *
	 * @param array|false $access
	 * @param \WP_Post $post
	 * @param \WP_User $user
	 * @param array $post_level_ids
	 *
	 * @return mixed
	 */
	public function access_filter( $access, $post, $user, $post_level_ids ) {

		$is_admin   = is_admin() && ! ssp_is_ajax();
		$is_podcast = in_array( $post->post_type, ssp_post_types() );

		if ( $is_admin || ! $is_podcast || ! $access ) {
			return $access;
		}

		$series = $this->get_episode_series( $post->ID );

		foreach ( $series as $series_item ) {
			$post_level_ids = array_merge( $post_level_ids, $this->get_series_level_ids( $series_item->term_id ) );
		}

		return $this->has_access( $user, $post_level_ids );
	}

	/**
	 * Check if user has access to the episode. Took the logic from PMPro.
	 *
	 * @see pmpro_has_membership_access()
	 * */
	protected function has_access( $user, $post_level_ids ) {
		if ( empty( $post_level_ids ) ) {
			return true;
		}

		$user_levels = pmpro_getMembershipLevelsForUser( $user->ID );

		$user_level_ids = array_map( function ( $level ) {
			return $level->id;
		}, $user_levels );

		return count( $user_levels ) && count( array_intersect( $user_level_ids, $post_level_ids ) );
	}

	/**
	 * Gets series level ids.
	 *
	 * @param $term_id
	 *
	 * @return int[]
	 */
	protected function get_series_level_ids( $term_id ) {
		$levels    = (array) ssp_get_option( sprintf( 'series_%s_pmpro_levels', $term_id ), array() );
		$level_ids = array();
		foreach ( $levels as $level ) {
			$level_ids[] = (int) str_replace( 'lvl_', '', $level );
		}

		return $level_ids;
	}

	/**
	 * Gets array of episode series terms.
	 *
	 * @param $post_id
	 *
	 * @return WP_Term[]
	 */
	protected function get_episode_series( $post_id ) {
		$series = wp_get_post_terms( $post_id, 'series' );

		if( is_wp_error($series) ){
			return [];
		}

		return $series;
	}

	/**
	 * Inits integration settings.
	 */
	public function integration_settings(){
		$args = $this->get_integration_settings();

		if ( ! ssp_is_connected_to_castos() ) {
			$msg = __( 'Please <a href="%s">connect to Castos hosting</a> to enable integrations', 'seriously-simple-podcasting' );
			$msg = sprintf( $msg, admin_url( 'edit.php?post_type=podcast&page=podcast_settings&tab=castos-hosting' ) );

			$args['description'] = $msg;
			$args['fields']      = array();
		}

		$this->add_integration_settings( $args );
	}

	/**
	 * Gets integration settings.
	 *
	 * @return array
	 */
	protected function get_integration_settings(){
		$series           = $this->get_series();
		$levels           = $this->get_membership_levels();
		$checkbox_options = [];
		foreach ( $levels as $level ) {
			$checkbox_options[ 'lvl_' . $level->id ] = sprintf( 'Requires %s membership', $level->name );
		}

		$settings = array(
			'id'          => 'paid_memberships_pro',
			'title'       => __( 'Paid Memberships Pro', 'seriously-simple-podcasting' ),
			'description' => __( 'Paid Memberships Pro integration settings.', 'seriously-simple-podcasting' ),
			'fields'      => array(),
		);

		foreach ( $series as $series_item ) {

			$series_item_settings = array(
				'id'          => sprintf( 'series_%s_pmpro_levels', $series_item->term_id ),
				'label'       => $series_item->name,
				'type'        => 'checkbox_multi',
				'options'     => $checkbox_options,
			);


			$settings['fields'][] = $series_item_settings;
		}

		return $settings;
	}

	/**
	 * Gets array of all available series terms.
	 *
	 * @return WP_Term[]|WP_Error
	 */
	protected function get_series() {
		return get_terms( 'series', array( 'hide_empty' => false ) );
	}

	/**
	 * Gets all possible membership levels.
	 *
	 * @return array
	 */
	protected function get_membership_levels() {
		return (array)pmpro_getAllLevels();
	}
}
