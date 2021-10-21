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
		$this->init_integration_settings();
	}

	/**
	 * Inits integration settings
	 */
	public function init_integration_settings(){
		$args = array(
			'id' => 'paid_memberships_pro',
			'title'       => __( 'Paid Memberships Pro', 'seriously-simple-podcasting' ),
			'description' => __( 'Paid Memberships Pro integration settings.', 'seriously-simple-podcasting' ),

			'fields' => array(
				array(
					'id'          => 'enable_pmp_integration',
					'label'       => __( 'Enable', 'seriously-simple-podcasting' ),
					'description' => __( 'Turn on to enable Memberships Pro plugin integration', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox',
				),
			),
		);

		if ( ! ssp_is_connected_to_castos() ) {
			$msg = __( 'Please <a href="%s">connect to Castos hosting</a> to enable integrations', 'seriously-simple-podcasting' );
			$msg = sprintf( $msg, admin_url( 'edit.php?post_type=podcast&page=podcast_settings&tab=castos-hosting' ) );

			$args['description'] = $msg;
			$args['fields']      = array();
		}

		$this->add_integration_settings( $args );
	}
}
