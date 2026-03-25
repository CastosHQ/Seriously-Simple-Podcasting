<?php

namespace SeriouslySimplePodcasting\Integrations\Blocks;

use SeriouslySimplePodcasting\Entities\Available_Podcasts_Attribute;
use SeriouslySimplePodcasting\Entities\Available_Tags_Attribute;
use SeriouslySimplePodcasting\ShortCodes\Podcast_Playlist;

/**
 * Handles registration and rendering of the `seriously-simple-podcasting/playlist-player` block.
 *
 * @package Seriously Simple Podcasting
 * @since 2.0.4
 */
class Playlist_Player_Block {

	/**
	 * Registers the block type.
	 *
	 * @return void
	 */
	public function register() {
		register_block_type(
			'seriously-simple-podcasting/playlist-player',
			array(
				'attributes'      => array(
					'availablePodcasts' => array(
						'type'    => 'array',
						'default' => new Available_Podcasts_Attribute(),
					),
					'selectedPodcast'   => array(
						'type'    => 'string',
						'default' => '-1',
					),
					'availableTags'     => array(
						'type'    => 'array',
						'default' => new Available_Tags_Attribute(),
					),
					'selectedTag'       => array(
						'type'    => 'string',
						'default' => '',
					),
					// Use string everywhere instead of number because of the WP bug.
					// It doesn't show the saved value in the admin after page refresh.
					'limit'             => array(
						'type'    => 'string',
						'default' => -1,
					),
					'orderBy'           => array(
						'type'    => 'string',
						'default' => 'date',
					),
					'order'             => array(
						'type'    => 'string',
						'default' => 'desc',
					),
				),
				'render_callback' => array( $this, 'render_callback' ),
			)
		);
	}

	/**
	 * Render callback for the playlist player block.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Rendered playlist HTML.
	 */
	public function render_callback( $attributes ) {
		$podcast_id = ( '' === $attributes['selectedPodcast'] ) ? -1 : intval( $attributes['selectedPodcast'] );
		$args       = array();

		if ( $podcast_id ) {
			$args['series'] = $this->get_term_slug_by_id( $podcast_id );
		}

		if ( ! empty( $attributes['selectedTag'] ) ) {
			$args['tag'] = $attributes['selectedTag'];
		}

		if ( ! empty( $attributes['limit'] ) ) {
			$args['limit'] = $attributes['limit'];
		}

		if ( ! empty( $attributes['orderBy'] ) ) {
			$args['orderby'] = $attributes['orderBy'];
		}

		if ( ! empty( $attributes['order'] ) ) {
			$args['order'] = $attributes['order'];
		}

		if ( ! empty( $attributes['className'] ) ) {
			$args['class'] = $attributes['className'];
		}

		$podcast_playlist = new Podcast_Playlist();

		return $podcast_playlist->shortcode( $args );
	}

	/**
	 * Gets term slug by ID.
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return string|null Term slug or null if not found.
	 */
	protected function get_term_slug_by_id( $term_id ) {
		$term = get_term_by( 'id', $term_id, ssp_series_taxonomy() );

		if ( $term ) {
			return $term->slug;
		}

		return null;
	}
}
