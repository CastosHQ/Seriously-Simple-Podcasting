<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Settings_Controller;

/**
 * Verifies the shared episode access check and the media paths it gates:
 * the download route, REST link fields and meta, the audio player endpoint, playlist AJAX
 * items, and password-protected episodes in the SSP feed and core's series feed.
 */
class EpisodeAccessTest extends \Codeception\TestCase\WPTestCase {

	const TOKEN = 'secret-api-token';

	const PASSWORD = 'episode-password';

	const AUDIO = 'https://example.com/episode.mp3';

	/** @var int */
	private $series_id;

	/** @var array */
	private $server_backup;

	/** @var mixed */
	private $default_series_backup;

	/** @var int */
	private $public_id;

	/** @var int */
	private $protected_id;

	protected function setUp(): void {
		parent::setUp();
		$this->server_backup         = $_SERVER;
		$this->default_series_backup = get_option( Settings_Controller::SETTINGS_BASE . 'default_series' );
		update_option( 'ss_podcasting_podmotor_account_api_token', self::TOKEN );

		// The test teardown unregisters custom post types and taxonomies after each test.
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		$this->app_property( 'series_controller' )->register_taxonomy();

		// Earlier tests may leave DOING_AJAX or a JSON request behind, which route wp_die() past the test handler.
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_wp_die_handler' ) );
		add_filter( 'wp_die_json_handler', array( $this, 'get_wp_die_handler' ) );

		$this->series_id    = self::factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		$this->public_id    = $this->create_episode( array( 'post_title' => 'Public episode' ) );
		$this->protected_id = $this->create_episode(
			array(
				'post_title'    => 'Protected episode',
				'post_password' => self::PASSWORD,
			)
		);
	}

	protected function tearDown(): void {
		// go_to() rewrites the request URI, which later feed tests read for the self link.
		$_SERVER = $this->server_backup;
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ], $_GET['ssp_podcast_id'] );
		delete_option( 'ss_podcasting_podmotor_account_api_token' );
		delete_option( Settings_Controller::SETTINGS_BASE . 'is_podcast_private_' . $this->series_id );
		update_option( Settings_Controller::SETTINGS_BASE . 'default_series', $this->default_series_backup );
		remove_all_filters( 'ssp_feed_include_password_protected' );
		remove_all_filters( 'ssp_can_access_episode' );
		wp_set_current_user( 0 );
		$GLOBALS['wp_rest_server'] = null;
		parent::tearDown();
	}

	/* Shared access check */

	public function test_anonymous_visitor_can_access_published_episode() {
		$this->assertTrue( ssp_can_access_episode( $this->public_id ) );
	}

	public function test_anonymous_visitor_cannot_access_password_protected_episode() {
		$this->assertFalse( ssp_can_access_episode( $this->protected_id ) );
	}

	public function test_visitor_who_entered_the_password_can_access_episode() {
		$this->enter_password( self::PASSWORD );

		$this->assertTrue( ssp_can_access_episode( $this->protected_id ) );
	}

	public function test_wrong_password_cookie_does_not_grant_access() {
		$this->enter_password( 'wrong-password' );

		$this->assertFalse( ssp_can_access_episode( $this->protected_id ) );
	}

	public function test_unpublished_episodes_are_denied_to_other_users() {
		$author_id = self::factory()->user->create( array( 'role' => 'author' ) );
		$draft_id  = $this->create_episode( array( 'post_status' => 'draft', 'post_author' => $author_id ) );
		$private_id = $this->create_episode( array( 'post_status' => 'private', 'post_author' => $author_id ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'contributor' ) ) );

		$this->assertFalse( ssp_can_access_episode( $draft_id ) );
		$this->assertFalse( ssp_can_access_episode( $private_id ) );
	}

	public function test_editor_can_access_password_protected_and_draft_episodes() {
		$draft_id = $this->create_episode( array( 'post_status' => 'draft' ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertTrue( ssp_can_access_episode( $this->protected_id ) );
		$this->assertTrue( ssp_can_access_episode( $draft_id ) );
	}

	public function test_non_podcast_post_is_denied() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertFalse( ssp_can_access_episode( $post_id ) );
	}

	public function test_castos_authenticated_request_can_access_password_protected_episode_repeatedly() {
		$request = $this->castos_request( '/ssp/v1/episodes' );

		$this->assertTrue( ssp_can_access_episode( $this->protected_id, $request ) );
		$this->assertTrue( ssp_can_access_episode( $this->protected_id, $request ) );
	}

	public function test_castos_authentication_is_not_shared_with_a_replayed_request() {
		$request = $this->castos_request( '/ssp/v1/episodes' );
		$replay  = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		foreach ( $request->get_headers() as $name => $values ) {
			$replay->set_header( $name, $values );
		}
		$replay->set_body( $request->get_body() );

		$this->assertTrue( ssp_can_access_episode( $this->protected_id, $request ) );
		$this->assertFalse( ssp_can_access_episode( $this->protected_id, $replay ) );
	}

	public function test_access_filter_can_deny_access() {
		add_filter( 'ssp_can_access_episode', '__return_false' );

		$this->assertFalse( ssp_can_access_episode( $this->public_id ) );
	}

	/* Download route */

	public function test_download_route_refuses_password_protected_episode() {
		$this->expectException( \WPDieException::class );
		$this->expectExceptionCode( 403 );

		$this->check_file_access( $this->protected_id );
	}

	public function test_download_route_serves_published_episode() {
		$this->assertTrue( $this->check_file_access( $this->public_id ) );
	}

	public function test_download_route_serves_password_protected_episode_after_password_entry() {
		$this->enter_password( self::PASSWORD );

		$this->assertTrue( $this->check_file_access( $this->protected_id ) );
	}

	public function test_download_route_serves_password_protected_episode_restored_to_its_feed() {
		$this->restore_password_protected_in( $this->series_id );

		$this->assertTrue( $this->check_file_access( $this->protected_id ) );
	}

	public function test_download_route_refuses_episode_when_another_series_feed_is_restored() {
		$other_series_id = self::factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		add_filter(
			'ssp_feed_include_password_protected',
			function ( $include, $series_id ) use ( $other_series_id ) {
				return $series_id === $other_series_id;
			},
			10,
			2
		);

		$this->expectException( \WPDieException::class );
		$this->expectExceptionCode( 403 );

		$this->check_file_access( $this->protected_id );
	}

	public function test_download_route_serves_episode_when_the_default_feed_is_restored() {
		$default_series_id = self::factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		update_option( Settings_Controller::SETTINGS_BASE . 'default_series', $default_series_id );
		$this->restore_password_protected_in( $default_series_id );

		$this->assertTrue( $this->check_file_access( $this->protected_id ) );
	}

	public function test_download_route_refuses_unpublished_password_protected_episode_restored_to_its_feed() {
		$draft_id = $this->create_episode(
			array(
				'post_status'   => 'draft',
				'post_password' => self::PASSWORD,
			)
		);
		$this->restore_password_protected_in( $this->series_id );

		$this->expectException( \WPDieException::class );
		$this->expectExceptionCode( 403 );

		$this->check_file_access( $draft_id );
	}

	public function test_download_route_refuses_restored_episode_when_the_access_filter_denies_it() {
		$this->restore_password_protected_in( $this->series_id );
		add_filter( 'ssp_can_access_episode', '__return_false' );

		$this->expectException( \WPDieException::class );
		$this->expectExceptionCode( 403 );

		$this->check_file_access( $this->protected_id );
	}

	public function test_download_route_refuses_draft_episode_to_contributor() {
		$draft_id = $this->create_episode( array( 'post_status' => 'draft' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'contributor' ) ) );

		$this->expectException( \WPDieException::class );
		$this->expectExceptionCode( 403 );

		$this->check_file_access( $draft_id );
	}

	/* REST */

	public function test_rest_listing_hides_media_of_password_protected_episode_from_anonymous_visitor() {
		$items = $this->rest_items( new \WP_REST_Request( 'GET', '/ssp/v1/episodes' ) );

		$this->assertArrayHasKey( $this->protected_id, $items, 'Title and permalink stay listed.' );
		$protected = $items[ $this->protected_id ];
		$this->assertEmpty( $protected['download_link'] );
		$this->assertEmpty( $protected['player_link'] );
		$this->assertEmpty( $protected['audio_player'] );
		$this->assertArrayNotHasKey( 'meta', $protected );
		$this->assertNotEmpty( $protected['link'] );

		$public = $items[ $this->public_id ];
		$this->assertNotEmpty( $public['download_link'] );
		$this->assertNotEmpty( $public['player_link'] );
		$this->assertSame( self::AUDIO, $public['meta']['audio_file'] );
	}

	public function test_rest_listing_keeps_media_for_castos_authenticated_request() {
		$items = $this->rest_items( $this->castos_request( '/ssp/v1/episodes' ) );

		$protected = $items[ $this->protected_id ];
		$this->assertNotEmpty( $protected['download_link'] );
		$this->assertNotEmpty( $protected['player_link'] );
		$this->assertSame( self::AUDIO, $protected['meta']['audio_file'] );
	}

	public function test_rest_listing_keeps_meta_of_private_podcast_for_castos_authenticated_request() {
		update_option( Settings_Controller::SETTINGS_BASE . 'is_podcast_private_' . $this->series_id, 'yes' );

		$anonymous = $this->rest_items( new \WP_REST_Request( 'GET', '/ssp/v1/episodes' ) );
		$castos    = $this->rest_items( $this->castos_request( '/ssp/v1/episodes' ) );

		$this->assertArrayNotHasKey( 'meta', $anonymous[ $this->public_id ] );
		$this->assertSame( self::AUDIO, $castos[ $this->public_id ]['meta']['audio_file'] );
	}

	public function test_rest_listing_keeps_media_of_password_protected_episode_for_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$items = $this->rest_items( new \WP_REST_Request( 'GET', '/ssp/v1/episodes' ) );

		$protected = $items[ $this->protected_id ];
		$this->assertNotEmpty( $protected['download_link'] );
		$this->assertNotEmpty( $protected['player_link'] );
		$this->assertSame( self::AUDIO, $protected['meta']['audio_file'] );
	}

	public function test_core_rest_route_hides_meta_of_password_protected_episode() {
		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/' . SSP_CPT_PODCAST . '/' . $this->protected_id ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'meta', $response->get_data() );
		$this->assertEmpty( $response->get_data()['download_link'] );
	}

	public function test_audio_player_endpoint_refuses_password_protected_episode() {
		$_GET['ssp_podcast_id'] = (string) $this->protected_id;

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/ssp/v1/audio_player' ) );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_audio_player_endpoint_serves_password_protected_episode_to_castos() {
		$_GET['ssp_podcast_id'] = (string) $this->protected_id;

		$response = rest_do_request( $this->castos_request( '/ssp/v1/audio_player' ) );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_audio_player_endpoint_serves_published_episode() {
		$_GET['ssp_podcast_id'] = (string) $this->public_id;

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/ssp/v1/audio_player' ) );

		$this->assertSame( 200, $response->get_status() );
	}

	/* Playlist AJAX */

	public function test_playlist_items_omit_media_and_excerpt_of_password_protected_episode() {
		$items = $this->playlist_items( array( get_post( $this->public_id ), get_post( $this->protected_id ) ) );

		$this->assertCount( 2, $items );
		$this->assertNotEmpty( $items[0]['audio_file'] );
		$this->assertSame( '', $items[1]['audio_file'] );
		$this->assertSame( '', $items[1]['excerpt'] );
		$this->assertSame( 'Protected episode', $items[1]['title'] );
	}

	/* Feeds */

	public function test_ssp_feed_excludes_password_protected_episode() {
		$ids = $this->ssp_feed_ids();

		$this->assertContains( $this->public_id, $ids );
		$this->assertNotContains( $this->protected_id, $ids );
	}

	public function test_restore_filter_returns_password_protected_episode_to_ssp_feed() {
		$this->restore_password_protected_in( $this->series_id );

		$this->assertContains( $this->protected_id, $this->ssp_feed_ids() );
	}

	public function test_core_series_feed_excludes_password_protected_episode() {
		$ids = $this->core_series_feed_ids();

		$this->assertContains( $this->public_id, $ids );
		$this->assertNotContains( $this->protected_id, $ids );
	}

	public function test_restore_filter_returns_password_protected_episode_to_core_series_feed() {
		$this->restore_password_protected_in( $this->series_id );

		$this->assertContains( $this->protected_id, $this->core_series_feed_ids() );
	}

	/* Helpers */

	private function create_episode( $args = array() ) {
		$episode_id = self::factory()->post->create(
			array_merge(
				array(
					'post_type'    => SSP_CPT_PODCAST,
					'post_status'  => 'publish',
					'post_excerpt' => 'Episode excerpt',
				),
				$args
			)
		);
		update_post_meta( $episode_id, 'audio_file', self::AUDIO );
		wp_set_object_terms( $episode_id, array( $this->series_id ), ssp_series_taxonomy() );

		return $episode_id;
	}

	/** Restores password-protected episodes to one series feed only. */
	private function restore_password_protected_in( $restored_series_id ) {
		add_filter(
			'ssp_feed_include_password_protected',
			function ( $include, $series_id ) use ( $restored_series_id ) {
				return $series_id === $restored_series_id;
			},
			10,
			2
		);
	}

	/** Sets the cookie WordPress stores after a visitor submits the post password form. */
	private function enter_password( $password ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$hasher = new \PasswordHash( 8, true );

		$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = $hasher->HashPassword( $password );
	}

	private function check_file_access( $episode_id ) {
		$controller = ssp_frontend_controller();
		$method     = new \ReflectionMethod( $controller, 'check_episode_file_access' );
		$method->setAccessible( true );

		return $method->invoke( $controller, $episode_id, get_post( $episode_id ) );
	}

	private function rest_items( $request ) {
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$items = array();
		foreach ( $response->get_data() as $item ) {
			$items[ $item['id'] ] = $item;
		}

		return $items;
	}

	private function playlist_items( $episodes ) {
		$controller = ssp_app()->players_controller;
		$method     = new \ReflectionMethod( $controller, 'prepare_playlist_items' );
		$method->setAccessible( true );

		return $method->invoke( $controller, $episodes );
	}

	private function ssp_feed_ids() {
		$series = get_term( $this->series_id, ssp_series_taxonomy() );
		$query  = $this->app_property( 'feed_handler' )->get_feed_query( $series->slug, array(), 'published' );

		return wp_list_pluck( $query->posts, 'ID' );
	}

	private function core_series_feed_ids() {
		$this->go_to( html_entity_decode( get_term_feed_link( $this->series_id, ssp_series_taxonomy() ) ) );

		return wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' );
	}

	private function app_property( $name ) {
		$property = new \ReflectionProperty( 'SeriouslySimplePodcasting\Controllers\App_Controller', $name );
		$property->setAccessible( true );

		return $property->getValue( ssp_app() );
	}

	/** Builds a GET request with a valid Castos action-bound signature (see EpisodesRestControllerCastosAuthTest). */
	private function castos_request( $route ) {
		$timestamp = time();
		$nonce     = bin2hex( random_bytes( 32 ) );
		$home_path = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		$path      = $home_path . '/' . trim( rest_get_url_prefix(), '/' ) . $route;
		$message   = implode( "\n", array( 'GET', $path, '', json_encode( array() ), $timestamp, $nonce ) );

		$request = new \WP_REST_Request( 'GET', $route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( json_encode( array() ) );
		$request->set_header( 'X-Castos-Signature', hash_hmac( 'sha256', $message, self::TOKEN ) );
		$request->set_header( 'X-Castos-Timestamp', $timestamp );
		$request->set_header( 'X-Castos-Nonce', $nonce );

		return $request;
	}
}
