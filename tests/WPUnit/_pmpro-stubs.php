<?php
/**
 * Global-namespace stubs for PMPro functions that are absent in the WPUnit suite.
 *
 * Guarded by function_exists() so the file is a no-op when PMPro is loaded.
 */

if ( ! function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
	/**
	 * Stub: returns an empty array, meaning the user holds no membership levels.
	 *
	 * @param int $user_id
	 * @return array
	 */
	function pmpro_getMembershipLevelsForUser( $user_id ) {
		return [];
	}
}
