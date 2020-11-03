<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Helpers\Log_Helper;

/**
 * SSP Options Handler
 *
 * @package Seriously Simple Podcasting
 */
class Options_Handler {

	protected $available_subscribe_options = array(
		'acast'            => 'Acast',
		'amazon-alexa'     => 'Amazon Alexa',
		'anchor'           => 'Anchor',
		'apple-podcasts'   => 'Apple Podcasts',
		'blubrry'          => 'Blubrry',
		'breaker'          => 'Breaker',
		'bullhorn'         => 'Bullhorn',
		'castbox'          => 'CastBox',
		'castro'           => 'Castro',
		'clammr'           => 'Clammr',
		'deezer'           => 'Deezer',
		'downcast'         => 'Downcast',
		'google-play'      => 'Google Play',
		'google-podcasts'  => 'Google Podcasts',
		'himalaya.com'     => 'Himalaya.com',
		'laughable'        => 'Laughable',
		'libsyn'           => 'Libsyn',
		'listen-notes'     => 'Listen Notes',
		'miro'             => 'Miro',
		'mixcloud'         => 'MixCloud',
		'overcast'         => 'Overcast',
		'owltail'          => 'OwlTail',
		'pandora'          => 'Pandora',
		'patreon'          => 'Patreon',
		'player.fm'        => 'Player.fm',
		'plex'             => 'Plex',
		'pocketcasts'      => 'PocketCasts',
		'podbay'           => 'Podbay',
		'podbean'          => 'Podbean',
		'podcast-addict'   => 'Podcast Addict',
		'podcast-republic' => 'Podcast Republic',
		'podcast.de'       => 'Podcast.de',
		'podchaser'        => 'Podchaser',
		'podcoin'          => 'Podcoin',
		'podfan'           => 'Podfan',
		'podkicker'        => 'Podkicker',
		'podknife'         => 'Podknife',
		'podtail'          => 'Podtail',
		'rss'              => 'RSS',
		'rssradio'         => 'RSSRadio',
		'radio-public'     => 'Radio Public',
		'radio.com'        => 'Radio.com',
		'redcircle'        => 'RedCircle',
		'soundcloud'       => 'SoundCloud',
		'spotify'          => 'Spotify',
		'spreaker'         => 'Spreaker',
		'stitcher'         => 'Stitcher',
		'the-podcast-app'  => 'The Podcast App',
		'tunein'           => 'TuneIn',
		'vkontakte'        => 'VKontakte',
		'we.fo'            => 'We.fo',
		'yandex'           => 'Yandex',
		'youtube'          => 'YouTube',
		'custom'           => 'custom',
		'fyyd.de'          => 'fyyd.de',
		'iheartradio'      => 'iHeartRadio',
		'itunes'           => 'iTunes',
		'ivoox'            => 'iVoox',
		'mytuner-radio'    => 'myTuner Radio',
	);

	/**
	 * Build options fields
	 *
	 * @return array Fields to be displayed on options page.
	 */
	public function options_fields() {
		global $wp_post_types;

		$post_type_options = array();

		// Set options for post type selection.
		foreach ( $wp_post_types as $post_type => $data ) {

			$disallowed_post_types = array(
				'page',
				'attachment',
				'revision',
				'nav_menu_item',
				'wooframework',
				'podcast',
			);
			if ( in_array( $post_type, $disallowed_post_types, true ) ) {
				continue;
			}

			$post_type_options[ $post_type ] = $data->labels->name;
		}

		$options = array();

		$subscribe_options_array = $this->get_subscribe_field_options();

		$feed_details_url = add_query_arg(
			array(
				'post_type' => 'podcast',
				'page'      => 'podcast_settings',
				'tab'       => 'feed-details',
			)
		);

		$options['subscribe'] = array(
			'title'       => __( 'Subscribe options', 'seriously-simple-podcasting' ),
			'description' => sprintf(
				/* translators: %s: URL to feed details */
				__( 'Here you can change the available options which power the Subscribe URLs that appear below the player on your website. The Subscribe URLS are edited under <a href="%s">Settings -> Feed Details</a>', 'seriously-simple-podcasting' ),
				$feed_details_url
			),
			'fields'      => $subscribe_options_array,
		);

		$options = apply_filters( 'ssp_options_fields', $options );

		return $options;
	}

	/**
	 * Inject HTML content into the Options form
	 *
	 * @return string
	 */
	public function get_extra_html_content() {
		// Add the 'Add new subscribe option'

		$html  = '<p>Click "Add subscribe option" below to add a new subscribe URL field</p>';
		$html .= '<p class="add">' . "\n";
		$html .= '<input id="ssp-options-add-subscribe" type="button" class="button-primary" value="' . esc_attr( __( 'Add subscribe option', 'seriously-simple-podcasting' ) ) . '" />' . "\n";
		$html .= '</p>' . "\n";

		return $html;
	}

	/**
	 * Builds the array of field settings for the subscribe links, based on the options stored in the options table.
	 * // @todo this is duplicated from the settings handler, so it should probably be placed in it's own class somewhere
	 *
	 * @return array
	 */
	public function get_subscribe_field_options() {
		$subscribe_field_options = array();
		$subscribe_options       = get_option( 'ss_podcasting_subscribe_options', array() );
		if ( empty( $subscribe_options ) ) {
			return $subscribe_field_options;
		}

		$count = 1;
		foreach ( $this->available_subscribe_options as $key => $title ) {
			$subscribe_field_options[] = array(
				'id'          => 'subscribe_option_' . $count,
				// translators: %s: Service title eg iTunes
				'label'       => sprintf( __( '%s', 'seriously-simple-podcasting' ), $title ),
				// translators: %1$s and %2$s: HTML anchor opening and closing tags
				'description' => sprintf( __( '%1$sDelete%2$s', 'seriously-simple-podcasting' ), '<a class="delete_subscribe_option" data-count="' . $count . '" data-option="' . $key . '" href="#delete">', '</a>' ),
				'type'        => 'checkbox',
				'default'     => 'off', // check against stored options and turn on as needed
				'placeholder' => __( 'Subscribe button label', 'seriously-simple-podcasting' ),
				'callback'    => 'wp_strip_all_tags',
				'class'       => 'text subscribe-option',
			);
			$count ++;
		}

		return apply_filters( 'ssp_subscribe_field_options', $subscribe_field_options );
	}

	/**
	 * Update the ss_podcasting_subscribe_options array based on the individual ss_podcasting_subscribe_option_ options
	 *
	 * @return bool
	 */
	public function update_subscribe_options() {
		$continue          = true;
		$count             = 0;
		$subscribe_options = array();
		while ( false !== $continue ) {
			$count ++;
			$subscribe_option = get_option( 'ss_podcasting_subscribe_option_' . $count, '' );
			if ( empty( $subscribe_option ) ) {
				$continue = false;
			} else {
				$subscribe_key                       = $this->create_subscribe_option_key( $subscribe_option );
				$subscribe_options[ $subscribe_key ] = $subscribe_option;
			}
		}
		update_option( 'ss_podcasting_subscribe_options', $subscribe_options );

		return true;
	}

	/**
	 * Inserts a new option into the ss_podcasting_subscribe_options array
	 *
	 * @return mixed|void
	 */
	public function insert_subscribe_option() {
		$subscribe_options            = get_option( 'ss_podcasting_subscribe_options', array() );
		$subscribe_options['new_url'] = 'New URL field label';
		update_option( 'ss_podcasting_subscribe_options', $subscribe_options );

		return $subscribe_options;
	}

	/**
	 * Deletes a subscribe option, based on it's key
	 *
	 * @param $option_key
	 *
	 * @return mixed|void
	 */
	public function delete_subscribe_option( $option_key, $option_count ) {
		$subscribe_options = get_option( 'ss_podcasting_subscribe_options', array() );
		if ( isset( $subscribe_options[ $option_key ] ) ) {
			unset( $subscribe_options[ $option_key ] );
		}
		update_option( 'ss_podcasting_subscribe_options', $subscribe_options );

		// delete actual option from database eg ss_podcasting_subscribe_option_7
		$subscribe_option_key = 'ss_podcasting_subscribe_option_' . $option_count;
		delete_option( $subscribe_option_key );

		return $subscribe_options;
	}

	/**
	 * Converts the Subscribe option label to the relevant key
	 *
	 * @param string $subscribe_option
	 *
	 * @return string $subscribe_key
	 */
	public function create_subscribe_option_key( $subscribe_option ) {
		$subscribe_key = preg_replace( '/[^A-Za-z]/', '_', $subscribe_option );
		$subscribe_key = strtolower( $subscribe_key . '_url' );

		return $subscribe_key;
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

		$subscribe_array = array();
		foreach ( $subscribe_options as $key => $label ) {
			// get the main feed url
			$url = get_option( 'ss_podcasting_' . $key, '' );
			// if we're in a series, and the series has a url for this option
			if ( is_array( $terms ) ) {
				if ( isset( $terms[0] ) ) {
					if ( false !== get_option( 'ss_podcasting_' . $key . '_' . $terms[0]->term_id ) ) {
						$url = get_option( 'ss_podcasting_' . $key . '_' . $terms[0]->term_id, '' );
					}
				}
			}

			/**
			 * extract icon name from $key
			 */
			$icon_name = \str_replace( array( '_url', '_' ), array( '', '-' ), $key );

			$subscribe_array[ $key ] = array(
				'url'   => $url,
				'label' => $label,
				'icon'  => $icon_name . '.png',
			);
		}

		return apply_filters( 'ssp_episode_subscribe_details', $subscribe_array, $episode_id, $context );

	}

	/**
	 * Returns all subscribe urls regardless of episode or series
	 *
	 * @return array
	 */
	public function get_all_subscribe_urls() {
		$subscribe_options = get_option( 'ss_podcasting_subscribe_options', array() );
		$all_series        = get_terms(
			array(
				'taxonomy'   => 'series',
				'hide_empty' => false,
			)
		);
		$subscribe_array   = array();
		foreach ( $subscribe_options as $key => $label ) {
			$url = get_option( 'ss_podcasting_' . $key, '' );
			if ( is_array( $all_series ) ) {
				foreach ( $all_series as $series ) {
					if ( false !== get_option( 'ss_podcasting_' . $key . '_' . $series->term_id ) ) {
						$url = get_option( 'ss_podcasting_' . $key . '_' . $series->term_id, '' );
					}
				}
			}
			$icon_name                     = \str_replace( array( '_url', '_' ), array( '', '-' ), $key );
			$subscribe_array[ $icon_name ] = array(
				'url'   => $url,
				'label' => $label,
				'icon'  => $icon_name . '.png',
			);
		}

		return $subscribe_array;
	}

	/**
	 * Gather the subscribe links for a CSV export
	 *
	 * @return array $subscribe_links
	 */
	public function get_subscribe_url_data() {
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
	public function send_subscribe_links_to_browser_download() {
		$subscribe_links_data = $this->get_subscribe_url_data();
		$upload_dir           = wp_upload_dir();
		$export_file          = trailingslashit( $upload_dir['path'] ) . 'subscribe_options.csv';
		$export_file_url      = trailingslashit( $upload_dir['url'] ) . 'subscribe_options.csv';

		$export_file_pointer = fopen( $export_file, 'w' );
		foreach ( $subscribe_links_data as $subscribe_links_items ) {
			fputcsv( $export_file_pointer, $subscribe_links_items );
		}
		fclose( $export_file_pointer );

		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Robots: none' );
		header( 'Content-Length: ' . filesize( $export_file ) );
		header( 'Content-Type: application/force-download' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename="' . basename( $export_file ) . '";' );
		header( 'Content-Transfer-Encoding: binary' );

		header( 'Location: ' . $export_file_url );
		exit;
	}
}
