<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Integrations\Yoast\Schema\PodcastSeries;

// Yoast SEO is not activated in the WPUnit suite, so the schema base class this
// integration extends must exist before the autoloader pulls in PodcastSeries.
require_once __DIR__ . '/_yoast-schema-stubs.php';

/**
 * Covers the shape of the Yoast PodcastSeries graph piece.
 *
 * @covers \SeriouslySimplePodcasting\Integrations\Yoast\Schema\PodcastSeries::generate
 */
class YoastPodcastSeriesSchemaTest extends \Codeception\TestCase\WPTestCase {

	const CANONICAL = 'https://example.com/podcast/season-one/';

	/**
	 * Creates a series term, overriding any of the default term arguments.
	 */
	protected function createSeries( $args = array() ) {
		return $this->factory()->term->create( array_merge(
			array(
				'taxonomy' => ssp_series_taxonomy(),
				'name'     => 'Season One',
			),
			$args
		) );
	}

	/**
	 * Queries the series archive and returns the generated graph piece.
	 */
	protected function generateSchema( $term_id ) {
		// generate() reads the term from the main query, not from an argument.
		$this->go_to( get_term_link( $term_id, ssp_series_taxonomy() ) );

		$piece = new PodcastSeries();

		$context            = new \stdClass();
		$context->canonical = self::CANONICAL;
		$context->title     = 'Season One Podcast';

		$piece->context = $context;

		return $piece->generate();
	}

	/**
	 * Identity keys are wired from the Yoast context and the series repository.
	 */
	public function testSchemaCarriesSeriesIdentity() {
		$term_id = $this->createSeries();
		$term    = get_term( $term_id, ssp_series_taxonomy() );

		$schema = $this->generateSchema( $term_id );

		$this->assertSame( 'PodcastSeries', $schema['@type'] );
		$this->assertSame( self::CANONICAL . '#/schema/podcastSeries', $schema['@id'] );
		$this->assertSame( self::CANONICAL, $schema['url'] );
		$this->assertSame( 'Season One Podcast', $schema['name'] );
		$this->assertSame( ssp_series_repository()->get_feed_url( $term ), $schema['webFeed'] );
		$this->assertStringContainsString( $term->slug, $schema['webFeed'] );
	}

	/**
	 * A series with no image of its own falls back to the bundled placeholder.
	 */
	public function testImageFallsBackToThePlaceholder() {
		$term_id = $this->createSeries();

		$schema = $this->generateSchema( $term_id );

		$this->assertSame( esc_url( SSP_PLUGIN_URL . 'assets/images/no-image.png' ), $schema['image'] );
	}

	/**
	 * The description is plain text — schema.org values must carry no markup.
	 */
	public function testDescriptionIsStrippedOfMarkup() {
		$term_id = $this->createSeries( array( 'description' => '<p>Hosted by <strong>someone</strong>.</p>' ) );

		$schema = $this->generateSchema( $term_id );

		$this->assertSame( 'Hosted by someone.', $schema['description'] );
	}

	/**
	 * A series without a description emits no description key at all.
	 */
	public function testDescriptionIsOmittedWhenTheSeriesHasNone() {
		$term_id = $this->createSeries( array( 'description' => '' ) );

		$schema = $this->generateSchema( $term_id );

		$this->assertArrayNotHasKey( 'description', $schema );
	}

	/**
	 * The per-series author setting wins over the global one.
	 */
	public function testAuthorUsesThePerSeriesSetting() {
		$term_id = $this->createSeries();
		update_option( 'ss_podcasting_data_author', 'Global Host' );
		update_option( 'ss_podcasting_data_author_' . $term_id, 'Series Host' );

		$schema = $this->generateSchema( $term_id );

		$this->assertSame(
			array(
				'@type' => 'Person',
				'name'  => 'Series Host',
			),
			$schema['author']
		);
	}

	/**
	 * With no author configured anywhere, the site name stands in.
	 */
	public function testAuthorFallsBackToTheSiteName() {
		$term_id = $this->createSeries();
		delete_option( 'ss_podcasting_data_author' );
		delete_option( 'ss_podcasting_data_author_' . $term_id );

		$schema = $this->generateSchema( $term_id );

		$this->assertSame( get_bloginfo( 'name' ), $schema['author']['name'] );
	}
}
