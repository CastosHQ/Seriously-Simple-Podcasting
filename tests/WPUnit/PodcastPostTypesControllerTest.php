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

        // Mirror the real Episode_Repository::format_enclosure() so the controller stores a
        // WP-standard "url\nsize\nmime\n" value (the mocked repository would otherwise return null).
        $this->mock_episode_repository
            ->method('format_enclosure')
            ->willReturnCallback(function ($url, $size = 0, $mime = '') {
                return $url ? $url . "\n" . intval($size) . "\n" . ($mime ?: 'audio/mpeg') . "\n" : '';
            });

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
        // Enclosure is refreshed after filesize_raw is calculated, so it carries the real size.
        $this->assertEquals("$new_enclosure\n5452595\naudio/mpeg\n", get_post_meta($this->post_id, 'enclosure', true));
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
        // No filesize_raw available, so size defaults to 0 in the formatted value.
        $this->assertEquals("$enclosure\n0\naudio/mpeg\n", get_post_meta($this->post_id, 'enclosure', true));
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
        // Castos path writes the enclosure before the early return; size unknown here, so 0.
        $this->assertEquals("$new_enclosure\n0\naudio/mpeg\n", get_post_meta($this->post_id, 'enclosure', true));
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
        // get_file_size returns false, so no filesize_raw and size stays 0 in the formatted value.
        $this->assertEquals("$new_enclosure\n0\naudio/mpeg\n", get_post_meta($this->post_id, 'enclosure', true));
        // Duration should still be updated
        $this->assertEquals('00:05:30', get_post_meta($this->post_id, 'duration', true));
        // Filesize should not be updated if repository returns false
        $this->assertEmpty(get_post_meta($this->post_id, 'filesize', true));
        $this->assertEmpty(get_post_meta($this->post_id, 'filesize_raw', true));
    }

    /**
     * Test that a changed URL does not inherit the previous file's size in the enclosure
     * when the new file's size cannot be fetched (would otherwise serialize a stale size).
     */
    public function testHandleEnclosureUpdateResetsStaleSizeOnUrlChangeWhenFilesizeFails()
    {
        $post = get_post($this->post_id);
        $old_enclosure = 'https://example.com/old-audio.mp3';
        $new_enclosure = 'https://example.com/new-audio.mp3';

        // Existing state: old URL with a known (now stale) size.
        update_post_meta($this->post_id, 'audio_file', $old_enclosure);
        update_post_meta($this->post_id, 'filesize_raw', 9999999);

        // New file's size lookup fails.
        $this->mock_episode_repository
            ->expects($this->once())
            ->method('get_file_size')
            ->with($new_enclosure)
            ->willReturn(false);

        $this->mock_function('ssp_is_connected_to_castos', false);

        $this->controller->handle_enclosure_update($post, $new_enclosure);

        // Enclosure must pair the new URL with size 0, not the old file's 9999999.
        $this->assertEquals("$new_enclosure\n0\naudio/mpeg\n", get_post_meta($this->post_id, 'enclosure', true));
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
        // filesize_raw not provided (no 'raw' key), so size stays 0 in the formatted value.
        $this->assertEquals("$new_enclosure\n0\naudio/mpeg\n", get_post_meta($this->post_id, 'enclosure', true));
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
     * A meta-box save preserves the authoritative GUID, even when legacy keys differ.
     */
    public function testMetaBoxSaveKeepsExistingEpisodeGuid()
    {
        update_post_meta($this->post_id, 'ssp_episode_guid', 'kept-guid');
        update_post_meta($this->post_id, 'ssp_original_guid', 'old-original');
        update_post_meta($this->post_id, 'ssp_guid', 'old-native');
        $this->saveEpisodeMetaBox();
        $this->assertSame('kept-guid', get_post_meta($this->post_id, 'ssp_episode_guid', true));
        $this->assertSame('old-native', get_post_meta($this->post_id, 'ssp_guid', true));
    }

    /**
     * Legacy saves store the published priority with exact original GUID bytes.
     */
    public function testMetaBoxSaveStoresLegacyGuid()
    {
        $guids = [
            'backslash'    => 'original\\guid',
            'single quote' => "original'guid",
            'double quote' => 'original"guid',
            'hex hash'     => 'd41d8cd98f00b204e9800998ecf8427e',
            'digits only'  => '01234567890123456789',
        ];
        foreach ($guids as $case => $original) {
            $id = $this->factory()->post->create(['post_type' => SSP_CPT_PODCAST]);
            update_post_meta($id, 'ssp_original_guid', wp_slash($original));
            update_post_meta($id, 'ssp_guid', 'stray-guid');
            $this->saveEpisodeMetaBox($id);
            $this->assertSame($original, get_post_meta($id, 'ssp_episode_guid', true), $case . ' stored bytes');
            $this->assertSame('stray-guid', get_post_meta($id, 'ssp_guid', true), $case);
        }

        $native = $this->factory()->post->create(['post_type' => SSP_CPT_PODCAST]);
        update_post_meta($native, 'ssp_guid', 'native-legacy');
        $this->saveEpisodeMetaBox($native);
        $this->assertSame('native-legacy', get_post_meta($native, 'ssp_episode_guid', true));

        $imported = $this->factory()->post->create(['post_type' => SSP_CPT_PODCAST]);
        update_post_meta($imported, 'ssp_original_guid', 'original-only');
        $this->saveEpisodeMetaBox($imported);
        $this->assertSame('original-only', get_post_meta($imported, 'ssp_episode_guid', true));
        $this->assertFalse(metadata_exists('post', $imported, 'ssp_guid'));
    }

    /**
     * The save path stores the original, not a filtered presentation value.
     */
    public function testMetaBoxSaveStoresUnfilteredGuid()
    {
        update_post_meta($this->post_id, 'ssp_original_guid', 'raw-guid');
        $filter = function ($guid) { return 'filtered-' . $guid; };
        add_filter('ssp/episode/guid', $filter);
        try {
            $this->saveEpisodeMetaBox();
            $this->assertSame('filtered-raw-guid', ssp_episode_guid($this->post_id));
            $this->assertSame('raw-guid', get_post_meta($this->post_id, 'ssp_episode_guid', true));
            $this->assertFalse(metadata_exists('post', $this->post_id, 'ssp_guid'));
        } finally {
            remove_filter('ssp/episode/guid', $filter);
        }
    }

    /**
     * A brand-new episode gets a stable UUIDv5, never an ssp_guid write.
     */
    public function testMetaBoxSaveGeneratesStableUuidV5()
    {
        $this->saveEpisodeMetaBox();
        $guid = get_post_meta($this->post_id, 'ssp_episode_guid', true);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $guid);
        $this->assertFalse(metadata_exists('post', $this->post_id, 'ssp_guid'));
        $this->saveEpisodeMetaBox();
        $this->assertSame($guid, get_post_meta($this->post_id, 'ssp_episode_guid', true));
    }

    /**
     * Duplicator completion removes the copied episode's identity.
     */
    public function testPostDuplicatorDoesNotCopyEpisodeGuid()
    {
        $this->controller->prevent_copy_meta();
        $copy = $this->factory()->post->create(['post_type' => SSP_CPT_PODCAST]);
        update_post_meta($copy, 'ssp_episode_guid', 'copied-guid');
        do_action('mtphr_post_duplicator_created', $copy);
        $this->assertFalse(metadata_exists('post', $copy, 'ssp_episode_guid'));
    }

    private function saveEpisodeMetaBox($post_id = null)
    {
        $post_id = $post_id ?: $this->post_id;
        $previous_post = $_POST;
        $previous_user = get_current_user_id();
        wp_set_current_user($this->factory()->user->create(['role' => 'administrator']));
        // Use the real controller and repository: this class's mocks omit custom fields
        // and would make meta_box_save() skip the GUID path entirely.
        $controller = ssp_app()->podcast_post_types_controller;
        $this->assertArrayHasKey('audio_file', $controller->custom_fields());
        $_POST = [
            'post_type' => SSP_CPT_PODCAST,
            'seriouslysimple_' . SSP_CPT_PODCAST . '_nonce' => wp_create_nonce(plugin_basename($controller->dir)),
            'audio_file' => 'https://example.com/episode.mp3',
            'duration' => '00:01:00',
            'filesize' => '1 MB',
            'filesize_raw' => '1048576',
        ];
        // Supply unchanged media metadata so the real repository needn't fetch a remote file.
        foreach (['audio_file', 'duration', 'filesize', 'filesize_raw'] as $key) {
            update_post_meta($post_id, $key, $_POST[$key]);
        }
        try {
            $this->assertTrue($controller->meta_box_save($post_id, get_post($post_id)));
        } finally {
            $_POST = $previous_post;
            wp_set_current_user($previous_user);
        }
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

