<?php
/**
 * Paid Memberships Pro controller
 */

namespace SeriouslySimplePodcasting\Integrations\Paid_Memberships_Pro;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paid Memberships Pro controller
 *
 * @author Sergey Zakharchenko
 * @package SeriouslySimplePodcasting
 * @since 2.9.3
 */
abstract class Abstract_Integrator {

	/**
	 * Adds integrations settings
	 *
	 * @param array $args
	 */
	public function add_integration_settings( $args ) {
		add_filter( 'ssp_integration_settings', function ( $settings ) use ( $args ) {
			$settings['items'][ $args['id'] ] = $args;

			return $settings;
		} );
	}
}
