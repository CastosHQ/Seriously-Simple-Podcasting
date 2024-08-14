<?php

/**
 * @var array $podcast_options
 * */
return array(
	'title' => __( 'Hosting', 'seriously-simple-podcasting' ),
	'button_text' => __( 'Connect', 'seriously-simple-podcasting' ),
	'button_class' => 'castos-connect disabled hidden',
	'sections' => array(
		'credentials' => array(
			'condition_callback' => function () {
				return ! ssp_is_connected_to_castos();
			},
			'title' => __( 'Podcast Hosting', 'seriously-simple-podcasting' ),
			'description' => sprintf( __( 'Connect your WordPress site to your %s account.', 'seriously-simple-podcasting' ), '<a target="_blank" href="' . SSP_CASTOS_APP_URL . '">Castos</a>' ),
			'fields' => array(
				array(
					'id' => 'podmotor_account_api_token',
					'type' => 'text',
					'label' => __( 'Castos API token', 'seriously-simple-podcasting' ),
					'description' => sprintf( __( 'Your Castos API token. Available from your %s.', 'seriously-simple-podcasting' ), '<a target="_blank" href="' . SSP_CASTOS_APP_URL . 'integrations/api-details' . '">Castos dashboard</a>' ),
					'default' => '',
					'placeholder' => __( 'Enter your API token', 'seriously-simple-podcasting' ),
					'callback' => 'sanitize_text_field',
					'class' => 'regular-text',
				),
			),
		),
		'sync' => array(
			'condition_callback' => 'ssp_is_connected_to_castos',
			'title' => __( 'Sync to Castos', 'seriously-simple-podcasting' ),
			'no_store' => true,
			'fields' => array(
				array(
					'id' => 'castos_sync_info',
					'label' => __( 'Castos Account', 'seriously-simple-podcasting' ),
					'description' => do_shortcode( '[castos_email]' ),
					'type' => 'info',
				),
				array(
					'id' => 'podcasts_sync',
					'label' => __( 'Podcast', 'seriously-simple-podcasting' ),
					'description' => __( 'Select the podcast you want to sync to your Castos hosting account.', 'seriously-simple-podcasting' ),
					'type' => 'podcasts_sync',
					'options' => $podcast_options,
				),
				array(
					'id' => 'trigger_sync',
					'type' => 'button',
					'label' => esc_attr( __( 'Trigger Sync', 'seriously-simple-podcasting' ) ),
					'description' => '<br><br>' . sprintf(
							__( 'Use this option for a one time sync of your existing WordPress podcast to your %s. If you encounter any problems with it, please contact support at hello@castos.com.', 'seriously-simple-podcasting' ),
							'<a target="_blank" rel="noopener" href="' . esc_attr( SSP_CASTOS_APP_URL ) . '">' . __( 'Castos account', 'seriously-simple-podcasting' ) . '</a>'
						),
					'class' => 'button-primary',
				),
			),
		),
		'disconnect' => array(
			'condition_callback' => 'ssp_is_connected_to_castos',
			'title' => __( 'Danger Zone', 'seriously-simple-podcasting' ),
			'no_store' => true,
			'fields' => array(
				array(
					'id' => 'disconnect_castos',
					'label' => __( 'Disconnect', 'seriously-simple-podcasting' ),
					'description' => __( 'Select this if you wish to disconnect your Castos account.', 'seriously-simple-podcasting' ),
					'type' => 'button',
					'default' => '',
					'callback' => 'wp_strip_all_tags',
					'class' => 'disconnect-castos button',
				),
			),
		),
	),
);
