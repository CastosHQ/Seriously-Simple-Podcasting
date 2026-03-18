<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Cron_Controller;
use SeriouslySimplePodcasting\Entities\Castos_Response_Episode;
use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Upgrade_Handler;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;

class CronControllerTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test that upload_scheduled_episodes saves podmotor_episode_id on successful sync.
	 */
	public function testUploadScheduledEpisodesSavesPodmotorEpisodeId() {
		$post_id = $this->factory()->post->create( array( 'post_type' => 'podcast' ) );
		$episode = get_post( $post_id );

		$castos_episode_id = 4821;

		$response                   = new Castos_Response_Episode();
		$response->success          = true;
		$response->code             = 200;
		$response->castos_episode_id = $castos_episode_id;

		$castos_handler = $this->createMock( Castos_Handler::class );
		$castos_handler->method( 'upload_episode_to_castos' )
			->with( $episode )
			->willReturn( $response );

		$episode_repository = $this->createMock( Episode_Repository::class );
		$episode_repository->method( 'get_scheduled_episodes' )
			->willReturn( array( $episode ) );
		$episode_repository->expects( $this->once() )
			->method( 'update_episode_sync_status' )
			->with( $post_id, Sync_Status::SYNC_STATUS_SYNCED );

		$controller = $this->make_controller( $castos_handler, $episode_repository );

		$uploaded = $controller->upload_scheduled_episodes();

		$this->assertSame( 1, $uploaded );
		$this->assertEquals(
			$castos_episode_id,
			get_post_meta( $post_id, 'podmotor_episode_id', true ),
			'Cron retry must save podmotor_episode_id from the API response'
		);
	}

	/**
	 * Creates a Cron_Controller with the given mocks.
	 *
	 * @param Castos_Handler     $castos_handler     Castos handler mock.
	 * @param Episode_Repository $episode_repository Episode repository mock.
	 *
	 * @return Cron_Controller
	 */
	private function make_controller( $castos_handler, $episode_repository ) {
		$upgrade_handler = $this->createMock( Upgrade_Handler::class );

		return new Cron_Controller( $castos_handler, $episode_repository, $upgrade_handler );
	}
}
