<?php

namespace Tests\WPUnit;

class SeriesTaxonomyRegistrationTest extends \Codeception\TestCase\WPTestCase {

	private const EARLY_POST_TYPE = 'ssp898_early';
	private const LATE_POST_TYPE  = 'ssp898_late';

	protected function setUp(): void {
		parent::setUp();
		$this->unregister_test_post_types();

		add_filter( 'pre_option_ss_podcasting_use_post_types', array( $this, 'enabled_post_types' ) );

		// Recreate the init order without replaying unrelated init callbacks.
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		$this->register_test_post_type( self::EARLY_POST_TYPE );
		$this->assertFalse( post_type_exists( self::LATE_POST_TYPE ) );
		ssp_app()->series_controller->register_taxonomy();
		$this->register_test_post_type( self::LATE_POST_TYPE );
	}

	protected function tearDown(): void {
		remove_filter( 'pre_option_ss_podcasting_use_post_types', array( $this, 'enabled_post_types' ) );
		$this->unregister_test_post_types();
		parent::tearDown();
	}

	private function unregister_test_post_types() {
		foreach ( array( self::EARLY_POST_TYPE, self::LATE_POST_TYPE ) as $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				unregister_post_type( $post_type );
			}
		}
	}

	public function enabled_post_types() {
		return array( self::EARLY_POST_TYPE, self::LATE_POST_TYPE );
	}

	private function register_test_post_type( $post_type ) {
		$result = register_post_type(
			$post_type,
			array(
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'custom-fields' ),
			)
		);
		$this->assertNotWPError( $result );
	}

	public function testLateRegisteredEnabledPostTypeHasSeriesTaxonomyAndRestField() {
		$taxonomy = ssp_series_taxonomy();
		$this->assertContains( $taxonomy, get_object_taxonomies( self::LATE_POST_TYPE ) );

		$controller = new \WP_REST_Posts_Controller( self::LATE_POST_TYPE );
		$schema     = $controller->get_item_schema();
		$this->assertArrayHasKey( 'series', $schema['properties'] );
	}

	public function testPodcastAndEarlyRegisteredPostTypeKeepSeriesTaxonomy() {
		$taxonomy = ssp_series_taxonomy();
		$this->assertContains( $taxonomy, get_object_taxonomies( SSP_CPT_PODCAST ) );
		$this->assertContains( $taxonomy, get_object_taxonomies( self::EARLY_POST_TYPE ) );
	}
}
