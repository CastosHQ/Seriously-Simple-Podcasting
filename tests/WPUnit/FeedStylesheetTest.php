<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;

class FeedStylesheetTest extends \Codeception\TestCase\WPTestCase {

	private $series_id;
	private $server_backup;
	private $error_reporting;

	protected function setUp(): void {
		parent::setUp();

		$this->error_reporting = error_reporting();
		error_reporting( E_ALL );
		$this->server_backup = $_SERVER;
		$_SERVER['REQUEST_URI'] = '/';
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		ssp_app()->series_controller->register_taxonomy();
		$this->series_id = self::factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_wp_die_handler' ) );
		add_filter( 'wp_die_json_handler', array( $this, 'get_wp_die_handler' ) );
	}

	protected function tearDown(): void {
		$_SERVER = $this->server_backup;
		error_reporting( $this->error_reporting );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_wp_die_handler' ) );
		remove_filter( 'wp_die_json_handler', array( $this, 'get_wp_die_handler' ) );
		parent::tearDown();
	}

	/** @dataProvider feed_types */
	public function testStylesheetIsAbsentByDefault( $type ) {
		$feed = $this->render_feed( $type );

		$this->assertStringNotContainsString( '<?xml-stylesheet', $feed );
		$this->assert_valid_feed_xml( $feed );
	}

	/** @dataProvider feed_types */
	public function testLegacyStylesheetFiltersHaveNoEffect( $type ) {
		$enable_calls = 0;
		$url_calls = 0;
		$enable = static function () use ( &$enable_calls ) {
			++$enable_calls;
			return true;
		};
		$url = static function () use ( &$url_calls ) {
			++$url_calls;
			return 'https://example.com/custom-feed.xsl';
		};
		add_filter( 'ssp_enable_rss_stylesheet', $enable );
		add_filter( 'ssp_rss_stylesheet', $url );
		try {
			$feed = $this->render_feed( $type );
		} finally {
			remove_filter( 'ssp_enable_rss_stylesheet', $enable );
			remove_filter( 'ssp_rss_stylesheet', $url );
		}
		$this->assertStringNotContainsString( '<?xml-stylesheet', $feed );
		$this->assertSame( 0, $enable_calls );
		$this->assertSame( 0, $url_calls );
		$this->assert_valid_feed_xml( $feed );
	}

	public function testLegacyThemeTemplateReceivesEmptyStylesheetUrl() {
		$template = static function () {
			return dirname( __DIR__ ) . '/Support/fixtures/legacy-theme/feed-podcast.php';
		};
		add_filter( 'ssp_feed_template_file', $template );
		set_error_handler( static function ( $severity, $message, $file ) {
			if ( false !== strpos( $file, 'legacy-theme/feed-podcast.php' ) && false !== strpos( $message, 'stylesheet_url' ) ) {
				throw new \ErrorException( $message, 0, $severity, $file );
			}
			return false;
		} );
		try {
			$feed = $this->render_feed( 'podcast' );
		} finally {
			restore_error_handler();
			remove_filter( 'ssp_feed_template_file', $template );
		}
		$this->assertStringContainsString( '<title>Legacy template</title>', $feed );
		$this->assertStringContainsString( '<stylesheet-url></stylesheet-url>', $feed );
		$this->assert_valid_feed_xml( $feed );
	}

	public function testPodcastFeedRetainsChannelImage() {
		$image_url = 'https://example.com/podcast-cover.jpg';
		$image = static function () use ( $image_url ) {
			return $image_url;
		};
		add_filter( 'ssp_feed_image', $image );
		try {
			$feed = $this->render_feed( 'podcast' );
		} finally {
			remove_filter( 'ssp_feed_image', $image );
		}
		$this->assert_valid_feed_xml( $feed );
		$document = new \DOMDocument();
		$this->assertTrue( $document->loadXML( $feed, LIBXML_NONET ) );
		$xpath = new \DOMXPath( $document );
		$images = $xpath->query( '/rss/channel/image/url' );
		$this->assertCount( 1, $images );
		$this->assertSame( $image_url, $images->item( 0 )->textContent );
	}

	public static function feed_types() {
		return array(
			'podcast'   => array( 'podcast' ),
			'no access' => array( 'no-access' ),
			'not found' => array( '404' ),
		);
	}

	private function render_feed( $type ) {
		if ( 'podcast' === $type ) {
			$controller = new \ReflectionClass( 'SeriouslySimplePodcasting\\Controllers\\App_Controller' );
			$property = $controller->getProperty( 'feed_controller' );
			$property->setAccessible( true );
			$error_reporting = error_reporting();
			try {
				return $property->getValue( ssp_app() )->get_podcast_feed( $this->series_id );
			} finally {
				error_reporting( $error_reporting );
			}
		}

		// The response methods exit after rendering. Render the real template, then
		// interrupt before exit so the test process can inspect the output.
		$renderer = $this->getMockBuilder( Renderer::class )->onlyMethods( array( 'render' ) )->getMock();
		$renderer->method( 'render' )->willReturnCallback( static function ( $path, $data ) {
			( new Renderer() )->render( $path, $data );
			throw new \RuntimeException( 'Feed rendered' );
		} );
		$handler = new Feed_Handler( ssp_app()->get_settings_handler(), $renderer );
		ob_start();
		try {
			if ( 'no-access' === $type ) {
				$handler->render_feed_no_access( $this->series_id, 'Access denied' );
			} else {
				$handler->render_feed_404();
			}
		} catch ( \RuntimeException $exception ) {
			if ( 'Feed rendered' !== $exception->getMessage() ) {
				throw $exception;
			}
		} finally {
			$feed = ob_get_clean();
		}
		return $feed;
	}

	private function assert_valid_feed_xml( $feed ) {
		$document = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		try {
			libxml_clear_errors();
			$loaded = $document->loadXML( $feed, LIBXML_NONET );
			$errors = array_map( static function ( $error ) {
				return trim( $error->message );
			}, libxml_get_errors() );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
		}
		$this->assertTrue( $loaded, 'Feed XML must be well-formed: ' . implode( '; ', $errors ) );
		$this->assertSame( 'rss', $document->documentElement->tagName );
	}
}
