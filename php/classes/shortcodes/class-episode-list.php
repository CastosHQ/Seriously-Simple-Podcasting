<?php
/**
 * Episode List shortcode class.
 *
 * Renders the same episode list as the podcast-list Gutenberg block,
 * using the shared Episode_List_Presenter.
 *
 * @package SeriouslySimplePodcasting
 * @since 3.15.0
 */

namespace SeriouslySimplePodcasting\ShortCodes;

use SeriouslySimplePodcasting\Presenters\Episode_List_Presenter;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Episode List Shortcode
 *
 * @author     Serhiy Zakharchenko
 * @package    SeriouslySimplePodcasting
 * @since      3.15.0
 */
class Episode_List implements Shortcode {

	/**
	 * Snake_case shortcode attr => camelCase presenter attr.
	 *
	 * @since 3.15.0
	 */
	const ATTRIBUTE_MAP = array(
		'podcast_id'           => array(
			'key'  => 'selectedPodcast',
			'type' => 'string',
		),
		'posts_per_page'       => array(
			'key'  => 'postsPerPage',
			'type' => 'string',
		),
		'order_by'             => array(
			'key'  => 'orderBy',
			'type' => 'string',
		),
		'order'                => array(
			'key'  => 'order',
			'type' => 'string',
		),
		'columns'              => array(
			'key'  => 'columnsPerRow',
			'type' => 'string',
		),
		'display_player'       => array(
			'key'  => 'player',
			'type' => 'bool',
		),
		'display_excerpt'      => array(
			'key'  => 'excerpt',
			'type' => 'bool',
		),
		'display_title'        => array(
			'key'  => 'showTitle',
			'type' => 'bool',
		),
		'display_image'        => array(
			'key'  => 'featuredImage',
			'type' => 'bool',
		),
		'image_size'           => array(
			'key'  => 'featuredImageSize',
			'type' => 'string',
		),
		'title_size'           => array(
			'key'  => 'titleSize',
			'type' => 'string',
		),
		'title_under_image'    => array(
			'key'  => 'titleUnderImage',
			'type' => 'bool',
		),
		'player_below_excerpt' => array(
			'key'  => 'playerBelowExcerpt',
			'type' => 'bool',
		),
		'pagination'           => array(
			'key'  => 'paginationType',
			'type' => 'string',
		),
		'title_color'          => array(
			'key'  => 'titleColor',
			'type' => 'string',
		),
		'layout'               => array(
			'key'  => 'layout',
			'type' => 'string',
		),
		'clickable'            => array(
			'key'  => 'clickable',
			'type' => 'string',
		),
		'button_text'          => array(
			'key'  => 'buttonText',
			'type' => 'string',
		),
		'text_color'           => array(
			'key'  => 'textColor',
			'type' => 'string',
		),
		'link_color'           => array(
			'key'  => 'linkColor',
			'type' => 'string',
		),
		'card_background'      => array(
			'key'  => 'cardBackground',
			'type' => 'string',
		),
		'button_color'         => array(
			'key'  => 'buttonColor',
			'type' => 'string',
		),
		'button_background'    => array(
			'key'  => 'buttonBackground',
			'type' => 'string',
		),
	);

	/**
	 * Episode list presenter instance.
	 *
	 * @var Episode_List_Presenter
	 */
	private $presenter;

	/**
	 * Initializes the episode list shortcode.
	 *
	 * @since 3.15.0
	 *
	 * @param Episode_List_Presenter $presenter Episode list presenter instance.
	 */
	public function __construct( $presenter ) {
		$this->presenter = $presenter;
	}

	/**
	 * Renders the episode list shortcode.
	 *
	 * @since 3.15.0
	 *
	 * @param array $params Shortcode attributes.
	 *
	 * @return string HTML output.
	 */
	public function shortcode( $params ) {
		$defaults = array(
			'podcast_id'           => '-1',
			'posts_per_page'       => '0',
			'order_by'             => 'date',
			'order'                => 'desc',
			'columns'              => '1',
			'display_player'       => 'true',
			'display_excerpt'      => 'true',
			'display_title'        => 'true',
			'display_image'        => 'false',
			'image_size'           => 'medium',
			'title_size'           => '24',
			'title_under_image'    => 'false',
			'player_below_excerpt' => 'false',
			'pagination'           => 'full',
			'title_color'          => '',
			'layout'               => 'list',
			'clickable'            => 'button',
			'button_text'          => 'Listen Now',
			'text_color'           => '',
			'link_color'           => '',
			'card_background'      => '',
			'button_color'         => '',
			'button_background'    => '',
		);

		$atts = shortcode_atts( $defaults, $params, 'ssp_episode_list' );

		// Fallback: register the style if the block system hasn't done it (e.g. Classic Editor).
		if ( ! wp_style_is( 'ssp-podcast-list', 'registered' ) ) {
			wp_register_style( 'ssp-podcast-list', esc_url( SSP_PLUGIN_URL . 'assets/css/blocks/podcast-list.css' ), array(), SSP_VERSION );
		}
		wp_enqueue_style( 'ssp-podcast-list' );

		$presenter_atts = $this->map_attributes( $atts );

		return $this->presenter->render( $presenter_atts );
	}

	/**
	 * Maps snake_case shortcode attributes to camelCase presenter attributes
	 * with proper type casting.
	 *
	 * @since 3.15.0
	 *
	 * @param array $atts Parsed shortcode attributes.
	 *
	 * @return array Presenter-compatible attributes.
	 */
	private function map_attributes( $atts ) {
		$mapped = array();

		foreach ( self::ATTRIBUTE_MAP as $shortcode_key => $config ) {
			if ( ! isset( $atts[ $shortcode_key ] ) ) {
				continue;
			}

			$value                    = $atts[ $shortcode_key ];
			$mapped[ $config['key'] ] = 'bool' === $config['type']
				? filter_var( $value, FILTER_VALIDATE_BOOLEAN )
				: $value;
		}

		return $mapped;
	}
}
