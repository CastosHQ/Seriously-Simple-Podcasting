<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Repositories\Episode_Repository;

class EpisodeRepositoryFailedSyncTest extends \Codeception\TestCase\WPTestCase {

	protected function setUp(): void {
		parent::setUp();
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Repositories\Episode_Repository::get_failed_sync_episodes()
	 */
	public function testGetFailedSyncEpisodesReturnsEpisodeOnAnotherHostWithoutPodmotorIds(): void {
		$episode_id = $this->createEpisode( 'https://media.test/audio.mp3' );

		$episodes = $this->getFailedSyncEpisodes( 'https://site.test' );

		$this->assertSame( array( $episode_id ), $this->getEpisodeIds( $episodes ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Repositories\Episode_Repository::get_failed_sync_episodes()
	 */
	public function testGetFailedSyncEpisodesExcludesEpisodeOnSiteHost(): void {
		$this->createEpisode( 'https://site.test/audio.mp3' );

		$episodes = $this->getFailedSyncEpisodes( 'https://site.test' );

		$this->assertSame( array(), $this->getEpisodeIds( $episodes ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Repositories\Episode_Repository::get_failed_sync_episodes()
	 */
	public function testGetFailedSyncEpisodesExcludesEpisodeWithBothPodmotorIds(): void {
		$this->createEpisode(
			'https://media.test/audio.mp3',
			array(
				'podmotor_file_id'    => '10',
				'podmotor_episode_id' => '20',
			)
		);

		$episodes = $this->getFailedSyncEpisodes( 'https://site.test' );

		$this->assertSame( array(), $this->getEpisodeIds( $episodes ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Repositories\Episode_Repository::get_failed_sync_episodes()
	 */
	public function testGetFailedSyncEpisodesMatchesUnderscoreInSiteHostLiterally(): void {
		$episode_id = $this->createEpisode( 'https://myXsite.test/audio.mp3' );

		$episodes = $this->getFailedSyncEpisodes( 'https://my_site.test' );

		$this->assertSame( array( $episode_id ), $this->getEpisodeIds( $episodes ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Repositories\Episode_Repository::get_failed_sync_episodes()
	 */
	public function testGetFailedSyncEpisodesMatchesPercentInSiteHostLiterally(): void {
		$episode_id = $this->createEpisode( 'https://myXsite.test/audio.mp3' );

		$episodes = $this->getFailedSyncEpisodes( 'https://my%site.test' );

		$this->assertSame( array( $episode_id ), $this->getEpisodeIds( $episodes ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Repositories\Episode_Repository::get_failed_sync_episodes()
	 */
	public function testGetFailedSyncEpisodesHandlesSingleQuoteInSiteHost(): void {
		global $wpdb;

		$episode_id       = $this->createEpisode( 'https://media.test/audio.mp3' );
		$wpdb->last_error = '';

		$episodes = $this->getFailedSyncEpisodes( "https://my'site.test" );

		$this->assertSame( '', $wpdb->last_error );
		$this->assertSame( array( $episode_id ), $this->getEpisodeIds( $episodes ) );
	}

	/**
	 * @covers \SeriouslySimplePodcasting\Repositories\Episode_Repository::get_failed_sync_episodes()
	 */
	public function testGetFailedSyncEpisodesReturnsNoRowsForEmptySiteHost(): void {
		$this->createEpisode( 'https://media.test/audio.mp3' );

		$episodes = $this->getFailedSyncEpisodes( '' );

		$this->assertSame( array(), $this->getEpisodeIds( $episodes ) );
	}

	/**
	 * Creates a published episode with the supplied metadata.
	 *
	 * @param string $audio_file Audio file URL.
	 * @param array  $meta       Additional episode metadata.
	 *
	 * @return int
	 */
	private function createEpisode( $audio_file, $meta = array() ) {
		$episode_id = $this->factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => 'publish',
			)
		);

		update_post_meta( $episode_id, 'audio_file', $audio_file );

		foreach ( $meta as $key => $value ) {
			update_post_meta( $episode_id, $key, $value );
		}

		return $episode_id;
	}

	/**
	 * Gets IDs from failed sync episode entities.
	 *
	 * @param array $episodes Failed sync episode entities.
	 *
	 * @return int[]
	 */
	private function getEpisodeIds( $episodes ) {
		return wp_list_pluck( $episodes, 'post_id' );
	}

	/**
	 * Gets failed sync episodes for a site URL.
	 *
	 * @param string $site_url Site URL.
	 *
	 * @return array
	 */
	private function getFailedSyncEpisodes( $site_url ) {
		$site_url_filter = function () use ( $site_url ) {
			return $site_url;
		};

		add_filter( 'site_url', $site_url_filter );
		$repository = new Episode_Repository( ssp_get_service( 'feed_handler' ) );
		remove_filter( 'site_url', $site_url_filter );

		wp_cache_delete( 'episode_ids', 'ssp' );

		return $repository->get_failed_sync_episodes();
	}
}
