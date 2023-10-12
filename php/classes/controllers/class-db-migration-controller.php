<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;
use SeriouslySimplePodcasting\Traits\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration controller
 * Checks if the plugin needs to run a database migration after the plugin updates
 *
 * @package Seriously Simple Podcasting
 * @author Serhiy Zakharchenko
 * @since 2.9.3
 */
class DB_Migration_Controller {

	use Singleton;

	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_migrate_db' ) );

		return $this;
	}

	public function maybe_migrate_db() {
		$db_version = get_option( 'ssp_db_version' );
		if ( $db_version === SSP_VERSION ) {
			return;
		}

		switch ( SSP_VERSION ) {
			case '2.9.3':
				$this->update_date_recorded();
				break;
		}

		update_option( 'ssp_db_version', SSP_VERSION, false );
	}

	/**
	 * @since 2.9.3
	 * Updates date_recorded format.
	 * Unfortunately, the old format dd-mm-YYYY doesn't allow ordering episodes by date in the query.
	 * So, we need to update it to YYYY-mm-dd format.
	 * */
	protected function update_date_recorded() {
		$args = array(
			'post_type'      => ssp_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
		);

		$query = new \WP_Query( $args );

		foreach ( $query->posts as $post ) {
			$date_recorded = get_post_meta( $post->ID, 'date_recorded', true );

			$time = $date_recorded ? strtotime( $date_recorded ) : strtotime( $post->post_date );

			$date_recorded = date( 'Y-m-d', $time );

			update_post_meta( $post->ID, 'date_recorded', $date_recorded );
		}
	}

}
