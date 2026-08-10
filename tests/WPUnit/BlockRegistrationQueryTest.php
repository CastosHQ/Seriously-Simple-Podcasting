<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Integrations\Blocks\Castos_Html_Player_Block;
use SeriouslySimplePodcasting\Integrations\Blocks\Episode_List_Block;
use SeriouslySimplePodcasting\Integrations\Blocks\Playlist_Player_Block;
use SeriouslySimplePodcasting\Integrations\Blocks\Ssp_Podcasts_Block;
use SeriouslySimplePodcasting\Presenters\Episode_List_Presenter;

/**
 * Guards the block layer against loading taxonomy data at registration or editor-bootstrap time.
 *
 * Block attribute defaults are JSON encoded on every editor, site editor and widgets page load
 * by get_block_editor_server_block_settings(). Any get_terms() reached from there is executed on
 * every one of those page loads, and WP_Term_Query primes the term cache with a single
 * "WHERE t.term_id IN (...)" statement listing every returned ID — which is what hosting
 * environments were killing on tag-heavy sites (ssp-issues#850).
 *
 * @package SeriouslySimplePodcasting\Tests
 * @since 3.17.0
 */
class BlockRegistrationQueryTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * Longest SQL statement tolerated during editor bootstrap.
	 *
	 * The reported failure was a 36,000+ character statement. The seeded tag count below is chosen
	 * so that reintroducing bulk term loading comfortably exceeds this, rather than squeaking under
	 * it and leaving the guard unable to fail.
	 */
	const MAX_STATEMENT_LENGTH = 2000;

	/**
	 * Number of tags seeded to make bulk term loading visible.
	 *
	 * Term IDs run to four digits or more here, so a full "IN (...)" list over these terms is well
	 * past MAX_STATEMENT_LENGTH.
	 */
	const SEEDED_TAG_COUNT = 1200;

	/**
	 * Captured SQL statements for the current recording window.
	 *
	 * @var string[]
	 */
	protected $captured = array();

	/**
	 * Query filter callback used to record statements.
	 *
	 * @var callable|null
	 */
	protected $recorder;

	protected function tearDown(): void {
		$this->stop_recording();
		parent::tearDown();
	}

	/**
	 * Registering any SSP block must not touch the term tables.
	 *
	 * Two of these blocks built their podcast list while registering, so this query ran on every
	 * request — frontend page views included, where the list can never be used.
	 */
	public function test_block_registration_runs_no_term_queries() {
		$this->seed_series( 5 );

		foreach ( $this->block_registrars() as $block_name => $register ) {
			unregister_block_type( $block_name );

			$this->start_recording();
			$register();
			$this->stop_recording();

			$this->assertSame(
				array(),
				$this->term_queries(),
				sprintf( 'Registering %s must not query the term tables.', $block_name )
			);
		}
	}

	/**
	 * Encoding the server-side block settings must not query tags.
	 *
	 * This is the exact path wp-admin/edit-form-blocks.php takes on every editor page load.
	 */
	public function test_editor_bootstrap_runs_no_tag_queries() {
		$this->seed_tags( self::SEEDED_TAG_COUNT );

		$this->start_recording();
		$this->encode_server_block_settings();
		$this->stop_recording();

		$this->assertSame(
			array(),
			$this->matching_queries( '/\bpost_tag\b/' ),
			'Encoding block settings for the editor must not query the post_tag taxonomy.'
		);
	}

	/**
	 * Encoding the server-side block settings must not produce an oversized statement.
	 *
	 * Statement length is the symptom hosts actually killed, so it is asserted directly rather
	 * than inferred from the absence of a taxonomy name.
	 */
	public function test_editor_bootstrap_produces_no_oversized_statement() {
		$this->seed_tags( self::SEEDED_TAG_COUNT );

		$this->start_recording();
		$this->encode_server_block_settings();
		$this->stop_recording();

		$longest = 0;
		foreach ( $this->captured as $sql ) {
			$longest = max( $longest, strlen( $sql ) );
		}

		$this->assertLessThan(
			self::MAX_STATEMENT_LENGTH,
			$longest,
			sprintf( 'Editor bootstrap produced a %d character statement; bulk term loading has returned.', $longest )
		);
	}

	/**
	 * No block may carry taxonomy data in its attributes.
	 *
	 * Attribute defaults are serialized to every editor page, so a list of terms parked there is
	 * both a query on load and payload on the wire.
	 */
	public function test_no_block_declares_taxonomy_attributes() {
		$this->register_all_blocks();

		$registry = \WP_Block_Type_Registry::get_instance();

		foreach ( array_keys( $this->block_registrars() ) as $block_name ) {
			$block = $registry->get_registered( $block_name );

			$this->assertNotNull( $block, sprintf( '%s should be registered.', $block_name ) );
			$this->assertArrayNotHasKey( 'availableTags', (array) $block->attributes, sprintf( '%s must not declare availableTags.', $block_name ) );
			$this->assertArrayNotHasKey( 'availablePodcasts', (array) $block->attributes, sprintf( '%s must not declare availablePodcasts.', $block_name ) );
		}
	}

	/**
	 * The attributes the editor still relies on must survive the removal.
	 */
	public function test_remaining_block_attributes_are_untouched() {
		$this->register_all_blocks();

		$registry = \WP_Block_Type_Registry::get_instance();

		$playlist = $registry->get_registered( 'seriously-simple-podcasting/playlist-player' );
		$this->assertSame( '-1', $playlist->attributes['selectedPodcast']['default'] );
		$this->assertSame( '', $playlist->attributes['selectedTag']['default'] );
		$this->assertSame( 'date', $playlist->attributes['orderBy']['default'] );

		$episode_list = $registry->get_registered( 'seriously-simple-podcasting/podcast-list' );
		$this->assertSame( '-1', $episode_list->attributes['selectedPodcast']['default'] );
		$this->assertSame( strval( ssp_get_default_series_id() ), $episode_list->attributes['defaultPodcastId']['default'] );
		$this->assertIsArray( $episode_list->attributes['availableImageSizes']['default'] );

		$podcasts = $registry->get_registered( 'seriously-simple-podcasting/ssp-podcasts' );
		$this->assertSame( array( '-1' ), $podcasts->attributes['ids']['default'] );
		$this->assertSame( 'id', $podcasts->attributes['sort_by']['default'] );
	}

	/**
	 * The REST response labels the default podcast, and labels it exactly once.
	 *
	 * The editor renders term names verbatim because Rest_Api_Controller already appends the
	 * "(default)" suffix through rest_prepare_{taxonomy}. Decorating client side as well produced
	 * "Name (default) (default)", so both halves of that contract are asserted here.
	 */
	public function test_rest_labels_the_default_podcast_exactly_once() {
		$default_id = $this->factory()->term->create(
			array(
				'taxonomy' => ssp_series_taxonomy(),
				'name'     => 'Default Show',
			)
		);
		$other_id   = $this->factory()->term->create(
			array(
				'taxonomy' => ssp_series_taxonomy(),
				'name'     => 'Other Show',
			)
		);

		ssp_update_option( 'default_series', $default_id );

		$names = wp_list_pluck( $this->request_series(), 'name' );

		$this->assertContains( 'Default Show (default)', $names );
		$this->assertContains( 'Other Show', $names );
		$this->assertNotContains( 'Default Show (default) (default)', $names );
	}

	/**
	 * The editor fetches podcasts over REST, so it needs the route the taxonomy is exposed on.
	 */
	public function test_series_rest_base_matches_the_registered_taxonomy() {
		$this->register_shared_assets();

		$data      = wp_scripts()->get_data( 'ssp-block-script', 'data' );
		$rest_base = get_taxonomy( ssp_series_taxonomy() )->rest_base;

		$this->assertIsString( $data, 'ssp-block-script should carry localized data.' );
		$this->assertStringContainsString( '"seriesRestBase":"' . $rest_base . '"', $data );

		// The route the editor builds from that base has to actually answer.
		$this->assertNotEmpty( $this->request_series() );
	}

	/**
	 * Requests the podcast list the way the editor does.
	 *
	 * @return array Response data.
	 */
	protected function request_series() {
		$rest_base = get_taxonomy( ssp_series_taxonomy() )->rest_base;

		$request = new \WP_REST_Request( 'GET', '/wp/v2/' . $rest_base );
		$request->set_param( 'per_page', 100 );
		$request->set_param( '_fields', 'id,name' );

		$response = rest_do_request( $request );

		$this->assertFalse( $response->is_error(), 'The editor podcast route should respond successfully.' );

		return $response->get_data();
	}

	/**
	 * Block name => callable that registers it.
	 *
	 * @return callable[]
	 */
	protected function block_registrars() {
		$episode_list_presenter = new Episode_List_Presenter(
			ssp_get_service( 'episode_repository' ),
			ssp_get_service( 'players_controller' ),
			ssp_get_service( 'renderer' )
		);

		return array(
			'seriously-simple-podcasting/castos-html-player' => function () {
				( new Castos_Html_Player_Block() )->register();
			},
			'seriously-simple-podcasting/podcast-list'       => function () use ( $episode_list_presenter ) {
				( new Episode_List_Block( $episode_list_presenter ) )->register();
			},
			'seriously-simple-podcasting/playlist-player'    => function () {
				( new Playlist_Player_Block() )->register();
			},
			'seriously-simple-podcasting/ssp-podcasts'       => function () {
				( new Ssp_Podcasts_Block() )->register();
			},
		);
	}

	/**
	 * Re-registers every SSP block so assertions read the current registration code.
	 */
	protected function register_all_blocks() {
		foreach ( $this->block_registrars() as $block_name => $register ) {
			unregister_block_type( $block_name );
			$register();
		}
	}

	/**
	 * Runs the shared asset registration that carries data to the editor bundle.
	 */
	protected function register_shared_assets() {
		wp_deregister_script( 'ssp-block-script' );

		$blocks = new \SeriouslySimplePodcasting\Integrations\Blocks\Castos_Blocks(
			ssp_get_service( 'admin_notices_handler' ),
			new Episode_List_Presenter(
				ssp_get_service( 'episode_repository' ),
				ssp_get_service( 'players_controller' ),
				ssp_get_service( 'renderer' )
			)
		);

		$method = new \ReflectionMethod( $blocks, 'register_shared_assets' );
		$method->setAccessible( true );
		$method->invoke( $blocks );
	}

	/**
	 * Encodes the server-side block settings exactly as the editor page does.
	 */
	protected function encode_server_block_settings() {
		require_once ABSPATH . 'wp-admin/includes/post.php';

		wp_json_encode( get_block_editor_server_block_settings() );
	}

	/**
	 * Begins recording SQL with a cold cache, so results model a real uncached page load.
	 */
	protected function start_recording() {
		wp_cache_flush();

		$this->captured = array();
		$this->recorder = function ( $sql ) {
			$this->captured[] = $sql;

			return $sql;
		};

		add_filter( 'query', $this->recorder );
	}

	/**
	 * Stops recording, leaving the captured statements in place for assertions.
	 */
	protected function stop_recording() {
		if ( $this->recorder ) {
			remove_filter( 'query', $this->recorder );
			$this->recorder = null;
		}
	}

	/**
	 * Captured statements that read the term tables.
	 *
	 * @return string[]
	 */
	protected function term_queries() {
		global $wpdb;

		$pattern = sprintf( '/\b(%s|%s)\b/', preg_quote( $wpdb->terms, '/' ), preg_quote( $wpdb->term_taxonomy, '/' ) );

		return $this->matching_queries( $pattern );
	}

	/**
	 * Captured statements matching a pattern.
	 *
	 * @param string $pattern Regular expression.
	 *
	 * @return string[]
	 */
	protected function matching_queries( $pattern ) {
		return array_values(
			array_filter(
				$this->captured,
				function ( $sql ) use ( $pattern ) {
					return (bool) preg_match( $pattern, $sql );
				}
			)
		);
	}

	/**
	 * Seeds tags directly, since the assertions only depend on the rows the term query returns.
	 *
	 * @param int $count Number of tags to create.
	 */
	protected function seed_tags( $count ) {
		global $wpdb;

		for ( $i = 0; $i < $count; $i++ ) {
			$wpdb->insert(
				$wpdb->terms,
				array(
					'name' => 'Seeded tag ' . $i,
					'slug' => 'seeded-tag-' . $i,
				)
			);

			$wpdb->insert(
				$wpdb->term_taxonomy,
				array(
					'term_id'  => $wpdb->insert_id,
					'taxonomy' => 'post_tag',
					'count'    => 0,
				)
			);
		}

		clean_term_cache( array(), 'post_tag' );
	}

	/**
	 * Seeds podcast series terms.
	 *
	 * @param int $count Number of series to create.
	 */
	protected function seed_series( $count ) {
		$this->factory()->term->create_many( $count, array( 'taxonomy' => ssp_series_taxonomy() ) );
	}
}
