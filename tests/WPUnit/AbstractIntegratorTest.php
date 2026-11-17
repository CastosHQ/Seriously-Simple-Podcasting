<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Integrations\Abstract_Integrator;

class AbstractIntegratorTestDouble extends Abstract_Integrator {

	public function getUsersData( $user_ids ) {
		return $this->get_users_data( $user_ids );
	}

	public function setDependencies( $castos_handler, $logger, $series_podcasts_map ) {
		$this->castos_handler      = $castos_handler;
		$this->logger              = $logger;
		$this->series_podcasts_map = $series_podcasts_map;
	}

	public function addSubscribersToPodcast( $series_id, $user_ids ) {
		return $this->add_subscribers_to_podcast( $series_id, $user_ids );
	}

	public function revokeSubscribersFromPodcast( $series_id, $user_ids ) {
		return $this->revoke_subscribers_from_podcast( $series_id, $user_ids );
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

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Abstract_Integrator::add_subscribers_to_podcast()
	 */
	public function testAddSubscribersToPodcastSkipsUnknownUserIds(): void {
		$user_id         = $this->createUser( 'existing@example.com', 'Existing User' );
		$missing_user_id = $user_id + 100000;
		$series_id       = 7;
		$podcast_id      = 42;

		$castos_handler = $this->getMockBuilder( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'add_subscribers_to_podcasts' ) )
			->getMock();
		$castos_handler->expects( $this->once() )
			->method( 'add_subscribers_to_podcasts' )
			->with(
				array( $podcast_id ),
				array(
					array(
						'name'  => 'Existing User',
						'email' => 'existing@example.com',
					),
				)
			)
			->willReturn( 1 );

		$logger = $this->getMockBuilder( \SeriouslySimplePodcasting\Helpers\Log_Helper::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'log' ) )
			->getMock();

		$this->integrator->setDependencies( $castos_handler, $logger, array( $series_id => $podcast_id ) );

		$count = $this->integrator->addSubscribersToPodcast( $series_id, array( $missing_user_id, $user_id ) );

		$this->assertSame( 1, $count );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Integrations\Abstract_Integrator::revoke_subscribers_from_podcast()
	 */
	public function testRevokeSubscribersFromPodcastSkipsUnknownUserIds(): void {
		$user_id         = $this->createUser( 'existing@example.com', 'Existing User' );
		$missing_user_id = $user_id + 100000;
		$series_id       = 7;
		$podcast_id      = 42;

		$castos_handler = $this->getMockBuilder( \SeriouslySimplePodcasting\Handlers\Castos_Handler::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'revoke_subscribers_from_podcasts' ) )
			->getMock();
		$castos_handler->expects( $this->once() )
			->method( 'revoke_subscribers_from_podcasts' )
			->with( array( $podcast_id ), array( 'existing@example.com' ) )
			->willReturn( 1 );

		$logger = $this->getMockBuilder( \SeriouslySimplePodcasting\Helpers\Log_Helper::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'log' ) )
			->getMock();

		$this->integrator->setDependencies( $castos_handler, $logger, array( $series_id => $podcast_id ) );

		$count = $this->integrator->revokeSubscribersFromPodcast( $series_id, array( $missing_user_id, $user_id ) );

		$this->assertSame( 1, $count );
	}
}
