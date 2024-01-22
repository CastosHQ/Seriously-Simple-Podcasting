<?php
/**
 * @var string $title
 * @var string $author
 * @var string $site_title
 * @var string $site_description
 * @var array $categories
 * @var array $subcategories
 * @var array $language
 * @var bool $is_default
 * */

$feed_fields = array(
	array(
		'id'          => 'data_title',
		'label'       => __( 'Title', 'seriously-simple-podcasting' ),
		'description' => __( 'Your podcast title.', 'seriously-simple-podcasting' ),
		'type'        => 'text',
		'default'     => $site_title,
		'placeholder' => $site_title,
		'class'       => 'large-text',
		'callback'    => 'wp_strip_all_tags',
	),
	array(
		'id'          => 'data_subtitle',
		'label'       => __( 'Subtitle', 'seriously-simple-podcasting' ),
		'description' => __( 'Your podcast subtitle.', 'seriously-simple-podcasting' ),
		'type'        => 'text',
		'default'     => $site_description,
		'placeholder' => $site_description,
		'class'       => 'large-text',
		'callback'    => 'wp_strip_all_tags',
	),
	array(
		'id'          => 'data_author',
		'label'       => __( 'Host', 'seriously-simple-podcasting' ),
		'description' => __( 'Your podcast host.', 'seriously-simple-podcasting' ),
		'type'        => 'text',
		'default'     => $site_title,
		'placeholder' => '',
		'class'       => 'large-text',
		'callback'    => 'wp_strip_all_tags',
	),
	array(
		'id'          => 'data_category',
		'label'       => __( 'Primary Category', 'seriously-simple-podcasting' ),
		'description' => __( 'Your podcast\'s primary category.', 'seriously-simple-podcasting' ),
		'type'        => 'select',
		'options'     => $categories,
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
		'options'     => $subcategories,
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
		'options'     => $categories,
		'default'     => '',
		'callback'    => 'wp_strip_all_tags',
	),
	array(
		'id'          => 'data_subcategory2',
		'label'       => __( 'Secondary Sub-Category', 'seriously-simple-podcasting' ),
		'description' => __( 'Your podcast\'s secondary sub-category (if available) - must be a sub-category of the secondary category selected above.', 'seriously-simple-podcasting' ),
		'type'        => 'select',
		'options'     => $subcategories,
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
		'options'     => $categories,
		'default'     => '',
		'callback'    => 'wp_strip_all_tags',
	),
	array(
		'id'          => 'data_subcategory3',
		'label'       => __( 'Tertiary Sub-Category', 'seriously-simple-podcasting' ),
		'description' => __( 'Your podcast\'s tertiary sub-category (if available) - must be a sub-category of the tertiary category selected above.', 'seriously-simple-podcasting' ),
		'type'        => 'select',
		'options'     => $subcategories,
		'default'     => '',
		'callback'    => 'wp_strip_all_tags',
	),
	array(
		'id'          => 'data_description',
		'label'       => __( 'Description/Summary', 'seriously-simple-podcasting' ),
		'description' => __( 'A description/summary of your podcast - no HTML allowed.', 'seriously-simple-podcasting' ),
		'type'        => 'textarea',
		'default'     => $site_description,
		'placeholder' => $site_description,
		'callback'    => 'wp_strip_all_tags',
		'class'       => 'large-text',
	),
	array(
		'id'          => 'data_image',
		'label'       => __( 'Cover Image', 'seriously-simple-podcasting' ),
		'description' => __( 'The podcast cover image must be between 1400x1400px and 3000x3000px in size and either .jpg or .png file format', 'seriously-simple-podcasting' ) .
		                 '. ' . __( 'Your image should be perfectly square in order for it to display properly in podcasting directories and mobile apps.', 'seriously-simple-podcasting' ) . '<br />' .
		                 ssp_dynamo_btn( $title, 'With ' . $author, 'Create a custom cover with our free tool' ),
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
		'default'     => $site_title,
		'placeholder' => $site_title,
		'class'       => 'large-text',
		'callback'    => 'wp_strip_all_tags',
	),
	array(
		'id'          => 'data_owner_email',
		'label'       => __( 'Owner email address', 'seriously-simple-podcasting' ),
		'description' => __( 'Podcast owner\'s email address (leave blank to omit from RSS feed).', 'seriously-simple-podcasting' ),
		'type'        => 'text',
		'default'     => '',
		'placeholder' => 'email@gmail.com ',
		'class'       => 'large-text',
		'callback'    => 'wp_strip_all_tags',
	),
	array(
		'id'          => 'data_language',
		'label'       => __( 'Language', 'seriously-simple-podcasting' ),
		// translators: placeholders are for a link to the ISO standards
		'description' => sprintf( __( 'Your podcast\'s language in %1$sISO-639-1 format%2$s.', 'seriously-simple-podcasting' ), '<a href="' . esc_url( 'http://www.loc.gov/standards/iso639-2/php/code_list.php' ) . '" target="' . wp_strip_all_tags( '_blank' ) . '">', '</a>' ),
		'type'        => 'text',
		'default'     => $language,
		'placeholder' => $language,
		'class'       => 'all-options',
		'callback'    => 'wp_strip_all_tags',
	),
	array(
		'id'          => 'data_copyright',
		'label'       => __( 'Copyright', 'seriously-simple-podcasting' ),
		'description' => __( 'Copyright line for your podcast.', 'seriously-simple-podcasting' ),
		'type'        => 'text',
		'default'     => '&#xA9; ' . date( 'Y' ) . ' ' . $site_title,
		'placeholder' => '&#xA9; ' . date( 'Y' ) . ' ' . $site_title,
		'class'       => 'large-text',
		'callback'    => 'wp_strip_all_tags',
	),
	array(
		'id'     => 'funding',
		'label'  => __( 'Podcast funding', 'seriously-simple-podcasting' ),
		'type'   => 'text_multi',
		'class'  => 'large-text',
		'fields' => array(
			array(
				'id'          => 'title',
				'type'        => 'text',
				'placeholder' => __( 'e.g. Donate to the show', 'seriously-simple-podcasting' ),
				'class'       => 'large-text',
				'description' => sprintf(
					'<a target="_blank" href="%s">%s</a>',
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
		'id'     => 'podcast_value',
		'label'  => __( 'Value4Value', 'seriously-simple-podcasting' ),
		'type'   => 'text_multi',
		'class'  => 'large-text',
		'fields' => array(
			array(
				'id'          => 'recipient',
				'type'        => 'text',
				'placeholder' => __( 'e.g. 1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', 'seriously-simple-podcasting' ),
				'description' => __( 'Enter your wallet address to accept crypto payment from your listeners (required).', 'seriously-simple-podcasting' ),
			),
			array(
				'id'          => 'name',
				'type'        => 'text',
				'placeholder' => __( 'e.g. Podcaster', 'seriously-simple-podcasting' ),
				'description' => __( 'Enter name of recipient (optional).', 'seriously-simple-podcasting' ),
			),
			array(
				'id'          => 'custom_key',
				'type'        => 'text',
				'placeholder' => __( 'e.g. 696969', 'seriously-simple-podcasting' ),
				'description' => __( 'Enter your custom key for the wallet address (optional).', 'seriously-simple-podcasting' ),
			),
			array(
				'id'          => 'custom_value',
				'type'        => 'text',
				'placeholder' => __( 'e.g. gd43478sod6', 'seriously-simple-podcasting' ),
				'description' => __( 'Enter your custom value for the wallet address (optional).', 'seriously-simple-podcasting' ),
			),
		),
	),
	array(
		'id'          => 'explicit',
		'label'       => __( 'Explicit', 'seriously-simple-podcasting' ),
		// translators: placeholders are for an Apple help document link
		'description' => sprintf(
			__( 'To mark this podcast as an explicit podcast, check this box. Explicit content rules can be found %1$shere%2$s.', 'seriously-simple-podcasting' ),
			'<a target="_blank" href="https://discussions.apple.com/thread/1079151">', '</a>'
		),
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
		'type'        => $is_default ? 'hidden' : 'checkbox',
		'default'     => 'on',
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
	'id'      => 'is_podcast_private',
	'label'   => __( 'Set Podcast To Private', 'seriously-simple-podcasting' ),
	'type'    => 'radio',
	'options' => array(
		'yes' => __( 'Yes', 'seriously-simple-podcasting' ),
		'no'  => __( 'No', 'seriously-simple-podcasting' ),
	),
	'default' => 'no',
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

$feed_fields[] = $private_podcast;

return apply_filters( 'ssp_feed_fields', $feed_fields );
