<?php

use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Images_Handler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ssp_beta_check' ) ) {
	function ssp_beta_check() {
		if ( ! strstr( SSP_VERSION, 'beta' ) ) {
			return;
		}
		/**
		 * Display the beta notice.
		 */
		add_action( 'admin_notices', 'ssp_beta_notice' );
		function ssp_beta_notice() {
			$beta_notice = __( 'You are using the Seriously Simple Podcasting beta, connected to ', 'seriously-simple-podcasting' );
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php echo $beta_notice . SSP_CASTOS_APP_URL; ?></strong>.
				</p>
			</div>
			<?php
		}

		return false;
	}
}


if ( ! function_exists( 'ssp_is_php_version_ok' ) ) {
	function ssp_is_php_version_ok() {
		if ( ! version_compare( PHP_VERSION, '5.6', '<' ) ) {
			return true;
		}
		/**
		 * We are running under PHP 5.6
		 */
		if ( ! is_admin() ) {
			return false;
		}
		/**
		 * Display an admin notice and gracefully do nothing.
		 */
		add_action( 'admin_notices', 'ssp_php_version_notice' );
		function ssp_php_version_notice() {
			$error_notice = __( 'The Seriously Simple Podcasting plugin requires PHP version 5.6 or higher. Please contact your web host to upgrade your PHP version or deactivate the plugin.', 'seriously-simple-podcasting' );
			$error_notice_apology = __( 'We apologise for any inconvenience.', 'seriously-simple-podcasting' );
			?>
			<div class="error">
				<p>
					<strong><?php echo $error_notice; ?></strong>.
				</p>
				<p><?php echo $error_notice_apology; ?></p>
			</div>
			<?php
		}

		return false;
	}
}

if ( ! function_exists( 'ssp_is_vendor_ok' ) ) {
	function ssp_is_vendor_ok() {

		if ( file_exists( SSP_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
			return true;
		}
		add_action( 'admin_notices', 'ssp_vendor_notice' );
		function ssp_vendor_notice() {
			$error_notice         = __( 'The Seriously Simple Podcasting vendor directory is missing or broken, please re-download/reinstall the plugin.', 'seriously-simple-podcasting' );
			$error_notice_apology = __( 'We apologise for any inconvenience.', 'seriously-simple-podcasting' );
			?>
			<div class="error">
				<p>
					<strong><?php echo $error_notice; ?></strong>.
				</p>
				<p><?php echo $error_notice_apology; ?></p>
			</div>
			<?php
		}

		return false;
	}
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
	 * @return boolean True if file is being downloaded
	 * @since  1.5
	 */
	function is_podcast_download() {
		$download = false;
		$episode  = false;
		global $wp_query;
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
	 * @param mixed $args Arguments
	 *
	 * @return mixed        Array if true, boolean if false.
	 * @since  1.0.0
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
	 * @param mixed $args Arguments
	 *
	 * @return string
	 * @since  1.0.0
	 */
	function ss_podcast( $args = '' ) {
		global $post, $ss_podcasting;

		$defaults = array(
			'echo'         => true,
			'link_title'   => 'true',
			'title'        => '',
			'content'      => 'series',
			'series'       => '',
			'before'       => '<div class="widget widget_ss_podcast">',
			'after'        => '</div><!--/.widget widget_ss_podcast-->',
			'before_title' => '<h3>',
			'after_title'  => '</h3>',
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

			if ( '' !== $args['title'] ) {
				$html .= $args['before_title'] . esc_html( $args['title'] ) . $args['after_title'] . "\n";
			}

			$html .= '<div class="ss_podcast">' . "\n";

			// Begin templating logic.
			$tpl = '<div class="%%CLASS%%"><h4 class="podcast-title">%%TITLE%%</h4><aside class="meta">%%META%%</aside></div>';
			$tpl = apply_filters( 'ssp_podcast_item_template', $tpl, $args );

			if ( 'episodes' === $query['content'] ) {

				$i = 0;
				foreach ( $query as $post ) {

					if ( ! is_object( $post ) ) {
						continue;
					}

					$template = $tpl;
					$i ++;

					setup_postdata( $post );

					$class = SSP_CPT_PODCAST;

					$title = get_the_title();
					if ( 'true' === $args['link_title'] ) {
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

					$class = SSP_CPT_PODCAST;

					$title = $series->title;
					if ( 'true' === $args['link_title'] ) {
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
	 * @return array
	 * @since  1.8.2
	 */
	function ssp_episode_ids() {
		global $ss_podcasting;

		// Remove action to prevent infinite loop
		remove_action( 'pre_get_posts', array( $ss_podcasting, 'add_all_post_types' ) );

		// Setup the default args
		$args = array(
			'post_type'      => array( SSP_CPT_PODCAST ),
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
		if ( false === $podcast_episodes ) {
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
	 * @param integer $n Number of episodes to fetch
	 * @param string $series Slug of series to fetch
	 * @param boolean $return_args True to return query args, false to return posts
	 * @param string $context Context of query
	 *
	 * @param array $exclude_series a list of series terms for which episodes should be excluded
	 *
	 * @return array                Array of posts or array of query args
	 * @since  1.8.2
	 */
	function ssp_episodes( $n = 10, $series = '', $return_args = false, $context = '', $exclude_series = array() ) {

		// Get all podcast episodes IDs
		$episode_ids = (array) ssp_episode_ids();

		if ( 'glance' === $context ) {
			return $episode_ids;
		}

		if ( empty( $episode_ids ) ) {
			return array();
		}

		// Get all valid podcast post types
		$podcast_post_types = ssp_post_types( true );

		if ( empty( $podcast_post_types ) ) {
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
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'series',
					'field'    => 'slug',
					'terms'    => esc_attr( $series ),
				),
			);
		}

		if ( ! empty( $exclude_series ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'series',
					'field'    => 'slug',
					'terms'    => $exclude_series,
					'operator' => 'NOT IN',
				),
			);
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
		if ( false === $posts ) {
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
	 * @param boolean $include_podcast Include the `podcast` post type or not
	 *
	 * @param bool $verify	Verify if the post type has been registered by register_post_type
	 *
	 * @return array                    Array of podcast post types
	 * @since  1.8.7
	 */
	function ssp_post_types( $include_podcast = true, $verify = true ) {

		// Get saved podcast post type option (default to empty array)
		$podcast_post_types = get_option( 'ss_podcasting_use_post_types', array() );

		if ( empty( $podcast_post_types ) && ! is_array( $podcast_post_types ) ) {
			$podcast_post_types = array();
		}

		// Add `podcast` post type to array if required
		if ( $include_podcast ) {
			$podcast_post_types[] = SSP_CPT_PODCAST;
		}

		if ( $verify ) {
			$valid_podcast_post_types = array();

			// Check if post types exist
			if ( ! empty( $podcast_post_types ) ) {

				foreach ( $podcast_post_types as $type ) {
					if ( post_type_exists( $type ) ) {
						$valid_podcast_post_types[] = $type;
					}
				}
			}
		} else {
			$valid_podcast_post_types = $podcast_post_types;
		}

		// Return only the valid podcast post types
		return apply_filters( 'ssp_podcast_post_types', $valid_podcast_post_types, $include_podcast );
	}
}

if ( ! function_exists( 'ssp_get_feed_category_output' ) ) {

	/**
	 * Get the XML markup for the feed category at the specified level
	 *
	 * @param int $level Category level
	 *
	 * @return string        XML output for feed vategory
	 */
	function ssp_get_feed_category_output( $level = 1, $series_id ) {

		$level = (int) $level;

		if ( 1 === $level ) {
			$level = '';
		}

		$category = get_option( 'ss_podcasting_data_category' . $level, '' );
		if ( $series_id ) {
			$series_category = get_option( 'ss_podcasting_data_category' . $level . '_' . $series_id, 'no-category' );
			if ( 'no-category' !== $series_category ) {
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
				if ( 'no-subcategory' !== $series_subcategory ) {
					$subcategory = $series_subcategory;
				}
			}
		}

		return apply_filters(
			'ssp_feed_category_output',
			array(
				'category'    => $category,
				'subcategory' => $subcategory,
			),
			$level,
			$series_id
		);
	}
}

if ( ! function_exists( 'ssp_readfile_chunked' ) ) {

	/**
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
	 *
	 * @param string    file
	 * @param boolean   return bytes of file
	 *
	 * @return   mixed
	 * @since     1.0.0
	 */
	function ssp_readfile_chunked( $file, $retbytes = true ) {

		$chunksize = 1 * ( 1024 * 1024 );
		$cnt       = 0;

		$handle = fopen( $file, 'r' );
		if ( false === $handle ) {
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

		$formatted_size_type  = preg_replace( '/[^a-z]/i', '', $formatted_size );
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

if ( ! function_exists( 'ssp_is_connected_to_castos' ) ) {

	/**
	 * Checks if the Castos credentials have been validated
	 *
	 * @return bool
	 */
	function ssp_is_connected_to_castos() {
		$is_connected       = false;
		$podmotor_email     = get_option( 'ss_podcasting_podmotor_account_email', '' );
		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
		if ( ! empty( $podmotor_email ) && ! empty( $podmotor_api_token ) ) {
			$is_connected = true;
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
				'post_content' => '', // leaving out the content for now
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

		$podmotor_import_podcasts = get_option( 'ss_podcasting_podmotor_import_podcasts', 'false' );

		/**
		 * Only if we should be importing posts
		 */
		if ( 'true' === $podmotor_import_podcasts ) {
			$podcast_query = ssp_get_existing_podcasts();

			/**
			 * Only if there are posts to import
			 */
			if ( $podcast_query->have_posts() ) {

				$podcast_data = ssp_build_podcast_data( $podcast_query );

				$castos_handler           = new Castos_Handler();
				$upload_podcasts_response = $castos_handler->upload_podcasts_to_podmotor( $podcast_data );

				if ( 'success' === $upload_podcasts_response['status'] ) {
					update_option( 'ss_podcasting_podmotor_import_podcasts', 'false' );
				}
			} else {
				/**
				 * There are no posts to import, disable import
				 */
				update_option( 'ss_podcasting_podmotor_import_podcasts', 'false' );

				return false;
			}

			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'ssp_get_external_rss_being_imported' ) ) {
	/**
	 * If an external RSS feed is being imported, return the url
	 * Otherwise return false
	 *
	 * @return mixed|void
	 */
	function ssp_get_external_rss_being_imported() {
		return get_option( 'ssp_external_rss', false );
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

if ( ! function_exists( 'ssp_download_file' ) ) {
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

if ( ! function_exists( 'ssp_email_podcasts_imported' ) ) {
	/**
	 * Send podcasts imported email
	 *
	 * @return mixed
	 */
	function ssp_email_podcasts_imported() {
		$site_name        = get_bloginfo( 'name' );
		$site_admin_email = get_bloginfo( 'admin_email' );
		$to               = $site_admin_email;
		/* translators: %s: Site Name */
		$subject = sprintf( __( 'Podcast import completed for %s' ), $site_name );
		$message = '';
		/* translators: %s: Site Name */
		$message .= sprintf( __( 'The Podcast import for %1$s has completed.' ), $site_name ) . PHP_EOL;
		$message .= __( 'Thank you for using Castos to host your podcasts.' );
		$from     = sprintf( 'From: "%1$s" <%2$s>', _x( 'Site Admin', 'email "From" field' ), $to );

		return wp_mail( $to, $subject, $message, $from );
	}
}

if ( ! function_exists( 'ssp_setup_upload_credentials' ) ) {
	/**
	 *
	 * Sets up uploading credentials for a Castos user to push files to Castos
	 *
	 * @return array
	 */
	function ssp_setup_upload_credentials() {
		$castos_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
		$castos_api_url   = SSP_CASTOS_APP_URL . 'api/v2/';

		$castos_episode_id = '';
		$post              = get_post();
		if ( $post ) {
			$castos_episode_id = get_post_meta( $post->ID, 'podmotor_episode_id', true );
		}

		return compact( 'castos_api_url', 'castos_api_token', 'castos_episode_id' );
	}
}

if ( ! function_exists( 'ssp_get_image_id_from_url' ) ) {
	/**
	 * Get image ID when only the URL of the image is known
	 * @deprecated Do not use this function. Use attachment_url_to_postid() instead
	 * @todo: remove it in the next versions
	 *
	 * @param $image_url
	 *
	 * @return mixed
	 */
	function ssp_get_image_id_from_url( $image_url ) {
		$relative_image_url = str_replace( get_site_url(), '', $image_url );
		global $wpdb;
		// double escaped placeholder to allow for LIKE wildcard search
		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid LIKE '%%%s%%' LIMIT 1;", $relative_image_url ) );

		return isset( $attachment[0] ) ? $attachment[0] : false;
	}
}

if ( ! function_exists( 'ssp_check_if_podcast_has_shortcode' ) ) {
	/**
	 * Check if the podcast content has a specific shortcode
	 *
	 * @param $podcast_id
	 * @param $shortcode
	 *
	 * @return bool
	 */
	function ssp_check_if_podcast_has_shortcode( $podcast_id = 0, $shortcode = '' ) {
		if ( empty( $podcast_id ) ) {
			return false;
		}
		$podcast         = get_post( $podcast_id );
		$podcast_content = $podcast->post_content;
		if ( has_shortcode( $podcast_content, $shortcode ) ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'ssp_check_if_podcast_has_player' ) ) {
	/**
	 * Checks to see if the episode has the new player WIP
	 *
	 * @param int $podcast_id
	 *
	 * @return false
	 */
	function ssp_check_if_podcast_has_elementor_player( $podcast_id = 0 ) {
		if ( empty( $podcast_id ) ) {
			return false;
		}
		$document            = \Elementor\Plugin::$instance->documents->get_doc_for_frontend( $podcast_id );
		$content             = $document->get_content();
		$media_player_string = '<audio class="wp-audio-shortcode" id="audio-' . $podcast_id;
		if ( strstr( $content, $media_player_string ) ) {
			return true;
		}
		$player_mode        = get_option( 'ss_podcasting_player_mode', 'dark' );
		$html_player_string = '<div id="embed-app" class="' . $player_mode . '-mode castos-player" data-episode="' . $podcast_id . '">';
		if ( strstr( $content, $html_player_string ) ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'ssp_get_episode_series_id' ) ) {
	/**
	 * Get the series id from a podcast episode
	 * Will only return the first series id if more than one exist
	 * or zero (0) if none exist
	 *
	 *
	 * @param $episode_id
	 *
	 * @return int
	 */
	function ssp_get_episode_series_id( $episode_id ) {
		$series_id = 0;
		$series    = wp_get_post_terms( $episode_id, 'series' );

		if ( empty( $series ) ) {
			return $series_id;
		}
		$series_ids = wp_list_pluck( $series, 'term_id' );
		if ( empty( $series_ids ) ) {
			return $series_id;
		}

		return $series_ids[0];
	}
}

if ( ! function_exists( 'get_series_data_for_castos' ) ) {
	/**
	 * Get the Series Data for a series (by series_id) for Castos to sync the data
	 *
	 * @param $series_id
	 *
	 * @return array
	 */
	function get_series_data_for_castos( $series_id ) {
		$podcast = array();

		// Podcast title
		$title = get_option( 'ss_podcasting_data_title', get_bloginfo( 'name' ) );

		$series_title = get_option( 'ss_podcasting_data_title_' . $series_id, '' );
		if ( $series_title ) {
			$title = $series_title;
		}
		$podcast['podcast_title'] = $title;

		// Podcast description
		$description        = get_option( 'ss_podcasting_data_description', get_bloginfo( 'description' ) );
		$series_description = get_option( 'ss_podcasting_data_description_' . $series_id, '' );
		if ( $series_description ) {
			$description = $series_description;
		}
		$podcast_description            = mb_substr( wp_strip_all_tags( $description ), 0, 3999 );
		$podcast['podcast_description'] = $podcast_description;

		// Podcast author
		$author        = get_option( 'ss_podcasting_data_author', get_bloginfo( 'name' ) );
		$series_author = get_option( 'ss_podcasting_data_author_' . $series_id, '' );
		if ( $series_author ) {
			$author = $series_author;
		}
		$podcast['author_name'] = $author;

		// Podcast owner name
		$owner_name        = get_option( 'ss_podcasting_data_owner_name', get_bloginfo( 'name' ) );
		$series_owner_name = get_option( 'ss_podcasting_data_owner_name_' . $series_id, '' );
		if ( $series_owner_name ) {
			$owner_name = $series_owner_name;
		}
		$podcast['podcast_owner'] = $owner_name;

		// Podcast owner email address
		$owner_email        = get_option( 'ss_podcasting_data_owner_email', get_bloginfo( 'admin_email' ) );
		$series_owner_email = get_option( 'ss_podcasting_data_owner_email_' . $series_id, '' );
		if ( $series_owner_email ) {
			$owner_email = $series_owner_email;
		}
		$podcast['owner_email'] = $owner_email;

		// Podcast explicit setting
		$explicit_option = get_option( 'ss_podcasting_explicit_' . $series_id, '' );
		if ( $explicit_option && 'on' === $explicit_option ) {
			$podcast['explicit'] = 1;
		} else {
			$podcast['explicit'] = 0;
		}

		// Podcast language
		$language        = get_option( 'ss_podcasting_data_language', get_bloginfo( 'language' ) );
		$series_language = get_option( 'ss_podcasting_data_language_' . $series_id, '' );
		if ( $series_language ) {
			$language = $series_language;
		}
		$podcast['language'] = $language;

		// Podcast cover image
		$image        = get_option( 'ss_podcasting_data_image', '' );
		$series_image = get_option( 'ss_podcasting_data_image_' . $series_id, 'no-image' );
		if ( 'no-image' !== $series_image ) {
			$image = $series_image;
		}
		$podcast['cover_image'] = $image;

		// Podcast copyright string
		$copyright        = get_option( 'ss_podcasting_data_copyright', '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) );
		$series_copyright = get_option( 'ss_podcasting_data_copyright_' . $series_id, '' );
		if ( $series_copyright ) {
			$copyright = $series_copyright;
		}
		$podcast['copyright'] = $copyright;

		// Podcast Categories
		$itunes_category1            = ssp_get_feed_category_output( 1, $series_id );
		$itunes_category2            = ssp_get_feed_category_output( 2, $series_id );
		$itunes_category3            = ssp_get_feed_category_output( 3, $series_id );
		$podcast['itunes_category1'] = $itunes_category1['category'];
		$podcast['itunes_category2'] = $itunes_category2['category'];
		$podcast['itunes_category3'] = $itunes_category3['category'];

		$podcast['itunes']      = get_option( 'ss_podcasting_itunes_url_' . $series_id, '' );
		$podcast['google_play'] = get_option( 'ss_podcasting_google_play_url_' . $series_id, '' );

		return $podcast;

	}
}

if ( ! function_exists( 'parse_episode_url_with_media_prefix' ) ) {
	/**
	 * Takes an episode url and appends the media prefix in front of it
	 *
	 * @param string $audio_file_url
	 * @param string $media_prefix
	 *
	 * @return string
	 */
	function parse_episode_url_with_media_prefix( $audio_file_url = '', $media_prefix = '' ) {
		if ( empty( $media_prefix ) ) {
			return $audio_file_url;
		}
		if ( empty( $audio_file_url ) ) {
			return $audio_file_url;
		}
		$url_parts = wp_parse_url( $audio_file_url );

		return $media_prefix . $url_parts['host'] . $url_parts['path'];
	}
}

if ( ! function_exists( 'get_keywords_for_episode' ) ) {
	/**
	 * Return a comma delimited list of tags for a post
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	function get_keywords_for_episode( $post_id ) {
		$tags = get_the_tags( $post_id );
		if ( ! $tags ) {
			return '';
		}

		$keyword_array = array();

		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$keyword_array[] = $tag->name;
			}
		}

		return implode( ',', $keyword_array );

	}
}

/**
 * Checks of the Elementor plugin is installed and active, by checking the WordPress list of active plugins
 */
if ( ! function_exists( 'ssp_is_elementor_ok' ) ) {
	function ssp_is_elementor_ok() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		if ( array_key_exists( 'elementor/elementor.php', $active_plugins ) || in_array( 'elementor/elementor.php', $active_plugins, true ) ) {
			return true;
		}
		return false;
	}
}

/**
 * Checks if the feed image is valid
 */
if ( ! function_exists( 'ssp_is_feed_image_valid' ) ) {
	/**
	 * @param string $image_url
	 *
	 * @return bool
	 */
	function ssp_is_feed_image_valid( $image_url ) {
		global $images_handler; /** @var Images_Handler $images_handler */
		return $images_handler->is_feed_image_valid( $image_url );
	}
}

/**
 * Checks if the image is square
 */
if ( ! function_exists( 'ssp_is_image_square' ) ) {
	/**
	 * @param array $image_data_array Converted image data array with width and height keys
	 *
	 * @return bool
	 * */
	function ssp_is_image_square( $image_data_array = array() ) {
		global $images_handler; /** @var Images_Handler $images_handler */
		return $images_handler->is_image_square( $image_data_array );
	}
}


/**
 * Almost the same function as wp_get_attachment_image_src(), but returning the associative human readable array
 */
if ( ! function_exists( 'ssp_get_attachment_image_src' ) ) {
	/**
	 * @param int $attachment_id
	 * @param string $size
	 *
	 * @return array
	 */
	function  ssp_get_attachment_image_src( $attachment_id, $size = "full" ) {
		global $images_handler; /** @var Images_Handler $images_handler */
		return $images_handler->get_attachment_image_src( $attachment_id, $size  );
	}
}


/**
 * Get the episode content for showing in the feed. Now Apple supports only p tags.
 * This function removes iframes and shortcodes from the content and strips all tags except <p> and <a>
 */
if ( ! function_exists( 'ssp_get_the_feed_item_content' ) ) {
	/**
	 * @return string
	 */
	function ssp_get_the_feed_item_content() {
		$content = get_the_content();
		$blocks  = parse_blocks( $content );
		if ( $blocks and is_array( $blocks ) ) {
			$content = '';
			$allowed_blocks = [
				'core/paragraph',
				'core/list',
			];
			foreach ( $blocks as $block ) {
				if ( in_array( $block['blockName'], $allowed_blocks ) ) {
					$content .= $block['innerHTML'];
				}
			}
		} else {
			$content = get_the_content_feed( 'rss2' );
		}

		$content = strip_shortcodes( $content );
		$content = preg_replace( '/<\/?iframe(.|\s)*?>/', '', $content );
		$content = str_replace( '<br>', PHP_EOL, $content );
		$content = strip_tags( $content, '<p>,<a>,<ul>,<ol>,<li>' );

		// Remove empty paragraphs as well.
		$content = trim( str_replace( '<p></p>', '', $content ) );

		return apply_filters( 'ssp_feed_item_content', $content, get_the_ID() );
	}
}
