<?php

namespace Tests\WPUnit;

class SspDownloadFileTest extends \Codeception\TestCase\WPTestCase {

	public function testSspDownloadFileCopiesLocalFileAndEmitsDeprecation(): void {
		$source_path = tempnam( get_temp_dir(), 'ssp-source-' );
		$target_path = tempnam( get_temp_dir(), 'ssp-target-' );
		$contents    = 'sample file';

		file_put_contents( $source_path, $contents );

		try {
			$this->setExpectedDeprecated( 'ssp_download_file' );

			$this->assertTrue( ssp_download_file( $source_path, $target_path ) );
			$this->assertSame( $contents, file_get_contents( $target_path ) );
		} finally {
			if ( file_exists( $source_path ) ) {
				unlink( $source_path );
			}

			if ( file_exists( $target_path ) ) {
				unlink( $target_path );
			}
		}
	}
}
