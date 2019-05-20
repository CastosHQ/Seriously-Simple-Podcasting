<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Helpers\Log_Helper;

class Options_Handler {

	/**
	 * Build options fields
	 *
	 * @return array Fields to be displayed on options page.
	 */
	public function options_fields() {
		global $wp_post_types;

		$post_type_options = array();

		// Set options for post type selection.
		foreach ( $wp_post_types as $post_type => $data ) {

			$disallowed_post_types = array(
				'page',
				'attachment',
				'revision',
				'nav_menu_item',
				'wooframework',
				'podcast',
			);
			if ( in_array( $post_type, $disallowed_post_types, true ) ) {
				continue;
			}

			$post_type_options[ $post_type ] = $data->labels->name;
		}

		$options = array();

		$subscribe_options_array = $this->get_subscribe_field_options();

		$options['general'] = array(
			'title'       => __( 'General', 'seriously-simple-podcasting' ),
			'description' => __( 'General Settings', 'seriously-simple-podcasting' ),
			'fields'      => $subscribe_options_array,
		);

		$options = apply_filters( 'ssp_options_fields', $options );

		return $options;
	}

	/**
	 * Inject HTML content into the Options form
	 *
	 * @return string
	 */
	public function get_extra_html_content() {
		// Add the 'Add new subscribe option'
		$html  = '';
		$html .= '<p class="add">' . "\n";
		$html .= '<input id="ssp-options-add-subscribe" type="button" class="button-primary" value="' . esc_attr( __( 'Add subscribe option', 'seriously-simple-podcasting' ) ) . '" />' . "\n";
		$html .= '</p>' . "\n";

		return $html;
	}

	/**
	 * Builds the array of field settings for the subscribe links, based on the options stored in the options table.
	 * // @todo this is duplicated from the settings handler, so it should probably be placed in it's own class somewhere
	 *
	 * @return array
	 */
	public function get_subscribe_field_options() {
		$subscribe_field_options = array();
		$subscribe_options       = get_option( 'ss_podcasting_subscribe_options', array() );
		$logger                  = new Log_Helper();
		$logger->log( 'Subscribe Options', $subscribe_options );
		if ( empty( $subscribe_options ) ) {
			return $subscribe_field_options;
		}

		$count = 1;
		foreach ( $subscribe_options as $key => $title ) {
			$subscribe_field_options[] = array(
				'id'          => 'subscribe_option_' . $count,
				// translators: %s: Service title eg iTunes
				'label'       => sprintf( __( 'Subscribe option %s', 'seriously-simple-podcasting' ), $count ),
				// translators: %s: Service title eg iTunes
				'description' => sprintf( __( 'Subscribe option %s.', 'seriously-simple-podcasting' ), $count ),
				'type'        => 'text',
				'default'     => $title,
				// translators: %s: Service title eg iTunes
				'placeholder' => sprintf( __( 'Subscribe option %s', 'seriously-simple-podcasting' ), $count ),
				'callback'    => 'wp_strip_all_tags',
				'class'       => 'text subscribe-option',
			);
			$count++;
		}

		return apply_filters( 'ssp_subscribe_field_options', $subscribe_field_options );
	}

	/**
	 * Update the ss_podcasting_subscribe_options array based on the individual ss_podcasting_subscribe_option_ options
	 *
	 * @return bool
	 */
	public function update_subscribe_options() {
		$continue          = true;
		$count             = 0;
		$subscribe_options = array();
		while ( false !== $continue ) {
			$count ++;
			$subscribe_option = get_option( 'ss_podcasting_subscribe_option_' . $count, '' );
			if ( empty( $subscribe_option ) ) {
				$continue = false;
			} else {
				$subscribe_key                       = $this->create_subscribe_option_key( $subscribe_option );
				$subscribe_options[ $subscribe_key ] = $subscribe_option;
			}
		}
		update_option( 'ss_podcasting_subscribe_options', $subscribe_options );

		return true;
	}

	public function insert_subscribe_option() {
		$subscribe_options            = get_option( 'ss_podcasting_subscribe_options', array() );
		$subscribe_options['new_url'] = 'New Option';
		update_option( 'ss_podcasting_subscribe_options', $subscribe_options );

		return $subscribe_options;
	}

	/**
	 * Converts the Subscribe option label to the relevant key
	 *
	 * @param string $subscribe_option
	 *
	 * @return string $subscribe_key
	 */
	public function create_subscribe_option_key( $subscribe_option ) {
		$subscribe_key = preg_replace( '/[^A-Za-z]/', '_', $subscribe_option );
		$subscribe_key = strtolower( $subscribe_key . '_url' );

		return $subscribe_key;
	}
}
