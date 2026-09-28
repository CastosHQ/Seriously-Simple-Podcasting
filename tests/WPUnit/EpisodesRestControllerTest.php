<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Rest\Episodes_Rest_Controller;

class EpisodesRestControllerTest extends \Codeception\TestCase\WPTestCase {

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
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
	 * A privileged user with a valid REST nonce still sees private episodes (no editor regression).
	 */
	public function testGetItemsWithValidNonceShowsPrivateEpisodes() {
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		// A valid nonce is bound to the resolved user, exactly as core keeps the cookie user
		// when @wordpress/api-fetch sends X-WP-Nonce.
		wp_set_current_user( $user );
		$nonce = wp_create_nonce( 'wp_rest' );

		$publish = $this->create_episode( 'publish', $user );
		$private = $this->create_episode( 'private', $user );

		$ids = $this->get_items_ids( array( 'X-WP-Nonce' => $nonce ) );

		$this->assertContains( $publish, $ids );
		$this->assertContains( $private, $ids, 'A privileged user with a valid nonce should see private episodes' );
	}

	/**
	 * The episode GUID captured on import must be readable by Castos through
	 * the episode payload's meta; an episode without one is unaffected.
	 */
	public function testEpisodePayloadExposesOriginalGuid() {
		// The WP test case unregisters all meta keys in tearDown, so the plugin's
		// init-time registration is gone by this test — re-run it.
		ssp_get_service( 'cpt_podcast_handler' )->register_post_type();

		$user     = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$imported = $this->create_episode( 'publish', $user );
		$manual   = $this->create_episode( 'publish', $user );

		update_post_meta( $imported, 'ssp_original_guid', 'https://example.com/?p=123' );

		$request = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$request->set_param( 'per_page', 10 );
		$request->set_param( 'page', 1 );

		$items = array();
		foreach ( (array) $this->make_controller()->get_items( $request )->get_data() as $item ) {
			$items[ $item['id'] ] = $item;
		}

		$this->assertSame(
			'https://example.com/?p=123',
			isset( $items[ $imported ]['meta']['ssp_original_guid'] ) ? $items[ $imported ]['meta']['ssp_original_guid'] : null,
			'Imported episode GUID must be exposed in the REST meta Castos pulls'
		);
		$this->assertSame(
			'',
			isset( $items[ $manual ]['meta']['ssp_original_guid'] ) ? $items[ $manual ]['meta']['ssp_original_guid'] : null,
			'An episode without an imported GUID exposes the field as empty'
		);
	}

	/**
	 * Both Castos routes report the resolved published GUID without changing legacy or core identity.
	 */
	public function testBothEpisodeRoutesExposePublishedGuidWithoutWritingOnRead() {
		ssp_get_service( 'cpt_podcast_handler' )->register_post_type();
		$series = self::factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$imported = $this->create_episode( 'publish', $user );
		$native = $this->create_episode( 'publish', $user );
		$legacy = $this->create_episode( 'publish', $user );
		$legacy_native = $this->create_episode( 'publish', $user );
		$fallback = $this->create_episode( 'publish', $user );
		foreach ( array( $imported, $native, $legacy, $legacy_native, $fallback ) as $id ) {
			wp_set_object_terms( $id, array( $series ), ssp_series_taxonomy() );
		}
		update_post_meta( $imported, 'ssp_original_guid', 'import-original' );
		update_post_meta( $imported, 'ssp_guid', 'import-stray' );
		update_post_meta( $imported, 'ssp_episode_guid', 'import-published' );
		update_post_meta( $native, 'ssp_episode_guid', 'native-published' );
		update_post_meta( $legacy, 'ssp_original_guid', 'legacy-original' );
		update_post_meta( $legacy, 'ssp_guid', 'legacy-stray' );
		update_post_meta( $legacy_native, 'ssp_guid', 'legacy-native' );

		// Initialise REST registration (including the computed field) after WPUnit's meta reset.
		rest_get_server();
		$expected = array(
			$imported => 'import-published',
			$native => 'native-published',
			$legacy => 'legacy-original',
			$legacy_native => 'legacy-native',
			$fallback => get_the_guid( $fallback ),
		);
		foreach ( array( '/ssp/v1/episodes', '/ssp/v1/podcasts/' . $series . '/episodes' ) as $route ) {
			$request = new \WP_REST_Request( 'GET', $route );
			$request->set_param( 'per_page', 20 );
			$request->set_param( 'page', 1 );
			$response = rest_do_request( $request );
			$this->assertSame( 200, $response->get_status(), $route );
			$items = array();
			foreach ( $response->get_data() as $item ) {
				$items[ $item['id'] ] = $item;
			}
			foreach ( $expected as $id => $guid ) {
				$this->assertArrayHasKey( $id, $items, $route );
				$this->assertSame( $guid, $items[ $id ]['ssp_episode_guid'], $route );
				$this->assertSame( get_the_guid( $id ), $items[ $id ]['guid']['rendered'], 'Core GUID remains the WP post GUID' );
				if ( in_array( $id, array( $legacy, $legacy_native, $fallback ), true ) ) {
					$this->assertFalse( metadata_exists( 'post', $id, 'ssp_episode_guid' ), 'Read must not store legacy GUIDs' );
				}
				if ( in_array( $id, array( $native, $fallback ), true ) ) {
					$this->assertFalse( metadata_exists( 'post', $id, 'ssp_guid' ), 'Read must not write the old key' );
				}
			}
			$this->assertSame( 'import-original', $items[ $imported ]['meta']['ssp_original_guid'] );
			$this->assertSame( '', $items[ $native ]['meta']['ssp_original_guid'] );
		}
	}

	/**
	 * A filtered REST GUID is output-only, and the core update route ignores writes to it.
	 */
	public function testEpisodeGuidRestFieldIsFilteredAndReadOnly() {
		ssp_get_service( 'cpt_podcast_handler' )->register_post_type();
		rest_get_server();
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$id = $this->create_episode( 'publish', $user );
		update_post_meta( $id, 'ssp_episode_guid', 'stored-guid' );
		$filter = function ( $guid ) { return 'filtered-' . $guid; };
		add_filter( 'ssp/episode/guid', $filter );
		try {
			$request = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
			$request->set_param( 'per_page', 10 );
			$request->set_param( 'page', 1 );
			$items = rest_do_request( $request )->get_data();
			$found = wp_list_filter( $items, array( 'id' => $id ) );
			$this->assertSame( 'filtered-stored-guid', reset( $found )['ssp_episode_guid'] );
			$this->assertSame( 'stored-guid', get_post_meta( $id, 'ssp_episode_guid', true ) );

			wp_set_current_user( $user );
			$type = get_post_type_object( SSP_CPT_PODCAST );
			$base = $type->rest_base ?: SSP_CPT_PODCAST;
			$update = new \WP_REST_Request( 'POST', '/wp/v2/' . $base . '/' . $id );
			$update->set_param( 'ssp_episode_guid', 'attempted-overwrite' );
			$response = rest_do_request( $update );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( 'stored-guid', get_post_meta( $id, 'ssp_episode_guid', true ) );
			$this->assertSame( 'filtered-stored-guid', $response->get_data()['ssp_episode_guid'] );
		} finally {
			remove_filter( 'ssp/episode/guid', $filter );
			wp_set_current_user( 0 );
		}
	}

	/**
	 * Private podcast identity is hidden from public REST reads, not editors or Castos.
	 */
	public function testPrivatePodcastRestHidesGuidAndMetaOnlyFromAnonymousReaders() {
		ssp_get_service( 'cpt_podcast_handler' )->register_post_type();
		rest_get_server();
		$editor = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$series = self::factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		$public_series = self::factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		$private_series = $this->create_episode( 'publish', $editor );
		$private_default = $this->create_episode( 'publish', $editor );
		$public = $this->create_episode( 'publish', $editor );
		wp_set_object_terms( $private_series, array( $series ), ssp_series_taxonomy() );
		wp_set_object_terms( $private_default, array(), ssp_series_taxonomy() );
		$this->assertSame( array(), wp_get_post_terms( $private_default, ssp_series_taxonomy() ) );
		wp_set_object_terms( $public, array( $public_series ), ssp_series_taxonomy() );
		ssp_update_option( 'is_podcast_private', 'yes', $series );
		ssp_update_option( 'is_podcast_private', 'yes' );
		$guids = array(
			$private_series  => 'private-series-guid',
			$private_default => 'private-default-guid',
			$public          => 'public-guid',
		);
		foreach ( $guids as $id => $guid ) {
			update_post_meta( $id, 'ssp_episode_guid', $guid );
			update_post_meta( $id, 'ssp_original_guid', 'original-' . $guid );
		}
		$type = get_post_type_object( SSP_CPT_PODCAST );
		$base = $type->rest_base ?: SSP_CPT_PODCAST;
		update_option( 'ss_podcasting_podmotor_account_api_token', 'private-guid-test-token' );

		try {
			foreach ( $guids as $id => $guid ) {
				$route = '/wp/v2/' . $base . '/' . $id;
				$this->reset_rest_privacy_auth_cache();
				wp_set_current_user( 0 );
				$anonymous = rest_do_request( new \WP_REST_Request( 'GET', $route ) );
				$this->assertSame( 200, $anonymous->get_status(), $route );
				$data = $anonymous->get_data();
				if ( $public === $id ) {
					$this->assertSame( $guid, $data['ssp_episode_guid'] );
					$this->assertArrayHasKey( 'meta', $data );
				} else {
					$this->assertArrayNotHasKey( 'ssp_episode_guid', $data, $route );
					$this->assertArrayNotHasKey( 'meta', $data, $route );
				}

				$this->reset_rest_privacy_auth_cache();
				$ssp_route = $private_series === $id ? '/ssp/v1/podcasts/' . $series . '/episodes' : '/ssp/v1/episodes';
				$ssp_request = new \WP_REST_Request( 'GET', $ssp_route );
				$ssp_request->set_param( 'per_page', 20 );
				$ssp_response = rest_do_request( $ssp_request );
				$this->assertSame( 200, $ssp_response->get_status(), $ssp_route );
				$found = wp_list_filter( $ssp_response->get_data(), array( 'id' => $id ) );
				$this->assertCount( 1, $found, $ssp_route );
				$ssp_data = reset( $found );
				if ( $public === $id ) {
					$this->assertSame( $guid, $ssp_data['ssp_episode_guid'] );
					$this->assertArrayHasKey( 'meta', $ssp_data );
				} else {
					$this->assertArrayNotHasKey( 'ssp_episode_guid', $ssp_data, $ssp_route );
					$this->assertArrayNotHasKey( 'meta', $ssp_data, $ssp_route );
				}

				$this->reset_rest_privacy_auth_cache();
				wp_set_current_user( $editor );
				$editor_request = new \WP_REST_Request( 'GET', $route );
				$editor_request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
				$editor_response = rest_do_request( $editor_request );
				$this->assertSame( 200, $editor_response->get_status(), $route );
				$this->assertSame( $guid, $editor_response->get_data()['ssp_episode_guid'] );
				$this->assertArrayHasKey( 'meta', $editor_response->get_data() );

				$this->reset_rest_privacy_auth_cache();
				wp_set_current_user( 0 );
				$timestamp = (string) time();
				$nonce = bin2hex( random_bytes( 32 ) );
				$path = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' ) . '/' . trim( rest_get_url_prefix(), '/' ) . $route;
				$message = implode( "\n", array( 'GET', $path, '', wp_json_encode( array() ), $timestamp, $nonce ) );
				$castos_request = new \WP_REST_Request( 'GET', $route );
				$castos_request->set_header( 'X-Castos-Timestamp', $timestamp );
				$castos_request->set_header( 'X-Castos-Nonce', $nonce );
				$castos_request->set_header( 'X-Castos-Signature', hash_hmac( 'sha256', $message, 'private-guid-test-token' ) );
				$castos_response = rest_do_request( $castos_request );
				$this->assertSame( 200, $castos_response->get_status(), $route );
				$this->assertSame( $guid, $castos_response->get_data()['ssp_episode_guid'] );
				$this->assertArrayHasKey( 'meta', $castos_response->get_data() );
			}
		} finally {
			delete_option( 'ss_podcasting_podmotor_account_api_token' );
			ssp_update_option( 'is_podcast_private', 'no', $series );
			ssp_update_option( 'is_podcast_private', 'no' );
			wp_set_current_user( 0 );
			$this->reset_rest_privacy_auth_cache();
		}
	}

	/** Clears the request-scoped privacy filter state between simulated REST requests. */
	private function reset_rest_privacy_auth_cache() {
		global $wp_filter;
		foreach ( $wp_filter[ 'rest_prepare_' . SSP_CPT_PODCAST ]->callbacks as $priority_callbacks ) {
			foreach ( $priority_callbacks as $callback ) {
				$function = $callback['function'];
				if ( ! is_array( $function ) || 'maybe_hide_meta_for_private_podcast' !== $function[1] ) {
					continue;
				}
				foreach ( array( 'cookie_authenticated' => false, 'castos_authenticated' => null ) as $name => $value ) {
					$property = new \ReflectionProperty( $function[0], $name );
					$property->setAccessible( true );
					$property->setValue( $function[0], $value );
				}
			}
		}
	}

	private function create_episode( $status, $author ) {
		return self::factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => $status,
				'post_author' => $author,
			)
		);
	}

	/** Calls get_items() with the given headers and returns the listed episode IDs. */
	private function get_items_ids( $headers = array() ) {
		$request = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$request->set_param( 'per_page', 10 );
		$request->set_param( 'page', 1 );

		foreach ( $headers as $key => $value ) {
			$request->set_header( $key, $value );
		}

		$response = $this->make_controller()->get_items( $request );

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

	private function make_controller() {
		$episode_repo = ssp_episode_repository();

		return new Episodes_Rest_Controller( $episode_repo );
	}
}
