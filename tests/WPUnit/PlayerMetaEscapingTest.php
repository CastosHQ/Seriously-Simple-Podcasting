<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Players_Controller;
use SeriouslySimplePodcasting\Handlers\Options_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;

/**
 * Tests escaping of values in player meta link attributes.
 *
 * @package SeriouslySimplePodcasting\Tests
 * @since 3.17.1
 */
class PlayerMetaEscapingTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * Episode titles containing quotes and markup remain within both player meta title attributes.
	 *
	 * @since 3.17.1
	 */
	public function testEpisodeTitleIsEscapedInPlayerMetaLinkAttributes() {
		$title      = '<code>" data-note="episode</code>';
		$episode_id = $this->create_episode( $title );

		$this->assertSame( $title, get_the_title( $episode_id ) );

		$output   = $this->render_player_meta( $episode_id );
		$expected = $title . ' ';

		foreach ( array( 'podcast-meta-download', 'podcast-meta-new-window' ) as $class ) {
			$link = $this->get_player_meta_link( $output, $class );

			$this->assertSame( $expected, $link->getAttribute( 'title' ) );
			$this->assertFalse( $link->hasAttribute( 'data-note' ) );
		}
	}

	/**
	 * An ordinary episode title must be escaped once in both player meta link attributes.
	 *
	 * @since 3.17.1
	 */
	public function testOrdinaryEpisodeTitleIsEscapedOnceInPlayerMetaLinks() {
		$title      = "Jane's Rock & Roll";
		$episode_id = $this->create_episode( $title );
		$output    = $this->render_player_meta( $episode_id );
		$displayed = get_the_title( $episode_id );
		$expected  = "Jane’s Rock & Roll ";

		$this->assertStringContainsString( 'title="' . esc_attr( $displayed ) . ' "', $output );

		foreach ( array( 'podcast-meta-download', 'podcast-meta-new-window' ) as $class ) {
			$link = $this->get_player_meta_link( $output, $class );

			$this->assertSame( $expected, $link->getAttribute( 'title' ) );
		}
	}

	/**
	 * Subscribe labels containing quotes and markup remain text in the title attribute and link.
	 *
	 * @since 3.17.1
	 */
	public function testSubscribeLabelIsEscapedInAttributeAndLinkText() {
		$label      = '<code>" data-note="subscribe</code>';
		$episode_id = $this->create_episode( 'Subscribe test episode' );
		$filter     = static function () use ( $label ) {
			return array(
				'_url' => array(
					'url'   => 'https://example.com/subscribe',
					'label' => $label,
				),
			);
		};
		$enabled    = static function () {
			return 'on';
		};

		add_filter( 'ssp_episode_subscribe_details', $filter, 10, 3 );
		add_filter( 'pre_option_ss_podcasting_player_subscribe_urls_enabled', $enabled );

		try {
			$output = $this->render_player_meta( $episode_id );
		} finally {
			remove_filter( 'ssp_episode_subscribe_details', $filter, 10 );
			remove_filter( 'pre_option_ss_podcasting_player_subscribe_urls_enabled', $enabled, 10 );
		}

		$link = $this->get_player_meta_link( $output, 'podcast-meta-itunes' );

		$this->assertSame( $label, $link->getAttribute( 'title' ) );
		$this->assertSame( $label, $link->textContent );
		$this->assertFalse( $link->hasAttribute( 'data-note' ) );
		$this->assertCount( 0, $link->getElementsByTagName( 'code' ) );
	}

	/**
	 * Creates a published episode with an audio file.
	 *
	 * @param string $title Episode title.
	 *
	 * @return int Episode ID.
	 * @since 3.17.1
	 */
	protected function create_episode( $title ) {
		$episode_id = $this->factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_type'   => SSP_CPT_PODCAST,
			)
		);

		update_post_meta( $episode_id, 'audio_file', 'https://example.com/episode.mp3' );
		update_post_meta( $episode_id, 'filesize', '1 MB' );

		return $episode_id;
	}

	/**
	 * Renders player meta for an episode through the players controller.
	 *
	 * @param int $episode_id Episode ID.
	 *
	 * @return string Rendered player meta.
	 * @since 3.17.1
	 */
	protected function render_player_meta( $episode_id ) {
		$enabled = static function () {
			return 'on';
		};

		add_filter( 'pre_option_ss_podcasting_download_file_enabled', $enabled );
		add_filter( 'pre_option_ss_podcasting_play_in_new_window_enabled', $enabled );

		$had_global_post = array_key_exists( 'post', $GLOBALS );
		$previous_post   = $had_global_post ? $GLOBALS['post'] : null;
		$post            = get_post( $episode_id );
		$GLOBALS['post'] = $post;
		setup_postdata( $post );

		try {
			$controller = new Players_Controller(
				new Renderer(),
				new Options_Handler(),
				ssp_get_service( 'episode_repository' )
			);

			return $controller->episode_meta_details( $episode_id );
		} finally {
			wp_reset_postdata();
			if ( $had_global_post ) {
				$GLOBALS['post'] = $previous_post;
			} else {
				unset( $GLOBALS['post'] );
			}
			remove_filter( 'pre_option_ss_podcasting_download_file_enabled', $enabled, 10 );
			remove_filter( 'pre_option_ss_podcasting_play_in_new_window_enabled', $enabled, 10 );
		}
	}

	/**
	 * Finds a player meta link by its CSS class.
	 *
	 * @param string $html  Rendered player meta.
	 * @param string $class Link CSS class.
	 *
	 * @return \DOMElement Player meta link.
	 * @since 3.17.1
	 */
	protected function get_player_meta_link( $html, $class ) {
		$document            = new \DOMDocument();
		$previous_libxml_mode = libxml_use_internal_errors( true );
		try {
			$document->loadHTML( '<!DOCTYPE html><html><body>' . $html . '</body></html>' );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous_libxml_mode );
		}

		foreach ( $document->getElementsByTagName( 'a' ) as $link ) {
			$classes = preg_split( '/\s+/', trim( $link->getAttribute( 'class' ) ) );
			if ( in_array( $class, $classes, true ) ) {
				return $link;
			}
		}

		$this->fail( sprintf( 'Could not find player meta link with class "%s".', $class ) );
	}
}
