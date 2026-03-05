<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler;

class CPTPodcastHandlerTest extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var CPT_Podcast_Handler
     */
    protected $handler;

    /**
     * @var string|null
     */
    protected $original_token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = ssp_app()->get_service('cpt_podcast_handler');
        $this->original_token = get_option('ss_podcasting_podmotor_account_api_token', null);
    }

    protected function tearDown(): void
    {
        null === $this->original_token
            ? delete_option('ss_podcasting_podmotor_account_api_token')
            : update_option('ss_podcasting_podmotor_account_api_token', $this->original_token);

        parent::tearDown();
    }

    /**
     * @covers \SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler::custom_fields
     */
    public function testPodmotorFieldsPresentWhenConnectedToCastos()
    {
        update_option('ss_podcasting_podmotor_account_api_token', 'test-token');

        $fields = $this->handler->custom_fields();

        $this->assertArrayHasKey('podmotor_file_id', $fields);
        $this->assertArrayHasKey('podmotor_episode_id', $fields);
    }

    /**
     * @covers \SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler::custom_fields
     */
    public function testPodmotorFieldsPresentWhenNotConnectedToCastos()
    {
        update_option('ss_podcasting_podmotor_account_api_token', '');

        $fields = $this->handler->custom_fields();

        $this->assertArrayHasKey('podmotor_file_id', $fields);
        $this->assertArrayHasKey('podmotor_episode_id', $fields);
    }
}
