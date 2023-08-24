<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Interfaces\Service;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

/**
 * SSP Custom Post Type Podcast Handler
 *
 * @package Seriously Simple Podcasting
 * @since 2.6.3 Moved from the admin controller class
 */
class CPT_Podcast_Handler implements Service {

	/**
	 * @deprecated
	 * @see ssp_series_taxonomy() instead
	 * */
	const TAXONOMY_SERIES = 'series';

	const DEFAULT_SERIES_SLUG = 'podcasts';


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
		$this->register_meta();
	}

	/**
	 * Register taxonomies
	 * @return void
	 */
	public function register_taxonomies() {
		$podcast_post_types = ssp_post_types( true );

		$args = $this->get_series_args();
		$this->register_series_taxonomy( $podcast_post_types, $args );
		$this->listen_updating_series_slug( $podcast_post_types, $args );

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

	/**
	 * @param array $podcast_post_types
	 * @param array $args
	 *
	 * @return void
	 */
	protected function listen_updating_series_slug( $podcast_post_types, $args ) {
		add_filter( 'pre_update_option_ss_podcasting_series_slug', function ( $slug ) use ( $podcast_post_types, $args ) {
			$forbidden = array(
				'podcast',
				'category'
			);

			$slug = empty( $slug ) || in_array( $slug, $forbidden ) ? ssp_series_slug() : $slug;

			$args['rewrite']['slug'] = $slug;

			// Reregister series taxonomy with the new slug and flush rewrite rules after that.
			$this->register_series_taxonomy( $podcast_post_types, $args );
			flush_rewrite_rules();

			return $slug;
		} );
	}

	/**
	 * @param array $podcast_post_types
	 * @param array $args
	 *
	 * @return void
	 */
	protected function register_series_taxonomy( $podcast_post_types, $args ) {
		register_taxonomy( ssp_series_taxonomy(), $podcast_post_types, $args );
	}

	protected function get_podcast_args() {
		$labels = array(
			'name'                  => _x( 'Episode', 'post type general name', 'seriously-simple-podcasting' ),
			'singular_name'         => _x( 'Episode', 'post type singular name', 'seriously-simple-podcasting' ),
			'add_new'               => __( 'Add New Episode', 'seriously-simple-podcasting' ),
			'add_new_item'          => __( 'Add New Episode', 'seriously-simple-podcasting' ),
			'edit_item'             => __( 'Edit Episode', 'seriously-simple-podcasting' ),
			'new_item'              => __( 'New Episode', 'seriously-simple-podcasting' ),
			'all_items'             => __( 'All Episodes', 'seriously-simple-podcasting' ),
			'view_item'             => __( 'View Episode', 'seriously-simple-podcasting' ),
			'search_items'          => __( 'Search Episodes', 'seriously-simple-podcasting' ),
			'not_found'             => __( 'No Episodes Found', 'seriously-simple-podcasting' ),
			'not_found_in_trash'    => __( 'No Episodes In Trash', 'seriously-simple-podcasting' ),
			'parent_item_colon'     => '',
			'menu_name'             => __( 'Podcasting', 'seriously-simple-podcasting' ),
			'filter_items_list'     => __( 'Filter Episode list', 'seriously-simple-podcasting' ),
			'items_list_navigation' => __( 'Episode list navigation', 'seriously-simple-podcasting' ),
			'items_list'            => __( 'Episode list', 'seriously-simple-podcasting' ),
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
			'name'                       => __( 'Podcasts', 'seriously-simple-podcasting' ),
			'singular_name'              => __( 'Podcast', 'seriously-simple-podcasting' ),
			'search_items'               => __( 'Search Podcasts', 'seriously-simple-podcasting' ),
			'all_items'                  => __( 'All Podcasts', 'seriously-simple-podcasting' ),
			'parent_item'                => __( 'Parent Podcast', 'seriously-simple-podcasting' ),
			'parent_item_colon'          => __( 'Parent Podcast:', 'seriously-simple-podcasting' ),
			'edit_item'                  => __( 'Edit Podcast', 'seriously-simple-podcasting' ),
			'update_item'                => __( 'Update Podcast', 'seriously-simple-podcasting' ),
			'add_new_item'               => __( 'Add New Podcast', 'seriously-simple-podcasting' ),
			'new_item_name'              => __( 'New Podcast Name', 'seriously-simple-podcasting' ),
			'menu_name'                  => __( 'All Podcasts', 'seriously-simple-podcasting' ),
			'view_item'                  => __( 'View Podcast', 'seriously-simple-podcasting' ),
			'popular_items'              => __( 'Popular Podcasts', 'seriously-simple-podcasting' ),
			'separate_items_with_commas' => __( 'Separate podcasts with commas', 'seriously-simple-podcasting' ),
			'add_or_remove_items'        => __( 'Add or remove Podcasts', 'seriously-simple-podcasting' ),
			'choose_from_most_used'      => __( 'Choose from the most used Podcasts', 'seriously-simple-podcasting' ),
			'not_found'                  => __( 'No Podcasts Found', 'seriously-simple-podcasting' ),
			'items_list_navigation'      => __( 'Podcasts list navigation', 'seriously-simple-podcasting' ),
			'items_list'                 => __( 'Podcasts list', 'seriously-simple-podcasting' ),
		);

		$series_args = array(
			'public'            => true,
			'hierarchical'      => true,
			'rewrite'           => array( 'slug' => ssp_series_slug() ),
			'labels'            => $series_labels,
			'show_in_rest'      => true,
			'rest_base'         => 'series',
			'show_admin_column' => true,
			'capabilities'      => $this->roles_handler->get_podcast_tax_capabilities(),
		);

		return apply_filters( 'ssp_register_taxonomy_args', $series_args, ssp_series_taxonomy() );
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
		$is_connected_to_castos = ssp_is_connected_to_castos();

		if ( $is_connected_to_castos ) {
			$fields['sync_status'] = array(
				'name'             => __( 'Sync status:', 'seriously-simple-podcasting' ),
				'type'             => 'sync_status',
				'default'          => Sync_Status::SYNC_STATUS_NONE,
				'section'          => 'info',
			);
		}

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
			'type'             => 'episode_file',
			'default'          => '',
			'section'          => 'info',
			'meta_description' => __( 'The full URL for the podcast episode media file.', 'seriously-simple-podcasting' ),
		);

		//
		if ( $is_connected_to_castos ) {
			$fields['podmotor_file_id'] = array(
				'type'             => 'hidden',
				'default'          => '',
				'section'          => 'info',
				'meta_description' => __( 'Seriously Simple Hosting file id.', 'seriously-simple-podcasting' ),
			);
		} else {
			$description = __( 'Get lower bandwidth fees, file storage, and better stats when hosting with Castos.', 'seriously-simple-podcasting' );
			$btn         = array(
				'title' => 'Try Castos for free',
				'url'   => 'https://castos.com/podcast-hosting-wordpress/?utm_source=ssp&utm_medium=episode-file-box&utm_campaign=upgrade'
			);

			$fields['audio_file']['description'] .= ssp_upsell_field( $description, $btn );
		}

		$post = get_post();
		$post_title = $post ? $post->post_title : '';
		if ( $post ) {
			$podcasts = ssp_get_episode_podcasts( $post->ID );
			if ( isset( $podcasts[0] ) && $podcasts[0] instanceof \WP_Term ) {
				$podcast_title = $podcasts[0]->name;
			}
		}

		if ( empty( $podcast_title ) ) {
			$podcast_title = ssp_get_option( 'data_title' );
		}

		$fields['cover_image'] = array(
			'name'             => __( 'Episode Image:', 'seriously-simple-podcasting' ),
			'description'      => __( 'The episode image should be square to display properly in podcasting apps and directories, and should be at least 300x300px in size.', 'seriously-simple-podcasting' ) .
			'<br>' . ssp_dynamo_btn( $post_title, $podcast_title, 'Create an episode image with our free tool' ),
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

		if ( $is_connected_to_castos ) {
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
				'name'             => __( 'iTunes Episode Title (Exclude Your Podcast / Show Number):', 'seriously-simple-podcasting' ),
				'description'      => __( 'The iTunes Episode Title. NO Podcast / Show Number Should Be Included.', 'seriously-simple-podcasting' ),
				'type'             => 'text',
				'default'          => '',
				'section'          => 'info',
				'meta_description' => __( 'The iTunes Episode Title. NO Podcast / Show Number Should Be Included', 'seriously-simple-podcasting' ),
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
