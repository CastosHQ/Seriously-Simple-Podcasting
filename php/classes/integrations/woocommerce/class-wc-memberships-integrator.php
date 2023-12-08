<?php
/**
 * WooCommerce Memberships Integrator.
 */

namespace SeriouslySimplePodcasting\Integrations\Woocommerce;

use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;
use SeriouslySimplePodcasting\Integrations\Abstract_Integrator;
use SeriouslySimplePodcasting\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MemberPress Integrator
 *
 *
 * @author Sergiy Zakharchenko
 * @package SeriouslySimplePodcasting
 * @since 2.16.0
 */
class WC_Memberships_Integrator extends Abstract_Integrator {

	use Singleton;

	const ADD_LIST_OPTION = 'ssp_wcmps_add_subscribers';

	const REVOKE_LIST_OPTION = 'ssp_wcmps_revoke_subscribers';

	const EVENT_BULK_SYNC_SUBSCRIBERS = 'ssp_wcmps_bulk_sync_subscribers';

	const EVENT_ADD_SUBSCRIBERS = 'ssp_wcmps_add_subscribers';

	const EVENT_REVOKE_SUBSCRIBERS = 'ssp_wcmps_revoke_subscribers';

	const SINGLE_SYNC_DATA_OPTION = 'ssp_wcmps_single_sync_data';

	const SINGLE_SYNC_EVENT = 'ssp_wcmps_single_sync';


	/**
	 * Class WC_Memberships_Integrator constructor.
	 *
	 * @param Feed_Handler $feed_handler
	 * @param Castos_Handler $castos_handler
	 * @param Log_Helper $logger
	 * @param Admin_Notifications_Handler $notices_handler
	 */
	public function init( $feed_handler, $castos_handler, $logger, $notices_handler ) {

		$this->feed_handler    = $feed_handler;
		$this->castos_handler  = $castos_handler;
		$this->logger          = $logger;
		$this->notices_handler = $notices_handler;

		add_action( 'plugins_loaded', array( $this, 'late_init' ) );
	}

	public function late_init(){
		if ( !  $this->check_dependencies( array( 'WC_Memberships_Loader' ) ) ) {
			return;
		}

		if ( is_admin() && ! ssp_is_ajax() ) {
			$this->init_integration_settings();
		} else {
			$integration_enabled = ssp_get_option( 'enable_wcmps_integration' );
			if ( $integration_enabled ) {
				$this->protect_private_series();
			}
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
		$this->init_single_sync_subscriber();

		// Init bulk sync process.
		$this->init_bulk_sync_process();
	}

	/**
	 * Steps:
	 * 1. When user changes the global podcasts => membership levels settings, we schedule new bulk sync.
	 * 2. When we schedule it, we generate the users => podcasts(series) map, that existed before saving those settings.
	 * 3. When bulk sync starts, we set BULK_UPDATE_STARTED mark. If this mark exists, we show a bulk updating notice on settings pages.
	 * 4. We calculate user ids to add and to remove by difference between saved map and current map.
	 *    We save these ids into separate options ADD_LIST_OPTION and REVOKE_LIST_OPTION.
	 * 5. We schedule add subscribers process.
	 * 6. We sync 100 subscribers per time, and after each successfull request, we update the list of users to sync.
	 * 7. After add subscribers process is done, we schedule remove subscribers process.
	 * 8. After remove subscribers process is done (list of subscribers to remove is empty), we remove BULK_UPDATE_STARTED mark.
	 *
	 * How does bulk sync works - we check the difference between old map and the new map.
	 * Old map is generated when user saves the settings, new map - when EVENT_BULK_SYNC_SUBSCRIBERS job is run.
	 * If user saves settings multiple times, between those events, it still should work correctly, because old map was already saved,
	 * and we do not regenerate it again. So, when the job starts, it checks the difference between map generated before the first change and the last saved state.
	 *
	 * Edge cases:
	 * 1. Sync job fails (API problems etc.), and we didn't sync all the subscribers.
	 * To avoid that, we save ids to add and ids to revoke, and update them every time API returns OK.
	 * 2. User changed settings when bulk update is not finished.
	 * For this case, we regenerate map and schedule another bulk sync, which will run only when previous bulk update job is fully completed.
	 *
	 * @return void
	 */
	protected function init_bulk_sync_process() {
		// Schedule the bulk sync when Series -> Membership Level association is changed.
		add_filter( 'allowed_options', function ( $allowed_options ) {
			// Option ss_podcasting_is_wcmps_integration is just a marker that integration settings have been saved.
			// If so, we can do the sync magic.
			if ( isset( $allowed_options['ss_podcasting'] ) ) {
				$key = array_search( 'ss_podcasting_is_wcmps_integration', $allowed_options['ss_podcasting'] );
				if ( false !== $key ) {
					unset( $allowed_options['ss_podcasting'][ $key ] );
					$this->schedule_bulk_sync_subscribers();
				}
			}

			return $allowed_options;
		}, 20 );

		// Step 1. Run the scheduled bulk sync. Prepare add and remove lists, and run add process.
		add_action( self::EVENT_BULK_SYNC_SUBSCRIBERS, array( $this, 'bulk_sync_subscribers' ) );

		// Step 2. Run add process.
		add_action( self::EVENT_ADD_SUBSCRIBERS, array( $this, 'bulk_add_subscribers' ) );

		// Step 3. Run revoke process.
		add_action( self::EVENT_REVOKE_SUBSCRIBERS, array( $this, 'bulk_revoke_subscribers' ) );
	}

	/**
	 * Unfortunately, there is no action that we can use to track the member change.
	 * So, we need to listen the members table update.
	 * */
	protected function init_single_sync_subscriber() {
		$this->listen_user_membership_update();
		$this->listen_single_sync();
	}

	/**
	 * Do single sync as the separate event to not interfere with the DB update process.
	 * @return void
	 * @see listen_user_membership_update()
	 *
	 */
	protected function listen_single_sync() {
		add_action( self::SINGLE_SYNC_EVENT, function () {
			$single_update_data = get_option( self::SINGLE_SYNC_DATA_OPTION, array() );
			if ( empty( $single_update_data['users'] ) ) {
				return;
			}

			foreach ( $single_update_data['users'] as $user_id => $actions ) {
				$added_memberships   = $actions['added_memberships'];
				$revoked_memberships = $actions['revoked_memberships'];

				$revoke_series_ids = $this->convert_membership_ids_into_series_ids( $revoked_memberships );
				$add_series_ids    = $this->convert_membership_ids_into_series_ids( $added_memberships );

				$res = $this->sync_user( $user_id, $revoke_series_ids, $add_series_ids );

				if ( ! $res ) {
					// Let's make sure there won't be an infinite number of attempts.
					if ( $single_update_data['attempts'] < 10 ) {
						$this->logger->log( __METHOD__ . sprintf( ': Error! Could not sync user %s.', $user_id ) );
					} else {
						$this->logger->log( __METHOD__ . sprintf( ': Error! Failed to sync user %s. Will try again later.', $user_id ) );
						$single_update_data['attempts'] = $single_update_data['attempts'] + 1;
						update_option( self::SINGLE_SYNC_DATA_OPTION, $single_update_data );
						$this->schedule_single_sync( 20 );
					}

					return;
				}
			}

			delete_option( self::SINGLE_SYNC_DATA_OPTION );
		} );
	}

	/**
	 * There is no action or filter on members update, and there might be a lot of possible cases where members can be updated,
	 * so the only 100% way to listen the members update is to listen the database queries.
	 *
	 * @return void
	 */
	protected function listen_user_membership_update() {
		/**
		 * @param \WC_Memberships_Membership_Plan $plan
		 * @param array $user_data {
		 *     User data.
		 *
		 *  @type int $user_id User ID.
		 *  @type int $user_membership_id User membership ID.
		 *  @type bool $is_update Is it update or create.
		 * }
		 * */
		add_action( 'wc_memberships_user_membership_saved', function ( $plan, $user_data ) {
			$user_membership_id = isset( $user_data['user_membership_id'] ) ? $user_data['user_membership_id'] : '';
			if ( empty( $user_membership_id ) ) {
				return;
			}

			$user_membership = $this->get_user_membership( $user_membership_id );

			if ( 'active' === $user_membership->get_status() ) {
				$this->prepare_single_sync( $user_data['user_id'], $user_membership->get_plan_id(), null );
			} else {
				$this->prepare_single_sync( $user_data['user_id'], null, $user_membership->get_plan_id() );
			}

		}, 10, 2 );

		/**
		 * @param \WC_Memberships_User_Membership $user_membership
		 * */
		add_action( 'wc_memberships_user_membership_deleted', function ( $user_membership ) {
			$this->prepare_single_sync( $user_membership->user_id, null, $user_membership->get_plan_id() );
		} );
	}

	/**
	 * @param $user_membership_id
	 *
	 * @return \WC_Memberships_User_Membership|null
	 */
	protected function get_user_membership( $user_membership_id ) {
		return wc_memberships()->get_user_memberships_instance()->get_user_membership( $user_membership_id );
	}

	/**
	 * @param int $user_id
	 * @param int $added_membership
	 * @param int $revoked_membership
	 *
	 * @return void
	 */
	protected function prepare_single_sync( $user_id, $added_membership, $revoked_membership ) {
		$single_sync_data = get_option( self::SINGLE_SYNC_DATA_OPTION, array() );

		$added_memberships = isset( $single_sync_data['users'][ $user_id ]['added_memberships'] ) ?
			$single_sync_data['users'][ $user_id ]['added_memberships'] : array();

		$revoked_memberships = isset( $single_sync_data['users'][ $user_id ]['revoked_memberships'] ) ?
			$single_sync_data['users'][ $user_id ]['revoked_memberships'] : array();

		$single_sync_data['users'][ $user_id ] = array(
			'added_memberships'   => array_unique( array_merge( $added_memberships, array( $added_membership ) ) ),
			'revoked_memberships' => array_unique( array_merge( $revoked_memberships, array( $revoked_membership ) ) ),
		);

		$single_sync_data['attempts'] = 0;

		update_option( self::SINGLE_SYNC_DATA_OPTION, $single_sync_data, false );
		$this->schedule_single_sync( 0 );
	}

	/**
	 * Schedule single sync.
	 *
	 * @param int $delay Schedule delay in minutes.
	 *
	 * @return void
	 */
	protected function schedule_single_sync( $delay = 5 ) {
		if ( ! wp_next_scheduled( self::SINGLE_SYNC_EVENT ) ) {
			wp_schedule_single_event( time() + $delay * MINUTE_IN_SECONDS, self::SINGLE_SYNC_EVENT );
		}
	}

	/**
	 * @param array $membership_ids
	 *
	 * @return array
	 */
	protected function convert_membership_ids_into_series_ids( $membership_ids ) {
		$series_ids = array();
		foreach ( $membership_ids as $level_id ) {
			$series_ids = array_merge( $series_ids, $this->get_series_ids_by_level( $level_id ) );
		}

		return array_unique( $series_ids );
	}

	/**
	 * Gets users series map.
	 *
	 * @return array
	 */
	protected function get_users_series_map() {
		return get_option( 'ss_wcmps_users_series_map', array() );
	}


	/**
	 * Updates users series map.
	 *
	 * @param array $map
	 *
	 * @return void
	 */
	protected function update_users_series_map( $map ) {
		update_option( 'ss_wcmps_users_series_map', $map, false );
	}

	/**
	 * Gets the map between users and related series [['2' => [3, 4]]].
	 *
	 * @return array
	 */
	protected function generate_users_series_map() {
		$map = array();

		$user_memberships = get_posts(
			array(
				'post_type'   => 'wc_user_membership',
				'numberposts' => - 1,
				'post_status' => 'wcm-active',
				'nopaging'    => true,
			)
		);

		foreach ( $user_memberships as $user_membership ) {
			$user       = get_user_by( 'id', $user_membership->post_author );
			$membership = get_post( $user_membership->post_parent );
			if ( ! $user || ! $membership ) {
				continue;
			}

			$podcast_ids = isset( $map[ $user->ID ] ) ? $map[ $user->ID ] : array();
			$add_podcasts_ids = $this->get_series_ids_by_level( $membership->ID );

			$map[ $user->ID ] = array_unique( array_merge( $podcast_ids, $add_podcasts_ids ) );
		}

		return $map;
	}


	/**
	 * @return string
	 */
	protected function get_successfully_finished_notice() {
		return __( 'WC Memberships data successfully synchronized!', 'seriously-simple-podcasting' );
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
		// Protect feed.
		add_action( 'ssp_before_feed', array( $this, 'protect_feed_access' ) );

		// Protect content.
		add_filter( 'ssp_show_excerpt_player', array( $this, 'hide_player_from_excerpt' ), 10, 2 );
	}

	/**
	 * Protects access to private feeds.
	 * */
	public function protect_feed_access() {
		$series_slug = $this->feed_handler->get_series_slug();
		if ( empty( $series_slug ) ) {
			return;
		}

		$series = get_term_by( 'slug', $this->feed_handler->get_series_slug(), 'series' );

		$has_access         = true;
		$required_level_ids = $this->get_series_level_ids( $series->term_id );

		if ( $required_level_ids ) {
			$has_access = $this->has_access( wp_get_current_user(), $required_level_ids );
		}

		if ( ! $has_access ) {
			$description = __( 'This content is Private. To access this podcast, contact the site owner.', 'seriously-simple-podcasting' );
			$this->feed_handler->render_feed_no_access( $series->term_id, $description );
			exit();
		}
	}

	/**
	 * For WC Memberships, page content is protected by the plugin itself, so we don't need to protect it.
	 * The only content which we need to hide, is player on excerpts.
	 * */
	public function hide_player_from_excerpt( $show, $post ) {

		if ( ! current_user_can( 'wc_memberships_view_restricted_post_content', $post->ID ) ) {
			return false;
		}

		return $show;
	}


	/**
	 * Check if user has access to the episode.
	 *
	 * @param \WP_User $user
	 * @param int[] $required_level_ids
	 *
	 * @return bool
	 */
	protected function has_access( $user, $required_level_ids ) {
		if ( empty( $required_level_ids ) ) {
			return true;
		}

		if ( ! $user->exists() ) {
			return false;
		}

		$user_active_memberships = wc_memberships_get_user_active_memberships( $user->ID );

		if ( empty( $user_active_memberships ) ) {
			return false;
		}

		$user_membership_ids = array_map( function( $user_membership ){
			return $user_membership->id;
		}, $user_active_memberships );

		return count( $user_membership_ids ) && count( array_intersect( $user_membership_ids, $required_level_ids ) );
	}


	/**
	 * Gets series level ids.
	 *
	 * @param int $term_id
	 *
	 * @return int[]
	 */
	protected function get_series_level_ids( $term_id ) {
		$levels    = (array) ssp_get_option( sprintf( 'series_%s_wcmps_levels', $term_id ), null );
		$level_ids = array();
		foreach ( $levels as $level ) {
			$level_ids[] = (int) str_replace( 'lvl_', '', $level );
		}

		return array_filter( $level_ids );
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
		} else {
			if ( 'podcast_settings' === filter_input( INPUT_GET, 'page' ) &&
			     ( $this->bulk_update_started() || wp_next_scheduled( self::SINGLE_SYNC_EVENT ) ) ) {
				$this->notices_handler->add_flash_notice( __( 'Synchronizing WooCommerce Memberships data with Castos...', 'seriously-simple-podcasting' ) );
			}
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
		$settings = array(
			'id'          => 'wc_memberships',
			'title'       => __( 'Woocommerce Memberships', 'seriously-simple-podcasting' ),
			'description' => __( 'Select which Podcast you would like to be available only
								to Members via Woocommerce Memberships.', 'seriously-simple-podcasting' ),
			'fields'      => array(
				array(
					'id'   => 'is_wcmps_integration',
					'type' => 'hidden',
				),
				array(
					'id'          => 'enable_wcmps_integration',
					'type'        => 'checkbox',
					'default'     => '',
					'label'       => __( 'Enable integration', 'seriously-simple-podcasting' ),
					'description' => __( 'Enable Woocommerce Memberships integration', 'seriously-simple-podcasting' ),
				),
			),
		);

		if ( ! $this->needs_extended_integration_settings() ) {
			$settings['description'] = '';

			return $settings;
		}

		$series = $this->get_series();
		$levels = $this->get_membership_levels();

		if ( ! $levels ) {
			$levels_url              = admin_url( 'edit.php?post_type=wc_membership_plan' );
			$settings['description'] = sprintf( __( 'To require membership to access a podcast please <a href="%s">set up
										memberships</a> first.', 'seriously-simple-podcasting' ), $levels_url );

			return $settings;
		}

		$checkbox_options = array();

		foreach ( $levels as $level ) {
			$checkbox_options[ 'lvl_' . $level->id ] = sprintf( 'Require %s to access', $level->name );
		}

		foreach ( $series as $series_item ) {
			$series_item_settings = array(
				'id'          => sprintf( 'series_%s_wcmps_levels', $series_item->term_id ),
				'label'       => $series_item->name,
				'type'        => 'select2_multi',
				'options'     => $checkbox_options,
				'description' => 'Require enrollment to membership',
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
	 * @return bool
	 */
	protected function needs_extended_integration_settings() {
		if ( ! ssp_get_option( 'enable_wcmps_integration' ) ) {
			return false;
		}

		$is_integration_page   = 'wc_memberships' === filter_input( INPUT_GET, 'integration' );
		$is_integration_update = 'wc_memberships' === filter_input( INPUT_POST, 'ssp_integration' );

		if ( ! $is_integration_page && ! $is_integration_update ) {
			return false;
		}

		return true;
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
	 * Gets all possible membership levels.
	 *
	 * @return \WC_Memberships_Membership_Plan[]
	 */
	protected function get_membership_levels() {
		return wc_memberships_get_membership_plans();
	}
}
