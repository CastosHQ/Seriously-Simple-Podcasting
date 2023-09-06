<?php
/**
 * Singleton Trait
 */

namespace SeriouslySimplePodcasting\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton Trait
 * Moved this code from the parent Controller class.
 *
 * @author Sergiy Zakharchenko
 * @package SeriouslySimplePodcasting
 * @since 2.23.0
 */
trait URL_Helper {
	/**
	 * Checks if it's an SSP admin page or not
	 *
	 * @return bool
	 */
	protected function is_ssp_admin_page() {
		return SSP_CPT_PODCAST === filter_input( INPUT_GET, 'post_type' );
	}

	protected function is_ssp_post_page() {
		return in_array( get_current_screen()->post_type, ssp_post_types() );
	}
}
