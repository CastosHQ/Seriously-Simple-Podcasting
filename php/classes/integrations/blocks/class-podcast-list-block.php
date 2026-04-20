<?php

namespace SeriouslySimplePodcasting\Integrations\Blocks;

use SeriouslySimplePodcasting\Presenters\Episode_List_Presenter;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

/**
 * Handles registration and rendering of the `seriously-simple-podcasting/podcast-list` block.
 *
 * @package Seriously Simple Podcasting
 * @since 2.0.4
 */
class Podcast_List_Block {

	use Useful_Variables;

	/**
	 * @var Episode_List_Presenter
	 */
	protected $episode_list_presenter;

	/**
	 * @param Episode_List_Presenter $episode_list_presenter Episode list presenter instance.
	 */
	public function __construct( $episode_list_presenter ) {
		$this->episode_list_presenter = $episode_list_presenter;
		$this->init_useful_variables();
	}

	/**
	 * Registers the block type and related assets.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_style( 'ssp-podcast-list', esc_url( $this->assets_url . 'css/blocks/podcast-list' . $this->script_suffix . '.css' ), array(), $this->version );

		add_action(
			'admin_enqueue_scripts',
			function () {
				wp_enqueue_style( 'ssp-podcast-list' );
			}
		);

		add_action(
			'wp_enqueue_scripts',
			function () {
				if ( function_exists( 'has_block' ) && has_block( 'seriously-simple-podcasting/podcast-list' ) ) {
					wp_enqueue_style( 'ssp-podcast-list' );
				}
			}
		);

		$default_series_id = ssp_get_default_series_id();

		register_block_type(
			'seriously-simple-podcasting/podcast-list',
			array(
				'editor_script'   => 'ssp-block-script',
				'editor_style'    => 'ssp-block-style',
				'attributes'      => array(
					'showTitle'           => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'featuredImage'       => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'availableImageSizes' => array(
						'type'    => 'array',
						'default' => array_merge(
							array(
								array(
									'label' => 'Full',
									'value' => 'full',
								),
							),
							array_map(
								function ( $item ) {
									return array(
										'label' => ucfirst( $item ),
										'value' => $item,
									);
								},
								get_intermediate_image_sizes()
							)
						),
					),
					'featuredImageSize'   => array(
						'type'    => 'string',
						'default' => 'full',
					),
					'excerpt'             => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'player'              => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'playerBelowExcerpt'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'availablePodcasts'   => array(
						'type'    => 'array',
						'default' => $this->get_podcast_settings(),
					),
					'selectedPodcast'     => array(
						'type'    => 'string',
						'default' => '-1',
					),
					'defaultPodcastId'    => array(
						'type'    => 'string',
						'default' => strval( $default_series_id ),
					),
					// Use string everywhere instead of number because of the WP bug.
					// It doesn't show the saved value in the admin after page refresh.
					'postsPerPage'        => array(
						'type'    => 'string',
						'default' => 0,
					),
					'orderBy'             => array(
						'type'    => 'string',
						'default' => 'date',
					),
					'order'               => array(
						'type'    => 'string',
						'default' => 'desc',
					),
					'columnsPerRow'       => array(
						'type'    => 'string',
						'default' => 1,
					),
					'titleSize'           => array(
						'type'    => 'string',
						'default' => 16,
					),
					'titleUnderImage'     => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'paginationType'      => array(
						'type' => 'string',
					),
					'titleColor'          => array(
						'type' => 'string',
					),
					'layout'              => array(
						'type'    => 'string',
						'default' => 'list',
					),
					'clickable'           => array(
						'type'    => 'string',
						'default' => 'button',
					),
					'buttonText'          => array(
						'type'    => 'string',
						'default' => __( 'Listen Now', 'seriously-simple-podcasting' ),
					),
					'cardBackground'      => array(
						'type' => 'string',
					),
					'textColor'           => array(
						'type' => 'string',
					),
					'linkColor'           => array(
						'type' => 'string',
					),
					'buttonColor'         => array(
						'type' => 'string',
					),
					'buttonBackground'    => array(
						'type' => 'string',
					),
					'paginationColor'     => array(
						'type' => 'string',
					),
					'paginationActiveColor' => array(
						'type' => 'string',
					),
				),
				'render_callback' => array( $this, 'render_callback' ),
			)
		);
	}

	/**
	 * Render callback for the podcast list block.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Rendered block output.
	 */
	public function render_callback( $attributes ) {
		return $this->episode_list_presenter->render( $attributes );
	}

	/**
	 * Gets podcast settings for block attributes.
	 *
	 * @return array
	 */
	protected function get_podcast_settings() {
		$default_series_id = ssp_get_default_series_id();

		return array_merge(
			array(
				array(
					'label' => __( '-- All --', 'seriously-simple-podcasting' ),
					'value' => -1,
				),
			),
			array_map(
				function ( $item ) use ( $default_series_id ) {
					$label = $default_series_id === $item->term_id ?
						ssp_get_default_series_name( $item->name ) :
						$item->name;

					return array(
						'label' => $label,
						'value' => $item->term_id,
					);
				},
				ssp_get_podcasts()
			)
		);
	}
}
