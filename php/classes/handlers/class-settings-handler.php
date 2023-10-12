<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Interfaces\Service;

/**
 * SSP Settings Handler
 *
 * @package Seriously Simple Podcasting
 */
class Settings_Handler implements Service {

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page.
	 */
	public function settings_fields() {
		$settings = array(
			'general'         => $this->get_general_settings(),
			'player-settings' => $this->get_player_settings(),
			'feed-details'    => $this->get_feed_settings(),
			'security'        => $this->get_security_settings(),
			'publishing'      => $this->get_publishing_settings(),
			'castos-hosting'  => $this->get_hosting_settings(),
			'import'          => $this->get_import_settings(),
			'extensions'      => $this->get_extensions_settings(),
		);

		$integrations = $this->get_integrations_settings();
		if ( $integrations ) {
			$settings['integrations'] = $integrations;
		}

		return apply_filters( 'ssp_settings_fields', $settings );
	}

	/**
	 * General settings
	 *
	 * @return array
	 */
	public function get_general_settings() {
		global $wp_post_types; // Todo: get rid of global here

		$post_type_options = array();

		// Set options for post type selection.
		foreach ( $wp_post_types as $post_type => $data ) {

			$disallowed_post_types = array(
				'page',
				'attachment',
				'revision',
				'nav_menu_item',
				'wooframework',
				SSP_CPT_PODCAST,
			);
			if ( in_array( $post_type, $disallowed_post_types, true ) ) {
				continue;
			}

			$post_type_options[ $post_type ] = $data->labels->name;
		}

		return ssp_config( 'settings/general', compact( 'post_type_options' ) );
	}

	/**
	 * Player settings
	 *
	 * @return array
	 */
	public function get_feed_settings() {
		// translators: placeholders are simply html tags to break up the content.
		return array(
			'title'       => __( 'Feed details', 'seriously-simple-podcasting' ),
			'description' => sprintf( __( 'This data will be used in the feed for your podcast so your listeners will know more about it before they subscribe.%1$sAll of these fields are optional, but it is recommended that you fill in as many of them as possible. Blank fields will use the assigned defaults in the feed.%2$s', 'seriously-simple-podcasting' ), '<br/><em>', '</em>' ),
			'fields'      => $this->get_feed_fields(),
		);
	}

	/**
	 * Security settings
	 *
	 * @return array
	 */
	public function get_security_settings() {
		$protection_password_callback = array( $this, 'encode_password' );
		$validate_message_callback    = array( $this, 'validate_message' );

		return ssp_config( 'settings/security', compact( 'protection_password_callback', 'validate_message_callback' ) );
	}

	/**
	 * @return array
	 */
	public function get_import_settings() {
		return ssp_config( 'settings/import' );
	}

	/**
	 * @return array|null
	 */
	public function get_integrations_settings() {
		$integrations = ssp_config( 'settings/integrations' );
		$integrations = apply_filters( 'ssp_integration_settings', $integrations);

		if ( empty( $integrations['items'] ) ) {
			return null;
		}

		return $integrations;
	}

	/**
	 * @return array
	 */
	public function get_extensions_settings() {
		return ssp_config( 'settings/extensions' );
	}

	/**
	 * @return array
	 */
	public function get_hosting_settings() {
		$podcast_options[0] = __( 'Default Podcast', 'seriously-simple-podcasting' );
		$podcasts           = ssp_get_podcasts();
		$podcast_options    += array_combine(
			array_map( function ( $i ) {
				return $i->term_id;
			}, $podcasts ),
			array_map( function ( $i ) {
				return $i->name;
			}, $podcasts )
		);

		return ssp_config( 'settings/hosting', compact( 'podcast_options' ) );
	}

	/**
	 * @return array
	 */
	public function get_publishing_settings(){
		return ssp_config( 'settings/publishing' );
	}

	/**
	 * Player settings
	 *
	 * @return array
	 */
	public function get_player_settings() {
		$player_style             = ssp_get_option( 'player_style', 'larger' );
		$is_meta_data_enabled     = $this->is_player_meta_data_enabled();
		$is_custom_colors_enabled = $this->is_player_custom_colors_enabled();
		$color_settings           = $is_custom_colors_enabled ? $this->get_player_color_settings() : array();

		return ssp_config( 'settings/player', compact(
			'player_style', 'is_meta_data_enabled', 'is_custom_colors_enabled', 'color_settings'
		) );
	}

	/**
	 * @return bool
	 */
	public function is_player_meta_data_enabled(){
		return 'on' === ssp_get_option( 'player_meta_data_enabled', 'on' );
	}

	/**
	 * @return bool
	 */
	public function is_player_custom_colors_enabled() {
		return 'on' === ssp_get_option( 'player_custom_colors_enabled' );
	}

	/**
	 * @return array
	 */
	public function get_player_color_settings() {
		$settings = ssp_config( 'settings/player-color' );
		return apply_filters( 'ssp_player_color_settings', $settings );
	}

	/**
	 * @return array
	 */
	public function get_feed_fields() {
		$title  = $this->get_current_feed_option( 'data_title' );
		$author = $this->get_current_feed_option( 'data_author' );

		$feed_details_fields     = ssp_config( 'settings/feed', compact( 'title', 'author' ) );
		$subscribe_options_array = $this->get_subscribe_field_options();

		return array_merge( $feed_details_fields, $subscribe_options_array );
	}

	/**
	 * This function gets option value for the feed details page ( Podcasting -> Settings -> Feed Details )
	 *
	 * @param string $option
	 *
	 * @return string
	 */
	protected function get_current_feed_option( $option ) {
		$podcast_id = $this->get_current_feed_series_id();

		return ssp_get_option( $option, '', $podcast_id );
	}

	/**
	 * This function gets current podcast ID for the feed details page ( Podcasting -> Settings -> Feed Details )
	 *
	 * @return int
	 */
	protected function get_current_feed_series_id() {
		$podcast_slug = filter_input( INPUT_GET, 'feed-series' );
		if ( ! $podcast_slug ) {
			return 0;
		}

		$podcast = get_term_by( 'slug', $podcast_slug, 'series' );

		return isset( $podcast->term_id ) ? $podcast->term_id : 0;
	}

	/**
	 * Encode feed password
	 *
	 * @param string $password User input
	 *
	 * @return string           Encoded password
	 */
	public function encode_password( $password ) {

		if ( $password && strlen( $password ) > 0 && '' !== $password ) {
			$password = md5( $password );
		} else {
			$option   = get_option( 'ss_podcasting_protection_password' );
			$password = $option;
		}

		return $password;
	}

	/**
	 * Validate protectino message
	 *
	 * @param string $message User input
	 *
	 * @return string          Validated message
	 */
	public function validate_message( $message ) {

		if ( $message ) {

			$allowed = array(
				'a'      => array(
					'href'   => array(),
					'title'  => array(),
					'target' => array(),
				),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'p'      => array(),
			);

			$message = wp_kses( $message, $allowed );
		}

		return $message;
	}

	/**
	 * Builds the array of field settings for the subscribe links, based on the options stored in the options table.
	 *
	 * @return array
	 */
	public function get_subscribe_field_options() {
		$subscribe_field_options[] = array(
			'id'          => '',
			'label'       => __( 'Subscribe button links', 'seriously-simple-podcasting' ),
			'description' => __( 'To create Subscribe Buttons for your site visitors, enter the Distribution URL to your show in the directories below.', 'seriously-simple-podcasting' ),
			'type'        => '',
			'placeholder' => __( 'Subscribe button links', 'seriously-simple-podcasting' ),
		);

		$options_handler             = new Options_Handler();
		$available_subscribe_options = $options_handler->available_subscribe_options;

		$subscribe_options = get_option( 'ss_podcasting_subscribe_options', array() );
		if ( empty( $subscribe_options ) ) {
			return $subscribe_field_options;
		}

		if ( isset( $_GET['feed-series'] ) && 'default' !== $_GET['feed-series'] ) {
			$feed_series_slug = sanitize_text_field( $_GET['feed-series'] );
			$series           = get_term_by( 'slug', $feed_series_slug, 'series' );
			$series_id        = $series->ID;
		}

		foreach ( $subscribe_options as $option_key ) {
			if ( isset( $available_subscribe_options[ $option_key ] ) ) {
				if ( isset( $series_id ) ) {
					$field_id = $option_key . '_url_' . $series_id;
					$value    = get_option( 'ss_podcasting_' . $field_id );
				} else {
					$field_id = $option_key . '_url';
					$value    = get_option( 'ss_podcasting_' . $field_id );
				}
			} else {
				continue;
			}
			$subscribe_field_options[] = array(
				'id'          => $field_id,
				// translators: %s: Service title eg iTunes
				'label'       => sprintf( __( '%s URL', 'seriously-simple-podcasting' ), $available_subscribe_options[ $option_key ] ),
				// translators: %s: Service title eg iTunes
				'description' => sprintf( __( 'Your podcast\'s %s URL.', 'seriously-simple-podcasting' ), $available_subscribe_options[ $option_key ] ),
				'type'        => 'text',
				'default'     => $value,
				// translators: %s: Service title eg iTunes
				'placeholder' => sprintf( __( '%s URL', 'seriously-simple-podcasting' ), $available_subscribe_options[ $option_key ] ),
				'callback'    => 'esc_url_raw',
				'class'       => 'regular-text',
			);
		}

		return $subscribe_field_options;
	}

	/**
	 * Get the field option
	 *
	 * @param $field_id
	 * @param bool $default
	 *
	 * @return false|mixed|void
	 * @since 5.7.0
	 */
	public function get_field( $field_id, $default = false ) {
		return get_option( 'ss_podcasting_' . $field_id, $default );
	}

	/**
	 * Set the field option
	 *
	 * @param string $field_id
	 * @param string $value
	 *
	 * @return bool
	 * @since 5.7.0
	 */
	public function set_field( $field_id, $value ) {
		return update_option( 'ss_podcasting_' . $field_id, $value );
	}
}
