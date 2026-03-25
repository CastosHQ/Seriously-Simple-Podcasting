<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Archive_Page_Handler;

class ArchivePageTest extends \Codeception\TestCase\WPTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
	}

	protected function tearDown(): void
	{
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		parent::tearDown();
	}

	/**
	 * Returns the Archive_Page_Handler instance from the plugin container.
	 *
	 * @return \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler
	 */
	protected function get_archive_page_handler() {
		return ssp_app()->get_service( 'archive_page_handler' );
	}

	// =========================================================================
	// Archive_Page_Handler::create_podcast_archive_page()
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler::create_podcast_archive_page()
	 */
	public function testCreatesNewPageWhenNoneExists()
	{
		$page_id = $this->get_archive_page_handler()->create_podcast_archive_page();

		$this->assertIsInt( $page_id );
		$this->assertGreaterThan( 0, $page_id );

		$page = get_post( $page_id );
		$this->assertNotNull( $page );
		$this->assertEquals( $page_id, (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler::create_podcast_archive_page()
	 */
	public function testReturnsExistingPageWhenOptionSet()
	{
		$existing_page_id = $this->factory()->post->create( [
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_name'   => 'podcast',
			'post_title'  => 'Podcast',
		] );

		update_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID, $existing_page_id );

		$returned_id = $this->get_archive_page_handler()->create_podcast_archive_page();

		$this->assertEquals( $existing_page_id, $returned_id );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler::create_podcast_archive_page()
	 */
	public function testReusesExistingPageBySlug()
	{
		$existing_page_id = $this->factory()->post->create( [
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_name'   => 'ssp-podcast-archive',
			'post_title'  => 'Podcast',
		] );

		$returned_id = $this->get_archive_page_handler()->create_podcast_archive_page();

		$this->assertEquals( $existing_page_id, $returned_id );
		$this->assertEquals( $existing_page_id, (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler::create_podcast_archive_page()
	 */
	public function testRestoresPageFromTrash()
	{
		$page_id = $this->factory()->post->create( [
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => 'ssp-podcast-archive',
			'post_title'   => 'Podcast',
			'post_content' => '<!-- wp:seriously-simple-podcasting/podcast-list {"featuredImage":false,"excerpt":true,"player":true,"titleSize":"24"} /-->',
		] );

		// Trash it — WordPress mangles slug to ssp-podcast-archive__trashed.
		wp_trash_post( $page_id );

		$returned_id = $this->get_archive_page_handler()->create_podcast_archive_page();

		$this->assertEquals( $page_id, $returned_id );
		$this->assertEquals( 'publish', get_post_status( $returned_id ) );
		$this->assertEquals( $page_id, (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler::create_podcast_archive_page()
	 */
	public function testRespectsStaleOptionValue()
	{
		$trashed_page_id = $this->factory()->post->create( [
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_name'   => 'some-page',
			'post_title'  => 'Some Page',
		] );
		wp_trash_post( $trashed_page_id );

		update_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID, $trashed_page_id );

		$returned_id = $this->get_archive_page_handler()->create_podcast_archive_page();

		$this->assertEquals( 0, $returned_id, 'Should not override a stale option — the user had a page.' );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler::setup_podcast_archive_page()
	 */
	public function testSetupBypassesStaleOptionValue()
	{
		$trashed_page_id = $this->factory()->post->create( [
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_name'   => 'some-page',
			'post_title'  => 'Some Page',
		] );
		wp_trash_post( $trashed_page_id );

		update_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID, $trashed_page_id );

		$returned_id = $this->get_archive_page_handler()->setup_podcast_archive_page();

		$this->assertGreaterThan( 0, $returned_id );
		$this->assertNotEquals( 'trash', get_post_status( $returned_id ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler::setup_podcast_archive_page()
	 */
	public function testSetupRestoresOptionOnFailure()
	{
		$stale_page_id = 999999;
		update_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID, $stale_page_id );

		// Block page creation by pre-occupying the slug with a non-page post type.
		// setup_podcast_archive_page() will clear the option, fail to find a page by slug,
		// then create_page() should succeed — so we test the restore by hooking wp_insert_post.
		add_filter( 'wp_insert_post_empty_content', '__return_true' );

		$returned_id = $this->get_archive_page_handler()->setup_podcast_archive_page();

		remove_filter( 'wp_insert_post_empty_content', '__return_true' );

		$this->assertEquals( 0, $returned_id );
		$this->assertEquals( $stale_page_id, (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID ),
			'Should restore the old option value when creation fails.'
		);
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler::create_podcast_archive_page()
	 */
	public function testPageHasCorrectContent()
	{
		$page_id = $this->get_archive_page_handler()->create_podcast_archive_page();
		$page    = get_post( $page_id );

		$this->assertStringContainsString(
			'<!-- wp:seriously-simple-podcasting/podcast-list {"featuredImage":false,"excerpt":true,"player":true,"titleSize":"24"} /-->',
			$page->post_content
		);
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler::create_podcast_archive_page()
	 */
	public function testPageHasCorrectProperties()
	{
		$page_id = $this->get_archive_page_handler()->create_podcast_archive_page();
		$page    = get_post( $page_id );

		$this->assertEquals( 'page', $page->post_type );
		$this->assertEquals( 'publish', $page->post_status );
		$this->assertEquals( 'closed', $page->comment_status );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler::get_podcast_archive_page_content()
	 */
	public function testPageUsesShortcodeWhenBlockEditorDisabled()
	{
		// Simulate Classic Editor — disable block editor for pages.
		add_filter( 'use_block_editor_for_post_type', '__return_false' );

		$page_id = $this->get_archive_page_handler()->create_podcast_archive_page();
		$page    = get_post( $page_id );

		$this->assertStringContainsString( '[ssp_episode_list', $page->post_content );
		$this->assertStringContainsString( 'display_player="true"', $page->post_content );
		$this->assertStringNotContainsString( '<!-- wp:', $page->post_content );

		remove_filter( 'use_block_editor_for_post_type', '__return_false' );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Archive_Page_Handler::get_podcast_archive_page_content()
	 */
	public function testPageUsesBlockWhenBlockEditorEnabled()
	{
		$page_id = $this->get_archive_page_handler()->create_podcast_archive_page();
		$page    = get_post( $page_id );

		$this->assertStringContainsString( '<!-- wp:seriously-simple-podcasting/podcast-list', $page->post_content );
		$this->assertStringNotContainsString( '[ssp_episode_list', $page->post_content );
	}

	// =========================================================================
	// activate() — fresh install page creation
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Controllers\App_Controller::activate()
	 */
	public function testActivateCreatesPageOnFreshInstall()
	{
		// Simulate fresh install — no ssp_version option.
		delete_option( 'ssp_version' );

		ssp_app()->activate();

		$page_id = (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$this->assertGreaterThan( 0, $page_id );

		$page = get_post( $page_id );
		$this->assertNotNull( $page );
		$this->assertEquals( 'page', $page->post_type );
		$this->assertEquals( 'publish', $page->post_status );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Controllers\App_Controller::activate()
	 */
	public function testActivateDoesNotCreatePageOnUpgrade()
	{
		// Simulate upgrade — ssp_version exists.
		update_option( 'ssp_version', '3.14.0' );

		ssp_app()->activate();

		$page_id = get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$this->assertEmpty( $page_id );
	}

	// =========================================================================
	// Settings UI
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Renderers\Settings_Renderer::render_single_select_page()
	 */
	public function testSettingsRendererOutputsPageDropdown()
	{
		$page_id = $this->factory()->post->create( [
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => 'My Podcast Page',
		] );

		$renderer = \SeriouslySimplePodcasting\Renderers\Settings_Renderer::instance();
		$field    = [
			'id'      => 'podcast_page_id',
			'type'    => 'single_select_page',
			'default' => '',
		];

		$html = $renderer->render_field( $field, $page_id, Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );

		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( 'js-ssp-select2', $html );
		$this->assertStringContainsString( 'My Podcast Page (ID: ' . $page_id . ')', $html );
		$this->assertStringContainsString( 'selected', $html );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Renderers\Settings_Renderer::render_single_select_page()
	 */
	public function testSettingsRendererShowsNoneOptionWhenNoPageSelected()
	{
		// wp_dropdown_pages() returns empty when no pages exist, so create one.
		$this->factory()->post->create( [
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => 'Some Page',
		] );

		$renderer = \SeriouslySimplePodcasting\Renderers\Settings_Renderer::instance();
		$field    = [
			'id'      => 'podcast_page_id',
			'type'    => 'single_select_page',
			'default' => '',
		];

		$html = $renderer->render_field( $field, '', Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );

		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( 'Select a page', $html );
	}

	public function testPodcastPageIdFieldExistsInGeneralSettings()
	{
		$settings = ssp_config( 'settings/general', array( 'post_type_options' => array() ) );
		$field_ids = array_column( $settings['fields'], 'id' );

		$this->assertContains( 'podcast_page_id', $field_ids );
	}

	public function testPodcastPageIdFieldIsSingleSelectPage()
	{
		$settings = ssp_config( 'settings/general', array( 'post_type_options' => array() ) );
		$fields   = array_column( $settings['fields'], 'type', 'id' );

		$this->assertEquals( 'single_select_page', $fields['podcast_page_id'] );
	}

	// =========================================================================
	// Routing — has_archive is always true
	// =========================================================================

	/**
	 * has_archive is always true regardless of page assignment.
	 * The archive URL is controlled by the rewrite slug, and template_include
	 * serves the page content when a page is assigned.
	 *
	 * @covers \SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler::register_post_type()
	 */
	public function testHasArchiveIsAlwaysTrue()
	{
		// With page assigned.
		$page_id = $this->factory()->post->create( [
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_name'   => 'ssp-podcast-archive',
		] );
		update_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID, $page_id );

		$handler = ssp_app()->get_service( 'cpt_podcast_handler' );
		$handler->register_post_type();

		$this->assertTrue( get_post_type_object( SSP_CPT_PODCAST )->has_archive );

		// Without page assigned.
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$handler->register_post_type();

		$this->assertTrue( get_post_type_object( SSP_CPT_PODCAST )->has_archive );
	}

	// =========================================================================
	// Template override: serve page instead of CPT archive
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Controllers\Frontend_Controller::maybe_serve_archive_page()
	 */
	public function testServeArchivePageReturnsOriginalTemplateWhenNoPageAssigned()
	{
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );

		$this->go_to( home_url( '/podcast/' ) );

		// The filter is already registered by the plugin bootstrap.
		$result = apply_filters( 'template_include', '/some/archive-podcast.php' );

		$this->assertEquals( '/some/archive-podcast.php', $result );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Controllers\Frontend_Controller::maybe_serve_archive_page()
	 */
	public function testServeArchivePageReplacesQueryWhenPageAssigned()
	{
		global $wpdb;

		$page_id = $this->factory()->post->create( [
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => 'Podcast',
		] );

		$wpdb->update( $wpdb->posts, [ 'post_name' => 'podcast-archive-test' ], [ 'ID' => $page_id ] );
		clean_post_cache( $page_id );

		update_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID, $page_id );

		// Use query string to simulate CPT archive (rewrite rules may not be loaded in tests).
		$this->go_to( home_url( '?post_type=podcast' ) );
		$this->assertTrue( is_post_type_archive( SSP_CPT_PODCAST ), 'Should be podcast archive' );

		apply_filters( 'template_include', '/some/archive-podcast.php' );

		global $wp_query;
		$this->assertTrue( $wp_query->is_page );
		$this->assertFalse( $wp_query->is_archive );
		$this->assertEquals( $page_id, $wp_query->queried_object_id );
	}

	// =========================================================================
	// Admin notice for existing installs
	// =========================================================================

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::maybe_show_archive_page_notice()
	 */
	public function testArchivePageNoticeNotShownWhenDismissed()
	{
		update_option( Archive_Page_Handler::OPTION_NOTICE_DISMISSED, true );
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );

		$handler = new \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler( $this->get_archive_page_handler(), new \SeriouslySimplePodcasting\Renderers\Renderer() );

		ob_start();
		$handler->maybe_show_archive_page_notice();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler::maybe_show_archive_page_notice()
	 */
	public function testArchivePageNoticeNotShownWhenPageAssigned()
	{
		delete_option( Archive_Page_Handler::OPTION_NOTICE_DISMISSED );

		$page_id = $this->factory()->post->create( [
			'post_type'   => 'page',
			'post_status' => 'publish',
		] );
		update_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID, $page_id );

		$handler = new \SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler( $this->get_archive_page_handler(), new \SeriouslySimplePodcasting\Renderers\Renderer() );

		ob_start();
		$handler->maybe_show_archive_page_notice();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}
}
