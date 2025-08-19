<?php

use Codeception\TestCase\WPTestCase;
use SeriouslySimplePodcasting\Controllers\Players_Controller;
use SeriouslySimplePodcasting\Renderers\Renderer;

class SSP_Functions_Test extends WPTestCase {
	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @var Renderer
	 */
	protected $renderer;

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @covers ssp_version_notice()
	 */
	public function test_ssp_version_notice() {
		$this->assertTrue( function_exists( 'ssp_version_notice' ) );

		ob_start();
		ssp_version_notice();
		$out = ob_get_flush();

		$this->assertStringContainsString( '<div class="notice notice-warning">', $out );
		$this->assertStringContainsString( 'You are using the Seriously Simple Podcasting', $out );
	}

	/**
	 * @covers ssp_version_check()
	 */
	public function test_ssp_version_check() {
		$this->assertTrue( function_exists( 'ssp_version_check' ) );

		$res = ssp_version_check();

		if ( strstr( SSP_VERSION, 'beta' ) || strstr( SSP_VERSION, 'alpha' ) ) {
			$this->assertTrue( $res );
		} else {
			$this->assertFalse( $res );
		}
	}

	/**
	 * @covers ssp_php_version_notice()
	 */
	public function test_ssp_php_version_notice() {
		$this->assertTrue( function_exists( 'ssp_php_version_notice' ) );

		ob_start();
		ssp_php_version_notice();
		$res = ob_get_flush();

		$this->assertStringContainsString( 'The Seriously Simple Podcasting plugin requires PHP version 5.6 or higher. Please contact your web host to upgrade your PHP version or deactivate the plugin.', $res );
		$this->assertStringContainsString( 'We apologise for any inconvenience.', $res );
	}

	/**
	 * @covers ssp_is_php_version_ok()
	 */
	public function test_ssp_is_php_version_ok() {
		$this->assertTrue( function_exists( 'ssp_is_php_version_ok' ) );

		$res = ssp_is_php_version_ok();

		if ( version_compare( PHP_VERSION, '5.6', '>=' ) ) {
			$this->assertTrue( $res );
		} else {
			$this->assertFalse( $res );
		}
	}

	/**
	 * @covers ssp_vendor_notice()
	 */
	public function test_ssp_vendor_notice() {
		$this->assertTrue( function_exists( 'ssp_vendor_notice' ) );

		ob_start();
		ssp_vendor_notice();
		$out = ob_get_flush();

		$this->assertStringContainsString( 'The Seriously Simple Podcasting vendor directory is missing or broken, please re-download/reinstall the plugin.', $out );
		$this->assertStringContainsString( 'We apologise for any inconvenience.', $out );
	}

	/**
	 * @covers ssp_is_vendor_ok()
	 */
	public function test_ssp_is_vendor_ok() {
		$this->assertTrue( function_exists( 'ssp_is_vendor_ok' ) );

		$res = ssp_is_vendor_ok();

		$this->assertTrue( $res );
	}

	/**
	 * @covers ssp_get_upload_directory()
	 */
	public function test_ssp_get_upload_directory() {
		$this->assertTrue( function_exists( 'ssp_get_upload_directory' ) );

		$res = ssp_get_upload_directory();

		$this->assertStringContainsString( 'wp-content', $res );
		$this->assertStringContainsString( 'uploads', $res );
		$this->assertStringContainsString( 'ssp', $res );
	}

	/**
	 * @covers ssp_cannot_write_uploads_dir_error()
	 */
	public function test_ssp_cannot_write_uploads_dir_error() {
		$this->assertTrue( function_exists( 'ssp_cannot_write_uploads_dir_error' ) );

		ob_start();
		ssp_cannot_write_uploads_dir_error();
		$out = ob_get_flush();

		$this->assertStringContainsString( 'Unable to create directory', $out );
		$this->assertStringContainsString( 'Is its parent directory writable by the server?', $out );
	}

	/**
	 * @covers ssp_is_podcast_download()
	 */
	public function test_ssp_is_podcast_download() {
		$this->assertTrue( function_exists( 'ssp_is_podcast_download' ) );

		$this->assertFalse( ssp_is_podcast_download() );

		// Make sure the filter works
		$cb = function () {
		     return true;
		};
		
		add_filter( 'ssp_is_podcast_download', $cb );
		 
		$this->assertTrue( ssp_is_podcast_download() );
		remove_filter( 'ssp_is_podcast_download', $cb );
	}

	/**
	 * @covers ss_get_podcast()
	 */
	public function test_ss_get_podcast() {
		$this->assertTrue( function_exists( 'ss_get_podcast' ) );

		$args = array(
			'title'      => '',
			'content'    => 'episodes',
			'series'     => '',
			'echo'       => false,
			'size'       => 100,
			'link_title' => true,
		);

		$create_args = array(
			'post_type'   => SSP_CPT_PODCAST,
			'post_status' => 'publish',
		);

		// Test episodes

		$episodes_number = 6;

		$this->factory()->post->create_many(
			$episodes_number,
			$create_args,
			array(
				'post_title'   => new WP_UnitTest_Generator_Sequence( 'Episode %s' ),
				'post_content' => new WP_UnitTest_Generator_Sequence( 'Episode content %s' ),
			)
		);

		$episodes = ss_get_podcast( $args );

		$this->assertArrayHasKey( 'content', $episodes );
		$this->assertEquals( 'episodes', $episodes['content'] );

		$count = 0;
		foreach ( $episodes as $episode ) {
			if ( $episode instanceof WP_Post ) {
				++$count;
			}
		}

		$this->assertEquals( $count, $episodes_number );

		// Test series
		$args['content'] = 'series';

		$series_number = 3;

		// Create one less because default series should be created automatically on plugin activation
		$this->factory()->category->create_many(
			$series_number - 1,
			array(
				'taxonomy' => 'series',
			)
		);

		$terms = get_terms(
			array(
				'taxonomy'   => 'series',
				'hide_empty' => false,
			)
		);

		// +1 because there is a default Series already
		$this->assertCount( $series_number, $terms );

		foreach ( $terms as $term ) {
            $this->factory()->post->create(
                array(
                    'post_type'   => SSP_CPT_PODCAST,
                    'post_status' => 'publish',
                    'tax_input'   => array( 
						'series' => array( $term->term_id ) 
					),
                )
            );
		}

		$series = ss_get_podcast( $args );

		$this->assertArrayHasKey( 'content', $series );
		$this->assertEquals( 'series', $series['content'] );
		$count = 0;
		foreach ( $series as $item ) {
			if ( is_object( $item ) ) {
				$this->assertObjectHasProperty( 'title', $item );
				$this->assertObjectHasProperty( 'url', $item );
				$this->assertObjectHasProperty( 'count', $item );
				++$count;
			}
		}

		$this->assertEquals( $series_number, $count );
	}
}
