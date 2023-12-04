<?php

namespace SeriouslySimplePodcasting\Controllers;


// Exit if accessed directly.
use SeriouslySimplePodcasting\Handlers\Series_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This is controller for Podcast and other SSP post types (which are enabled via settings) custom behavior.
 *
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       3.0.0
 */
class Series_Controller {

	/**
	 * @var Series_Handler
	 * */
	private $series_handler;

	public function __construct( $series_handler ) {
		$this->series_handler = $series_handler;

		$taxonomy = ssp_series_taxonomy();

		add_action( 'init', array( $this, 'register_taxonomy' ), 11 );
		add_filter( "{$taxonomy}_row_actions", array( $this, 'add_term_actions' ), 10, 2 );
		add_action( 'ssp_triggered_podcast_sync', array( $this, 'update_podcast_sync_status' ), 10, 3 );
		add_filter('term_name', array($this, 'update_default_series_name'), 10, 2);
	}


	/**
	 * @return void
	 */
	public function register_taxonomy() {
		$this->series_handler->register_taxonomy();
	}

	/**
	 * @return void
	 */
	public function enable_primary_series() {
		$this->series_handler->enable_primary_series();
	}

	/**
	 * @param string $name
	 * @param \WP_Term $tag
	 *
	 * @return string
	 */
	public function update_default_series_name( $name, $tag ) {
		if ( ! is_object( $tag ) || $tag->taxonomy != ssp_series_taxonomy() ) {
			return $name;
		}

		if ( $tag->term_id == ssp_get_default_series_id() ) {
			return sprintf( '%s (%s)', $name, 'default' );
		}

		return $name;
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

	/**
	 * @param int $podcast_id
	 * @param array $response
	 * @param string $status
	 *
	 * @return void
	 */
	public function update_podcast_sync_status( $podcast_id, $response, $status ) {

		$this->series_handler->update_sync_status( $podcast_id, $status );
	}
}
