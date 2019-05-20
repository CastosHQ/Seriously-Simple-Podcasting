<?php

namespace SeriouslySimplePodcasting\Handlers;

/**
 * SSP Settings Handler
 *
 * @package Seriously Simple Podcasting
 */

class Settings_Handler {

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
				'podcast',
			);
			if ( in_array( $post_type, $disallowed_post_types, true ) ) {
				continue;
			}

			$post_type_options[ $post_type ] = $data->labels->name;
		}

		// Set up available category options.
		$category_options = array(
			''                           => __( '-- None --', 'seriously-simple-podcasting' ),
			'Arts'                       => __( 'Arts', 'seriously-simple-podcasting' ),
			'Business'                   => __( 'Business', 'seriously-simple-podcasting' ),
			'Comedy'                     => __( 'Comedy', 'seriously-simple-podcasting' ),
			'Education'                  => __( 'Education', 'seriously-simple-podcasting' ),
			'Games & Hobbies'            => __( 'Games & Hobbies', 'seriously-simple-podcasting' ),
			'Government & Organizations' => __( 'Government & Organizations', 'seriously-simple-podcasting' ),
			'Health'                     => __( 'Health', 'seriously-simple-podcasting' ),
			'Kids & Family'              => __( 'Kids & Family', 'seriously-simple-podcasting' ),
			'Music'                      => __( 'Music', 'seriously-simple-podcasting' ),
			'News & Politics'            => __( 'News & Politics', 'seriously-simple-podcasting' ),
			'Religion & Spirituality'    => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			'Science & Medicine'         => __( 'Science & Medicine', 'seriously-simple-podcasting' ),
			'Society & Culture'          => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			'Sports & Recreation'        => __( 'Sports & Recreation', 'seriously-simple-podcasting' ),
			'Technology'                 => __( 'Technology', 'seriously-simple-podcasting' ),
			'TV & Film'                  => __( 'TV & Film', 'seriously-simple-podcasting' ),
		);

		// Set up available sub-category options.
		$subcategory_options = array(
			''                       => __( '-- None --', 'seriously-simple-podcasting' ),
			'Design'                 => array(
				'label' => __( 'Design', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Fashion & Beauty'       => array(
				'label' => __( 'Fashion & Beauty', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Food'                   => array(
				'label' => __( 'Food', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Literature'             => array(
				'label' => __( 'Literature', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Performing Arts'        => array(
				'label' => __( 'Performing Arts', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Visual Arts'            => array(
				'label' => __( 'Visual Arts', 'seriously-simple-podcasting' ),
				'group' => __( 'Arts', 'seriously-simple-podcasting' ),
			),
			'Business News'          => array(
				'label' => __( 'Business News', 'seriously-simple-podcasting' ),
				'group' => __( 'Business', 'seriously-simple-podcasting' ),
			),
			'Careers'                => array(
				'label' => __( 'Careers', 'seriously-simple-podcasting' ),
				'group' => __( 'Business', 'seriously-simple-podcasting' ),
			),
			'Investing'              => array(
				'label' => __( 'Investing', 'seriously-simple-podcasting' ),
				'group' => __( 'Business', 'seriously-simple-podcasting' ),
			),
			'Management & Marketing' => array(
				'label' => __( 'Management & Marketing', 'seriously-simple-podcasting' ),
				'group' => __( 'Business', 'seriously-simple-podcasting' ),
			),
			'Shopping'               => array(
				'label' => __( 'Shopping', 'seriously-simple-podcasting' ),
				'group' => __( 'Business', 'seriously-simple-podcasting' ),
			),
			'Education'              => array(
				'label' => __( 'Education', 'seriously-simple-podcasting' ),
				'group' => __( 'Education', 'seriously-simple-podcasting' ),
			),
			'Education Technology'   => array(
				'label' => __( 'Education Technology', 'seriously-simple-podcasting' ),
				'group' => __( 'Education', 'seriously-simple-podcasting' ),
			),
			'Higher Education'       => array(
				'label' => __( 'Higher Education', 'seriously-simple-podcasting' ),
				'group' => __( 'Education', 'seriously-simple-podcasting' ),
			),
			'K-12'                   => array(
				'label' => __( 'K-12', 'seriously-simple-podcasting' ),
				'group' => __( 'Education', 'seriously-simple-podcasting' ),
			),
			'Language Courses'       => array(
				'label' => __( 'Language Courses', 'seriously-simple-podcasting' ),
				'group' => __( 'Education', 'seriously-simple-podcasting' ),
			),
			'Training'               => array(
				'label' => __( 'Training', 'seriously-simple-podcasting' ),
				'group' => __( 'Education', 'seriously-simple-podcasting' ),
			),
			'Automotive'             => array(
				'label' => __( 'Automotive', 'seriously-simple-podcasting' ),
				'group' => __( 'Games & Hobbies', 'seriously-simple-podcasting' ),
			),
			'Aviation'               => array(
				'label' => __( 'Aviation', 'seriously-simple-podcasting' ),
				'group' => __( 'Games & Hobbies', 'seriously-simple-podcasting' ),
			),
			'Hobbies'                => array(
				'label' => __( 'Hobbies', 'seriously-simple-podcasting' ),
				'group' => __( 'Games & Hobbies', 'seriously-simple-podcasting' ),
			),
			'Other Games'            => array(
				'label' => __( 'Other Games', 'seriously-simple-podcasting' ),
				'group' => __( 'Games & Hobbies', 'seriously-simple-podcasting' ),
			),
			'Video Games'            => array(
				'label' => __( 'Video Games', 'seriously-simple-podcasting' ),
				'group' => __( 'Games & Hobbies', 'seriously-simple-podcasting' ),
			),
			'Local'                  => array(
				'label' => __( 'Local', 'seriously-simple-podcasting' ),
				'group' => __( 'Government & Organizations', 'seriously-simple-podcasting' ),
			),
			'National'               => array(
				'label' => __( 'National', 'seriously-simple-podcasting' ),
				'group' => __( 'Government & Organizations', 'seriously-simple-podcasting' ),
			),
			'Non-Profit'             => array(
				'label' => __( 'Non-Profit', 'seriously-simple-podcasting' ),
				'group' => __( 'Government & Organizations', 'seriously-simple-podcasting' ),
			),
			'Regional'               => array(
				'label' => __( 'Regional', 'seriously-simple-podcasting' ),
				'group' => __( 'Government & Organizations', 'seriously-simple-podcasting' ),
			),
			'Alternative Health'     => array(
				'label' => __( 'Alternative Health', 'seriously-simple-podcasting' ),
				'group' => __( 'Health', 'seriously-simple-podcasting' ),
			),
			'Fitness & Nutrition'    => array(
				'label' => __( 'Fitness & Nutrition', 'seriously-simple-podcasting' ),
				'group' => __( 'Health', 'seriously-simple-podcasting' ),
			),
			'Self-Help'              => array(
				'label' => __( 'Self-Help', 'seriously-simple-podcasting' ),
				'group' => __( 'Health', 'seriously-simple-podcasting' ),
			),
			'Sexuality'              => array(
				'label' => __( 'Sexuality', 'seriously-simple-podcasting' ),
				'group' => __( 'Health', 'seriously-simple-podcasting' ),
			),
			'Buddhism'               => array(
				'label' => __( 'Buddhism', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Christianity'           => array(
				'label' => __( 'Christianity', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Hinduism'               => array(
				'label' => __( 'Hinduism', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Islam'                  => array(
				'label' => __( 'Islam', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Judaism'                => array(
				'label' => __( 'Judaism', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Other'                  => array(
				'label' => __( 'Other', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Spirituality'           => array(
				'label' => __( 'Spirituality', 'seriously-simple-podcasting' ),
				'group' => __( 'Religion & Spirituality', 'seriously-simple-podcasting' ),
			),
			'Medicine'               => array(
				'label' => __( 'Medicine', 'seriously-simple-podcasting' ),
				'group' => __( 'Science & Medicine', 'seriously-simple-podcasting' ),
			),
			'Natural Sciences'       => array(
				'label' => __( 'Natural Sciences', 'seriously-simple-podcasting' ),
				'group' => __( 'Science & Medicine', 'seriously-simple-podcasting' ),
			),
			'Social Sciences'        => array(
				'label' => __( 'Social Sciences', 'seriously-simple-podcasting' ),
				'group' => __( 'Science & Medicine', 'seriously-simple-podcasting' ),
			),
			'History'                => array(
				'label' => __( 'History', 'seriously-simple-podcasting' ),
				'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			),
			'Personal Journals'      => array(
				'label' => __( 'Personal Journals', 'seriously-simple-podcasting' ),
				'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			),
			'Philosophy'             => array(
				'label' => __( 'Philosophy', 'seriously-simple-podcasting' ),
				'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			),
			'Places & Travel'        => array(
				'label' => __( 'Places & Travel', 'seriously-simple-podcasting' ),
				'group' => __( 'Society & Culture', 'seriously-simple-podcasting' ),
			),
			'Amateur'                => array(
				'label' => __( 'Amateur', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports & Recreation', 'seriously-simple-podcasting' ),
			),
			'College & High School'  => array(
				'label' => __( 'College & High School', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports & Recreation', 'seriously-simple-podcasting' ),
			),
			'Outdoor'                => array(
				'label' => __( 'Outdoor', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports & Recreation', 'seriously-simple-podcasting' ),
			),
			'Professional'           => array(
				'label' => __( 'Professional', 'seriously-simple-podcasting' ),
				'group' => __( 'Sports & Recreation', 'seriously-simple-podcasting' ),
			),
			'Gadgets'                => array(
				'label' => __( 'Gadgets', 'seriously-simple-podcasting' ),
				'group' => __( 'Technology', 'seriously-simple-podcasting' ),
			),
			'Tech News'              => array(
				'label' => __( 'Tech News', 'seriously-simple-podcasting' ),
				'group' => __( 'Technology', 'seriously-simple-podcasting' ),
			),
			'Podcasting'             => array(
				'label' => __( 'Podcasting', 'seriously-simple-podcasting' ),
				'group' => __( 'Technology', 'seriously-simple-podcasting' ),
			),
			'Software How-To'        => array(
				'label' => __( 'Software How-To', 'seriously-simple-podcasting' ),
				'group' => __( 'Technology', 'seriously-simple-podcasting' ),
			),
		);

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
					'id'          => 'player_locations',
					'label'       => __( 'Media player locations', 'seriously-simple-podcasting' ),
					'description' => __( 'Select where to show the podcast media player along with the episode data (download link, duration and file size)', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox_multi',
					'options'     => array(
						'content'       => __( 'Full content', 'seriously-simple-podcasting' ),
						'excerpt'       => __( 'Excerpt', 'seriously-simple-podcasting' ),
						'excerpt_embed' => __( 'oEmbed Excerpt', 'seriously-simple-podcasting' ),
					),
					'default'     => array(),
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
					'id'          => 'itunes_fields_enabled',
					'label'       => __( 'Enable iTunes fields ', 'seriously-simple-podcasting' ),
					'description' => __( 'Turn this on to enable the iTunes iOS11 specific fields on each episode.', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox',
					'default'     => '',
				),
				array(
					'id'          => 'player_meta_data_enabled',
					'label'       => __( 'Enable Player meta data ', 'seriously-simple-podcasting' ),
					'description' => __( 'Turn this on to enable player meta data underneath the player. (download link, episode duration and date recorded).', 'seriously-simple-podcasting' ),
					'type'        => 'checkbox',
					'default'     => 'on',
				),
				array(
					'id'          => 'player_style',
					'label'       => __( 'Media player style', 'seriously-simple-podcasting' ),
					'description' => __( 'Select the style of media player you wish to display on your site.', 'seriously-simple-podcasting' ),
					'type'        => 'radio',
					'options'     => array(
						'standard' => __( 'Standard Compact Player', 'seriously-simple-podcasting' ),
						'larger'   => __( 'HTML5 Player With Album Art', 'seriously-simple-podcasting' ),
					),
					'default'     => 'all',
				),
				array(
					'id'          => 'player_background_skin_colour',
					'label'       => __( 'Background skin colour', 'seriously-simple-podcasting' ),
					'description' => '<br>' . __( 'Only applicable if using the new HTML5 player', 'seriously-simple-podcasting' ),
					'type'        => 'colour-picker',
					'default'     => '#222222',
					'class'       => 'ssp-color-picker',
				),
				array(
					'id'          => 'player_wave_form_colour',
					'label'       => __( 'Player progress bar colour', 'seriously-simple-podcasting' ),
					'description' => '<br>' . __( 'Only applicable if using the new HTML5 player', 'seriously-simple-podcasting' ),
					'type'        => 'colour-picker',
					'default'     => '#fff',
					'class'       => 'ssp-color-picker',
				),
				array(
					'id'          => 'player_wave_form_progress_colour',
					'label'       => __( 'Player progress bar progress colour', 'seriously-simple-podcasting' ),
					'description' => '<br>' . __( 'Only applicable if using the new HTML5 player', 'seriously-simple-podcasting' ),
					'type'        => 'colour-picker',
					'default'     => '#00d4f7',
					'class'       => 'ssp-color-picker',
				),
			),
		);

		$settings['feed-details'] = array(
			'title'       => __( 'Feed details', 'seriously-simple-podcasting' ),
			// translators: placeholders are simply html tags to break up the content
			'description' => sprintf( __( 'This data will be used in the feed for your podcast so your listeners will know more about it before they subscribe.%1$sAll of these fields are optional, but it is recommended that you fill in as many of them as possible. Blank fields will use the assigned defaults in the feed.%2$s', 'seriously-simple-podcasting' ), '<br/><em>', '</em>' ),
		);

		$feed_details_fields                = array(
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
				'label'       => __( 'Author', 'seriously-simple-podcasting' ),
				'description' => __( 'Your podcast author.', 'seriously-simple-podcasting' ),
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
				'description' => __( 'Your podcast cover image - must have a minimum size of 1400x1400 px.', 'seriously-simple-podcasting' ),
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
				'id'          => 'explicit',
				'label'       => __( 'Explicit', 'seriously-simple-podcasting' ),
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
				'id'          => 'publish_date',
				'label'       => __( 'Source for publish date', 'seriously-simple-podcasting' ),
				'description' => __( 'Use the "Published date" of the post or use "Date recorded" from the Podcast episode details.', 'seriously-simple-podcasting' ),
				'type'        => 'radio',
				'options'     => array(
					'published' => __( 'Published date', 'seriously-simple-podcasting' ),
					'recorded'  => __( 'Recorded date', 'seriously-simple-podcasting' )
				),
				'default'     => 'published',
			),
			array(
				'id'          => 'consume_order',
				'label'       => __( 'Show Type', 'seriously-simple-podcasting' ),
				'description' => sprintf( __( 'The order your podcast episodes will be listed. %1$sMore details here.%2$s', 'seriously-simple-podcasting' ), '<a href="' . esc_url( 'https://www.seriouslysimplepodcasting.com/ios-11-podcast-tags/' ) . '" target="' . wp_strip_all_tags( '_blank' ) . '">', '</a>' ),
				'type'        => 'select',
				'options'     => array(
					''         => __( 'Please Select', 'seriously-simple-podcasting' ),
					'episodic' => __( 'Episodic', 'seriously-simple-podcasting' ),
					'serial'   => __( 'Serial', 'seriously-simple-podcasting' )
				),
				'default'     => '',
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
		);
		$subscribe_options_array            = $this->get_subscribe_field_options();
		$settings['feed-details']['fields'] = array_merge( $feed_details_fields, $subscribe_options_array );

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

		$settings['redirection'] = array(
			'title'       => __( 'Redirection', 'seriously-simple-podcasting' ),
			'description' => __( 'Use these settings to safely move your podcast to a different location. Only do this once your new podcast is setup and active.', 'seriously-simple-podcasting' ),
			'fields'      => array(
				array(
					'id'          => 'redirect_feed',
					'label'       => __( 'Redirect podcast feed to new URL', 'seriously-simple-podcasting' ),
					'description' => sprintf( __( 'Redirect your feed to a new URL (specified below).%1$sThis will inform all podcasting services that your podcast has moved and 48 hours after you have saved this option it will permanently redirect your feed to the new URL.', 'seriously-simple-podcasting' ), '<br/>' ),
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
					'label'       => __( 'Feed for a specific series', 'seriously-simple-podcasting' ),
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

		// @todo analytics integration
		/*$settings['analytics'] = array(
			'title'       => __( 'Analytics', 'seriously-simple-podcasting' ),
			'description' => sprintf( __( 'Connect your %s analytics application with your podcast site' ), '<a target="_blank" href=" ' . SSP_CASTOS_APP_URL . '">Seriously Simple Hosting</a>' ),
			'fields'      => array(
				array(
					'id'          => 'ssp_analytics_token',
					'label'       => __( 'Analytics Token', 'seriously-simple-podcasting' ),
					'description' => '',
					'type'        => 'text',
					'callback'    => 'esc_url_raw',
					'class'       => 'regular-text',
				),
			),
		);*/

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
					'label'       => __( 'Castos API token', 'seriously-simple-podcasting' ),
					'description' => __( 'Your Castos API token. Available from your Castos account dashboard.', 'seriously-simple-podcasting' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Enter your api token', 'seriously-simple-podcasting' ),
					'callback'    => 'sanitize_text_field',
					'class'       => 'regular-text',
				),
				array(
					'id'      => 'podmotor_account_id',
					'type'    => 'hidden',
					'default' => '',
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

		// @todo there has to be a better way to do this
		if ( ! ssp_is_connected_to_podcastmotor() ) {
			$settings['castos-hosting']['fields'][3]['container_class'] = 'hidden';
		}

		$fields = array();
		if ( ssp_is_connected_to_podcastmotor() ) {
			if ( ! ssp_get_external_rss_being_imported() ) {
				$fields = array(
					array(
						'id'          => 'podmotor_import',
						'label'       => __( 'Import your podcast', 'seriously-simple-podcasting' ),
						'description' => __( 'Import your podcast to your Castos hosting account.', 'seriously-simple-podcasting' ),
						'type'        => 'checkbox',
						'default'     => '',
						'callback'    => 'wp_strip_all_tags',
						'class'       => 'import-castos',
					),
				);
			}
		}
		$settings['import'] = array(
			'title'       => __( 'Import', 'seriously-simple-podcasting' ),
			'description' => sprintf( __( 'Manage import options.', 'seriously-simple-podcasting' ), '<a href="' . SSP_CASTOS_APP_URL . '">Castos</a>' ),
			'fields'      => $fields,
		);

		$settings['extensions'] = array(
			'title'               => __( 'Extensions', 'seriously-simple-podcasting' ),
			'description'         => __( 'These extensions add functionality to your Seriously Simple Podcasting powered podcast.', 'seriously-simple-podcasting' ),
			'fields'              => array(),
			'disable_save_button' => true,
		);

		$settings = apply_filters( 'ssp_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Builds the array of field settings for the subscribe links, based on the options stored in the options table.
	 *
	 * @return array
	 */
	public function get_subscribe_field_options() {
		$subscribe_field_options = array();
		$subscribe_links_options = get_option( 'ss_podcasting_subscribe_options', array() );
		if ( empty( $subscribe_links_options ) ) {
			return $subscribe_field_options;
		}

		foreach ( $subscribe_links_options as $key => $title ) {
			$subscribe_field_options[] = array(
				'id'          => $key,
				// translators: %s: Service title eg iTunes
				'label'       => sprintf( __( '%s URL', 'seriously-simple-podcasting' ), $title ),
				// translators: %s: Service title eg iTunes
				'description' => sprintf( __( 'Your podcast\'s %s URL.', 'seriously-simple-podcasting' ), $title ),
				'type'        => 'text',
				'default'     => '',
				// translators: %s: Service title eg iTunes
				'placeholder' => sprintf( __( '%s URL', 'seriously-simple-podcasting' ), $title ),
				'callback'    => 'esc_url_raw',
				'class'       => 'regular-text',
			);
		}

		return $subscribe_field_options;
	}

}
