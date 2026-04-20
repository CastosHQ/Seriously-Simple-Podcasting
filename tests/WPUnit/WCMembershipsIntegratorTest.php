<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator;

class WCMembershipsIntegratorTest extends \Codeception\TestCase\WPTestCase {

	protected function setUp(): void {
		parent::setUp();
		delete_option( WC_Memberships_Integrator::SINGLE_SYNC_DATA_OPTION );
	}

	protected function tearDown(): void {
		delete_option( WC_Memberships_Integrator::SINGLE_SYNC_DATA_OPTION );
		wp_clear_scheduled_hook( WC_Memberships_Integrator::SINGLE_SYNC_EVENT );
		parent::tearDown();
	}

	/**
	 * Builds a stub membership with the given status, user_id, and plan_id.
	 */
	protected function makeMembership( string $status, int $userId, int $planId ) {
		return new class( $status, $userId, $planId ) {
			private $status;
			private $user_id;
			private $plan_id;

			public function __construct( $status, $userId, $planId ) {
				$this->status  = $status;
				$this->user_id = $userId;
				$this->plan_id = $planId;
			}

			public function get_status() {
				return $this->status;
			}

			public function get_user_id() {
				return $this->user_id;
			}

			public function get_plan_id() {
				return $this->plan_id;
			}
		};
	}

	/**
	 * Invokes a protected method on the integrator via reflection.
	 */
	protected function invokeMethod( string $method, array $args = [] ) {
		$integrator = WC_Memberships_Integrator::instance();
		$ref        = new \ReflectionMethod( $integrator, $method );
		$ref->setAccessible( true );
		return $ref->invokeArgs( $integrator, $args );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator::sync_membership()
	 */
	public function testSyncMembershipQueuesAddForActiveMembership() {
		$this->invokeMethod( 'sync_membership', [ $this->makeMembership( 'active', 42, 100 ) ] );

		$data = get_option( WC_Memberships_Integrator::SINGLE_SYNC_DATA_OPTION );
		$this->assertContains( 100, $data['users'][42]['added_memberships'] );
		$this->assertNotContains( 100, $data['users'][42]['revoked_memberships'] );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator::sync_membership()
	 */
	public function testSyncMembershipQueuesRevokeForNonActiveMembership() {
		$this->invokeMethod( 'sync_membership', [ $this->makeMembership( 'paused', 42, 100 ) ] );

		$data = get_option( WC_Memberships_Integrator::SINGLE_SYNC_DATA_OPTION );
		$this->assertContains( 100, $data['users'][42]['revoked_memberships'] );
		$this->assertNotContains( 100, $data['users'][42]['added_memberships'] );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator::prepare_single_sync()
	 */
	public function testPrepareSingleSyncFiltersNullValues() {
		$this->invokeMethod( 'prepare_single_sync', [ 42, 100, null ] );

		$data = get_option( WC_Memberships_Integrator::SINGLE_SYNC_DATA_OPTION );
		$this->assertContains( 100, $data['users'][42]['added_memberships'] );
		$this->assertNotContains( null, $data['users'][42]['added_memberships'] );
		$this->assertNotContains( null, $data['users'][42]['revoked_memberships'] );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator::prepare_single_sync()
	 */
	public function testPrepareSingleSyncMergesDataForSameUser() {
		$this->invokeMethod( 'prepare_single_sync', [ 42, null, 100 ] ); // revoke plan 100
		$this->invokeMethod( 'prepare_single_sync', [ 42, 100, null ] ); // add plan 100

		$data = get_option( WC_Memberships_Integrator::SINGLE_SYNC_DATA_OPTION );
		$this->assertContains( 100, $data['users'][42]['added_memberships'] );
		$this->assertContains( 100, $data['users'][42]['revoked_memberships'] );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator::prepare_single_sync()
	 */
	public function testPrepareSingleSyncDoesNotDuplicatePlanIds() {
		$this->invokeMethod( 'prepare_single_sync', [ 42, 100, null ] );
		$this->invokeMethod( 'prepare_single_sync', [ 42, 100, null ] );

		$data       = get_option( WC_Memberships_Integrator::SINGLE_SYNC_DATA_OPTION );
		$plan_count = count( array_keys( $data['users'][42]['added_memberships'], 100 ) );
		$this->assertSame( 1, $plan_count );
	}

	/**
	 * Helper: builds a $pending array from $snapshot with the given user IDs removed (processed).
	 */
	protected function buildPending( array $snapshot, array $processed_user_ids ): array {
		$pending = $snapshot;
		foreach ( $processed_user_ids as $uid ) {
			unset( $pending['users'][ $uid ] );
		}
		return $pending;
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator::reconcile_sync_data()
	 */
	public function testReconcileRemovesAllProcessedUsersWhenNoRace() {
		$this->invokeMethod( 'prepare_single_sync', [ 42, 100, null ] );
		$this->invokeMethod( 'prepare_single_sync', [ 99, 200, null ] );
		$snapshot = get_option( WC_Memberships_Integrator::SINGLE_SYNC_DATA_OPTION );

		$result = $this->invokeMethod( 'reconcile_sync_data', [
			$snapshot,
			$this->buildPending( $snapshot, [ 42, 99 ] ),
		] );

		$this->assertEmpty( $result['users'] );
	}

	/**
	 * Scenario E: a concurrent write for a DIFFERENT user must survive reconciliation.
	 *
	 * @covers \SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator::reconcile_sync_data()
	 */
	public function testReconcilePreservesConcurrentWriteForDifferentUser() {
		$this->invokeMethod( 'prepare_single_sync', [ 42, null, 100 ] );
		$snapshot = get_option( WC_Memberships_Integrator::SINGLE_SYNC_DATA_OPTION );

		// Webhook writes user 99 while cron processes user 42.
		$this->invokeMethod( 'prepare_single_sync', [ 99, 200, null ] );

		$result = $this->invokeMethod( 'reconcile_sync_data', [
			$snapshot,
			$this->buildPending( $snapshot, [ 42 ] ),
		] );

		$this->assertArrayNotHasKey( 42, $result['users'] );
		$this->assertArrayHasKey( 99, $result['users'] );
		$this->assertContains( 200, $result['users'][99]['added_memberships'] );
	}

	/**
	 * Scenario E: a concurrent write for the SAME user must survive reconciliation.
	 * The snapshot won't match the current data, so the entry is kept.
	 *
	 * @covers \SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator::reconcile_sync_data()
	 */
	public function testReconcilePreservesConcurrentWriteForSameUser() {
		$this->invokeMethod( 'prepare_single_sync', [ 42, null, 100 ] );
		$snapshot = get_option( WC_Memberships_Integrator::SINGLE_SYNC_DATA_OPTION );

		// Webhook adds plan 100 for same user while cron processes the revoke.
		$this->invokeMethod( 'prepare_single_sync', [ 42, 100, null ] );

		$result = $this->invokeMethod( 'reconcile_sync_data', [
			$snapshot,
			$this->buildPending( $snapshot, [ 42 ] ),
		] );

		$this->assertArrayHasKey( 42, $result['users'] );
		$this->assertContains( 100, $result['users'][42]['added_memberships'] );
	}

	/**
	 * When a sync fails, successfully-processed users are removed
	 * but pending users stay in the reconciled data.
	 *
	 * @covers \SeriouslySimplePodcasting\Integrations\Woocommerce\WC_Memberships_Integrator::reconcile_sync_data()
	 */
	public function testReconcileOnPartialFailureKeepsPendingUsers() {
		$this->invokeMethod( 'prepare_single_sync', [ 42, 100, null ] );
		$this->invokeMethod( 'prepare_single_sync', [ 99, 200, null ] );
		$snapshot = get_option( WC_Memberships_Integrator::SINGLE_SYNC_DATA_OPTION );

		// User 42 succeeded, user 99 failed.
		$result = $this->invokeMethod( 'reconcile_sync_data', [
			$snapshot,
			$this->buildPending( $snapshot, [ 42 ] ),
		] );

		$this->assertArrayNotHasKey( 42, $result['users'] );
		$this->assertArrayHasKey( 99, $result['users'] );
	}
}
