<?php
/**
 * Paid Memberships Pro controller
 */

namespace SeriouslySimplePodcasting\Integrations\Paid_Memberships_Pro;

use SeriouslySimplePodcasting\Traits\Singleton;

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
class Paid_Memberships_Pro_Integrator extends Abstract_Integrator {

	use Singleton;

	/**
	 * Class Paid_Memberships_Pro_Integrator constructor.
	 */
	public function init() {
		if ( ! class_exists( 'PMPro_Membership_Level' ) ) {
			return;
		}

		// Use 12 priority because Podcast and Series post types registered on 11
		add_action( 'init', array( $this, 'init_integration_settings' ), 12 );
	}

	/**
	 * Inits integration settings
	 */
	public function init_integration_settings(){
		$args = $this->get_integration_settings();

		if ( ! ssp_is_connected_to_castos() ) {
			$msg = __( 'Please <a href="%s">connect to Castos hosting</a> to enable integrations', 'seriously-simple-podcasting' );
			$msg = sprintf( $msg, admin_url( 'edit.php?post_type=podcast&page=podcast_settings&tab=castos-hosting' ) );

			$args['description'] = $msg;
			$args['fields']      = array();
		}

		$this->add_integration_settings( $args );
	}

	/**
	 * @return array
	 */
	protected function get_integration_settings(){
		$series           = $this->get_series();
		$levels           = $this->get_membership_levels();
		$checkbox_options = [];
		foreach ( $levels as $level ) {
			$checkbox_options[ 'lvl_' . $level->id ] = sprintf( 'Requires %s membership', $level->name );
		}

		$settings = array(
			'id'          => 'paid_memberships_pro',
			'title'       => __( 'Paid Memberships Pro', 'seriously-simple-podcasting' ),
			'description' => __( 'Paid Memberships Pro integration settings.', 'seriously-simple-podcasting' ),
			'fields'      => array(),
		);

		foreach ( $series as $series_item ) {

			$series_item_settings = array(
				'id'          => sprintf( 'series_%s_requires_pmp_lvl', $series_item->term_id ),
				'label'       => $series_item->name,
				'type'        => 'checkbox_multi',
				'options'     => $checkbox_options,
			);


			$settings['fields'][] = $series_item_settings;
		}

		return $settings;
	}

	/**
	 * @return int[]|string|string[]|\WP_Error|\WP_Term[]
	 */
	protected function get_series() {
		return get_terms( 'series', array( 'hide_empty' => false ) );
	}

	/**
	 * @return array
	 */
	protected function get_membership_levels() {
		return (array)pmpro_getAllLevels();
	}
}
