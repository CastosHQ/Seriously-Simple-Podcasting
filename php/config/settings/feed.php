<?php
/**
 * @var string $title
 * @var string $author
 * */

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

$feed_fields = array(
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
		'id'     => 'podcast_value',
		'label'  => __( 'Value4Value', 'seriously-simple-podcasting' ),
		'type'   => 'text_multi',
		'class'  => 'large-text',
		'fields' => array(
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
