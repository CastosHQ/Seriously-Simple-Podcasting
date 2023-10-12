<?php
/**
 * Renderer class.
 */

namespace SeriouslySimplePodcasting\Renderers;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Interfaces\Service;
use SeriouslySimplePodcasting\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @author Serhiy Zakharchenko
 * @package SeriouslySimplePodcasting
 * */
class Renderer implements Service {

	use Singleton;

	/**
	 * Todo: get rid of direct instance creating
	 * */
	public function __construct() {
		return $this;
	}

	/**
	 * Renderer function (old)
	 *
	 * @param array $data
	 * @param string $template_path
	 *
	 * @return string
	 *
	 * @deprecated Changed the parameters order. Please use the new fetch() and render() functions.
	 * Todo: change all the places where it's used to the new fetch() function and remove it.
	 */
	public function render_deprecated( $data, $template_path ) {
		extract( $data, EXTR_OVERWRITE );
		ob_start();

		$template_file = SSP_PLUGIN_PATH . 'templates/' . $template_path . '.php';
		include $template_file;

		$template_content = ob_get_clean();

		$template_content = apply_filters( 'ssp_render_template', $template_content );

		return $template_content;
	}


	/**
	 * Prints the template.
	 *
	 * @param string $template_path
	 * @param array $data
	 *
	 * @return void
	 */
	public function render( $template_path, $data = [] ) {
		$html = $this->fetch( $template_path, $data );

		echo $html;
	}

	/**
	 * Fetches the template string.
	 *
	 * There are 4 possible path variants:
	 * 1. Absolute path: /app/wp-content/plugins/seriously-simple-podcasting/templates/feed/feed-item.php
	 * 2. Relative WP path: wp-content/plugins/seriously-simple-podcasting/templates/feed/feed-item.php
	 * 3. Relative plugin path: templates/feed/feed-item.php
	 * 4. Relative plugin path inside templates folder: feed/feed-item.php
	 * And each path variant can be with and without .php extension.
	 *
	 * @param string $template_path
	 * @param array $data
	 *
	 * @return string
	 */
	public function fetch( $template_path, $data = [] ) {
		$abs_path = '';

		// Check if there is extension in the end. If not, lets add it.
		$ext = pathinfo( $template_path, PATHINFO_EXTENSION );

		if ( ! $ext ) {
			$template_path .= '.php';
		}

		// Now try to search template in different locations
		if ( file_exists( SSP_PLUGIN_PATH . 'templates/' . $template_path ) ) {
			$abs_path = SSP_PLUGIN_PATH . 'templates/' . $template_path;
		}

		if ( ! $abs_path && file_exists( SSP_PLUGIN_PATH . $template_path ) ) {
			$abs_path = SSP_PLUGIN_PATH . $template_path;
		}

		if ( ! $abs_path && file_exists( ABSPATH . $template_path ) ) {
			$abs_path = ABSPATH . $template_path;
		}

		if ( ! $abs_path && file_exists( $template_path ) ) {
			$abs_path = $template_path;
		}

		if ( ! $abs_path ) {
			return '';
		}

		extract( $data, EXTR_OVERWRITE );
		ob_start();

		include $abs_path;

		$template_content = (string) ob_get_clean();

		return apply_filters( 'ssp_render_template', $template_content );
	}
}
