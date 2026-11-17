<?php
/**
 * Tests for the Recent Episodes template.
 *
 * @package SeriouslySimplePodcasting
 */

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Renderers\Renderer;

/**
 * Checks how the Recent Episodes template prints its settings.
 */
class RecentEpisodesTemplateTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * Template renderer.
	 *
	 * @var Renderer
	 */
	protected $renderer;

	/**
	 * Sets up the renderer.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->renderer = new Renderer();
	}

	/**
	 * Renders the recent episodes template.
	 *
	 * @param array $overrides Template variables to override.
	 *
	 * @return string
	 */
	private function render_template( $overrides = array() ) {
		$vars = array(
			'episodes'             => array(),
			'show_episode_image'   => false,
			'show_episode_title'   => false,
			'show_episode_excerpt' => false,
			'show_read_more'       => false,
			'show_date'            => false,
			'read_more_text'       => 'Listen',
			'date_source'          => 'published',
			'date_format'          => 'F j, Y',
			'episode_image_source' => 'featured_image',
			'columns'              => 3,
		);

		return $this->renderer->fetch( 'episodes/recent-episodes', array_merge( $vars, $overrides ) );
	}

	/**
	 * Creates a published episode post.
	 *
	 * @param string $date Post date.
	 *
	 * @return \WP_Post
	 */
	private function create_episode( $date = '2026-01-05 12:00:00' ) {
		$post_id = $this->factory()->post->create(
			array(
				'post_title'    => 'Recent Episode',
				'post_status'   => 'publish',
				'post_date'     => $date,
				'post_date_gmt' => $date,
			)
		);

		return get_post( $post_id );
	}

	/**
	 * Parses an HTML string into an XPath helper.
	 *
	 * @param string $html HTML markup.
	 *
	 * @return \DOMXPath
	 */
	private function xpath( $html ) {
		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();

		return new \DOMXPath( $dom );
	}

	/**
	 * Escapes every letter so the string is used literally as a date format.
	 *
	 * @param string $text Text to print.
	 *
	 * @return string
	 */
	private function literal_date_format( $text ) {
		return preg_replace( '/([A-Za-z])/', '\\\\$1', $text );
	}

	/**
	 * The columns value is printed as a whole number.
	 */
	public function testColumnsValueIsNumeric() {
		$html = $this->render_template( array( 'columns' => '3 a' ) );

		$this->assertMatchesRegularExpression( '/--ssp-recent-episodes-columns:\s*3\s*\}/', $html );
		$this->assertStringNotContainsString( '3 a', $html );
	}

	/**
	 * The default columns value is printed unchanged.
	 */
	public function testColumnsKeepsDefaultValue() {
		$html = $this->render_template();

		$this->assertMatchesRegularExpression( '/:root\s*\{\s*--ssp-recent-episodes-columns:\s*3\s*\}/', $html );
	}

	/**
	 * The date is printed escaped as text.
	 */
	public function testDateEscapesValue() {
		$episode = $this->create_episode();
		$text    = '"<b>y</b>';
		$html    = $this->render_template(
			array(
				'episodes'    => array( $episode ),
				'show_date'   => true,
				'date_format' => $this->literal_date_format( $text ),
			)
		);

		$this->assertStringNotContainsString( '<b>y</b>', $html );

		$dates = $this->xpath( $html )->query( '//p[contains(@class, "ssp-recent-episode-post__date")]' );
		$this->assertSame( 1, $dates->length );
		$this->assertSame( $text, $dates->item( 0 )->textContent );
		$this->assertSame( 0, $dates->item( 0 )->getElementsByTagName( '*' )->length );
	}

	/**
	 * An ordinary date is printed unchanged.
	 */
	public function testDateKeepsOrdinaryValue() {
		$episode = $this->create_episode( '2026-01-05 12:00:00' );
		$html    = $this->render_template(
			array(
				'episodes'  => array( $episode ),
				'show_date' => true,
			)
		);

		$this->assertStringContainsString( '<p class="ssp-recent-episode-post__date">January 5, 2026</p>', $html );
	}

	/**
	 * The read-more text keeps only HTML allowed in post content.
	 */
	public function testReadMoreTextAllowsOnlyPostHtml() {
		$episode = $this->create_episode();
		$html    = $this->render_template(
			array(
				'episodes'       => array( $episode ),
				'show_read_more' => true,
				'read_more_text' => '<span contenteditable="true">Go</span><input type="text"><span>Listen</span>',
			)
		);

		$this->assertStringNotContainsString( 'contenteditable', $html );
		$this->assertStringNotContainsString( '<input', $html );
		$this->assertStringContainsString( '<span>Go</span><span>Listen</span>', $html );
	}

	/**
	 * Ordinary read-more text is printed unchanged.
	 */
	public function testReadMoreTextKeepsOrdinaryValue() {
		$episode = $this->create_episode();
		$html    = $this->render_template(
			array(
				'episodes'       => array( $episode ),
				'show_read_more' => true,
				'read_more_text' => 'Listen →',
			)
		);

		$links = $this->xpath( $html )->query( '//a[contains(@class, "view-episode")]' );
		$this->assertSame( 1, $links->length );
		$this->assertSame( 'Listen →', $links->item( 0 )->textContent );
		$this->assertStringContainsString( 'class="view-episode">Listen →</a>', $html );
	}
}
