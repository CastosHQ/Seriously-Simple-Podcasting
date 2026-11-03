<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;

class FeedStylesheetTest extends \Codeception\TestCase\WPTestCase {

	private $series_id;
	private $server_backup;

	protected function setUp(): void {
		parent::setUp();

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
	public function testStylesheetCanBeEnabled( $type ) {
		add_filter( 'ssp_enable_rss_stylesheet', '__return_true' );
		try {
			$feed = $this->render_feed( $type );
		} finally {
			remove_filter( 'ssp_enable_rss_stylesheet', '__return_true' );
		}

		$url = esc_url( SSP_PLUGIN_URL . 'templates/feed-stylesheet.xsl?v=2' );
		$this->assertStringContainsString( '<?xml-stylesheet type="text/xsl" href="' . $url . '"?>', $feed );
		$this->assert_valid_feed_xml( $feed );
	}

	public function testEnabledStylesheetUrlCanBeFiltered() {
		$url = 'https://example.com/custom-feed.xsl';
		$filter = static function () use ( $url ) {
			return $url;
		};
		add_filter( 'ssp_enable_rss_stylesheet', '__return_true' );
		add_filter( 'ssp_rss_stylesheet', $filter );
		try {
			$feed = $this->render_feed( 'podcast' );
		} finally {
			remove_filter( 'ssp_enable_rss_stylesheet', '__return_true' );
			remove_filter( 'ssp_rss_stylesheet', $filter );
		}

		$this->assertStringContainsString( '<?xml-stylesheet type="text/xsl" href="' . $url . '"?>', $feed );
		$this->assertStringNotContainsString( 'feed-stylesheet.xsl', $feed );
		$this->assert_valid_feed_xml( $feed );
	}

	public function testCustomUrlRequiresEnableFilter() {
		$filter = static function () {
			return 'https://example.com/custom-feed.xsl';
		};
		add_filter( 'ssp_rss_stylesheet', $filter );
		try {
			$feed = $this->render_feed( 'podcast' );
		} finally {
			remove_filter( 'ssp_rss_stylesheet', $filter );
		}
		$this->assertStringNotContainsString( '<?xml-stylesheet', $feed );
		$this->assert_valid_feed_xml( $feed );
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
			return $property->getValue( ssp_app() )->get_podcast_feed( $this->series_id );
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
