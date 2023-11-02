<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Interfaces\Service;

/**
 * SSP Series Handler
 *
 * @package Seriously Simple Podcasting
 */
class Series_Handler implements Service {

	const META_SYNC_STATUS = 'sync_status';

	/**
	 * @var Admin_Notifications_Handler $notices_handler
	 * */
	protected $notices_handler;

	/**
	 * @param $notices_handler
	 */
	public function __construct( $notices_handler ) {
		$this->notices_handler = $notices_handler;
		$taxonomy = ssp_series_taxonomy();
		add_filter( "{$taxonomy}_row_actions", array( $this, 'add_term_actions' ), 10, 2 );
		add_action( 'ssp_triggered_podcast_sync', array( $this, 'update_podcast_sync_status' ), 10, 3 );
	}

	/**
	 * Register taxonomies
	 * @return void
	 */
	public function register_taxonomy() {
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

	/**
	 * @param array $actions
	 * @param \WP_Term $term
	 *
	 * @return array
	 */
	public function add_term_actions( $actions, $term ) {

		$link = '<a href="%s">' . __( 'Edit&nbsp;Feed&nbsp;Details', 'seriously-simple-podcasting' ) . '</a>';
		$link = sprintf( $link, sprintf(
			'edit.php?post_type=%s&page=podcast_settings&tab=feed-details&feed-series=%s',
			SSP_CPT_PODCAST,
			$term->slug
		) );

		$actions['edit_feed_details'] = $link;

		return $actions;
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

		$castos_handler = new Castos_Handler();
		$response       = $castos_handler->update_podcast_data( $series_data );

		if ( 'success' !== $response['status'] ) {
			return false;
		}

		return true;
	}

	/**
	 * @param int $podcast_id
	 * @param array $response
	 * @param string $status
	 *
	 * @return void
	 */
	public function update_podcast_sync_status( $podcast_id, $response, $status ) {

		$this->update_sync_status( $podcast_id, $status );
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
}
