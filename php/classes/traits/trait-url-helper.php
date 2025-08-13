<?php
/**
 * URL Helper trait.
 *
 * @package SeriouslySimplePodcasting
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

	/**
	 * Checks if this is a SSP post page or not.
	 *
	 * @return bool
	 */
	protected function is_ssp_post_page() {

		$current_screen = get_current_screen();
		if ( ! $current_screen ) {
			return false;
		}

		return in_array( $current_screen->post_type, ssp_post_types() );
	}

	/**
	 * Checks if this is a podcast post type page or not.
	 *
	 * @return bool
	 */
	protected function is_ssp_podcast_page() {
		$current_screen = get_current_screen();
		if ( ! $current_screen ) {
			return false;
		}

		return in_array( $current_screen->post_type, array( SSP_CPT_PODCAST ), true );
	}

	/**
	 * Check if this is any post page.
	 *
	 * @return bool
	 */
	protected function is_any_post_page() {
		$current_screen = get_current_screen();
		if ( ! $current_screen ) {
			return false;
		}

		return 'post' === $current_screen->base;
	}
}
