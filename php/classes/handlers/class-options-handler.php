<?php

namespace SeriouslySimplePodcasting\Handlers;

/**
 * SSP Options Handler
 *
 * @package Seriously Simple Podcasting
 */
class Options_Handler {

	public $available_subscribe_options = array(
		'acast'            => 'Acast',
		'amazon'           => 'Amazon',
		'anchor'           => 'Anchor',
		'apple_podcasts'   => 'Apple Podcasts',
		'blubrry'          => 'Blubrry',
		'breaker'          => 'Breaker',
		'bullhorn'         => 'Bullhorn',
		'castbox'          => 'CastBox',
		'castro'           => 'Castro',
		'clammr'           => 'Clammr',
		'deezer'           => 'Deezer',
		'downcast'         => 'Downcast',
		'google_play'      => 'Google Play',
		'google_podcasts'  => 'Google Podcasts',
		'himalaya_com'     => 'Himalaya.com',
		'laughable'        => 'Laughable',
		'libsyn'           => 'Libsyn',
		'listen_notes'     => 'Listen Notes',
		'miro'             => 'Miro',
		'mixcloud'         => 'MixCloud',
		'overcast'         => 'Overcast',
		'owltail'          => 'OwlTail',
		'pandora'          => 'Pandora',
		'patreon'          => 'Patreon',
		'player_fm'        => 'Player.fm',
		'plex'             => 'Plex',
		'pocketcasts'      => 'PocketCasts',
		'podbay'           => 'Podbay',
		'podbean'          => 'Podbean',
		'podcast_addict'   => 'Podcast Addict',
		'podcast_republic' => 'Podcast Republic',
		'podcast_de'       => 'Podcast.de',
		'podchaser'        => 'Podchaser',
		'podcoin'          => 'Podcoin',
		'podfan'           => 'Podfan',
		'podkicker'        => 'Podkicker',
		'podknife'         => 'Podknife',
		'podtail'          => 'Podtail',
		'rss'              => 'RSS',
		'rssradio'         => 'RSSRadio',
		'radio_public'     => 'Radio Public',
		'radio_com'        => 'Radio.com',
		'redcircle'        => 'RedCircle',
		'soundcloud'       => 'SoundCloud',
		'spotify'          => 'Spotify',
		'spreaker'         => 'Spreaker',
		'stitcher'         => 'Stitcher',
		'the_podcast_app'  => 'The Podcast App',
		'tunein'           => 'TuneIn',
		'vkontakte'        => 'VKontakte',
		'we_fo'            => 'We.fo',
		'yandex'           => 'Yandex',
		'youtube'          => 'YouTube',
		'custom'           => 'custom',
		'fyyd_de'          => 'fyyd.de',
		'iheartradio'      => 'iHeartRadio',
		'itunes'           => 'iTunes',
		'ivoox'            => 'iVoox',
		'mytuner_radio'    => 'myTuner Radio',
	);

	/**
	 * Build options fields
	 *
	 * @return array Fields to be displayed on options page.
	 */
	public function options_fields() {

		$options = array();

		$subscribe_options_array = $this->get_subscribe_field_options();

		$feed_details_url = add_query_arg(
			array(
				'post_type' => SSP_CPT_PODCAST,
				'page'      => 'podcast_settings',
				'tab'       => 'feed-details',
			)
		);

		$options['subscribe'] = array(
			'title'       => __( 'Distribution options', 'seriously-simple-podcasting' ),
			'description' => sprintf(
				/* translators: %s: URL to feed details */
				__( 'Here you can change the available options which power the Distribution URLs that appear below the player on your website. The Distribution URLS are edited under <a href="%s">Settings -> Feed Details</a><p>Select which Distribution links you want to display to your site visitors:</p>', 'seriously-simple-podcasting' ),
				$feed_details_url
			),
			'fields'      => $subscribe_options_array,
		);

		$options = apply_filters( 'ssp_options_fields', $options );

		return $options;
	}

	/**
	 * Builds the array of field settings for the subscribe links, based on the options stored in the options table.
	 * // @todo this is duplicated from the settings handler, so it should probably be placed in it's own class somewhere
	 *
	 * @return array
	 */
	public function get_subscribe_field_options() {
		$subscribe_field_options = array();

		$subscribe_field_options[] = array(
			'id'          => 'subscribe_options',
			// translators: %s: Service title eg iTunes
			'label'       => __( 'Distribution options', 'seriously-simple-podcasting' ),
			// translators: %s: Service title eg iTunes
			'description' => '',
			'type'        => 'checkbox_multi',
			'options'     => $this->available_subscribe_options,
			'default'     => array(),
		);

		return apply_filters( 'ssp_subscribe_field_options', $subscribe_field_options );
	}

	/**
	 * Get the subscribe urls for an episode
	 *
	 * @param $episode_id
	 * @param $context
	 *
	 * @return mixed|void
	 */
	public function get_subscribe_urls( $episode_id, $context ) {
		$terms             = get_the_terms( $episode_id, 'series' );
		$subscribe_options = get_option( 'ss_podcasting_subscribe_options', array() );
		$subscribe_array   = array();

		if ( ! is_array( $subscribe_options ) ) {
			return apply_filters( 'ssp_episode_subscribe_details', $subscribe_array, $episode_id, $context );
		}

		foreach ( $subscribe_options as $option_key ) {
			if ( ! isset( $this->available_subscribe_options[ $option_key ] ) ) {
				continue;
			}
			// get the main feed url
			$url = get_option( 'ss_podcasting_' . $option_key . '_url', '' );
			// if we're in a series, and the series has a url for this option
			if ( is_array( $terms ) && isset( $terms[0] ) ) {
				$series_url = get_option( 'ss_podcasting_' . $option_key . '_url_' . $terms[0]->term_id, '' );

				if ( $series_url ) {
					$url = $series_url;
				}
			}
			$icon                           = str_replace( '_', '-', $option_key );

			$subscribe_array[ $option_key ] = array(
				'key'   => $option_key,
				'url'   => $url,
				'label' => $this->available_subscribe_options[ $option_key ],
				'class' => $option_key,
				'icon'  => $icon . '.png',
			);
		}

		return apply_filters( 'ssp_episode_subscribe_details', $subscribe_array, $episode_id, $context );
	}

	/**
	 * Gather the legacy subscribe links for a CSV export
	 *
	 * @return array $subscribe_links
	 */
	public function get_old_subscribe_url_data() {
		$subscribe_options = get_option( 'ss_podcasting_subscribe_options', array() );

		$headers = array( 'Feed name' );
		foreach ( $subscribe_options as $key => $label ) {
			$headers[] = $label;
		}

		$links = array( 'Default feed' );
		foreach ( $subscribe_options as $key => $label ) {
			$url     = get_option( 'ss_podcasting_' . $key, '' );
			$links[] = $url;
		}

		$all_series = get_terms(
			array(
				'taxonomy'   => 'series',
				'hide_empty' => false,
			)
		);

		if ( empty( $all_series ) ) {
			return array(
				$headers,
				$links,
			);
		}

		$subscribe_links = array( $headers, $links );

		foreach ( $all_series as $series ) {
			$series_links = array( $series->name . ' feed' );
			foreach ( $subscribe_options as $key => $label ) {
				$url            = get_option( 'ss_podcasting_' . $key . '_' . $series->term_id, '' );
				$series_links[] = $url;
			}
			$subscribe_links[] = $series_links;
		}

		return $subscribe_links;
	}

	/**
	 * Gather the existing subscribe data and send to the browser as a csv download
	 */
	public function send_old_subscribe_links_to_browser_download() {
		$file_data = $this->store_old_subscribe_links_to_a_file();
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Robots: none' );
		header( 'Content-Length: ' . filesize( $file_data['export_file'] ) );
		header( 'Content-Type: application/force-download' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename="' . basename( $file_data['export_file'] ) . '";' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Location: ' . $file_data['export_file_url'] );
		exit;
	}

	/**
	 * Export the current subscribe links to a csv file
	 *
	 * @return array $file_data
	 */
	public function store_old_subscribe_links_to_a_file() {
		$subscribe_links_data = $this->get_old_subscribe_url_data();
		$upload_dir           = wp_upload_dir();
		$export_file          = trailingslashit( $upload_dir['path'] ) . 'subscribe_options.csv';
		$export_file_url      = trailingslashit( $upload_dir['url'] ) . 'subscribe_options.csv';

		$export_file_pointer = fopen( $export_file, 'w' );
		foreach ( $subscribe_links_data as $subscribe_links_items ) {
			fputcsv( $export_file_pointer, $subscribe_links_items );
		}
		fclose( $export_file_pointer );

		return array(
			'export_file'     => $export_file,
			'export_file_url' => $export_file_url,
		);
	}
}
