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

		$this->init_useful_variables();

		$this->admin_notices_handler = $admin_notices_handler;
		$this->episode_repository    = $episode_repository;
		$this->players_controller    = $players_controller;
		$this->renderer              = $renderer;

		if ( ! file_exists( SSP_PLUGIN_PATH . 'build/index.asset.php' ) ) {
			if ( is_admin() ) {
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
		$paged            = ( filter_input( INPUT_GET, 'podcast_page' ) ) ?: 1;
		$allowed_order_by = array( 'ID', 'title', 'date', 'recorded' );

		$query_args = array(
			'post_type'      => ssp_post_types(),
			'podcast_id'     => ( '' === $attributes['selectedPodcast'] ) ? -1 : intval( $attributes['selectedPodcast'] ),
			'posts_per_page' => intval( $attributes['postsPerPage'] ?: get_option( 'posts_per_page', 10 ) ),
			'paged'          => $paged,
			'orderby'        => in_array( $attributes['orderBy'], $allowed_order_by ) ? $attributes['orderBy'] : 'date',
			'order'          => 'asc' === $attributes['order'] ? 'asc' : 'desc',
		);

		$episodes_query = $this->get_podcast_list_episodes_query( $query_args );

		// We can't use get_next_posts_link() because it doesn't work on single pages.
		$paginate = $this->get_podcast_list_paginate_links( $episodes_query, $paged );

		$args = array(
			'episode_repository'  => $this->episode_repository,
			'players_controller'  => $this->players_controller,
			'permalink_structure' => get_option( 'permalink_structure' ),
			'show_player'         => boolval( $attributes['player'] ),
			'player_style'        => get_option( 'ss_podcasting_player_style', '' ),
			'episodes_query'      => $episodes_query,
			'paginate'            => $paginate,
			'show_title'          => boolval( $attributes['showTitle'] ),
			'show_img'            => boolval( $attributes['featuredImage'] ),
			'img_size'            => strval( $attributes['featuredImageSize'] ),
			'is_player_below'     => boolval( $attributes['playerBelowExcerpt'] ),
			'show_excerpt'        => boolval( $attributes['excerpt'] ),
			'columns_per_row'     => intval( $attributes['columnsPerRow'] ),
			'title_size'          => intval( $attributes['titleSize'] ),
			'title_under_img'     => intval( $attributes['titleUnderImage'] ),
		);

		$podcast_list = $this->renderer->fetch( 'blocks/podcast-list', $args );

		return apply_filters( 'podcast_list_dynamic_block_html_content', $podcast_list );
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

		// Fix for the new Default Series, now 0 becomes default series ID
		if ( ! $query_args['podcast_id'] ) {
			$query_args['podcast_id'] = ssp_get_default_series_id();
		}

		// -1 stands for all episodes ( option "-- All --" )
		if ( - 1 != $query_args['podcast_id'] ) {
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

		$dependencies = $this->asset_file['dependencies'];

		// Dependency wp-edit-post is needed only for PostPublishPanel block, and it leads to a warning on widgets page.
		// So, we can safely remove it since it's automatically included on post edit pages.
		$dependencies = array_diff( $dependencies, array( 'wp-edit-post' ) );

		wp_register_script(
			'ssp-block-script',
			esc_url( SSP_PLUGIN_URL . 'build/index.js' ),
			$dependencies,
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

		$this->register_podcast_list();
	}



	protected function register_podcast_list() {

		wp_register_style( 'ssp-podcast-list', esc_url( $this->assets_url . 'css/blocks/podcast-list' . $this->script_suffix . '.css' ), array(), $this->version );

		add_action( 'admin_enqueue_scripts', function(){
			wp_enqueue_style( 'ssp-podcast-list' );
		});

		add_action( 'wp_enqueue_scripts', function () {
			if ( function_exists( 'has_block' ) && has_block( 'seriously-simple-podcasting/podcast-list' ) ) {
				wp_enqueue_style( 'ssp-podcast-list' );
			}
		} );

		$default_series_id = ssp_get_default_series_id();

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
							),
							array_map( function ( $item ) use ( $default_series_id ) {
								$label = $default_series_id === $item->term_id ?
									     ssp_get_default_series_name( $item->name ) :
									     $item->name;
								return array(
									'label' => $label,
									'value' => $item->term_id,
								);
							}, ssp_get_podcasts() ) ),
					),
					'selectedPodcast' => array(
						'type'    => 'string',
						'default' => '-1',
					),
					'defaultPodcastId' => array(
						'type'    => 'string',
						'default' => $default_series_id,
					),
					// Use string everywhere instead of number because of the WP bug.
					// It doesn't show the saved value in the admin after page refresh.
					'postsPerPage' => array(
						'type'    => 'string',
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
					'columnsPerRow' => array(
						'type'    => 'string',
						'default' => 1,
					),
					'titleSize' => array(
						'type'    => 'string',
						'default' => 16,
					),
					'titleUnderImage' => array(
						'type'    => 'boolean',
						'default' => false,
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
