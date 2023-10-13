<?php
/**
 * API_Podcast Entity.
 *
 * @package SeriouslySimplePodcasting
 * */

namespace SeriouslySimplePodcasting\Entities;

/**
 * Class API_Podcast.
 * @since 2.24.0
 * @author Serhiy Zakharchenko
 */
class API_Podcast extends Abstract_API_Entity {

	/**
	 * @var int
	 * */
	public $id;

	/**
	 * @var int
	 * */
	public $series_id;

	/**
	 * @var string
	 * */
	public $podcast_title;

	/**
	 * @var bool
	 * */
	public $is_feed_protected;

	/**
	 * @var string
	 * */
	public $ssp_import_status;

	/**
	 * @var bool
	 * */
	public $ads_enabled;
}

