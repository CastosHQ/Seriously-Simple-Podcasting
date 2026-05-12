<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;

class CastosHandlerImageTest extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var Castos_Handler
     */
    protected $castos_handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->castos_handler = ssp_get_service('castos_handler');
    }

    protected function tearDown(): void
    {
        remove_all_filters('ssp_use_featured_image_for_castos');
        remove_all_filters('ssp_allow_castos_image_removal');
        remove_all_filters('pre_http_request');
        parent::tearDown();
    }

    /**
     * Creates a test episode post.
     *
     * @return int Post ID.
     */
    protected function createEpisode()
    {
        return $this->factory()->post->create([
            'post_status' => 'publish',
            'post_type'   => SSP_CPT_PODCAST,
        ]);
    }

    /**
     * Creates an attachment post with image metadata.
     *
     * @param int $width  Image width.
     * @param int $height Image height.
     *
     * @return int Attachment ID.
     */
    protected function createAttachment($width = 800, $height = 800)
    {
        $filename = 'test-image-' . wp_rand() . '.png';

        $attachment_id = $this->factory()->attachment->create([
            'post_mime_type' => 'image/png',
            'post_type'     => 'attachment',
        ]);

        update_post_meta($attachment_id, '_wp_attached_file', $filename);
        wp_update_attachment_metadata($attachment_id, [
            'width'  => $width,
            'height' => $height,
            'file'   => $filename,
            'sizes'  => [],
        ]);

        return $attachment_id;
    }

    /**
     * @covers Castos_Handler::get_episode_image_url()
     */
    public function testReturnsEmptyWhenNoImageExists()
    {
        $episode_id = $this->createEpisode();
        $post       = get_post($episode_id);

        $result = $this->castos_handler->get_episode_image_url($post);

        $this->assertSame('', $result);
    }

    /**
     * @covers Castos_Handler::get_episode_image_url()
     */
    public function testReturnsCoverImageWhenSet()
    {
        $episode_id    = $this->createEpisode();
        $attachment_id = $this->createAttachment(800, 800);
        $post          = get_post($episode_id);

        $image_url = wp_get_attachment_image_src($attachment_id, 'full')[0];
        update_post_meta($episode_id, 'cover_image', $image_url);
        update_post_meta($episode_id, 'cover_image_id', $attachment_id);

        $result = $this->castos_handler->get_episode_image_url($post);

        $this->assertSame($image_url, $result);
    }

    /**
     * @covers Castos_Handler::get_episode_image_url()
     */
    public function testFallsBackToSquareFeaturedImage()
    {
        $episode_id    = $this->createEpisode();
        $attachment_id = $this->createAttachment(800, 800);
        $post          = get_post($episode_id);

        set_post_thumbnail($episode_id, $attachment_id);

        $result = $this->castos_handler->get_episode_image_url($post);

        $featured_src = wp_get_attachment_image_src($attachment_id, 'full');
        $this->assertSame($featured_src[0], $result);
    }

    /**
     * @covers Castos_Handler::get_episode_image_url()
     */
    public function testSkipsNonSquareFeaturedImage()
    {
        $episode_id    = $this->createEpisode();
        $attachment_id = $this->createAttachment(800, 600);
        $post          = get_post($episode_id);

        set_post_thumbnail($episode_id, $attachment_id);

        $result = $this->castos_handler->get_episode_image_url($post);

        $this->assertSame('', $result);
    }

    /**
     * @covers Castos_Handler::get_episode_image_url()
     */
    public function testFilterDisablesFeaturedImageFallback()
    {
        $episode_id    = $this->createEpisode();
        $attachment_id = $this->createAttachment(800, 800);
        $post          = get_post($episode_id);

        set_post_thumbnail($episode_id, $attachment_id);

        add_filter('ssp_use_featured_image_for_castos', '__return_false');

        $result = $this->castos_handler->get_episode_image_url($post);

        $this->assertSame('', $result);
    }

    /**
     * @covers Castos_Handler::get_episode_image_url()
     */
    public function testFilterReceivesEpisodeId()
    {
        $episode_id    = $this->createEpisode();
        $attachment_id = $this->createAttachment(800, 800);
        $post          = get_post($episode_id);

        set_post_thumbnail($episode_id, $attachment_id);

        $received_id = null;
        add_filter('ssp_use_featured_image_for_castos', function ($use, $id) use (&$received_id) {
            $received_id = $id;
            return $use;
        }, 10, 2);

        $this->castos_handler->get_episode_image_url($post);

        $this->assertSame($episode_id, $received_id);
    }

    /**
     * @covers Castos_Handler::get_episode_image_url()
     */
    public function testFilterDoesNotAffectCoverImage()
    {
        $episode_id    = $this->createEpisode();
        $attachment_id = $this->createAttachment(800, 800);
        $post          = get_post($episode_id);

        $image_url = wp_get_attachment_image_src($attachment_id, 'full')[0];
        update_post_meta($episode_id, 'cover_image', $image_url);
        update_post_meta($episode_id, 'cover_image_id', $attachment_id);

        add_filter('ssp_use_featured_image_for_castos', '__return_false');

        $result = $this->castos_handler->get_episode_image_url($post);

        $this->assertSame($image_url, $result);
    }

    /**
     * Sets up an existing episode with a podmotor_episode_id and file_id,
     * intercepts the HTTP request, and returns the captured post body.
     *
     * @param int $episode_id Post ID.
     *
     * @return array|null Decoded JSON body from the intercepted request.
     */
    protected function captureUploadBody($episode_id)
    {
        update_post_meta($episode_id, 'podmotor_episode_id', 123);
        update_post_meta($episode_id, 'podmotor_file_id', 456);
        update_option('ss_podcasting_podmotor_account_api_token', 'test-token');

        $captured_body = null;
        add_filter('pre_http_request', function ($preempt, $args) use (&$captured_body) {
            $captured_body = json_decode($args['body'], true);
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body'     => wp_json_encode(['id' => 123, 'success' => true]),
            ];
        }, 10, 2);

        $this->castos_handler->upload_episode_to_castos(get_post($episode_id));

        return $captured_body;
    }

    /**
     * @covers Castos_Handler::upload_episode_to_castos()
     */
    public function testUploadSendsEmptyImageUrlForExistingEpisode()
    {
        $episode_id = $this->createEpisode();
        $body       = $this->captureUploadBody($episode_id);

        $this->assertIsArray($body);
        $this->assertArrayHasKey('featured_image_url', $body);
        $this->assertSame('', $body['featured_image_url']);
    }

    /**
     * @covers Castos_Handler::upload_episode_to_castos()
     */
    public function testUploadOmitsEmptyImageUrlForNewEpisode()
    {
        $episode_id = $this->createEpisode();
        update_post_meta($episode_id, 'podmotor_file_id', 456);
        update_option('ss_podcasting_podmotor_account_api_token', 'test-token');

        $captured_body = null;
        add_filter('pre_http_request', function ($preempt, $args) use (&$captured_body) {
            $captured_body = json_decode($args['body'], true);
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body'     => wp_json_encode(['id' => 123, 'success' => true]),
            ];
        }, 10, 2);

        $this->castos_handler->upload_episode_to_castos(get_post($episode_id));

        $this->assertIsArray($captured_body);
        $this->assertArrayNotHasKey('featured_image_url', $captured_body);
    }

    /**
     * @covers Castos_Handler::upload_episode_to_castos()
     */
    public function testUploadRemovalFilterPreventsEmptyImageUrl()
    {
        $episode_id = $this->createEpisode();

        add_filter('ssp_allow_castos_image_removal', '__return_false');

        $body = $this->captureUploadBody($episode_id);

        $this->assertIsArray($body);
        $this->assertArrayNotHasKey('featured_image_url', $body);
    }

    /**
     * @covers Castos_Handler::upload_episode_to_castos()
     */
    public function testUploadRemovalFilterReceivesEpisodeId()
    {
        $episode_id = $this->createEpisode();

        $received_id = null;
        add_filter('ssp_allow_castos_image_removal', function ($allow, $id) use (&$received_id) {
            $received_id = $id;
            return $allow;
        }, 10, 2);

        $this->captureUploadBody($episode_id);

        $this->assertSame($episode_id, $received_id);
    }
}
