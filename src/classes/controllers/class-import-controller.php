<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.0
 */
class Admin_Controller {

	/**
	 * Render the progress bar to show the importing RSS feed progress
	 *
	 * @return false|string
	 */
	public function render_external_import_process() {
		ob_start();
		?>
		<h3 class="ssp-ssp-external-feed-message">Your external RSS feed is being imported. Please leave this window open until it completes</h3>
		<div id="ssp-external-feed-progress"></div>
		<div id="ssp-external-feed-status"><p>Commencing feed import</p></div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

}
