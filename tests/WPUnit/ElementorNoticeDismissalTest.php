<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;

class ElementorNoticeDismissalTest extends \Codeception\TestCase\WPTestCase {
	const OPTION = 'ss_podcasting_elementor_templates_disabled';
	const ACTION = 'ssp_disable_elementor_template_notice';

	private $original_user_id;
	private $original_get;
	private $original_server;

	protected function setUp(): void {
		parent::setUp();
		$this->original_user_id = get_current_user_id();
		$this->original_get     = $_GET;
		$this->original_server  = $_SERVER;
		$_GET                   = array();
		update_option( self::OPTION, 'false' );
	}

	protected function tearDown(): void {
		$_GET    = $this->original_get;
		$_SERVER = $this->original_server;
		wp_set_current_user( $this->original_user_id );
		parent::tearDown();
	}

	public function testRequestWithoutNonceLeavesOptionUnchanged(): void {
		$this->set_manager_user();
		$_GET = array( self::ACTION => 'true' );

		ssp_app()->disable_elementor_template_notice();

		$this->assertSame( 'false', get_option( self::OPTION ) );
	}

	public function testRequestWithBadNonceLeavesOptionUnchanged(): void {
		$this->set_manager_user();
		$_GET = array(
			self::ACTION => 'true',
			'nonce'      => 'invalid',
		);

		ssp_app()->disable_elementor_template_notice();

		$this->assertSame( 'false', get_option( self::OPTION ) );
	}

	public function testUserWithoutCapabilityLeavesOptionUnchanged(): void {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertFalse( current_user_can( 'manage_podcast' ) );
		$_GET = array(
			self::ACTION => 'true',
			'nonce'      => wp_create_nonce( self::ACTION ),
		);

		ssp_app()->disable_elementor_template_notice();

		$this->assertSame( 'false', get_option( self::OPTION ) );
	}

	public function testValidRequestDisablesNotice(): void {
		$this->set_manager_user();
		$_GET = array(
			self::ACTION => 'true',
			'nonce'      => wp_create_nonce( self::ACTION ),
		);

		ssp_app()->disable_elementor_template_notice();

		$this->assertSame( 'true', get_option( self::OPTION ) );
	}

	public function testDismissLinkFromNoticeDisablesNotice(): void {
		$this->set_manager_user();
		$_SERVER['REQUEST_URI'] = '/wp-admin/edit.php?post_type=' . SSP_CPT_PODCAST;
		$handler                = new Admin_Notifications_Handler( null, new Renderer() );

		ob_start();
		$handler->show_elementor_templates_notice();
		$html = ob_get_clean();

		$this->assertSame( 1, preg_match( '/href="([^"]*' . self::ACTION . '[^"]*)"/', $html, $matches ) );
		wp_parse_str( (string) wp_parse_url( html_entity_decode( $matches[1] ), PHP_URL_QUERY ), $query );
		$_GET = $query;

		ssp_app()->disable_elementor_template_notice();

		$this->assertSame( 'true', get_option( self::OPTION ) );
	}

	public function testNoticeShownToUserWhoCanDismissIt(): void {
		$this->set_manager_user();

		$this->assertTrue( $this->is_notice_hooked() );
	}

	public function testNoticeHiddenFromUserWithoutCapability(): void {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'author' ) ) );
		$this->assertFalse( current_user_can( 'manage_podcast' ) );

		$this->assertFalse( $this->is_notice_hooked() );
	}

	private function is_notice_hooked(): bool {
		update_option( 'active_plugins', array( 'elementor/elementor.php' ) );
		$_GET     = array( 'post_type' => SSP_CPT_PODCAST );
		$handler  = new Admin_Notifications_Handler( null, new Renderer() );
		$callback = array( $handler, 'show_elementor_templates_notice' );

		$handler->show_elementor_templates_available();
		$hooked = false !== has_action( 'admin_notices', $callback );
		remove_action( 'admin_notices', $callback );

		return $hooked;
	}

	private function set_manager_user(): void {
		$user = $this->factory()->user->create_and_get( array( 'role' => 'administrator' ) );
		$user->add_cap( 'manage_podcast' );
		wp_set_current_user( $user->ID );
	}
}
