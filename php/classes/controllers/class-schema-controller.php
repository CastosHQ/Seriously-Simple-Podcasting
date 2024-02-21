<?php

namespace SeriouslySimplePodcasting\Controllers;


use SeriouslySimplePodcasting\Integrations\Yoast\Schema\PodcastEpisode;
use SeriouslySimplePodcasting\Integrations\Yoast\Schema\PodcastSeries;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use Yoast\WP\SEO\Context\Meta_Tags_Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Schema controller
 *
 * @author      Serhiy Zakharchenko
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.7.3
 */
class Schema_Controller {

	/**
	 * @var Episode_Repository
	 * */
	protected $episode_repository;

	/**
	 * @param Episode_Repository $episode_repository
	 */
	public function __construct( $episode_repository ) {
		$this->episode_repository = $episode_repository;

		add_filter( 'wpseo_schema_graph_pieces', array( $this, 'add_graph_pieces' ) );
		add_filter( 'wpseo_schema_webpage', array( $this, 'filter_webpage' ), 10, 2 );
	}

	/**
	 * Adds pieces to the Yoast SEO graph
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function add_graph_pieces( $data ) {
		$data[] = new PodcastEpisode( $this->episode_repository );
		$data[] = new PodcastSeries();

		return $data;
	}

	/**
	 * Changes the Yoast webpage output.
	 *
	 * @param array             $data    The Schema Organization data.
	 * @param Meta_Tags_Context $context Context value object.
	 *
	 * @return array $data The Schema Organization data.
	 */
	public function filter_webpage( $data, $context ) {
		$ssp_post_types = ssp_post_types( true );

		if ( is_singular( $ssp_post_types ) ) {
			$data['mainEntityOfPage'] = $context->canonical . '#/schema/podcast';
			$data['potentialAction']  = array(
				"@type"  => "ListenAction",
				"target" => $context->canonical . '#podcast_player_' . get_the_ID(),
				"object" => array( "@id" => $context->canonical . '#/schema/podcast' ),
			);
		}

		return $data;
	}
}
