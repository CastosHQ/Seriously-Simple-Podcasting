<?php

return array(
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
			'label'       => __( 'Your RSS feeds', 'seriously-simple-podcasting' ),
			'description' => '',
			'type'        => 'feed_link',
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
