<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;
use SeriouslySimplePodcasting\Repositories\Sync_Refusal_Repository;

/**
 * Verifies that Castos credentials stay within the configured origin and editor capability boundary.
 *
 * @since 3.17.1
 */
class CastosTokenExposureTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * Token used by the request and editor assertions.
	 *
	 * @var string
	 */
	const API_TOKEN = 'test-castos-token';

	/**
	 * Fresh handler used to exercise the registered HTTP filter.
	 *
	 * @var Castos_Handler
	 */
	protected $castos_handler;

	/**
	 * The application handler whose filter is temporarily replaced by the fresh handler.
	 *
	 * @var Castos_Handler
	 */
	protected $registered_castos_handler;

	/**
	 * The option value before this test class changed it.
	 *
	 * @var mixed
	 */
	protected $original_api_token;

	/**
	 * Prepare an isolated Castos handler and token option for each test.
	 *
	 * @since 3.17.1
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->registered_castos_handler = ssp_get_service( 'castos_handler' );
		remove_filter( 'http_request_args', array( $this->registered_castos_handler, 'authorization_headers' ), 10 );

		$this->original_api_token = get_option( Castos_Handler::API_TOKEN_OPTION, null );
		update_option( Castos_Handler::API_TOKEN_OPTION, self::API_TOKEN );

		$this->castos_handler = new Castos_Handler(
			ssp_get_service( 'feed_handler' ),
			new Log_Helper(),
			ssp_get_service( 'admin_notices_handler' ),
			new Sync_Refusal_Repository()
		);
	}

	/**
	 * Restore the application's HTTP filter, token option, and editor globals.
	 *
	 * @since 3.17.1
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		remove_filter( 'http_request_args', array( $this->castos_handler, 'authorization_headers' ), 10 );
		add_filter( 'http_request_args', array( $this->registered_castos_handler, 'authorization_headers' ), 10, 2 );

		if ( null === $this->original_api_token ) {
			delete_option( Castos_Handler::API_TOKEN_OPTION );
		} else {
			update_option( Castos_Handler::API_TOKEN_OPTION, $this->original_api_token );
		}

		wp_set_current_user( 0 );
		wp_dequeue_script( 'ssp-fileupload' );
		wp_deregister_script( 'ssp-fileupload' );
		unset( $GLOBALS['post'] );

		parent::tearDown();
	}

	/**
	 * The active configured scheme, host, and effective port receive the token.
	 *
	 * The request is built from the active constant so the same assertion follows a
	 * staging override supplied before WordPress loads the plugin. The constant cannot
	 * be redefined after the WPUnit process has started.
	 *
	 * @since 3.17.1
	 *
	 * @covers \SeriouslySimplePodcasting\Handlers\Castos_Handler::authorization_headers
	 *
	 * @return void
	 */
	public function testAttachesTokenToConfiguredCastosOrigin() {
		$configured_url = wp_parse_url( SSP_CASTOS_APP_URL );
		$default_port   = 'https' === strtolower( $configured_url['scheme'] ) ? 443 : 80;
		$port           = isset( $configured_url['port'] ) ? $configured_url['port'] : $default_port;
		$url            = strtoupper( $configured_url['scheme'] ) . '://' . strtoupper( $configured_url['host'] ) . ':' . $port . '/api/v2/files';

		$args = $this->apply_http_request_filter( $url );

		$this->assertSame( 'Bearer ' . self::API_TOKEN, $args['headers']['Authorization'] );

		$args = $this->apply_http_request_filter( SSP_CASTOS_APP_URL . 'api/v2/files' );

		$this->assertSame( 'Bearer ' . self::API_TOKEN, $args['headers']['Authorization'] );
	}

	/**
	 * Requests with a different origin or an invalid URL receive no token.
	 *
	 * @since 3.17.1
	 *
	 * @covers \SeriouslySimplePodcasting\Handlers\Castos_Handler::authorization_headers
	 *
	 * @return void
	 */
	public function testDoesNotAttachTokenOutsideConfiguredCastosOrigin() {
		$configured_url = wp_parse_url( SSP_CASTOS_APP_URL );
		$scheme         = strtolower( $configured_url['scheme'] );
		$host           = strtolower( $configured_url['host'] );
		$default_port   = 'https' === $scheme ? 443 : 80;
		$port           = isset( $configured_url['port'] ) ? (int) $configured_url['port'] : $default_port;
		$other_port     = 65535 === $port ? 65534 : $port + 1;
		$configured_url = rtrim( SSP_CASTOS_APP_URL, '/' );

		$urls = array(
			'URL in path'       => 'https://example.org/' . $configured_url . '/api/v2',
			'URL in query'      => 'https://example.org/?redirect=' . $configured_url . '/api/v2',
			'host suffix'       => $scheme . '://' . $host . '.example.org/api/v2',
			'userinfo'          => $scheme . '://' . $host . '@example.org/api/v2',
			'different port'    => $scheme . '://' . $host . ':' . $other_port . '/api/v2',
			'missing host'      => $scheme . ':///api/v2',
			'malformed URL'     => 'not a URL',
		);

		if ( 'https' === $scheme ) {
			$urls['plain HTTP'] = 'http://' . $host . '/api/v2';
		}

		foreach ( $urls as $case => $url ) {
			$args = $this->apply_http_request_filter( $url );

			$this->assertArrayNotHasKey( 'Authorization', $args['headers'], $case . ' must not receive the Castos token' );
		}
	}

	/**
	 * Contributors do not receive upload credentials in the episode editor.
	 *
	 * @since 3.17.1
	 *
	 * @covers \SeriouslySimplePodcasting\Controllers\Assets_Controller::enqueue_admin_scripts
	 *
	 * @return void
	 */
	public function testContributorDoesNotReceiveUploadCredentials() {
		$localized_data = $this->enqueue_episode_editor_scripts( 'contributor' );

		$this->assertFalse( $localized_data );
		$this->assertTrue( wp_script_is( 'ssp-fileupload', 'enqueued' ) );
	}

	/**
	 * Authors retain the Castos uploader and its localized credentials.
	 *
	 * @since 3.17.1
	 *
	 * @covers \SeriouslySimplePodcasting\Controllers\Assets_Controller::enqueue_admin_scripts
	 *
	 * @return void
	 */
	public function testAuthorReceivesUploadCredentials() {
		$this->assertUploadCredentialsLocalizedForRole( 'author' );
	}

	/**
	 * Podcast Editors retain the Castos uploader and its localized credentials.
	 *
	 * @since 3.17.1
	 *
	 * @covers \SeriouslySimplePodcasting\Controllers\Assets_Controller::enqueue_admin_scripts
	 *
	 * @return void
	 */
	public function testPodcastEditorReceivesUploadCredentials() {
		ssp_get_service( 'roles_handler' )->add_podcast_editor_role();

		$this->assertUploadCredentialsLocalizedForRole( 'podcast_editor' );
	}

	/**
	 * Podcast Managers retain the Castos uploader and its localized credentials.
	 *
	 * @since 3.17.1
	 *
	 * @covers \SeriouslySimplePodcasting\Controllers\Assets_Controller::enqueue_admin_scripts
	 *
	 * @return void
	 */
	public function testPodcastManagerReceivesUploadCredentials() {
		ssp_get_service( 'roles_handler' )->add_podcast_manager_role();

		$this->assertUploadCredentialsLocalizedForRole( 'podcast_manager' );
	}

	/**
	 * Apply the real HTTP request filter with an empty request header set.
	 *
	 * @since 3.17.1
	 *
	 * @param string $url Request URL.
	 *
	 * @return array Filtered request arguments.
	 */
	protected function apply_http_request_filter( $url ) {
		return apply_filters(
			'http_request_args',
			array( 'headers' => array() ),
			$url
		);
	}

	/**
	 * Assert that the real admin enqueue path localizes credentials for a role.
	 *
	 * @since 3.17.1
	 *
	 * @param string $role WordPress role.
	 *
	 * @return void
	 */
	protected function assertUploadCredentialsLocalizedForRole( $role ) {
		$localized_data = $this->enqueue_episode_editor_scripts( $role );

		$this->assertIsString( $localized_data );
		$this->assertStringContainsString( self::API_TOKEN, $localized_data );
	}

	/**
	 * Run the actual episode editor enqueue hook and return localized script data.
	 *
	 * @since 3.17.1
	 *
	 * @param string $role WordPress role.
	 *
	 * @return string|false Localized upload credentials or false.
	 */
	protected function enqueue_episode_editor_scripts( $role ) {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );

		$episode_id = self::factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => 'publish',
				'post_author' => $user_id,
			)
		);

		$GLOBALS['post'] = get_post( $episode_id );
		set_current_screen( 'post.php' );
		get_current_screen()->post_type = SSP_CPT_PODCAST;
		wp_dequeue_script( 'ssp-fileupload' );
		wp_deregister_script( 'ssp-fileupload' );

		do_action( 'admin_enqueue_scripts', 'post.php' );

		return wp_scripts()->get_data( 'ssp-fileupload', 'data' );
	}
}
