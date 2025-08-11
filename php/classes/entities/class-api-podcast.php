<?php
/**
 * API_Podcast Entity.
 *
 * @package SeriouslySimplePodcasting
 * */

namespace SeriouslySimplePodcasting\Entities;

/**
 * Class API_Podcast.
 *
 * @since 2.24.0
 * @author Serhiy Zakharchenko
 */
class API_Podcast extends Abstract_API_Entity {

	/**
	 * Podcast ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Series ID associated with the podcast.
	 *
	 * @var int
	 */
	public $series_id;

	/**
	 * Title of the podcast.
	 *
	 * @var string
	 */
	public $podcast_title;

	/**
	 * Whether the podcast feed is protected.
	 *
	 * @var bool
	 */
	public $is_feed_protected;

	/**
	 * Import status for SSP.
	 *
	 * @var string
	 */
	public $ssp_import_status;

	/**
	 * Whether ads are enabled for the podcast.
	 *
	 * @var bool
	 */
	public $ads_enabled;
}
