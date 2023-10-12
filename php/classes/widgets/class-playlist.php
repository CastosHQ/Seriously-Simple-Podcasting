<?php

namespace SeriouslySimplePodcasting\Widgets;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seriously Simple Podcasting Podcast Playlist Widget
 *
 * @author    Hugh Lashbrooke, Serhiy Zakharchenko
 * @package   SeriouslySimplePodcasting
 * @category  SeriouslySimplePodcasting/Widgets
 * @since     1.9.0
 */
class Playlist extends Castos_Widget {

	/**
	 * Constructor function.
	 * @since  1.9.0
	 */
	public function __construct() {
		$this->widget_cssclass    = 'widget_podcast_playlist';
		$this->widget_description = __( 'Display a playlist of episodes.', 'seriously-simple-podcasting' );
		$this->widget_title       = __( 'Podcast: Playlist', 'seriously-simple-podcasting' );
		$this->alt_option_name    = 'widget_podcast_playlist';

		parent::__construct( 'podcast-playlist' );
	}


	/**
	 * @param array $instance
	 *
	 * @return string
	 */
	public function get_widget_body( $instance ) {
		$shortcode = '[podcast_playlist';

		$allowed_atts = array(
			'include'      => 'episodes',
			'exclude'      => 'exclude',
			'series'       => 'series_slug',
			'player_style' => 'player_style',
			'style'        => 'color_style',
			'order'        => 'order',
			'orderby'      => 'orderby',
			'limit'        => 'limit'
		);

		foreach ( $allowed_atts as $k => $v ) {
			if ( ! empty( $instance[ $v ] ) ) {
				$shortcode .= sprintf( ' %s="%s"', $k, $instance[ $v ] );
			}
		}

		$shortcode .= ']';

		return do_shortcode( $shortcode );
	}

	/**
	 * @return array
	 */
	protected function get_series() {
		$series_terms = get_terms( 'series' );
		$series       = array();

		foreach ( $series_terms as $term ) {
			$series[ $term->slug ] = $term->name;
		}

		return $series;
	}

	/**
	 * @return array
	 */
	protected function get_fields() {
		return array(
			array(
				'type'        => 'text',
				'id'          => 'title',
				'label'       => __( 'Title (optional):', 'seriously-simple-podcasting' ),
				'placeholder' => __( 'Widget title', 'seriously-simple-podcasting' ),
			),
			array(
				'type'        => 'text',
				'id'          => 'episodes',
				'label'       => __( 'Episodes (comma-separated IDs):', 'seriously-simple-podcasting' ),
				'placeholder' => __( 'Display all episodes', 'seriously-simple-podcasting' ),
			),
			array(
				'type'        => 'text',
				'id'          => 'exclude',
				'label'       => __( 'Exclude episodes', 'seriously-simple-podcasting' ),
				'placeholder' => __( 'Do not exclude episodes', 'seriously-simple-podcasting' ),
			),
			array(
				'type'        => 'number',
				'id'          => 'limit',
				'label'       => __( 'Maximum number of episodes in list', 'seriously-simple-podcasting' ),
				'placeholder' => __( 'Do not limit number of episodes', 'seriously-simple-podcasting' ),
			),
			array(
				'type'        => 'select',
				'id'          => 'series_slug',
				'label'       => __( 'Podcast:', 'seriously-simple-podcasting' ),
				'placeholder' => __( 'Default', 'seriously-simple-podcasting' ),
				'items'       => $this->get_series(),
			),
			array(
				'type'        => 'select',
				'id'          => 'player_style',
				'label'       => __( 'Player style:', 'seriously-simple-podcasting' ),
				'placeholder' => __( 'Default', 'seriously-simple-podcasting' ),
				'items'       => array(
					'standard' => 'Standard',
					'compact'  => 'Compact',
				),
			),
			array(
				'type'        => 'select',
				'id'          => 'color_style',
				'label'       => __( 'Color style:', 'seriously-simple-podcasting' ),
				'placeholder' => __( 'Default', 'seriously-simple-podcasting' ),
				'items'       => array(
					'dark'  => __( 'Dark', 'seriously-simple-podcasting' ),
					'light' => __( 'Light', 'seriously-simple-podcasting' ),
				),
			),
			array(
				'type'        => 'select',
				'id'          => 'order',
				'label'       => __( 'Order:', 'seriously-simple-podcasting' ),
				'placeholder' => __( 'Default', 'seriously-simple-podcasting' ),
				'items'       => array(
					'asc'  => __( 'ASC', 'seriously-simple-podcasting' ),
					'desc' => __( 'DESC', 'seriously-simple-podcasting' ),
				),
			),
			array(
				'type'        => 'select',
				'id'          => 'orderby',
				'label'       => __( 'Order By:', 'seriously-simple-podcasting' ),
				'placeholder' => __( 'Default', 'seriously-simple-podcasting' ),
				'items' => array(
					'id'            => __( 'ID', 'seriously-simple-podcasting' ),
					'menu_order'    => __( 'Menu order', 'seriously-simple-podcasting' ),
					'author'        => __( 'Author', 'seriously-simple-podcasting' ),
					'title'         => __( 'Title', 'seriously-simple-podcasting' ),
					'name'          => __( 'Name', 'seriously-simple-podcasting' ),
					'type'          => __( 'Type', 'seriously-simple-podcasting' ),
					'date'          => __( 'Date', 'seriously-simple-podcasting' ),
					'modified'      => __( 'Modified', 'seriously-simple-podcasting' ),
					'comment_count' => __( 'Comment count', 'seriously-simple-podcasting' ),
				),
			),
		);
	}
}
