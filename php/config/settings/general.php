<?php
/**
 * @var $post_type_options
 * */
return array(
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
