<?php

namespace Tests\Unit;

use Tests\Support\UnitTester;
use SeriouslySimplePodcasting\Rest\Episodes_Rest_Controller;

class EpisodesRestControllerTest extends \Codeception\Test\Unit {

	protected UnitTester $tester;

	/**
	 * Test that the filter parameter cannot override post_status for unauthenticated requests.
	 *
	 * The get_items() method should strip filter[post_status] and filter[post_type]
	 * so unauthenticated users cannot query drafts, private posts, or other post types.
	 */
	public function testFilterCannotOverridePostStatusForUnauthenticated() {
		$controller = $this->make_controller();

		// Use reflection to call prepare_filter_args (the method we'll create to sanitize filter)
		$method = new \ReflectionMethod( $controller, 'sanitize_filter_args' );
		$method->setAccessible( true );

		$filter = array(
			'post_status' => 'draft',
			'post_type'   => 'page',
			'meta_query'  => array(
				array(
					'key'   => '_secret',
					'value' => 'password',
				),
			),
			's'           => 'confidential',
		);

		$result = $method->invoke( $controller, $filter, false );

		// post_status and post_type must be stripped for unauthenticated requests
		$this->assertArrayNotHasKey( 'post_status', $result, 'filter[post_status] should be stripped for unauthenticated requests' );
		$this->assertArrayNotHasKey( 'post_type', $result, 'filter[post_type] should be stripped for unauthenticated requests' );
	}

	/**
	 * Test that authenticated users with proper capabilities can use filter params.
	 */
	public function testFilterAllowedForAuthenticatedRequests() {
		$controller = $this->make_controller();

		$method = new \ReflectionMethod( $controller, 'sanitize_filter_args' );
		$method->setAccessible( true );

		$filter = array(
			'post_status' => 'draft',
			'post_type'   => 'page',
			's'           => 'test',
		);

		$result = $method->invoke( $controller, $filter, true );

		// Authenticated requests should keep these params
		$this->assertArrayHasKey( 'post_status', $result, 'filter[post_status] should be allowed for authenticated requests' );
		$this->assertArrayHasKey( 'post_type', $result, 'filter[post_type] should be allowed for authenticated requests' );
	}

	/**
	 * Test that dangerous query vars are always stripped regardless of auth.
	 */
	public function testDangerousQueryVarsAlwaysStripped() {
		$controller = $this->make_controller();

		$method = new \ReflectionMethod( $controller, 'sanitize_filter_args' );
		$method->setAccessible( true );

		$filter = array(
			'meta_query' => array( array( 'key' => '_secret', 'value' => 'x' ) ),
			'meta_key'   => '_secret',
			'meta_value' => 'x',
			's'          => 'test',
		);

		$result = $method->invoke( $controller, $filter, false );

		$this->assertArrayNotHasKey( 'meta_query', $result, 'filter[meta_query] should always be stripped for unauthenticated' );
		$this->assertArrayNotHasKey( 'meta_key', $result, 'filter[meta_key] should always be stripped for unauthenticated' );
		$this->assertArrayNotHasKey( 'meta_value', $result, 'filter[meta_value] should always be stripped for unauthenticated' );
	}

	private function make_controller() {
		$episode_repo = $this->makeEmpty( \SeriouslySimplePodcasting\Repositories\Episode_Repository::class );

		return new Episodes_Rest_Controller( $episode_repo );
	}
}
