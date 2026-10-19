<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler;

/**
 * Verifies episode-only meta registration and edit-rights authorization.
 *
 * @since 3.17.1
 */
class EpisodeMetaRegistrationTest extends \Codeception\TestCase\WPTestCase {

	const EXTRA_POST_TYPE = 'ssp_meta_episode';

	/** @var CPT_Podcast_Handler */
	private $handler;

	/** @var array */
	private $meta_keys;

	/** @var array */
	private $meta_registry_backup;

	/** @var array */
	private $options_backup;

	/** @var mixed */
	private $rest_server_backup;

	/** @var int */
	private $user_backup;

	protected function setUp(): void {
		parent::setUp();

		$this->meta_registry_backup = isset( $GLOBALS['wp_meta_keys'] ) ? $GLOBALS['wp_meta_keys'] : array();
		$this->rest_server_backup   = isset( $GLOBALS['wp_rest_server'] ) ? $GLOBALS['wp_rest_server'] : null;
		$this->user_backup          = get_current_user_id();
		$this->options_backup       = array();
		foreach ( array(
			'ss_podcasting_use_post_types'        => array( self::EXTRA_POST_TYPE ),
			'ss_podcasting_itunes_fields_enabled' => 'on',
		) as $option => $value ) {
			$this->options_backup[ $option ] = get_option( $option, null );
			update_option( $option, $value );
		}

		$this->handler = ssp_app()->get_service( 'cpt_podcast_handler' );
		$this->handler->register_post_type();
		$this->meta_keys = array_keys( $this->handler->custom_fields() );

		// Configured episode types can register later on init than the podcast type.
		register_post_type(
			self::EXTRA_POST_TYPE,
			array(
				'public'          => true,
				'show_in_rest'    => true,
				'rest_base'       => 'ssp-meta-episodes',
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'supports'        => array( 'title', 'editor', 'author', 'custom-fields' ),
			)
		);
		ssp_app()->series_controller->register_taxonomy();
		// Core builds REST schemas from the post types and meta registered above.
		$GLOBALS['wp_rest_server'] = null;
	}

	protected function tearDown(): void {
		// Meta registrations are global state, not part of the database transaction.
		foreach ( array( '', SSP_CPT_PODCAST, self::EXTRA_POST_TYPE, 'post' ) as $subtype ) {
			$current  = get_registered_meta_keys( 'post', $subtype );
			$original = isset( $this->meta_registry_backup['post'][ $subtype ] ) ? $this->meta_registry_backup['post'][ $subtype ] : array();
			foreach ( $this->meta_keys as $key ) {
				if ( isset( $current[ $key ] ) ) {
					unregister_meta_key( 'post', $key, $subtype );
				}
				if ( isset( $original[ $key ] ) ) {
					$args                  = $original[ $key ];
					$args['object_subtype'] = $subtype;
					register_meta( 'post', $key, $args );
				}
			}
		}
		$GLOBALS['wp_meta_keys'] = $this->meta_registry_backup;
		unregister_post_type( self::EXTRA_POST_TYPE );
		foreach ( $this->options_backup as $option => $value ) {
			null === $value ? delete_option( $option ) : update_option( $option, $value );
		}
		wp_set_current_user( $this->user_backup );
		$GLOBALS['wp_rest_server'] = $this->rest_server_backup;

		parent::tearDown();
	}

	public function testEpisodeMetaIsRegisteredOnlyForEpisodePostTypes() {
		$this->assertContains( 'duration', $this->meta_keys );
		$this->assertContains( 'itunes_title', $this->meta_keys );
		$this->assertNotContains( 'post', ssp_post_types() );

		$global_meta = get_registered_meta_keys( 'post' );
		$post_meta   = get_registered_meta_keys( 'post', 'post' );
		foreach ( array( SSP_CPT_PODCAST, self::EXTRA_POST_TYPE ) as $subtype ) {
			$registered = get_registered_meta_keys( 'post', $subtype );
			foreach ( $this->meta_keys as $key ) {
				$this->assertArrayHasKey( $key, $registered, $subtype . ': ' . $key );
				$this->assertTrue( $registered[ $key ]['show_in_rest'], $subtype . ': ' . $key );
				$this->assertArrayNotHasKey( $key, $global_meta, $key . ' must not apply to every post type.' );
				$this->assertArrayNotHasKey( $key, $post_meta, $key . ' must not apply to ordinary posts.' );
			}
		}
	}

	public function testEveryEpisodeMetaKeyRequiresEpisodeEditRights() {
		$editor_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$other_id  = self::factory()->user->create( array( 'role' => 'author' ) );

		foreach ( array( SSP_CPT_PODCAST, self::EXTRA_POST_TYPE ) as $subtype ) {
			$episode_id = $this->create_episode( $subtype, $editor_id );
			$this->assertTrue( user_can( $editor_id, 'edit_post', $episode_id ) );
			$this->assertFalse( user_can( $other_id, 'edit_post', $episode_id ) );

			$registered = get_registered_meta_keys( 'post', $subtype );
			foreach ( $this->meta_keys as $key ) {
				$this->assertArrayHasKey( $key, $registered );
				$this->assertArrayHasKey( 'auth_callback', $registered[ $key ] );
				$callback = $registered[ $key ]['auth_callback'];
				$this->assertTrue( is_callable( $callback ), $subtype . ': ' . $key );

				// Check the supplied user's rights, not the current user's or $allowed.
				wp_set_current_user( $editor_id );
				$this->assertFalse( call_user_func( $callback, true, $key, $episode_id, $other_id, 'edit_post_meta', array() ), $subtype . ': ' . $key );
				wp_set_current_user( $other_id );
				$this->assertTrue( call_user_func( $callback, false, $key, $episode_id, $editor_id, 'edit_post_meta', array() ), $subtype . ': ' . $key );
			}
		}
	}

	/** @dataProvider ordinary_post_role_provider */
	public function testNonEpisodePostCannotStoreEpisodeMetaThroughRest( $role, $status ) {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		$post_id = $this->create_episode( 'post', $user_id, $status );
		wp_set_current_user( $user_id );
		$this->assertTrue( current_user_can( 'edit_post', $post_id ), 'The ordinary post itself must be editable.' );
		$this->assertFalse( metadata_exists( 'post', $post_id, 'duration' ) );

		$response = $this->update_rest_meta( $post_id, array( 'duration' => '<em>ordinary post</em>' ) );

		// Core versions either ignore unregistered properties or reject their schema.
		$this->assertContains( $response->get_status(), array( 200, 400 ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, 'duration' ) );
	}

	public static function ordinary_post_role_provider() {
		return array(
			'Contributor draft' => array( 'contributor', 'draft' ),
			'Author published'  => array( 'author', 'publish' ),
		);
	}

	/** @dataProvider editable_episode_provider */
	public function testRestAllowsEpisodeMetaWritesWithoutChangingValues( $subtype, $role ) {
		$user_id    = self::factory()->user->create( array( 'role' => $role ) );
		$episode_id = $this->create_episode( $subtype, $user_id );
		wp_set_current_user( $user_id );
		$this->assertTrue( current_user_can( 'edit_post', $episode_id ) );
		$values = array(
			'duration'              => '01:02:03<span class="duration">minutes</span> & 100%',
			'itunes_title'          => "Jane's & <code>title</code> ]]> \\ still text",
			'itunes_episode_type'   => '<em>episode type</em> & bonus',
			'itunes_episode_number' => '007',
			'itunes_season_number'  => '',
			'date_recorded'         => '2026-10-15',
		);

		$response = $this->update_rest_meta( $episode_id, $values );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'meta', $data );
		foreach ( $values as $key => $value ) {
			$this->assertSame( $value, get_post_meta( $episode_id, $key, true ), $key . ' must be stored unchanged.' );
			$this->assertArrayHasKey( $key, $data['meta'] );
			$this->assertSame( $value, $data['meta'][ $key ], $key . ' must be returned unchanged.' );
		}
	}

	public function testDirectEpisodeMetaWritesPreserveStoredValues() {
		$values = array(
			'duration'              => ' 01:02:03<em>duration</em> ',
			'itunes_episode_number' => '007',
			'itunes_season_number'  => '',
			'itunes_title'          => "Jane's title ]]> & 100% \\ text",
			'castos_file_data'      => wp_json_encode( array( 'path' => 'uploads/100%25/audio.mp3', 'name' => 'Jane\'s "mix" \\ audio.mp3' ) ),
		);
		foreach ( array( SSP_CPT_PODCAST, self::EXTRA_POST_TYPE ) as $subtype ) {
			$episode_id = $this->create_episode( $subtype, 0 );
			foreach ( $values as $key => $value ) {
				// Metadata APIs unslash their input, independently of registered sanitizers.
				update_post_meta( $episode_id, $key, wp_slash( $value ) );
				$this->assertSame( $value, get_post_meta( $episode_id, $key, true ), $subtype . ': ' . $key );
			}
		}
	}

	public static function editable_episode_provider() {
		return array(
			'podcast administrator' => array( 'podcast', 'administrator' ),
			'extra Author owner'    => array( self::EXTRA_POST_TYPE, 'author' ),
		);
	}

	private function create_episode( $subtype, $author_id, $status = 'publish' ) {
		return self::factory()->post->create(
			array(
				'post_type'   => $subtype,
				'post_status' => $status,
				'post_author' => $author_id,
				'post_title'  => 'Meta registration test',
			)
		);
	}

	private function update_rest_meta( $post_id, $meta ) {
		$post_type = get_post_type_object( get_post_type( $post_id ) );
		$rest_base = $post_type->rest_base ? $post_type->rest_base : $post_type->name;
		$request   = new \WP_REST_Request( 'POST', '/wp/v2/' . $rest_base . '/' . $post_id );
		$request->set_param( 'meta', $meta );

		return rest_do_request( $request );
	}
}
