<?php

namespace SeriouslySimplePodcasting\Widgets;

use SeriouslySimplePodcasting\Renderers\Renderer;
use WP_Widget;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seriously Simple Podcasting Abstract Widget Class
 *
 * @author    Sergey Zakharchenko
 * @package   SeriouslySimplePodcasting
 * @category  SeriouslySimplePodcasting/Widgets
 * @since     2.7.4
 */
abstract class Castos_Widget extends WP_Widget {
	protected $widget_cssclass;
	protected $widget_description;
	protected $widget_idbase;
	protected $widget_title;
	protected $renderer;

	public function __construct( $id_base ) {
		// Widget variable settings
		$this->widget_cssclass    = 'widget_podcast_playlist';
		$this->widget_description = __( 'Display a playlist of episodes.', 'seriously-simple-podcasting' );
		$this->widget_idbase      = 'ss_podcast';
		$this->widget_title       = __( 'Podcast: Playlist', 'seriously-simple-podcasting' );
		$this->renderer           = new Renderer();

		// Widget settings
		$widget_ops = array(
			'classname'                   => $this->widget_cssclass,
			'description'                 => $this->widget_description,
			'customize_selective_refresh' => true,
		);

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );

		parent::__construct( $id_base, $this->widget_title, $widget_ops );
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		foreach ( $this->get_fields() as $field ) {
			$id              = $field['id'];
			$instance[ $id ] = isset( $new_instance[ $id ] ) ? $new_instance[ $id ] : '';

			if ( isset( $new_instance[ $id ] ) ) {
				$new_value = $new_instance[ $id ];
				if ( is_string( $new_value ) ) {
					$new_value = strip_tags( $new_value );
				}

				$instance[ $id ] = $new_value;
			} else {
				$instance[ $id ] = '';
			}
		}

		$this->flush_widget_cache();

		return $instance;
	}

	public function flush_widget_cache() {
		wp_cache_delete( 'widget_podcast_playlist', 'widget' );
	}

	public function form( $instance ) {
		foreach ( $this->get_fields() as $field ) {
			$field['value']      = isset( $instance[ $field['id'] ] ) ? esc_attr( $instance[ $field['id'] ] ) : '';
			$field['field_id']   = $this->get_field_id( $field['id'] );
			$field['field_name'] = $this->get_field_name( $field['id'] );

			echo $this->render_field( $field );
		}
	}

	protected function render_field( $field ) {
		if ( empty( $field['type'] ) ) {
			return '';
		}

		return $this->renderer->render( $field, sprintf( 'widget/fields/%s', $field['type'] ) );
	}

	public function widget( $args, $instance ) {
		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'widget_podcast_playlist', 'widget' );
		}

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];

			return;
		}

		ob_start();

		$title = $instance['title'];

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		echo $this->get_widget_body( $instance );

		echo $args['after_widget'];

		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ] = ob_get_flush();
			wp_cache_set( 'widget_podcast_series', $cache, 'widget' );
		} else {
			ob_end_flush();
		}
	}

	abstract protected function get_fields();

	abstract protected function get_widget_body( $instance );
}
