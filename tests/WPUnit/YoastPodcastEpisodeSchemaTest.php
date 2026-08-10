<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Integrations\Yoast\Schema\PodcastEpisode;

// Yoast SEO is not activated in the WPUnit suite, so the schema base class this
// integration extends must exist before the autoloader pulls in PodcastEpisode.
require_once __DIR__ . '/_yoast-schema-stubs.php';

/**
 * Covers the episode duration emitted in the Yoast PodcastEpisode schema (#867).
 *
 * @covers \SeriouslySimplePodcasting\Integrations\Yoast\Schema\PodcastEpisode::generate
 */
class YoastPodcastEpisodeSchemaTest extends \Codeception\TestCase\WPTestCase {

	const ENCLOSURE = 'https://episodes.castos.com/example-episode.mp3';

	protected function setUp(): void {
		parent::setUp();

		// An empty duration meta makes get_duration() read the media file and write
		// the result back; disabling that keeps the absent-duration cases read-only.
		add_filter( 'ssp_enable_get_file_duration', '__return_false' );
	}

	protected function tearDown(): void {
		remove_filter( 'ssp_enable_get_file_duration', '__return_false' );
		parent::tearDown();
	}

	/**
	 * Creates a published episode carrying an enclosure and the given stored duration.
	 */
	protected function createEpisode( $duration ) {
		$episode_id = $this->factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => SSP_CPT_PODCAST,
		) );

		update_post_meta( $episode_id, 'audio_file', self::ENCLOSURE );
		update_post_meta( $episode_id, 'duration', $duration );

		return $episode_id;
	}

	/**
	 * Runs the schema piece against an episode and returns the generated graph.
	 */
	protected function generateSchema( $episode_id ) {
		$piece = new PodcastEpisode( ssp_episode_repository() );

		$context            = new \stdClass();
		$context->post      = get_post( $episode_id );
		$context->canonical = get_permalink( $episode_id );
		$context->title     = get_the_title( $episode_id );

		$piece->context = $context;

		return $piece->generate();
	}

	/**
	 * Every duration format SSP stores or accepts reaches the schema as ISO 8601.
	 *
	 * @dataProvider storedDurationProvider
	 */
	public function testStoredDurationIsEmittedAsIso8601( $stored, $expected ) {
		$episode_id = $this->createEpisode( $stored );

		$schema = $this->generateSchema( $episode_id );

		$this->assertArrayHasKey(
			'duration',
			$schema,
			sprintf( 'Stored duration "%s" produced no schema duration.', $stored )
		);
		$this->assertSame( $expected, $schema['duration'] );
	}

	/**
	 * Emitted values place the time components after the ISO 8601 T separator.
	 *
	 * @dataProvider storedDurationProvider
	 */
	public function testEmittedDurationIsValidIso8601( $stored, $expected ) {
		$episode_id = $this->createEpisode( $stored );

		$schema = $this->generateSchema( $episode_id );

		$this->assertArrayHasKey( 'duration', $schema );
		$this->assertStringStartsWith(
			'PT',
			$schema['duration'],
			sprintf( 'Time components must follow the T separator, got "%s".', $schema['duration'] )
		);

		// Without the T separator DateInterval still parses the value, but reads the
		// components as a date period — "P14M" is fourteen months, not fourteen minutes.
		$interval = new \DateInterval( $schema['duration'] );

		$this->assertSame(
			0,
			$interval->y + $interval->m + $interval->d,
			sprintf( 'Duration "%s" was parsed as a date period, not a time period.', $schema['duration'] )
		);
	}

	/**
	 * Parsing adapts to the stored value rather than rewriting it (ssp-issues#201).
	 *
	 * @dataProvider storedDurationProvider
	 */
	public function testStoredDurationMetaIsNotRewritten( $stored, $expected ) {
		$episode_id = $this->createEpisode( $stored );

		$this->generateSchema( $episode_id );

		$this->assertSame(
			$stored,
			get_post_meta( $episode_id, 'duration', true ),
			'The stored duration format must be preserved.'
		);
	}

	public function storedDurationProvider() {
		return array(
			'unpadded seconds only'         => array( '0:27', 'PT1M' ),
			'padded seconds only'           => array( '00:27', 'PT1M' ),
			'minutes rounding up'           => array( '13:38', 'PT14M' ),
			'minutes rounding down'         => array( '27:16', 'PT27M' ),
			'seconds exactly on the half'   => array( '13:30', 'PT13M' ),
			'seconds one past the half'     => array( '13:31', 'PT14M' ),
			'seconds just over the half'    => array( '2:31', 'PT3M' ),
			'unpadded hours'                => array( '1:13:38', 'PT1H14M' ),
			'padded hours'                  => array( '01:13:38', 'PT1H14M' ),
			'hours with padded minutes'     => array( '2:05:09', 'PT2H5M' ),
			'whole hour'                    => array( '1:00:00', 'PT1H' ),
			'hour with trailing seconds'    => array( '1:00:20', 'PT1H' ),
			'rounding up into a whole hour' => array( '59:45', 'PT1H' ),
			'rounding up into the next hour' => array( '1:59:45', 'PT2H' ),
		);
	}

	/**
	 * A duration that is absent, unparseable, or rounds to zero emits no key at all
	 * rather than an invalid value.
	 *
	 * @dataProvider absentDurationProvider
	 */
	public function testUnusableDurationEmitsNoDurationKey( $stored ) {
		$episode_id = $this->createEpisode( $stored );

		$schema = $this->generateSchema( $episode_id );

		$this->assertArrayNotHasKey(
			'duration',
			$schema,
			sprintf( 'Stored duration "%s" should not produce a schema duration.', $stored )
		);

		// The empty case reaches get_duration()'s backfill branch, which writes meta.
		$this->assertSame(
			$stored,
			get_post_meta( $episode_id, 'duration', true ),
			'An unusable duration must not be backfilled or rewritten.'
		);
	}

	public function absentDurationProvider() {
		return array(
			'empty meta'          => array( '' ),
			'non-time text'       => array( 'not a duration' ),
			'zero minutes'        => array( '0:00' ),
			'zero hours'          => array( '0:00:00' ),
		);
	}

	/**
	 * An episode with no media attached contributes nothing to the graph.
	 */
	public function testEpisodeWithoutEnclosureGeneratesNothing() {
		$episode_id = $this->factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => SSP_CPT_PODCAST,
		) );

		$this->assertSame( array(), $this->generateSchema( $episode_id ) );
	}

	/**
	 * Identity and metadata keys are wired from the Yoast context and the post.
	 */
	public function testSchemaCarriesEpisodeIdentityAndMetadata() {
		$episode_id = $this->createEpisode( '13:38' );
		wp_update_post( array(
			'ID'           => $episode_id,
			'post_date'    => '2026-03-04 09:15:00',
			'post_excerpt' => 'An episode about testing.',
		) );

		$schema    = $this->generateSchema( $episode_id );
		$permalink = get_permalink( $episode_id );

		$this->assertSame( 'PodcastEpisode', $schema['@type'] );
		$this->assertSame( $permalink . '#/schema/podcast', $schema['@id'] );
		$this->assertSame( $permalink, $schema['url'] );
		$this->assertSame( get_the_title( $episode_id ), $schema['name'] );
		$this->assertSame( '2026-03-04', $schema['datePublished'] );
		$this->assertSame( 'An episode about testing.', $schema['description'] );
	}

	/**
	 * The enclosure is exposed under the graph key matching the episode's media type.
	 *
	 * @dataProvider episodeTypeProvider
	 */
	public function testEnclosureIsExposedUnderTheKeyMatchingItsType( $episode_type, $expected_key, $expected_type ) {
		$episode_id = $this->createEpisode( '13:38' );
		update_post_meta( $episode_id, 'filesize', '12 MB' );
		update_post_meta( $episode_id, 'episode_type', $episode_type );

		$schema = $this->generateSchema( $episode_id );

		$this->assertArrayHasKey( $expected_key, $schema );
		$this->assertSame( $expected_type, $schema[ $expected_key ]['@type'] );
		$this->assertSame( self::ENCLOSURE, $schema[ $expected_key ]['contentUrl'] );
		$this->assertSame( '12 MB', $schema[ $expected_key ]['contentSize'] );
	}

	public function episodeTypeProvider() {
		return array(
			'audio'             => array( 'audio', 'audio', 'AudioObject' ),
			'video'             => array( 'video', 'video', 'VideoObject' ),
			'unrecognised type' => array( 'something-else', 'associatedMedia', 'MediaObject' ),
			'no type recorded'  => array( '', 'audio', 'AudioObject' ),
		);
	}

	/**
	 * An episode belonging to no series emits no partOfSeries key.
	 */
	public function testPartOfSeriesIsAbsentWhenTheEpisodeHasNoSeries() {
		$episode_id = $this->createEpisode( '13:38' );
		wp_set_object_terms( $episode_id, array(), ssp_series_taxonomy() );

		$schema = $this->generateSchema( $episode_id );

		$this->assertArrayNotHasKey( 'partOfSeries', $schema );
	}

	/**
	 * Each series the episode belongs to becomes a PodcastSeries node.
	 */
	public function testPartOfSeriesCarriesEachAssignedSeries() {
		$episode_id = $this->createEpisode( '13:38' );
		$term_id    = $this->factory()->term->create( array(
			'taxonomy' => ssp_series_taxonomy(),
			'name'     => 'Season One',
		) );
		wp_set_object_terms( $episode_id, array( $term_id ), ssp_series_taxonomy() );

		$schema = $this->generateSchema( $episode_id );
		$url    = get_term_link( $term_id, ssp_series_taxonomy() );

		$series = null;
		foreach ( $schema['partOfSeries'] as $node ) {
			if ( 'Season One' === $node['name'] ) {
				$series = $node;
			}
		}

		$this->assertNotNull( $series, 'The assigned series is missing from partOfSeries.' );
		$this->assertSame( 'PodcastSeries', $series['@type'] );
		$this->assertSame( $url, $series['url'] );
		$this->assertSame( $url . '#/schema/podcastSeries', $series['id'] );
	}

	/**
	 * A series whose permalink cannot be resolved is skipped rather than emitted broken.
	 */
	public function testSeriesWithAnUnresolvableLinkIsSkipped() {
		$episode_id = $this->createEpisode( '13:38' );

		// A term ID that resolves to nothing makes get_term_link() return WP_Error.
		$inject_missing_term = function () {
			return array( 999999999 );
		};
		add_filter( 'wp_get_object_terms', $inject_missing_term );

		$schema = $this->generateSchema( $episode_id );

		remove_filter( 'wp_get_object_terms', $inject_missing_term );

		$this->assertArrayNotHasKey( 'partOfSeries', $schema );
	}
}
