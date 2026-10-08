<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Handlers\RSS_Import_Handler;
use SeriouslySimplePodcasting\Handlers\UUID_Handler;

class SeriesControllerTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * Bodies of intercepted Castos series/create requests.
	 *
	 * @var array
	 */
	private $push_bodies = array();

	/**
	 * Original form data, restored after each test.
	 *
	 * @var array
	 */
	private $original_post;

	/**
	 * Handler instance used by the controller, whose cache survives between tests.
	 *
	 * @var \SeriouslySimplePodcasting\Handlers\Series_Handler
	 */
	private $series_handler;

	/**
	 * @var \ReflectionProperty
	 */
	private $default_series_id_property;

	/**
	 * Memoized value to restore after this test.
	 *
	 * @var int|null
	 */
	private $original_default_series_id;

	protected function setUp(): void {
		parent::setUp();

		$this->original_post = $_POST;
		$_POST               = array();
		$this->push_bodies   = array();

		// Intercept before any setting change can trigger a background push.
		add_filter( 'pre_http_request', array( $this, 'intercept_http' ), 10, 3 );

		$handler_property = new \ReflectionProperty( ssp_app()->series_controller, 'series_handler' );
		$handler_property->setAccessible( true );
		$this->series_handler             = $handler_property->getValue( ssp_app()->series_controller );
		$this->default_series_id_property = new \ReflectionProperty( $this->series_handler, 'default_series_id' );
		$this->default_series_id_property->setAccessible( true );
		$this->original_default_series_id = $this->default_series_id_property->getValue( $this->series_handler );
		$this->reset_default_series_cache();

		RSS_Import_Handler::reset_import_data();
		delete_option( 'ss_podcasting_podmotor_account_api_token' );

		if ( ! taxonomy_exists( ssp_series_taxonomy() ) ) {
			ssp_app()->series_controller->register_taxonomy();
		}

		ssp_app()->series_controller->enable_default_series();
		$this->reset_default_series_cache();
	}

	protected function tearDown(): void {
		try {
			remove_filter( 'pre_http_request', array( $this, 'intercept_http' ) );
			RSS_Import_Handler::reset_import_data();
			$_POST = $this->original_post;

			parent::tearDown();
		} finally {
			$this->default_series_id_property->setValue( $this->series_handler, $this->original_default_series_id );
		}
	}

	/**
	 * Simulate a new request after a fixture changes the default-series option.
	 */
	private function reset_default_series_cache() {
		$this->default_series_id_property->setValue( $this->series_handler, null );
	}

	/**
	 * Records series pushes and prevents real HTTP requests in every test.
	 */
	public function intercept_http( $preempt, $args, $url ) {
		if ( false !== strpos( $url, 'api/v2/series/create' ) ) {
			$this->push_bodies[] = $args['body'];
		}

		return array(
			'body'     => wp_json_encode( array( 'status' => 'success' ) ),
			'response' => array( 'code' => 200 ),
		);
	}

	private function create_series( $name ) {
		return $this->factory()->term->create(
			array(
				'taxonomy' => ssp_series_taxonomy(),
				'name'     => $name,
			)
		);
	}

	/**
	 * Uses the read-only derivation API, never the feed getter that stores GUIDs.
	 */
	private function derived_guid( $series_id ) {
		$term = get_term( $series_id, ssp_series_taxonomy() );

		return ssp_get_service( 'feed_handler' )->get_derived_guid( $term->slug );
	}

	public function testCreatingSeriesStoresDerivedGuidInOwnOption() {
		update_option( 'permalink_structure', '/%postname%/' );
		$first  = $this->create_series( 'First New Show' );
		$second = $this->create_series( 'Second New Show' );

		$this->assertSame( $this->derived_guid( $first ), get_option( 'ss_podcasting_data_guid_' . $first ) );
		$this->assertSame( $this->derived_guid( $second ), get_option( 'ss_podcasting_data_guid_' . $second ) );
		$this->assertNotSame( $this->derived_guid( $first ), $this->derived_guid( $second ), 'Each series derives its identity from its own feed URL' );
		$this->assertSame( get_option( 'ss_podcasting_data_guid_' . $first ), ssp_get_podcast_guid( $first ) );
	}

	public function testCreatingSeriesWithoutDefaultStillStoresOwnGuid() {
		$legacy_guid = '9b1e7c34-2f5a-5d8e-b6c1-4a7f0e3d92aa';
		delete_option( 'ss_podcasting_default_series' );
		$this->reset_default_series_cache();
		update_option( 'ss_podcasting_data_guid', $legacy_guid );

		$series_id = $this->create_series( 'Nondefault Show Before Default Exists' );

		$this->assertSame( 0, ssp_get_default_series_id(), 'Ordinary term creation does not assign the default' );
		$this->assertSame( $this->derived_guid( $series_id ), get_option( 'ss_podcasting_data_guid_' . $series_id ) );
		$this->assertNotSame( $legacy_guid, ssp_get_podcast_guid( $series_id ), 'The legacy identity belongs only to the default podcast' );
		$this->assertSame( $legacy_guid, get_option( 'ss_podcasting_data_guid' ) );
	}

	public function testFirstSeriesCreatePushIncludesStoredGuid() {
		update_option( 'ss_podcasting_podmotor_account_api_token', 'test-token' );
		// Adding the token synchronizes existing series; isolate the creation push.
		$this->push_bodies = array();

		$series_id = $this->create_series( 'Connected New Show' );

		$this->assertCount( 1, $this->push_bodies, 'Creation sends one series/create request' );
		$this->assertEquals( $series_id, $this->push_bodies[0]['series_id'] );
		$this->assertArrayHasKey( 'guid', $this->push_bodies[0] );
		$this->assertSame( $this->derived_guid( $series_id ), $this->push_bodies[0]['guid'] );
		$this->assertSame( get_option( 'ss_podcasting_data_guid_' . $series_id ), $this->push_bodies[0]['guid'] );
	}

	public function testPlainPermalinkCreationStoresOwnGuidWhenSlugLookupMisses() {
		update_option( 'permalink_structure', '' );
		delete_option( 'ss_podcasting_data_guid' );
		$slug             = 'language-hidden-new-show';
		$filtered_queries = 0;
		$hide_slug        = function ( $clauses, $taxonomies, $args ) use ( $slug, &$filtered_queries ) {
			if ( in_array( ssp_series_taxonomy(), $taxonomies, true ) && ! empty( $args['slug'] ) && in_array( $slug, (array) $args['slug'], true ) ) {
				$clauses['where'] .= ' AND 1 = 0';
				++$filtered_queries;
			}

			return $clauses;
		};
		// Simulate a language filter hiding only slug queries, not ID resolution.
		add_filter( 'terms_clauses', $hide_slug, 10, 3 );

		try {
			$series_id = $this->create_series( 'Language Hidden New Show' );
			$term      = get_term( $series_id, ssp_series_taxonomy() );
			$this->assertInstanceOf( \WP_Term::class, $term );
			$this->assertSame( $slug, $term->slug );
			$this->assertFalse( get_term_by( 'slug', $slug, ssp_series_taxonomy() ), 'The simulated filter must really hide the new term from slug lookup' );
			$this->assertGreaterThan( 0, $filtered_queries );
			$this->assertSame( $this->derived_guid( $series_id ), get_option( 'ss_podcasting_data_guid_' . $series_id ) );
			$this->assertSame( $this->derived_guid( $series_id ), ssp_get_podcast_guid( $series_id ) );
			$this->assertFalse( get_option( 'ss_podcasting_data_guid' ), 'Creation must never write a named series GUID into the legacy option' );
		} finally {
			remove_filter( 'terms_clauses', $hide_slug, 10 );
		}
	}

	public function testPlainPermalinkFirstPushGuidIsDistinctFromDefaultSeries() {
		update_option( 'home', 'https://guid.example.test' );
		update_option( 'permalink_structure', '' );
		// Model the pre-update default whose stored identity was host-only.
		$default_guid = UUID_Handler::v5( Feed_Handler::PODCAST_NAMESPACE_UUID, 'guid.example.test' );
		update_option( 'ss_podcasting_data_guid_' . ssp_get_default_series_id(), $default_guid );

		update_option( 'ss_podcasting_podmotor_account_api_token', 'test-token' );
		// Exclude pushes triggered by connecting existing series.
		$this->push_bodies = array();
		$series_id         = $this->create_series( 'Plain Permalink Second Show' );

		$this->assertCount( 1, $this->push_bodies );
		$this->assertEquals( $series_id, $this->push_bodies[0]['series_id'] );
		$this->assertSame( $this->derived_guid( $series_id ), $this->push_bodies[0]['guid'] );
		$this->assertNotSame( $default_guid, $this->push_bodies[0]['guid'], 'The second podcast must not push the default podcast identity' );
	}

	public function testDefaultSeriesCreationAdoptsLegacyGuidIntoOwnOption() {
		$legacy_guid = '9b1e7c34-2f5a-5d8e-b6c1-4a7f0e3d92aa';
		$previous_id = ssp_get_default_series_id();
		delete_option( 'ss_podcasting_default_series' );
		$this->reset_default_series_cache();
		update_option( 'ss_podcasting_data_title', 'Legacy Default Creation' );
		update_option( 'ss_podcasting_data_guid', $legacy_guid );

		ssp_app()->series_controller->enable_default_series();

		$series_id = ssp_get_default_series_id();
		$this->assertGreaterThan( 0, $series_id );
		$this->assertNotSame( $previous_id, $series_id, 'Exercise a newly created default term, not an existing one' );
		$this->assertSame( $legacy_guid, get_option( 'ss_podcasting_data_guid' ) );
		$this->assertSame( $legacy_guid, get_option( 'ss_podcasting_data_guid_' . $series_id ), 'The new default adopts the legacy identity into its own option' );

		$ordinary_id = $this->create_series( 'Ordinary Show After Default Creation' );
		$this->assertNotSame( $legacy_guid, ssp_get_podcast_guid( $ordinary_id ), 'Default creation must reset its flag so ordinary creation does not adopt the legacy GUID' );
	}

	public function testDefaultSeriesCreationStoresDerivedGuidWithoutLegacyGuid() {
		$previous_id = ssp_get_default_series_id();
		// Token setup synchronizes existing series; exclude those setup requests.
		update_option( 'ss_podcasting_podmotor_account_api_token', 'test-token' );
		delete_option( 'ss_podcasting_default_series' );
		$this->reset_default_series_cache();
		delete_option( 'ss_podcasting_data_guid' );
		delete_option( 'ss_podcasting_data_title' );
		update_option( 'blogname', 'Fresh Default Creation' );
		$this->push_bodies = array();

		ssp_app()->series_controller->enable_default_series();

		$series_id = ssp_get_default_series_id();
		$this->assertGreaterThan( 0, $series_id );
		$this->assertNotSame( $previous_id, $series_id );
		$this->assertSame( $this->derived_guid( $series_id ), get_option( 'ss_podcasting_data_guid_' . $series_id ) );
		$this->assertSame( $this->derived_guid( $series_id ), ssp_get_podcast_guid( $series_id ) );
		$this->assertFalse( get_option( 'ss_podcasting_data_guid' ), 'A new default writes its own option, not the legacy one' );
		$this->assertCount( 0, $this->push_bodies, 'With no previous default ID, remap the existing Castos default instead of creating a duplicate' );
	}

	public function testRecreatingDefaultWithStaleIdPushesNewSeriesWithGuid() {
		$legacy_guid = '9b1e7c34-2f5a-5d8e-b6c1-4a7f0e3d92aa';
		$stale_id    = $this->create_series( 'Deleted Default Target' );
		$this->assertTrue( wp_delete_term( $stale_id, ssp_series_taxonomy() ) );

		// Connect while the current default is valid, then simulate its stale ID.
		update_option( 'ss_podcasting_podmotor_account_api_token', 'test-token' );
		update_option( 'ss_podcasting_default_series', $stale_id );
		$this->reset_default_series_cache();
		update_option( 'ss_podcasting_data_title', 'Recreated Default Podcast' );
		update_option( 'ss_podcasting_data_guid', $legacy_guid );
		$this->push_bodies = array();
		$this->assertEquals( $stale_id, $this->series_handler->default_series_id(), 'The replacement request must resolve the stale ID, not a previous request cache' );

		ssp_app()->series_controller->enable_default_series();

		$series_id = ssp_get_default_series_id();
		$this->assertGreaterThan( 0, $series_id );
		$this->assertNotSame( $stale_id, $series_id );
		$this->assertInstanceOf( \WP_Term::class, get_term( $series_id, ssp_series_taxonomy() ) );
		$this->assertCount( 1, $this->push_bodies, 'Default recreation must send a series/create request for the replacement term' );
		$this->assertEquals( $series_id, $this->push_bodies[0]['series_id'] );
		$this->assertArrayHasKey( 'guid', $this->push_bodies[0] );

		$this->assertSame( $legacy_guid, get_option( 'ss_podcasting_data_guid_' . $series_id ), 'The replacement default adopts the legacy identity into its own option' );
		$this->assertSame( $legacy_guid, ssp_get_podcast_guid( $series_id ) );
		$this->assertSame( $legacy_guid, $this->push_bodies[0]['guid'] );
		$this->assertSame( $legacy_guid, get_option( 'ss_podcasting_data_guid' ) );
	}

	public function testEditingExistingSeriesWithoutGuidDoesNotGenerateOne() {
		$series_id = $this->create_series( 'Existing Show Without GUID' );
		// Model a pre-update series that never rendered its feed.
		delete_option( 'ss_podcasting_data_guid_' . $series_id );
		$this->assertSame( '', ssp_get_podcast_guid( $series_id ) );

		$result = wp_update_term( $series_id, ssp_series_taxonomy(), array( 'name' => 'Renamed Existing Show' ) );

		$this->assertNotWPError( $result );
		$this->assertFalse( get_option( 'ss_podcasting_data_guid_' . $series_id ) );
		$this->assertSame( '', ssp_get_podcast_guid( $series_id ), 'Editing must not backfill a missing GUID' );
	}

	/**
	 * @dataProvider permalink_cases
	 */
	public function testCreatingSeriesAndFeedRenderPreserveAlreadyResolvedOwnGuid( $permalink_structure ) {
		update_option( 'permalink_structure', $permalink_structure );
		$existing_guid = '5f0a1c2d-3e4b-5a6c-8d7e-9f0a1b2c3d4e';
		$seed_guid     = function ( $series_id ) use ( $existing_guid ) {
			update_option( 'ss_podcasting_data_guid_' . $series_id, $existing_guid );
		};
		// Supply an identity before created_series, regardless of SSP's hook priority.
		add_action( 'create_series', $seed_guid );

		try {
			$series_id = $this->create_series( 'Show With Existing Identity' );
		} finally {
			remove_action( 'create_series', $seed_guid );
		}

		$this->assertNotSame( $existing_guid, $this->derived_guid( $series_id ) );
		$this->assertSame( $existing_guid, get_option( 'ss_podcasting_data_guid_' . $series_id ) );
		$this->assertSame( $existing_guid, ssp_get_podcast_guid( $series_id ) );

		$term = get_term( $series_id, ssp_series_taxonomy() );
		$this->assertSame( $existing_guid, ssp_get_service( 'feed_handler' )->get_guid( $term->slug ), 'Feed rendering keeps the stored identity rather than using the new derivation' );
		$this->assertSame( $existing_guid, get_option( 'ss_podcasting_data_guid_' . $series_id ) );
	}

	public static function permalink_cases() {
		return array(
			'plain permalinks'  => array( '' ),
			'pretty permalinks' => array( '/%postname%/' ),
		);
	}
}
