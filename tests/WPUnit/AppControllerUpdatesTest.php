<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\Archive_Page_Handler;
use SeriouslySimplePodcasting\Handlers\Upgrade_Handler;

class AppControllerUpdatesTest extends \Codeception\TestCase\WPTestCase {
	private $original_rewrite;
	private $original_user_id;

	protected function setUp(): void {
		parent::setUp();
		$this->original_user_id = get_current_user_id();
		global $wp_rewrite;
		$this->original_rewrite = clone $wp_rewrite;
		add_filter( 'flush_rewrite_rules_hard', '__return_false' );
	}

	protected function tearDown(): void {
		global $wp_rewrite;
		$wp_rewrite = $this->original_rewrite;
		remove_filter( 'flush_rewrite_rules_hard', '__return_false' );
		wp_set_current_user( $this->original_user_id );
		parent::tearDown();
	}

	public function testFreshInstallSeedsDefaultsWithoutRunningMigrations(): void {
		delete_option( 'ssp_version' );
		delete_option( 'ss_podcasting_subscribe_options' );
		delete_option( 'ss_podcasting_episode_description' );
		delete_option( Admin_Notifications_Handler::CONSTANT_NOTICES_KEY );

		ssp_app()->maybe_run_plugin_updates();

		$this->assertSame( SSP_VERSION, get_option( 'ssp_version' ) );
		$this->assertSame( array( 'apple_podcasts', 'spotify' ), get_option( 'ss_podcasting_subscribe_options' ) );
		$this->assertFalse( get_option( 'ss_podcasting_episode_description' ) );
		$notices = get_option( Admin_Notifications_Handler::CONSTANT_NOTICES_KEY, array() );
		$this->assertArrayNotHasKey( Upgrade_Handler::XSLT_NOTICE_KEY, $notices );
	}

	public function testFreshInstallPreservesExistingSubscribeOptions(): void {
		delete_option( 'ssp_version' );
		$subscribe_options = array( 'custom_directory', 'spotify' );
		update_option( 'ss_podcasting_subscribe_options', $subscribe_options );

		ssp_app()->maybe_run_plugin_updates();

		$this->assertSame( SSP_VERSION, get_option( 'ssp_version' ) );
		$this->assertSame( $subscribe_options, get_option( 'ss_podcasting_subscribe_options' ) );
	}

	public function testFreshInstallSetsUpSeriesAndRewrites(): void {
		global $wp_rewrite;
		delete_option( 'ssp_version' );
		delete_option( 'ss_podcasting_default_series' );
		unset( $wp_rewrite->extra_rules_top['^podcast-download/([^/]*)/([^/]*)/?'] );

		ssp_app()->maybe_run_plugin_updates();

		$series_id = ssp_get_option( 'default_series' );
		$this->assertNotEmpty( $series_id );
		$this->assertInstanceOf( \WP_Term::class, get_term( (int) $series_id, ssp_series_taxonomy() ) );
		$this->assertSame(
			'index.php?podcast_episode=$matches[1]',
			$wp_rewrite->extra_rules_top['^podcast-download/([^/]*)/([^/]*)/?'] ?? null
		);
	}

	public function testUpgradeStillSetsUpSeriesAndRewrites(): void {
		global $wp_rewrite;
		update_option( 'ssp_version', '3.17.0' );
		delete_option( 'ss_podcasting_default_series' );
		unset( $wp_rewrite->extra_rules_top['^podcast-download/([^/]*)/([^/]*)/?'] );

		ssp_app()->maybe_run_plugin_updates();

		$series_id = ssp_get_option( 'default_series' );
		$this->assertNotEmpty( $series_id );
		$this->assertInstanceOf( \WP_Term::class, get_term( (int) $series_id, ssp_series_taxonomy() ) );
		$this->assertSame(
			'index.php?podcast_episode=$matches[1]',
			$wp_rewrite->extra_rules_top['^podcast-download/([^/]*)/([^/]*)/?'] ?? null
		);
	}

	/** @dataProvider updateVersions */
	public function testUpdatesCheckUploadDirectory( $version ): void {
		if ( null === $version ) {
			delete_option( 'ssp_version' );
		} else {
			update_option( 'ssp_version', $version );
		}
		$priority = has_action( 'admin_notices', 'ssp_cannot_write_uploads_dir_error' );
		if ( false !== $priority ) {
			remove_action( 'admin_notices', 'ssp_cannot_write_uploads_dir_error', $priority );
		}
		// A path under an existing file cannot be created; use a unique path to avoid WP's tested-path cache.
		$unwritable_path = __FILE__ . '/ssp-upload-test-' . uniqid();
		$upload_error = static function ( $uploads ) use ( $unwritable_path ) {
			$uploads['path'] = $unwritable_path;
			return $uploads;
		};
		add_filter( 'upload_dir', $upload_error );
		try {
			ssp_app()->maybe_run_plugin_updates();
			$this->assertNotFalse( has_action( 'admin_notices', 'ssp_cannot_write_uploads_dir_error' ) );
		} finally {
			remove_filter( 'upload_dir', $upload_error );
			remove_action( 'admin_notices', 'ssp_cannot_write_uploads_dir_error' );
			if ( false !== $priority ) {
				add_action( 'admin_notices', 'ssp_cannot_write_uploads_dir_error', $priority );
			}
		}
	}

	public static function updateVersions(): array {
		return array( 'fresh install' => array( null ), 'upgrade' => array( '3.17.0' ) );
	}

	private function getPageIds(): array {
		return get_posts( array(
			'post_type'   => 'page',
			'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft' ),
			'numberposts' => -1,
			'fields'      => 'ids',
			'orderby'     => 'ID',
			'order'       => 'ASC',
		) );
	}

	public function testFreshInstallCreatesArchivePageWithoutActivationHook(): void {
		delete_option( 'ssp_version' );
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$pages_before = $this->getPageIds();

		ssp_app()->maybe_run_plugin_updates();

		$page_id = (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$this->assertGreaterThan( 0, $page_id );
		$this->assertSame( 'page', get_post_type( $page_id ) );
		$this->assertSame( 'publish', get_post_status( $page_id ) );
		$this->assertSame( array( $page_id ), array_values( array_diff( $this->getPageIds(), $pages_before ) ) );
	}

	public function testActivationCreatesOneArchivePageBeforeFreshInstallUpdate(): void {
		delete_option( 'ssp_version' );
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );

		$pages_before_activation = $this->getPageIds();
		ssp_app()->activate();

		$page_id = (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$this->assertGreaterThan( 0, $page_id );
		$pages_before = $this->getPageIds();
		$this->assertSame( array( $page_id ), array_values( array_diff( $pages_before, $pages_before_activation ) ) );

		ssp_app()->maybe_run_plugin_updates();

		$this->assertSame( SSP_VERSION, get_option( 'ssp_version' ) );
		$this->assertSame( $page_id, (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID ) );
		$this->assertSame( $pages_before, $this->getPageIds() );
	}

	public function testFreshInstallAsSubscriberUsesAdministratorAsArchivePageAuthor(): void {
		delete_option( 'ssp_version' );
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$this->factory()->user->create( array( 'role' => 'administrator' ) );
		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		ssp_app()->maybe_run_plugin_updates();

		$page_id = (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$this->assertGreaterThan( 0, $page_id );
		$page = get_post( $page_id );
		$this->assertInstanceOf( \WP_Post::class, $page );
		$this->assertNotSame( $subscriber_id, (int) $page->post_author );
		$author = get_userdata( (int) $page->post_author );
		$this->assertInstanceOf( \WP_User::class, $author );
		$this->assertContains( 'administrator', $author->roles );
	}

	public function testFreshInstallUsesCustomRoleManagerAsArchivePageAuthor(): void {
		delete_option( 'ssp_version' );
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		foreach ( get_users() as $user ) {
			if ( in_array( 'administrator', $user->roles, true ) || user_can( $user, 'manage_options' ) ) {
				$user->set_role( 'subscriber' );
				$user->remove_cap( 'manage_options' );
			}
		}
		add_role( 'ssp_test_manager', 'SSP Test Manager', array( 'read' => true, 'manage_options' => true ) );
		try {
			$manager_id = $this->factory()->user->create( array( 'role' => 'ssp_test_manager' ) );
			wp_set_current_user( 0 );

			ssp_app()->maybe_run_plugin_updates();

			$page_id = (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
			$this->assertGreaterThan( 0, $page_id );
			$page = get_post( $page_id );
			$this->assertInstanceOf( \WP_Post::class, $page );
			$this->assertSame( $manager_id, (int) $page->post_author );
		} finally {
			remove_role( 'ssp_test_manager' );
		}
	}

	public function testFreshInstallSkipsAdministratorDeniedManageOptionsAsArchivePageAuthor(): void {
		delete_option( 'ssp_version' );
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		foreach ( get_users() as $user ) {
			if ( in_array( 'administrator', $user->roles, true ) || user_can( $user, 'manage_options' ) ) {
				$user->set_role( 'subscriber' );
				$user->remove_cap( 'manage_options' );
			}
		}
		$denied_id = $this->factory()->user->create( array( 'role' => 'administrator', 'user_login' => 'ssp_admin_a' ) );
		$allowed_id = $this->factory()->user->create( array( 'role' => 'administrator', 'user_login' => 'ssp_admin_b' ) );
		$denied = get_userdata( $denied_id );
		$denied->add_cap( 'manage_options', false );
		wp_set_current_user( 0 );

		ssp_app()->maybe_run_plugin_updates();

		$page_id = (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$this->assertGreaterThan( 0, $page_id );
		$page = get_post( $page_id );
		$this->assertInstanceOf( \WP_Post::class, $page );
		$this->assertNotSame( $denied_id, (int) $page->post_author );
		$this->assertSame( $allowed_id, (int) $page->post_author );
	}

	public function testFreshInstallWithoutLoggedInUserUsesAdministratorInsteadOfUserOne(): void {
		delete_option( 'ssp_version' );
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$user_one = get_userdata( 1 );
		if ( $user_one ) {
			$user_one->set_role( 'subscriber' );
		}
		$this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( 0 );

		ssp_app()->maybe_run_plugin_updates();

		$page_id = (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$this->assertGreaterThan( 0, $page_id );
		$page = get_post( $page_id );
		$this->assertInstanceOf( \WP_Post::class, $page );
		$this->assertNotSame( 1, (int) $page->post_author );
		$author = get_userdata( (int) $page->post_author );
		$this->assertInstanceOf( \WP_User::class, $author );
		$this->assertContains( 'administrator', $author->roles );
	}

	public function testFreshInstallWithoutEligibleAuthorCreatesNoPage(): void {
		delete_option( 'ssp_version' );
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		foreach ( get_users( array( 'role' => 'administrator' ) ) as $administrator ) {
			$administrator->set_role( 'subscriber' );
		}
		wp_set_current_user( 0 );
		$pages_before = $this->getPageIds();

		ssp_app()->maybe_run_plugin_updates();

		$this->assertFalse( get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID ) );
		$this->assertSame( $pages_before, $this->getPageIds() );
	}

	public function testActivationAsAdministratorUsesCurrentUserAsArchivePageAuthor(): void {
		delete_option( 'ssp_version' );
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$administrator_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $administrator_id );

		ssp_app()->activate();

		$page_id = (int) get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$this->assertGreaterThan( 0, $page_id );
		$page = get_post( $page_id );
		$this->assertInstanceOf( \WP_Post::class, $page );
		$this->assertSame( $administrator_id, (int) $page->post_author );
	}

	public function testFreshInstallPreservesTrashedArchivePageOption(): void {
		delete_option( 'ssp_version' );
		$page_id = $this->factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		wp_trash_post( $page_id );
		update_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID, $page_id );
		$option_before = get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$pages_before  = $this->getPageIds();

		ssp_app()->maybe_run_plugin_updates();

		$this->assertSame( $option_before, get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID ) );
		$this->assertSame( 'trash', get_post_status( $page_id ) );
		$this->assertSame( $pages_before, $this->getPageIds() );
	}

	public function testFreshInstallPreservesDeletedArchivePageOption(): void {
		delete_option( 'ssp_version' );
		$page_id = $this->factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		wp_delete_post( $page_id, true );
		update_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID, $page_id );
		$option_before = get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$pages_before  = $this->getPageIds();

		ssp_app()->maybe_run_plugin_updates();

		$this->assertSame( $option_before, get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID ) );
		$this->assertNull( get_post( $page_id ) );
		$this->assertSame( $pages_before, $this->getPageIds() );
	}

	public function testUpgradeDoesNotCreateArchivePage(): void {
		update_option( 'ssp_version', '3.17.0' );
		delete_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID );
		$pages_before = $this->getPageIds();

		ssp_app()->maybe_run_plugin_updates();

		$this->assertFalse( get_option( Archive_Page_Handler::OPTION_PODCAST_PAGE_ID ) );
		$this->assertSame( $pages_before, $this->getPageIds() );
	}

	public function testOlderVersionRunsApplicableMigrations(): void {
		update_option( 'ssp_version', '1.20.4' );
		delete_option( 'ss_podcasting_episode_description' );
		$subscribe_options = array( 'custom_directory', 'spotify' );
		update_option( 'ss_podcasting_subscribe_options', $subscribe_options );

		ssp_app()->maybe_run_plugin_updates();

		$this->assertSame( SSP_VERSION, get_option( 'ssp_version' ) );
		$this->assertSame( 'excerpt', get_option( 'ss_podcasting_episode_description' ) );
		$this->assertSame( $subscribe_options, get_option( 'ss_podcasting_subscribe_options' ) );
	}

	public function testLegacyMigrationMayReplaceSubscribeOptions(): void {
		update_option( 'ssp_version', '1.19.20' );
		update_option( 'ss_podcasting_subscribe_options', array( 'custom_directory' ) );

		ssp_app()->maybe_run_plugin_updates();

		$this->assertSame( SSP_VERSION, get_option( 'ssp_version' ) );
		$this->assertSame(
			array( 'apple_podcasts', 'stitcher', 'google_podcasts', 'spotify' ),
			get_option( 'ss_podcasting_subscribe_options' )
		);
	}

	public function testSameVersionLeavesOptionsAndRewriteSetupAlone(): void {
		global $wp_rewrite;
		update_option( 'ssp_version', SSP_VERSION );
		update_option( 'ss_podcasting_subscribe_options', array( 'custom_directory' ) );
		update_option( 'ss_podcasting_episode_description', 'custom_description' );
		delete_option( 'ss_podcasting_default_series' );
		unset( $wp_rewrite->extra_rules_top['^podcast-download/([^/]*)/([^/]*)/?'] );

		ssp_app()->maybe_run_plugin_updates();

		$this->assertSame( SSP_VERSION, get_option( 'ssp_version' ) );
		$this->assertSame( array( 'custom_directory' ), get_option( 'ss_podcasting_subscribe_options' ) );
		$this->assertSame( 'custom_description', get_option( 'ss_podcasting_episode_description' ) );
		$this->assertFalse( get_option( 'ss_podcasting_default_series' ) );
		$this->assertArrayNotHasKey( '^podcast-download/([^/]*)/([^/]*)/?', $wp_rewrite->extra_rules_top );
	}
}
