<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Repositories\Episode_Repository;

class EpisodeRepositoryEnclosureTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Episode_Repository
	 */
	protected $episode_repository;

	protected function setUp(): void {
		parent::setUp();
		$this->episode_repository = ssp_get_service( 'episode_repository' );
	}

	/**
	 * @covers Episode_Repository::format_enclosure
	 */
	public function testFormatEnclosureProducesThreeNewlineSeparatedParts() {
		$value = $this->episode_repository->format_enclosure( 'https://example.com/episode.mp3', 12345, 'audio/mpeg' );

		$this->assertSame( "https://example.com/episode.mp3\n12345\naudio/mpeg\n", $value );

		// Mirrors WP core rss_enclosure()/PowerPress parsing: explode keeps url/size/mime in order.
		$parts = explode( "\n", $value );
		$this->assertSame( 'https://example.com/episode.mp3', $parts[0] );
		$this->assertSame( '12345', $parts[1] );
		$this->assertSame( 'audio/mpeg', $parts[2] );
	}

	/**
	 * @covers Episode_Repository::format_enclosure
	 */
	public function testFormatEnclosureCastsSizeToIntAndDefaultsToZero() {
		$this->assertSame( "https://example.com/e.mp3\n0\naudio/mpeg\n", $this->episode_repository->format_enclosure( 'https://example.com/e.mp3', '' ) );
		$this->assertSame( "https://example.com/e.mp3\n999\naudio/mpeg\n", $this->episode_repository->format_enclosure( 'https://example.com/e.mp3', '999' ) );
	}

	/**
	 * @covers Episode_Repository::format_enclosure
	 */
	public function testFormatEnclosureDerivesMimeFromExtensionWhenNotProvided() {
		$audio = $this->episode_repository->format_enclosure( 'https://example.com/show.mp3', 1 );
		$video = $this->episode_repository->format_enclosure( 'https://example.com/show.mp4', 1 );

		$this->assertSame( 'audio/mpeg', explode( "\n", $audio )[2] );
		$this->assertSame( 'video/mp4', explode( "\n", $video )[2] );
	}

	/**
	 * @covers Episode_Repository::get_file_mime_type
	 */
	public function testMimeTypeDefaultsToAudioForUnknownExtension() {
		$this->assertSame( 'audio/mpeg', $this->episode_repository->get_file_mime_type( 'https://example.com/file.unknownext' ) );
	}

	/**
	 * @covers Episode_Repository::get_file_mime_type
	 */
	public function testMimeTypeFilterCanOverrideDerivedValue() {
		add_filter( 'ssp_enclosure_mime_type', function () {
			return 'audio/x-custom';
		} );

		$this->assertSame( 'audio/x-custom', $this->episode_repository->get_file_mime_type( 'https://example.com/file.mp3' ) );

		remove_all_filters( 'ssp_enclosure_mime_type' );
	}

	/**
	 * @covers Episode_Repository::format_enclosure
	 */
	public function testFormatEnclosureReturnsEmptyStringForEmptyUrl() {
		$this->assertSame( '', $this->episode_repository->format_enclosure( '', 100, 'audio/mpeg' ) );
	}
}
