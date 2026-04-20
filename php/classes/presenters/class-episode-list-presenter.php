<?php

namespace SeriouslySimplePodcasting\Presenters;

use SeriouslySimplePodcasting\Controllers\Players_Controller;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;

/**
 * Prepares and renders the episode list used by the podcast-list block,
 * [ssp_episode_list] shortcode, and any other delivery mechanism.
 *
 * Presenters own the data-fetching, transformation, and rendering orchestration
 * for a specific UI component. They are consumed by blocks, shortcodes, and widgets.
 *
 * @package Seriously Simple Podcasting
 * @since 3.15.0
 */
class Episode_List_Presenter {

	/**
	 * @var Episode_Repository
	 */
	protected $episode_repository;

	/**
	 * @var Players_Controller
	 */
	protected $players_controller;

	/**
	 * @var Renderer
	 */
	protected $renderer;

	/**
	 * @param Episode_Repository $episode_repository Episode repository instance.
	 * @param Players_Controller $players_controller Players controller instance.
	 * @param Renderer           $renderer           Renderer instance.
	 */
	public function __construct( $episode_repository, $players_controller, $renderer ) {
		$this->episode_repository = $episode_repository;
		$this->players_controller = $players_controller;
		$this->renderer           = $renderer;
	}

	/**
	 * Renders the episode list HTML.
	 *
	 * Accepts the same attribute format as the podcast-list block.
	 *
	 * @since 3.15.0
	 *
	 * @param array $attributes Render attributes.
	 *
	 * @return string Rendered HTML.
	 */
	public function render( $attributes ) {
		$query_args      = $this->normalize_attributes( $attributes );
		$episodes_query  = $this->build_query( $query_args );
		$allowed_pagination = array( 'simple', 'full' );
		$pagination_type = ! empty( $attributes['paginationType'] ) && in_array( $attributes['paginationType'], $allowed_pagination, true )
			? $attributes['paginationType'] : 'simple';
		$paginate        = $this->paginate( $episodes_query, $query_args['paged'], $pagination_type );
		$template_args   = $this->prepare_template_args( $attributes, $episodes_query, $paginate, $pagination_type );

		$html = $this->renderer->fetch( 'blocks/podcast-list', $template_args );

		return apply_filters( 'podcast_list_dynamic_block_html_content', $html );
	}

	/**
	 * Converts block/shortcode attributes into WP_Query arguments.
	 *
	 * @since 3.15.0
	 *
	 * @param array $attributes Raw attributes from block or shortcode.
	 *
	 * @return array Query arguments.
	 */
	protected function normalize_attributes( $attributes ) {
		$paged            = filter_input( INPUT_GET, 'podcast_page' );
		$paged            = $paged ? $paged : 1;
		$allowed_order_by = array( 'ID', 'title', 'date', 'recorded' );

		return array(
			'post_type'      => ssp_post_types(),
			'podcast_id'     => ( '' === $attributes['selectedPodcast'] ) ? -1 : intval( $attributes['selectedPodcast'] ),
			'posts_per_page' => intval( isset( $attributes['postsPerPage'] ) ? $attributes['postsPerPage'] : get_option( 'posts_per_page', 10 ) ),
			'paged'          => $paged,
			'orderby'        => in_array( $attributes['orderBy'], $allowed_order_by, true ) ? $attributes['orderBy'] : 'date',
			'order'          => 'asc' === $attributes['order'] ? 'asc' : 'desc',
		);
	}

	/**
	 * Prepares the template arguments from attributes, query, and pagination.
	 *
	 * @since 3.15.0
	 *
	 * @param array     $attributes      Raw attributes from block or shortcode.
	 * @param \WP_Query $episodes_query  Episodes query result.
	 * @param array     $paginate        Pagination links.
	 * @param string    $pagination_type Validated pagination type.
	 *
	 * @return array Template arguments for the episode list template.
	 */
	protected function prepare_template_args( $attributes, $episodes_query, $paginate, $pagination_type = 'simple' ) {
		$allowed_layouts   = array( 'list', 'cards' );
		$allowed_clickable = array( 'button', 'card', 'title' );

		$layout    = ! empty( $attributes['layout'] ) && in_array( $attributes['layout'], $allowed_layouts, true )
			? $attributes['layout'] : 'list';
		$clickable = ! empty( $attributes['clickable'] ) && in_array( $attributes['clickable'], $allowed_clickable, true )
			? $attributes['clickable'] : 'button';

		return array(
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
			'title_color'         => sanitize_hex_color( $attributes['titleColor'] ?? '' ) ?? '',
			'layout'              => $layout,
			'clickable'           => $clickable,
			'pagination_type'     => $pagination_type,
			'button_text'         => ! empty( $attributes['buttonText'] ) ? strval( $attributes['buttonText'] ) : __( 'Listen Now', 'seriously-simple-podcasting' ),
			'text_color'          => sanitize_hex_color( $attributes['textColor'] ?? '' ) ?? '',
			'link_color'          => sanitize_hex_color( $attributes['linkColor'] ?? '' ) ?? '',
			'card_bg'             => sanitize_hex_color( $attributes['cardBackground'] ?? '' ) ?? '',
			'button_color'        => sanitize_hex_color( $attributes['buttonColor'] ?? '' ) ?? '',
			'button_bg'           => sanitize_hex_color( $attributes['buttonBackground'] ?? '' ) ?? '',
			'pagination_color'    => sanitize_hex_color( $attributes['paginationColor'] ?? '' ) ?? '',
			'pagination_active'   => sanitize_hex_color( $attributes['paginationActiveColor'] ?? '' ) ?? '',
		);
	}

	/**
	 * Builds the episode list WP_Query.
	 *
	 * @since 3.15.0
	 *
	 * @param array $args Query arguments.
	 *
	 * @return \WP_Query
	 */
	public function build_query( $args ) {
		$defaults = array(
			'post_status'    => 'publish',
			'post_type'      => SSP_CPT_PODCAST,
			'podcast_id'     => -1,
			'posts_per_page' => 10,
			'paged'          => 1,
			'orderby'        => 'date',
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

		// Fix for the new Default Series, now 0 becomes default series ID.
		if ( ! $query_args['podcast_id'] ) {
			$query_args['podcast_id'] = ssp_get_default_series_id();
		}

		// -1 stands for all episodes ( option "-- All --" ).
		if ( -1 !== $query_args['podcast_id'] ) {
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
	 * Generates pagination links for the episode list.
	 *
	 * @since 3.15.0
	 *
	 * @param \WP_Query $episodes_query  Episodes query object.
	 * @param int       $current_page    Current page number.
	 * @param string    $pagination_type Pagination type: 'simple' or 'full'.
	 *
	 * @return array Paginate links array.
	 */
	public function paginate( $episodes_query, $current_page, $pagination_type = 'simple' ) {
		$args = array(
			'format'    => '?podcast_page=%#%',
			'total'     => $episodes_query->max_num_pages,
			'current'   => $current_page,
			'prev_text' => __( '&laquo; Newer Episodes', 'seriously-simple-podcasting' ),
			'next_text' => __( 'Older Episodes &raquo;', 'seriously-simple-podcasting' ),
			'type'      => 'array',
		);

		// Filter name uses "podcast_list" (the original Gutenberg block name) rather than
		// "episode_list" (the shortcode added later) for backward compatibility — introduced in 2.24.0.
		$args = apply_filters( 'ssp_podcast_list_paginate_args', $args, $episodes_query );

		$all_links = paginate_links( $args );

		if ( 'full' === $pagination_type ) {
			$links = is_array( $all_links ) ? $all_links : array();
		} else {
			$links = array();

			if ( is_array( $all_links ) ) {
				foreach ( $all_links as $item ) {
					if ( strpos( $item, 'class="next' ) || strpos( $item, 'class="prev' ) ) {
						$links[] = $item;
					}
				}
			}
		}

		/** @see ssp_podcast_list_paginate_args for the filter naming rationale. */
		return apply_filters( 'ssp_podcast_list_paginate_links', $links, $all_links, $episodes_query, $pagination_type );
	}
}
