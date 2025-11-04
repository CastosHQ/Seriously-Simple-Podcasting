<?php
/**
 * SSP Podcasts Gutenberg Block
 *
 * @package Seriously Simple Podcasting
 * @since 3.14.0
 */

namespace SeriouslySimplePodcasting\Integrations\Blocks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles registration and rendering of the `seriously-simple-podcasting/ssp-podcasts` block.
 */
class Ssp_Podcasts_Block {

	/**
	 * Registers block type and related assets.
	 *
	 * @return void
	 */
	public function register() {
		// Register CSS assets for the block (but don't enqueue by default).
		wp_register_style(
			'ssp-podcast-list-shortcode',
			esc_url( SSP_PLUGIN_URL . 'assets/css/podcast-list.css' ),
			array(),
			SSP_VERSION
		);

		register_block_type(
			'seriously-simple-podcasting/ssp-podcasts',
			array(
				'editor_script'   => 'ssp-block-script',
				// Load both the small editor helper styles and the full shortcode styles in the editor
				'editor_style'    => array( 'ssp-block-style', 'ssp-podcast-list-shortcode' ),
				'attributes'      => array(
					'ids'                 => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'string' ),
						'default' => array( '-1' ),
					),
					'availablePodcasts'   => array(
						'type'    => 'array',
						'default' => $this->get_podcast_settings(),
					),
					'columns'             => array(
						'type'    => 'number',
						'default' => 1,
					),
					'sort_by'             => array(
						'type'    => 'string',
						'default' => 'id',
					),
					'sort'                => array(
						'type'    => 'string',
						'default' => 'asc',
					),
					'clickable'           => array(
						'type'    => 'string',
						'default' => 'button',
					),
					'show_button'         => array(
						'type'    => 'string',
						'default' => 'true',
					),
					'show_description'    => array(
						'type'    => 'string',
						'default' => 'true',
					),
					'show_episode_count'  => array(
						'type'    => 'string',
						'default' => 'true',
					),
					'description_words'   => array(
						'type'    => 'number',
						'default' => 0,
					),
					'description_chars'   => array(
						'type'    => 'number',
						'default' => 0,
					),
					'background'          => array(
						'type'    => 'string',
						'default' => '#f8f9fa',
					),
					'background_hover'    => array(
						'type'    => 'string',
						'default' => '#e9ecef',
					),
					'button_color'        => array(
						'type'    => 'string',
						'default' => '#343a40',
					),
					'button_hover_color'  => array(
						'type'    => 'string',
						'default' => '#495057',
					),
					'button_text_color'   => array(
						'type'    => 'string',
						'default' => '#ffffff',
					),
					'button_text'         => array(
						'type'    => 'string',
						'default' => __( 'Listen Now', 'seriously-simple-podcasting' ),
					),
					'title_color'         => array(
						'type'    => 'string',
						'default' => '#6c5ce7',
					),
					'episode_count_color' => array(
						'type'    => 'string',
						'default' => '#6c757d',
					),
					'description_color'   => array(
						'type'    => 'string',
						'default' => '#6c757d',
					),
				),
				'render_callback' => array( $this, 'render_callback' ),
			)
		);
	}

	/**
	 * Render callback for the SSP Podcasts block.
	 *
	 * @since 3.14.0
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered block output.
	 */
	public function render_callback( $attributes ) {
		if ( ! shortcode_exists( 'ssp_podcasts' ) ) {
			return '';
		}

		$shortcode_params = array();
		$is_manual_sort   = isset( $attributes['sort_by'] ) && 'manual' === $attributes['sort_by'];

		foreach ( $attributes as $key => $value ) {
			if ( ! $this->is_attribute_allowed( $key ) ) {
				continue;
			}

			if ( 'ids' === $key ) {
				$shortcode_params['ids'] = $this->format_ssp_podcasts_ids_for_shortcode( $value, $is_manual_sort );
				continue;
			}

			if ( in_array( $key, $this->get_int_attribute_keys(), true ) ) {
				$shortcode_params[ $key ] = intval( $value );
				continue;
			}

			if ( in_array( $key, array( 'show_button', 'show_description', 'show_episode_count' ), true ) ) {
				$shortcode_params[ $key ] = $this->normalize_bool_to_string( $value );
				continue;
			}

			$shortcode_params[ $key ] = is_scalar( $value ) ? $value : '';
		}

		$podcast_list = new \SeriouslySimplePodcasting\ShortCodes\Podcast_List();
		return $podcast_list->shortcode( $shortcode_params );
	}

	/**
	 * Allowed attribute keys passed to shortcode layer.
	 *
	 * @return array
	 */
	protected function get_allowed_attribute_keys() {
		return array(
			'ids',
			'columns',
			'sort_by',
			'sort',
			'clickable',
			'show_button',
			'show_description',
			'show_episode_count',
			'description_words',
			'description_chars',
			'background',
			'background_hover',
			'button_color',
			'button_hover_color',
			'button_text_color',
			'button_text',
			'title_color',
			'episode_count_color',
			'description_color',
		);
	}

	/**
	 * Whether the provided attribute key is allowed.
	 *
	 * @param string $key Attribute key.
	 * @return bool
	 */
	protected function is_attribute_allowed( $key ) {
		return in_array( $key, $this->get_allowed_attribute_keys(), true );
	}

	/**
	 * Integer attribute keys.
	 *
	 * @return array
	 */
	protected function get_int_attribute_keys() {
		return array( 'columns', 'description_words', 'description_chars' );
	}

	/**
	 * Normalize boolean-like values to 'true' or 'false'.
	 *
	 * @param mixed $value Value to normalize.
	 * @return string 'true' or 'false'.
	 */
	protected function normalize_bool_to_string( $value ) {
		return ( true === $value || 'true' === $value || '1' === $value || 1 === $value ) ? 'true' : 'false';
	}

	/**
	 * Format ids attribute to shortcode-compatible value.
	 *
	 * @param mixed $value Attribute value.
	 * @param bool  $is_manual_sort Whether manual sort is active.
	 * @return string
	 */
	protected function format_ssp_podcasts_ids_for_shortcode( $value, $is_manual_sort ) {
		if ( ! is_array( $value ) ) {
			return '';
		}

		$ids_array = array_map( 'strval', $value );

		if ( $is_manual_sort ) {
			$ids_array = array_values(
				array_filter(
					$ids_array,
					function ( $v ) {
						return '' !== $v && '-1' !== $v;
					}
				)
			);
		}

		if ( empty( $ids_array ) ) {
			return '__none__';
		}

		if ( in_array( '-1', $ids_array, true ) ) {
			return '';
		}

		return implode( ',', $ids_array );
	}

	/**
	 * Gets podcast settings for block attributes (availablePodcasts list).
	 *
	 * @return array
	 */
	protected function get_podcast_settings() {
		$default_series_id = ssp_get_default_series_id();

		return array_merge(
			array(
				array(
					'label' => __( '-- All --', 'seriously-simple-podcasting' ),
					'value' => - 1,
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
