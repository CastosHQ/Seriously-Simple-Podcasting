<?php

namespace Tests\WPUnit;

/**
 * Synthetic audio fixtures for duration tests.
 *
 * The streams are built in-process rather than committed as binaries — only the frame
 * headers and the Xing/LAME tag carry meaning, so the payload is silence.
 */
trait Mp3_Fixtures {

	/**
	 * Fixture paths to remove after each test.
	 *
	 * @var string[]
	 */
	protected $fixtures = array();

	/**
	 * Writes a minimal MPEG Layer III stream and returns its path.
	 *
	 * `advertised_frames` defaults to the number of frames actually written; passing a
	 * larger value produces a file whose header claims a longer runtime than the data,
	 * which keeps long-duration fixtures small.
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

		return $this->write_fixture( $header . $data, 'wav' );
	}

	/**
	 * Persists fixture bytes to a temp file registered for cleanup.
	 *
	 * @param string $bytes Raw audio stream.
	 * @param string $extension File extension, without the dot.
	 *
	 * @return string Absolute path.
	 */
	protected function write_fixture( $bytes, $extension = 'mp3' ) {
		$base = tempnam( get_temp_dir(), 'ssp-duration-' );
		$path = $base . '.' . $extension;

		unlink( $base ); // tempnam() creates the file; only the extended path is used.
		file_put_contents( $path, $bytes );
		$this->fixtures[] = $path;

		return $path;
	}

	/**
	 * Deletes every fixture written during the test.
	 *
	 * @return void
	 */
	protected function remove_fixtures() {
		foreach ( $this->fixtures as $path ) {
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}

		$this->fixtures = array();
	}
}
