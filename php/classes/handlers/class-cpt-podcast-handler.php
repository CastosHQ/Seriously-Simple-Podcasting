<?php

namespace SeriouslySimplePodcasting\Handlers;

/**
 * SSP Custom Post Type Podcast Handler
 *
 * @package Seriously Simple Podcasting
 * @since 2.6.3 Moved from the admin controller class
 */
class CPT_Podcast_Handler {

	protected $roles_handler;

	/**
	 * CPT_Podcast_Handler constructor.
	 *
	 * @param Roles_Handler $roles_handler
	 */
	public function __construct( $roles_handler ) {
		$this->roles_handler = $roles_handler;
	}

	/**
	 * Register SSP_CPT_PODCAST post type
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type( SSP_CPT_PODCAST, $this->get_podcast_args() );

		$this->register_taxonomies();
		$this->register_meta();
	}

	/**
	 * Register taxonomies
	 * @return void
	 */
	protected function register_taxonomies() {
		$podcast_post_types = ssp_post_types( true );

		$args = $this->get_series_args();
		register_taxonomy( apply_filters( 'ssp_series_taxonomy', 'series' ), $podcast_post_types, $args );

		// Add Tags to podcast post type
		if ( apply_filters( 'ssp_use_post_tags', true ) ) {
			register_taxonomy_for_object_type( 'post_tag', SSP_CPT_PODCAST );
		} else {
			/**
			 * Uses post tags by default. Alternative option added in as some users
			 * want to filter by podcast tags only
			 */
			$args = $this->get_podcast_tags_args();
			register_taxonomy( apply_filters( 'ssp_podcast_tags_taxonomy', 'podcast_tags' ), $podcast_post_types, $args );
		}
	}

	protected function get_podcast_args() {
		$labels = array(
			'name'                  => _x( 'Podcast', 'post type general name', 'seriously-simple-podcasting' ),
			'singular_name'         => _x( 'Podcast', 'post type singular name', 'seriously-simple-podcasting' ),
			'add_new'               => _x( 'Add New', SSP_CPT_PODCAST, 'seriously-simple-podcasting' ),
			'add_new_item'          => sprintf( __( 'Add New %s', 'seriously-simple-podcasting' ), __( 'Episode', 'seriously-simple-podcasting' ) ),
			'edit_item'             => sprintf( __( 'Edit %s', 'seriously-simple-podcasting' ), __( 'Episode', 'seriously-simple-podcasting' ) ),
			'new_item'              => sprintf( __( 'New %s', 'seriously-simple-podcasting' ), __( 'Episode', 'seriously-simple-podcasting' ) ),
			'all_items'             => sprintf( __( 'All %s', 'seriously-simple-podcasting' ), __( 'Episodes', 'seriously-simple-podcasting' ) ),
			'view_item'             => sprintf( __( 'View %s', 'seriously-simple-podcasting' ), __( 'Episode', 'seriously-simple-podcasting' ) ),
			'search_items'          => sprintf( __( 'Search %s', 'seriously-simple-podcasting' ), __( 'Episodes', 'seriously-simple-podcasting' ) ),
			'not_found'             => sprintf( __( 'No %s Found', 'seriously-simple-podcasting' ), __( 'Episodes', 'seriously-simple-podcasting' ) ),
			'not_found_in_trash'    => sprintf( __( 'No %s Found In Trash', 'seriously-simple-podcasting' ), __( 'Episodes', 'seriously-simple-podcasting' ) ),
			'parent_item_colon'     => '',
			'menu_name'             => __( 'Podcast', 'seriously-simple-podcasting' ),
			'filter_items_list'     => sprintf( __( 'Filter %s list', 'seriously-simple-podcasting' ), __( 'Episode', 'seriously-simple-podcasting' ) ),
			'items_list_navigation' => sprintf( __( '%s list navigation', 'seriously-simple-podcasting' ), __( 'Episode', 'seriously-simple-podcasting' ) ),
			'items_list'            => sprintf( __( '%s list', 'seriously-simple-podcasting' ), __( 'Episode', 'seriously-simple-podcasting' ) ),
		);
		$slug   = apply_filters( 'ssp_archive_slug', __( SSP_CPT_PODCAST, 'seriously-simple-podcasting' ) );
		$args   = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => array( 'slug' => $slug, 'feeds' => true ),
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'supports'            => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				'page-attributes',
				'comments',
				'author',
				'custom-fields',
				'publicize',
			),
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-microphone',
			'show_in_rest'        => true,
			'capabilities'        => $this->roles_handler->get_podcast_capabilities(),
		);

		return apply_filters( 'ssp_register_post_type_args', $args, SSP_CPT_PODCAST );
	}

	protected function get_podcast_tags_args() {
		$labels = array(
			'name'                       => __( 'Tags', 'seriously-simple-podcasting' ),
			'singular_name'              => __( 'Tag', 'seriously-simple-podcasting' ),
			'search_items'               => __( 'Search Tags', 'seriously-simple-podcasting' ),
			'popular_items'              => __( 'Popular Tags', 'seriously-simple-podcasting' ),
			'all_items'                  => __( 'All Tags', 'seriously-simple-podcasting' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Tag', 'seriously-simple-podcasting' ),
			'update_item'                => __( 'Update Tag', 'seriously-simple-podcasting' ),
			'add_new_item'               => __( 'Add New Tag', 'seriously-simple-podcasting' ),
			'new_item_name'              => __( 'New Tag Name', 'seriously-simple-podcasting' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'seriously-simple-podcasting' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'seriously-simple-podcasting' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'seriously-simple-podcasting' ),
			'not_found'                  => __( 'No tags found.', 'seriously-simple-podcasting' ),
			'menu_name'                  => __( 'Tags', 'seriously-simple-podcasting' ),
		);

		$args = array(
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'podcast_tags' ),
			'capabilities'          => $this->roles_handler->get_podcast_tax_capabilities(),
		);

		return apply_filters( 'ssp_register_taxonomy_args', $args, 'podcast_tags' );
	}

	protected function get_series_args() {
		$series_labels = array(
			'name'                       => __( 'Podcast Series', 'seriously-simple-podcasting' ),
			'singular_name'              => __( 'Series', 'seriously-simple-podcasting' ),
			'search_items'               => __( 'Search Series', 'seriously-simple-podcasting' ),
			'all_items'                  => __( 'All Series', 'seriously-simple-podcasting' ),
			'parent_item'                => __( 'Parent Series', 'seriously-simple-podcasting' ),
			'parent_item_colon'          => __( 'Parent Series:', 'seriously-simple-podcasting' ),
			'edit_item'                  => __( 'Edit Series', 'seriously-simple-podcasting' ),
			'update_item'                => __( 'Update Series', 'seriously-simple-podcasting' ),
			'add_new_item'               => __( 'Add New Series', 'seriously-simple-podcasting' ),
			'new_item_name'              => __( 'New Series Name', 'seriously-simple-podcasting' ),
			'menu_name'                  => __( 'Series', 'seriously-simple-podcasting' ),
			'view_item'                  => __( 'View Series', 'seriously-simple-podcasting' ),
			'popular_items'              => __( 'Popular Series', 'seriously-simple-podcasting' ),
			'separate_items_with_commas' => __( 'Separate series with commas', 'seriously-simple-podcasting' ),
			'add_or_remove_items'        => __( 'Add or remove Series', 'seriously-simple-podcasting' ),
			'choose_from_most_used'      => __( 'Choose from the most used Series', 'seriously-simple-podcasting' ),
			'not_found'                  => __( 'No Series Found', 'seriously-simple-podcasting' ),
			'items_list_navigation'      => __( 'Series list navigation', 'seriously-simple-podcasting' ),
			'items_list'                 => __( 'Series list', 'seriously-simple-podcasting' ),
		);

		$series_args = array(
			'public'            => true,
			'hierarchical'      => true,
			'rewrite'           => array( 'slug' => apply_filters( 'ssp_series_slug', 'series' ) ),
			'labels'            => $series_labels,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'capabilities'      => $this->roles_handler->get_podcast_tax_capabilities(),
		);

		return apply_filters( 'ssp_register_taxonomy_args', $series_args, 'series' );
	}

	/**
	 * Registers podcast meta fields
	 * */
	protected function register_meta() {
		global $wp_version;

		// The enhanced register_meta function is only available for WordPress 4.6+
		if ( version_compare( $wp_version, '4.6', '<' ) ) {
			return;
		}

		// Get all displayed custom fields
		$fields = $this->custom_fields();

		// Add 'filesize_raw' as this is not included in the displayed field options
		$fields['filesize_raw'] = array(
			'meta_description' => __( 'The raw file size of the podcast episode media file in bytes.', 'seriously-simple-podcasting' ),
		);

		foreach ( $fields as $key => $data ) {

			$args = array(
				'type'         => 'string',
				'description'  => isset( $data['meta_description'] ) ? $data['meta_description'] : "",
				'single'       => true,
				'show_in_rest' => true,
			);

			register_meta( 'post', $key, $args );
		}
	}


	/**
	 * Setup custom fields for episodes
	 * @return array Custom fields
	 */
	public function custom_fields() {
		$is_itunes_fields_enabled = get_option( 'ss_podcasting_itunes_fields_enabled' );
		$fields                   = array();

		$fields['episode_type'] = array(
			'name'             => __( 'Episode type:', 'seriously-simple-podcasting' ),
			'description'      => '',
			'type'             => 'radio',
			'default'          => 'audio',
			'options'          => array(
				'audio' => __( 'Audio', 'seriously-simple-podcasting' ),
				'video' => __( 'Video', 'seriously-simple-podcasting' )
			),
			'section'          => 'info',
			'meta_description' => __( 'The type of podcast episode - either Audio or Video', 'seriously-simple-podcasting' ),
		);

		// In v1.14+ the `audio_file` field can actually be either audio or video, but we're keeping the field name here for backwards compatibility
		$fields['audio_file'] = array(
			'name'             => __( 'Episode file:', 'seriously-simple-podcasting' ),
			'description'      => __( 'Upload audio episode files as MP3 or M4A, video episodes as MP4, or paste the file URL.', 'seriously-simple-podcasting' ),
			'type'             => 'file',
			'default'          => '',
			'section'          => 'info',
			'meta_description' => __( 'The full URL for the podcast episode media file.', 'seriously-simple-podcasting' ),
		);

		//
		if ( ssp_is_connected_to_castos() ) {
			$fields['podmotor_file_id'] = array(
				'type'             => 'hidden',
				'default'          => '',
				'section'          => 'info',
				'meta_description' => __( 'Seriously Simple Hosting file id.', 'seriously-simple-podcasting' ),
			);
		}

		$fields['cover_image'] = array(
			'name'             => __( 'Episode Image:', 'seriously-simple-podcasting' ),
			'description'      => __( 'The episode image should be square to display properly in podcasting apps and directories, and should be at least 300x300px in size.', 'seriously-simple-podcasting' ),
			'type'             => 'image',
			'default'          => '',
			'section'          => 'info',
			'meta_description' => __( 'The full URL of image file used in HTML 5 player if available.', 'seriously-simple-podcasting' ),
		);

		$fields['cover_image_id'] = array(
			'type'             => 'hidden',
			'default'          => '',
			'section'          => 'info',
			'meta_description' => __( 'Cover image id.', 'seriously-simple-podcasting' ),
		);

		$fields['duration'] = array(
			'name'             => __( 'Duration:', 'seriously-simple-podcasting' ),
			'description'      => __( 'Duration of podcast file for display (calculated automatically if possible).', 'seriously-simple-podcasting' ),
			'type'             => 'text',
			'default'          => '',
			'section'          => 'info',
			'meta_description' => __( 'The duration of the file for display purposes.', 'seriously-simple-podcasting' ),
		);

		$fields['filesize'] = array(
			'name'             => __( 'File size:', 'seriously-simple-podcasting' ),
			'description'      => __( 'Size of the podcast file for display (calculated automatically if possible).', 'seriously-simple-podcasting' ),
			'type'             => 'text',
			'default'          => '',
			'section'          => 'info',
			'meta_description' => __( 'The size of the podcast episode for display purposes.', 'seriously-simple-podcasting' ),
		);

		if ( ssp_is_connected_to_castos() ) {
			$fields['filesize_raw'] = array(
				'type'             => 'hidden',
				'default'          => '',
				'section'          => 'info',
				'meta_description' => __( 'Raw size of the podcast episode.', 'seriously-simple-podcasting' ),
			);
		}

		$fields['date_recorded'] = array(
			'name'             => __( 'Date recorded:', 'seriously-simple-podcasting' ),
			'description'      => __( 'The date on which this episode was recorded.', 'seriously-simple-podcasting' ),
			'type'             => 'datepicker',
			'default'          => '',
			'section'          => 'info',
			'meta_description' => __( 'The date on which the podcast episode was recorded.', 'seriously-simple-podcasting' ),
		);

		$fields['explicit'] = array(
			'name'             => __( 'Explicit:', 'seriously-simple-podcasting' ),
			'description'      => __( 'Mark this episode as explicit.', 'seriously-simple-podcasting' ),
			'type'             => 'checkbox',
			'default'          => '',
			'section'          => 'info',
			'meta_description' => __( 'Indicates whether the episode is explicit.', 'seriously-simple-podcasting' ),
		);

		$fields['block'] = array(
			'name'             => __( 'Block:', 'seriously-simple-podcasting' ),
			'description'      => __( 'Block this episode from appearing in the iTunes & Google Play podcast libraries.', 'seriously-simple-podcasting' ),
			'type'             => 'checkbox',
			'default'          => '',
			'section'          => 'info',
			'meta_description' => __( 'Indicates whether this specific episode should be blocked from the iTunes and Google Play Podcast libraries.', 'seriously-simple-podcasting' ),
		);

		if ( $is_itunes_fields_enabled && $is_itunes_fields_enabled == 'on' ) {
			/**
			 * New iTunes Tag Announced At WWDC 2017
			 */
			$fields['itunes_episode_number'] = array(
				'name'             => __( 'iTunes Episode Number:', 'seriously-simple-podcasting' ),
				'description'      => __( 'The iTunes Episode Number. Leave Blank If None.', 'seriously-simple-podcasting' ),
				'type'             => 'number',
				'default'          => '',
				'section'          => 'info',
				'meta_description' => __( 'The iTunes Episode Number. Leave Blank If None.', 'seriously-simple-podcasting' ),
			);

			/**
			 * New iTunes Tag Announced At WWDC 2017
			 */
			$fields['itunes_title'] = array(
				'name'             => __( 'iTunes Episode Title (Exclude Your Series / Show Number):', 'seriously-simple-podcasting' ),
				'description'      => __( 'The iTunes Episode Title. NO Series / Show Number Should Be Included.', 'seriously-simple-podcasting' ),
				'type'             => 'text',
				'default'          => '',
				'section'          => 'info',
				'meta_description' => __( 'The iTunes Episode Title. NO Series / Show Number Should Be Included', 'seriously-simple-podcasting' ),
			);

			/**
			 * New iTunes Tag Announced At WWDC 2017
			 */
			$fields['itunes_season_number'] = array(
				'name'             => __( 'iTunes Season Number:', 'seriously-simple-podcasting' ),
				'description'      => __( 'The iTunes Season Number. Leave Blank If None.', 'seriously-simple-podcasting' ),
				'type'             => 'number',
				'default'          => '',
				'section'          => 'info',
				'meta_description' => __( 'The iTunes Season Number. Leave Blank If None.', 'seriously-simple-podcasting' ),
			);

			/**
			 * New iTunes Tag Announced At WWDC 2017
			 */
			$fields['itunes_episode_type'] = array(
				'name'             => __( 'iTunes Episode Type:', 'seriously-simple-podcasting' ),
				'description'      => '',
				'type'             => 'select',
				'default'          => '',
				'options'          => array(
					''        => __( 'Please Select', 'seriously-simple-podcasting' ),
					'full'    => __( 'Full: For Normal Episodes', 'seriously-simple-podcasting' ),
					'trailer' => __( 'Trailer: Promote an Upcoming Show', 'seriously-simple-podcasting' ),
					'bonus'   => __( 'Bonus: For Extra Content Related To a Show', 'seriously-simple-podcasting' )
				),
				'section'          => 'info',
				'meta_description' => __( 'The iTunes Episode Type', 'seriously-simple-podcasting' ),
			);
		}

		return apply_filters( 'ssp_episode_fields', $fields );
	}
}
