<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Renderers\Settings_Renderer;

class SettingsHandlerTest extends \Codeception\TestCase\WPTestCase {

	protected Settings_Handler $handler;
	protected int $default_series_id;

	protected function setUp(): void {
		parent::setUp();
		$this->handler           = new Settings_Handler();
		$this->default_series_id = ssp_get_default_series_id();
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Handlers\Settings_Handler::get_podcasts_list
	 */
	public function testGetPodcastsListAppendsDefaultLabel() {
		$list         = $this->callGetPodcastsList();
		$default_name = $list[ $this->default_series_id ] ?? null;

		$this->assertNotNull( $default_name, 'Default podcast should be in the list' );
		$this->assertStringContainsString( '(default)', $default_name );
	}

	/**
	 * Regression test: get_podcasts_list() must not mutate the WP_Term objects shared
	 * with the wp_cache. Previously it did `$podcast->name = ssp_get_default_series_name(...)`,
	 * which poisoned the cache so that render_feed_link() would call the same function again
	 * on an already-decorated name, producing "Name (default) (default)".
	 *
	 * @covers \SeriouslySimplePodcasting\Handlers\Settings_Handler::get_podcasts_list
	 */
	public function testGetPodcastsListDoesNotMutateTermName() {
		$original_name = get_term( $this->default_series_id, ssp_series_taxonomy() )->name;

		$this->callGetPodcastsList();

		$podcasts = ssp_get_podcasts();
		foreach ( $podcasts as $podcast ) {
			if ( $podcast->term_id === $this->default_series_id ) {
				$this->assertSame(
					$original_name,
					$podcast->name,
					'get_podcasts_list() must not modify the WP_Term name in the object cache'
				);
				return;
			}
		}

		$this->fail( 'Default podcast not found in ssp_get_podcasts()' );
	}

	/**
	 * Regression test: after get_podcasts_list() runs, rendering the feed link must show
	 * "(default)" exactly once — not twice.
	 *
	 * @covers \SeriouslySimplePodcasting\Renderers\Settings_Renderer::render_feed_link
	 */
	public function testFeedLinkDoesNotShowDefaultLabelTwice() {
		$this->callGetPodcastsList();

		$render_feed_link = new \ReflectionMethod( Settings_Renderer::instance(), 'render_feed_link' );
		$render_feed_link->setAccessible( true );
		$html = $render_feed_link->invoke( Settings_Renderer::instance() );

		$this->assertSame(
			1,
			substr_count( $html, '(default)' ),
			'Feed link HTML must contain "(default)" exactly once'
		);
	}

	protected function callGetPodcastsList(): array {
		$method = new \ReflectionMethod( $this->handler, 'get_podcasts_list' );
		$method->setAccessible( true );
		return $method->invoke( $this->handler );
	}
}
