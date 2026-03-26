<?php

namespace SeriouslySimplePodcasting\Integrations\Blocks;

/**
 * Handles registration and rendering of the `seriously-simple-podcasting/castos-html-player` block.
 *
 * @package Seriously Simple Podcasting
 * @since 2.8.2
 */
class Castos_Html_Player_Block {

	/**
	 * Registers the block type.
	 *
	 * @return void
	 */
	public function register() {
		register_block_type(
			'seriously-simple-podcasting/castos-html-player',
			array(
				'editor_script'   => 'ssp-block-script',
				'editor_style'    => 'ssp-castos-player',
				'attributes'      => array(
					'episodeId' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'render_callback' => array( $this, 'render_callback' ),
			)
		);
	}

	/**
	 * Render callback for the Castos HTML Player block.
	 *
	 * @param array $args Block attributes.
	 *
	 * @return string Rendered player HTML.
	 */
	public function render_callback( $args ) {
		return ssp_frontend_controller()->players_controller->render_html_player( $args['episodeId'], true, 'block', $args );
	}
}
