<?php
/**
 * Paid Memberships Pro controller
 */

namespace SeriouslySimplePodcasting\Integrations;

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
abstract class Abstract_Integrator {

	/**
	 * @var array
	 * */
	protected $series_podcasts_map;

	/**
	 * @var array
	 * */
	protected $series_levels_map;

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

}
