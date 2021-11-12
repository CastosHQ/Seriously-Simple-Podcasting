<?php
/**
 * Paid Memberships Pro controller.
 */

namespace SeriouslySimplePodcasting\Integrations\Paid_Memberships_Pro;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;
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
	 * @var Castos_Handler
	 * */
	protected $castos_handler;

	/**
	 * @var Renderer
	 * */
	protected $renderer;

	/**
	 * @var Log_Helper
	 * */
	protected $logger;

	/**
	 * @var $castos_podcasts
	 * */
	protected $castos_podcasts;


	/**
	 * Class Paid_Memberships_Pro_Integrator constructor.
	 *
	 * @param Feed_Handler $feed_handler
	 * @param Castos_Handler $castos_handler
	 * @param Renderer $renderer
	 * @param Log_Helper $logger
	 */
	public function init( $feed_handler, $castos_handler, $renderer, $logger ) {
		if ( ! $this->check_dependencies() ) {
			return;
		}

		$this->feed_handler   = $feed_handler;
		$this->castos_handler = $castos_handler;
		$this->renderer       = $renderer;
		$this->logger         = $logger;

		if ( is_admin() && ! ssp_is_ajax() ) {
			$this->init_integration_settings();
		} else {
			$this->protect_private_series();
		}

		$this->init_subscribers_sync();
	}


	/**
	 * Inits subscribers sync.
	 * There are 2 cases when sync is needed:
	 * 1. When user's Membership Level is changed.
	 * 2. When Series -> Membership Level association is changed.
	 */
	protected function init_subscribers_sync() {

		// Sync users when their Membership Level is changed (from admin panel, when registered or cancelled).
		add_filter( 'pmpro_change_level', array(
			$this,
			'sync_subscribers_on_change_membership_level'
		), 10, 3 );


		// Schedule the bulk sync when Series -> Membership Level association is changed.
		add_filter( 'allowed_options', function ( $allowed_options ) {
			// Option ss_podcasting_is_pmpro_integration is just a marker that PMPro integration settings have been saved.
			// If so, we can do the sync magic.
			if ( isset( $allowed_options['ss_podcasting'] ) ) {
				$key = array_search( 'ss_podcasting_is_pmpro_integration', $allowed_options['ss_podcasting'] );
				if ( false !== $key ) {
					unset( $allowed_options['ss_podcasting'][ $key ] );
					$this->schedule_bulk_sync_subscribers();
				}
			}

			return $allowed_options;
		}, 20 );

		// Run the scheduled bulk sync.
		add_action( 'ssp_bulk_sync_subscribers', array( $this, 'bulk_sync_subscribers' ) );
	}

	/**
	 * Schedule bulk sync subscribers.
	 */
	protected function schedule_bulk_sync_subscribers(){
		// 1. Save old membership level map: [['level' => ['users']['series']]]
		update_option( 'ss_pmpro_users_series_map', $this->get_users_series_map(), false );

		// 2. Schedule a task to add/revoke users
		if ( ! wp_next_scheduled( 'ssp_bulk_sync_subscribers' ) ) {
			wp_schedule_single_event( time(), 'ssp_bulk_sync_subscribers' );
		}
	}


	/**
	 * Gets the map between levels, series and users [['level' => ['users']['series']]].
	 *
	 * @return array
	 */
	protected function get_users_series_map() {
		$map    = array();

		$membership_users = $this->get_membership_user_ids();

		foreach ( $membership_users as $user ) {
			$series = $this->get_series_ids_by_level( $user->membership_id );

			$map[ $user->user_id ] = $series;
		}

		return $map;
	}


	/**
	 * Bulk sync subscribers after settings change.
	 */
	public function bulk_sync_subscribers() {
		$old_map = get_option( 'ss_pmpro_users_series_map', array() );
		$new_map = $this->get_users_series_map();

		foreach ( $new_map as $user_id => $new_series ) {
			$old_series = isset( $old_map[ $user_id ] ) ? $old_map[ $user_id ] : array();

			$add_series    = array_diff( $new_series, $old_series );
			$revoke_series = array_diff( $old_series, $new_series );

			$this->sync_user( $user_id, $revoke_series, $add_series );
		}
	}


	/**
	 * Sync subscribers when user's Membership Level is changed (case 1).
	 *
	 * @param array|int $level
	 * @param int $user_id
	 */
	public function sync_subscribers_on_change_membership_level( $level, $user_id ) {

		$level_id = is_array( $level ) ? $level['membership_id'] : $level;

		$old_level = pmpro_getMembershipLevelForUser( $user_id );

		$old_series_ids = $this->get_series_ids_by_level( $old_level->id );

		$new_series_ids = $this->get_series_ids_by_level( $level_id );

		$revoke_series_ids = array_diff( $old_series_ids, $new_series_ids );

		$add_series_ids = array_diff( $new_series_ids, $old_series_ids );

		$this->sync_user( $user_id, $revoke_series_ids, $add_series_ids );

		return $level_id;
	}

	/**
	 * @param int $user_id
	 * @param int[] $revoke_series_ids
	 * @param int[] $add_series_ids
	 */
	protected function sync_user( $user_id, $revoke_series_ids, $add_series_ids ) {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;
		}

		if ( $revoke_series_ids ) {
			$this->logger->log( __METHOD__ . sprintf( ': Revoke user %s from series %s', $user->user_email, json_encode( $revoke_series_ids ) ) );
			$res = $this->revoke_subscriber_from_podcasts( $user, $revoke_series_ids );
			$this->logger->log( __METHOD__ . ': Revoke result', $res );
		}

		if ( $add_series_ids ) {
			$this->logger->log( __METHOD__ . sprintf( ': Add user %s to series %s', $user->user_email, json_encode( $add_series_ids ) ) );
			$res = $this->add_subscriber_to_podcasts( $user, $add_series_ids );
			$this->logger->log( __METHOD__ . ': Add result', $res );
		}
	}


	/**
	 * Revokes subscriber from multiple Castos podcasts.
	 *
	 * @param \WP_User $user
	 * @param int[] $series_ids
	 *
	 * @return array
	 */
	protected function revoke_subscriber_from_podcasts( $user, $series_ids ) {
		$podcast_ids = $this->convert_series_ids_to_podcast_ids( $series_ids );

		return $this->castos_handler->revoke_subscriber_from_podcasts( $podcast_ids, $user->user_email );
	}


	/**
	 * Adds subscriber to multiple Castos podcasts.
	 *
	 * @param \WP_User $user
	 * @param int[] $series_ids
	 *
	 * @return array
	 */
	protected function add_subscriber_to_podcasts( $user, $series_ids ) {
		$podcast_ids = $this->convert_series_ids_to_podcast_ids( $series_ids );

		return $this->castos_handler->add_subscriber_to_podcasts(
			$podcast_ids,
			$user->user_email,
			$user->display_name
		);
	}


	/**
	 * Converts series IDs to the Castos podcast IDs.
	 *
	 * @param int[] $series_ids
	 *
	 * @return array
	 */
	protected function convert_series_ids_to_podcast_ids( $series_ids ) {
		$series_podcasts_map = $this->get_series_podcasts_map();

		$podcast_ids = array();

		foreach ( $series_ids as $series_id ) {
			$podcast_ids[] = $series_podcasts_map[ $series_id ];
		}

		return $podcast_ids;
	}


	/**
	 * Gets IDs of the series attached to the Membership Level.
	 *
	 * @param int $level_id
	 *
	 * @return array
	 */
	protected function get_series_ids_by_level( $level_id ) {

		$series_ids = array();

		if ( empty( $level_id ) ) {
			return $series_ids;
		}

		$series_terms = $this->get_series();

		foreach ( $series_terms as $series ) {
			$levels_ids = $this->get_series_level_ids( $series->term_id );

			if ( in_array( $level_id, $levels_ids ) ) {
				$series_ids[] = $series->term_id;
			}
		}

		return $series_ids;
	}

	/**
	 * Gets IDs of the all the users who have any membership level.
	 *
	 * @param int $level_id
	 *
	 * @return array
	 */
	protected function get_membership_user_ids() {

		global $wpdb;

		$query = "SELECT DISTINCT user_id, membership_id from {$wpdb->pmpro_memberships_users} WHERE status='active'";

		$res = $wpdb->get_results( $query );

		return $res;
	}


	/**
	 * Gets the map between series and podcasts.
	 *
	 * @return array
	 */
	protected function get_series_podcasts_map() {
		$podcasts = $this->castos_handler->get_podcasts();

		$map = array();

		if ( empty( $podcasts['data']['podcast_list'] ) ) {
			$this->logger->log( __METHOD__ . ': Error: empty podcasts!' );

			return $map;
		}

		foreach ( $podcasts['data']['podcast_list'] as $podcast ) {
			$map[ $podcast['series_id'] ] = $podcast['id'];
		}

		return $map;
	}


	/**
	 * Checks if all needed classes and functions from PMPro plugin exist.
	 *
	 * @return bool
	 */
	protected function check_dependencies() {
		$classes   = array( 'PMPro_Membership_Level' );
		$functions = array( 'pmpro_getMembershipLevelsForUser', 'pmpro_get_no_access_message' );

		foreach ( $classes as $class ) {
			if ( ! class_exists( $class ) ) {
				return false;
			}
		}

		foreach ( $functions as $function ) {
			if ( ! function_exists( $function ) ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Inits integration settings.
	 * */
	protected function init_integration_settings() {
		// Use priority 12 because Podcast and Series post types registered on 11.
		add_action( 'init', array( $this, 'integration_settings' ), 12 );
	}


	/**
	 * Protects private series.
	 * */
	protected function protect_private_series() {
		add_filter( 'pmpro_has_membership_access_filter', array( $this, 'access_filter' ), 10, 4 );
		add_action( 'ssp_before_feed', array( $this, 'protect_feed_access' ) );

		add_filter( 'ssp_show_media_player_in_content', function ( $show ) {
			if ( function_exists( 'pmpro_has_membership_access' ) && ! pmpro_has_membership_access() ) {
				return false;
			}

			return $show;
		} );
	}


	/**
	 * Protects access to private feeds.
	 * */
	public function protect_feed_access() {
		$series_slug = $this->feed_handler->get_podcast_series();
		if ( empty( $series_slug ) ) {
			return;
		}
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
	 * @param object[] $post_levels
	 *
	 * @return bool
	 */
	public function access_filter( $access, $post, $user, $post_levels ) {

		// Get level ids.
		$post_level_ids = array_filter( array_map( function ( $item ) {
			return isset( $item->id ) ? $item->id : null;
		}, (array) $post_levels ) );

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
	 * @return bool
	 * *@see pmpro_has_membership_access()
	 */
	protected function has_access( $user, $post_level_ids ) {
		if ( empty( $post_level_ids ) ) {
			return true;
		}

		$user_levels = (array) pmpro_getMembershipLevelsForUser( $user->ID );

		$user_level_ids = array();
		foreach ( $user_levels as $user_level ) {
			$user_level_ids[] = $user_level->id;
		}

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
		$levels    = (array) ssp_get_option( sprintf( 'series_%s_pmpro_levels', $term_id ), null );
		$level_ids = array();
		foreach ( $levels as $level ) {
			$level_ids[] = (int) str_replace( 'lvl_', '', $level );
		}

		return array_filter( $level_ids );
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

		if ( is_wp_error( $series ) ) {
			return [];
		}

		return $series;
	}


	/**
	 * Inits integration settings.
	 */
	public function integration_settings() {

		if ( ! $this->needs_integration_settings() ) {
			return;
		}

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
	 * Checks if we need to obtain the dynamic integration settings.
	 *
	 * @return bool
	 */
	protected function needs_integration_settings() {
		global $pagenow;

		return 'options.php' === $pagenow || 'podcast_settings' === filter_input( INPUT_GET, 'page' );
	}


	/**
	 * Gets integration settings.
	 *
	 * @return array
	 */
	protected function get_integration_settings() {
		$series = $this->get_series();
		$levels = $this->get_membership_levels();

		$settings = array(
			'id'          => 'paid_memberships_pro',
			'title'       => __( 'Paid Memberships Pro', 'seriously-simple-podcasting' ),
			'description' => __( 'Select which podcast Series you would like to be available only
								to Members via Paid Memberships Pro.', 'seriously-simple-podcasting' ),
			'fields'      => array(
				array(
					'id'   => 'is_pmpro_integration',
					'type' => 'hidden',
				),
			),
		);

		if ( ! $levels ) {
			$levels_url              = admin_url( 'admin.php?page=pmpro-membershiplevels' );
			$settings['description'] = sprintf( __( 'To require membership to access a podcast please <a href="%s">set up a
										membership level</a> first.', 'seriously-simple-podcasting' ), $levels_url );
			return $settings;
		}

		$checkbox_options = array();

		foreach ( $levels as $level ) {
			$checkbox_options[ 'lvl_' . $level->id ] = sprintf( 'Require %s to access', $level->name );
		}

		foreach ( $series as $series_item ) {
			$series_item_settings = array(
				'id'      => sprintf( 'series_%s_pmpro_levels', $series_item->term_id ),
				'label'   => $series_item->name,
				'type'    => 'checkbox_multi',
				'options' => $checkbox_options,
			);

			if ( ! $this->is_series_protected_in_castos( $series_item->term_id ) ) {
				$series_item_settings['type']        = 'info';
				$series_item_settings['description'] = 'Please first make this podcast private in your Castos dashboard';
			}

			$settings['fields'][] = $series_item_settings;
		}

		return $settings;
	}


	/**
	 * Check if the series is protected on Castos side.
	 *
	 * @param int $series_id
	 * @param bool $default
	 *
	 * @return bool|mixed
	 */
	protected function is_series_protected_in_castos( $series_id, $default = false ) {
		$podcasts = $this->get_castos_podcasts();

		foreach ( $podcasts as $podcast ) {
			if ( isset( $podcast['series_id'] ) && $series_id === $podcast['series_id'] ) {
				return $podcast['is_feed_protected'];
			}
		}

		// Return true
		return $default;
	}


	/**
	 * @return array
	 */
	protected function get_castos_podcasts() {
		if ( is_null( $this->castos_podcasts ) ) {
			$podcasts              = $this->castos_handler->get_podcasts();
			$this->castos_podcasts = isset( $podcasts['data']['podcast_list'] ) ?
				$podcasts['data']['podcast_list'] :
				array();
		}

		return $this->castos_podcasts;
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
		return (array) pmpro_getAllLevels();
	}
}
