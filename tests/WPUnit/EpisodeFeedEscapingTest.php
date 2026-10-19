<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Controllers\App_Controller;

/**
 * Verifies that episode values remain text in the public RSS feed.
 *
 * @since 3.17.1
 */
class EpisodeFeedEscapingTest extends \Codeception\TestCase\WPTestCase {

	const AUDIO = 'https://example.com/episode.mp3';

	/** @var int */
	private $series_id;

	/** @var array */
	private $server_backup;

	/** @var array */
	private $options_backup;

	/** @var array */
	private $post_globals_backup;

	/** @var int */
	private $user_backup;

	protected function setUp(): void {
		parent::setUp();

		$this->user_backup = get_current_user_id();
		wp_set_current_user( 0 );
		$this->server_backup = $_SERVER;
		// Other tests' go_to() calls can leave an unrelated URI in the feed's self link.
		$_SERVER['REQUEST_URI'] = '/';
		$this->options_backup  = array();
		foreach ( array( 'ss_podcasting_use_post_types' => array(), 'ss_podcasting_itunes_fields_enabled' => 'on' ) as $option => $value ) {
			$this->options_backup[ $option ] = get_option( $option, null );
			update_option( $option, $value );
		}

		$this->post_globals_backup = array();
		foreach ( array( 'post', 'id', 'authordata', 'currentday', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages' ) as $key ) {
			if ( array_key_exists( $key, $GLOBALS ) ) {
				$this->post_globals_backup[ $key ] = $GLOBALS[ $key ];
			}
		}

		// WordPress unregisters custom post types and taxonomies between tests.
		ssp_app()->get_service( 'cpt_podcast_handler' )->register_post_type();
		ssp_app()->series_controller->register_taxonomy();
		$this->series_id = self::factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		// DOING_AJAX from earlier tests must not turn feed errors into process exits.
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_wp_die_handler' ) );
		add_filter( 'wp_die_json_handler', array( $this, 'get_wp_die_handler' ) );
	}

	protected function tearDown(): void {
		$_SERVER = $this->server_backup;
		wp_set_current_user( $this->user_backup );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_wp_die_handler' ) );
		remove_filter( 'wp_die_json_handler', array( $this, 'get_wp_die_handler' ) );
		foreach ( $this->options_backup as $option => $value ) {
			null === $value ? delete_option( $option ) : update_option( $option, $value );
		}
		foreach ( array( 'post', 'id', 'authordata', 'currentday', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages' ) as $key ) {
			if ( array_key_exists( $key, $this->post_globals_backup ) ) {
				$GLOBALS[ $key ] = $this->post_globals_backup[ $key ];
			} else {
				unset( $GLOBALS[ $key ] );
			}
		}

		parent::tearDown();
	}

	/**
	 * Episode meta containing markup remains literal text in its feed element.
	 *
	 * @dataProvider episode_meta_text_provider
	 */
	public function testEpisodeMetaRemainsTextInFeedElements( $meta_key, $tag ) {
		$text       = 'Before <em>episode detail</em> & after';
		$episode_id = $this->create_episode();
		update_post_meta( $episode_id, $meta_key, $text );

		$xpath = $this->assert_single_episode_feed( $this->render_feed(), $episode_id );

		$this->assertSame( $text, $xpath->evaluate( 'string(/rss/channel/item/' . $tag . ')' ) );
		$this->assertCount( 0, $xpath->query( '/rss/channel/item/' . $tag . '/*' ) );
	}

	public static function episode_meta_text_provider() {
		return array(
			'episode type'   => array( 'itunes_episode_type', 'itunes:episodeType' ),
			'episode number' => array( 'itunes_episode_number', 'itunes:episode' ),
			'season number'  => array( 'itunes_season_number', 'itunes:season' ),
			'duration'       => array( 'duration', 'itunes:duration' ),
		);
	}

	/** @dataProvider enclosure_length_provider */
	public function testFileSizeMetaProducesNumericEnclosureLength( $filesize, $expected_length ) {
		$episode_id = $this->create_episode();
		update_post_meta( $episode_id, 'filesize_raw', $filesize );

		$xpath = $this->assert_single_episode_feed( $this->render_feed(), $episode_id );

		$this->assertSame( $expected_length, $xpath->evaluate( 'string(/rss/channel/item/enclosure/@length)' ) );
	}

	public static function enclosure_length_provider() {
		return array(
			'HTML-only entity' => array( '&nbsp;', '0' ),
			'element markup'   => array( '"><em>size</em>', '0' ),
			'valid filesize'   => array( '12345678', '12345678' ),
		);
	}

	/** @dataProvider cdata_title_provider */
	public function testItunesTitlePreservesCdataTerminators( $title ) {
		$episode_id = $this->create_episode();
		update_post_meta( $episode_id, 'itunes_title', $title );

		$xpath = $this->assert_single_episode_feed( $this->render_feed(), $episode_id );

		$this->assertSame( $title, $xpath->evaluate( 'string(/rss/channel/item/itunes:title)' ) );
	}

	public static function cdata_title_provider() {
		return array(
			'literal terminators'   => array( 'Before ]]> after & another ]]> ending' ),
			'terminator and markup' => array(
				'Before ]]><em>title detail</em> & after',
			),
		);
	}

	public function testValidEpisodeFeedValuesAreUnchanged() {
		$episode_id = $this->create_episode( "Jane's Rock & Roll" );
		$values     = array(
			'itunes_title'          => "Jane's bonus & behind the scenes",
			'itunes_episode_type'   => 'full',
			'itunes_episode_number' => '12',
			'itunes_season_number'  => '3',
			'duration'             => '01:02:03',
			'explicit'             => 'on',
			'block'                => 'on',
		);
		foreach ( $values as $key => $value ) {
			update_post_meta( $episode_id, $key, $value );
		}

		$image = static function () {
			return 'https://example.com/cover.jpg';
		};
		add_filter( 'ssp_feed_item_image', $image );
		try {
			$feed = $this->render_feed();
		} finally {
			remove_filter( 'ssp_feed_item_image', $image );
		}
		$xpath = $this->assert_single_episode_feed( $feed, $episode_id );

		$this->assertSame( 'Jane’s Rock & Roll', $xpath->evaluate( 'string(/rss/channel/item/title)' ) );
		$this->assertSame( 'Jane’s Rock & Roll', $xpath->evaluate( 'string(/rss/channel/item/ssp:image/ssp:title)' ) );
		$this->assertStringContainsString( '<title>Jane&#8217;s Rock &#038; Roll</title>', $feed );

		$expected = array(
			'itunes:title'        => $values['itunes_title'],
			'itunes:episodeType'  => 'full',
			'itunes:episode'      => '12',
			'itunes:season'       => '3',
			'itunes:duration'     => '01:02:03',
			'itunes:explicit'     => 'true',
			'itunes:block'        => 'yes',
			'googleplay:explicit' => 'Yes',
			'googleplay:block'    => 'yes',
		);
		foreach ( $expected as $tag => $value ) {
			$this->assertSame( $value, $xpath->evaluate( 'string(/rss/channel/item/' . $tag . ')' ), $tag );
		}
	}

	/**
	 * The remaining CDATA sinks must also preserve terminators as visible text.
	 *
	 * @dataProvider cdata_field_provider
	 */
	public function testOtherCdataFieldsPreserveTerminatorsAsText( $field, $tags ) {
		$episode_id = $this->create_episode();
		$text       = 'Before ]]><em>episode detail</em> & after ]]>';
		$filter     = static function ( $args, $post_id ) use ( $field, $text, $episode_id ) {
			if ( $episode_id === $post_id ) {
				$args[ $field ] = $text;
			}
			return $args;
		};
		add_filter( 'ssp_feed_item_args', $filter, 10, 2 );
		try {
			$feed = $this->render_feed();
		} finally {
			remove_filter( 'ssp_feed_item_args', $filter );
		}
		$xpath = $this->assert_single_episode_feed( $feed, $episode_id );

		foreach ( $tags as $tag ) {
			$this->assertSame( $text, $xpath->evaluate( 'string(/rss/channel/item/' . $tag . ')' ), $tag );
		}
	}

	public static function cdata_field_provider() {
		return array(
			'author'      => array( 'author', array( 'dc:creator', 'itunes:author' ) ),
			'description' => array( 'description', array( 'description', 'content:encoded' ) ),
			'subtitle'    => array( 'itunes_subtitle', array( 'itunes:subtitle' ) ),
			'summary'     => array( 'itunes_summary', array( 'itunes:summary' ) ),
			'Google Play' => array( 'gp_description', array( 'googleplay:description' ) ),
		);
	}

	/** @dataProvider ordinary_cdata_provider */
	public function testCdataEscapingLeavesOtherStringsUnchanged( $text ) {
		$this->assertSame( $text, ssp_esc_cdata( $text ) );
	}

	public static function ordinary_cdata_provider() {
		return array(
			'brackets' => array( '<![CDATA[unfinished ]] and >' ),
		);
	}

	private function create_episode( $title = 'Original episode' ) {
		$episode_id = self::factory()->post->create(
			array(
				'post_type'    => SSP_CPT_PODCAST,
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => 'An ordinary episode description.',
			)
		);
		update_post_meta( $episode_id, 'audio_file', self::AUDIO );
		update_post_meta( $episode_id, 'filesize_raw', '1024' );
		wp_set_object_terms( $episode_id, array( $this->series_id ), ssp_series_taxonomy() );

		return $episode_id;
	}

	/** Uses the real feed query, controller and templates, not a fabricated item. */
	private function render_feed() {
		$property = new \ReflectionProperty( App_Controller::class, 'feed_controller' );
		$property->setAccessible( true );
		$error_reporting = error_reporting();
		try {
			return $property->getValue( ssp_app() )->get_podcast_feed( $this->series_id );
		} finally {
			// Feed_Handler suppresses errors for public output; do not suppress later tests.
			error_reporting( $error_reporting );
			wp_reset_postdata();
		}
	}

	/** Parses the whole feed and verifies the expected episode and enclosure. */
	private function assert_single_episode_feed( $feed, $episode_id ) {
		$document = $this->parse_xml( $feed );
		$xpath    = new \DOMXPath( $document );
		foreach ( array(
			'itunes'     => 'http://www.itunes.com/dtds/podcast-1.0.dtd',
			'googleplay' => 'http://www.google.com/schemas/play-podcasts/1.0',
			'dc'         => 'http://purl.org/dc/elements/1.1/',
			'content'    => 'http://purl.org/rss/1.0/modules/content/',
			'ssp'        => 'https://castos.com/seriously-simple-podcasting/namespace/1.0',
		) as $prefix => $namespace ) {
			$xpath->registerNamespace( $prefix, $namespace );
		}

		$this->assertCount( 1, $xpath->query( '//item' ), 'Episode values must not create additional feed items.' );
		$this->assertSame( ssp_episode_guid( $episode_id ), $xpath->evaluate( 'string(/rss/channel/item/guid)' ) );
		$this->assertCount( 1, $xpath->query( '//enclosure' ) );
		$this->assertSame( self::AUDIO, $xpath->evaluate( 'string(/rss/channel/item/enclosure/@url)' ) );
		$this->assertCount( 0, $xpath->query( '//em' ), 'Episode values must remain literal text.' );

		return $xpath;
	}

	private function parse_xml( $xml ) {
		$document = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );
		try {
			libxml_clear_errors();
			$loaded = $document->loadXML( $xml, LIBXML_NONET );
			$errors = array_map( static function ( $error ) {
				return trim( $error->message );
			}, libxml_get_errors() );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
		}
		$this->assertTrue( $loaded, 'Feed XML must be well-formed: ' . implode( '; ', $errors ) );

		return $document;
	}
}
