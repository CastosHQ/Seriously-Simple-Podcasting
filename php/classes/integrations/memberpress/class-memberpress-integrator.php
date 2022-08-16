<?php
/**
 * MemberPress Integrator.
 */

namespace SeriouslySimplePodcasting\Integrations\Paid_Memberships_Pro;

use MeprCptModel;
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
class Memberpress_Integrator extends Abstract_Integrator {

	use Singleton;

	const ADD_LIST_OPTION = 'ssp_memberpress_add_subscribers';

	const REVOKE_LIST_OPTION = 'ssp_memberpress_revoke_subscribers';

	const EVENT_BULK_SYNC_SUBSCRIBERS = 'ssp_memberpress_bulk_sync_subscribers';

	const EVENT_ADD_SUBSCRIBERS = 'ssp_memberpress_add_subscribers';

	const EVENT_REVOKE_SUBSCRIBERS = 'ssp_memberpress_revoke_subscribers';

	const SINGLE_SYNC_DATA_OPTION = 'ssp_memberpress_single_sync_data';

	const SINGLE_SYNC_EVENT = 'ssp_memberpress_single_sync';

	/**
	 * @var Feed_Handler
	 * */
	protected $feed_handler;

	/**
	 * @var Castos_Handler
	 * */
	protected $castos_handler;

	/**
	 * @var Admin_Notifications_Handler
	 * */
	protected $notices_handler;

	/**
	 * @var Log_Helper
	 * */
	protected $logger;

	/**
	 * @var array
	 * */
	protected $castos_podcasts;


	/**
	 * Class Paid_Memberships_Pro_Integrator constructor.
	 *
	 * @param Feed_Handler $feed_handler
	 * @param Castos_Handler $castos_handler
	 * @param Log_Helper $logger
	 * @param Admin_Notifications_Handler $notices_handler
	 */
	public function init( $feed_handler, $castos_handler, $logger, $notices_handler ) {

		if ( ! $this->check_dependencies( array( 'MeprUser', 'MeprCptModel', 'MeprProduct', 'MeprDb' ) ) ) {
			return;
		}

		$this->feed_handler    = $feed_handler;
		$this->castos_handler  = $castos_handler;
		$this->logger          = $logger;
		$this->notices_handler = $notices_handler;

		if ( is_admin() && ! ssp_is_ajax() ) {
			$this->init_integration_settings();
		} else {
			$integration_enabled = ssp_get_option( 'enable_memberpress_integration' );
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
			// Option ss_podcasting_is_memberpress_integration is just a marker that PMPro integration settings have been saved.
			// If so, we can do the sync magic.
			if ( isset( $allowed_options['ss_podcasting'] ) ) {
				$key = array_search( 'ss_podcasting_is_memberpress_integration', $allowed_options['ss_podcasting'] );
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
		$this->listen_members_table_update();
		$this->listen_single_sync();
	}

	/**
	 * Do single sync as the separate event to not interfere with the DB update process.
	 * @see listen_members_table_update()
	 *
	 * @return void
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
						$this->schedule_single_sync( time() + 20 * MINUTE_IN_SECONDS );
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
	protected function listen_members_table_update(){
		add_filter( 'query', function ( $query ) {
			if ( false === strpos( $query, 'UPDATE' ) ) {
				return $query;
			}

			/**
			 * @var \MeprDb $mepr_db
			 * */
			$mepr_db = \MeprDb::fetch();

			// Does current query updates members table?
			if ( false === strpos( $query, $mepr_db->members ) ) {
				return $query;
			}

			// Lets get the user ID.
			preg_match( "#`user_id`='(\d*)#", $query, $matches );

			if ( empty( $matches[1] ) ) {
				return $query;
			}

			$user_id = $matches[1];

			// And now we can calculate the changes and schedule the sync process.
			$old_members_data = $mepr_db->get_one_record( $mepr_db->members, array( 'user_id' => $user_id ) );

			$old_memberships = $this->get_memberships( $old_members_data );
			$new_memberships = $this->get_user_memberships( $user_id );

			$revoked_memberships = array_diff( $old_memberships, $new_memberships );
			$added_memberships   = array_diff( $new_memberships, $old_memberships );

			$single_sync_data             = get_option( self::SINGLE_SYNC_DATA_OPTION, array() );
			$single_sync_data['users'][ $user_id ] = array(
				'added_memberships'   => $added_memberships,
				'revoked_memberships' => $revoked_memberships,
			);
			$single_sync_data['attempts'] = 0;
			update_option( self::SINGLE_SYNC_DATA_OPTION, $single_sync_data, false );
			$this->schedule_single_sync( time() );

			return $query;
		} );
	}

	/**
	 * Schedule single sync.
	 *
	 * @return void
	 */
	protected function schedule_single_sync( $time ){
		if ( ! wp_next_scheduled( self::SINGLE_SYNC_EVENT ) ) {
			wp_schedule_single_event( $time, self::SINGLE_SYNC_EVENT );
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
	 * @param object $member_data
	 *
	 * @return array
	 */
	protected function get_memberships( $member_data ) {
		$memberships = isset( $member_data->memberships ) ? $member_data->memberships : '';

		if ( strpos( $memberships, ',' ) ) {
			$memberships = explode( ',', $memberships );
		} else {
			$memberships = array( $memberships );
		}

		return array_filter( array_map( 'intval', $memberships ) );
	}

	/**
	 * Checks if bulk update has been started.
	 *
	 * @return int
	 */
	protected function bulk_update_started() {
		return wp_next_scheduled( self::EVENT_BULK_SYNC_SUBSCRIBERS ) ||
		       wp_next_scheduled( self::EVENT_ADD_SUBSCRIBERS ) ||
		       wp_next_scheduled( self::EVENT_REVOKE_SUBSCRIBERS );
	}

	/**
	 * Gets users series map.
	 *
	 * @return array
	 */
	protected function get_users_series_map() {
		return get_option( 'ss_memberpress_users_series_map', array() );
	}

	/**
	 * Schedule bulk sync subscribers.
	 */
	protected function schedule_bulk_sync_subscribers() {
		if ( ! wp_next_scheduled( self::EVENT_BULK_SYNC_SUBSCRIBERS ) ) {
			// 1. Save old users->series map: [['user_id' => ['series1', [series2]],]
			$this->update_users_series_map( $this->generate_users_series_map() );

			// 2. Schedule a task to add/revoke users
			wp_schedule_single_event( time(), self::EVENT_BULK_SYNC_SUBSCRIBERS );
		}
	}

	/**
	 * Updates users series map.
	 *
	 * @param array $map
	 *
	 * @return void
	 */
	protected function update_users_series_map( $map ) {
		update_option( 'ss_memberpress_users_series_map', $map, false );
	}

	/**
	 * Gets the map between users and related series [['2' => [3, 4]]].
	 *
	 * @return array
	 */
	protected function generate_users_series_map() {
		$map = array();

		$membership_users = $this->get_membership_users();

		foreach ( $membership_users as $user ) {
			$series = array();
			foreach ( $user['memberships'] as $membership_id ) {
				$series = array_merge( $series, $this->get_series_ids_by_level( $membership_id ) );
			}
			$map[ $user['ID'] ] = array_unique( $series );
		}

		return $map;
	}


	/**
	 * Bulk sync subscribers after settings change.
	 */
	public function bulk_sync_subscribers() {
		if ( $this->bulk_update_started() ) {

			// Another process is running, try to sync later.
			if ( ! wp_next_scheduled( self::EVENT_BULK_SYNC_SUBSCRIBERS ) ) {
				wp_schedule_single_event( time() + 5 * MINUTE_IN_SECONDS, self::EVENT_BULK_SYNC_SUBSCRIBERS );
			}

			return;
		}

		$old_map = $this->get_users_series_map();
		$new_map = $this->generate_users_series_map();

		$list_to_add    = array();
		$list_to_revoke = array();

		foreach ( $new_map as $user_id => $new_series ) {
			$old_series = isset( $old_map[ $user_id ] ) ? $old_map[ $user_id ] : array();

			$add_series = array_diff( $new_series, $old_series );
			foreach ( $add_series as $series_id ) {
				$list_to_add[ $series_id ][] = $user_id;
			}

			$revoke_series = array_diff( $old_series, $new_series );
			foreach ( $revoke_series as $series_id ) {
				$list_to_revoke[ $series_id ][] = $user_id;
			}
		}

		$list_to_add    = array_map( 'array_unique', $list_to_add );
		$list_to_revoke = array_map( 'array_unique', $list_to_revoke );

		update_option( self::ADD_LIST_OPTION, $list_to_add );
		update_option( self::REVOKE_LIST_OPTION, $list_to_revoke );

		$this->schedule_bulk_add_subscribers( 0 );
	}

	/**
	 * Schedule bulk add subscribers.
	 *
	 * @param int $delay Schedule delay in minutes
	 *
	 * @return void
	 */
	protected function schedule_bulk_add_subscribers( $delay = 5 ) {
		if ( ! wp_next_scheduled( self::EVENT_ADD_SUBSCRIBERS ) ) {
			wp_schedule_single_event( time() + $delay * MINUTE_IN_SECONDS, self::EVENT_ADD_SUBSCRIBERS );
			$this->logger->log( __METHOD__ . ' Scheduled bulk add subscribers.' );
		}
	}

	/**
	 * Schedule bulk revoke subscribers.
	 *
	 * @return void
	 */
	protected function schedule_bulk_revoke_subscribers( $delay = 5 ) {
		if ( ! wp_next_scheduled( self::EVENT_REVOKE_SUBSCRIBERS ) ) {
			wp_schedule_single_event( time() + $delay * MINUTE_IN_SECONDS, self::EVENT_REVOKE_SUBSCRIBERS );
			$this->logger->log( __METHOD__ . ' Scheduled bulk revoke subscribers.' );
		}
	}

	/**
	 * Bulk sync subscribers after settings change.
	 */
	public function bulk_add_subscribers() {
		// Always schedule next event till $list_to_add is not empty.
		$list_to_add = $updated_list_to_add = get_option( self::ADD_LIST_OPTION );

		// This block is needed just to make sure that if process dies another one will be started later
		if ( $list_to_add ) {
			// Schedule it one more time to make sure we don't stop till it's done.
			$this->schedule_bulk_add_subscribers();
		} else {
			$this->schedule_bulk_revoke_subscribers( 0 );

			return;
		}

		foreach ( $list_to_add as $series_id => $user_ids ) {
			$user_ids_chunked = array_chunk( $user_ids, 100 );
			foreach ( $user_ids_chunked as $k => $bunch ) {
				$count = $this->add_subscribers_to_podcast( $series_id, $bunch );

				if ( ! $count ) {
					// Something is wrong, let's wait for next run.
					$this->logger->log( __METHOD__ . 'Add subscribers error! $count: ' . $count );

					return;
				}

				// We successfully added subscribers, lets update our list
				unset( $user_ids_chunked[ $k ] );
				$updated_list_to_add[ $series_id ] = call_user_func_array( 'array_merge', $user_ids_chunked );
				update_option( self::ADD_LIST_OPTION, $updated_list_to_add );
			}

			unset( $updated_list_to_add[ $series_id ] );
			update_option( self::ADD_LIST_OPTION, $updated_list_to_add );
		}

		// We successfully finished the job, so we can remove the spare one, and schedule the next step
		wp_clear_scheduled_hook( self::EVENT_ADD_SUBSCRIBERS );
		$this->schedule_bulk_revoke_subscribers( 0 );
		$this->logger->log( __METHOD__ . 'Add subscribers process successfully finished!' );
	}

	/**
	 * Bulk sync subscribers after settings change.
	 */
	public function bulk_revoke_subscribers() {
		// Always schedule next event till $list_to_revoke is not empty.
		$list_to_revoke = $updated_list_to_revoke = get_option( self::REVOKE_LIST_OPTION );

		if ( $list_to_revoke ) {
			// Schedule it one more time to make sure we don't stop till it's done.
			$this->schedule_bulk_revoke_subscribers();
		} else {
			// Last step: do nothing, just log it.
			$this->logger->log( __METHOD__ . 'Bulk update successfully finished!' );

			return;
		}

		foreach ( $list_to_revoke as $series_id => $user_ids ) {
			$user_ids_chunked = array_chunk( $user_ids, 100 );
			foreach ( $user_ids_chunked as $k => $bunch ) {
				$count = $this->revoke_subscribers_from_podcast( $series_id, $bunch );

				if ( ! $count ) {
					// Something is wrong, let's wait for next run.
					$this->logger->log( __METHOD__ . 'Add subscribers error! $count: ' . $count );

					return;
				}

				// We successfully revoked subscribers, lets update our list
				unset( $user_ids_chunked[ $k ] );
				$updated_list_to_revoke[ $series_id ] = call_user_func_array( 'array_merge', $user_ids_chunked );
				update_option( self::REVOKE_LIST_OPTION, $updated_list_to_revoke );
			}
			unset( $updated_list_to_revoke[ $series_id ] );
			update_option( self::REVOKE_LIST_OPTION, $updated_list_to_revoke );
		}

		wp_clear_scheduled_hook( self::EVENT_REVOKE_SUBSCRIBERS );

		$this->logger->log( __METHOD__ . 'Revoke subscribers process successfully finished!' );
	}


	/**
	 * Gets IDs of all users who have any membership level.
	 *
	 * @return array
	 */
	protected function get_membership_users() {

		$params = array(
			'status' => 'active',
		);

		$list_table = \MeprUser::list_table( 'registered', 'DESC', 0, '', 'any', 0, $params );

		if ( empty( $list_table['results'] ) ) {
			return array();
		}

		$membership_users = array_map( function ( $user ) {
			return array(
				'ID'          => intval( $user->ID ),
				'memberships' => $this->get_memberships( $user ),
			);
		}, $list_table['results'] );

		return $membership_users;
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
		add_filter( 'mepr-last-chance-to-block-content', array( $this, 'protect_content' ), 10, 2 );
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
	 * This code was partially copied and modified from LLMS_Template_Loader::template_loader()
	 * */
	public function protect_content( $is_protected, $current_post ) {

		// We need to protect series and their episodes
		$current_series = $this->get_current_page_related_series( $current_post );

		if ( empty( $current_series ) ) {
			return $is_protected;
		}

		$protected_series = array();

		// We need to protect only private series
		foreach ( $current_series as $series ) {
			if ( $this->is_series_protected_in_castos( $series->term_id ) ) {
				$protected_series[] = $series;
			}
		}

		if ( empty( $protected_series ) ) {
			return $is_protected;
		}

		// Now we need to check if current user has access to all protected post series
		$user = wp_get_current_user();

		if ( $this->is_admin_user( $user ) ) {
			return $is_protected;
		}

		foreach ( $protected_series as $series ) {
			$series_level_ids = $this->get_series_level_ids( $series->term_id );
			if ( ! $this->has_access( $user, $series_level_ids ) ) {
				return true;
			}
		}

		return $is_protected;
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

		$user_level_ids = $this->get_user_memberships( $user->ID );

		return count( $user_level_ids ) && count( array_intersect( $user_level_ids, $required_level_ids ) );
	}


	/**
	 * @param int $user_id
	 *
	 * @return array
	 */
	protected function get_user_memberships( $user_id ){
		$member_data = \MeprUser::member_data( $user_id, [ 'memberships' ] );

		return $this->get_memberships( $member_data );
	}


	/**
	 * Gets series level ids.
	 *
	 * @param int $term_id
	 *
	 * @return int[]
	 */
	protected function get_series_level_ids( $term_id ) {
		$levels    = (array) ssp_get_option( sprintf( 'series_%s_memberpress_levels', $term_id ), null );
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
				$this->notices_handler->add_flash_notice( __( 'Synchronizing MemberPress data with Castos...', 'seriously-simple-podcasting' ) );
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
			'id'          => 'memberpress',
			'title'       => __( 'MemberPress', 'seriously-simple-podcasting' ),
			'description' => __( 'Select which Podcast you would like to be available only
								to Members via MemberPress.', 'seriously-simple-podcasting' ),
			'fields'      => array(
				array(
					'id'   => 'is_memberpress_integration',
					'type' => 'hidden',
				),
				array(
					'id'          => 'enable_memberpress_integration',
					'type'        => 'checkbox',
					'default'     => '',
					'label'       => __( 'Enable integration', 'seriously-simple-podcasting' ),
					'description' => __( 'Enable MemberPress integration', 'seriously-simple-podcasting' ),
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
			$levels_url              = admin_url( 'admin.php?page=pmpro-membershiplevels' );
			$settings['description'] = sprintf( __( 'To require membership to access a podcast please <a href="%s">set up a
										membership level</a> first.', 'seriously-simple-podcasting' ), $levels_url );

			return $settings;
		}

		$checkbox_options = array();

		foreach ( $levels as $level ) {
			$checkbox_options[ 'lvl_' . $level->ID ] = sprintf( 'Require %s to access', $level->post_title );
		}

		foreach ( $series as $series_item ) {
			$series_item_settings = array(
				'id'      => sprintf( 'series_%s_memberpress_levels', $series_item->term_id ),
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
	 * @return bool
	 */
	protected function needs_extended_integration_settings() {
		if ( ! ssp_get_option( 'enable_memberpress_integration' ) ) {
			return false;
		}

		$is_integration_page   = 'memberpress' === filter_input( INPUT_GET, 'integration' );
		$is_integration_update = 'memberpress' === filter_input( INPUT_POST, 'ssp_integration' );

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
	 * Gets all possible membership levels.
	 *
	 * @return array
	 */
	protected function get_membership_levels() {
		return MeprCptModel::all( 'MeprProduct' );
	}
}
