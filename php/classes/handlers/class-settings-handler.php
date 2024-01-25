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
	 * @var array
	 * */
	protected $feed_fields;

	/**
	 * @var int
	 * */
	protected $default_series_id;

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
			'description' => sprintf(
				__( 'This data will be used in the feed for your podcast so your listeners will know more about it before they subscribe. %1$sIt is recommended that you fill in as many fields as possible (that apply to your podcast), however, some fields are required to satisfy Podcast RSS validation requirements.%2$s%3$sTo learn more about Podcast RSS Feed requirements, %4$sclick here%5$s.', 'seriously-simple-podcasting' ),
				'<br/><em>', '</em>', '<br/>',
				'<a target="_blank" href="https://support.castos.com/article/196-podcast-rss-feed-requirements">', '</a>'
			),
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
		$podcast_options = $this->get_podcasts_list();

		return ssp_config( 'settings/hosting', compact( 'podcast_options' ) );
	}

	/**
	 * @return array|false
	 */
	protected function get_podcasts_list() {
		$default_podcast_id = $this->default_series_id();
		$podcasts           = ssp_get_podcasts();
		foreach ( $podcasts as $podcast ) {
			if ( $default_podcast_id === $podcast->term_id ) {
				$podcast->name = ssp_get_default_series_name( $podcast->name );
				break;
			}
		}

		return array_combine(
			array_map( function ( $i ) {
				return $i->term_id;
			}, $podcasts ),
			array_map( function ( $i ) {
				return $i->name;
			}, $podcasts )
		);
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
	 * @param string $id
	 *
	 * @return array|null
	 */
	public function get_field_by_id( $id ) {
		$feed_fields = $this->get_feed_fields();

		foreach ( $feed_fields as $feed_field ) {
			if ( $id === $feed_field['id'] ) {
				return $feed_field;
			}
		}

		return null;
	}

	/**
	 * Gets the feed option, if it's empty, tries to get options from the default feed in some cases.
	 *
	 * Since version 3.0, we use the Default Series settings, that should replace the default feed settings
	 *
	 * @param string|array $field
	 * @param int $series_id
	 * @param string $default
	 *
	 * @return string|null
	 * @since 3.0.0
	 */
	public function get_feed_option( $field, $series_id, $default = '' ) {

		if ( is_string( $field ) ) {
			$field = $this->get_field_by_id( $field );
		}

		$option = $field['id'];

		if ( 'data_image' === $option ) {
			return $this->get_feed_image( $series_id );
		}

		if ( 'data_title' === $option ) {
			return $this->get_feed_title( $series_id );
		}

		$data = ssp_get_option( $option, null, $series_id );

		// For empty values, propagate some settings from the default feed
		if ( ! isset( $data ) ) {
			$propagate_exclusions = array( 'exclude_feed', 'redirect_feed' );
			$propagated_types     = array( 'checkbox', 'select' );
			$propagate            = in_array( $field['type'], $propagated_types, true ) &&
			                        ! in_array( $field['id'], $propagate_exclusions, true );
			$default_series_id    = $this->default_series_id();

			if ( $propagate && ( $series_id != $default_series_id ) ) {
				$data = ssp_get_option( $option, null, $default_series_id );
			}
		}

		if ( ! isset( $data ) ) {
			$data = isset( $field['default'] ) ? $field['default'] : $default;
		}

		return $data;
	}

	/**
	 * @return int
	 */
	public function default_series_id() {
		if ( ! isset( $this->default_series_id ) ) {
			$this->default_series_id = ssp_get_default_series_id();
		}

		return $this->default_series_id;
	}

	/**
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_feed_title( $series_id ) {
		$title = ssp_get_option( 'data_title', '', $series_id );
		if ( ! $title ) {
			$term = get_term_by( 'id', $series_id, ssp_series_taxonomy() );
			if ( ! empty( $term->name ) ) {
				$title = $term->name;
			}
		}
		if ( ! $title ) {
			$title = get_bloginfo( 'name' );
		}

		return $title;
	}

	/**
	 * @param int $series_id
	 *
	 * @return string
	 */
	public function get_feed_image( $series_id ){
		// If it's series feed, try to use its own feed image.
		if ( $series_id ) {
			$image = ssp_get_option( 'data_image', null, $series_id );
		}

		// If couldn't show the series feed image, try to use the series taxonomy image
		if ( ! isset( $image ) || ! ssp_is_feed_image_valid( $image ) ) {
			$image = ssp_get_podcast_image_src( get_term_by( 'id', $series_id, ssp_series_taxonomy() ), 'full' );
		}

		// If couldn't show the series image, try to use the default series cover image.
		if ( ! isset( $image ) || ! ssp_is_feed_image_valid( $image ) ) {
			$image = ssp_get_option( 'data_image', null, ssp_get_default_series_id() );
		}

		if ( ! isset( $image ) || ! ssp_is_feed_image_valid( $image ) ) {
			$image = '';
		}

		return $image;
	}


	/**
	 * @param int|null $podcast_id
	 *
	 * @return array
	 */
	public function get_feed_fields( $podcast_id = null ) {
		if ( $this->feed_fields ) {
			return $this->feed_fields;
		}

		$podcast_id       = $podcast_id ?: $this->get_current_series_id();
		$title            = ssp_get_option( 'data_title', '', $podcast_id );
		$author           = ssp_get_option( 'data_author', '', $podcast_id );
		$site_title       = get_bloginfo( 'name' );
		$site_description = get_bloginfo( 'description' );
		$categories       = ssp_config( 'settings/feed-categories' );
		$subcategories    = ssp_config( 'settings/feed-subcategories' );
		$language         = get_bloginfo( 'language' );
		$is_default       = $podcast_id === $this->default_series_id();

		$feed_details_fields = ssp_config(
			'settings/feed',
			compact( 'title', 'author', 'site_title', 'site_description', 'categories', 'subcategories', 'language', 'is_default' )
		);

		$subscribe_options_array = $this->get_subscribe_field_options( $podcast_id );

		$this->feed_fields = array_merge( $feed_details_fields, $subscribe_options_array );

		return $this->feed_fields;
	}

	/**
	 * This function gets current podcast ID for the feed details page ( Podcasting -> Settings -> Feed Details )
	 *
	 * @return int
	 */
	protected function get_current_series_id() {
		$podcast_slug = filter_input( INPUT_GET, 'feed-series' );
		if ( $podcast_slug ) {
			$podcast = get_term_by( 'slug', $podcast_slug, 'series' );

			return isset( $podcast->term_id ) ? $podcast->term_id : 0;
		}

		$term_id = filter_input( INPUT_GET, 'tag_ID', FILTER_VALIDATE_INT );

		return $term_id;
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
	 * @param int $series_id
	 *
	 * @return array
	 */
	public function get_subscribe_field_options( $series_id ) {
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

		foreach ( $subscribe_options as $option_key ) {
			if ( ! isset( $available_subscribe_options[ $option_key ] ) ) {
				continue;
			}

			$field_id = $option_key . '_url';
			$value = ssp_get_option( $field_id, '', $series_id );

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
}
