<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\Podcast_Post_Types_Controller;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Handlers\Podping_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;

class PodcastPostTypesControllerTest extends \Codeception\TestCase\WPTestCase
{
    /**
     * Controller instance.
     *
     * @var Podcast_Post_Types_Controller
     */
    private $controller;

    /**
     * Mock episode repository.
     *
     * @var Episode_Repository
     */
    private $mock_episode_repository;

    /**
     * Test post ID.
     *
     * @var int
     */
    private $post_id;

    /**
     * Original Castos connection token.
     *
     * @var mixed
     */
    private $original_castos_token;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->original_castos_token = get_option('ss_podcasting_podmotor_account_api_token', null);

        // Create a test post
        $this->post_id = $this->factory()->post->create([
            'post_type' => 'podcast',
            'post_title' => 'Test Episode',
            'post_date' => '2024-01-01 12:00:00',
        ]);

        // Create mocks for all required dependencies
        $mock_cpt_podcast_handler = $this->createMock(CPT_Podcast_Handler::class);
        $mock_castos_handler = $this->createMock(Castos_Handler::class);
        $mock_admin_notices_handler = $this->createMock(Admin_Notifications_Handler::class);
        $mock_podping_handler = $this->createMock(Podping_Handler::class);
        $this->mock_episode_repository = $this->createMock(Episode_Repository::class);
        $mock_series_handler = $this->createMock(Series_Handler::class);

        // Create controller instance with all required dependencies
        $this->controller = new Podcast_Post_Types_Controller(
            $mock_cpt_podcast_handler,
            $mock_castos_handler,
            $mock_admin_notices_handler,
            $mock_podping_handler,
            $this->mock_episode_repository,
            $mock_series_handler
        );
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        if (null === $this->original_castos_token) {
            delete_option('ss_podcasting_podmotor_account_api_token');
        } else {
            update_option('ss_podcasting_podmotor_account_api_token', $this->original_castos_token);
        }

        parent::tearDown();
    }

    /**
     * Test handle_enclosure_update with new enclosure.
     */
    public function testHandleEnclosureUpdateWithNewEnclosure()
    {
        $post = get_post($this->post_id);
        $new_enclosure = 'https://example.com/new-audio.mp3';
        $old_enclosure = 'https://example.com/old-audio.mp3';

        // Set up existing audio_file meta
        update_post_meta($this->post_id, 'audio_file', $old_enclosure);

        // Mock repository methods
        $this->mock_episode_repository
            ->expects($this->once())
            ->method('get_file_duration')
            ->with($new_enclosure)
            ->willReturn('00:05:30');

        $this->mock_episode_repository
            ->expects($this->once())
            ->method('get_file_size')
            ->with($new_enclosure)
            ->willReturn([
                'formatted' => '5.2 MB',
                'raw' => 5452595,
            ]);

        // Mock Castos connection check
        $this->mock_function('ssp_is_connected_to_castos', false);

        // Call the method
        $this->controller->handle_enclosure_update($post, $new_enclosure);

        // Assertions
        $this->assertEquals($new_enclosure, get_post_meta($this->post_id, 'enclosure', true));
        $this->assertEquals('2024-01-01 12:00:00', get_post_meta($this->post_id, 'date_recorded', true));
        $this->assertEquals('00:05:30', get_post_meta($this->post_id, 'duration', true));
        $this->assertEquals('5.2 MB', get_post_meta($this->post_id, 'filesize', true));
        $this->assertEquals(5452595, get_post_meta($this->post_id, 'filesize_raw', true));
    }

    /**
     * Test handle_enclosure_update with same enclosure (no change).
     */
    public function testHandleEnclosureUpdateWithSameEnclosure()
    {
        $post = get_post($this->post_id);
        $enclosure = 'https://example.com/audio.mp3';

        // Set up existing audio_file meta with same value
        update_post_meta($this->post_id, 'audio_file', $enclosure);
        update_post_meta($this->post_id, 'date_recorded', '2023-12-01 10:00:00');

        // Mock Castos connection check
        $this->mock_function('ssp_is_connected_to_castos', false);

        // Call the method
        $this->controller->handle_enclosure_update($post, $enclosure);

        // Assertions
        $this->assertEquals($enclosure, get_post_meta($this->post_id, 'enclosure', true));
        // date_recorded should not change since enclosure didn't change
        $this->assertEquals('2023-12-01 10:00:00', get_post_meta($this->post_id, 'date_recorded', true));
    }

    /**
     * Test handle_enclosure_update when connected to Castos.
     */
    public function testHandleEnclosureUpdateWhenConnectedToCastos()
    {
        $post = get_post($this->post_id);
        $new_enclosure = 'https://example.com/new-audio.mp3';

        // Mock Castos connection check to return true
        $this->mock_function('ssp_is_connected_to_castos', true);

        // Repository methods should not be called when connected to Castos
        $this->mock_episode_repository
            ->expects($this->never())
            ->method('get_file_duration');

        $this->mock_episode_repository
            ->expects($this->never())
            ->method('get_file_size');

        // Call the method
        $this->controller->handle_enclosure_update($post, $new_enclosure);

        // Assertions
        $this->assertEquals($new_enclosure, get_post_meta($this->post_id, 'enclosure', true));
        $this->assertEquals('2024-01-01 12:00:00', get_post_meta($this->post_id, 'date_recorded', true));
        // File metadata should not be updated when connected to Castos
        $this->assertEmpty(get_post_meta($this->post_id, 'duration', true));
        $this->assertEmpty(get_post_meta($this->post_id, 'filesize', true));
    }

    /**
     * Test handle_enclosure_update with empty date_recorded.
     */
    public function testHandleEnclosureUpdateWithEmptyDateRecorded()
    {
        $post = get_post($this->post_id);
        $enclosure = 'https://example.com/audio.mp3';

        // Set up existing audio_file meta with same value
        update_post_meta($this->post_id, 'audio_file', $enclosure);
        // Don't set date_recorded (it should be empty)

        // Mock Castos connection check
        $this->mock_function('ssp_is_connected_to_castos', false);

        // Call the method
        $this->controller->handle_enclosure_update($post, $enclosure);

        // date_recorded should be updated even if enclosure didn't change
        $this->assertEquals('2024-01-01 12:00:00', get_post_meta($this->post_id, 'date_recorded', true));
    }

    /**
     * Test handle_enclosure_update with failed file size.
     */
    public function testHandleEnclosureUpdateWithFailedFilesize()
    {
        $post = get_post($this->post_id);
        $new_enclosure = 'https://example.com/new-audio.mp3';

        // Mock repository to return false for filesize
        $this->mock_episode_repository
            ->expects($this->once())
            ->method('get_file_duration')
            ->with($new_enclosure)
            ->willReturn('00:05:30');

        $this->mock_episode_repository
            ->expects($this->once())
            ->method('get_file_size')
            ->with($new_enclosure)
            ->willReturn(false);

        // Mock Castos connection check
        $this->mock_function('ssp_is_connected_to_castos', false);

        // Call the method
        $this->controller->handle_enclosure_update($post, $new_enclosure);

        // Assertions
        $this->assertEquals($new_enclosure, get_post_meta($this->post_id, 'enclosure', true));
        // Duration should still be updated
        $this->assertEquals('00:05:30', get_post_meta($this->post_id, 'duration', true));
        // Filesize should not be updated if repository returns false
        $this->assertEmpty(get_post_meta($this->post_id, 'filesize', true));
        $this->assertEmpty(get_post_meta($this->post_id, 'filesize_raw', true));
    }

    /**
     * Test handle_enclosure_update with partial filesize data.
     */
    public function testHandleEnclosureUpdateWithPartialFilesizeData()
    {
        $post = get_post($this->post_id);
        $new_enclosure = 'https://example.com/new-audio.mp3';

        // Mock repository to return partial filesize data
        $this->mock_episode_repository
            ->expects($this->once())
            ->method('get_file_duration')
            ->with($new_enclosure)
            ->willReturn('00:05:30');

        $this->mock_episode_repository
            ->expects($this->once())
            ->method('get_file_size')
            ->with($new_enclosure)
            ->willReturn([
                'formatted' => '5.2 MB',
                // 'raw' key missing
            ]);

        // Mock Castos connection check
        $this->mock_function('ssp_is_connected_to_castos', false);

        // Call the method
        $this->controller->handle_enclosure_update($post, $new_enclosure);

        // Assertions
        $this->assertEquals($new_enclosure, get_post_meta($this->post_id, 'enclosure', true));
        $this->assertEquals('00:05:30', get_post_meta($this->post_id, 'duration', true));
        $this->assertEquals('5.2 MB', get_post_meta($this->post_id, 'filesize', true));
        // filesize_raw should not be set if not provided
        $this->assertEmpty(get_post_meta($this->post_id, 'filesize_raw', true));
    }

    /**
     * Test handle_enclosure_update with existing duration and BOTH filesize fields.
     */
    public function testHandleEnclosureUpdateWithExistingMetadata()
    {
        $post = get_post($this->post_id);
        $enclosure = 'https://example.com/audio.mp3';

        // Set up existing metadata - BOTH filesize and filesize_raw must exist
        update_post_meta($this->post_id, 'audio_file', $enclosure);
        update_post_meta($this->post_id, 'duration', '00:10:00');
        update_post_meta($this->post_id, 'filesize', '10M');
        update_post_meta($this->post_id, 'filesize_raw', '10485760');

        // Mock Castos connection check
        $this->mock_function('ssp_is_connected_to_castos', false);

        // Repository methods should not be called since metadata already exists
        $this->mock_episode_repository
            ->expects($this->never())
            ->method('get_file_duration');

        $this->mock_episode_repository
            ->expects($this->never())
            ->method('get_file_size');

        // Call the method
        $this->controller->handle_enclosure_update($post, $enclosure);

        // Assertions - existing metadata should remain unchanged
        $this->assertEquals('00:10:00', get_post_meta($this->post_id, 'duration', true));
        $this->assertEquals('10M', get_post_meta($this->post_id, 'filesize', true));
        $this->assertEquals('10485760', get_post_meta($this->post_id, 'filesize_raw', true));
    }

    /**
     * Test handle_enclosure_update recalculates when only filesize exists.
     * Ensures we don't mix frontend data (filesize) with backend data (filesize_raw).
     */
    public function testHandleEnclosureUpdateRecalculatesWhenOnlyFilesizeExists()
    {
        $post = get_post($this->post_id);
        $enclosure = 'https://example.com/audio.mp3';

        // Set up existing metadata - only filesize, no filesize_raw
        update_post_meta($this->post_id, 'audio_file', $enclosure);
        update_post_meta($this->post_id, 'filesize', '10M');
        // filesize_raw is missing

        // Mock Castos connection check
        $this->mock_function('ssp_is_connected_to_castos', false);

        // Repository methods SHOULD be called to recalculate both fields
        $this->mock_episode_repository
            ->expects($this->once())
            ->method('get_file_size')
            ->with($enclosure)
            ->willReturn([
                'formatted' => '10M',
                'raw' => 10485760,
            ]);

        // Call the method
        $this->controller->handle_enclosure_update($post, $enclosure);

        // Assertions - both fields should be updated from backend
        $this->assertEquals('10M', get_post_meta($this->post_id, 'filesize', true));
        $this->assertEquals(10485760, get_post_meta($this->post_id, 'filesize_raw', true));
    }

    /**
     * Test handle_enclosure_update recalculates when only filesize_raw exists.
     * Ensures data consistency between both fields.
     */
    public function testHandleEnclosureUpdateRecalculatesWhenOnlyFilesizeRawExists()
    {
        $post = get_post($this->post_id);
        $enclosure = 'https://example.com/audio.mp3';

        // Set up existing metadata - only filesize_raw, no filesize
        update_post_meta($this->post_id, 'audio_file', $enclosure);
        update_post_meta($this->post_id, 'filesize_raw', '10485760');
        // filesize is missing

        // Mock Castos connection check
        $this->mock_function('ssp_is_connected_to_castos', false);

        // Repository methods SHOULD be called to recalculate both fields
        $this->mock_episode_repository
            ->expects($this->once())
            ->method('get_file_size')
            ->with($enclosure)
            ->willReturn([
                'formatted' => '10M',
                'raw' => 10485760,
            ]);

        // Call the method
        $this->controller->handle_enclosure_update($post, $enclosure);

        // Assertions - both fields should be updated from backend
        $this->assertEquals('10M', get_post_meta($this->post_id, 'filesize', true));
        $this->assertEquals(10485760, get_post_meta($this->post_id, 'filesize_raw', true));
    }

    /**
     * Test handle_enclosure_update recalculates when neither field exists.
     */
    public function testHandleEnclosureUpdateRecalculatesWhenBothFieldsMissing()
    {
        $post = get_post($this->post_id);
        $enclosure = 'https://example.com/audio.mp3';

        // Set up existing metadata - neither filesize nor filesize_raw
        update_post_meta($this->post_id, 'audio_file', $enclosure);
        // Both fields are missing

        // Mock Castos connection check
        $this->mock_function('ssp_is_connected_to_castos', false);

        // Repository methods SHOULD be called to calculate both fields
        $this->mock_episode_repository
            ->expects($this->once())
            ->method('get_file_size')
            ->with($enclosure)
            ->willReturn([
                'formatted' => '10M',
                'raw' => 10485760,
            ]);

        // Call the method
        $this->controller->handle_enclosure_update($post, $enclosure);

        // Assertions - both fields should be set from backend
        $this->assertEquals('10M', get_post_meta($this->post_id, 'filesize', true));
        $this->assertEquals(10485760, get_post_meta($this->post_id, 'filesize_raw', true));
    }

    /**
     * Helper method to mock functions.
     *
     * @param string $function_name Function name to mock.
     * @param mixed  $return_value Return value for the function.
     */
    private function mock_function($function_name, $return_value)
    {
        if ('ssp_is_connected_to_castos' === $function_name) {
            update_option('ss_podcasting_podmotor_account_api_token', $return_value ? 'test-token' : '');
            return;
        }

        if (!function_exists($function_name)) {
            // Create a mock function if it doesn't exist
            eval("function {$function_name}() { return " . var_export($return_value, true) . "; }");
        }
    }
}

