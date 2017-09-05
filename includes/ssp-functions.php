<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple Logging function
 *
 * @param $message string debug message
 * @param $data mixed debug data
 *
 * @return bool
 */
function ssp_debug( $message, $data = '' ) {
	if ( ! defined( 'SSP_DEBUG' ) || ! SSP_DEBUG ) {
		return false;
	}
	$file = SSP_LOG_PATH;
	if ( ! is_file( $file ) ) {
		file_put_contents( $file, '' );
	}
	if ( ! empty( $data ) ) {
		$message = array( $message => $data );
	}
	$data_string = print_r( $message, true ) . "\n";
	file_put_contents( $file, $data_string, FILE_APPEND );
}

/**
 * Clear debug log
 */
function ssp_debug_clear() {
	$file = SSP_PLUGIN_PATH . 'ssp.log.txt';
	file_put_contents( $file, '' );
}

if ( ! function_exists( 'ssp_get_upload_directory' ) ) {
	/**
	 * Gets the temporary Seriously Simple Podcasting upload directory
	 * Typically ../wp-content/uploads/ssp
	 * If it does not already exist, attempts to create it
	 *
	 * @param bool $return Whether to return the path or not
	 *
	 * @return bool|string
	 */
	function ssp_get_upload_directory( $return = true ) {
		$time = current_time( 'mysql' );
		if ( ! ( ( $uploads = wp_upload_dir( $time ) ) && false === $uploads['error'] ) ) {
			add_action( 'admin_notices', 'ssp_cannot_write_uploads_dir_error' );
		} else {
			if ( $return ) {
				$ssp_upload_dir = trailingslashit( $uploads['basedir'] ) . trailingslashit( 'ssp' );
				if ( ! is_dir( $ssp_upload_dir ) ) {
					wp_mkdir_p( $ssp_upload_dir );
				}
				
				return $ssp_upload_dir;
			}
		}
	}
}

if ( ! function_exists( 'ssp_cannot_write_uploads_dir_error' ) ) {
	/**
	 * Displays an admin error of the wp-content folder permissions are incorrect
	 */
	function ssp_cannot_write_uploads_dir_error() {
		$time    = current_time( 'mysql' );
		$uploads = wp_upload_dir( $time );
		if ( 0 === strpos( $uploads['basedir'], ABSPATH ) ) {
			$error_path = str_replace( ABSPATH, '', $uploads['basedir'] );
		} else {
			$error_path = basename( $uploads['basedir'] );
		}
		$class   = 'notice notice-error';
		$message = sprintf(
		/* translators: %s: Error path */
			__( 'Unable to create directory %s. Is its parent directory writable by the server?' ),
			esc_html( $error_path )
		);
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
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

if ( ! function_exists( 'convert_human_readable_to_bytes' ) ) {
	
	/**
	 * Converts human readable file size (eg 280 kb) to bytes (286720)
	 *
	 * @param $formatted_size
	 *
	 * @return string
	 */
	function convert_human_readable_to_bytes( $formatted_size ) {
		
		$formatted_size_type  = preg_replace( '/[^a-z]/', '', $formatted_size );
		$formatted_size_value = trim( str_replace( $formatted_size_type, '', $formatted_size ) );
		
		switch ( strtoupper( $formatted_size_type ) ) {
			case 'KB':
				return $formatted_size_value * 1024;
			case 'MB':
				return $formatted_size_value * pow( 1024, 2 );
			case 'GB':
				return $formatted_size_value * pow( 1024, 3 );
			case 'TB':
				return $formatted_size_value * pow( 1024, 4 );
			case 'PB':
				return $formatted_size_value * pow( 1024, 5 );
			default:
				return $formatted_size_value;
		}
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

if ( ! function_exists( 'ssp_get_existing_podcasts' ) ) {
	/**
	 * Get all available posts that are registered as podcasts
	 *
	 * @return WP_Query
	 */
	function ssp_get_existing_podcasts() {
		$podcast_post_types = ssp_post_types( true );
		$args               = array(
			'post_type'      => $podcast_post_types,
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'orderby'        => 'ID',
			'meta_query'     => array(
				array(
					'key'     => 'audio_file',
					'compare' => 'EXISTS',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'podmotor_episode_id',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'podmotor_episode_id',
						'value'   => '0',
						'compare' => '=',
					),
				),
			),
		);
		$podcasts           = new WP_Query( $args );
		
		return $podcasts;
	}
} // End if().

if ( ! function_exists( 'ssp_build_podcast_data' ) ) {
	/**
	 * Generate the podcast data to be send via the SSH API
	 *
	 * @param $podcast_query
	 *
	 * @return $podcast_data array
	 */
	function ssp_build_podcast_data( $podcast_query ) {
		$podcasts = $podcast_query->get_posts();
		
		$podcast_data = array();
		foreach ( $podcasts as $podcast ) {
			$podcast_data[ $podcast->ID ] = array(
				'post_id'      => $podcast->ID,
				'post_title'   => $podcast->post_title,
				'post_content' => $podcast->post_content,
				'post_date'    => $podcast->post_date,
				'audio_file'   => get_post_meta( $podcast->ID, 'audio_file', true ),
			);
		}
		
		return $podcast_data;
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
			
			$podcast_post_types = ssp_post_types( true );
			$args               = array(
				'post_type'      => $podcast_post_types,
				'posts_per_page' => - 1,
				'post_status'    => 'any',
				'meta_query'     => array(
					array(
						'key'     => 'audio_file',
						'compare' => 'EXISTS',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => 'podmotor_episode_id',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'podmotor_episode_id',
							'value'   => '0',
							'compare' => '=',
						),
					),
				),
			);
			$podcasts           = new WP_Query( $args );
			
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
			$podcast_query = ssp_get_existing_podcasts();
			
			/**
			 * Only if there are posts to import
			 */
			if ( $podcast_query->have_posts() ) {
				ssp_debug( 'Podcasts exist to import' );
				
				$podcast_data = ssp_build_podcast_data( $podcast_query );
				
				$podmotor_handler         = new Podmotor_Handler();
				$upload_podcasts_response = $podmotor_handler->upload_podcasts_to_podmotor( $podcast_data );
				
				if ( 'success' === $upload_podcasts_response['status'] ) {
					
					ssp_debug( 'Success uploading podcast data, switching off podcast importer' );
					update_option( 'ss_podcasting_podmotor_import_podcasts', 'false' );
					
				} else {
					ssp_debug( 'Error uploading podcast data' );
					ssp_debug( $upload_podcasts_response );
				}
				
			} else {
				/**
				 * There are no posts to import, disable import
				 */
				ssp_debug( 'Switching off podcast import' );
				update_option( 'ss_podcasting_podmotor_import_podcasts', 'false' );
				
				//ssp_email_podcasts_imported();
				
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
		$curl_url = add_query_arg( array( 'ssp_podcast_importer' => 'true' ), trailingslashit( site_url() ) );
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
			$file_path        = ssp_get_upload_directory() . $remote_file_info['basename'];
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
	 * Download external file in chunks
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
	/**
	 * Send podcasts imported email
	 *
	 * @return mixed
	 */
	function ssp_email_podcasts_imported() {
		$new_line         = "\n";
		$site_name        = get_bloginfo( 'name' );
		$site_admin_email = get_bloginfo( 'admin_email' );
		$to               = $site_admin_email;
		$subject          = sprintf( __( 'Podcast import completed for %s' ), $site_name );
		$message          = '';
		$message          .= sprintf( __( 'The Podcast import for %1$s has completed.%2$s' ), $site_name, $new_line );
		$message          .= sprintf( __( 'Thank you for using Seriously Simple Hosting to host your podcasts.%1$s' ), $new_line );
		$from             = sprintf( 'From: "%1$s" <%2$s>', _x( 'Site Admin', 'email "From" field' ), $to );
		
		return wp_mail( $to, $subject, $message, $from );
	}
}

if ( ! function_exists( 'ssp_podmotor_decrypt_config' ) ) {
	/**
	 * Decrypt data
	 *
	 * @param $encrypted_string
	 * @param $unique_key
	 *
	 * @return bool|mixed
	 */
	function ssp_podmotor_decrypt_config( $encrypted_string, $unique_key ) {
		if ( preg_match( "/^(.*)::(.*)$/", $encrypted_string, $regs ) ) {
			list( $original_string, $encrypted_string, $encoding_iv ) = $regs;
			$encoding_method = 'AES-128-CTR';
			$encoding_key    = crypt( $unique_key, sha1( $unique_key ) );
			if ( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
				$decrypted_token = openssl_decrypt( $encrypted_string, $encoding_method, $encoding_key, 0, pack( 'H*', $encoding_iv ) );
			} else {
				$decrypted_token = openssl_decrypt( $encrypted_string, $encoding_method, $encoding_key, 0, hex2bin( $encoding_iv ) );
			}
			$config = unserialize( $decrypted_token );
			
			return $config;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'ssp_setup_upload_credentials' ) ) {
	function ssp_setup_upload_credentials() {
		
		$podmotor_account_id    = get_option( 'ss_podcasting_podmotor_account_id', '' );
		$podmotor_account_email = get_option( 'ss_podcasting_podmotor_account_email', '' );
		$podmotor_array         = ssp_podmotor_decrypt_config( $podmotor_account_id, $podmotor_account_email );
		
		$bucket        = $podmotor_array['bucket'];
		$show_slug     = $podmotor_array['show_slug'];
		$access_key_id = $podmotor_array['credentials_key'];
		$secret        = $podmotor_array['credentials_secret'];
		
		$policy = base64_encode( json_encode( array(
			'expiration' => date( 'Y-m-d\TH:i:s.000\Z', strtotime( '+1 day' ) ),
			// ISO 8601 - date('c'); generates uncompatible date, so better do it manually
			'conditions' => array(
				array( 'bucket' => $bucket ),
				array( 'acl' => 'public-read' ),
				array( 'starts-with', '$key', '' ),
				array( 'starts-with', '$Content-Type', '' ),
				// accept all files
				array( 'starts-with', '$name', '' ),
				// Plupload internally adds name field, so we need to mention it here
				array( 'starts-with', '$Filename', '' ),
				// One more field to take into account: Filename - gets silently sent by FileReference.upload() in Flash http://docs.amazonwebservices.com/AmazonS3/latest/dev/HTTPPOSTFlash.html
			),
		) ) );
		
		$signature    = base64_encode( hash_hmac( 'sha1', $policy, $secret, true ) );
		$episodes_url = SSP_PODMOTOR_EPISODES_URL;
		
		return compact( 'bucket', 'show_slug', 'episodes_url', 'access_key_id', 'policy', 'signature' );
		
	}
}