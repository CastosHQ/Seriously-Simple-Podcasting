<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Integrations\Abstract_Integrator;

class AbstractIntegratorTestDouble extends Abstract_Integrator {

	public function getUsersData( $user_ids ) {
		return $this->get_users_data( $user_ids );
	}
}

class AbstractIntegratorTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var AbstractIntegratorTestDouble
	 */
	private $integrator;

	protected function setUp(): void {
		parent::setUp();
		$this->integrator = new AbstractIntegratorTestDouble();
	}

	/**
	 * Creates a user with the fields returned by get_users_data().
	 *
	 * @param string $email
	 * @param string $display_name
	 *
	 * @return int
	 */
	private function createUser( $email, $display_name ) {
		return $this->factory()->user->create(
			array(
				'user_email'   => $email,
				'display_name' => $display_name,
			)
		);
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Abstract_Integrator::get_users_data()
	 */
	public function testGetUsersDataReturnsEmailAndDisplayNameForIntegerIds(): void {
		$first_user_id  = $this->createUser( 'first@example.com', 'First User' );
		$second_user_id = $this->createUser( 'second@example.com', 'Second User' );

		$users_data = $this->integrator->getUsersData( array( $first_user_id, $second_user_id ) );

		$this->assertSame(
			array(
				$first_user_id  => array(
					'user_email'   => 'first@example.com',
					'display_name' => 'First User',
				),
				$second_user_id => array(
					'user_email'   => 'second@example.com',
					'display_name' => 'Second User',
				),
			),
			$users_data
		);
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Abstract_Integrator::get_users_data()
	 */
	public function testGetUsersDataCastsIdsToIntegers(): void {
		global $wpdb;

		$user_id          = $this->createUser( 'user@example.com', 'Example User' );
		$provided_id      = $user_id . "'";
		$wpdb->last_error = '';

		$users_data = $this->integrator->getUsersData( array( $provided_id ) );

		$this->assertSame( '', $wpdb->last_error );
		$this->assertCount( 1, $users_data );
		$this->assertArrayHasKey( $user_id, $users_data );
		$this->assertSame(
			array(
				'user_email'   => 'user@example.com',
				'display_name' => 'Example User',
			),
			$users_data[ $user_id ]
		);
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Abstract_Integrator::get_users_data()
	 */
	public function testGetUsersDataReturnsEmptyForEmptyIdsWithoutRunningQuery(): void {
		global $wpdb;

		$query_count = $wpdb->num_queries;
		$users_data  = $this->integrator->getUsersData( array() );

		$this->assertSame( array(), $users_data );
		$this->assertSame( $query_count, $wpdb->num_queries );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Abstract_Integrator::get_users_data()
	 */
	public function testGetUsersDataReturnsEmptyForZeroAndNonNumericIdsWithoutRunningQuery(): void {
		global $wpdb;

		$query_count = $wpdb->num_queries;
		$users_data  = $this->integrator->getUsersData( array( 0, '0', 'abc' ) );

		$this->assertSame( array(), $users_data );
		$this->assertSame( $query_count, $wpdb->num_queries );
	}
}
