<?php

namespace SeriouslySimplePodcasting\Controllers;



use SeriouslySimplePodcasting\Integrations\Yoast\Schema\PodcastEpisode;
use SeriouslySimplePodcasting\Integrations\Yoast\Schema\PodcastSeries;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Schema controller
 *
 * @author      Sergey Zakharchenko
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.7.3
 */
class Schema_Controller extends Controller {

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );

		add_filter( 'wpseo_schema_graph_pieces', array( $this, 'add_graph_pieces' ) );
	}

    public static function add_graph_pieces( $data ) {
		return $data; //todo: remove this line

		$data[] = new PodcastEpisode();
		$data[] = new PodcastSeries();

        return $data;
    }
}
