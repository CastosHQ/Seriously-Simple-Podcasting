<?php

use SeriouslySimplePodcasting\Controllers\App_Controller;
use SeriouslySimplePodcasting\Controllers\Episode_Controller;
use SeriouslySimplePodcasting\Controllers\Frontend_Controller;
use SeriouslySimplePodcasting\Controllers\Settings_Controller;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler;
use SeriouslySimplePodcasting\Handlers\Images_Handler;
use SeriouslySimplePodcasting\Interfaces\Service;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Series_Repository;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! function_exists( 'ssp_beta_notice' ) ) {
	/**
	 * Displays SSP beta version notice.
	 *
	 * @return void
	 */
	function ssp_beta_notice() {
		$beta_notice = __( 'You are using the Seriously Simple Podcasting beta ( %1$s ), connected to %2$s', 'seriously-simple-podcasting' );
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php echo sprintf( $beta_notice, SSP_VERSION, SSP_CASTOS_APP_URL ); ?></strong>.
			</p>
		</div>
		<?php
	}
}


if ( ! function_exists( 'ssp_beta_check' ) ) {
	/**
	 * Checks if it's a beta version, and if yes, displays notice.
	 *
	 * @return bool
	 */
	function ssp_beta_check() {
		if ( ! strstr( SSP_VERSION, 'beta' ) ) {
			return false;
		}
		/**
		 * Display the beta notice.
		 */
		add_action( 'admin_notices', 'ssp_beta_notice' );

		return true;
	}
}

if ( ! function_exists( 'ssp_php_version_notice' ) ) {
	/**
	 * Displays PHP version issue notice.
	 *
	 * @return void
	 */
	function ssp_php_version_notice() {
		$error_notice         = __( 'The Seriously Simple Podcasting plugin requires PHP version 5.6 or higher. Please contact your web host to upgrade your PHP version or deactivate the plugin.', 'seriously-simple-podcasting' );
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
}

if ( ! function_exists( 'ssp_is_php_version_ok' ) ) {
	/**
	 * Checks if PHP version is ok, and if not, displays notice.
	 *
	 * @return bool
	 */
	function ssp_is_php_version_ok() {
		if ( version_compare( PHP_VERSION, '5.6', '>=' ) ) {
			return true;
		}

		/**
		 * Display an admin notice.
		 */
		add_action( 'admin_notices', 'ssp_php_version_notice' );

		return false;
	}
}

if ( ! function_exists( 'ssp_vendor_notice' ) ) {
	/**
	 * @return void
	 */
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
}

if ( ! function_exists( 'ssp_is_vendor_ok' ) ) {
	/**
	 * @return bool
	 */
	function ssp_is_vendor_ok() {

		if ( file_exists( SSP_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
			return true;
		}
		add_action( 'admin_notices', 'ssp_vendor_notice' );

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
	 * @return string|void
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

if ( ! function_exists( 'ssp_is_podcast_download' ) ) {
	/**
	 * Check if podcast file is being downloaded
	 * @return boolean True if file is being downloaded
	 * @since  1.5
	 */
	function ssp_is_podcast_download() {
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
	 * Wrapper function to get the podcast episodes.
	 *
	 * @param mixed $args Arguments
	 *
	 * @return mixed        Array if true, boolean if false.
	 * @since  1.0.0
	 */
	function ss_get_podcast( $args = '' ) {
		$defaults = array(
			'title'   => '',
			'content' => 'series',
		);

		$args = apply_filters( 'ssp_get_podcast_args', wp_parse_args( $args, $defaults ) );

		$query = array();

		if ( 'episodes' == $args['content'] ) {
			// Get selected series
			$podcast_series = empty( $args['series'] ) ? null : $args['series'];

			// Get query args
			$query_args = apply_filters( 'ssp_get_podcast_query_args', ssp_episodes( -1, $podcast_series, true ) );

			// The Query
			$query = get_posts( $query_args );

			// The Display
			if ( ! is_wp_error( $query ) && is_array( $query ) && count( $query ) > 0 ) {
				foreach ( $query as $k => $v ) {
					// Get the URL
					$query[$k]->url = get_permalink( $v->ID );
				}
			} else {
				$query = false;
			}

		} else {

			$terms = get_terms( 'series' );

			if ( count( $terms ) > 0) {

				foreach ( $terms as $term ) {
					$query[ $term->term_id ] = new stdClass();
					$query[ $term->term_id ]->title = $term->name;
					$query[ $term->term_id ]->url = get_term_link( $term );

					$query_args = apply_filters( 'ssp_get_podcast_series_query_args', ssp_episodes( -1, $term->slug, true, '' ) );

					$posts = get_posts( $query_args );

					$count = count( $posts );
					$query[ $term->term_id ]->count = $count;
				}
			}

		}

		$query['content'] = $args['content'];

		return $query;
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

					$meta = $ss_podcasting->episode_meta_details( $post->ID, 'shortcode' );

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

					$meta     = sprintf( __( '%s episodes', 'seriously-simple-podcasting' ), $series->count );
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
	 * @return int[]
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
			wp_cache_set( $key, $podcast_episodes, $group, HOUR_IN_SECONDS );
		}

		// Reinstate action for future queries
		add_action( 'pre_get_posts', array( $ss_podcasting, 'add_all_post_types' ) );

		return (array) $podcast_episodes;
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

		if ( empty( $episode_ids ) && ! $return_args ) {
			return array();
		}

		// Get all valid podcast post types
		$podcast_post_types = ssp_post_types();

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

		if ( $exclude_series ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'series',
					'field'    => 'slug',
					'terms'    => $exclude_series,
					'operator' => 'NOT IN',
				),
			);
		} elseif ( $series && $series != ssp_get_default_series_slug() ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'series',
					'field'    => 'slug',
					'terms'    => esc_attr( $series ),
				),
			);
		}

		$args = apply_filters( 'ssp_episode_query_args', $args, $context );

		if ( $return_args ) {
			return $args;
		}

		// Todo: investigate if cache works correctly. For example, for different $n
		// Todo: Also, can it lead to the fatal errors if there are too many $posts?
		// Todo: Should we remove or improve the cache here?
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
	 * @param int $series_id
	 *
	 * @return array        XML output for feed vategory
	 */
	function ssp_get_feed_category_output( $level, $series_id ) {

		$level = (int) $level;

		if ( 1 === $level ) {
			$level = '';
		}

		if ( $series_id ) {
			$default_series_id = ssp_get_default_series_id();

			// Try to get the series category
			$category = get_option( 'ss_podcasting_data_category' . $level . '_' . $series_id, 'no-category' );

			// Try to get the default series category if series category was not setup yet
			if ( 'no-category' === $category ) {
				$category = get_option( 'ss_podcasting_data_category' . $level . '_' . $default_series_id, 'no-category' );
			}

			// Try to get category from the default feed settings (old variant, just for the backwards compatibility)
			if ( 'no-category' === $category ) {
				$category = get_option( 'ss_podcasting_data_category' . $level, '' );
			}

			$subcategory = '';

			// Try to get the series subcategory
			if ( $category ) {
				$subcategory = get_option( 'ss_podcasting_data_subcategory' . $level . '_' . $series_id, 'no-subcategory' );
			}

			// Try to get the default series category if series category was not setup yet
			if ( 'no-subcategory' === $subcategory ) {
				$subcategory = get_option( 'ss_podcasting_data_subcategory' . $level . '_' . $default_series_id, 'no-subcategory' );
			}

			// Try to get category from the default feed settings (old variant, just for the backwards compatibility)
			if ( 'no-subcategory' === $subcategory ) {
				$subcategory = get_option( 'ss_podcasting_data_subcategory' . $level, '' );
			}

		} else {
			// If there is no series ID, it's a deprecated default feed settings, which are not used anymore
			$category = get_option( 'ss_podcasting_data_category' . $level, '' );
			$subcategory = $category ? get_option( 'ss_podcasting_data_subcategory' . $level, '' ) : '';
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
		$is_connected = false;
		$cache_key    = 'ssp_is_connected_to_castos';
		if ( $cache = wp_cache_get( $cache_key ) ) {
			return $cache;
		}
		$podmotor_email     = get_option( 'ss_podcasting_podmotor_account_email', '' );
		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
		if ( ! empty( $podmotor_email ) && ! empty( $podmotor_api_token ) ) {
			$is_connected = true;
		}

		wp_cache_add( $cache_key, $is_connected );

		return $is_connected;
	}
}

if ( ! function_exists( 'ssp_get_not_synced_episodes' ) ) {
	/**
	 * Get all available posts that are registered as podcasts and not synced to Castos
	 *
	 * @return WP_Query
	 */
	function ssp_get_not_synced_episodes( $posts_per_page = -1 ) {
		$podcast_post_types = ssp_post_types();
		$args               = array(
			'post_type'      => $podcast_post_types,
			'posts_per_page' => $posts_per_page,
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

		return new WP_Query( $args );
	}
} // End if().

if ( ! function_exists( 'ssp_build_podcast_data' ) ) {
	/**
	 * Generate the podcast data to be send via the SSH API
	 *
	 * @param $podcast_query
	 *
	 * @return array $podcast_data
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

if ( ! function_exists( 'ssp_check_if_podcast_has_elementor_player' ) ) {
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
	 * @param int|null $default
	 *
	 * @return int
	 */
	function ssp_get_episode_series_id( $episode_id, $default = null ) {
		$series_id = isset( $default ) ? intval( $default ) : ssp_get_default_series_id();
		$series    = wp_get_post_terms( $episode_id, ssp_series_taxonomy() );

		if ( empty( $series ) || is_wp_error( $series ) ) {
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
		/**
		 * @var Castos_Handler $castos_handler
		 * */
		$castos_handler = ssp_get_service( 'castos_handler' );

		return $castos_handler->generate_series_data_for_castos( $series_id );
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
		// Prevent redundant media prefixes.
		if ( false !== strpos( $audio_file_url, $media_prefix ) ) {
			return $audio_file_url;
		}
		$url_parts = wp_parse_url( $audio_file_url );

		$new_url = $media_prefix . $url_parts['host'] . $url_parts['path'];
		if ( isset( $url_parts['query'] ) ) {
			$new_url .= '?' . $url_parts['query'];
		}

		return $new_url;
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
	 * @param \WP_Post|int|null $post
	 *
	 * @return string
	 */
	function ssp_get_the_feed_item_content( $post = null ) {
		$post = get_post( $post );

		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$content      = $post->post_content;
		$is_gutenberg = false !== strpos( $content, '<!-- wp:' );
		$blocks       = $is_gutenberg ? parse_blocks( $content ) : array();

		/**
		 * The same as in @see excerpt_remove_blocks() plus 'core/block',
		 * */
		if ( $blocks and is_array( $blocks ) ) {
			$content = '';
			$allowed_blocks = array(
				null,
				'core/freeform',
				'core/heading',
				'core/html',
				'core/list',
				'core/media-text',
				'core/paragraph',
				'core/preformatted',
				'core/pullquote',
				'core/quote',
				'core/table',
				'core/verse',
				'core/columns',
				'core/group',
				'core/block',
				'create-block/castos-transcript',
			);

			$allowed_blocks = apply_filters( 'ssp_feed_item_content_allowed_blocks', $allowed_blocks );

			$hidden_by_default = apply_filters( 'ssp_hidden_by_default_blocks', array(
				'core/group', // For backward compatibility, group block should be hidden
				'create-block/castos-transcript',
			) );

			foreach ( $blocks as $block ) {
				$is_allowed = in_array( $block['blockName'], $allowed_blocks ) &&
							  ( ! isset( $block['attrs']['hideFromFeed'] ) || true !== $block['attrs']['hideFromFeed'] );

				// Check for hidden by default blocks
				if ( $is_allowed && in_array( $block['blockName'], $hidden_by_default ) &&
					 ! isset( $block['attrs']['hideFromFeed'] ) ) {
					$is_allowed = false;
				}

				if ( ! $is_allowed ) {
					continue;
				}

				$block_content = render_block( $block );

				// Strip tags with content inside (styles, scripts)
				$strip_tags = array('style', 'script');

				foreach ( $strip_tags as $strip_tag ) {
					$strip_pattern = sprintf( '/<%s[^>]*>([\s\S]*?)<\/%s[^>]*>/', $strip_tag, $strip_tag );
					$block_content = preg_replace( $strip_pattern, '', $block_content );
				}
				$content .= $block_content;
			}
		} else {
			$frontend_controller = ssp_frontend_controller();
			$frontend_controller->remove_filter( 'the_content', 'content_meta_data' );
			$content = get_the_content_feed( 'rss2' );
			$frontend_controller->restore_filters();
		}

		$content = strip_shortcodes( $content );
		$content = preg_replace( '/<\/?iframe(.|\s)*?>/', '', $content );
		$content = str_replace( '<br>', PHP_EOL, $content );
		$content = strip_tags( $content, '<p>,<a>,<ul>,<ol>,<li>,<strong>,<em>,<h2>,<h3>,<h4>,<h5>,<label>' );

		// Remove empty paragraphs as well.
		$content = trim( str_replace( '<p></p>', '', $content ) );

		return apply_filters( 'ssp_feed_item_content', $content, get_the_ID() );
	}
}

/**
 * Get the episode content for showing in the feed. Now Apple supports only p tags.
 * This function removes iframes and shortcodes from the content and strips all tags except <p> and <a>
 */
if ( ! function_exists( 'ssp_get_episode_excerpt' ) ) {
	/**
	 * @param int|WP_Post $episode
	 *
	 * @return string
	 */
	function ssp_get_episode_excerpt( $episode ) {
		$episode = get_post( $episode );
		$excerpt = get_the_excerpt( $episode );

		$num_words = apply_filters( 'ssp_episode_excerpt_num_words', 50 );

		$excerpt = wp_trim_words( $excerpt, $num_words );

		return apply_filters( 'ssp_get_episode_excerpt', $excerpt, $episode );
	}
}


/**
 * Get the feed url by its slug
 */
if ( ! function_exists( 'ssp_get_feed_url' ) ) {
	/**
	 * @param string $series_slug
	 *
	 * @return string
	 * @since 2.8.2
	 */
	function ssp_get_feed_url( $series_slug = '' ) {

		$feed_series = $series_slug ?: 'default';

		$permalink_structure = get_option( 'permalink_structure' );

		$home_url = trailingslashit( home_url() );

		if ( $permalink_structure ) {
			$feed_slug = apply_filters( 'ssp_feed_slug', SSP_CPT_PODCAST );
			$feed_url  = $home_url . 'feed/' . $feed_slug;
		} else {
			$feed_url = $home_url . '?feed=' . SSP_CPT_PODCAST;
		}

		if ( $feed_series && 'default' !== $feed_series ) {
			if ( $permalink_structure ) {
				$feed_url .= '/' . $feed_series;
			} else {
				$feed_url .= '&podcast_series=' . $feed_series;
			}
		}

		$feed_url = trailingslashit( $feed_url );

		return apply_filters( 'ssp_get_feed_url', $feed_url, $series_slug );
	}
}


/**
 * Get the SSP option
 */
if ( ! function_exists( 'ssp_get_option' ) ) {
	/**
	 * @param string $option
	 * @param string $default
	 * @param int $series_id
	 *
	 * @return string|null
	 * @since 2.9.3
	 */
	function ssp_get_option( $option, $default = '', $series_id = 0 ) {
		$option = Settings_Controller::SETTINGS_BASE . $option;

		// Maybe append series ID to option name.
		if ( $series_id ) {
			$option .= '_' . $series_id;
		}

		$data = get_option( $option, $default );

		return apply_filters( 'ssp_get_setting', $data, compact( 'option', 'default', 'series_id' ) );
	}
}

/**
 * Get the SSP option
 */
if ( ! function_exists( 'ssp_add_option' ) ) {
	/**
	 * @param string $option
	 * @param mixed $value
	 * @param int $series_id
	 *
	 * @return bool
	 * @since 2.15.0
	 */
	function ssp_add_option( $option, $value, $series_id = '' ) {
		$option = Settings_Controller::SETTINGS_BASE . $option;

		// Maybe append series ID to option name.
		if ( $series_id ) {
			$option .= '_' . $series_id;
		}

		return add_option( $option, $value );
	}
}

/**
 * Get the SSP option
 */
if ( ! function_exists( 'ssp_update_option' ) ) {
	/**
	 * @param string $option
	 * @param mixed $value
	 * @param int $series_id
	 *
	 * @return bool
	 * @since 2.15.0
	 */
	function ssp_update_option( $option, $value, $series_id = '' ) {
		$option = Settings_Controller::SETTINGS_BASE . $option;

		// Maybe append series ID to option name.
		if ( $series_id ) {
			$option .= '_' . $series_id;
		}

		return update_option( $option, $value );
	}
}


/**
 * Check if it's an ajax action or not
 */
if ( ! function_exists( 'ssp_is_ajax' ) ) {

	/**
	 * Is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @return bool
	 */
	function ssp_is_ajax() {
		return function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}


/**
 * Get frontend controller.
 */
if ( ! function_exists( 'ssp_frontend_controller' ) ) {

	/**
	 * Get frontend controller.
	 *
	 * @return Frontend_Controller
	 */
	function ssp_frontend_controller() {
		global $ss_podcasting;
		return $ss_podcasting;
	}
}


/**
 * Get an episode controller.
 */
if ( ! function_exists( 'ssp_episode_controller' ) ) {

	/**
	 * Get an episode controller.
	 *
	 * @return Episode_Controller
	 */
	function ssp_episode_controller() {
		return ssp_frontend_controller()->episode_controller;
	}
}


/**
 * Get the current Series base slug.
 */
if ( ! function_exists( 'ssp_series_slug' ) ) {

	/**
	 * Get base slug for Series taxonomy.
	 * Since 2.14.0, Series taxonomy was renamed to Podcasts.
	 * @since 2.14.0
	 *
	 * @return string
	 */
	function ssp_series_slug() {
		if ( $slug = ssp_get_option( 'series_slug' ) ) {
			return $slug;
		}

		$is_existing_user = count( ssp_episodes() ) > 0;

		$slug = $is_existing_user ? ssp_series_taxonomy() : CPT_Podcast_Handler::DEFAULT_SERIES_SLUG;

		return apply_filters( 'ssp_series_slug', $slug );
	}
}


/**
 * Get the current Series base slug.
 */
if ( ! function_exists( 'ssp_get_podcast_image_src' ) ) {
	/**
	 *
	 * @param WP_Term $term
	 * @param string $size
	 *
	 * @return int|null
	 */
	function ssp_get_podcast_image_src( $term, $size = 'thumbnail' ) {
		return ssp_series_repository()->get_image_src( $term, $size );
	}
}

/**
 * Get SSP app object.
 */
if ( ! function_exists( 'ssp_app' ) ) {

	/**
	 * Get frontend controller.
	 *
	 * @return App_Controller
	 */
	function ssp_app() {
		global $ssp_app;
		if ( empty( $ssp_app ) ) {
			$ssp_app = new App_Controller();
		}

		return $ssp_app;
	}
}

/**
 * Get Service.
 */
if ( ! function_exists( 'ssp_get_service' ) ) {
	/**
	 * Get service object.
	 *
	 * @return Service
	 */
	function ssp_get_service( $service_id ) {
		return ssp_app()->get_service( $service_id );
	}
}


/**
 * Get SSP Player.
 */
if ( ! function_exists( 'ssp_player' ) ) {
	function ssp_player( $post_id = 0 ) {
		global $post;
		if ( ! $post_id ) {
			$post_id = $post->ID;
		}

		return ssp_frontend_controller()->audio_player( '', $post_id );
	}
}

/**
 * Get SSP episode image.
 */
if ( ! function_exists( 'ssp_episode_image' ) ) {
	function ssp_episode_image( $episode_id, $size = 'full' ) {
		return ssp_frontend_controller()->get_image( $episode_id, $size );
	}
}

/**
 * Gets media prefix
 */
if ( ! function_exists( 'ssp_get_media_prefix' ) ) {
	/**
	 * @param int $series_id
	 *
	 * @return string
	 * @since 2.20.0 Do not carry over the media prefix to subsequent podcasts
	 */
	function ssp_get_media_prefix( $series_id ) {
		return ssp_get_option( 'media_prefix', '', $series_id );
	}
}

/**
 * Gets renderer service.
 */
if ( ! function_exists( 'ssp_renderer' ) ) {
	/**
	 * @return Renderer|Service
	 */
	function ssp_renderer() {
		return ssp_get_service( 'renderer' );
	}
}

/**
 * Gets dynamo button ( in the feed and episode settings ).
 */
if ( ! function_exists( 'ssp_dynamo_btn' ) ) {
	/**
	 * Gets dynamo button
	 *
	 * @param string $title
	 * @param string $subtitle
	 * @param string $description
	 *
	 * @return string
	 * @since 2.20.0
	 */
	function ssp_dynamo_btn( $title, $subtitle, $description ) {
		$default_podcast_title = ssp_get_option( 'data_title', ssp_get_default_series_id() );
		if ( ! $title ) {
			$title = __( 'My new episode', 'seriously-simple-podcasting' );
		}
		if ( ! $subtitle ) {
			$title = __( "My Podcast Title", 'seriously-simple-podcasting' );
		}

		return ssp_renderer()->fetch( 'settings/dynamo-btn', compact( 'title', 'subtitle', 'description', 'default_podcast_title' ) );
	}
}


/**
 * Print upsell field.
 */
if ( ! function_exists( 'ssp_upsell_field' ) ) {
	/**
	 * Gets upsell field
	 *
	 * @param string $description
	 * @param array $btn
	 *
	 * @return string
	 * @since 2.21.0
	 */
	function ssp_upsell_field( $description, $btn ) {
		return ssp_renderer()->fetch( 'settings/upsell-field', compact( 'description', 'btn' ) );
	}
}

/**
 * Gets array of episode podcast terms.
 */
if ( ! function_exists( 'ssp_get_episode_podcasts' ) ) {
	/**
	 * Gets array of episode podcast terms.
	 *
	 * @param $post_id
	 *
	 * @return WP_Term[]
	 */
	function ssp_get_episode_podcasts( $post_id ) {
		$series = wp_get_post_terms( $post_id, 'series' );

		if ( is_wp_error( $series ) ) {
			return [];
		}

		return $series;
	}
}

/**
 * Gets array of podcast terms.
 */
if ( ! function_exists( 'ssp_get_podcasts' ) ) {
	/**
	 * Gets array of podcast terms.
	 *
	 * @param bool $hide_empty
	 *
	 * @return WP_Term[]
	 */
	function ssp_get_podcasts( $hide_empty = false ) {
		$podcasts = get_terms( ssp_series_taxonomy(), array( 'hide_empty' => $hide_empty ) );
		return is_array( $podcasts ) ? $podcasts : array();
	}
}

/**
 * Gets SSP Version.
 */
if ( ! function_exists( 'ssp_version' ) ) {
	/**
	 * Gets SSP Version.
	 *
	 * @return string|null
	 */
	function ssp_version() {
		return defined( 'SSP_VERSION' ) ? SSP_VERSION : null;
	}
}

/**
 * Gets SSP Version.
 */
if ( ! function_exists( 'ssp_series_taxonomy' ) ) {
	/**
	 * Gets SSP Version.
	 *
	 * @return string|null
	 */
	function ssp_series_taxonomy() {
		return apply_filters( 'ssp_series_taxonomy', 'series' );
	}
}

/**
 * Gets SSP Version.
 */
if ( ! function_exists( 'ssp_renderer' ) ) {
	/**
	 * Gets SSP Version.
	 *
	 * @return Renderer|Service
	 */
	function ssp_renderer() {
		return ssp_app()->get_service('renderer');
	}
}

if( ! function_exists('ssp_config') ){
	/**
	 * @param string $name
	 * @param array $args
	 *
	 * @return array
	 */
	function ssp_config( $name, $args = array() ) {
		$args   = extract( $args );
		$name   = trim( $name, '/' );
		$config = require SSP_PLUGIN_PATH . '/php/config/' . $name . '.php';

		return apply_filters( 'ssp_config', $config, $name );
	}
}

if( ! function_exists('ssp_get_tab_url') ){
	/**
	 * @param string $tab
	 *
	 * @return string
	 */
	function ssp_get_tab_url( $tab ) {
		return add_query_arg( array(
			'post_type' => SSP_CPT_PODCAST,
			'page'      => 'podcast_settings',
			'tab'       => $tab
		), admin_url( 'edit.php' ) );
	}
}


if( ! function_exists('ssp_get_default_series_id') ){
	/**
	 *
	 * @return int
	 */
	function ssp_get_default_series_id() {
		return intval( ssp_get_option( 'default_series' ) );
	}
}

if( ! function_exists('ssp_get_default_series') ){
	/**
	 *
	 * @return WP_Term|null
	 */
	function ssp_get_default_series() {
		$series_id = ssp_get_default_series_id();
		if ( $series_id ) {
			$series = get_term_by( 'id', $series_id, ssp_series_taxonomy() );
		}

		return empty( $series ) ? null : $series;
	}
}

if( ! function_exists('ssp_get_default_series_slug') ){
	/**
	 *
	 * @return string
	 */
	function ssp_get_default_series_slug() {
		$series = ssp_get_default_series();

		return $series ? $series->slug : '';
	}
}

if ( ! function_exists( 'ssp_get_default_series_name' ) ) {
	/**
	 * @param string $name
	 *
	 * @return string
	 */
	function ssp_get_default_series_name( $name ) {
		return sprintf(
			__( '%s (default)', 'seriously-simple-podcasting' ),
			$name
		);
	}
}

if( ! function_exists('ssp_series_repository') ){
	/**
	 * @return Series_Repository
	 */
	function ssp_series_repository() {
		return Series_Repository::instance();
	}
}
