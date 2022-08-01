<?php

use Codeception\TestCase\WPTestCase;
use SeriouslySimplePodcasting\Controllers\Players_Controller;
use SeriouslySimplePodcasting\Renderers\Renderer;

class Renderer_Test extends WPTestCase {
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

		$this->renderer = new Renderer();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @covers Players_Controller::render_html_player() method returns the new html player code
	 */
	public function test_fetch_path() {
		// There are 4 possible path variants:
		// 1. Absolute path: /app/wp-content/plugins/seriously-simple-podcasting/templates/test.php
		// 2. Relative WP path: wp-content/plugins/seriously-simple-podcasting/templates/test.php
		// 3. Relative plugin path: templates/test.php
		// 4. Relative plugin path inside templates folder: test.php
		// And each of them can be with .php extension and without.
		$paths = array(
			SSP_PLUGIN_PATH . 'templates/test',
			SSP_PLUGIN_PATH . 'templates/test.php',
			'wp-content/plugins/seriously-simple-podcasting/templates/test.php',
			'wp-content/plugins/seriously-simple-podcasting/templates/test',
			'templates/test.php',
			'templates/test',
			'test.php',
			'test',
		);

		$template_path = SSP_PLUGIN_PATH . 'templates/test.php';

		file_put_contents( $template_path, '<p>Test Variable: <?php echo $test_var ?></p>' );

		foreach ( $paths as $path ) {
			$test_var = 'Some Value';
			$tmpl     = $this->renderer->fetch( $path, compact( 'test_var' ) );

			$this->assertEquals( $tmpl, '<p>Test Variable: Some Value</p>' );
		}

		unlink( $template_path );
	}
}
