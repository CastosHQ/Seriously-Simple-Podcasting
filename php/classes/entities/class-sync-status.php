<?php
/**
 * Sync_Status.
 *
 * @package SeriouslySimplePodcasting
 * */

namespace SeriouslySimplePodcasting\Entities;

/**
 * Class Sync_Status.
 * Abstract entity class.
 * @since 2.23.0
 */
class Sync_Status extends Abstract_Entity {

	const SYNC_STATUS_SYNCED = 'synced';
	const SYNC_STATUS_SYNCED_WITH_ERRORS = 'synced_with_errors';
	const SYNC_STATUS_FAILED = 'failed';
	const SYNC_STATUS_SYNCING = 'syncing';
	const SYNC_STATUS_NONE = 'none';

	/**
	 * @var string $status
	 * */
	public $status;

	/**
	 * @var string $title
	 * */
	public $title = '';

	/**
	 * @var string $message
	 * */
	public $message = '';

	/**
	 * @var string $error
	 * */
	public $error = '';

	public function __construct( $status ) {
		$statuses = self::get_available_sync_statuses();
		if ( ! array_key_exists( $status, $statuses ) ) {
			throw new \Exception( sprintf( 'Status %s is not registered!', $status ) );
		}

		parent::__construct( $statuses[ $status ] );
	}


	/**
	 * @return array
	 */
	public static function get_available_sync_statuses() {
		$statuses = array(
			self::SYNC_STATUS_SYNCED => array(
				'status'  => self::SYNC_STATUS_SYNCED,
				'title'   => __( 'Synced', 'seriously-simple-podcasting' ),
				'message' => __( 'Sync with Castos complete.', 'seriously-simple-podcasting' ),
			),
			self::SYNC_STATUS_FAILED  => array(
				'status'  => self::SYNC_STATUS_FAILED,
				'title'   => __( 'Failed', 'seriously-simple-podcasting' ),
				'message' => __( 'Could not sync with Castos.', 'seriously-simple-podcasting' ),
			),
			self::SYNC_STATUS_SYNCING => array(
				'status'  => self::SYNC_STATUS_SYNCING,
				'title'   => __( 'Syncing', 'seriously-simple-podcasting' ),
				'message' => __( 'Sending your episode and details to your Castos account.', 'seriously-simple-podcasting' ),
			),
			self::SYNC_STATUS_NONE                => array(
				'status'  => self::SYNC_STATUS_NONE,
				'title'   => __( 'Not synced', 'seriously-simple-podcasting' ),
				'message' => __( 'Not synced yet.', 'seriously-simple-podcasting' ),
			),
			self::SYNC_STATUS_SYNCED_WITH_ERRORS => array(
				'status'  => self::SYNC_STATUS_SYNCED_WITH_ERRORS,
				'title'   => __( 'Completed', 'seriously-simple-podcasting' ),
				'message' => __( 'Completed with errors.', 'seriously-simple-podcasting' ),
			),
		);

		return apply_filters( 'ssp_available_sync_statuses', $statuses );
	}
}
