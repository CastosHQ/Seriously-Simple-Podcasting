<?php

namespace Tests\WPUnit;

class UpgradeHandlerTest extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var \SeriouslySimplePodcasting\Handlers\Upgrade_Handler
     */
    protected $upgrade_handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->upgrade_handler = ssp_app()->get_service('upgrade_handler');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers \SeriouslySimplePodcasting\Handlers\Upgrade_Handler::get_updated_enclosure_url
     */
    public function testGetUpdatedEnclosureUrl()
    {
        $variants = [
            'https://seriouslysimplepodcasting.s3.amazonaws.com/One-Sensitive/Intro.m4a'                                   => 'https://episodes.castos.com/One-Sensitive/Intro.m4a',
            'https://s3.amazonaws.com/seriouslysimplepodcasting/spotfight/WWE-SmackDown-Review-ABSTURZ-18.10.19.mp3'       => 'https://episodes.castos.com/spotfight/WWE-SmackDown-Review-ABSTURZ-18.10.19.mp3',
            'https://s3.us-west-001.backblazeb2.com/seriouslysimplepodcasting/thegatheringpodcast/In-suffering-take-2.mp3' => 'https://episodes.castos.com/thegatheringpodcast/In-suffering-take-2.mp3',
            'https://episodes.seriouslysimplepodcasting.com/djreecepodcast/9PMCheckIn5-22-2017.mp3'                        => 'https://episodes.castos.com/djreecepodcast/9PMCheckIn5-22-2017.mp3',
            'https://episodes.castos.com/djreecepodcast/9PMCheckIn5-22-2017.mp3'                                           => 'https://episodes.castos.com/djreecepodcast/9PMCheckIn5-22-2017.mp3',
        ];

        foreach ($variants as $url => $expected) {
            $updated = $this->upgrade_handler->get_updated_enclosure_url($url);
            $this->assertEquals($expected, $updated);
        }
    }

    /**
     * @covers \SeriouslySimplePodcasting\Handlers\Upgrade_Handler::format_enclosures
     */
    public function testFormatEnclosuresBackfillsLegacyBareUrlMeta()
    {
        $episode_id = $this->factory()->post->create([
            'post_status' => 'publish',
            'post_type'   => SSP_CPT_PODCAST,
        ]);

        // Legacy state: audio_file holds the URL, enclosure is a bare URL, size is known.
        update_post_meta($episode_id, 'audio_file', 'https://episodes.castos.com/show.mp3');
        update_post_meta($episode_id, 'enclosure', 'https://episodes.castos.com/show.mp3');
        update_post_meta($episode_id, 'filesize_raw', 24680);

        $this->upgrade_handler->format_enclosures();

        $this->assertEquals(
            "https://episodes.castos.com/show.mp3\n24680\naudio/mpeg\n",
            get_post_meta($episode_id, 'enclosure', true)
        );
    }
}
