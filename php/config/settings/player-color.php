<?php

return array(
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
