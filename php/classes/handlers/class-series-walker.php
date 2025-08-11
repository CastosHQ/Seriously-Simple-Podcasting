<?php

namespace SeriouslySimplePodcasting\Handlers;

/**
 * Class Series_Walker
 *
 * Customizes the series taxonomy checklist output in the admin area.
 * Extends WordPress core Walker_Category_Checklist class.
 *
 * @package Seriously Simple Podcasting
 */
class Series_Walker extends \Walker_Category_Checklist {

	/**
	 * Series handler instance.
	 *
	 * @var Series_Handler
	 */
	protected $series_handler;

	/**
	 * Default series term ID.
	 *
	 * @var int
	 */
	protected $default_series_id;

	/**
	 * Series_Walker constructor.
	 *
	 * @param Series_Handler $series_handler Series handler instance.
	 */
	public function __construct( $series_handler ) {
		$this->series_handler = $series_handler;
	}

	/**
	 * Start the element output.
	 *
	 * @param string  $output Used to append additional content (passed by reference).
	 * @param WP_Term $data_object The current term object.
	 * @param int     $depth Depth of the term in reference to parents. Default 0.
	 * @param array   $args An array of arguments. See {@see wp_terms_checklist()}.
	 * @param int     $current_object_id Optional. ID of the current term. Default 0.
	 *
	 * @since 5.9.0 Renamed `$category` to `$data_object` and `$id` to `$current_object_id`
	 *              to match parent class for PHP 8 named parameter support.
	 *
	 * @see Walker::start_el()
	 *
	 * @since 2.5.1
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = array(), $current_object_id = 0 ) {

		if ( $this->default_series_id() === $data_object->term_id ) {
			$current_output = '';
			parent::start_el( $current_output, $data_object, $depth, $args, $current_object_id );
			$current_output = str_replace( $data_object->name, $this->series_handler->default_series_name( $data_object->name ), $current_output );
			$output        .= $current_output;

			return;
		}

		parent::start_el( $output, $data_object, $depth, $args, $current_object_id );
	}


	/**
	 * Gets the default series term ID.
	 *
	 * @return int Default series term ID.
	 */
	protected function default_series_id() {
		if ( ! $this->default_series_id ) {
			$this->default_series_id = ssp_get_default_series_id();
		}

		return $this->default_series_id;
	}
}
