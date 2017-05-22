<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple Logging function
 *
 * @param $data
 *
 * @return bool
 */
function ssp_debug( $data ) {
	if ( ! defined( 'SSP_DEBUG' ) || ! SSP_DEBUG ) {
		return false;
	}
	$file = SSP_LOG_PATH;
	if ( ! is_file( $file ) ) {
		file_put_contents( $file, '' );
	}
	$data_string = print_r( $data, true ) . "\n";
	file_put_contents( $file, $data_string, FILE_APPEND );
}

/**
 * Clear debug log
 */
function ssp_debug_clear() {
	$file = SSP_PLUGIN_PATH . 'ssp.log.txt';
	file_put_contents( $file, '' );
}

if ( ! function_exists( 'is_podcast_download' ) ) {
	/**
	 * Check if podcast file is being downloaded
	 * @since  1.5
	 * @return boolean True if file is being downloaded
	 */
	function is_podcast_download() {
		global $wp_query;

		$download = $episode = false;

		if ( isset( $wp_query->query_vars['podcast_episode'] ) && $wp_query->query_vars['podcast_episode'] ) {
			$download = true;
			$episode  = intval( $wp_query->query_vars['podcast_episode'] );
		}

		return apply_filters( 'ssp_is_podcast_download', $download, $episode );
	}
}

if ( ! function_exists( 'ss_get_podcast' ) ) {
	/**
	 * Wrapper function to get the podcast episodes from the SeriouslySimplePodcasting class.
	 *
	 * @param  mixed $args Arguments
	 *
	 * @since  1.0.0
	 * @return mixed        Array if true, boolean if false.
	 */
	function ss_get_podcast( $args = '' ) {
		global $ss_podcasting;

		return $ss_podcasting->get_podcast( $args );
	}
}

/**
 * Enable the usage of do_action( 'get_podcast' ) to display podcast within a theme/plugin.
 * @since  1.0.0
 */
add_action( 'get_podcast', 'ss_podcast' );

if ( ! function_exists( 'ss_podcast' ) ) {
	/**
	 * Display or return HTML-formatted podcast data.
	 *
	 * @param  mixed $args Arguments
	 *
	 * @since  1.0.0
	 * @return string
	 */
	function ss_podcast( $args = '' ) {
		global $post, $ss_podcasting;

		$defaults = array(
			'echo'         => true,
			'link_title'   => true,
			'title'        => '',
			'content'      => 'series',
			'series'       => '',
			'before'       => '<div class="widget widget_ss_podcast">',
			'after'        => '</div><!--/.widget widget_ss_podcast-->',
			'before_title' => '<h3>',
			'after_title'  => '</h3>'
		);

		$args = wp_parse_args( $args, $defaults );

		// Allow child themes/plugins to filter here
		$args = apply_filters( 'ssp_podcast_args', $args );
		$html = '';

		do_action( 'ssp_podcast_before', $args );

		// The Query
		$query = ss_get_podcast( $args );

		// The Display
		if ( ! is_wp_error( $query ) && is_array( $query ) && count( $query ) > 0 ) {
			$html .= $args['before'] . "\n";

			if ( '' != $args['title'] ) {
				$html .= $args['before_title'] . esc_html( $args['title'] ) . $args['after_title'] . "\n";
			}

			$html .= '<div class="ss_podcast">' . "\n";

			// Begin templating logic.
			$tpl = '<div class="%%CLASS%%"><h4 class="podcast-title">%%TITLE%%</h4><aside class="meta">%%META%%</aside></div>';
			$tpl = apply_filters( 'ssp_podcast_item_template', $tpl, $args );

			if ( $query['content'] == 'episodes' ) {

				$i = 0;
				foreach ( $query as $post ) {

					if ( ! is_object( $post ) ) {
						continue;
					}

					$template = $tpl;
					$i ++;

					setup_postdata( $post );

					$class = 'podcast';

					$title = get_the_title();
					if ( true == $args['link_title'] ) {
						$title = '<a href="' . esc_url( $post->url ) . '" title="' . esc_attr( $title ) . '">' . $title . '</a>';
					}

					$meta = $ss_podcasting->episode_meta( $post->ID, 'shortcode' );

					$template = str_replace( '%%CLASS%%', $class, $template );
					$template = str_replace( '%%TITLE%%', $title, $template );
					$template = str_replace( '%%META%%', $meta, $template );

					$html .= $template;

				}

			} else {

				$i = 0;
				foreach ( $query as $series ) {

					if ( ! is_object( $series ) ) {
						continue;
					}

					$template = $tpl;
					$i ++;

					$class = 'podcast';

					$title = $series->title;
					if ( true == $args['link_title'] ) {
						$title = '<a href="' . esc_url( $series->url ) . '" title="' . esc_attr( $title ) . '">' . $title . '</a>';
					}

					$template = str_replace( '%%CLASS%%', $class, $template );
					$template = str_replace( '%%TITLE%%', $title, $template );

					$meta     = $series->count . __( ' episodes', 'seriously-simple-podcasting' );
					$template = str_replace( '%%META%%', $meta, $template );

					$html .= $template;

				}

			}

			$html .= '<div class="fix"></div>' . "\n";

			$html .= '</div><!--/.ss_podcast-->' . "\n";
			$html .= $args['after'] . "\n";

			wp_reset_postdata();
		}

		// Allow themes/plugins to filter here
		$html = apply_filters( 'ssp_podcast_html', $html, $query, $args );

		if ( ! $args['echo'] ) {
			return $html;
		}

		// Should only run if "echo" is set to true
		echo $html;

		do_action( 'ssp_podcast_after', $args );
	}
}

if ( ! function_exists( 'ssp_episode_ids' ) ) {

	/**
	 * Get post IDs of all podcast episodes for all post types
	 * @since  1.8.2
	 * @return array
	 */
	function ssp_episode_ids() {
		global $ss_podcasting;

		// Remove action to prevent infinite loop
		remove_action( 'pre_get_posts', array( $ss_podcasting, 'add_all_post_types' ) );

		// Setup the default args
		$args = array(
			'post_type'      => array( 'podcast' ),
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		);

		// Do we have any additional post types to add?
		$podcast_post_types = ssp_post_types( false );

		if ( ! empty( $podcast_post_types ) ) {
			$args['post_type']  = ssp_post_types();
			$args['meta_query'] = array(
				array(
					'key'     => apply_filters( 'ssp_audio_file_meta_key', 'audio_file' ),
					'compare' => '!=',
					'value'   => '',
				),
			);
		}

		// Do we have this stored in the cache?
		$key              = 'episode_ids';
		$group            = 'ssp';
		$podcast_episodes = wp_cache_get( $key, $group );

		// If nothing in cache then fetch episodes again and store in cache
		if ( $podcast_episodes === false ) {
			$podcast_episodes = get_posts( $args );
			wp_cache_set( $key, $podcast_episodes, $group, HOUR_IN_SECONDS * 12 );
		}

		// Reinstate action for future queries
		add_action( 'pre_get_posts', array( $ss_podcasting, 'add_all_post_types' ) );

		return $podcast_episodes;
	}

}

if ( ! function_exists( 'ssp_episodes' ) ) {

	/**
	 * Fetch all podcast episodes
	 *
	 * @param  integer $n Number of episodes to fetch
	 * @param  string $series Slug of series to fetch
	 * @param  boolean $return_args True to return query args, false to return posts
	 * @param  string $context Context of query
	 *
	 * @since  1.8.2
	 * @return array                Array of posts or array of query args
	 */
	function ssp_episodes( $n = 10, $series = '', $return_args = false, $context = '' ) {

		// Get all podcast episodes IDs
		$episode_ids = (array) ssp_episode_ids();

		if ( $context === 'glance' ) {
			return $episode_ids;
		}

		if ( empty( $episode_ids ) ) {
			return array();
		}

		// Get all valid podcast post types
		$podcast_post_types = ssp_post_types( true );

		if ( empty ( $podcast_post_types ) ) {
			return array();
		}

		// Fetch podcast episodes
		$args = array(
			'post_type'           => $podcast_post_types,
			'post_status'         => 'publish',
			'posts_per_page'      => $n,
			'ignore_sticky_posts' => true,
			'post__in'            => $episode_ids,
		);

		if ( $series ) {
			$args['series'] = esc_attr( $series );
		}

		$args = apply_filters( 'ssp_episode_query_args', $args, $context );

		if ( $return_args ) {
			return $args;
		}

		// Do we have anything in the cache here?
		$key   = 'episodes_' . $series;
		$group = 'ssp';
		$posts = wp_cache_get( $key, $group );

		// If nothing in cache then fetch episodes again and store in cache
		if ( $posts === false ) {
			$posts = get_posts( $args );
			wp_cache_add( $key, $posts, $group, HOUR_IN_SECONDS * 12 );
		}

		return $posts;
	}

}

if ( ! function_exists( 'ssp_post_types' ) ) {

	/**
	 * Fetch all valid podcast post types
	 *
	 * @param  boolean $include_podcast Include the `podcast` post type or not
	 *
	 * @since  1.8.7
	 * @return array                    Array of podcast post types
	 */
	function ssp_post_types( $include_podcast = true ) {

		// Get saved podcast post type option (default to empty array)
		$podcast_post_types = get_option( 'ss_podcasting_use_post_types', array() );

		if ( empty( $podcast_post_types ) && ! is_array( $podcast_post_types ) ) {
			$podcast_post_types = array();
		}

		// Add `podcast` post type to array if required
		if ( $include_podcast ) {
			$podcast_post_types[] = 'podcast';
		}

		$valid_podcast_post_types = array();

		// Check if post types exist
		if ( ! empty( $podcast_post_types ) ) {

			foreach ( $podcast_post_types as $type ) {
				if ( post_type_exists( $type ) ) {
					$valid_podcast_post_types[] = $type;
				}
			}

		}

		// Return only the valid podcast post types
		return apply_filters( 'ssp_podcast_post_types', $valid_podcast_post_types, $include_podcast );
	}
}

if ( ! function_exists( 'ssp_get_feed_category_output' ) ) {

	/**
	 * Get the XML markup for the feed category at the specified level
	 *
	 * @param  int $level Category level
	 *
	 * @return string        XML output for feed vategory
	 */
	function ssp_get_feed_category_output( $level = 1, $series_id ) {

		$level = (int) $level;

		if ( 1 == $level ) {
			$level = '';
		}

		$category = get_option( 'ss_podcasting_data_category' . $level, '' );
		if ( $series_id ) {
			$series_category = get_option( 'ss_podcasting_data_category' . $level . '_' . $series_id, 'no-category' );
			if ( 'no-category' != $series_category ) {
				$category = $series_category;
			}
		}
		if ( ! $category ) {
			$category    = '';
			$subcategory = '';
		} else {
			$subcategory = get_option( 'ss_podcasting_data_subcategory' . $level, '' );
			if ( $series_id ) {
				$series_subcategory = get_option( 'ss_podcasting_data_subcategory' . $level . '_' . $series_id, 'no-subcategory' );
				if ( 'no-subcategory' != $series_subcategory ) {
					$subcategory = $series_subcategory;
				}
			}
		}

		return apply_filters( 'ssp_feed_category_output', array(
			'category'    => $category,
			'subcategory' => $subcategory
		), $level, $series_id );
	}

}

if ( ! function_exists( 'ssp_readfile_chunked' ) ) {

	/**
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
	 *
	 * @param    string    file
	 * @param    boolean   return bytes of file
	 *
	 * @since     1.0.0
	 * @return   mixed
	 */
	function ssp_readfile_chunked( $file, $retbytes = true ) {

		$chunksize = 1 * ( 1024 * 1024 );
		$cnt       = 0;

		$handle = fopen( $file, 'r' );
		if ( $handle === false ) {
			return false;
		}

		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, $chunksize );
			echo $buffer;
			ob_flush();
			flush();

			if ( $retbytes ) {
				$cnt += strlen( $buffer );
			}
		}

		$status = fclose( $handle );

		if ( $retbytes && $status ) {
			return $cnt;
		}

		return $status;
	}
}

if ( ! function_exists( 'ssp_is_connected_to_podcastmotor' ) ) {

	/**
	 * Checks if the PodcastMotor credentials have been validated
	 *
	 * @return bool
	 */
	function ssp_is_connected_to_podcastmotor() {
		$is_connected = false;
		$podmotor_id  = get_option( 'ss_podcasting_podmotor_account_id', '' );
		if ( ! empty( $podmotor_id ) ) {
			$podmotor_email     = get_option( 'ss_podcasting_podmotor_account_email', '' );
			$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
			if ( ! empty( $podmotor_email ) && ! empty( $podmotor_api_token ) ) {
				$is_connected = true;
			}
		}
		return $is_connected;
	}
}

if ( ! function_exists( 'ssp_get_existing_podcast' ) ) {

	/**
	 * Check if one podcast exists that can be uploaded to PodcastMotor
	 *
	 * @return WP_Query
	 */
	function ssp_get_existing_podcast() {
		$args     = array(
			'post_type'      => 'podcast',
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'meta_query'     => array(
				array(
					'key'     => 'podmotor_episode_id',
					'compare' => 'NOT EXISTS',
					'value'   => ''
				),
			)
		);
		$podcasts = new WP_Query( $args );

		return $podcasts;
	}

}

if ( ! function_exists( 'ssp_get_importing_podcasts_count' ) ) {
	/**
	 * Counts the number of podcasts still being imported
	 *
	 * @return string $count number of podcasts.
	 */
	function ssp_get_importing_podcasts_count() {
		$podmotor_import_podcasts = get_option( 'ss_podcasting_podmotor_import_podcasts', 'false' );
		if ( 'true' === $podmotor_import_podcasts ) {

			global $wpdb;
			$args     = array(
				'post_type'      => 'podcast',
				'posts_per_page' => - 1,
				'post_status'    => 'any',
				'meta_query'     => array(
					array(
						'key'     => 'podmotor_episode_id',
						'compare' => 'NOT EXISTS',
						'value'   => ''
					),
				)
			);
			$podcasts = new WP_Query( $args );

			return $podcasts->post_count;
		} else {
			return 'Not importing any podcasts';
		}
	}
}


if ( ! function_exists( 'ssp_import_existing_podcasts' ) ) {

	/**
	 * Imports existing podcasts to Seriously Simple Hosting
	 *
	 * @return bool
	 */
	function import_existing_podcast() {

		ssp_debug( 'Importing Existing Podcasts' );
		$podmotor_import_podcasts = get_option( 'ss_podcasting_podmotor_import_podcasts', 'false' );

		/**
		 * Only if we should be importing posts
		 */
		if ( 'true' == $podmotor_import_podcasts ) {
			ssp_debug( 'Import podcasts is on' );
			$podcast_query = ssp_get_existing_podcast();

			/**
			 * Only if there are posts to import
			 */
			if ( $podcast_query->have_posts() ) {
				ssp_debug( 'Podcasts exist to import' );

				$podcasts   = $podcast_query->get_posts();
				$podcast    = $podcasts[0]; // there will always only ever be one
				$podcast_id = $podcast->ID;
				ssp_debug( 'Importing Podcast ' . $podcast_id );
				/**
				 * Update the podmotor_episode_id, in case this gets triggered again
				 */
				update_post_meta( $podcast_id, 'podmotor_episode_id', '0' );
				$podmotor_handler = new Podmotor_Handler();
				$podcast_url      = get_post_meta( $podcast_id, 'audio_file', true );

				if ( ! strstr( $podcast_url, site_url() ) ) {
					ssp_debug( 'Uploading external file ' . $podcast_url . ' for Podcast ' . $podcast_id );
					$file_upload_response = $podmotor_handler->upload_file_from_external_source( $podcast_url );
				} else {
					// convert the local url to a directory path
					$podcast_file = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $podcast_url );
					ssp_debug( 'Uploading local file ' . $podcast_file . ' for Podcast ' . $podcast_id );
					$file_upload_response = $podmotor_handler->upload_file_to_podmotor_storage( $podcast_file, $podcast_id );
				}
				
				if ( 'success' === $file_upload_response['status'] ) {

					ssp_debug( 'Success uploading file for Podcast ' . $podcast_id );
					$file_data_response = $podmotor_handler->upload_podmotor_storage_file_data_to_podmotor( $file_upload_response['podmotor_file'] );
					if ( 'success' === $file_data_response['status'] ) {

						ssp_debug( 'Success uploading file data for Podcast ' . $podcast_id );
						update_post_meta( $podcast_id, 'audio_file', $file_data_response['file_path'] );
						update_post_meta( $podcast_id, 'podmotor_file_id', $file_data_response['file_id'] );
						$episode_data_response = $podmotor_handler->upload_podcast_to_podmotor( $podcast );
						if ( 'success' === $episode_data_response['status'] ) {

							ssp_debug( 'Success uploading podcast data for Podcast ' . $podcast_id );
							update_post_meta( $podcast_id, 'podmotor_episode_id', $episode_data_response['episode_id'] );

						} else {
							ssp_debug( 'Error uploading podcast data for Podcast ' . $podcast_id );
							ssp_debug( $episode_data_response );
						}

					} else {
						ssp_debug( 'Error uploading file data for Podcast ' . $podcast_id );
						ssp_debug( $file_data_response );
					}

				} else {
					ssp_debug( 'Error uploading file for Podcast ' . $podcast_id );
					ssp_debug( $file_upload_response );
				}

				$podmotor_episode_id = get_post_meta( $podcast_id, 'podmotor_episode_id', '0' );
				if ( empty( $podmotor_episode_id ) ) {
					delete_post_meta( $podcast_id, 'podmotor_episode_id' );
				}

			} else {
				/**
				 * There are no more posts to import, disable import
				 */
				ssp_debug( 'Switching off podcast import' );
				update_option( 'ss_podcasting_podmotor_import_podcasts', 'false' );
				ssp_email_podcasts_imported();

				return false;
			}

			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'ssp_trigger_import_existing_podcast_to_podmotor' ) ) {

	/**
	 * Trigger the process of importing existing podcasts to Seriously Simple Hosting
	 * @return bool
	 */

	function ssp_trigger_import_existing_podcast_to_podmotor() {
		// connect to podmotor app and insert queue

	    $unique = mktime();
		ssp_debug( 'Triggering Curl for #' . $unique . ' at ' . date( 'd-m-Y H:i:s' ) );
		$curl_url = add_query_arg( array( 'podcast_importer' => 'true' ), trailingslashit( site_url() ) );
		$curl     = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_TIMEOUT_MS     => 60,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER         => false,
				CURLOPT_NOSIGNAL       => true,
				CURLOPT_URL            => $curl_url,
			)
		);
		$request = curl_exec( $curl );
		if ( false === $request ) {
			ssp_debug( 'Curl error for #' . $unique . ': ' . curl_error( $curl ) . ' at ' . date( 'd-m-Y H:i:s' ) );
		} else {
			ssp_debug( 'Curl completed for #' . $unique . ' at ' . date( 'd-m-Y H:i:s' ) );
		}
		curl_close( $curl );
	}
}

if ( ! function_exists( 'ssp_download_remote_file' ) ) {

	/**
	 * Takes an external file and downloads it to the server
	 *
	 * @param string $remote_file
	 * @param string $extension_override override the default extension
	 *
	 * @return bool|mixed file_path.
	 */
	function ssp_download_remote_file( $remote_file = '', $extension_override = '' ) {

		$response = false;
		if ( ! empty( $remote_file ) ) {
			$remote_file_info = pathinfo( $remote_file );
			$file_path        = SSP_UPLOADS_DIR . $remote_file_info['basename'];
			if ( ! empty( $extension_override ) ) {
				$file_path = $file_path . '.' . $extension_override;
			}
			$complete = ssp_download_file( $remote_file, $file_path );
			if ( $complete ) {
				$response = $file_path;
			}
		}

		return $response;
	}
}

if ( ! function_exists( 'ssp_import_external_rss_feed_to_ssp' ) ) {
	/**
	 * Download external file in chunkcs
	 *
	 * @param $file_source
	 * @param $file_target
	 *
	 * @return bool
	 */
	function ssp_download_file( $file_source, $file_target ) {
		$rh = fopen( $file_source, 'rb' );
		$wh = fopen( $file_target, 'wb' );
		if ( ! $rh || ! $wh ) {
			return false;
		}

		while ( ! feof( $rh ) ) {
			if ( fwrite( $wh, fread( $rh, 1024 ) ) === false ) {
				return false;
			}
		}

		fclose( $rh );
		fclose( $wh );

		return true;
	}
}


if ( ! function_exists( 'ssp_import_external_rss_feed_to_ssp' ) ) {
	/**
	 * Import Podcast Episodes from an external feed
	 */
	function ssp_import_external_rss_feed_to_ssp() {
		$ss_podcasting_podcast_rss_url = get_option( 'ss_podcasting_podcast_rss_url', '' );
		if ( ! empty( $ss_podcasting_podcast_rss_url ) ) {
			$ssp_importer = new SSP_RSS_Import( $ss_podcasting_podcast_rss_url );
			$imported     = $ssp_importer->import();

			return $imported;
		}

		return false;
	}
}

if ( ! function_exists( 'ssp_email_podcasts_imported' ) ) {
	function ssp_email_podcasts_imported() {
		$new_line         = "\n";
		$site_name        = get_bloginfo( 'name' );
		$site_admin_email = get_bloginfo( 'admin_email' );
		$to               = $site_admin_email;
		$subject          = sprintf( __( 'Podcast import completed for %s' ), $site_name );
		$message          = '';
		$message .= sprintf( __( 'The Podcast import for %1$s has completed.%2$s' ), $site_name, $new_line );
		$message .= sprintf( __( 'Thank you for using Seriously Simple Hosting to host your podcasts.%1$s' ), $new_line );
		$from = sprintf( 'From: "%1$s" <%2$s>', _x( 'Site Admin', 'email "From" field' ), $to );

		return wp_mail( $to, $subject, $message, $from );
	}
}