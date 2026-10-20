<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Integrations\Blocks\Castos_Html_Player_Block;

/** Verifies episode rendering access at the shortcode, block and widget render callers. */
class EpisodeRenderAccessTest extends \Codeception\TestCase\WPTestCase {

	const TITLE    = 'Secret render title';
	const BODY     = 'Secret render body';
	const EXCERPT  = 'Secret render excerpt';
	const AUDIO    = 'https://example.com/secret-render-audio.mp3';
	const PASSWORD = 'render-password';

	private $users;
	private $cookies_backup;
	private $user_backup;
	private $globals_backup;

	protected function setUp(): void {
		parent::setUp();
		$this->cookies_backup = $_COOKIE;
		$this->user_backup    = get_current_user_id();
		$this->globals_backup = array_intersect_key( $GLOBALS, array_flip( array( 'post', 'episode_context' ) ) );
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
		wp_set_current_user( 0 );

		// WordPress unregisters custom post types and taxonomies between tests.
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		$property = new \ReflectionProperty( 'SeriouslySimplePodcasting\Controllers\App_Controller', 'series_controller' );
		$property->setAccessible( true );
		$property->getValue( ssp_app() )->register_taxonomy();

		$this->users = array( 'anonymous' => 0 );
		foreach ( array( 'author', 'contributor', 'editor' ) as $role ) {
			$this->users[ $role ] = self::factory()->user->create( array( 'role' => $role ) );
		}
	}

	protected function tearDown(): void {
		remove_filter( 'ssp_can_access_episode', '__return_false' );
		$_COOKIE = $this->cookies_backup;
		wp_set_current_user( $this->user_backup );
		foreach ( array( 'post', 'episode_context' ) as $name ) {
			if ( array_key_exists( $name, $this->globals_backup ) ) {
				$GLOBALS[ $name ] = $this->globals_backup[ $name ];
			} else {
				unset( $GLOBALS[ $name ] );
			}
		}
		parent::tearDown();
	}

	/** @dataProvider denied_episode_cases */
	public function test_podcast_episode_hides_inaccessible_content( $viewer, $post_type, $status, $password ) {
		$id = $this->create_episode( array( 'post_type' => $post_type, 'post_status' => $status, 'post_password' => $password ) );
		wp_set_current_user( $this->users[ $viewer ] );

		$this->assert_hidden( $this->render_episode( $id ) );
	}

	public static function denied_episode_cases() {
		return array(
			'contributor, another authors private post'    => array( 'contributor', 'post', 'private', '' ),
			'contributor, another authors private episode' => array( 'contributor', SSP_CPT_PODCAST, 'private', '' ),
			'contributor, another authors draft episode'   => array( 'contributor', SSP_CPT_PODCAST, 'draft', '' ),
			'contributor, published non-podcast post'      => array( 'contributor', 'post', 'publish', '' ),
			'anonymous, scheduled episode'                => array( 'anonymous', SSP_CPT_PODCAST, 'future', '' ),
			'anonymous, password-protected episode'       => array( 'anonymous', SSP_CPT_PODCAST, 'publish', self::PASSWORD ),
		);
	}

	/** @dataProvider accessible_episode_cases */
	public function test_podcast_episode_renders_accessible_content( $viewer, $status, $password ) {
		$id = $this->create_episode(
			array(
				'post_status'   => $status,
				'post_password' => $password,
			)
		);
		wp_set_current_user( $this->users[ $viewer ] );
		if ( $password ) {
			$this->enter_password( $password );
		}

		$output = $this->render_episode( $id );

		$this->assertStringContainsString( self::TITLE, $output );
		$this->assertStringContainsString( self::EXCERPT, $output );
		$this->assertStringContainsString( self::BODY, $output );
		$this->assertStringContainsString( '<audio', $output );
	}

	public static function accessible_episode_cases() {
		return array(
			'anonymous, published episode'        => array( 'anonymous', 'publish', '' ),
			'editor, draft preview'               => array( 'editor', 'draft', '' ),
			'editor, scheduled preview'           => array( 'editor', 'future', '' ),
			'visitor, correct password cookie'    => array( 'anonymous', 'publish', self::PASSWORD ),
		);
	}

	/** @dataProvider denied_player_cases */
	public function test_html_player_hides_inaccessible_content( $viewer, $post_type, $status ) {
		$id = $this->create_episode( array( 'post_type' => $post_type, 'post_status' => $status ) );
		wp_set_current_user( $this->users[ $viewer ] );

		$this->assert_hidden( ssp_frontend_controller()->players_controller->render_html_player( $id ) );
	}

	public function test_html_player_renders_accessible_episode() {
		$id = $this->create_episode();

		$output = ssp_frontend_controller()->players_controller->render_html_player( $id );

		$this->assertStringContainsString( self::TITLE, $output );
		$this->assertStringContainsString( '<audio', $output );
	}

	/** @dataProvider denied_player_cases */
	public function test_media_player_hides_inaccessible_content( $viewer, $post_type, $status ) {
		$id = $this->create_episode( array( 'post_type' => $post_type, 'post_status' => $status ) );
		wp_set_current_user( $this->users[ $viewer ] );

		$this->assert_hidden( ssp_frontend_controller()->players_controller->render_media_player( $id ) );
	}

	public static function denied_player_cases() {
		return array(
			'another authors private episode' => array( 'contributor', SSP_CPT_PODCAST, 'private' ),
			'published non-podcast post'      => array( 'contributor', 'post', 'publish' ),
			'anonymous, draft episode'        => array( 'anonymous', SSP_CPT_PODCAST, 'draft' ),
		);
	}

	public function test_media_player_renders_accessible_episode() {
		$id = $this->create_episode();

		$this->assertStringContainsString( '<audio', ssp_frontend_controller()->players_controller->render_media_player( $id ) );
	}

	/** @dataProvider current_and_latest_episode_ids */
	public function test_html_player_on_page_plays_latest_published_episode( $episode_id ) {
		$this->create_latest_published_episode();
		$page_id = $this->create_episode( array( 'post_type' => 'page', 'post_title' => 'Hosting page' ) );
		update_post_meta( $page_id, 'audio_file', 'https://example.com/page-audio.mp3' );
		$GLOBALS['post'] = get_post( $page_id );
		$block = new Castos_Html_Player_Block();

		$output = $block->render_callback( array( 'episodeId' => $episode_id ) );

		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( '<audio', $output );
		$this->assertStringContainsString( self::AUDIO, $output );
		$this->assertStringNotContainsString( 'Older episode', $output );
		$this->assertStringNotContainsString( 'page-audio.mp3', $output );
		$this->assertStringNotContainsString( 'Newer unpublished episode', $output );
	}

	public static function current_and_latest_episode_ids() {
		return array( 'zero' => array( '0' ), 'empty' => array( '' ), 'latest' => array( '-1' ) );
	}

	public function test_current_html_player_renders_editor_draft_preview() {
		$id = $this->create_episode( array( 'post_status' => 'draft' ) );
		$GLOBALS['post'] = get_post( $id );
		wp_set_current_user( $this->users['editor'] );

		$output = ssp_frontend_controller()->players_controller->render_html_player( '0' );

		$this->assertStringContainsString( self::AUDIO, $output );
	}

	public function test_current_html_player_hides_anonymous_draft_episode() {
		$id = $this->create_episode( array( 'post_status' => 'draft' ) );
		$GLOBALS['post'] = get_post( $id );

		$this->assert_hidden( ssp_frontend_controller()->players_controller->render_html_player( '0' ) );
	}

	public function test_podcast_episode_minus_one_renders_latest_published_episode() {
		$this->create_latest_published_episode();

		$output = $this->render_episode( '-1' );

		$this->assertStringContainsString( self::TITLE, $output );
		$this->assertStringContainsString( self::BODY, $output );
		$this->assertStringContainsString( '<audio', $output );
		$this->assertStringNotContainsString( 'Older episode', $output );
		$this->assertStringNotContainsString( 'Newer unpublished episode', $output );
	}

	public function test_current_media_player_hides_anonymous_password_protected_episode() {
		$id = $this->create_episode( array( 'post_password' => self::PASSWORD ) );
		$GLOBALS['post'] = get_post( $id );

		$this->assert_hidden( ssp_frontend_controller()->players_controller->render_media_player( 0 ) );
	}

	public function test_access_filter_hides_published_episode() {
		$id = $this->create_episode();
		add_filter( 'ssp_can_access_episode', '__return_false' );

		$this->assert_hidden( $this->render_episode( $id ) );
	}

	/** Distinct dates avoid relying on ID ordering when selecting the latest episode. */
	private function create_latest_published_episode() {
		$older_date = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - DAY_IN_SECONDS );
		$older_id = $this->create_episode(
			array(
				'post_title'    => 'Older episode',
				'post_date'     => $older_date,
				'post_date_gmt' => get_gmt_from_date( $older_date ),
			)
		);
		update_post_meta( $older_id, 'audio_file', 'https://example.com/older-audio.mp3' );
		$latest_date = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - HOUR_IN_SECONDS );
		$this->create_episode(
			array(
				'post_date'     => $latest_date,
				'post_date_gmt' => get_gmt_from_date( $latest_date ),
			)
		);
		$this->create_episode( array( 'post_status' => 'draft', 'post_title' => 'Newer unpublished episode' ) );
	}

	private function create_episode( $args = array() ) {
		$args = array_merge(
			array(
				'post_type'    => SSP_CPT_PODCAST,
				'post_status'  => 'publish',
				'post_author'  => $this->users['author'],
				'post_title'   => self::TITLE,
				'post_content' => self::BODY,
				'post_excerpt' => self::EXCERPT,
			),
			$args
		);
		if ( 'future' === $args['post_status'] ) {
			$args['post_date']     = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) + DAY_IN_SECONDS );
			$args['post_date_gmt'] = get_gmt_from_date( $args['post_date'] );
		}
		$id = self::factory()->post->create( $args );
		update_post_meta( $id, 'audio_file', self::AUDIO );
		$this->assertSame( $args['post_status'], get_post_status( $id ), 'Fixture must retain its requested status.' );

		return $id;
	}

	private function render_episode( $id ) {
		return ssp_frontend_controller()->podcast_episode( $id, array( 'title', 'excerpt', 'content', 'player' ) );
	}

	private function assert_hidden( $output ) {
		foreach ( array( self::TITLE, self::EXCERPT, self::BODY, self::AUDIO, 'secret-render-audio.mp3' ) as $secret ) {
			$this->assertStringNotContainsString( $secret, $output );
		}
		$this->assertSame( '', $output );
	}

	/** Sets the cookie WordPress stores after a visitor submits the post password form. */
	private function enter_password( $password ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$hasher = new \PasswordHash( 8, true );

		$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = $hasher->HashPassword( $password );
	}
}
