<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Integrations\Paid_Memberships_Pro\Paid_Memberships_Pro_Integrator;

// Provide a global-namespace stub for pmpro_getMembershipLevelsForUser() so
// tests that exercise the non-editor code-path (which reaches has_access())
// do not trigger a fatal "call to undefined function".
require_once __DIR__ . '/_pmpro-stubs.php';

class PaidMembershipsProIntegratorTest extends \Codeception\TestCase\WPTestCase {

	/** @var int[] Term IDs created during a test, cleaned up in tearDown. */
	private array $created_term_ids = [];

	/** @var string[] Option keys set during a test, cleaned up in tearDown. */
	private array $created_option_keys = [];

	/** @var int[] User IDs created during a test, cleaned up in tearDown. */
	private array $created_user_ids = [];

	protected function setUp(): void {
		parent::setUp();
		$this->created_term_ids    = [];
		$this->created_option_keys = [];
		$this->created_user_ids    = [];
	}

	protected function tearDown(): void {
		foreach ( $this->created_option_keys as $key ) {
			delete_option( $key );
		}
		foreach ( $this->created_term_ids as $term_id ) {
			wp_delete_term( $term_id, ssp_series_taxonomy() );
		}
		foreach ( $this->created_user_ids as $user_id ) {
			wp_delete_user( $user_id );
		}
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Creates a series term and configures it to require the given PMPro levels.
	 *
	 * @param string[] $level_keys  e.g. [ 'lvl_2' ]
	 * @return int  The new term ID.
	 */
	private function createSeriesWithLevels( array $level_keys ): int {
		$result  = wp_insert_term( 'Test Series ' . uniqid(), ssp_series_taxonomy() );
		$term_id = $result['term_id'];
		$this->created_term_ids[] = $term_id;

		if ( ! empty( $level_keys ) ) {
			$option_key = 'ss_podcasting_series_' . $term_id . '_pmpro_levels';
			update_option( $option_key, $level_keys );
			$this->created_option_keys[] = $option_key;
		}

		return $term_id;
	}

	/**
	 * Creates a `podcast` episode post and assigns it to the given series term.
	 *
	 * @param int $term_id
	 * @return int  The new post ID.
	 */
	private function createEpisodeInSeries( int $term_id ): int {
		$episode_id = $this->factory()->post->create( [
			'post_type'   => 'podcast',
			'post_status' => 'publish',
			'post_title'  => 'Test Episode ' . uniqid(),
		] );
		wp_set_post_terms( $episode_id, [ $term_id ], ssp_series_taxonomy() );

		return $episode_id;
	}

	// -------------------------------------------------------------------------
	// Tests
	// -------------------------------------------------------------------------

	/**
	 * An administrator (who has edit_post capability for the episode) must bypass
	 * the PMPro membership-level check and receive the original $access value.
	 *
	 * This covers the bug-fix: admins opening a members-only episode in a
	 * front-end page builder (e.g. Divi Visual Builder) must not be blocked.
	 *
	 * @covers \SeriouslySimplePodcasting\Integrations\Paid_Memberships_Pro\Paid_Memberships_Pro_Integrator::access_filter()
	 */
	public function testEditorBypassesMembershipProtection(): void {
		$term_id    = $this->createSeriesWithLevels( [ 'lvl_2' ] );
		$episode_id = $this->createEpisodeInSeries( $term_id );

		$admin_id                 = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		$this->created_user_ids[] = $admin_id;

		$integrator = Paid_Memberships_Pro_Integrator::instance();
		$result     = $integrator->access_filter(
			true,
			get_post( $episode_id ),
			get_userdata( $admin_id ),
			[]
		);

		$this->assertTrue(
			$result,
			'An administrator with edit_post capability must bypass the membership check and receive access.'
		);
	}

	/**
	 * A subscriber (no edit_post capability) without a required membership level
	 * must still be denied access to the protected episode.
	 *
	 * The pmpro stub returns [] (no levels), so has_access() returns false.
	 *
	 * @covers \SeriouslySimplePodcasting\Integrations\Paid_Memberships_Pro\Paid_Memberships_Pro_Integrator::access_filter()
	 */
	public function testNonEditorWithoutLevelStillDenied(): void {
		$term_id    = $this->createSeriesWithLevels( [ 'lvl_2' ] );
		$episode_id = $this->createEpisodeInSeries( $term_id );

		$subscriber_id            = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		$this->created_user_ids[] = $subscriber_id;

		$integrator = Paid_Memberships_Pro_Integrator::instance();
		$result     = $integrator->access_filter(
			true,
			get_post( $episode_id ),
			get_userdata( $subscriber_id ),
			[]
		);

		$this->assertFalse(
			$result,
			'A subscriber without the required membership level must be denied access to the protected episode.'
		);
	}

	/**
	 * Sanity check: when the series has no PMPro levels configured, any user
	 * (including a subscriber) must receive access.
	 *
	 * has_access() short-circuits on empty $related_level_ids and returns true
	 * without calling pmpro_getMembershipLevelsForUser().
	 *
	 * @covers \SeriouslySimplePodcasting\Integrations\Paid_Memberships_Pro\Paid_Memberships_Pro_Integrator::access_filter()
	 */
	public function testEpisodeWithoutRequiredLevelIsAccessible(): void {
		// Create a series with NO pmpro levels option set.
		$term_id    = $this->createSeriesWithLevels( [] );
		$episode_id = $this->createEpisodeInSeries( $term_id );

		$subscriber_id            = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		$this->created_user_ids[] = $subscriber_id;

		$integrator = Paid_Memberships_Pro_Integrator::instance();
		$result     = $integrator->access_filter(
			true,
			get_post( $episode_id ),
			get_userdata( $subscriber_id ),
			[]
		);

		$this->assertTrue(
			$result,
			'An episode whose series has no PMPro levels configured must be accessible to any user.'
		);
	}
}
