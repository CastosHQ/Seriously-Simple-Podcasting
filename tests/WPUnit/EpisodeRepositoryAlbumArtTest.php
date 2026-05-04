<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Repositories\Episode_Repository;

class EpisodeRepositoryAlbumArtTest extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var Episode_Repository
     */
    protected $episode_repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->episode_repository = ssp_get_service('episode_repository');
    }

    protected function tearDown(): void
    {
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
    protected function createAttachment($width = 800, $height = 600)
    {
        $filename = 'test-image-' . wp_rand() . '.png';

        $attachment_id = $this->factory()->attachment->create([
            'post_mime_type' => 'image/png',
            'post_type'     => 'attachment',
        ]);

        // _wp_attached_file is required for wp_get_attachment_image_src() to return a URL.
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
     * @covers Episode_Repository::get_album_art()
     */
    public function testReturnsFeaturedImageWhenNoOtherImagesExist()
    {
        $episode_id    = $this->createEpisode();
        $attachment_id = $this->createAttachment(1200, 800);

        set_post_thumbnail($episode_id, $attachment_id);

        $result = $this->episode_repository->get_album_art($episode_id);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['src']);
        $this->assertStringNotContainsString('no-album-art', $result['src']);
    }

    /**
     * @covers Episode_Repository::get_album_art()
     */
    public function testSkipsFeaturedImageWhenCoverImageExists()
    {
        $episode_id          = $this->createEpisode();
        $cover_attachment_id = $this->createAttachment(1400, 1400);
        $feat_attachment_id  = $this->createAttachment(1200, 800);

        update_post_meta($episode_id, 'cover_image_id', $cover_attachment_id);
        set_post_thumbnail($episode_id, $feat_attachment_id);

        $result = $this->episode_repository->get_album_art($episode_id);

        // Should return the square cover image, not the featured image.
        $this->assertIsArray($result);
        $cover_src = wp_get_attachment_image_src($cover_attachment_id, 'full');
        $this->assertNotFalse($cover_src, 'Cover attachment must resolve to an image URL.');
        $this->assertSame($cover_src[0], $result['src']);
    }

    /**
     * @covers Episode_Repository::get_album_art()
     */
    public function testFeaturedImageDoesNotRequireSquareness()
    {
        $episode_id    = $this->createEpisode();
        $attachment_id = $this->createAttachment(1600, 900);

        set_post_thumbnail($episode_id, $attachment_id);

        $result = $this->episode_repository->get_album_art($episode_id);

        // Should return the non-square featured image instead of placeholder.
        $this->assertIsArray($result);
        $this->assertNotEmpty($result['src']);
        $this->assertStringNotContainsString('no-album-art', $result['src']);
    }

    /**
     * @covers Episode_Repository::get_album_art()
     */
    public function testReturnsPlaceholderWhenNoFeaturedImageExists()
    {
        $episode_id = $this->createEpisode();

        $result = $this->episode_repository->get_album_art($episode_id);

        $this->assertIsArray($result);
        $this->assertStringContainsString('no-album-art', $result['src']);
    }
}
