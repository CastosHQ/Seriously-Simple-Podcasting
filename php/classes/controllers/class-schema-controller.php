<?php

namespace SeriouslySimplePodcasting\Controllers;



use SeriouslySimplePodcasting\Integrations\Yoast\Schema\PodcastEpisode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Main plugin class
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
		$data[] = new PodcastEpisode();

        return $data;
    }
}
