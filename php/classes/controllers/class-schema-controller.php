<?php

namespace SeriouslySimplePodcasting\Controllers;


use SeriouslySimplePodcasting\Integrations\Yoast\Schema\PodcastEpisode;
use SeriouslySimplePodcasting\Integrations\Yoast\Schema\PodcastSeries;
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

	public function __construct() {
		add_filter( 'wpseo_schema_graph_pieces', array( $this, 'add_graph_pieces' ) );
		add_filter( 'wpseo_schema_webpage', array( $this, 'filter_webpage' ), 10, 2 );
	}

	public static function add_graph_pieces( $data ) {
		$data[] = new PodcastEpisode();
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
