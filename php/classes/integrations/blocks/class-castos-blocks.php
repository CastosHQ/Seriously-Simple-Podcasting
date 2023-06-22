<?php

namespace SeriouslySimplePodcasting\Integrations\Blocks;

use SeriouslySimplePodcasting\Controllers\Players_Controller;
use SeriouslySimplePodcasting\Handlers\Admin_Notifications_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blocks class, used to load blocks and any relevant block assets
 *
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Blocks
 * @since       2.0.4
 */
class Castos_Blocks {

	use Useful_Variables;

	/**
	 * @var array $asset_file
	 */
	protected $asset_file;

	/**
	 * @var Admin_Notifications_Handler $admin_notices_handler
	 * */
	protected $admin_notices_handler;

	/**
	 * @var Episode_Repository $episode_repository
	 * */
	protected $episode_repository;

	/**
	 * @var Players_Controller $players_controller
	 * */
	protected $players_controller;

	/**
	 * @var Renderer $renderer
	 * */
	protected $renderer;

	/**
	 * Castos_Blocks constructor.
	 *
	 * @param Admin_Notifications_Handler $admin_notices_handler
	 * @param Episode_Repository $episode_repository
	 * @param Players_Controller $players_controller
	 * @param Renderer $renderer
	 */
	public function __construct( $admin_notices_handler, $episode_repository, $players_controller, $renderer ) {

		$this->admin_notices_handler = $admin_notices_handler;
		$this->episode_repository    = $episode_repository;
		$this->players_controller    = $players_controller;
		$this->renderer              = $renderer;

		if ( ! file_exists( SSP_PLUGIN_PATH . 'build/index.asset.php' ) ) {
			if ( is_admin() ) {
				$this->admin_notices_handler = new Admin_Notifications_Handler( SSP_CPT_PODCAST );
				add_action( 'admin_notices', array( $this->admin_notices_handler, 'blocks_error_notice' ) );
			}

			return;
		}
		$this->asset_file = include SSP_PLUGIN_PATH . 'build/index.asset.php';

		// Our custom post types and taxonomies are registered on 11. Let's register blocks after that on 12.
		add_action( 'init', array( $this, 'register_castos_blocks' ), 12 );
	}

	/**
	 * Dynamic Podcast List Block callback
	 *
	 * @param $attributes
	 *
	 * @return string
	 */
	public function podcast_list_render_callback( $attributes ) {
		$player_style       = (string) get_option( 'ss_podcasting_player_style', '' );
		$paged              = ( filter_input( INPUT_GET, 'podcast_page' ) ) ?: 1;

		$show_title      = boolval( $attributes['showTitle'] );
		$show_img        = boolval( $attributes['featuredImage'] );
		$img_size        = $attributes['featuredImageSize'];
		$is_player_below = boolval( $attributes['playerBelowExcerpt'] );
		$show_excerpt    = $attributes['excerpt'];

		$episode_items = '';
		$permalink_structure = get_option( 'permalink_structure' );
		$allowed_order_by = array( 'ID', 'title', 'date', 'recorded' );

		$args = array(
			'post_type'      => ssp_post_types(),
			'podcast_id'     => intval( $attributes['selectedPodcast'] ),
			'posts_per_page' => intval( $attributes['postsPerPage'] ?: get_option( 'posts_per_page', 10 ) ),
			'paged'          => $paged,
			'orderby'        => in_array( $attributes['orderBy'], $allowed_order_by ) ? $attributes['orderBy'] : 'date',
			'order'          => 'asc' === $attributes['order'] ? 'asc' : 'desc',
		);

		$episodes_query = $this->get_podcast_list_episodes_query( $args );

		if ( $episodes_query->have_posts() ) {
			while ( $episodes_query->have_posts() ) {
				$episodes_query->the_post();
				$episode = get_post();
				$permalink = get_permalink();

				$player = '';
				if ( ! empty( $attributes['player'] ) ) {
					$file   = $permalink_structure ? $this->episode_repository->get_episode_download_link( $episode->ID ) : $this->episode_repository->get_enclosure( $episode->ID );
					$player = $this->players_controller->load_media_player( $file, $episode->ID, $player_style );
				}

				$episode_items .= $this->renderer->fetch(
					'episodes/block-episodes-list-item',
					compact( 'episode', 'player', 'show_title', 'show_img', 'img_size', 'is_player_below', 'show_excerpt', 'permalink' )
				);
			}
		} else {
			$episode_items = __( 'Sorry, episodes not found', 'seriously-simple-podcasting' );
		}

		// We can't use get_next_posts_link() because it doesn't work on single pages.
		$links         = $this->get_podcast_list_paginate_links( $episodes_query, $paged );
		$episode_items .= implode( "\n", $links );

		wp_reset_postdata();

		return apply_filters( 'podcast_list_dynamic_block_html_content', '<div>' . $episode_items . '</div>' );
	}

	/**
	 * @param array $args
	 *
	 * @return \WP_Query
	 */
	protected function get_podcast_list_episodes_query( $args ) {

		$defaults = array(
			'post_status'    => 'publish',
			'post_type'      => SSP_CPT_PODCAST,
			'podcast_id'     => - 1,
			'posts_per_page' => 10,
			'paged'          => 1,
			'orderby'       => 'date',
			'order'          => 'desc',
			'meta_query'     => array(
				array(
					'key'     => 'audio_file',
					'compare' => '!=',
					'value'   => '',
				),
			),
		);

		$query_args = wp_parse_args( $args, $defaults );

		// -1 stands for all episodes ( option "-- All --" )
		if ( - 1 !== $query_args['podcast_id'] ) {
			$tax_query = array(
				'taxonomy' => ssp_series_taxonomy(),
			);

			if ( $query_args['podcast_id'] ) {
				$tax_query['field'] = 'id';
				$tax_query['terms'] = $query_args['podcast_id'];
			} else {
				$tax_query['operator'] = 'NOT EXISTS';
			}
			$query_args['tax_query'] = array( $tax_query );
		}

		if ( 'recorded' === $query_args['orderby'] ) {
			$query_args['orderby']  = 'meta_value';
			$query_args['meta_key'] = 'date_recorded';
		}

		$query_args = apply_filters( 'podcast_list_dynamic_block_query_arguments', $query_args );

		return new \WP_Query( $query_args );
	}

	/**
	 * @param \WP_Query $episodes_query
	 * @param int $current_page
	 *
	 * @return array
	 */
	protected function get_podcast_list_paginate_links( $episodes_query, $current_page ){
		$args = array(
			'format'    => '?podcast_page=%#%',
			'total'     => $episodes_query->max_num_pages,
			'current'   => $current_page,
			'prev_text' => __( '&laquo; Newer Episodes' ),
			'next_text' => __( 'Older Episodes &raquo;' ),
			'type'      => 'array',
		);

		$args = apply_filters( 'ssp_podcast_list_paginate_args', $args, $episodes_query );

		$all_links = paginate_links( $args );

		$links = array();

		if ( is_array( $all_links ) ) {
			foreach ( $all_links as $item ) {
				if ( strpos( $item, 'class="next' ) || strpos( $item, 'class="prev' ) ) {
					$links[] = $item;
				}
			}
		}

		return apply_filters( 'ssp_podcast_list_paginate_links', $links, $all_links, $episodes_query );
	}

	/**
	 * Registers the Castos Player Block
	 *
	 * @return void
	 */
	public function register_castos_blocks() {

		wp_register_script(
			'ssp-block-script',
			esc_url( SSP_PLUGIN_URL . 'build/index.js' ),
			$this->asset_file['dependencies'],
			$this->asset_file['version'],
			true
		);

		wp_localize_script( 'ssp-block-script', 'sspAdmin', array(
			'sspPostTypes' => ssp_post_types(true, false),
			'isCastosUser' => ssp_is_connected_to_castos(),
		) );

		wp_register_style(
			'ssp-block-style',
			esc_url( SSP_PLUGIN_URL . 'assets/css/block-editor-styles.css' ),
			array(),
			$this->asset_file['version']
		);

		register_block_type(
			'seriously-simple-podcasting/castos-player',
			array(
				'editor_script' => 'ssp-block-script',
				'editor_style'  => 'ssp-castos-player',
			)
		);

		/**
		 * Is used for both rendering the block itself and preview in editor
		 * @since 2.8.2
		 * */
		register_block_type( 'seriously-simple-podcasting/castos-html-player', array(
			'editor_script'   => 'ssp-block-script',
			'editor_style'    => 'ssp-castos-player',
			'attributes'      => array(
				'episodeId' => array(
					'type' => 'string',
					'default' => '',
				),
			),
			'render_callback' => function ( $args ) {
				return ssp_frontend_controller()->players_controller->render_html_player( $args['episodeId'], false );
			}
		) );

		register_block_type(
			'seriously-simple-podcasting/audio-player',
			array(
				'editor_script' => 'ssp-block-script',
			)
		);

		register_block_type(
			'seriously-simple-podcasting/podcast-list',
			array(
				'editor_script'   => 'ssp-block-script',
				'editor_style'    => 'ssp-block-style',
				'attributes'      => array(
					'showTitle' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'featuredImage' => array(
						'type'    => 'boolean',
						'default' => false,
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
							array_map( function ( $item ) {
								return array(
									'label' => ucfirst( $item ),
									'value' => $item,
								);
							}, get_intermediate_image_sizes() )
						),
					),
					'featuredImageSize' => array(
						'type'    => 'string',
						'default' => 'full',
					),
					'excerpt' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'player' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'playerBelowExcerpt' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'availablePodcasts' => array(
						'type'    => 'array',
						'default' => array_merge(
							array(
								array(
									'label' => __( '-- All --', 'seriously-simple-podcasting' ),
									'value' => - 1,
								),
								array(
									'label' => __( 'Default', 'seriously-simple-podcasting' ),
									'value' => 0,
								),
							),
							array_map( function ( $item ) {
								return array(
									'label' => $item->name,
									'value' => $item->term_id,
								);
							}, ssp_get_podcasts() ) ),
					),
					'selectedPodcast' => array(
						'type'    => 'string',
						'default' => '',
					),
					'postsPerPage' => array(
						'type'    => 'number',
						'default' => 0,
					),
					'orderBy' => array(
						'type'    => 'string',
						'default' => 'date'
					),
					'order' => array(
						'type'    => 'string',
						'default' => 'desc'
					),
				),
				'render_callback' => array(
					$this,
					'podcast_list_render_callback',
				),
			)
		);
	}
}
