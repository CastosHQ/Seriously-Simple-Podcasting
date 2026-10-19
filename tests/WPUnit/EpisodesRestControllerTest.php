<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Rest\Episodes_Rest_Controller;

class EpisodesRestControllerTest extends \Codeception\TestCase\WPTestCase {

	private $had_use_post_types_option;

	private $original_use_post_types_option;

	protected function setUp(): void {
		parent::setUp();
		wp_cache_flush();

		$this->original_use_post_types_option = get_option( 'ss_podcasting_use_post_types' );
		$this->had_use_post_types_option      = false !== $this->original_use_post_types_option;
		update_option( 'ss_podcasting_use_post_types', array() );
		wp_cache_flush();
	}

	protected function tearDown(): void {
		if ( $this->had_use_post_types_option ) {
			update_option( 'ss_podcasting_use_post_types', $this->original_use_post_types_option );
		} else {
			delete_option( 'ss_podcasting_use_post_types' );
		}

		unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * The nonce gate grants cookie-derived privilege only for a valid wp_rest nonce.
	 */
	public function testHasValidRestNonceOnlyAcceptsValidNonce() {
		$editor = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $editor );

		$method = new \ReflectionMethod( Episodes_Rest_Controller::class, 'has_valid_rest_nonce' );
		$method->setAccessible( true );

		$missing = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$this->assertFalse( $method->invoke( null, $missing ), 'No nonce must not grant privilege' );

		$invalid = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$invalid->set_header( 'X-WP-Nonce', 'not-a-real-nonce' );
		$this->assertFalse( $method->invoke( null, $invalid ), 'An invalid nonce must not grant privilege' );

		$non_scalar = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$non_scalar->set_param( '_wpnonce', array( 'x' ) );
		$this->assertFalse( $method->invoke( null, $non_scalar ), 'A non-scalar _wpnonce must not grant privilege' );

		$valid = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$valid->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$this->assertTrue( $method->invoke( null, $valid ), 'A valid wp_rest nonce must grant privilege' );
	}

	/**
	 * A logged-in cookie without a REST nonce must not expose private episodes (the CSRF gap).
	 */
	public function testGetItemsWithoutNonceHidesPrivateEpisodes() {
		$user    = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$publish = $this->create_episode( 'publish', $user );
		$private = $this->create_episode( 'private', $user );

		// Logged-in browser cookie present, but no REST nonce accompanies the request.
		$_COOKIE[ LOGGED_IN_COOKIE ] = wp_generate_auth_cookie( $user, time() + HOUR_IN_SECONDS, 'logged_in' );
		wp_set_current_user( 0 );

		$ids = $this->get_items_ids();

		$this->assertContains( $publish, $ids, 'Published episode should be listed' );
		$this->assertNotContains( $private, $ids, 'Private episode must not leak without a valid REST nonce' );
	}

	/**
	 * A privileged user with a valid REST nonce defaults to published episodes.
	 */
	public function testGetItemsWithValidNonceDefaultsToPublishedEpisodes() {
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		// A valid nonce is bound to the resolved user, exactly as core keeps the cookie user
		// when @wordpress/api-fetch sends X-WP-Nonce.
		wp_set_current_user( $user );
		$nonce = wp_create_nonce( 'wp_rest' );

		$publish = $this->create_episode( 'publish', $user );
		$private = $this->create_episode( 'private', $user );

		$ids = $this->get_items_ids( array( 'X-WP-Nonce' => $nonce ) );

		$this->assertContains( $publish, $ids );
		$this->assertNotContains( $private, $ids, 'A privileged user without an explicit status should only see published episodes' );
	}

	public function testAnonymousEditContextIsRefusedWithoutSensitiveFields() {
		wp_set_current_user( 0 );

		$response = $this->get_items_response( array( 'context' => 'edit' ) );

		$this->assertInstanceOf( '\\WP_Error', $response );
		$this->assertSame( 401, $response->get_error_data()['status'] );
	}

	public function testContributorCannotRequestNativePodcastEditContext() {
		$this->assertNativePodcastEditContextIsRefusedForRole( 'contributor' );
	}

	public function testAuthorCannotRequestNativePodcastEditContext() {
		$this->assertNativePodcastEditContextIsRefusedForRole( 'author' );
	}

	public function testAuthorEditContextIncludesOwnPostAndOmitsAnotherAuthorsProtectedPost() {
		$author_id = self::factory()->user->create( array( 'role' => 'author' ) );
		$other_id  = self::factory()->user->create( array( 'role' => 'author' ) );
		$password  = 'another-authors-protected-password';

		update_option( 'ss_podcasting_use_post_types', array( 'post' ) );

		$own_post_id = $this->create_episode(
			'publish',
			$author_id,
			array(
				'post_type'    => 'post',
				'post_title'   => 'Authors raw title',
				'post_content' => 'Authors raw content',
				'post_excerpt' => 'Authors raw excerpt',
			)
		);
		$other_post_id = $this->create_episode(
			'publish',
			$other_id,
			array(
				'post_type'     => 'post',
				'post_password' => $password,
				'post_title'    => 'Other authors raw title',
				'post_content'  => 'Other authors raw content',
				'post_excerpt'  => 'Other authors raw excerpt',
			)
		);

		wp_set_current_user( $author_id );
		$response = $this->get_items_response( array( 'context' => 'edit' ) );

		$this->assertInstanceOf( '\\WP_REST_Response', $response );
		$items = $this->get_items_by_id( $response );
		$this->assertArrayHasKey( $own_post_id, $items );
		$this->assertArrayNotHasKey( $other_post_id, $items );
		$this->assertSame( 'Authors raw title', $items[ $own_post_id ]['title']['raw'] );
		$this->assertSame( 'Authors raw content', $items[ $own_post_id ]['content']['raw'] );
		$this->assertSame( 'Authors raw excerpt', $items[ $own_post_id ]['excerpt']['raw'] );
		$this->assertStringNotContainsString( $password, wp_json_encode( $response->get_data() ) );
	}

	public function testEditorReceivesEditContextWithPasswordAndRawFields() {
		$editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		$author_id = self::factory()->user->create( array( 'role' => 'author' ) );
		$password  = 'editors-protected-episode-password';
		$episode_id = $this->create_episode(
			'publish',
			$author_id,
			array(
				'post_password' => $password,
				'post_title'    => 'Editors raw title',
				'post_content'  => 'Editors raw content',
				'post_excerpt'  => 'Editors raw excerpt',
			)
		);

		wp_set_current_user( $editor_id );
		$response = $this->get_items_response( array( 'context' => 'edit' ) );

		$this->assertInstanceOf( '\\WP_REST_Response', $response );
		$items = $this->get_items_by_id( $response );
		$this->assertArrayHasKey( $episode_id, $items );
		$this->assertSame( $password, $items[ $episode_id ]['password'] );
		$this->assertSame( 'Editors raw title', $items[ $episode_id ]['title']['raw'] );
		$this->assertSame( 'Editors raw content', $items[ $episode_id ]['content']['raw'] );
		$this->assertSame( 'Editors raw excerpt', $items[ $episode_id ]['excerpt']['raw'] );
	}

	public function testAnonymousViewContextStillListsPublishedEpisodesWithoutPasswords() {
		$owner_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$password = 'anonymous-view-context-password';
		$regular_id = $this->create_episode( 'publish', $owner_id );
		$protected_id = $this->create_episode(
			'publish',
			$owner_id,
			array(
				'post_password' => $password,
				'post_title'    => 'Protected view title',
			)
		);
		wp_set_current_user( 0 );

		// No context parameter is supplied; the collection defaults to view context.
		$response = $this->get_items_response();

		$this->assertInstanceOf( '\\WP_REST_Response', $response );
		$items = $this->get_items_by_id( $response );
		$this->assertArrayHasKey( $regular_id, $items );
		$this->assertArrayHasKey( $protected_id, $items );
		$this->assertSame( 'publish', $items[ $protected_id ]['status'] );
		$this->assertArrayNotHasKey( 'password', $items[ $protected_id ] );
		$this->assertStringNotContainsString( $password, wp_json_encode( $response->get_data() ) );
	}

	private function assertNativePodcastEditContextIsRefusedForRole( $role ) {
		$owner_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$user_id  = self::factory()->user->create( array( 'role' => $role ) );
		$this->create_episode( 'publish', $owner_id );

		wp_set_current_user( $user_id );
		$native_post_types_filter = static function ( $post_types ) {
			return array( SSP_CPT_PODCAST );
		};
		add_filter( 'ssp_podcast_post_types', $native_post_types_filter, PHP_INT_MAX );

		try {
			$controller = $this->make_controller();
		} finally {
			remove_filter( 'ssp_podcast_post_types', $native_post_types_filter, PHP_INT_MAX );
		}

		$this->assertSame(
			array( SSP_CPT_PODCAST ),
			$controller->post_types,
			'Controller should use only the native podcast post type.'
		);

		$response = $this->get_items_response(
			array( 'context' => 'edit' ),
			array(),
			$controller
		);

		$this->assertInstanceOf( '\\WP_Error', $response );
		$this->assertSame( 403, $response->get_error_data()['status'] );
	}

	private function create_episode( $status, $author, $overrides = array() ) {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => SSP_CPT_PODCAST,
					'post_status' => $status,
					'post_author' => $author,
				),
				$overrides
			)
		);
	}

	/** Calls get_items() with the given params, headers, and optional controller. */
	private function get_items_response( $params = array(), $headers = array(), $controller = null ) {
		$request = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$request->set_param( 'per_page', 10 );
		$request->set_param( 'page', 1 );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		foreach ( $headers as $key => $value ) {
			$request->set_header( $key, $value );
		}

		if ( null === $controller ) {
			$controller = $this->make_controller();
		}

		return $controller->get_items( $request );
	}

	/** Calls get_items() with the given headers and returns the listed episode IDs. */
	private function get_items_ids( $headers = array() ) {
		$response = $this->get_items_response( array(), $headers );

		$ids = array();
		foreach ( (array) $response->get_data() as $item ) {
			if ( isset( $item['id'] ) ) {
				$ids[] = $item['id'];
			}
		}

		return $ids;
	}

	/**
	 * Test that the filter parameter cannot override post_status for unauthenticated requests.
	 */
	public function testFilterCannotOverridePostStatusForUnauthenticated() {
		$controller = $this->make_controller();

		$method = new \ReflectionMethod( $controller, 'sanitize_filter_args' );
		$method->setAccessible( true );

		$filter = array(
			'post_status' => 'draft',
			'post_type'   => 'page',
			's'           => 'test',
		);

		$result = $method->invoke( $controller, $filter, false );

		$this->assertArrayNotHasKey( 'post_status', $result, 'filter[post_status] should be stripped for unauthenticated requests' );
		$this->assertArrayNotHasKey( 'post_type', $result, 'filter[post_type] should be stripped for unauthenticated requests' );
	}

	/**
	 * Test that authenticated users can use filter params.
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

		$this->assertArrayHasKey( 'post_status', $result );
		$this->assertArrayHasKey( 'post_type', $result );
	}

	/**
	 * Test that only allowlisted keys survive for unauthenticated users.
	 */
	public function testOnlyAllowlistedKeysPassForUnauthenticated() {
		$controller = $this->make_controller();

		$method = new \ReflectionMethod( $controller, 'sanitize_filter_args' );
		$method->setAccessible( true );

		$filter = array(
			'meta_query'       => array( array( 'key' => '_secret', 'value' => 'x' ) ),
			'meta_key'         => '_secret',
			'nopaging'         => true,
			'has_password'     => true,
			'suppress_filters' => true,
			's'                => 'test',
			'posts_per_page'   => 10,
		);

		$result = $method->invoke( $controller, $filter, false );

		$this->assertArrayHasKey( 's', $result );
		$this->assertArrayHasKey( 'posts_per_page', $result );
		$this->assertArrayNotHasKey( 'meta_query', $result );
		$this->assertArrayNotHasKey( 'meta_key', $result );
		$this->assertArrayNotHasKey( 'nopaging', $result );
		$this->assertArrayNotHasKey( 'has_password', $result );
		$this->assertArrayNotHasKey( 'suppress_filters', $result );
	}

	private function get_items_by_id( $response ) {
		$items = array();

		foreach ( (array) $response->get_data() as $item ) {
			if ( isset( $item['id'] ) ) {
				$items[ $item['id'] ] = $item;
			}
		}

		return $items;
	}

	private function make_controller() {
		$episode_repo = ssp_episode_repository();

		return new Episodes_Rest_Controller( $episode_repo );
	}
}
