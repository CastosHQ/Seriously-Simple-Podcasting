<?php

namespace Tests\WPUnit;

require_once __DIR__ . '/_mp3-fixtures.php';

/**
 * The ssp/v1/file-duration route, which is how both episode editors learn a file's
 * runtime when one is selected from the Media Library.
 *
 * WordPress stores getID3's value in the attachment metadata, and getID3 halves it for
 * some mono MP3s — so the editors must ask SSP rather than read that value.
 */
class RestFileDurationTest extends \Codeception\TestCase\WPTestCase {

	use Mp3_Fixtures;

	const ROUTE = '/ssp/v1/file-duration';

	protected function setUp(): void {
		parent::setUp();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	protected function tearDown(): void {
		$this->remove_fixtures();
		parent::tearDown();
	}

	/**
	 * Uploads a fixture into the Media Library so WordPress generates its own metadata.
	 *
	 * @param string $path Fixture path.
	 *
	 * @return int Attachment ID.
	 */
	protected function upload_fixture( $path ) {
		$attachment_id = $this->factory()->attachment->create_upload_object( $path );

		$this->fixtures[] = get_attached_file( $attachment_id );

		return $attachment_id;
	}

	/**
	 * Dispatches the route and returns the response.
	 *
	 * @param array $params Query parameters.
	 *
	 * @return \WP_REST_Response
	 */
	protected function request( $params = array() ) {
		$request = new \WP_REST_Request( 'GET', self::ROUTE );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_do_request( $request );
	}

	/**
	 * The point of the route: the editor gets SSP's corrected runtime, not the halved
	 * one WordPress stored when it generated the attachment metadata.
	 */
	public function testReturnsCorrectedDurationForAffectedMonoMp3() {
		// 460 frames * 1152 samples / 44100 Hz = 12.02s, which getID3 reports as 0:06.
		$attachment_id = $this->upload_fixture( $this->build_mp3( array( 'frames' => 460 ) ) );

		$metadata = wp_get_attachment_metadata( $attachment_id );
		$this->assertSame( '0:06', $metadata['length_formatted'], 'WordPress should have stored the halved value' );

		$response = $this->request( array( 'attachment_id' => $attachment_id ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '0:12', $response->get_data()['duration'] );
	}

	/**
	 * Durations must stay unpadded — ssp-issues#201 removed the leading "00:", and the
	 * editors used to re-pad this value client-side. An hour-plus file is where the
	 * padding would show, as "01:00:00" instead of "1:00:00".
	 */
	public function testDurationIsNotZeroPadded() {
		// An hour of audio would be a 57 MB fixture, so advertise the frame count
		// instead of writing it: 137813 * 1152 / 44100 = 3600.02s.
		$attachment_id = $this->upload_fixture(
			$this->build_mp3(
				array(
					'frames'            => 40,
					'advertised_frames' => 137813,
				)
			)
		);

		$this->assertSame( '1:00:00', $this->request( array( 'attachment_id' => $attachment_id ) )->get_data()['duration'] );
	}

	/**
	 * Files getID3 reads correctly must come back exactly as WordPress has them.
	 */
	public function testStereoDurationMatchesWordPress() {
		$attachment_id = $this->upload_fixture(
			$this->build_mp3(
				array(
					'channels' => 2,
					'frames'   => 460,
				)
			)
		);

		$metadata = wp_get_attachment_metadata( $attachment_id );
		$response = $this->request( array( 'attachment_id' => $attachment_id ) );

		$this->assertNotEmpty( $metadata['length_formatted'], 'the comparison is meaningless without a stored value' );
		$this->assertSame( $metadata['length_formatted'], $response->get_data()['duration'] );
	}

	/**
	 * Episodes can be video, and the classic editor's media frame does not restrict the
	 * selection to audio — so a video attachment must answer rather than be rejected.
	 */
	public function testVideoAttachmentIsAccepted() {
		$attachment_id = $this->upload_fixture( $this->build_mp3( array( 'frames' => 460 ) ) );

		// The mime gate is what is under test; the bytes stay a readable MPEG stream.
		wp_update_post(
			array(
				'ID'             => $attachment_id,
				'post_mime_type' => 'video/mp4',
			)
		);

		$response = $this->request( array( 'attachment_id' => $attachment_id ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '0:12', $response->get_data()['duration'] );
	}

	/**
	 * upload_files alone must be enough. SSP's own Podcast Editor role carries it along
	 * with the podcast post type caps but not manage_podcast, so gating on the settings
	 * capability would lock out the people who create episodes.
	 */
	public function testUploadFilesIsEnoughWithoutManagePodcast() {
		$attachment_id = $this->upload_fixture( $this->build_mp3( array( 'frames' => 460 ) ) );

		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		$user    = new \WP_User( $user_id );
		$user->add_cap( 'upload_files' );
		wp_set_current_user( $user_id );

		$this->assertFalse( current_user_can( 'manage_podcast' ), 'the role must not carry the settings capability' );
		$this->assertSame( 200, $this->request( array( 'attachment_id' => $attachment_id ) )->get_status() );
	}

	public function testRejectsUserWhoCannotUploadFiles() {
		$attachment_id = $this->upload_fixture( $this->build_mp3( array( 'frames' => 100 ) ) );

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertSame( 403, $this->request( array( 'attachment_id' => $attachment_id ) )->get_status() );
	}

	public function testRejectsLoggedOutRequest() {
		$attachment_id = $this->upload_fixture( $this->build_mp3( array( 'frames' => 100 ) ) );

		wp_set_current_user( 0 );

		$this->assertSame( 401, $this->request( array( 'attachment_id' => $attachment_id ) )->get_status() );
	}

	public function testRejectsMissingAttachment() {
		$this->assertSame( 404, $this->request( array( 'attachment_id' => 999999 ) )->get_status() );
	}

	/**
	 * An arbitrary post ID must not be treated as a file to read.
	 */
	public function testRejectsPostThatIsNotAnAttachment() {
		$post_id = $this->factory()->post->create( array( 'post_type' => SSP_CPT_PODCAST ) );

		$this->assertSame( 404, $this->request( array( 'attachment_id' => $post_id ) )->get_status() );
	}

	public function testRejectsNonAudioAttachment() {
		$attachment_id = $this->upload_fixture( $this->write_fixture( 'not audio at all', 'txt' ) );

		$this->assertSame( 400, $this->request( array( 'attachment_id' => $attachment_id ) )->get_status() );
	}

	public function testRequiresAnAttachmentId() {
		$this->assertSame( 400, $this->request()->get_status() );
	}

	/**
	 * absint() turns a malformed ID into 0, and get_post( 0 ) returns the global post —
	 * which must not be mistaken for the requested attachment.
	 */
	public function testRejectsMalformedAttachmentId() {
		$GLOBALS['post'] = get_post( $this->upload_fixture( $this->build_mp3( array( 'frames' => 100 ) ) ) );

		$response = $this->request( array( 'attachment_id' => 'not-an-id' ) );
		unset( $GLOBALS['post'] );

		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * A file that cannot be read locally — offloaded media, or a URL the path mapper
	 * cannot resolve — falls back to what WordPress recorded at upload time. The save
	 * path calls the same calculation on the same URL, so it cannot recover this either;
	 * a possibly-halved duration beats a permanently blank one.
	 */
	public function testUnreadableFileFallsBackToStoredMetadata() {
		$attachment_id = $this->upload_fixture( $this->build_mp3( array( 'frames' => 460 ) ) );

		$stored = wp_get_attachment_metadata( $attachment_id )['length_formatted'];
		unlink( get_attached_file( $attachment_id ) );

		$response = $this->request( array( 'attachment_id' => $attachment_id ) );

		$this->assertSame( '0:06', $stored, 'WordPress should have stored the halved value' );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $stored, $response->get_data()['duration'] );
	}

	/**
	 * With neither a readable file nor stored metadata there is nothing to prefill, and
	 * that is not an error — the editors leave the field blank.
	 */
	public function testAttachmentWithoutDurationYieldsEmptyString() {
		$attachment_id = $this->upload_fixture( $this->build_mp3( array( 'frames' => 100 ) ) );

		unlink( get_attached_file( $attachment_id ) );
		wp_update_attachment_metadata( $attachment_id, array() );

		$response = $this->request( array( 'attachment_id' => $attachment_id ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '', $response->get_data()['duration'] );
	}
}
  