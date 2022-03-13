<?php
/**
 * Paid Memberships Pro controller
 */

namespace SeriouslySimplePodcasting\Integrations;

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

	/**
	 * Checks if all needed classes and functions exist.
	 *
	 * @return bool
	 */
	protected function check_dependencies( $classes, $functions ) {
		foreach ( $classes as $class ) {
			if ( ! class_exists( $class ) ) {
				return false;
			}
		}

		foreach ( $functions as $function ) {
			if ( ! function_exists( $function ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Gets array of all available series terms.
	 *
	 * @return \WP_Term[]|\WP_Error
	 */
	protected function get_series() {
		return get_terms( 'series', array( 'hide_empty' => false ) );
	}

	/**
	 * Gets array of episode series terms.
	 *
	 * @param $post_id
	 *
	 * @return \WP_Term[]
	 */
	protected function get_episode_series( $post_id ) {
		$series = wp_get_post_terms( $post_id, 'series' );

		if ( is_wp_error( $series ) ) {
			return [];
		}

		return $series;
	}
}
