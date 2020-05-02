<?php
/**
 * Podcast RSS feed template
 *
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// @todo move all of this logic into the Feed_Controller render_podcast_feed method, at the very least
global $ss_podcasting, $wp_query;

// @todo turn this off and fix any errors
// Hide all errors
error_reporting( 0 );

// Allow feed access by default
$give_access = true;

// Check if feed is password protected
$protection = get_option( 'ss_podcasting_protect', '' );

// Handle feed protection if required
if ( $protection && 'on' === $protection ) {

	$give_access = false;

	// Request password and give access if correct
	if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) && ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
		$give_access = false;
	} else {
		$username = get_option( 'ss_podcasting_protection_username' );
		$password = get_option( 'ss_podcasting_protection_password' );

		if ( $_SERVER['PHP_AUTH_USER'] === $username ) {
			if ( md5( $_SERVER['PHP_AUTH_PW'] ) === $password ) {
				$give_access = true;
			}
		}
	}
}

// Get specified podcast series
$podcast_series = '';
if ( isset( $_GET['podcast_series'] ) && $_GET['podcast_series'] ) {
	$podcast_series = esc_attr( $_GET['podcast_series'] );
} elseif ( isset( $wp_query->query_vars['podcast_series'] ) && $wp_query->query_vars['podcast_series'] ) {
	$podcast_series = esc_attr( $wp_query->query_vars['podcast_series'] );
}

// Get series ID
$series_id = 0;
if ( $podcast_series ) {
	$series    = get_term_by( 'slug', $podcast_series, 'series' );
	$series_id = $series->term_id;
}

// Allow dynamic access control
$give_access = apply_filters( 'ssp_feed_access', $give_access, $series_id );

// Send 401 status and display no access message if access has been denied
if ( ! $give_access ) {

	// Set default message
	$message = __( 'You are not permitted to view this podcast feed.', 'seriously-simple-podcasting' );

	// Check message option from plugin settings
	$message_option = get_option( 'ss_podcasting_protection_no_access_message' );
	if ( $message_option ) {
		$message = $message_option;
	}

	// Allow message to be filtered dynamically
	$message = apply_filters( 'ssp_feed_no_access_message', $message );

	$no_access_message = '<div style="text-align:center;font-family:sans-serif;border:1px solid red;background:pink;padding:20px 0;color:red;">' . $message . '</div>';

	header( 'WWW-Authenticate: Basic realm="Podcast Feed"' );
	header( 'HTTP/1.0 401 Unauthorized' );

	die( $no_access_message );
}

// If redirect is on, get new feed URL and redirect if setting was changed more than 48 hours ago
$redirect     = get_option( 'ss_podcasting_redirect_feed' );
$new_feed_url = false;
if ( $redirect && 'on' === $redirect ) {

	$new_feed_url = get_option( 'ss_podcasting_new_feed_url' );
	$update_date  = get_option( 'ss_podcasting_redirect_feed_date' );

	if ( $new_feed_url && $update_date ) {
		$redirect_date = strtotime( '+2 days', $update_date );
		$current_date  = time();

		// Redirect with 301 if it is more than 2 days since redirect was saved
		if ( $current_date > $redirect_date ) {
			header( 'HTTP/1.1 301 Moved Permanently' );
			header( 'Location: ' . $new_feed_url );
			exit;
		}
	}
}

// if this is the default feed, check for series for which posts should be excluded
$exclude_series = array();
if ( empty( $series_id ) ) {
	$series = get_terms(
		array(
			'taxonomy'   => 'series',
			'hide_empty' => false,
		)
	);
	foreach ( $series as $feed ) {
		$option_name         = 'ss_podcasting_exclude_feed_' . $feed->term_id;
		$exclude_feed_option = get_option( $option_name, 'off' );
		if ( 'on' === $exclude_feed_option ) {
			$exclude_series[] = $feed->slug;
		}
	}
}

// If this is a series specific feed, then check if we need to redirect
if ( $series_id ) {
	$redirect     = get_option( 'ss_podcasting_redirect_feed_' . $series_id );
	$new_feed_url = false;
	if ( $redirect && 'on' === $redirect ) {
		$new_feed_url = get_option( 'ss_podcasting_new_feed_url_' . $series_id );
		if ( $new_feed_url ) {
			header( 'HTTP/1.1 301 Moved Permanently' );
			header( 'Location: ' . $new_feed_url );
			exit;
		}
	}
}

// Podcast title
$title = get_option( 'ss_podcasting_data_title', get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_title = get_option( 'ss_podcasting_data_title_' . $series_id, '' );
	if ( $series_title ) {
		$title = $series_title;
	}
}
$title = apply_filters( 'ssp_feed_title', $title, $series_id );

// Podcast description
$description = get_option( 'ss_podcasting_data_description', get_bloginfo( 'description' ) );
if ( $podcast_series ) {
	$series_description = get_option( 'ss_podcasting_data_description_' . $series_id, '' );
	if ( $series_description ) {
		$description = $series_description;
	}
}
$podcast_description = mb_substr( strip_tags( $description ), 0, 3999 );
$podcast_description = apply_filters( 'ssp_feed_description', $podcast_description, $series_id );

// Podcast language
$language = get_option( 'ss_podcasting_data_language', get_bloginfo( 'language' ) );
if ( $podcast_series ) {
	$series_language = get_option( 'ss_podcasting_data_language_' . $series_id, '' );
	if ( $series_language ) {
		$language = $series_language;
	}
}
$language = apply_filters( 'ssp_feed_language', $language, $series_id );

// Podcast copyright string
$copyright = get_option( 'ss_podcasting_data_copyright', '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_copyright = get_option( 'ss_podcasting_data_copyright_' . $series_id, '' );
	if ( $series_copyright ) {
		$copyright = $series_copyright;
	}
}
$copyright = apply_filters( 'ssp_feed_copyright', $copyright, $series_id );

// Podcast subtitle
$subtitle = get_option( 'ss_podcasting_data_subtitle', get_bloginfo( 'description' ) );
if ( $podcast_series ) {
	$series_subtitle = get_option( 'ss_podcasting_data_subtitle_' . $series_id, '' );
	if ( $series_subtitle ) {
		$subtitle = $series_subtitle;
	}
}
$subtitle = apply_filters( 'ssp_feed_subtitle', $subtitle, $series_id );

// Podcast author
$author = get_option( 'ss_podcasting_data_author', get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_author = get_option( 'ss_podcasting_data_author_' . $series_id, '' );
	if ( $series_author ) {
		$author = $series_author;
	}
}
$author = apply_filters( 'ssp_feed_author', $author, $series_id );

// Podcast owner name
$owner_name = get_option( 'ss_podcasting_data_owner_name', get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_owner_name = get_option( 'ss_podcasting_data_owner_name_' . $series_id, '' );
	if ( $series_owner_name ) {
		$owner_name = $series_owner_name;
	}
}
$owner_name = apply_filters( 'ssp_feed_owner_name', $owner_name, $series_id );

// Podcast owner email address
$owner_email = get_option( 'ss_podcasting_data_owner_email', get_bloginfo( 'admin_email' ) );
if ( $podcast_series ) {
	$series_owner_email = get_option( 'ss_podcasting_data_owner_email_' . $series_id, '' );
	if ( $series_owner_email ) {
		$owner_email = $series_owner_email;
	}
}
$owner_email = apply_filters( 'ssp_feed_owner_email', $owner_email, $series_id );

// Podcast explicit setting
$explicit_option = get_option( 'ss_podcasting_explicit', '' );
if ( $podcast_series ) {
	$series_explicit_option = get_option( 'ss_podcasting_explicit_' . $series_id, '' );
	$explicit_option        = $series_explicit_option;
}
$explicit_option = apply_filters( 'ssp_feed_explicit', $explicit_option, $series_id );
if ( $explicit_option && 'on' === $explicit_option ) {
	$itunes_explicit     = 'yes';
	$googleplay_explicit = 'Yes';
} else {
	$itunes_explicit     = 'clean';
	$googleplay_explicit = 'No';
}

// Podcast complete setting
$complete_option = get_option( 'ss_podcasting_complete', '' );
if ( $podcast_series ) {
	$series_complete_option = get_option( 'ss_podcasting_complete_' . $series_id, '' );
	$complete_option        = $series_complete_option;
}
$complete_option = apply_filters( 'ssp_feed_complete', $complete_option, $series_id );
if ( $complete_option && 'on' === $complete_option ) {
	$complete = 'yes';
} else {
	$complete = '';
}

// Podcast cover image
$image = get_option( 'ss_podcasting_data_image', '' );
if ( $podcast_series ) {
	$series_image = get_option( 'ss_podcasting_data_image_' . $series_id, 'no-image' );
	if ( 'no-image' !== $series_image ) {
		$image = $series_image;
	}
}
$image = apply_filters( 'ssp_feed_image', $image, $series_id );

// Podcast category and subcategory (all levels) - can be filtered with `ssp_feed_category_output`
$category1 = ssp_get_feed_category_output( 1, $series_id );
$category2 = ssp_get_feed_category_output( 2, $series_id );
$category3 = ssp_get_feed_category_output( 3, $series_id );

// Get iTunes Type
$itunes_type = get_option( 'ss_podcasting_consume_order' . ( $series_id > 0 ? '_' . $series_id : null ) );

// Get turbo setting
$turbo = get_option( 'ss_podcasting_turbocharge_feed', 'off' );
if ( $series_id && $series_id > 0 ) {
	$series_turbo = get_option( 'ss_podcasting_turbocharge_feed_' . $series_id );
	if ( false !== $series_turbo ) {
		$turbo = $series_turbo;
	}
}

// Get media prefix setting
$media_prefix = get_option( 'ss_podcasting_media_prefix', '' );
if ( $series_id && $series_id > 0 ) {
	$series_media_prefix = get_option( 'ss_podcasting_media_prefix_' . $series_id );
	if ( false !== $series_media_prefix ) {
		$media_prefix = $series_media_prefix;
	}
}

// Get episode description setting
$episode_description              = get_option( 'ss_podcasting_episode_description', 'excerpt' );
$episode_description_uses_excerpt = 'excerpt' === $episode_description;
if ( $series_id && $series_id > 0 ) {
	$series_episode_description = get_option( 'episode_description_' . $series_id );
	if ( false !== $series_episode_description ) {
		$episode_description = $series_episode_description;
	}
}

// Get stylehseet URL (filterable to allow disabling or custom RSS stylesheets)
$apply_stylesheet_url = apply_filters( 'ssp_enable_rss_stylesheet', true );
if ( $apply_stylesheet_url ) {
	$stylesheet_url = apply_filters( 'ssp_rss_stylesheet', $ss_podcasting->template_url . 'feed-stylesheet.xsl' );
}

// Set RSS content type and charset headers
header( 'Content-Type: ' . feed_content_type( 'podcast' ) . '; charset=' . get_option( 'blog_charset' ), true );

// Use `echo` for first line to prevent any extra characters at start of document
echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?>' . "\n";

// Include RSS stylesheet
if ( $stylesheet_url ) {
	echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( $stylesheet_url ) . '"?>';
}

?>
<rss version="2.0"
xmlns:content="http://purl.org/rss/1.0/modules/content/"
xmlns:wfw="http://wellformedweb.org/CommentAPI/"
xmlns:dc="http://purl.org/dc/elements/1.1/"
xmlns:atom="http://www.w3.org/2005/Atom"
xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
xmlns:googleplay="http://www.google.com/schemas/play-podcasts/1.0"
<?php do_action( 'rss2_ns' ); ?>>
	<channel>
		<title><?php echo esc_html( $title ); ?></title>
		<atom:link href="<?php esc_url( self_link() ); ?>" rel="self" type="application/rss+xml"/>
		<link><?php echo esc_url( apply_filters( 'ssp_feed_channel_link_tag', $ss_podcasting->home_url, $podcast_series ) ) ?></link>
		<description><?php echo esc_html( $description ); ?></description>
		<lastBuildDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ); ?></lastBuildDate>
		<language><?php echo esc_html( $language ); ?></language>
		<copyright><?php echo esc_html( $copyright ); ?></copyright>
		<itunes:subtitle><?php echo esc_html( $subtitle ); ?></itunes:subtitle>
		<itunes:author><?php echo esc_html( $author ); ?></itunes:author>
		<?php
		if ( $itunes_type ) {
			?>
			<itunes:type><?php echo $itunes_type; ?></itunes:type>
			<?php
		}
		?>
		<itunes:summary><?php echo esc_html( $podcast_description ); ?></itunes:summary>
		<itunes:owner>
			<itunes:name><?php echo esc_html( $owner_name ); ?></itunes:name>
			<itunes:email><?php echo esc_html( $owner_email ); ?></itunes:email>
		</itunes:owner>
		<itunes:explicit><?php echo esc_html( $itunes_explicit ); ?></itunes:explicit>
		<?php if ( $complete ) { ?>
			<itunes:complete><?php echo esc_html( $complete ); ?></itunes:complete><?php }
		if ( $image ) {
			?>
			<itunes:image href="<?php echo esc_url( $image ); ?>"></itunes:image>
			<image>
				<url><?php echo esc_url( $image ); ?></url>
				<title><?php echo esc_html( $title ); ?></title>
				<link><?php echo esc_url( apply_filters( 'ssp_feed_channel_link_tag', $ss_podcasting->home_url, $podcast_series ) ) ?></link>
			</image>
		<?php }
		if ( isset( $category1['category'] ) && $category1['category'] ) { ?>
			<itunes:category text="<?php echo esc_attr( $category1['category'] ); ?>">
				<?php if ( isset( $category1['subcategory'] ) && $category1['subcategory'] ) { ?>
					<itunes:category text="<?php echo esc_attr( $category1['subcategory'] ); ?>"></itunes:category>
				<?php } ?>
			</itunes:category>
		<?php } ?>
		<?php if ( isset( $category2['category'] ) && $category2['category'] ) { ?>
			<itunes:category text="<?php echo esc_attr( $category2['category'] ); ?>">
				<?php if ( isset( $category2['subcategory'] ) && $category2['subcategory'] ) { ?>
					<itunes:category text="<?php echo esc_attr( $category2['subcategory'] ); ?>"></itunes:category>
				<?php } ?>
			</itunes:category>
		<?php } ?>
		<?php if ( isset( $category3['category'] ) && $category3['category'] ) { ?>
			<itunes:category text="<?php echo esc_attr( $category3['category'] ); ?>">
				<?php if ( isset( $category3['subcategory'] ) && $category3['subcategory'] ) { ?>
					<itunes:category text="<?php echo esc_attr( $category3['subcategory'] ); ?>"></itunes:category>
				<?php } ?>
			</itunes:category>
		<?php } ?>
		<?php if ( $new_feed_url ) { ?>
			<itunes:new-feed-url><?php echo esc_url( $new_feed_url ); ?></itunes:new-feed-url>
		<?php } ?>
		<?php if ( 'off' === $turbo ) { ?>
			<googleplay:author><?php echo esc_html( $author ); ?></googleplay:author>
			<googleplay:email><?php echo esc_html( $owner_email ); ?></googleplay:email>
			<googleplay:description><?php echo esc_html( $podcast_description ); ?></googleplay:description>
			<googleplay:explicit><?php echo esc_html( $googleplay_explicit ); ?></googleplay:explicit>
			<?php if ( $image ) { ?>
				<googleplay:image href="<?php echo esc_url( $image ); ?>"></googleplay:image>
			<?php } ?>
		<?php } ?>

		<?php
		// Prevent WP core from outputting an <image> element
		remove_action( 'rss2_head', 'rss2_site_icon' );

		// Add RSS2 headers
		do_action( 'rss2_head' );

		// Get post IDs of all podcast episodes
		$num_posts = intval( apply_filters( 'ssp_feed_number_of_posts', get_option( 'posts_per_rss', 10 ) ) );

		$args = ssp_episodes( $num_posts, $podcast_series, true, 'feed', $exclude_series );

		$qry = new WP_Query( $args );

		if ( 'on' === $turbo ) {
			$turbo_post_count = 0;
		}

		if ( $qry->have_posts() ) {
			while ( $qry->have_posts() ) {
				$qry->the_post();

				// Audio file
				$audio_file = $ss_podcasting->get_enclosure( get_the_ID() );
				if ( get_option( 'permalink_structure' ) ) {
					$enclosure = $ss_podcasting->get_episode_download_link( get_the_ID() );
				} else {
					$enclosure = $audio_file;
				}

				$enclosure = apply_filters( 'ssp_feed_item_enclosure', $enclosure, get_the_ID() );

				if ( ! empty( $media_prefix ) ) {
					$enclosure = parse_episode_url_with_media_prefix( $enclosure, $media_prefix );
				}

				// If there is no enclosure then go no further
				if ( ! isset( $enclosure ) || ! $enclosure ) {
					continue;
				}

				// Get episode image from post featured image
				$episode_image = '';
				$image_id      = get_post_thumbnail_id( get_the_ID() );
				if ( $image_id ) {
					$image_att = wp_get_attachment_image_src( $image_id, 'full' );
					if ( $image_att ) {
						$episode_image = $image_att[0];
					}
				}
				$episode_image = apply_filters( 'ssp_feed_item_image', $episode_image, get_the_ID() );

				// Episode duration (default to 0:00 to ensure there is always a value for this)
				$duration = get_post_meta( get_the_ID(), 'duration', true );
				if ( ! $duration ) {
					$duration = '0:00';
				}
				$duration = apply_filters( 'ssp_feed_item_duration', $duration, get_the_ID() );

				// File size
				$size = get_post_meta( get_the_ID(), 'filesize_raw', true );

				if ( ! $size ) {
					$formatted_size = get_post_meta( get_the_ID(), 'filesize', true );
					if ( ssp_is_connected_to_castos() || $formatted_size ) {
						$size = convert_human_readable_to_bytes( $formatted_size );
					} else {
						$size = 1;
					}
				}
				$size = apply_filters( 'ssp_feed_item_size', $size, get_the_ID() );

				// File MIME type (default to MP3/MP4 to ensure there is always a value for this)
				$mime_type = $ss_podcasting->get_attachment_mimetype( $audio_file );
				if ( ! $mime_type ) {

					// Get the episode type (audio or video) to determine the appropriate default MIME type
					$episode_type = $ss_podcasting->get_episode_type( get_the_ID() );
					switch ( $episode_type ) {
						case 'audio':
							$mime_type = 'audio/mpeg';
							break;
						case 'video':
							$mime_type = 'video/mp4';
							break;
					}
				}
				$mime_type = apply_filters( 'ssp_feed_item_mime_type', $mime_type, get_the_ID() );

				// Episode explicit flag
				$ep_explicit = get_post_meta( get_the_ID(), 'explicit', true );
				$ep_explicit = apply_filters( 'ssp_feed_item_explicit', $ep_explicit, get_the_ID() );
				if ( $ep_explicit && $ep_explicit == 'on' ) {
					$itunes_explicit_flag     = 'yes';
					$googleplay_explicit_flag = 'Yes';
				} else {
					$itunes_explicit_flag     = 'clean';
					$googleplay_explicit_flag = 'No';
				}

				// Episode block flag
				$ep_block = get_post_meta( get_the_ID(), 'block', true );
				$ep_block = apply_filters( 'ssp_feed_item_block', $ep_block, get_the_ID() );
				if ( $ep_block && $ep_block == 'on' ) {
					$block_flag = 'yes';
				} else {
					$block_flag = 'no';
				}

				// Episode author
				$author = esc_html( get_the_author() );
				$author = apply_filters( 'ssp_feed_item_author', $author, get_the_ID() );

				// Episode content (with shortcodes and iframes removed)
				$content = get_the_content_feed( 'rss2' );
				$content = strip_shortcodes( $content );
				$content = preg_replace( '/<\/?iframe(.|\s)*?>/', '', $content );
				$content = apply_filters( 'ssp_feed_item_content', $content, get_the_ID() );

				// Description is set based on feed setting
				if ( $episode_description_uses_excerpt) {
					ob_start();
					the_excerpt_rss();
					$description = ob_get_clean();
				} else {
					$description = $content;
					if ( isset( $turbo_post_count ) && $turbo_post_count > 10 ) {
						// If turbo is on, limit the full html description to 4000 chars
						$description = mb_substr( $description, 0, 3999 );
					}
				}
				$description = apply_filters( 'ssp_feed_item_description', $description, get_the_ID() );

				// iTunes summary excludes HTML and must be shorter than 4000 characters
				$itunes_summary = wp_strip_all_tags( $description );
				$itunes_summary = mb_substr( $itunes_summary, 0, 3999 );
				$itunes_summary = apply_filters( 'ssp_feed_item_itunes_summary', $itunes_summary, get_the_ID() );

				// Google Play description is the same as iTunes summary, but must be shorter than 1000 characters
				$gp_description = mb_substr( $itunes_summary, 0, 999 );
				$gp_description = apply_filters( 'ssp_feed_item_gp_description', $gp_description, get_the_ID() );

				// iTunes subtitle excludes HTML and must be shorter than 255 characters
				$itunes_subtitle = wp_strip_all_tags( $description );
				$itunes_subtitle = str_replace(
					array(
						'>',
						'<',
						'\'',
						'"',
						'`',
						'[andhellip;]',
						'[&hellip;]',
						'[&#8230;]',
					),
					array( '', '', '', '', '', '', '', '' ),
					$itunes_subtitle
				);
				$itunes_subtitle = mb_substr( $itunes_subtitle, 0, 254 );
				$itunes_subtitle = apply_filters( 'ssp_feed_item_itunes_subtitle', $itunes_subtitle, get_the_ID() );

				// Date recorded
				$pub_date_type = get_option( 'ss_podcasting_publish_date', 'published' );
				if ( 'published' === $pub_date_type ) {
					$pub_date = esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ) );
				} else // 'recorded'.
				{
					$pub_date = esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_post_meta( get_the_ID(), 'date_recorded', true ), false ) );
				}

				// Tags/keywords
				$post_tags = get_the_tags( get_the_ID() );
				if ( $post_tags ) {
					$tags = array();
					foreach ( $post_tags as $tag ) {
						$tags[] = $tag->name;
					}
					$tags = apply_filters( 'ssp_feed_item_itunes_keyword_tags', $tags, get_the_ID() );
					if ( ! empty( $tags ) ) {
						$keywords = implode( $tags, ',' );
					}
				}

				$is_itunes_fields_enabled = get_option( 'ss_podcasting_itunes_fields_enabled' );
				if ( $is_itunes_fields_enabled && $is_itunes_fields_enabled == 'on' ) {
					// New iTunes WWDC 2017 Tags
					$itunes_episode_type   = get_post_meta( get_the_ID(), 'itunes_episode_type', true );
					$itunes_title          = get_post_meta( get_the_ID(), 'itunes_title', true );
					$itunes_episode_number = get_post_meta( get_the_ID(), 'itunes_episode_number', true );
					$itunes_season_number  = get_post_meta( get_the_ID(), 'itunes_season_number', true );
				}
				if ( isset( $turbo_post_count ) ) {
					$turbo_post_count ++;
				}
				?>
				<item>
					<title><?php esc_html( the_title_rss() ); ?></title>
					<link><?php esc_url( the_permalink_rss() ); ?></link>
					<pubDate><?php echo $pub_date; ?></pubDate>
					<dc:creator><?php echo $author; ?></dc:creator>
					<guid isPermaLink="false"><?php esc_html( the_guid() ); ?></guid>
					<description><![CDATA[<?php echo $description; ?>]]></description>
					<itunes:subtitle><![CDATA[<?php echo $itunes_subtitle; ?>]]></itunes:subtitle>
					<?php if ( $keywords ) : ?>
						<itunes:keywords><?php echo $keywords; ?></itunes:keywords>
					<?php endif; ?>
					<?php if ( $itunes_episode_type ) : ?>
						<itunes:episodeType><?php echo $itunes_episode_type; ?></itunes:episodeType>
					<?php endif; ?>
					<?php if ( $itunes_title ): ?>
						<itunes:title><![CDATA[<?php echo $itunes_title; ?>]]></itunes:title>
					<?php endif; ?>
					<?php if ( $itunes_episode_number ): ?>
						<itunes:episode><?php echo $itunes_episode_number; ?></itunes:episode>
					<?php endif; ?>
					<?php if ( $itunes_season_number ): ?>
						<itunes:season><?php echo $itunes_season_number; ?></itunes:season>
					<?php endif; ?>
					<?php if ( ! isset( $turbo_post_count ) || $turbo_post_count <= 10 ) { ?>
						<content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded>
					<?php } ?>
					<enclosure url="<?php echo esc_url( $enclosure ); ?>" length="<?php echo esc_attr( $size ); ?>" type="<?php echo esc_attr( $mime_type ); ?>"></enclosure>
					<?php if ( ! isset( $turbo_post_count ) || $turbo_post_count <= 10 ) { ?>
						<itunes:summary><![CDATA[<?php echo $itunes_summary; ?>]]></itunes:summary>
					<?php } ?>
					<?php if ( $episode_image ) { ?>
						<itunes:image href="<?php echo esc_url( $episode_image ); ?>"></itunes:image>
					<?php } ?>
					<itunes:explicit><?php echo esc_html( $itunes_explicit_flag ); ?></itunes:explicit>
					<itunes:block><?php echo esc_html( $block_flag ); ?></itunes:block>
					<itunes:duration><?php echo esc_html( $duration ); ?></itunes:duration>
					<itunes:author><?php echo $author; ?></itunes:author>
					<?php if ( 'off' === $turbo ) { ?>
						<googleplay:description><![CDATA[<?php echo $gp_description; ?>]]></googleplay:description>
						<?php if ( $episode_image ) { ?>
							<googleplay:image href="<?php echo esc_url( $episode_image ); ?>"></googleplay:image>
						<?php } ?>
						<googleplay:explicit><?php echo esc_html( $googleplay_explicit_flag ); ?></googleplay:explicit>
						<googleplay:block><?php echo esc_html( $block_flag ); ?></googleplay:block>
					<?php } ?>
				</item>
			<?php }
		} ?>
	</channel>
</rss>
