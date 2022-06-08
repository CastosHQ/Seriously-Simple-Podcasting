<?php

namespace SeriouslySimplePodcasting\Integrations\Elementor;

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Plugin;
use Elementor\Widget_Text_Editor;

class Settings_Extender {

	/**
	 * Feed hidden section ID.
	 *
	 * @since 2.14.0
	 *
	 * @var string Feed hidden section ID.
	 */
	const FEED_HIDDEN_SECTION = 'ssp_feed_hidden_section';


	public function __construct() {
		$this->add_widget_categories();

		// Feed hidden functionality.
		$this->init_feed_hidden_controls();
		$this->maybe_hide_element_content();
	}

	/**
	 * If ssp_feed_hidden settings is enabled, prevent saving the element content to the post_content.
	 *
	 * @return void
	 */
	protected function maybe_hide_element_content(){
		add_action('elementor/db/before_save', function(){
			add_filter( 'get_post_metadata', function ( $data, $post_id, $meta_key ) {
				if ( '_elementor_data' !== $meta_key ) {
					return $data;
				}

				$elements = $this->get_elements( $post_id );

				foreach ( $elements as $k => $el ) {
					$elements[ $k ] = $this->filter_feed_hidden_element( $el );
				}

				return $elements ? array( $elements ) : $data;
			}, 10, 3 );
		});
	}

	/**
	 * @param array $el Element settings
	 *
	 * @return array
	 */
	protected function filter_feed_hidden_element( $el ) {

		$hidden_by_default_widgets = array(
			'ssp-transcript',
		);

		$is_hidden_by_default = isset( $el['widgetType'] ) && in_array( $el['widgetType'], $hidden_by_default_widgets );

		$is_feed_hidden =
			( isset( $el['settings']['ssp_feed_hidden'] ) && 'yes' === $el['settings']['ssp_feed_hidden'] ) ||
			( $is_hidden_by_default && ! isset( $el['settings']['ssp_feed_hidden'] ) ); // ! isset because default values are not provided

		if ( $is_feed_hidden ) {
			$el['settings']['title']   = '';
			$el['settings']['editor']  = '';
			$el['settings']['content'] = '';
		}

		if ( ! empty( $el['elements'] ) ) {
			foreach ( $el['elements'] as $k => $v ) {
				$el['elements'][ $k ] = $this->filter_feed_hidden_element( $v );
			}
		}

		return $el;
	}

	/**
	 * Get elements from the post meta.
	 *
	 * @param int $object_id
	 * @param string $meta_key
	 *
	 * @return array
	 */
	protected function get_elements( $object_id, $meta_key = '_elementor_data' ) {
		$meta_cache = wp_cache_get( $object_id, 'post_meta' );

		if ( ! $meta_cache ) {
			// If empty, obtain it from the DB.
			$meta_cache = update_meta_cache( 'post', array( $object_id ) );
			if ( isset( $meta_cache[ $object_id ] ) ) {
				$meta_cache = $meta_cache[ $object_id ];
			} else {
				$meta_cache = null;
			}
		}

		$elements = null;

		if ( isset( $meta_cache[ $meta_key ] ) ) {
			$elements = json_decode( maybe_unserialize( $meta_cache[ $meta_key ][0] ), true );
		}

		return is_array( $elements ) ? $elements : array();
	}


	protected function add_widget_categories() {
		add_action( 'elementor/elements/categories_registered', [ $this, 'add_elementor_widget_categories' ] );
	}

	protected function init_feed_hidden_controls() {
		$supported_widgets = array(
			'text-editor',
			'ssp-transcript',
		);

		foreach ( $supported_widgets as $widget_name ) {
			add_action(
				'elementor/element/' . $widget_name . '/section_editor/after_section_end',
				array( $this, 'add_feed_hidden_settings' )
			);
		}
	}

	public function add_elementor_widget_categories( $elements_manager ) {
		$elements_manager->add_category(
			'podcasting',
			array(
				'title' => __( 'Podcasting', 'seriously-simple-podcasting' ),
				'icon'  => 'fa fa-microphone',
			)
		);
	}

	/**
	 * @param Widget_Text_Editor $element
	 *
	 * @return false|void
	 */
	public function add_feed_hidden_settings( $element ) {

		$section = self::FEED_HIDDEN_SECTION;

		$name = $element->get_unique_name();

		$exists = Plugin::instance()->controls_manager->get_control_from_stack( $name, $section );

		if ( ! is_wp_error( $exists ) ) {
			return false;
		}

		$element->start_controls_section(
			$section, array(
				'tab'   => Controls_Manager::TAB_CONTENT,
				'label' => __( 'Feed', 'seriously-simple-podcasting' ),
			)
		);

		$settings = array(
			'label'   => __( 'Hide From Podcast RSS Feed', 'seriously-simple-podcasting' ),
			'type'    => Controls_Manager::SWITCHER,
		);

		if ( 'ssp-transcript' === $name ) {
			$settings['default'] = 'yes';
		}

		$element->add_control( 'ssp_feed_hidden', $settings );

		$element->end_controls_section();
	}
}
