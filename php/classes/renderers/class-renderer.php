<?php
/**
 * Renderer class.
 */

namespace SeriouslySimplePodcasting\Renderers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @author Sergey Zakharchenko
 * @package SeriouslySimplePodcasting
 * */
class Renderer {

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
	public function render( $template_path, $data ) {
		$html = $this->fetch( $template_path, $data );

		echo $html;
	}

	/**
	 * Fetches the template string.
	 *
	 * @param string $template_path
	 * @param array $data
	 *
	 * @return string
	 */
	public function fetch( $template_path, $data ) {
		extract( $data, EXTR_OVERWRITE );
		ob_start();

		$template_file = SSP_PLUGIN_PATH . 'templates/' . $template_path . '.php';
		include $template_file;

		$template_content = (string) ob_get_clean();

		return apply_filters( 'ssp_render_template', $template_content );
	}
}
