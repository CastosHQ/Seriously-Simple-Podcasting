<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Settings_Controller;
use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\Ajax_Handler;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Repositories\Sync_Refusal_Repository;
use SeriouslySimplePodcasting\Handlers\Upgrade_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;

class XsltNoticeTest extends \Codeception\TestCase\WPTestCase {
	private $handler;
	private $original_user_id;
	private $original_screen;
	private $original_get;
	private $original_post;
	private $ajax_handler;

	protected function setUp(): void {
		parent::setUp();
		global $current_screen;
		$this->original_screen = $current_screen;
		$this->original_user_id = get_current_user_id();
		$this->original_get = $_GET;
		$this->original_post = $_POST;
		$_GET = array();
		$_POST = array();
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		set_current_screen( 'edit-' . SSP_CPT_PODCAST );
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->handler = new Admin_Notifications_Handler( null, new Renderer() );
		delete_option( Admin_Notifications_Handler::CONSTANT_NOTICES_KEY );
		delete_transient( Admin_Notifications_Handler::NOTICES_KEY );
	}

	protected function tearDown(): void {
		global $current_screen;
		$current_screen = $this->original_screen;
		$_GET = $this->original_get;
		$_POST = $this->original_post;
		$this->removeObjectHooks( $this->handler );
		if ( $this->ajax_handler ) {
			$this->removeObjectHooks( $this->ajax_handler );
		}
		wp_set_current_user( $this->original_user_id );
		parent::tearDown();
	}

	/** @dataProvider upgradeVersions */
	public function testUpgradeNoticeFollowsVersionBoundary( $version, $expected ): void {
		ssp_app()->get_service( 'upgrade_handler' )->run_upgrades( $version );
		$this->assertSame( $expected, array_key_exists( Upgrade_Handler::XSLT_NOTICE_KEY, $this->handler->get_constant_notices() ) );
	}

	public static function upgradeVersions(): array {
		return array(
			'before release' => array( '3.17.0', true ),
			'pre-release'    => array( '3.18.0-alpha.4', true ),
			'release'        => array( '3.18.0', false ),
			'later release'  => array( '3.19.0', false ),
		);
	}

	public function testRepeatingMigrationLeavesOneNotice(): void {
		$upgrade = ssp_app()->get_service( 'upgrade_handler' );
		$upgrade->run_upgrades( '3.17.0' );
		$upgrade->run_upgrades( '3.17.0' );
		$notices = $this->handler->get_constant_notices();
		$this->assertCount( 1, $notices );
		$this->assertArrayHasKey( Upgrade_Handler::XSLT_NOTICE_KEY, $notices );
	}

	public function testNoticeShowsExpectedTextAndLink(): void {
		ssp_app()->get_service( 'upgrade_handler' )->run_upgrades( '3.17.0' );
		$html = $this->renderNotices();
		$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );
		$this->assertSame(
			"Seriously Simple Podcasting: Browsers are ending support for styled RSS feeds, so we've removed the stylesheet from your feed. Opening your feed in a browser will now show raw XML. This is normal, and Apple Podcasts, Spotify, and other directories aren't affected. No action needed. Learn more",
			$text
		);
		$this->assertStringContainsString( '<strong>Seriously Simple Podcasting:</strong>', $html );
		$this->assertMatchesRegularExpression( '~<a\s+href="https://wordpress\.org/support/topic/rss-feed-stylesheet-being-deprecated/"[^>]*>\s*Learn more\s*</a>~', $html );
		$this->assertMatchesRegularExpression( '/class="[^"]*\bnotice-warning\b[^\"]*"/', $html );
		$this->assertMatchesRegularExpression( '/class="[^"]*\bis-dismissible\b[^\"]*\bis-constant\b[^\"]*"/', $html );
	}

	public function testNoticeIsHiddenOnNonSspScreen(): void {
		ssp_app()->get_service( 'upgrade_handler' )->run_upgrades( '3.17.0' );
		set_current_screen( 'edit-post' );
		$this->assertSame( '', trim( $this->renderNotices() ) );
	}

	public function testRemovalIsSiteWideAndPersists(): void {
		ssp_app()->get_service( 'upgrade_handler' )->run_upgrades( '3.17.0' );
		$this->assertStringContainsString( 'Browsers are ending support', $this->renderNotices() );
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertStringContainsString( 'Browsers are ending support', $this->renderNotices() );
		$this->assertTrue( $this->handler->remove_constant_notice( Upgrade_Handler::XSLT_NOTICE_KEY ) );
		$this->assertArrayNotHasKey( Upgrade_Handler::XSLT_NOTICE_KEY, $this->handler->get_constant_notices() );
		$this->assertSame( '', trim( $this->renderNotices() ) );
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( '', trim( $this->renderNotices() ) );
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame( '', trim( $this->renderNotices() ) );
	}

	public function testMigrationPreservesExistingCastosDisconnectNotice(): void {
		$this->handler->add_constant_notice( 'Castos disconnected', Admin_Notifications_Handler::WARNING, Castos_Handler::DISCONNECT_NOTICE_KEY );
		$before = $this->handler->get_constant_notices();
		ssp_app()->get_service( 'upgrade_handler' )->run_upgrades( '3.17.0' );
		$after = $this->handler->get_constant_notices();
		$this->assertArrayHasKey( Upgrade_Handler::XSLT_NOTICE_KEY, $after );
		$this->assertArrayHasKey( Castos_Handler::DISCONNECT_NOTICE_KEY, $after );
		$this->assertSame( $before[ Castos_Handler::DISCONNECT_NOTICE_KEY ], $after[ Castos_Handler::DISCONNECT_NOTICE_KEY ] );
	}

	public function testRenderedNoticeCanBeDismissedViaAjax(): void {
		ssp_app()->get_service( 'upgrade_handler' )->run_upgrades( '3.17.0' );
		$attributes = $this->renderedNoticeAttributes();
		$_POST = array( 'id' => $attributes['id'], 'nonce' => $attributes['nonce'] );

		$response = $this->dismissViaAjax();

		$this->assertTrue( $response['success'] );
		$this->assertArrayNotHasKey( Upgrade_Handler::XSLT_NOTICE_KEY, $this->handler->get_constant_notices() );
		$this->assertSame( '', trim( $this->renderNotices() ) );
	}

	/** @dataProvider invalidAjaxNonces */
	public function testAjaxDismissalRejectsInvalidOrMissingNonce( $nonce ): void {
		ssp_app()->get_service( 'upgrade_handler' )->run_upgrades( '3.17.0' );
		$attributes = $this->renderedNoticeAttributes();
		$_POST = array( 'id' => $attributes['id'] );
		if ( null !== $nonce ) {
			$_POST['nonce'] = $nonce;
		}

		$response = $this->dismissViaAjax();

		$this->assertFalse( $response['success'] );
		$this->assertArrayHasKey( Upgrade_Handler::XSLT_NOTICE_KEY, $this->handler->get_constant_notices() );
		$this->assertStringContainsString( 'Browsers are ending support', $this->renderNotices() );
	}

	public static function invalidAjaxNonces(): array {
		return array( 'missing' => array( null ), 'invalid' => array( 'invalid' ) );
	}

	public function testNoticeAndAjaxHooksAreRegistered(): void {
		$this->handler->bootstrap();
		$this->makeAjaxHandler();
		$this->assertNotFalse( has_action( 'admin_notices', array( $this->handler, 'display_notices' ) ) );
		$this->assertNotFalse( has_action( 'wp_ajax_remove_constant_notice', array( $this->ajax_handler, 'remove_constant_notice' ) ) );
	}

	private function renderedNoticeAttributes(): array {
		$html = $this->renderNotices();
		$this->assertMatchesRegularExpression( '/data-id="([^"]+)"\s+data-nonce="([^"]+)"/', $html );
		preg_match( '/data-id="([^"]+)"\s+data-nonce="([^"]+)"/', $html, $matches );
		$this->assertSame( Upgrade_Handler::XSLT_NOTICE_KEY, $matches[1] );
		return array( 'id' => html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' ), 'nonce' => html_entity_decode( $matches[2], ENT_QUOTES, 'UTF-8' ) );
	}

	private function makeAjaxHandler(): void {
		$this->ajax_handler = new Ajax_Handler(
			null,
			$this->handler,
			$this->createMock( Settings_Controller::class ),
			$this->createMock( Sync_Refusal_Repository::class ),
			$this->createMock( Feed_Handler::class )
		);
	}

	private function dismissViaAjax(): array {
		$this->makeAjaxHandler();
		$die_filter = static function () {
			return static function () {
				throw new \Error( 'Ajax response complete' );
			};
		};
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', $die_filter );
		add_filter( 'wp_die_json_handler', $die_filter );
		ob_start();
		try {
			$this->ajax_handler->remove_constant_notice();
			$this->fail( 'The AJAX handler should terminate after sending JSON.' );
		} catch ( \Error $error ) {
			$this->assertSame( 'Ajax response complete', $error->getMessage() );
		} finally {
			$output = ob_get_clean();
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_filter( 'wp_die_ajax_handler', $die_filter );
			remove_filter( 'wp_die_json_handler', $die_filter );
		}
		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'Expected a JSON response: ' . $output );
		return $response;
	}

	private function removeObjectHooks( $object ): void {
		global $wp_filter;
		foreach ( $wp_filter as $hook_name => $hook ) {
			foreach ( $hook->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'];
					if ( is_array( $function ) && $function[0] === $object ) {
						remove_filter( $hook_name, $function, $priority );
					}
				}
			}
		}
	}

	private function renderNotices(): string {
		ob_start();
		try {
			$this->handler->display_notices();
			return ob_get_contents();
		} finally {
			ob_end_clean();
		}
	}
}
