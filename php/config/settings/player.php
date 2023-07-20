<?php
/**
 * @var string $player_style
 * @var bool $is_meta_data_enabled
 * @var bool $is_custom_colors_enabled
 * @var array $color_settings
 * */
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

if ( $is_meta_data_enabled ) {
	$meta_settings = array(
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

	$meta_settings = apply_filters( 'ssp_player_meta_settings', $meta_settings );

	$player_settings['fields'] = array_merge( $player_settings['fields'], $meta_settings );
}

$player_settings['fields'][] = array(
	'id'          => 'player_custom_colors_enabled',
	'label'       => __( 'Enable Custom Player Colors', 'seriously-simple-podcasting' ),
	'description' => __( 'Turn this on to enable customer player color settings', 'seriously-simple-podcasting' ),
	'type'        => 'checkbox',
	'default'     => '',
);

if ( $is_custom_colors_enabled ) {
	$player_settings['fields'] = array_merge( $player_settings['fields'], $color_settings );
}

return $player_settings;
