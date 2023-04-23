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
				SSP_CPT_PODCAST,
			);
			if ( in_array( $post_type, $disallowed_post_types, true ) ) {
				continue;
			}

			$post_type_options[ $post_type ] = $data->labels->name;
		}

		$settings = array();

		$settings['general'] = array(
			'title'       => __( 'General', 'seriously-simple-podcasting' ),
			'description' => __( 'General Settings', 'seriously-simple-podcasting' ),
			'fields'      => array(
				array(
					'id'          => 'use_post_types',
					'label'       => __( 'Podcast post types', 'seriously-simple-podcasting' ),
					'description' => __( 'Use this setting to enable podcast functions on any post type - this will add all podcast posts from the specified types to your podcast feed.', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox_multi',
					'options'     => $post_type_options,
					'default'     => array(),
				),
				array(
					'id'          => 'include_in_main_query',
					'label'       => __( 'Include podcast in main blog', 'seriously-simple-podcasting' ),
					'description' => __( 'This setting may behave differently in each theme, so test it carefully after activation - it will add the \'podcast\' post type to your site\'s main query so that your podcast episodes appear on your home page along with your blog posts.', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox',
					'default'     => '',
				),
				array(
					'id'          => 'itunes_fields_enabled',
					'label'       => __( 'Enable iTunes fields ', 'seriously-simple-podcasting' ),
					'description' => __( 'Turn this on to enable the iTunes iOS11 specific fields on each episode.', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox',
					'default'     => '',
				),
				array(
					// After 2.14.0, we renamed Series to Podcasts
					'id'          => 'series_slug',
					'label'       => __( 'Podcasts slug', 'seriously-simple-podcasting' ),
					'description' => __( 'Podcast permalink base. Please don\'t use reserved slug `podcast`.', 'seriously-simple-podcasting' ),
					'type'        => 'text',
					'default'     => ssp_series_slug(),
				),
			),
		);

		$settings['player-settings'] = $this->get_player_settings();

		$settings['feed-details'] = array(
			'title'       => __( 'Feed details', 'seriously-simple-podcasting' ),
			// translators: placeholders are simply html tags to break up the content
			'description' => sprintf( __( 'This data will be used in the feed for your podcast so your listeners will know more about it before they subscribe.%1$sAll of these fields are optional, but it is recommended that you fill in as many of them as possible. Blank fields will use the assigned defaults in the feed.%2$s', 'seriously-simple-podcasting' ), '<br/><em>', '</em>' ),
		);


		$settings['feed-details']['fields'] = $this->get_feed_fields();

		$settings['security'] = array(
			'title'       => __( 'Security', 'seriously-simple-podcasting' ),
			'description' => __( 'Change these settings to ensure that your podcast feed remains private. This will block feed readers (including iTunes) from accessing your feed.', 'seriously-simple-podcasting' ),
			'fields'      => array(
				array(
					'id'          => 'protect',
					'label'       => __( 'Password protect your podcast feed', 'seriously-simple-podcasting' ),
					'description' => __( 'Mark if you would like to password protect your podcast feed - you can set the username and password below. This will block all feed readers (including iTunes) from accessing your feed.', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox',
					'default'     => '',
					'callback'    => 'wp_strip_all_tags',
				),
				array(
					'id'          => 'protection_username',
					'label'       => __( 'Username', 'seriously-simple-podcasting' ),
					'description' => __( 'Username for your podcast feed.', 'seriously-simple-podcasting' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Feed username', 'seriously-simple-podcasting' ),
					'class'       => 'regular-text',
					'callback'    => 'wp_strip_all_tags',
				),
				array(
					'id'          => 'protection_password',
					'label'       => __( 'Password', 'seriously-simple-podcasting' ),
					'description' => __( 'Password for your podcast feed. Once saved, the password is encoded and secured so it will not be visible on this page again.', 'seriously-simple-podcasting' ),
					'type'        => 'text_secret',
					'default'     => '',
					'placeholder' => __( 'Feed password', 'seriously-simple-podcasting' ),
					'callback'    => array( $this, 'encode_password' ),
					'class'       => 'regular-text',
				),
				array(
					'id'          => 'protection_no_access_message',
					'label'       => __( 'No access message', 'seriously-simple-podcasting' ),
					'description' => __( 'This message will be displayed to people who are not allowed access to your podcast feed. Limited HTML allowed.', 'seriously-simple-podcasting' ),
					'type'        => 'textarea',
					'default'     => __( 'You are not permitted to view this podcast feed.', 'seriously-simple-podcasting' ),
					'placeholder' => __( 'Message displayed to users who do not have access to the podcast feed', 'seriously-simple-podcasting' ),
					'callback'    => array( $this, 'validate_message' ),
					'class'       => 'large-text',
				),
			),
		);

		$settings['publishing'] = array(
			'title'       => __( 'Publishing', 'seriously-simple-podcasting' ),
			'description' => __( 'Use these URLs to share and publish your podcast feed. These URLs will work with any podcasting service (including iTunes).', 'seriously-simple-podcasting' ),
			'fields'      => array(
				array(
					'id'          => 'feed_url',
					'label'       => __( 'External feed URL', 'seriously-simple-podcasting' ),
					'description' => __( 'If you are syndicating your podcast using a third-party service (like Feedburner) you can insert the URL here, otherwise this must be left blank.', 'seriously-simple-podcasting' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'External feed URL', 'seriously-simple-podcasting' ),
					'callback'    => 'esc_url_raw',
					'class'       => 'regular-text',
				),
				array(
					'id'          => 'feed_link',
					'label'       => __( 'Complete feed', 'seriously-simple-podcasting' ),
					'description' => '',
					'type'        => 'feed_link',
					'callback'    => 'esc_url_raw',
				),
				array(
					'id'          => 'feed_link_series',
					'label'       => __( 'Feed for a specific podcast', 'seriously-simple-podcasting' ),
					'description' => '',
					'type'        => 'feed_link_series',
					'callback'    => 'esc_url_raw',
				),
				array(
					'id'          => 'podcast_url',
					'label'       => __( 'Podcast page', 'seriously-simple-podcasting' ),
					'description' => '',
					'type'        => 'podcast_url',
					'callback'    => 'esc_url_raw',
				),
			),
		);

		$settings['castos-hosting'] = array(
			'title'       => __( 'Hosting', 'seriously-simple-podcasting' ),
			'description' => sprintf( __( 'Connect your WordPress site to your %s account.', 'seriously-simple-podcasting' ), '<a target="_blank" href="' . SSP_CASTOS_APP_URL . '">Castos</a>' ),
			'fields'      => array(
				array(
					'id'          => 'podmotor_account_email',
					'label'       => __( 'Your email', 'seriously-simple-podcasting' ),
					'description' => __( 'The email address you used to register your Castos account.', 'seriously-simple-podcasting' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'email@domain.com', 'seriously-simple-podcasting' ),
					'callback'    => 'esc_email',
					'class'       => 'regular-text',
				),
				array(
					'id'          => 'podmotor_account_api_token',
					'label'       => __( 'Castos API key', 'seriously-simple-podcasting' ),
					'description' => __( 'Your Castos API key. Available from your Castos account dashboard.', 'seriously-simple-podcasting' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Enter your api key', 'seriously-simple-podcasting' ),
					'callback'    => 'sanitize_text_field',
					'class'       => 'regular-text',
				),
				array(
					'id'          => 'podmotor_disconnect',
					'label'       => __( 'Disconnect Castos', 'seriously-simple-podcasting' ),
					'description' => __( 'Disconnect your Castos account.', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox',
					'default'     => '',
					'callback'    => 'wp_strip_all_tags',
					'class'       => 'disconnect-castos',
				),
			),
		);

		if ( ! ssp_is_connected_to_castos() ) {
			foreach ( $settings['castos-hosting']['fields'] as $k => $field ) {
				if ( 'podmotor_disconnect' === $field['id'] ) {
					$settings['castos-hosting']['fields'][ $k ]['type'] = 'hidden';
					break;
				}
			}
		}

		$settings['import'] = array(
			'title'       => __( 'Import', 'seriously-simple-podcasting' ),
			'description' => '',
			'fields'      => array(),
		);

		if ( ssp_is_connected_to_castos() ) {
			if ( ! ssp_get_external_rss_being_imported() ) {
				$settings['import']['fields']      = array(
					array(
						'id'          => 'podmotor_import',
						'label'       => __( 'Sync to Castos', 'seriously-simple-podcasting' ),
						'description' => __( 'Sync your podcast to your Castos hosting account.', 'seriously-simple-podcasting' ),
						'type'        => 'checkbox',
						'default'     => '',
						'callback'    => 'wp_strip_all_tags',
						'class'       => 'import-castos',
					),
				);
				$settings['import']['description'] = sprintf( __( 'Use this option for a one time sync of your existing WordPress podcast to your Castos account. If you encounter any problems with it, please contact support at hello@castos.com.', 'seriously-simple-podcasting' ), '<a href="' . SSP_CASTOS_APP_URL . '">Castos</a>' );
			}
		}

		$settings['extensions'] = array(
			'title'               => __( 'Extensions', 'seriously-simple-podcasting' ),
			'description'         => __( 'These extensions add functionality to your Seriously Simple Podcasting powered podcast.', 'seriously-simple-podcasting' ),
			'fields'              => array(),
			'disable_save_button' => true,
		);

		$settings['integrations'] = apply_filters( 'ssp_integration_settings', array(
			'title' => __( 'Integrations', 'seriously-simple-podcasting' ),
			'items' => [],
		) );

		if ( empty( $settings['integrations']['items'] ) ) {
			unset( $settings['integrations'] );
		}

		return apply_filters( 'ssp_settings_fields', $settings );
	}

	/**
	 * Player settings
	 *
	 * @return array
	 */
	public function get_player_settings() {
		$player_settings = array(
			'title'       => __( 'Player', 'seriously-simple-podcasting' ),
			'description' => __( 'Player Settings', 'seriously-simple-podcasting' ),
			'fields'      => array(
				array(
					'id'          => 'player_locations',
					'label'       => __( 'Media player locations', 'seriously-simple-podcasting' ),
					'description' => __( 'Select where to show the podcast media player along with the episode data (download link, duration and file size)', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox_multi',
					'options'     => array(
						'content'       => __( 'Full content', 'seriously-simple-podcasting' ),
						'excerpt'       => __( 'Excerpt', 'seriously-simple-podcasting' ),
						'excerpt_embed' => __( 'oEmbed Excerpt', 'seriously-simple-podcasting' ),
					),
					'default'     => 'content',
				),
				array(
					'id'          => 'player_content_location',
					'label'       => __( 'Media player position', 'seriously-simple-podcasting' ),
					'description' => __( 'Select whether to display the media player above or below the full post content.', 'seriously-simple-podcasting' ),
					'type'        => 'radio',
					'options'     => array(
						'above' => __( 'Above content', 'seriously-simple-podcasting' ),
						'below' => __( 'Below content', 'seriously-simple-podcasting' ),
					),
					'default'     => 'above',
				),
				array(
					'id'          => 'player_content_visibility',
					'label'       => __( 'Media player visibility', 'seriously-simple-podcasting' ),
					'description' => __( 'Select whether to display the media player to everybody or only logged in users.', 'seriously-simple-podcasting' ),
					'type'        => 'radio',
					'options'     => array(
						'all'         => __( 'Everybody', 'seriously-simple-podcasting' ),
						'membersonly' => __( 'Only logged in users', 'seriously-simple-podcasting' ),
					),
					'default'     => 'all',
				),
				array(
					'id'          => 'player_subscribe_urls_enabled',
					'label'       => __( 'Show subscribe urls', 'seriously-simple-podcasting' ),
					'description' => __( 'Turn on to display subscribe urls under the player', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox',
					'default'     => 'on',
				),
				array(
					'id'          => 'player_style',
					'label'       => __( 'Media player style', 'seriously-simple-podcasting' ),
					'description' => __( 'Select the style of media player you wish to display on your site.', 'seriously-simple-podcasting' ),
					'type'        => 'radio',
					'options'     => array(
						'larger'   => __( 'HTML5 Player With Album Art', 'seriously-simple-podcasting' ),
						'standard' => __( 'Standard Compact Player', 'seriously-simple-podcasting' ),
					),
					'default'     => 'larger',
				),
			),
		);

		$player_style = ssp_get_option( 'player_style', 'larger' );
		if ( 'larger' === $player_style ) {
			$html_5_player_settings = array(
				array(
					'id'          => 'player_mode',
					'label'       => __( 'Player mode', 'seriously-simple-podcasting' ),
					'description' => __( 'Choose between Dark or Light mode, depending on your theme', 'seriously-simple-podcasting' ),
					'type'        => 'radio',
					'options'     => array(
						'dark'   => __( 'Dark Mode', 'seriously-simple-podcasting' ),
						'light'  => __( 'Light Mode', 'seriously-simple-podcasting' ),
					),
					'default'     => 'dark',
				),
				array(
					'id'          => 'subscribe_button_enabled',
					'label'       => __( 'Show subscribe button', 'seriously-simple-podcasting' ),
					'description' => __( 'Turn on to display the subscribe button', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox',
					'default'     => 'on',
				),
				array(
					'id'          => 'share_button_enabled',
					'label'       => __( 'Show share button', 'seriously-simple-podcasting' ),
					'description' => __( 'Turn on to display the share button', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox',
					'default'     => 'on',
				),
			);

			$player_settings['fields'] = array_merge( $player_settings['fields'], $html_5_player_settings );
		}

		$player_settings['fields'][] = array(
			'id'          => 'player_meta_data_enabled',
			'label'       => __( 'Enable Player meta data', 'seriously-simple-podcasting' ),
			'description' => __( 'Turn this on to enable player meta data underneath the player (download link, episode duration, date recorded, etc.).', 'seriously-simple-podcasting' ),
			'type'        => 'checkbox',
			'default'     => 'on',
		);

		$meta_data_enabled = 'on' === ssp_get_option( 'player_meta_data_enabled', 'on' );

		if ( $meta_data_enabled ) {
			$meta_settings = $this->get_player_meta_settings();

			$player_settings['fields'] = array_merge( $player_settings['fields'], $meta_settings );
		}

		$player_settings['fields'][] = array(
			'id'          => 'player_custom_colors_enabled',
			'label'       => __( 'Enable Custom Player Colors', 'seriously-simple-podcasting' ),
			'description' => __( 'Turn this on to enable customer player color settings', 'seriously-simple-podcasting' ),
			'type'        => 'checkbox',
			'default'     => '',
		);

		if ( $this->is_player_custom_colors_enabled() ) {
			$color_settings = $this->get_player_color_settings();

			$player_settings['fields'] = array_merge( $player_settings['fields'], $color_settings );
		}

		return $player_settings;
	}

	/**
	 * @return bool
	 */
	public function is_player_custom_colors_enabled() {
		return 'on' === ssp_get_option( 'player_custom_colors_enabled' );
	}

	public function get_player_color_settings() {
		$settings = array(
			array(
				'id'      => 'player_text_color',
				'label'   => __( 'Player Text Color', 'seriously-simple-podcasting' ),
				'type'    => 'color',
				'css_var' => array(
					'--ssp-dark-mode-text-color',
					'--ssp-light-mode-text-color'
				),
				'default' => '#fff',
			),
			array(
				'id'      => 'player_bg_color_1',
				'label'   => __( 'Player Background Color 1', 'seriously-simple-podcasting' ),
				'type'    => 'color',
				'css_var' => array(
					'--ssp-dark-mode-bg1-color',
					'--ssp-light-mode-bg1-color',
				),
				'default' => '#24212c',
			),
			array(
				'id'      => 'player_bg_color_2',
				'label'   => __( 'Player Background Color 2', 'seriously-simple-podcasting' ),
				'type'    => 'color',
				'css_var' => array(
					'--ssp-dark-mode-bg2-color',
					'--ssp-light-mode-bg2-color',
				),
				'default' => '#383344',
			),
			array(
				'id'      => 'player_panel_bg',
				'label'   => __( 'Player Panel Background', 'seriously-simple-podcasting' ),
				'type'    => 'color',
				'css_var' => array(
					'--ssp-dark-mode-player-panel-bg',
					'--ssp-light-mode-player-panel-bg',
				),
				'default' => '#2e2a37',
			),
			array(
				'id'      => 'player_panel_bg',
				'label'   => __( 'Player Panel Background', 'seriously-simple-podcasting' ),
				'type'    => 'color',
				'css_var' => array(
					'--ssp-dark-mode-player-panel-bg',
					'--ssp-light-mode-player-panel-bg',
				),
				'default' => '#2e2a37',
			),
			array(
				'id'      => 'player_panel_input_bg',
				'label'   => __( 'Player Panel Input Background', 'seriously-simple-podcasting' ),
				'type'    => 'color',
				'css_var' => array(
					'--ssp-dark-mode-player-panel-input-bg',
					'--ssp-light-mode-player-panel-input-bg',
				),
				'default' => '#423d4c',
			),
			array(
				'id'      => 'player_progress_bar_color',
				'label'   => __( 'Player Progress Bar Color', 'seriously-simple-podcasting' ),
				'type'    => 'color',
				'css_var' => '--ssp-progress-bar-color',
				'default' => '#df4e4f',
			),
			array(
				'id'      => 'player_btn_color',
				'label'   => __( 'Player Button Color', 'seriously-simple-podcasting' ),
				'type'    => 'color',
				'css_var' => '--ssp-play-btn-color',
				'default' => '#dd4142',
			),
			array(
				'id'      => 'player_btns_opacity',
				'label'   => __( 'Player Buttons Opacity', 'seriously-simple-podcasting' ),
				'type'    => 'number',
				'step'    => 0.1,
				'min'     => 0.3,
				'max'     => 1,
				'default' => 0.5,
				'css_var' => '--ssp-player-btns-opacity',
			),
		);

		return apply_filters( 'ssp_player_color_settings', $settings );
	}

	protected function get_player_meta_settings() {
		$settings = array(
			array(
				'id'          => 'download_file_enabled',
				'label'       => __( 'Show download file link', 'seriously-simple-podcasting' ),
				'description' => __( 'Turn on to display the download file link', 'seriously-simple-podcasting' ),
				'type'        => 'checkbox',
				'default'     => 'on',
			),
			array(
				'id'          => 'play_in_new_window_enabled',
				'label'       => __( 'Show play in new window link', 'seriously-simple-podcasting' ),
				'description' => __( 'Turn on to display the play in new window link', 'seriously-simple-podcasting' ),
				'type'        => 'checkbox',
				'default'     => 'on',
			),
			array(
				'id'          => 'duration_enabled',
				'label'       => __( 'Show duration', 'seriously-simple-podcasting' ),
				'description' => __( 'Turn on to display the track duration information', 'seriously-simple-podcasting' ),
				'type'        => 'checkbox',
				'default'     => 'on',
			),
			array(
				'id'          => 'date_recorded_enabled',
				'label'       => __( 'Show recorded date', 'seriously-simple-podcasting' ),
				'description' => __( 'Turn on to display the recorded date information', 'seriously-simple-podcasting' ),
				'type'        => 'checkbox',
				'default'     => 'on',
			),
		);

		return apply_filters( 'ssp_player_meta_settings', $settings );
	}

	public function get_feed_fields(){
		// Set up available category options.
		$category_options = array(
			''                        => __( '-- None --', 'seriously-simple-podcasting' ),
			'Arts'                    => __( 'Arts', 'seriously-simple-podcasting' ),
			'Business'                => __( 'Business', 'seriously-simple-podcasting' ),
			'Comedy'                  => __( 'Comedy', 'seriously-simple-podcasting' ),
			'Education'               => __( 'Education', 'seriously-simple-podcasting' ),
			'Fiction'                 => __( 'Fiction', 'seriously-simple-podcasting' ),
			'Government'              => __( 'Government', 'seriously-simple-podcasting' ),
			'History'                 => __( 'History', 'seriously-simple-podcasting' ),
			'Health & Fitness'        => __( 'Health & Fitness', 'seriously-simple-podcasting' ),
			'Kids & Family'           => __( 'Kids & Family', 'seriously-simple-podcasting' ),
			'Leisure'                 => __( 'Leisure', 'seriously-simple-podcasting' ),
			'Music'                   => __( 'Music', 'seriously-simple-podcasting' ),
			'News'                    => __( 'News', 'seriously-simple-podcasting' ),
			'Religion & Spirituality' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			'Science'                 => __( 'Science', 'seriously-simple-podcasting' ),
			'Society & Culture'       => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			'Sports'                  => __( 'Sports', 'seriously-simple-podcasting' ),
			'Technology'              => __( 'Technology', 'seriously-simple-podcasting' ),
			'True Crime'              => __( 'True Crime', 'seriously-simple-podcasting' ),
			'TV & Film'               => __( 'TV & Film', 'seriously-simple-podcasting' ),
		);

		// Set up available sub-category options.
		$subcategory_options = array(
			''                   => __( '-- None --', 'seriously-simple-podcasting' ),
			'Books'              => array(
				'label' => __( 'Books', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Design'             => array(
				'label' => __( 'Design', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Fashion & Beauty'   => array(
				'label' => __( 'Fashion & Beauty', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Food'               => array(
				'label' => __( 'Food', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Performing Arts'    => array(
				'label' => __( 'Performing Arts', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Visual Arts'        => array(
				'label' => __( 'Visual Arts', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Careers'            => array(
				'label' => __( 'Careers', 'seriously-simple-podcasting' ),
				'group' => __( 'Business', 'seriously-simple-podcasting' ),
			),
			'Entrepreneurship'   => array(
				'label' => __( 'Entrepreneurship', 'seriously-simple-podcasting' ),
				'group' => __( 'Business', 'seriously-simple-podcasting' ),
			),
			'Investing'          => array(
				'label' => __( 'Investing', 'seriously-simple-podcasting' ),
				'group' => __( 'Business', 'seriously-simple-podcasting' ),
			),
			'Management'         => array(
				'label' => __( 'Management', 'seriously-simple-podcasting' ),
				'group' => __( 'Business', 'seriously-simple-podcasting' ),
			),
			'Marketing'          => array(
				'label' => __( 'Marketing', 'seriously-simple-podcasting' ),
				'group' => __( 'Business', 'seriously-simple-podcasting' ),
			),
			'Non-Profit'         => array(
				'label' => __( 'Non-Profit', 'seriously-simple-podcasting' ),
				'group' => __( 'Business', 'seriously-simple-podcasting' ),
			),
			'Comedy Interviews'  => array(
				'label' => __( 'Comedy Interviews', 'seriously-simple-podcasting' ),
				'group' => __( 'Comedy', 'seriously-simple-podcasting' ),
			),
			'Improv'             => array(
				'label' => __( 'Improv', 'seriously-simple-podcasting' ),
				'group' => __( 'Comedy', 'seriously-simple-podcasting' ),
			),
			'Stand-Up'           => array(
				'label' => __( 'Stand-Up', 'seriously-simple-podcasting' ),
				'group' => __( 'Comedy', 'seriously-simple-podcasting' ),
			),
			'Courses'            => array(
				'label' => __( 'Courses', 'seriously-simple-podcasting' ),
				'group' => __( 'Education', 'seriously-simple-podcasting' ),
			),
			'How To'             => array(
				'label' => __( 'How To', 'seriously-simple-podcasting' ),
				'group' => __( 'Education', 'seriously-simple-podcasting' ),
			),
			'Language Learning'  => array(
				'label' => __( 'Language Learning', 'seriously-simple-podcasting' ),
				'group' => __( 'Education', 'seriously-simple-podcasting' ),
			),
			'Self-Improvement'   => array(
				'label' => __( 'Self-Improvement', 'seriously-simple-podcasting' ),
				'group' => __( 'Education', 'seriously-simple-podcasting' ),
			),
			'Comedy Fiction'     => array(
				'label' => __( 'Comedy Fiction', 'seriously-simple-podcasting' ),
				'group' => __( 'Fiction', 'seriously-simple-podcasting' ),
			),
			'Drama'              => array(
				'label' => __( 'Drama', 'seriously-simple-podcasting' ),
				'group' => __( 'Fiction', 'seriously-simple-podcasting' ),
			),
			'Science Fiction'    => array(
				'label' => __( 'Science Fiction', 'seriously-simple-podcasting' ),
				'group' => __( 'Fiction', 'seriously-simple-podcasting' ),
			),
			'Alternative Health' => array(
				'label' => __( 'Alternative Health', 'seriously-simple-podcasting' ),
				'group' => __( 'Health & Fitness', 'seriously-simple-podcasting' ),
			),
			'Fitness'            => array(
				'label' => __( 'Fitness', 'seriously-simple-podcasting' ),
				'group' => __( 'Health & Fitness', 'seriously-simple-podcasting' ),
			),
			'Medicine'           => array(
				'label' => __( 'Medicine', 'seriously-simple-podcasting' ),
				'group' => __( 'Health & Fitness', 'seriously-simple-podcasting' ),
			),
			'Mental Health'      => array(
				'label' => __( 'Mental Health', 'seriously-simple-podcasting' ),
				'group' => __( 'Health & Fitness', 'seriously-simple-podcasting' ),
			),
			'Nutrition'          => array(
				'label' => __( 'Nutrition', 'seriously-simple-podcasting' ),
				'group' => __( 'Health & Fitness', 'seriously-simple-podcasting' ),
			),
			'Sexuality'          => array(
				'label' => __( 'Sexuality', 'seriously-simple-podcasting' ),
				'group' => __( 'Health & Fitness', 'seriously-simple-podcasting' ),
			),
			'Education for Kids' => array(
				'label' => __( 'Education for Kids', 'seriously-simple-podcasting' ),
				'group' => __( 'Kids & Family', 'seriously-simple-podcasting' ),
			),
			'Parenting'          => array(
				'label' => __( 'Parenting', 'seriously-simple-podcasting' ),
				'group' => __( 'Kids & Family', 'seriously-simple-podcasting' ),
			),
			'Pets & Animals'     => array(
				'label' => __( 'Pets & Animals', 'seriously-simple-podcasting' ),
				'group' => __( 'Kids & Family', 'seriously-simple-podcasting' ),
			),
			'Stories for Kids'   => array(
				'label' => __( 'Stories for Kids', 'seriously-simple-podcasting' ),
				'group' => __( 'Kids & Family', 'seriously-simple-podcasting' ),
			),
			'Animation & Manga'  => array(
				'label' => __( 'Animation & Manga', 'seriously-simple-podcasting' ),
				'group' => __( 'Leisure', 'seriously-simple-podcasting' ),
			),
			'Automotive'         => array(
				'label' => __( 'Automotive', 'seriously-simple-podcasting' ),
				'group' => __( 'Leisure', 'seriously-simple-podcasting' ),
			),
			'Aviation'           => array(
				'label' => __( 'Aviation', 'seriously-simple-podcasting' ),
				'group' => __( 'Leisure', 'seriously-simple-podcasting' ),
			),
			'Crafts'             => array(
				'label' => __( 'Crafts', 'seriously-simple-podcasting' ),
				'group' => __( 'Leisure', 'seriously-simple-podcasting' ),
			),
			'Games'              => array(
				'label' => __( 'Games', 'seriously-simple-podcasting' ),
				'group' => __( 'Leisure', 'seriously-simple-podcasting' ),
			),
			'Hobbies'            => array(
				'label' => __( 'Hobbies', 'seriously-simple-podcasting' ),
				'group' => __( 'Leisure', 'seriously-simple-podcasting' ),
			),
			'Home & Garden'      => array(
				'label' => __( 'Home & Garden', 'seriously-simple-podcasting' ),
				'group' => __( 'Leisure', 'seriously-simple-podcasting' ),
			),
			'Video Games'        => array(
				'label' => __( 'Video Games', 'seriously-simple-podcasting' ),
				'group' => __( 'Leisure', 'seriously-simple-podcasting' ),
			),
			'Music Commentary'   => array(
				'label' => __( 'Music Commentary', 'seriously-simple-podcasting' ),
				'group' => __( 'Music', 'seriously-simple-podcasting' ),
			),
			'Music History'      => array(
				'label' => __( 'Music History', 'seriously-simple-podcasting' ),
				'group' => __( 'Music', 'seriously-simple-podcasting' ),
			),
			'Music Interviews'   => array(
				'label' => __( 'Music Interviews', 'seriously-simple-podcasting' ),
				'group' => __( 'Music', 'seriously-simple-podcasting' ),
			),
			'Business News'      => array(
				'label' => __( 'Business News', 'seriously-simple-podcasting' ),
				'group' => __( 'News', 'seriously-simple-podcasting' ),
			),
			'Daily News'         => array(
				'label' => __( 'Daily News', 'seriously-simple-podcasting' ),
				'group' => __( 'News', 'seriously-simple-podcasting' ),
			),
			'Entertainment News' => array(
				'label' => __( 'Entertainment News', 'seriously-simple-podcasting' ),
				'group' => __( 'News', 'seriously-simple-podcasting' ),
			),
			'News Commentary'    => array(
				'label' => __( 'News Commentary', 'seriously-simple-podcasting' ),
				'group' => __( 'News', 'seriously-simple-podcasting' ),
			),
			'Politics'           => array(
				'label' => __( 'Politics', 'seriously-simple-podcasting' ),
				'group' => __( 'News', 'seriously-simple-podcasting' ),
			),
			'Sports News'        => array(
				'label' => __( 'Sports News ', 'seriously-simple-podcasting' ),
				'group' => __( 'News', 'seriously-simple-podcasting' ),
			),
			'Tech News'          => array(
				'label' => __( 'Tech News', 'seriously-simple-podcasting' ),
				'group' => __( 'News', 'seriously-simple-podcasting' ),
			),
			'Buddhism'           => array(
				'label' => __( 'Buddhism', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Christianity'       => array(
				'label' => __( 'Christianity', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Hinduism'           => array(
				'label' => __( 'Hinduism', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Islam'              => array(
				'label' => __( 'Islam', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Judaism'            => array(
				'label' => __( 'Judaism', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Religion'           => array(
				'label' => __( 'Religion', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Spirituality'       => array(
				'label' => __( 'Spirituality', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Astronomy'          => array(
				'label' => __( 'Astronomy', 'seriously-simple-podcasting' ),
				'group' => __( 'Science', 'seriously-simple-podcasting' ),
			),
			'Chemistry'          => array(
				'label' => __( 'Chemistry', 'seriously-simple-podcasting' ),
				'group' => __( 'Science', 'seriously-simple-podcasting' ),
			),
			'Earth Sciences'     => array(
				'label' => __( 'Earth Sciences', 'seriously-simple-podcasting' ),
				'group' => __( 'Science', 'seriously-simple-podcasting' ),
			),
			'Life Sciences'      => array(
				'label' => __( 'Life Sciences', 'seriously-simple-podcasting' ),
				'group' => __( 'Science', 'seriously-simple-podcasting' ),
			),
			'Mathematics'        => array(
				'label' => __( 'Mathematics', 'seriously-simple-podcasting' ),
				'group' => __( 'Science', 'seriously-simple-podcasting' ),
			),
			'Natural Sciences'   => array(
				'label' => __( 'Natural Sciences', 'seriously-simple-podcasting' ),
				'group' => __( 'Science', 'seriously-simple-podcasting' ),
			),
			'Nature'             => array(
				'label' => __( 'Nature', 'seriously-simple-podcasting' ),
				'group' => __( 'Science', 'seriously-simple-podcasting' ),
			),
			'Physics'            => array(
				'label' => __( 'Physics', 'seriously-simple-podcasting' ),
				'group' => __( 'Science', 'seriously-simple-podcasting' ),
			),
			'Social Sciences'    => array(
				'label' => __( 'Social Sciences', 'seriously-simple-podcasting' ),
				'group' => __( 'Science', 'seriously-simple-podcasting' ),
			),
			'Documentary'        => array(
				'label' => __( 'Documentary', 'seriously-simple-podcasting' ),
				'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			),
			'Personal Journals'  => array(
				'label' => __( 'Personal Journals', 'seriously-simple-podcasting' ),
				'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			),
			'Philosophy'         => array(
				'label' => __( 'Philosophy', 'seriously-simple-podcasting' ),
				'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			),
			'Places & Travel'    => array(
				'label' => __( 'Places & Travel', 'seriously-simple-podcasting' ),
				'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			),
			'Relationships'      => array(
				'label' => __( 'Relationships', 'seriously-simple-podcasting' ),
				'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			),
			'Baseball'           => array(
				'label' => __( 'Baseball', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Basketball'         => array(
				'label' => __( 'Basketball', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Cricket'            => array(
				'label' => __( 'Cricket', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Fantasy Sports'     => array(
				'label' => __( 'Fantasy Sports ', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Football'           => array(
				'label' => __( 'Football', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Golf'               => array(
				'label' => __( 'Golf', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Hockey'             => array(
				'label' => __( 'Hockey', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Rugby'              => array(
				'label' => __( 'Rugby', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Running'            => array(
				'label' => __( 'Running', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Soccer'             => array(
				'label' => __( 'Soccer', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Swimming'           => array(
				'label' => __( 'Swimming', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Tennis'             => array(
				'label' => __( 'Tennis', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Volleyball'         => array(
				'label' => __( 'Volleyball', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Wilderness'         => array(
				'label' => __( 'Wilderness', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'Wrestling'          => array(
				'label' => __( 'Wrestling', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports', 'seriously-simple-podcasting' ),
			),
			'After Shows'        => array(
				'label' => __( 'After Shows', 'seriously-simple-podcasting' ),
				'group' => __( 'TV & Film', 'seriously-simple-podcasting' ),
			),
			'Film History'       => array(
				'label' => __( 'Film History', 'seriously-simple-podcasting' ),
				'group' => __( 'TV & Film', 'seriously-simple-podcasting' ),
			),
			'Film Interviews'    => array(
				'label' => __( 'Film Interviews', 'seriously-simple-podcasting' ),
				'group' => __( 'TV & Film', 'seriously-simple-podcasting' ),
			),
			'Film Reviews'       => array(
				'label' => __( 'Film Reviews', 'seriously-simple-podcasting' ),
				'group' => __( 'TV & Film', 'seriously-simple-podcasting' ),
			),
			'TV Reviews'         => array(
				'label' => __( 'TV Reviews', 'seriously-simple-podcasting' ),
				'group' => __( 'TV & Film', 'seriously-simple-podcasting' ),
			),
		);


		$feed_details_fields = array(
			array(
				'id'          => 'data_title',
				'label'       => __( 'Title', 'seriously-simple-podcasting' ),
				'description' => __( 'Your podcast title.', 'seriously-simple-podcasting' ),
				'type'        => 'text',
				'default'     => get_bloginfo( 'name' ),
				'placeholder' => get_bloginfo( 'name' ),
				'class'       => 'large-text',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_subtitle',
				'label'       => __( 'Subtitle', 'seriously-simple-podcasting' ),
				'description' => __( 'Your podcast subtitle.', 'seriously-simple-podcasting' ),
				'type'        => 'text',
				'default'     => get_bloginfo( 'description' ),
				'placeholder' => get_bloginfo( 'description' ),
				'class'       => 'large-text',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_author',
				'label'       => __( 'Host', 'seriously-simple-podcasting' ),
				'description' => __( 'Your podcast host.', 'seriously-simple-podcasting' ),
				'type'        => 'text',
				'default'     => get_bloginfo( 'name' ),
				'placeholder' => get_bloginfo( 'name' ),
				'class'       => 'large-text',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_category',
				'label'       => __( 'Primary Category', 'seriously-simple-podcasting' ),
				'description' => __( 'Your podcast\'s primary category.', 'seriously-simple-podcasting' ),
				'type'        => 'select',
				'options'     => $category_options,
				'default'     => '',
				'class'       => 'js-parent-category',
				'data'        => array(
					'subcategory' => 'data_subcategory',
				),
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_subcategory',
				'label'       => __( 'Primary Sub-Category', 'seriously-simple-podcasting' ),
				'description' => __( 'Your podcast\'s primary sub-category (if available) - must be a sub-category of the primary category selected above.', 'seriously-simple-podcasting' ),
				'type'        => 'select',
				'options'     => $subcategory_options,
				'default'     => '',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_category2',
				'label'       => __( 'Secondary Category', 'seriously-simple-podcasting' ),
				'description' => __( 'Your podcast\'s secondary category.', 'seriously-simple-podcasting' ),
				'type'        => 'select',
				'class'       => 'js-parent-category',
				'data'        => array(
					'subcategory' => 'data_subcategory2',
				),
				'options'     => $category_options,
				'default'     => '',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_subcategory2',
				'label'       => __( 'Secondary Sub-Category', 'seriously-simple-podcasting' ),
				'description' => __( 'Your podcast\'s secondary sub-category (if available) - must be a sub-category of the secondary category selected above.', 'seriously-simple-podcasting' ),
				'type'        => 'select',
				'options'     => $subcategory_options,
				'default'     => '',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_category3',
				'label'       => __( 'Tertiary Category', 'seriously-simple-podcasting' ),
				'description' => __( 'Your podcast\'s tertiary category.', 'seriously-simple-podcasting' ),
				'type'        => 'select',
				'class'       => 'js-parent-category',
				'data'        => array(
					'subcategory' => 'data_subcategory3',
				),
				'options'     => $category_options,
				'default'     => '',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_subcategory3',
				'label'       => __( 'Tertiary Sub-Category', 'seriously-simple-podcasting' ),
				'description' => __( 'Your podcast\'s tertiary sub-category (if available) - must be a sub-category of the tertiary category selected above.', 'seriously-simple-podcasting' ),
				'type'        => 'select',
				'options'     => $subcategory_options,
				'default'     => '',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_description',
				'label'       => __( 'Description/Summary', 'seriously-simple-podcasting' ),
				'description' => __( 'A description/summary of your podcast - no HTML allowed.', 'seriously-simple-podcasting' ),
				'type'        => 'textarea',
				'default'     => get_bloginfo( 'description' ),
				'placeholder' => get_bloginfo( 'description' ),
				'callback'    => 'wp_strip_all_tags',
				'class'       => 'large-text',
			),
			array(
				'id'          => 'data_image',
				'label'       => __( 'Cover Image', 'seriously-simple-podcasting' ),
				'description' => __( 'The podcast cover image must be between 1400x1400px and 3000x3000px in size and either .jpg or .png file format', 'seriously-simple-podcasting' ) .
				                 '. ' . __( 'Your image should be perfectly square in order for it to display properly in podcasting directories and mobile apps.', 'seriously-simple-podcasting' ) . '<br />' .
				                 ssp_dynamo_btn( $this->get_current_feed_option( 'data_title' ), 'With ' . $this->get_current_feed_option( 'data_author' ), 'Create a custom cover with our free tool' ),
				'type'        => 'image',
				'default'     => '',
				'placeholder' => '',
				'callback'    => 'esc_url_raw',
			),
			array(
				'id'          => 'data_owner_name',
				'label'       => __( 'Owner name', 'seriously-simple-podcasting' ),
				'description' => __( 'Podcast owner\'s name.', 'seriously-simple-podcasting' ),
				'type'        => 'text',
				'default'     => get_bloginfo( 'name' ),
				'placeholder' => get_bloginfo( 'name' ),
				'class'       => 'large-text',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_owner_email',
				'label'       => __( 'Owner email address', 'seriously-simple-podcasting' ),
				'description' => __( 'Podcast owner\'s email address.', 'seriously-simple-podcasting' ),
				'type'        => 'text',
				'default'     => get_bloginfo( 'admin_email' ),
				'placeholder' => get_bloginfo( 'admin_email' ),
				'class'       => 'large-text',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_language',
				'label'       => __( 'Language', 'seriously-simple-podcasting' ),
				// translators: placeholders are for a link to the ISO standards
				'description' => sprintf( __( 'Your podcast\'s language in %1$sISO-639-1 format%2$s.', 'seriously-simple-podcasting' ), '<a href="' . esc_url( 'http://www.loc.gov/standards/iso639-2/php/code_list.php' ) . '" target="' . wp_strip_all_tags( '_blank' ) . '">', '</a>' ),
				'type'        => 'text',
				'default'     => get_bloginfo( 'language' ),
				'placeholder' => get_bloginfo( 'language' ),
				'class'       => 'all-options',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'data_copyright',
				'label'       => __( 'Copyright', 'seriously-simple-podcasting' ),
				'description' => __( 'Copyright line for your podcast.', 'seriously-simple-podcasting' ),
				'type'        => 'text',
				'default'     => '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ),
				'placeholder' => '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ),
				'class'       => 'large-text',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'funding',
				'label'       => __( 'Podcast funding', 'seriously-simple-podcasting' ),
				'type'        => 'text_multi',
				'class'       => 'large-text',
				'fields'      => array(
					array(
						'id'          => 'title',
						'type'        => 'text',
						'placeholder' => __( 'e.g. Donate to the show', 'seriously-simple-podcasting' ),
						'class'       => 'large-text',
						'description' => sprintf(
							'<a href="%s">%s</a>',
							'https://support.castos.com/article/236-podcast-20-funding-tag-in-seriously-simple-podcasting',
							__( 'Learn More', 'seriously-simple-podcasting' )
						),
					),
					array(
						'id'          => 'url',
						'type'        => 'text',
						'placeholder' => __( 'e.g. https://buymeacoffee.com', 'seriously-simple-podcasting' ),
						'class'       => 'large-text',
					),
				),
			),
			array(
				'id'          => 'podcast_value',
				'label'       => __( 'Value4Value', 'seriously-simple-podcasting' ),
				'type'        => 'text_multi',
				'class'       => 'large-text',
				'fields'      => array(
					array(
						'id'          => 'recipient',
						'type'        => 'text',
						'placeholder' => __( 'e.g. 1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', 'seriously-simple-podcasting' ),
						'description' => __( 'Enter your wallet address to accept crypto payment from your listeners.', 'seriously-simple-podcasting' ),
					),
				),
			),
			array(
				'id'          => 'explicit',
				'label'       => __( 'Explicit', 'seriously-simple-podcasting' ),
				// translators: placeholders are for an Apple help document link
				'description' => sprintf( __( 'To mark this podcast as an explicit podcast, check this box. Explicit content rules can be found %s.', 'seriously-simple-podcasting' ), '<a href="https://discussions.apple.com/thread/1079151">here</a>' ),
				'type'        => 'checkbox',
				'default'     => '',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'complete',
				'label'       => __( 'Complete', 'seriously-simple-podcasting' ),
				'description' => __( 'Mark if this podcast is complete or not. Only do this if no more episodes are going to be added to this feed.', 'seriously-simple-podcasting' ),
				'type'        => 'checkbox',
				'default'     => '',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'locked',
				'label'       => __( 'Locked', 'seriously-simple-podcasting' ),
				'description' => __( 'Mark if this podcast is locked or not. Locked means that any attempt to import this feed into a new platform will be rejected.', 'seriously-simple-podcasting' ),
				'type'        => 'checkbox',
				'default'     => 'on',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'publish_date',
				'label'       => __( 'Source for publish date', 'seriously-simple-podcasting' ),
				'description' => __( 'Use the "Published date" of the post or use "Date recorded" from the Podcast episode details.', 'seriously-simple-podcasting' ),
				'type'        => 'radio',
				'options'     => array(
					'published' => __( 'Published date', 'seriously-simple-podcasting' ),
					'recorded'  => __( 'Recorded date', 'seriously-simple-podcasting' ),
				),
				'default'     => 'published',
			),
			array(
				'id'          => 'consume_order',
				'label'       => __( 'Show Type', 'seriously-simple-podcasting' ),
				// translators: placeholders are for help document link
				'description' => sprintf( __( 'The order your podcast episodes will be listed. %1$sMore details here.%2$s', 'seriously-simple-podcasting' ), '<a href="' . esc_url( 'https://castos.com/ios-11-podcast-tags/' ) . '" target="' . wp_strip_all_tags( '_blank' ) . '">', '</a>' ),
				'type'        => 'select',
				'options'     => array(
					''         => __( 'Please Select', 'seriously-simple-podcasting' ),
					'episodic' => __( 'Episodic', 'seriously-simple-podcasting' ),
					'serial'   => __( 'Serial', 'seriously-simple-podcasting' ),
				),
				'default'     => '',
			),
			array(
				'id'          => 'media_prefix',
				'label'       => __( 'Media File Prefix', 'seriously-simple-podcasting' ),
				// translators: placeholders are for help document link
				'description' => sprintf( __( 'Enter your Podtrac, Chartable, or other media file prefix here. %1$sMore details here.%2$s', 'seriously-simple-podcasting' ), '<a href="' . esc_url( 'https://support.castos.com/hc/en-us/articles/360019364119-Add-a-media-file-prefix-in-WordPress-for-Podtrac-Chartable-and-other-analytics-or-tracking-services' ) . '" target="' . wp_strip_all_tags( '_blank' ) . '">', '</a>' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => __( 'https://dts.podtrac.com/redirect/mp3/', 'seriously-simple-podcasting' ),
				'callback'    => 'esc_url_raw',
				'class'       => 'regular-text',
			),
			array(
				'id'          => 'episode_description',
				'label'       => __( 'Episode description', 'seriously-simple-podcasting' ),
				'description' => __( 'Use the excerpt or the post content in the description tag for episodes', 'seriously-simple-podcasting' ),
				'type'        => 'radio',
				'options'     => array(
					'excerpt' => __( 'Post Excerpt', 'seriously-simple-podcasting' ),
					'content' => __( 'Post Content', 'seriously-simple-podcasting' ),
				),
				'default'     => 'excerpt',
			),
			array(
				'id'          => 'exclude_feed',
				'label'       => __( 'Exclude podcast from default feed', 'seriously-simple-podcasting' ),
				// translators: placeholders are html anchor tags to support document
				'description' => sprintf( __( 'When enabled, this will exclude any episodes in this podcast feed from the default feed. %1$sMore details here.%2$s', 'seriously-simple-podcasting' ), '<a href="' . esc_url( 'https://support.castos.com/article/67-include-series-episodes-in-the-default-feed' ) . '" target="' . wp_strip_all_tags( '_blank' ) . '">', '</a>' ),
				'type'        => 'checkbox',
				'default'     => '',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'turbocharge_feed',
				'label'       => __( 'Turbocharge podcast feed', 'seriously-simple-podcasting' ),
				// translators: placeholders are html anchor tags to support document
				'description' => sprintf( __( 'When enabled, this setting will speed up your feed loading time. %1$sMore details here.%2$s', 'seriously-simple-podcasting' ), '<a href="' . esc_url( 'https://support.castos.com/article/220-turbocharging-your-feed-to-maximize-available-episodes' ) . '" target="' . wp_strip_all_tags( '_blank' ) . '">', '</a>' ),
				'type'        => 'checkbox',
				'default'     => '',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'redirect_feed',
				'label'       => __( 'Redirect this feed to new URL', 'seriously-simple-podcasting' ),
				'description' => sprintf( __( 'Redirect your feed to a new URL (specified below).', 'seriously-simple-podcasting' ), '<br/>' ),
				'type'        => 'checkbox',
				'default'     => '',
				'callback'    => 'wp_strip_all_tags',
			),
			array(
				'id'          => 'new_feed_url',
				'label'       => __( 'New podcast feed URL', 'seriously-simple-podcasting' ),
				'description' => __( 'Your podcast feed\'s new URL.', 'seriously-simple-podcasting' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => __( 'New feed URL', 'seriously-simple-podcasting' ),
				'callback'    => 'esc_url_raw',
				'class'       => 'regular-text',
			),
			array(
				'id'          => 'podping_notification',
				'label'       => __( 'Podping', 'seriously-simple-podcasting' ),
				'description' => sprintf( __( 'Enable podping notification. %1$sMore details here.%2$s', 'seriously-simple-podcasting' ), '<a href="' . esc_url( 'https://support.castos.com/article/275-what-is-podping' ) . '" target="' . wp_strip_all_tags( '_blank' ) . '">', '</a>' ),
				'type'        => 'checkbox',
				'default'     => 'on',
				'callback'    => 'wp_strip_all_tags',
			),
		);

		$private_podcast = array(
			'id'          => 'is_podcast_private',
			'label'       => __( 'Set Podcast To Private', 'seriously-simple-podcasting' ),
			'type'        => 'radio',
			'options'     => array(
				'yes' => __( 'Yes', 'seriously-simple-podcasting' ),
				'no'  => __( 'No', 'seriously-simple-podcasting' ),
			),
			'default'     => 'no',
		);

		if ( ! ssp_is_connected_to_castos() ) {
			$private_unavailable_descr = __( 'Setting a podcast as Private is only available to Castos hosting customers.', 'seriously-simple-podcasting' );
		} elseif ( class_exists( 'PMPro_Membership_Level' ) && ssp_get_option( 'enable_pmpro_integration', 'on' ) ) {
			$private_unavailable_descr = __( 'Looks like you\'re already using Paid Membership Pro to make your podcast private.', 'seriously-simple-podcasting' );
		} elseif ( class_exists( 'LifterLMS' ) && ssp_get_option( 'enable_lifterlms_integration' ) ) {
			$private_unavailable_descr = __( 'Looks like you\'re already using LifterLMS to make your podcast private.', 'seriously-simple-podcasting' );
		} elseif ( class_exists( 'MeprUser' ) && ssp_get_option( 'enable_memberpress_integration' ) ) {
			$private_unavailable_descr = __( 'Looks like you\'re already using MemberPress to make your podcast private.', 'seriously-simple-podcasting' );
		}

		if ( ! empty( $private_unavailable_descr ) ) {
			$private_podcast['description'] = $private_unavailable_descr;
			$private_podcast['type']        = '';
			// Change the ID to not override the original settings.
			$private_podcast['id'] = 'is_podcast_private_unavailable';
		}

		$feed_details_fields[] = $private_podcast;

		$subscribe_options_array            = $this->get_subscribe_field_options();

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
		$podcast_id = $this->get_current_feed_settings_podcast_id();

		return ssp_get_option( $option, '', $podcast_id );
	}

	/**
	 * This function gets current podcast ID for the feed details page ( Podcasting -> Settings -> Feed Details )
	 *
	 * @return int
	 */
	protected function get_current_feed_settings_podcast_id() {
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
	 * Checks if a user role exists, used in the SettingsController add_caps method
	 *
	 * @param $role
	 *
	 * @return bool
	 * @deprecated Use Roles_Handler::role_exists() instead
	 *
	 */
	public function role_exists( $role ) {
		if ( ! empty( $role ) ) {
			return $GLOBALS['wp_roles']->is_role( $role );
		}

		return false;
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
