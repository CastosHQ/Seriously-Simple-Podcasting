<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Repositories\Episode_Repository;

/**
 * Duration calculation for local audio files.
 *
 * getID3 (1.9.20+, bundled with WordPress since 5.5) doubles the bitrate it derives
 * from a Xing/Info header for mono files, so the playtime it reports is halved. The
 * fixtures below are synthesised in-process rather than committed as binaries — see
 * build_mp3() for the byte layout.
 */
class EpisodeRepositoryDurationTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Episode_Repository
	 */
	protected $episode_repository;

	/**
	 * Fixture paths to remove after each test.
	 *
	 * @var string[]
	 */
	protected $fixtures = array();

	protected function setUp(): void {
		parent::setUp();
		$this->episode_repository = ssp_get_service( 'episode_repository' );
	}

	protected function tearDown(): void {
		foreach ( $this->fixtures as $path ) {
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}
		$this->fixtures = array();
		parent::tearDown();
	}

	/**
	 * Writes a minimal MPEG Layer III stream and returns its path.
	 *
	 * Only the frame headers and the Xing/LAME tag are meaningful — the payload is
	 * silence, which is all getID3 reads. `advertised_frames` defaults to the number
	 * of frames actually written; passing a larger value produces a file whose header
	 * claims a longer runtime than the data, which keeps long-duration fixtures small.
	 *
	 * @param array $args mpeg_version, channels, frames, lame, advertised_frames.
	 *
	 * @return string Absolute path to the fixture.
	 */
	protected function build_mp3( $args = array() ) {
		$args = array_merge(
			array(
				'mpeg_version' => 1,     // 1 = MPEG-1 @ 44.1kHz, 2 = MPEG-2 @ 22.05kHz.
				'channels'     => 1,
				'frames'       => 460,
				'xing'         => true,
				'lame'         => true,
				'vbr_method'   => 0,     // 0 ("unknown") arms the defect; 1 ("cbr") is corrected by getID3.
			),
			$args
		);

		$mono = 1 === $args['channels'];

		if ( 1 === $args['mpeg_version'] ) {
			$byte1       = "\xFB";                 // MPEG-1, Layer III, no CRC.
			$byte2       = "\x90";                 // 128 kbps, 44.1 kHz.
			$sample_rate = 44100;
			$bitrate     = 128000;
			$side_info   = $mono ? 17 : 32;
			$frame_len   = (int) floor( 144 * $bitrate / $sample_rate );
		} else {
			$byte1       = "\xF3";                 // MPEG-2, Layer III, no CRC.
			$byte2       = "\x80";                 // 64 kbps, 22.05 kHz.
			$sample_rate = 22050;
			$bitrate     = 64000;
			$side_info   = $mono ? 9 : 17;
			$frame_len   = (int) floor( 72 * $bitrate / $sample_rate );
		}

		$byte3  = $mono ? "\xC4" : "\x04";         // Channel mode: mono / stereo.
		$header = "\xFF" . $byte1 . $byte2 . $byte3;

		$advertised = isset( $args['advertised_frames'] ) ? $args['advertised_frames'] : $args['frames'];
		$frame      = str_pad( $header, $frame_len, "\x00" );

		if ( ! $args['xing'] ) {
			return $this->write_fixture( str_repeat( $frame, $args['frames'] ) );
		}

		$vbr_offset = 4 + $side_info;
		$first      = $header . str_repeat( "\x00", $side_info )
			. 'Xing' . pack( 'N', 0x07 )           // Flags: frames | bytes | TOC.
			. pack( 'N', $advertised )
			. pack( 'N', $advertised * $frame_len )
			. str_repeat( "\x00", 100 );           // TOC.
		$first      = str_pad( $first, $frame_len, "\x00" );

		if ( $args['lame'] ) {
			// Offsets are relative to the Xing identifier — see getID3
			// module.audio.mp3.php, which reads long_version at +120 and derives the
			// rest from $VBRidOffset - 0x24.
			$first = substr_replace( $first, 'LAME3.101', $vbr_offset + 120, 9 );
			$first = substr_replace( $first, chr( $args['vbr_method'] & 0x0F ), $vbr_offset + 0x81, 1 );
			$first = substr_replace( $first, pack( 'N', $advertised * $frame_len ), $vbr_offset + 0x94, 4 );
		}

		// The Xing count excludes its own header frame, per the LAME convention.
		return $this->write_fixture( $first . str_repeat( $frame, $args['frames'] ) );
	}

	/**
	 * Persists fixture bytes to a temp file registered for cleanup.
	 *
	 * @param string $bytes Raw MP3 stream.
	 *
	 * @return string Absolute path.
	 */
	protected function write_fixture( $bytes ) {
		$base = tempnam( get_temp_dir(), 'ssp-duration-' );
		$path = $base . '.mp3';

		unlink( $base ); // tempnam() creates the file; only the .mp3 path is used.
		file_put_contents( $path, $bytes );
		$this->fixtures[] = $path;

		return $path;
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
	 * Writes a mono PCM WAV and returns its path — mono, but not an MP3.
	 *
	 * @param int $seconds Length of silence to write.
	 *
	 * @return string Absolute path to the fixture.
	 */
	protected function build_wav( $seconds = 2 ) {
		$rate = 8000;
		$data = str_repeat( "\x80", $rate * $seconds ); // 8-bit mono silence.

		$header = 'RIFF' . pack( 'V', 36 + strlen( $data ) ) . 'WAVE'
			. 'fmt ' . pack( 'V', 16 )
			. pack( 'v', 1 )      // PCM.
			. pack( 'v', 1 )      // Mono.
			. pack( 'V', $rate )
			. pack( 'V', $rate )  // Byte rate.
			. pack( 'v', 1 )      // Block align.
			. pack( 'v', 8 )      // Bits per sample.
			. 'data' . pack( 'V', strlen( $data ) );

		return $this->write_fixture( $header . $data );
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
