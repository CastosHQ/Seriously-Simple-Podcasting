<?php
/**
 * @var array $podcast_options
 * */
return array(
	'title'    => __( 'Hosting', 'seriously-simple-podcasting' ),
	'sections' => array(
		'credentials' => array(
			'title'       => __( 'Podcast Hosting', 'seriously-simple-podcasting' ),
			'description' => sprintf( __( 'Connect your WordPress site to your %s account.', 'seriously-simple-podcasting' ), '<a target="_blank" href="' . SSP_CASTOS_APP_URL . '">Castos</a>' ),
			'fields'      => array(
				array(
					'id'          => 'podmotor_account_email',
					'type'        => 'text',
					'label'       => __( 'Your email', 'seriously-simple-podcasting' ),
					'description' => __( 'The email address you used to register your Castos account.', 'seriously-simple-podcasting' ),
					'default'     => '',
					'placeholder' => __( 'email@domain.com', 'seriously-simple-podcasting' ),
					'callback'    => 'esc_email',
					'class'       => 'regular-text',
				),
				array(
					'id'          => 'podmotor_account_api_token',
					'type'        => 'text',
					'label'       => __( 'Castos API key', 'seriously-simple-podcasting' ),
					'description' => __( 'Your Castos API key. Available from your Castos account dashboard.', 'seriously-simple-podcasting' ),
					'default'     => '',
					'placeholder' => __( 'Enter your api key', 'seriously-simple-podcasting' ),
					'callback'    => 'sanitize_text_field',
					'class'       => 'regular-text',
				),
				array(
					'id'    => 'validate_api_credentials',
					'type'  => 'button',
					'label' => esc_attr( __( 'Verify Credentials', 'seriously-simple-podcasting' ) ),
					'class' => 'button-primary',
				),
			),
		),
		'sync'        => array(
			'condition_callback' => 'ssp_is_connected_to_castos',
			'title'              => __( 'Sync to Castos', 'seriously-simple-podcasting' ),
			'no_store'           => true,
			'description'        => sprintf(
				__( 'Use this option for a one time sync of your existing WordPress podcast to your Castos account. If you encounter any problems with it, please contact support at hello@castos.com.', 'seriously-simple-podcasting' ),
				'<a href="' . SSP_CASTOS_APP_URL . '">Castos</a>'
			),
			'fields'             => array(
				array(
					'id'          => 'podcasts_sync',
					'label'       => __( 'Podcast', 'seriously-simple-podcasting' ),
					'description' => __( 'Select the podcast you want to sync to your Castos hosting account.', 'seriously-simple-podcasting' ),
					'type'        => 'podcasts_sync',
					'options'     => $podcast_options,
				),
				array(
					'id'    => 'trigger_sync',
					'type'  => 'button',
					'label' => esc_attr( __( 'Trigger Sync', 'seriously-simple-podcasting' ) ),
					'class' => 'button-primary',
				),
			),
		),
		'disconnect'  => array(
			'condition_callback' => 'ssp_is_connected_to_castos',
			'title'              => __( 'Danger Zone', 'seriously-simple-podcasting' ),
			'no_store'           => true,
			'fields'             => array(
				array(
					'id'          => 'podmotor_disconnect',
					'label'       => __( 'Disconnect Castos', 'seriously-simple-podcasting' ),
					'description' => __( 'Select this if you wish to disconnect your Castos account.', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox',
					'default'     => '',
					'callback'    => 'wp_strip_all_tags',
					'class'       => 'disconnect-castos',
				),
			),
		),
	),
);
