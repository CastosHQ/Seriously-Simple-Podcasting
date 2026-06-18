<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Rest\Episodes_Rest_Controller;

class EnclosureMetaWriteTest extends \Codeception\TestCase\WPTestCase {

	protected function createEpisode() {
		return $this->factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => SSP_CPT_PODCAST,
		) );
	}

	/**
	 * Save path: the post-types controller writes the enclosure in WP-standard format
	 * using the stored filesize_raw meta.
	 *
	 * @covers \SeriouslySimplePodcasting\Controllers\Podcast_Post_Types_Controller::update_enclosure_meta
	 */
	public function testSavePathWritesFormattedEnclosureFromFilesizeRaw() {
		$episode_id = $this->createEpisode();
		update_post_meta( $episode_id, 'filesize_raw', 54321 );

		$controller = ssp_app()->podcast_post_types_controller;
		$method     = new \ReflectionMethod( $controller, 'update_enclosure_meta' );
		$method->setAccessible( true );
		$method->invoke( $controller, $episode_id, 'https://example.com/episode.mp3' );

		$this->assertSame(
			"https://example.com/episode.mp3\n54321\naudio/mpeg\n",
			get_post_meta( $episode_id, 'enclosure', true )
		);
	}

	/**
	 * Castos sync path: the REST callback writes the enclosure in WP-standard format,
	 * falling back to the stored filesize_raw meta when the payload carries no size.
	 *
	 * @covers \SeriouslySimplePodcasting\Rest\Episodes_Rest_Controller::update_episode
	 */
	public function testCastosSyncWritesFormattedEnclosureWithFilesizeRawFallback() {
		$episode_id = $this->createEpisode();
		update_post_meta( $episode_id, 'filesize_raw', 88888 );

		$controller = new Episodes_Rest_Controller( ssp_episode_repository() );

		$request = new \WP_REST_Request( 'PUT', '/ssp/v1/episodes/' . $episode_id );
		$request->set_url_params( array( 'episode_id' => $episode_id ) );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array(
			'file'    => array(
				'id'  => 123,
				'url' => 'https://episodes.castos.com/show.mp3',
			),
			'episode' => array( 'id' => 456 ),
		) ) );

		$controller->update_episode( $request );

		$this->assertSame(
			"https://episodes.castos.com/show.mp3\n88888\naudio/mpeg\n",
			get_post_meta( $episode_id, 'enclosure', true )
		);
	}

	/**
	 * Castos sync path: size and MIME from the payload take precedence over the fallback.
	 *
	 * @covers \SeriouslySimplePodcasting\Rest\Episodes_Rest_Controller::update_episode
	 */
	public function testCastosSyncPrefersPayloadSizeAndMime() {
		$episode_id = $this->createEpisode();
		update_post_meta( $episode_id, 'filesize_raw', 1 );

		$controller = new Episodes_Rest_Controller( ssp_episode_repository() );

		$request = new \WP_REST_Request( 'PUT', '/ssp/v1/episodes/' . $episode_id );
		$request->set_url_params( array( 'episode_id' => $episode_id ) );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array(
			'file'    => array(
				'id'        => 123,
				'url'       => 'https://episodes.castos.com/show.mp4',
				'size'      => 777777,
				'mime_type' => 'video/mp4',
			),
			'episode' => array( 'id' => 456 ),
		) ) );

		$controller->update_episode( $request );

		$this->assertSame(
			"https://episodes.castos.com/show.mp4\n777777\nvideo/mp4\n",
			get_post_meta( $episode_id, 'enclosure', true )
		);
	}
}
