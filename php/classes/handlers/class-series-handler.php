<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Interfaces\Service;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

/**
 * SSP Series Handler
 *
 * @package Seriously Simple Podcasting
 */
class Series_Handler implements Service {

	use Useful_Variables;

	const META_SYNC_STATUS = 'sync_status';

	/**
	 * @var Admin_Notifications_Handler
	 * */
	protected $notices_handler;

	/**
	 * @var Roles_Handler
	 * */
	protected $roles_handler;

	/**
	 * @var Castos_Handler
	 * */
	protected $castos_handler;

	/**
	 * @var Feed_Handler
	 * */
	protected $feed_handler;

	/**
	 * @var Settings_Handler
	 * */
	protected $settings_handler;

	/**
	 * @var Episode_Repository
	 * */
	protected $episode_repository;

	/**
	 * @var int $default_series_id
	 * */
	protected $default_series_id;

	/**
	 * @param Admin_Notifications_Handler $notices_handler
	 * @param Roles_Handler $roles_handler
	 * @param Castos_Handler $castos_handler
	 * @param Settings_Handler $settings_handler
	 * @param Episode_Repository $episode_repository
	 */
	public function __construct( $notices_handler, $roles_handler, $castos_handler, $settings_handler, $episode_repository ) {
		$this->notices_handler  = $notices_handler;
		$this->roles_handler    = $roles_handler;
		$this->castos_handler   = $castos_handler;
		$this->settings_handler = $settings_handler;
		$this->episode_repository = $episode_repository;

		$this->init_useful_variables();
	}

	/**
	 * Register taxonomies
	 * @return void
	 */
	public function register_taxonomy() {
		$podcast_post_types = ssp_post_types();

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

	public function maybe_save_series() {
		if ( ! isset( $_GET['page'] ) || 'podcast_settings' !== $_GET['page'] ) {
			return false;
		}
		if ( ! isset( $_GET['tab'] ) || 'feed-details' !== $_GET['tab'] ) {
			return false;
		}
		if ( ! isset( $_GET['settings-updated'] ) || 'true' !== $_GET['settings-updated'] ) {
			return false;
		}

		// Only do this if this is a Castos Customer
		if ( ! current_user_can( 'manage_podcast' ) || ! ssp_is_connected_to_castos() ) {
			return false;
		}

		if ( ! isset( $_GET['feed-series'] ) ) {
			$feed_series_slug = 'default';
		} else {
			$feed_series_slug = sanitize_text_field( $_GET['feed-series'] );
			if ( empty( $feed_series_slug ) ) {
				return false;
			}
		}

		if ( 'default' === $feed_series_slug ) {
			$series_data              = get_series_data_for_castos( 0 );
			$series_data['series_id'] = 0;
		} else {
			$series                   = get_term_by( 'slug', $feed_series_slug, 'series' );
			$series_data              = get_series_data_for_castos( $series->term_id );
			$series_data['series_id'] = $series->term_id;
		}

		$response = $this->castos_handler->update_podcast_data( $series_data );

		if ( 'success' !== $response['status'] ) {
			return false;
		}

		return true;
	}

	/**
	 * @param int $podcast_id
	 * @param string $status
	 *
	 * @return bool|int|\WP_Error
	 */
	public function update_sync_status( $podcast_id, $status ) {
		return update_term_meta( $podcast_id, self::META_SYNC_STATUS, $status );
	}

	/**
	 * @param int $podcast_id
	 *
	 * @return mixed
	 */
	public function get_sync_status( $podcast_id ) {
		return get_term_meta( $podcast_id, self::META_SYNC_STATUS, true );
	}

	/**
	 * Gets an array of series for the feed details settings.
	 *
	 * @return \WP_Term[]
	 */
	public function get_feed_details_series(){
		// First should always go the default series
		$series = array(
			get_term_by( 'id', $this->default_series_id(), ssp_series_taxonomy() ),
		);

		// Series submenu for feed details
		return array_merge( $series, get_terms( ssp_series_taxonomy(), array(
			'hide_empty' => false,
			'exclude'    => array( $this->default_series_id() ),
		) ) );
	}

	public function enable_default_series() {
		if ( $series_id = $this->create_default_series() ) {
			if ( ssp_is_connected_to_castos() ) {
				$this->castos_handler->update_default_series_id( $series_id );
			}
			$this->assign_orphan_episodes( $series_id );
		}
	}

	/**
	 * @return int
	 */
	public function default_series_id() {
		if ( ! isset( $this->default_series_id ) ) {
			$this->default_series_id = ssp_get_default_series_id();
		}

		return $this->default_series_id;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function default_series_name( $name ) {
		return ssp_get_default_series_name( $name );
	}

	/**
	 * @param int $series_id
	 *
	 * @return void
	 */
	protected function assign_orphan_episodes( $series_id ) {
		$orphan_episode_ids = $this->episode_repository->get_orphan_episode_ids();

		foreach ( $orphan_episode_ids as $post_id ) {
			wp_set_post_terms( $post_id, array( $series_id ), ssp_series_taxonomy(), true );
		}
	}

	/**
	 * @return int|null
	 */
	protected function create_default_series() {
		$series_id = ssp_get_option( 'default_series' );
		if ( $series_id ) {
			$term = get_term_by( 'id', $series_id, ssp_series_taxonomy() );
			if ( $term ) {
				return $series_id;
			}
		}

		$old_default_title = ssp_get_option( 'data_title' );
		$title             = $old_default_title ?: get_bloginfo( 'name' );
		$title             = $title ?: __( 'The First Podcast', 'seriously-simple-podcasting' );
		$series_id         = $this->create_default_series_term( $title );

		if ( $series_id ) {
			// Copy settings only for existing users
			if ( $old_default_title ) {
				$this->copy_default_series_settings( $series_id );
			}

			ssp_update_option( 'default_series', $series_id );
		}

		return $series_id;
	}

	/**
	 * @param $title
	 *
	 * @return int|null
	 */
	protected function create_default_series_term( $title ) {
		$slug     = sanitize_title( $title );
		$taxonomy = ssp_series_taxonomy();
		$res      = wp_insert_term( esc_html( $title ), $taxonomy, compact( 'slug' ) );

		if ( ! $this->is_insert_term_error( $res ) ) {
			return $res['term_id'];
		}

		$slug = 'default-podcast';
		$res  = wp_insert_term( esc_html( $title ), $taxonomy, compact( 'slug' ) );

		if ( ! $this->is_insert_term_error( $res ) ) {
			return $res['term_id'];
		}

		// Another try - maybe the 'default-podcast' series already exists.
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term ) {
			return $term->term_id;
		}

		// Last try - generate random slug
		$slug = wp_generate_password( 12, false );
		$res  = wp_insert_term( esc_html( $title ), $taxonomy, compact( 'slug' ) );

		if ( ! $this->is_insert_term_error( $res ) ) {
			return $res['term_id'];
		}

		return null;
	}

	/**
	 * @param array|\WP_Error $res
	 *
	 * @return bool
	 */
	protected function is_insert_term_error( $res ) {
		return is_wp_error( $res ) || empty( $res['term_id'] );
	}

	/**
	 * Copy the default Feed settings
	 *
	 *
	 * @param int $series_id
	 *
	 * @return void
	 */
	protected function copy_default_series_settings( $series_id ) {

		$feed_details_fields = $this->settings_handler->get_feed_fields( $series_id );

		foreach ( $feed_details_fields as $feed_details_field ) {
			$id    = $feed_details_field['id'];
			$value = ssp_get_option( $id, null );

			if ( isset( $value ) ) {
				ssp_update_option( $id, $value, $series_id );
			}
		}

		$subscribe_options = get_option( 'ss_podcasting_subscribe_options', array() );

		foreach ( $subscribe_options as $option_key ) {
			$field_id = $option_key . '_url';
			$value    = ssp_get_option( $field_id );
			ssp_update_option( $field_id, $value, $series_id );
		}
	}
}
