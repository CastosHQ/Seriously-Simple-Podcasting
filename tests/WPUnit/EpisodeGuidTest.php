<?php

namespace Tests\WPUnit;

class EpisodeGuidTest extends \Codeception\TestCase\WPTestCase {
	/**
	 * The authoritative key wins; otherwise the published legacy order remains intact.
	 */
	public function testEpisodeGuidResolutionPriority() {
		$id = self::factory()->post->create( array( 'post_type' => SSP_CPT_PODCAST ) );

		$this->assertSame( get_the_guid( $id ), ssp_episode_guid( $id ) );
		update_post_meta( $id, 'ssp_guid', 'native-legacy' );
		$this->assertSame( 'native-legacy', ssp_episode_guid( $id ) );
		update_post_meta( $id, 'ssp_original_guid', 'imported-original' );
		$this->assertSame( 'imported-original', ssp_episode_guid( $id ) );
		update_post_meta( $id, 'ssp_episode_guid', 'authoritative' );
		$this->assertSame( 'authoritative', ssp_episode_guid( $id ) );
	}

	/**
	 * A presentation filter cannot change the saved identity or write a new one.
	 */
	public function testEpisodeGuidFilterChangesOutputOnly() {
		$id = self::factory()->post->create( array( 'post_type' => SSP_CPT_PODCAST ) );
		update_post_meta( $id, 'ssp_original_guid', 'original' );
		$filter = function ( $guid, $episode_id ) use ( $id ) {
			return $id === $episode_id ? 'filtered-' . $guid : $guid;
		};
		add_filter( 'ssp/episode/guid', $filter, 10, 2 );
		try {
			$this->assertSame( 'filtered-original', ssp_episode_guid( $id ) );
			$this->assertFalse( metadata_exists( 'post', $id, 'ssp_episode_guid' ) );
			$this->assertSame( 'original', get_post_meta( $id, 'ssp_original_guid', true ) );
		} finally {
			remove_filter( 'ssp/episode/guid', $filter, 10 );
		}
	}
}
