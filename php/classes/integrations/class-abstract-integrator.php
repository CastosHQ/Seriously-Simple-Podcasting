<?php
/**
 * Paid Memberships Pro controller
 */

namespace SeriouslySimplePodcasting\Integrations;

use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paid Memberships Pro controller
 *
 * @author Sergiy Zakharchenko
 * @package SeriouslySimplePodcasting
 * @since 2.9.3
 */
abstract class Abstract_Integrator {

	const BULK_UPDATE_STARTED = '';

	const ADD_LIST_OPTION = '';

	const REVOKE_LIST_OPTION = '';

	const EVENT_BULK_SYNC_SUBSCRIBERS = '';

	const EVENT_ADD_SUBSCRIBERS = '';

	const EVENT_REVOKE_SUBSCRIBERS = '';


	/**
	 * @var array
	 * */
	protected $series_podcasts_map;

	/**
	 * @var array
	 * */
	protected $series_levels_map;

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
	 * Adds integrations settings
	 *
	 * @param array $args
	 */
	public function add_integration_settings( $args ) {
		add_filter( 'ssp_integration_settings', function ( $settings ) use ( $args ) {
			$settings['items'][ $args['id'] ] = $args;

			return $settings;
		} );
	}

	/**
	 * Checks if all needed classes and functions exist.
	 *
	 * @return bool
	 */
	protected function check_dependencies( $classes, $functions = array() ) {
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
	 * Gets array of all available series terms.
	 *
	 * @return \WP_Term[]|\WP_Error
	 */
	protected function get_series() {
		return get_terms( 'series', array( 'hide_empty' => false ) );
	}

	/**
	 * Gets array of episode series terms.
	 *
	 * @param $post_id
	 *
	 * @return \WP_Term[]
	 */
	protected function get_episode_series( $post_id ) {
		$series = wp_get_post_terms( $post_id, 'series' );

		if ( is_wp_error( $series ) ) {
			return [];
		}

		return $series;
	}

	/**
	 * @return \WP_Term[]
	 */
	protected function get_current_page_related_series( $post = null ) {
		// First, lets check if it's series page
		$queried = get_queried_object();

		if ( isset( $queried->taxonomy ) && 'series' === $queried->taxonomy ) {
			return array( $queried );
		}

		// If it's episode page, get related series
		if ( ! $post ) {
			global $post;
		}


		if( isset( $post->post_type ) && SSP_CPT_PODCAST === $post->post_type ){
			return $this->get_episode_series( $post->ID );
		}

		return array();
	}

	/**
	 * Gets the map between series and podcasts.
	 *
	 * @return array
	 */
	protected function get_series_podcasts_map() {
		if ( $this->series_podcasts_map ) {
			return $this->series_podcasts_map;
		}

		$podcasts = $this->castos_handler->get_podcasts();

		$map = array();

		if ( empty( $podcasts['data']['podcast_list'] ) ) {
			$this->logger->log( __METHOD__ . ': Error: empty podcasts!' );

			return $map;
		}

		foreach ( $podcasts['data']['podcast_list'] as $podcast ) {
			$map[ $podcast['series_id'] ] = $podcast['id'];
		}

		$this->series_podcasts_map = $map;

		return $map;
	}

	/**
	 * Gets IDs of the series attached to the Membership Level.
	 *
	 * @param int $level_id
	 *
	 * @return array
	 */
	protected function get_series_ids_by_level( $level_id ) {

		if ( isset( $this->series_levels_map[ $level_id ] ) ) {
			return $this->series_levels_map[ $level_id ];
		}

		$series_ids = array();

		if ( empty( $level_id ) ) {
			return $series_ids;
		}

		$series_terms = $this->get_series();

		if ( is_wp_error( $series_terms ) ) {
			$this->logger->log( __METHOD__ . sprintf( ': Could not get terms for level: %s!', $level_id ) );

			return $series_ids;
		}

		foreach ( $series_terms as $series ) {
			$levels_ids = $this->get_series_level_ids( $series->term_id );

			if ( in_array( $level_id, $levels_ids ) ) {
				$series_ids[] = $series->term_id;
			}
		}

		$this->series_levels_map[ $level_id ] = $series_ids;

		return $series_ids;
	}

	/**
	 * @param int $user_id
	 * @param int[] $revoke_series_ids
	 * @param int[] $add_series_ids
	 */
	protected function sync_user( $user_id, $revoke_series_ids, $add_series_ids ) {
		$user = get_user_by( 'id', $user_id );
		$success = true;

		if ( ! $user ) {
			return false;
		}

		if ( $revoke_series_ids ) {
			$this->logger->log( __METHOD__ . sprintf( ': Revoke user %s from series %s', $user->user_email, json_encode( $revoke_series_ids ) ) );
			$revoke_res = $this->revoke_subscriber_from_podcasts( $user, $revoke_series_ids );
			$this->logger->log( __METHOD__ . ': Revoke result', $revoke_res );

			// Something went wrong.
			if ( null === $revoke_res ) {
				$this->logger->log( __METHOD__ . sprintf( ': Could not revoke user %s from series %s', $user->user_email, json_encode( $revoke_series_ids ) ) );
				$success = false;
			}
		}

		if ( $add_series_ids ) {
			$this->logger->log( __METHOD__ . sprintf( ': Add user %s to series %s', $user->user_email, json_encode( $add_series_ids ) ) );
			$add_res = $this->add_subscriber_to_podcasts( $user, $add_series_ids );
			$this->logger->log( __METHOD__ . ': Add result', $add_res );

			// Something went wrong.
			if ( null === $add_res ) {
				$this->logger->log( __METHOD__ . sprintf( ': Could not add user %s to series %s', $user->user_email, json_encode( $add_series_ids ) ) );
				$success = false;
			}
		}

		return $success;
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
	 * Adds subscriber to multiple Castos podcasts.
	 *
	 * @param int $series_id
	 * @param int[] $user_ids
	 *
	 * @return int
	 */
	protected function add_subscribers_to_podcast( $series_id, $user_ids ) {
		$podcast_ids = $this->convert_series_ids_to_podcast_ids( array( $series_id ) );

		$subscribers = array();

		$users_data = $this->get_users_data( $user_ids );

		foreach ( $user_ids as $user_id ) {
			if ( empty( $users_data[ $user_id ] ) ) {
				$this->logger->log( __METHOD__ . ' Error: could not get user by id: ' . $user_id );
			}
			$subscribers[] = array(
				'name'  => $users_data[ $user_id ]['display_name'],
				'email' => $users_data[ $user_id ]['user_email'],
			);
		}

		$count = $this->castos_handler->add_subscribers_to_podcasts( $podcast_ids, $subscribers );

		$this->logger->log( __METHOD__ . ' Added subscribers: ' . $count );

		return $count;
	}

	/**
	 * Adds subscriber to multiple Castos podcasts.
	 *
	 * @param int $series_id
	 * @param int[] $user_ids
	 *
	 * @return int
	 */
	protected function revoke_subscribers_from_podcast( $series_id, $user_ids ) {
		$podcast_ids = $this->convert_series_ids_to_podcast_ids( array( $series_id ) );

		$emails = array();

		$user_data = $this->get_users_data( $user_ids );

		foreach ( $user_ids as $user_id ) {
			if ( empty( $user_data[ $user_id ] ) ) {
				$this->logger->log( __METHOD__ . ' Error: could not get user by id: ' . $user_id );
			}
			$emails[] = $user_data[ $user_id ]['user_email'];
		}

		$count = $this->castos_handler->revoke_subscribers_from_podcasts( $podcast_ids, $emails );

		$this->logger->log( __METHOD__ . ' Revoked subscribers: ' . $count );

		return $count;
	}

	/**
	 * @param int[] $user_ids
	 *
	 * @return array
	 */
	protected function get_users_data( $user_ids ) {
		global $wpdb;
		$query = sprintf(
			'SELECT `ID`, `user_email`, `display_name` FROM %s WHERE `ID` IN(%s)',
			$wpdb->users,
			implode( ',', $user_ids )
		);

		$rows = $wpdb->get_results( $query, ARRAY_A );

		$users_data = array();

		foreach ( $rows as $row ) {
			$users_data[ $row['ID'] ] = array(
				'user_email'   => $row['user_email'],
				'display_name' => $row['display_name'],
			);
		}

		return $users_data;
	}

	/**
	 * @param \WP_User $user
	 *
	 * @return bool
	 */
	protected function is_admin_user( $user ) {
		return user_can( $user, 'manage_options' );
	}

	/**
	 * Schedule bulk sync subscribers.
	 *
	 * Steps:
	 * 1. When user changes the global podcasts => membership levels settings, we schedule new bulk sync.
	 * 2. When we schedule it, we generate the users => podcasts(series) map, that existed before saving those settings.
	 * 3. If any of the scheduled bulk sync jobs exist, we show a bulk updating notice on settings pages.
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
	 */
	protected function schedule_bulk_sync_subscribers() {
		if ( ! wp_next_scheduled( static::EVENT_BULK_SYNC_SUBSCRIBERS ) ) {
			// 1. Save old users->series map: [['user_id' => ['series1', [series2]],]
			$this->update_users_series_map( $this->generate_users_series_map() );

			// 2. Schedule a task to add/revoke users
			wp_schedule_single_event( time(), static::EVENT_BULK_SYNC_SUBSCRIBERS );
		}
	}

	/**
	 * Schedule bulk add subscribers.
	 *
	 * @param int $delay Schedule delay in minutes
	 *
	 * @return void
	 */
	protected function schedule_bulk_add_subscribers( $delay = 5 ) {
		if ( ! wp_next_scheduled( static::EVENT_ADD_SUBSCRIBERS ) ) {
			wp_schedule_single_event( time() + $delay * MINUTE_IN_SECONDS, static::EVENT_ADD_SUBSCRIBERS );
			$this->logger->log( __METHOD__ . ' Scheduled bulk add subscribers.' );
		}
	}

	/**
	 * Schedule bulk revoke subscribers.
	 *
	 * @return void
	 */
	protected function schedule_bulk_revoke_subscribers( $delay = 5 ) {
		if ( ! wp_next_scheduled( static::EVENT_REVOKE_SUBSCRIBERS ) ) {
			wp_schedule_single_event( time() + $delay * MINUTE_IN_SECONDS, static::EVENT_REVOKE_SUBSCRIBERS );
			$this->logger->log( __METHOD__ . ' Scheduled bulk revoke subscribers.' );
		}
	}

	/**
	 * Bulk sync subscribers after settings change.
	 */
	public function bulk_sync_subscribers() {
		if ( $this->bulk_update_started() ) {

			// Another process is running, try to sync later.
			if ( ! wp_next_scheduled( static::EVENT_BULK_SYNC_SUBSCRIBERS ) ) {
				wp_schedule_single_event( time() + 5 * MINUTE_IN_SECONDS, static::EVENT_BULK_SYNC_SUBSCRIBERS );
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

		update_option( static::ADD_LIST_OPTION, $list_to_add );
		update_option( static::REVOKE_LIST_OPTION, $list_to_revoke );

		$this->schedule_bulk_add_subscribers( 0 );
	}

	/**
	 * Bulk sync subscribers after settings change.
	 */
	public function bulk_add_subscribers() {
		// Always schedule next event till $list_to_add is not empty.
		$list_to_add = $updated_list_to_add = get_option( static::ADD_LIST_OPTION );

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
				update_option( static::ADD_LIST_OPTION, $updated_list_to_add );
			}

			unset( $updated_list_to_add[ $series_id ] );
			update_option( static::ADD_LIST_OPTION, $updated_list_to_add );
		}

		// We successfully finished the job, so we can remove the spare one, and schedule the next step
		wp_clear_scheduled_hook( static::EVENT_ADD_SUBSCRIBERS );
		$this->schedule_bulk_revoke_subscribers( 0 );
		$this->logger->log( __METHOD__ . 'Add subscribers process successfully finished!' );
	}

	/**
	 * Bulk sync subscribers after settings change.
	 */
	public function bulk_revoke_subscribers() {
		// Always schedule next event till $list_to_revoke is not empty.
		$list_to_revoke = $updated_list_to_revoke = get_option( static::REVOKE_LIST_OPTION );

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
				update_option( static::REVOKE_LIST_OPTION, $updated_list_to_revoke );
			}
			unset( $updated_list_to_revoke[ $series_id ] );
			update_option( static::REVOKE_LIST_OPTION, $updated_list_to_revoke );
		}

		wp_clear_scheduled_hook( static::EVENT_REVOKE_SUBSCRIBERS );

		$this->logger->log( __METHOD__ . 'Revoke subscribers process successfully finished!' );
		$this->notices_handler->add_flash_notice( $this->get_successfully_finished_notice(), 'success' );
	}

	/**
	 * @return string
	 */
	protected function get_successfully_finished_notice(){
		return __( 'Subscribers data successfully synchronized!', 'seriously-simple-podcasting' );
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
	 * Checks if bulk update has been started.
	 *
	 * @return int
	 */
	protected function bulk_update_started() {
		return wp_next_scheduled( static::EVENT_BULK_SYNC_SUBSCRIBERS ) ||
		       wp_next_scheduled( static::EVENT_ADD_SUBSCRIBERS ) ||
		       wp_next_scheduled( static::EVENT_REVOKE_SUBSCRIBERS );
	}
}
