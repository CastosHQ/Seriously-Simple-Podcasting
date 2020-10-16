<?php

namespace SeriouslySimplePodcasting\Renderers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Renderer {



	public function render($data, $template_path){
		// enqueue any css or js, based ont he data being passed
		// start ob

		extract( $data, EXTR_OVERWRITE );
		ob_start();

		// include the $template_path
		$template_file = SSP_PLUGIN_PATH . 'templates/' . $template_path . '.php';
		include $template_file;

		$template_content = ob_get_clean();

		$template_content = apply_filters( 'ssp_render_template', $template_content );

		return $template_content;
	}
}
