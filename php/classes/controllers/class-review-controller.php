<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

/**
 * SSP Settings
 *
 * @package Seriously Simple Podcasting
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SettingsController class
 *
 * Handles plugin settings page
 *
 * @author      Sergiy Zakharchenko
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.15.0
 */
class Review_Controller {

	use Useful_Variables;

	const REVIEW_STATUS_OPTION = 'review_status';

	const INITIAL_DELAY = 30 * DAY_IN_SECONDS;

	const LATER_DELAY = 30 * DAY_IN_SECONDS;

	const STATUS_DISMISS = 'dismiss';

	const STATUS_REVIEWED = 'reviewed';

	const STATUS_REVIEW = 'review';

	const STATUS_LATER = 'later';


	/**
	 * @var Admin_Notifications_Handler
	 * */
	protected $notices_handler;

	/**
	 * @var Renderer
	 * */
	protected $renderer;

	/**
	 * Review_Controller constructor.
	 *
	 * @param Admin_Notifications_Handler $notices_handler
	 */
	public function __construct( $notices_handler, $renderer ) {
		if ( ! is_admin() || ! $this->is_podcast_related_request() ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			$this->init_ajax();

			return;
		}

		$this->init_useful_variables();

		$this->notices_handler = $notices_handler;
		$this->renderer        = $renderer;

		$this->init_frontend();
	}

	/**
	 * Checks if the admin page is podcast related.
	 *
	 * @return bool
	 */
	protected function is_podcast_related_request() {
		return filter_input( INPUT_GET, 'post_type' ) === SSP_CPT_PODCAST ||
		       filter_input( INPUT_POST, 'action' ) === 'ssp_review_notice_status';
	}

	/**
	 * Init ajax actions.
	 *
	 * @return void
	 */
	public function init_ajax() {
		add_action( 'wp_ajax_ssp_review_notice_status', function () {
			$status = filter_input( INPUT_POST, 'status' );
			$nonce  = filter_input( INPUT_POST, 'nonce' );

			$allowed_statuses = array(
				self::STATUS_DISMISS,
				self::STATUS_REVIEWED,
				self::STATUS_REVIEW,
				self::STATUS_LATER,
			);

			if ( ! wp_verify_nonce( $nonce, 'ssp_review_notice_' . $status ) || ! in_array( $status, $allowed_statuses ) ) {
				wp_send_json_error();
			}

			switch ( $status ) {
				case self::STATUS_LATER:
					$this->schedule_review_notice( self::LATER_DELAY );
					break;
				default:
					$this->update_review_status( $status );
			}

			wp_send_json_success();
		} );
	}

	/**
	 * Init frontend part.
	 * */
	public function init_frontend() {
		if ( ! $this->check_review_notice_status() ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'display_review_notice' ), 12 );
		add_action( 'admin_init', function () {
			wp_enqueue_script( 'ssp_review', $this->assets_url . 'admin/js/review.js', [ 'jquery' ] );
		} );
	}

	/**
	 * Checks if we need to show the review request or not, and schedules the showing if needed.
	 * */
	public function check_review_notice_status(){
		$status = ssp_get_option( self::REVIEW_STATUS_OPTION );

		if ( 0 === strpos( $status, 'start_since_' ) ) {
			$start_time = intval( str_replace( 'start_since_', '', $status ) );
			$current_time = time();
			if ( $current_time > $start_time ) {
				return true;
			}
		}

		if ( ! $status ) {
			$this->schedule_review_notice( self::INITIAL_DELAY );
		}

		return false;
	}

	/**
	 * Displays review notice.
	 * */
	public function display_review_notice() {
		$this->renderer->render( 'review-notice' );
	}

	/**
	 * Disables review notice.
	 * */
	protected function update_review_status( $status ) {
		ssp_update_option( self::REVIEW_STATUS_OPTION, $status );
	}

	/**
	 * Schedules the notice showing.
	 *
	 * @param int $delay Delay in seconds.
	 */
	protected function schedule_review_notice( $delay ) {
		$time = time() + $delay;
		$status = 'start_since_' . $time;

		ssp_update_option( self::REVIEW_STATUS_OPTION, $status );
	}
}
