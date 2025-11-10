<?php
/**
 * DB migration controller class file.
 *
 * @package Seriously Simple Podcasting
 */

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Traits\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration controller
 * Checks if the plugin needs to run a database migration after the plugin updates
 *
 * @package Seriously Simple Podcasting
 * @author Serhiy Zakharchenko
 * @since 2.9.3
 */
class DB_Migration_Controller {

	use Singleton;

	/**
	 * Admin notifications handler instance.
	 *
	 * @var Admin_Notifications_Handler
	 */
	protected $admin_notices_handler;

	/**
	 * Initializes the migration controller.
	 *
	 * @param Admin_Notifications_Handler $admin_notices_handler Admin notifications handler instance.
	 *
	 * @return self
	 */
	public function init( $admin_notices_handler = null ) {
		if ( $admin_notices_handler ) {
			$this->admin_notices_handler = $admin_notices_handler;
		}

		add_action( 'admin_init', array( $this, 'maybe_migrate_db' ) );
		add_action( 'admin_init', array( $this, 'maybe_check_duplicate_guids' ) );
		add_action( 'admin_init', array( $this, 'handle_fix_duplicate_guids_action' ) );
		add_action( 'wp_ajax_remove_constant_notice', array( $this, 'handle_notice_dismissal' ), 5 );
		add_filter( 'ssp_constant_notices', array( $this, 'update_notice_nonce' ), 10, 1 );

		return $this;
	}

	/**
	 * Checks if database migration is needed and runs it.
	 *
	 * @return void
	 */
	public function maybe_migrate_db() {
		$db_version = get_option( 'ssp_db_version' );
		if ( $db_version === SSP_VERSION ) {
			return;
		}

		switch ( SSP_VERSION ) {
			case '2.9.3':
				$this->update_date_recorded();
				break;
		}

		update_option( 'ssp_db_version', SSP_VERSION, false );
	}

	/**
	 * Updates date_recorded format from dd-mm-YYYY to YYYY-mm-dd.
	 *
	 * Unfortunately, the old format dd-mm-YYYY doesn't allow ordering episodes by date in the query.
	 * So, we need to update it to YYYY-mm-dd format.
	 *
	 * @since 2.9.3
	 *
	 * @return void
	 */
	protected function update_date_recorded() {
		$args = array(
			'post_type'      => ssp_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
		);

		$query = new \WP_Query( $args );

		foreach ( $query->posts as $post ) {
			$date_recorded = get_post_meta( $post->ID, 'date_recorded', true );

			$time = $date_recorded ? strtotime( $date_recorded ) : strtotime( $post->post_date );

			$date_recorded = wp_date( 'Y-m-d', $time );

			update_post_meta( $post->ID, 'date_recorded', $date_recorded );
		}
	}

	/**
	 * Checks if duplicate GUID options exist, and runs initial check if they don't.
	 * Also shows notice if duplicates are found and conditions are met.
	 * This runs on every admin page load to ensure the check is performed at least once.
	 *
	 * @since 3.14.0
	 * @todo Remove this feature after new year (2026) - it was a one-time fix for duplicate GUIDs from post duplicator plugins.
	 *
	 * @return void
	 */
	public function maybe_check_duplicate_guids() {
		// Check if the option exists in the database
		// get_option() returns false if option doesn't exist OR if it's set to false
		// So we need to check if it was explicitly set by comparing with a sentinel value
		$duplicate_guids_found = get_option( 'ssp_duplicate_guids_found', 'not_set' );

		// If option doesn't exist (returns our sentinel value), run the initial check
		if ( 'not_set' === $duplicate_guids_found ) {
			$duplicate_guids_found = $this->check_duplicate_guids();
		}

		// Show notice if duplicates are found and conditions are met
		if ( $duplicate_guids_found ) {
			$this->maybe_add_duplicate_guids_notice();
		}
	}

	/**
	 * Checks for duplicate GUIDs in episodes from January 1, 2025 onwards.
	 * Identifies episodes from October 5, 2025 onwards that have duplicate GUIDs.
	 * Stores the result in the ssp_duplicate_guids_found option.
	 *
	 * @since 3.14.0
	 * @todo Remove this feature after new year (2026) - it was a one-time fix for duplicate GUIDs from post duplicator plugins.
	 *
	 * @return bool True if duplicates are found, false otherwise.
	 */
	protected function check_duplicate_guids() {
		// Get episodes from 2025-01-01 onwards
		$episodes = $this->get_episodes_from_date( '2025-10-05' );

		// Build GUID map from episodes
		$guid_map = $this->build_guid_map( $episodes );

		if ( empty( $guid_map ) ) {
			update_option( 'ssp_duplicate_guids_found', false );
			return false;
		}

		$duplicates_found = false;

		foreach ( $episodes as $episode ) {
			$guid = get_post_meta( $episode->ID, 'ssp_guid', true );

			if ( empty( $guid ) ) {
				continue;
			}

			// Check if this GUID exists in the map with other episode IDs
			if ( isset( $guid_map[ $guid ] ) && count( $guid_map[ $guid ] ) > 1 ) {
				// Check if current episode ID is in the array
				if ( in_array( $episode->ID, $guid_map[ $guid ], true ) ) {
					$duplicates_found = true;
					break;
				}
			}
		}

		// Store the result
		update_option( 'ssp_duplicate_guids_found', $duplicates_found );

		return $duplicates_found;
	}

	/**
	 * Builds a GUID map from an array of episodes.
	 * Map structure: [guid => [episode_id1, episode_id2, ...]]
	 *
	 * @since 3.14.0
	 *
	 * @param array $episodes Array of episode post objects.
	 *
	 * @return array GUID map structure: [guid => [episode_id1, episode_id2, ...]]
	 */
	protected function build_guid_map( $episodes ) {
		$guid_map = array();

		foreach ( $episodes as $episode ) {
			$guid = get_post_meta( $episode->ID, 'ssp_guid', true );

			if ( empty( $guid ) ) {
				continue;
			}

			$guid_map[ $guid ][] = $episode->ID;
		}

		return $guid_map;
	}

	/**
	 * Gets episodes from the specified date onwards.
	 *
	 * @since 3.14.0
	 *
	 * @param string $from_date Start date in YYYY-MM-DD format.
	 * @param string $order     Order direction. 'DESC' for reverse chronological (latest first), 'ASC' for chronological. Default 'DESC'.
	 *
	 * @return \WP_Post[] Array of episode posts.
	 */
	protected function get_episodes_from_date( $from_date, $order = 'DESC' ) {
		$args = array(
			'post_type'      => ssp_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => $order,
			'date_query'     => array(
				array(
					'after'     => $from_date,
					'inclusive' => true,
				),
			),
		);

		$query = new \WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Gets the duplicate GUID notice message with fix link.
	 *
	 * @since 3.14.0
	 * @todo Remove this feature after new year (2026) - it was a one-time fix for duplicate GUIDs from post duplicator plugins.
	 *
	 * @param string $nonce Nonce value or placeholder. Default is '__NONCE__' placeholder.
	 *
	 * @return string Notice message HTML.
	 */
	protected function get_duplicated_guid_notice_message( $nonce = '__NONCE__' ) {
		$fix_url = add_query_arg(
			array(
				'post_type'  => SSP_CPT_PODCAST,
				'page'       => 'podcast_settings',
				'ssp_action' => 'fix_duplicate_guids',
				'nonce'      => $nonce,
			),
			admin_url( 'edit.php' )
		);

		return sprintf(
			// translators: %1$s is the link to fix duplicate GUIDs
			__( 'We noticed some of your episodes have duplicate GUIDs, likely from post duplicator plugins. Episodes having duplicate GUIDs are ignored by podcast directories. %1$s', 'seriously-simple-podcasting' ),
			'<a href="' . esc_url( $fix_url ) . '">' . __( 'Click here to automatically update these episodes with unique GUIDs.', 'seriously-simple-podcasting' ) . '</a>'
		);
	}

	/**
	 * Adds constant dismissible notice when duplicate GUIDs are found.
	 * Checks all three flags before adding the notice.
	 *
	 * @since 3.14.0
	 * @todo Remove this feature after new year (2026) - it was a one-time fix for duplicate GUIDs from post duplicator plugins.
	 *
	 * @return void
	 */
	protected function maybe_add_duplicate_guids_notice() {

		// Check if fix has been completed
		$fix_completed = get_option( 'ssp_duplicate_guids_fix_completed', false );
		if ( $fix_completed ) {
			return;
		}

		// Check if notice has been dismissed
		$notice_dismissed = get_option( 'ssp_duplicate_guids_notice_dismissed', false );
		if ( $notice_dismissed ) {
			return;
		}

		// Get notice message with __NONCE__ placeholder (will be replaced when displayed)
		$notice_message = $this->get_duplicated_guid_notice_message();

		$this->admin_notices_handler->add_constant_notice( $notice_message, Admin_Notifications_Handler::WARNING );
	}

	/**
	 * Updates the nonce in the duplicate GUID notice URL when notices are displayed.
	 * Replaces the __NONCE__ placeholder with a fresh nonce.
	 *
	 * @since 3.14.0
	 * @todo Remove this feature after new year (2026) - it was a one-time fix for duplicate GUIDs from post duplicator plugins.
	 *
	 * @param array $notices Array of constant notices.
	 *
	 * @return array Modified notices array.
	 */
	public function update_notice_nonce( $notices ) {
		// Use stable URL pattern to identify our notice (works in all languages)
		$url_pattern = 'ssp_action=fix_duplicate_guids';

		foreach ( $notices as $hash => $notice ) {
			if ( isset( $notice['notice'] ) && false !== strpos( $notice['notice'], $url_pattern ) ) {
				// Replace __NONCE__ placeholder with a fresh nonce
				$fresh_nonce = wp_create_nonce( 'fix_duplicate_guids' );
				$notices[ $hash ]['notice'] = str_replace( '__NONCE__', $fresh_nonce, $notices[ $hash ]['notice'] );
			}
		}

		return $notices;
	}

	/**
	 * Handles notice dismissal to update the ssp_duplicate_guids_notice_dismissed option.
	 * This runs before the default AJAX handler to check if our notice is being dismissed.
	 *
	 * @since 3.14.0
	 * @todo Remove this feature after new year (2026) - it was a one-time fix for duplicate GUIDs from post duplicator plugins.
	 *
	 * @return void
	 */
	public function handle_notice_dismissal() {
		if ( ! isset( $_POST['id'] ) || ! isset( $_POST['nonce'] ) ) {
			return;
		}

		$dismissed_notice_id = sanitize_text_field( wp_unslash( $_POST['id'] ) );
		$nonce               = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

		// Verify nonce
		if ( ! wp_verify_nonce( $nonce, 'notice-' . $dismissed_notice_id ) ) {
			return;
		}

		// Get all constant notices to check if the dismissed one is ours
		$constant_notices = $this->admin_notices_handler->get_constant_notices();

		if ( ! isset( $constant_notices[ $dismissed_notice_id ] ) ) {
			return;
		}

		update_option( 'ssp_duplicate_guids_notice_dismissed', true );
	}

	/**
	 * Handles the fix_duplicate_guids admin action.
	 * Verifies nonce and calls the fix method.
	 *
	 * @since 3.14.0
	 * @todo Remove this feature after new year (2026) - it was a one-time fix for duplicate GUIDs from post duplicator plugins.
	 *
	 * @return void
	 */
	public function handle_fix_duplicate_guids_action() {
		// Check if this is the action we're handling
		$action = isset( $_GET['ssp_action'] ) ? sanitize_text_field( wp_unslash( $_GET['ssp_action'] ) ) : '';
		if ( 'fix_duplicate_guids' !== $action ) {
			return;
		}

		// Verify nonce
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'fix_duplicate_guids' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'seriously-simple-podcasting' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_podcast' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'seriously-simple-podcasting' ) );
		}

		// Call the fix method
		$updated_episode_ids = $this->fix_duplicate_guids();

		// Store fix completed flag
		update_option( 'ssp_duplicate_guids_fix_completed', true );

		// Remove constant notice
		$hash = $this->admin_notices_handler->get_notice_hash( $this->get_duplicated_guid_notice_message() );
		$this->admin_notices_handler->remove_constant_notice( $hash );

		// Show success flash notice with updated episode IDs
		if ( ! empty( $updated_episode_ids ) && $this->admin_notices_handler ) {
			$this->show_fix_success_notice( $updated_episode_ids );
		}

		// Redirect to current page without action parameters to prevent action from being triggered again on refresh
		$redirect_url = remove_query_arg( array( 'ssp_action', 'nonce' ) );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Shows success notice after duplicate GUIDs have been fixed.
	 *
	 * @since 3.14.0
	 * @todo Remove this feature after new year (2026) - it was a one-time fix for duplicate GUIDs from post duplicator plugins.
	 *
	 * @param array $updated_episode_ids Array of updated episode IDs.
	 *
	 * @return void
	 */
	protected function show_fix_success_notice( $updated_episode_ids ) {
		if ( ! $this->admin_notices_handler ) {
			return;
		}

		$episode_ids_list = implode( ', ', $updated_episode_ids );
		$success_message  = sprintf(
			// translators: %s is a comma-separated list of episode IDs
			__( 'Duplicate GUIDs have been fixed. Updated episodes: %s', 'seriously-simple-podcasting' ),
			$episode_ids_list
		);
		$this->admin_notices_handler->add_flash_notice( $success_message, Admin_Notifications_Handler::SUCCESS );
	}

	/**
	 * Fixes duplicate GUIDs by regenerating them for affected episodes.
	 * Processes episodes from October 5, 2025 onwards in reverse chronological order.
	 *
	 * @since 3.14.0
	 * @todo Remove this feature after new year (2026) - it was a one-time fix for duplicate GUIDs from post duplicator plugins.
	 *
	 * @return array Array of updated episode IDs.
	 */
	protected function fix_duplicate_guids() {
		// Get episodes from 2025-10-05 onwards in reverse chronological order (latest first)
		$episodes = $this->get_episodes_from_date( '2025-10-05', 'DESC' );

		if ( empty( $episodes ) ) {
			return array();
		}

		// Build GUID map using the episodes array
		$guid_map = $this->build_guid_map( $episodes );

		if ( empty( $guid_map ) ) {
			return array();
		}

		$updated_episode_ids = array();

		foreach ( $episodes as $episode ) {
			$guid = get_post_meta( $episode->ID, 'ssp_guid', true );

			if ( empty( $guid ) ) {
				continue;
			}

			if ( isset( $guid_map[ $guid ] ) && count( $guid_map[ $guid ] ) > 1 ) {
				if ( in_array( $episode->ID, $guid_map[ $guid ], true ) ) {
					$old_guid = $guid;
					$new_guid = ssp_generate_episode_guid( $episode );

					if ( $new_guid !== $old_guid ) {
						$this->update_episode_guid( $episode->ID, $old_guid, $new_guid );
						$updated_episode_ids[] = $episode->ID;
					}
				}
			}

			// Remove current episode ID from map after checking
			// This ensures the original episode keeps its GUID
			if ( isset( $guid_map[ $guid ] ) ) {
				$key = array_search( $episode->ID, $guid_map[ $guid ], true );
				if ( false !== $key ) {
					unset( $guid_map[ $guid ][ $key ] );
					// Re-index array
					$guid_map[ $guid ] = array_values( $guid_map[ $guid ] );
				}
			}
		}

		return $updated_episode_ids;
	}


	/**
	 * Updates episode GUID and logs the change.
	 *
	 * @since 3.14.0
	 *
	 * @param int    $episode_id Episode post ID.
	 * @param string $old_guid   Previous GUID value.
	 * @param string $new_guid   New GUID value.
	 *
	 * @return void
	 */
	protected function update_episode_guid( $episode_id, $old_guid, $new_guid ) {
		update_post_meta( $episode_id, 'ssp_guid', $new_guid );

		error_log(
			sprintf(
				'SSP Duplicate GUID Fix: Episode ID %d - Previous episodes found with same GUID. Old GUID: %s, New GUID: %s',
				$episode_id,
				$old_guid,
				$new_guid
			)
		);
	}
}
