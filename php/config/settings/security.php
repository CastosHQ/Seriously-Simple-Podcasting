<?php
/**
 * @var callable $protection_password_callback
 * @var callable $validate_message_callback
 * */
return array(
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
			'callback'    => $protection_password_callback,
			'class'       => 'regular-text',
		),
		array(
			'id'          => 'protection_no_access_message',
			'label'       => __( 'No access message', 'seriously-simple-podcasting' ),
			'description' => __( 'This message will be displayed to people who are not allowed access to your podcast feed. Limited HTML allowed.', 'seriously-simple-podcasting' ),
			'type'        => 'textarea',
			'default'     => __( 'You are not permitted to view this podcast feed.', 'seriously-simple-podcasting' ),
			'placeholder' => __( 'Message displayed to users who do not have access to the podcast feed', 'seriously-simple-podcasting' ),
			'callback'    => $validate_message_callback,
			'class'       => 'large-text',
		),
	),
);
