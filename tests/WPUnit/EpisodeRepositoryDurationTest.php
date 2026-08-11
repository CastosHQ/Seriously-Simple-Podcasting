<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Repositories\Episode_Repository;

require_once __DIR__ . '/_mp3-fixtures.php';

/**
 * Duration calculation for local audio files.
 *
 * getID3 (1.9.20+, bundled with WordPress since 5.5) doubles the bitrate it derives
 * from a Xing/Info header for mono files, so the playtime it reports is halved. The
 * fixtures are synthesised in-process rather than committed as binaries — see
 * Mp3_Fixtures::build_mp3() for the byte layout.
 */
class EpisodeRepositoryDurationTest extends \Codeception\TestCase\WPTestCase {

	use Mp3_Fixtures;

	/**
	 * @var Episode_Repository
	 */
	protected $episode_repository;

	protected function setUp(): void {
		parent::setUp();
		$this->episode_repository = ssp_get_service( 'episode_repository' );
	}

	protected function tearDown(): void {
		$this->remove_fixtures();
		parent::tearDown();
	}

	/**
	 * @covers Episode_Repository::get_file_duration
	 */
	public function testMonoMp3ReportsRealDurationInsteadOfHalf() {
		// 460 frames * 1152 samples / 44100 Hz = 12.02s. getID3 alone reports 0:06.
		$file = $this->build_mp3( array( 'frames' => 460 ) );

		$this->assertSame( '0:12', $this->episode_repository->get_file_duration( $file ) );
	}

	/**
	 * @covers Episode_Repository::get_file_duration
	 */
	public function testMonoMp3UsesMpeg2SamplesPerFrame() {
		// MPEG-2 Layer III uses 576 samples per frame: 500 * 576 / 22050 = 13.06s.
		$file = $this->build_mp3(
			array(
				'mpeg_version' => 2,
				'frames'       => 500,
			)
		);

		$this->assertSame( '0:13', $this->episode_repository->get_file_duration( $file ) );
	}

	/**
	 * The gate is deliberately wider than the defect: it catches every mono MP3, not
	 * just the vbr_method values getID3 mis-times. This pins the resulting risk — a
	 * mono MPEG-1 file getID3 already reads correctly must come out unchanged.
	 *
	 * @covers Episode_Repository::get_file_duration
	 */
	public function testCorrectlyReadMonoMp3IsUnchanged() {
		// vbr_method 1 ("cbr") is corrected by getID3 itself, so it is not affected.
		$file = $this->build_mp3(
			array(
				'frames'     => 460,
				'vbr_method' => 1,
			)
		);

		$data = wp_read_audio_metadata( $file );

		$this->assertSame( 1, (int) $data['channels'], 'fixture should be mono' );
		$this->assertSame( '0:12', $data['length_formatted'], 'getID3 should already be correct here' );
		$this->assertSame( $data['length_formatted'], $this->episode_repository->get_file_duration( $file ) );
	}

	/**
	 * @covers Episode_Repository::get_file_duration
	 */
	public function testStereoDurationIsLeftToGetId3() {
		// Stereo is unaffected by the defect, so the value must match getID3's exactly.
		$file = $this->build_mp3(
			array(
				'channels' => 2,
				'frames'   => 460,
			)
		);

		$data = wp_read_audio_metadata( $file );

		$this->assertSame( $data['length_formatted'], $this->episode_repository->get_file_duration( $file ) );
	}

	/**
	 * @covers Episode_Repository::get_file_duration
	 */
	public function testMonoMp3WithoutXingHeaderIsLeftToGetId3() {
		// No frame count to recompute from, so getID3's value must survive untouched.
		$file = $this->build_mp3(
			array(
				'frames' => 200,
				'xing'   => false,
			)
		);

		$data = wp_read_audio_metadata( $file );

		$this->assertSame( $data['length_formatted'], $this->episode_repository->get_file_duration( $file ) );
	}

	/**
	 * Durations must stay unpadded — ssp-issues#201 removed the leading "00:".
	 *
	 * @covers Episode_Repository::get_file_duration
	 */
	public function testDurationFormatIsNotZeroPadded() {
		$short = $this->build_mp3( array( 'frames' => 460 ) );

		$this->assertSame( '0:12', $this->episode_repository->get_file_duration( $short ) );

		// An hour of audio would be a 57 MB fixture, so advertise the frame count
		// instead of writing it: 137813 * 1152 / 44100 = 3600.02s.
		$long = $this->build_mp3(
			array(
				'frames'            => 40,
				'advertised_frames' => 137813,
			)
		);

		$this->assertSame( '1:00:00', $this->episode_repository->get_file_duration( $long ) );
	}

	/**
	 * The correction is for MP3s — a mono WAV must not be touched.
	 *
	 * @covers Episode_Repository::is_mono_mp3
	 */
	public function testMonoWavIsLeftToGetId3() {
		$file = $this->build_wav( 2 );
		$data = wp_read_audio_metadata( $file );

		$this->assertSame( 1, (int) $data['channels'], 'fixture should be mono' );
		$this->assertNotSame( 'mp3', $data['dataformat'], 'fixture should not be an MP3' );
		$this->assertSame( $data['length_formatted'], $this->episode_repository->get_file_duration( $file ) );
	}

	/**
	 * @covers Episode_Repository::get_file_duration
	 */
	public function testUnreadableFileYieldsNoDuration() {
		$missing = get_temp_dir() . 'ssp-does-not-exist-' . uniqid() . '.mp3';

		$this->assertFalse( $this->episode_repository->get_file_duration( $missing ) );
	}

	/**
	 * The filter is how remote files get a duration at all, so it must fire even when
	 * no local metadata could be read.
	 *
	 * @covers Episode_Repository::get_file_duration
	 */
	public function testDurationFilterRunsWhenMetadataIsUnavailable() {
		$missing  = get_temp_dir() . 'ssp-does-not-exist-' . uniqid() . '.mp3';
		$received = 'not-called';

		$filter = function ( $duration ) use ( &$received ) {
			$received = $duration;

			return '42:00';
		};

		add_filter( 'ssp_file_duration', $filter );
		$result = $this->episode_repository->get_file_duration( $missing );
		remove_filter( 'ssp_file_duration', $filter );

		$this->assertFalse( $received, 'filter should receive the unresolved duration' );
		$this->assertSame( '42:00', $result, 'filter return value should be used' );
	}

	/**
	 * @covers Episode_Repository::ensure_getid3
	 */
	public function testEnsureGetid3ReportsTheLibraryAvailable() {
		$method = new \ReflectionMethod( Episode_Repository::class, 'ensure_getid3' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $this->episode_repository ) );
		$this->assertTrue( method_exists( 'getid3_lib', 'PlaytimeString' ) );
	}

	/**
	 * @covers Episode_Repository::mp3_samples_per_frame
	 */
	public function testSamplesPerFrameLookup() {
		$method = new \ReflectionMethod( Episode_Repository::class, 'mp3_samples_per_frame' );
		$method->setAccessible( true );

		$cases = array(
			// version, layer, expected samples per frame.
			array( '1', '1', 384 ),
			array( '2', '1', 384 ),
			array( '1', '2', 1152 ),
			array( '2', '2', 1152 ),
			array( '1', '3', 1152 ),
			array( '2', '3', 576 ),
			array( '2.5', '3', 576 ),
			array( '1', '', 0 ),
			// Layers 1 and 2 do not depend on the version, so they still resolve.
			array( '', '1', 384 ),
			array( '', '2', 1152 ),
			// Layer 3 does, and guessing would halve an MPEG-1 duration.
			array( '', '3', 0 ),
			array( '9', '3', 0 ),
		);

		foreach ( $cases as list( $version, $layer, $expected ) ) {
			$this->assertSame(
				$expected,
				$method->invoke( $this->episode_repository, $version, $layer ),
				sprintf( 'MPEG-%s Layer %s', $version, $layer ?: '?' )
			);
		}
	}
}
