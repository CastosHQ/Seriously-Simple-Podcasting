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

global $ss_podcasting, $wp_query;


// Hide all errors
error_reporting( 0 );

// Check if feed is password protected
$protection = get_option( 'ss_podcasting_protect', '' );

if ( $protection && $protection == 'on' ) {

	$give_access = false;

	$message_option = get_option('ss_podcasting_protection_no_access_message');
	$message = __( 'You are not permitted to view this podcast feed.' , 'seriously-simple-podcasting' );
	if ( $message_option && strlen( $message_option ) > 0 && $message_option != '' ) {
		$message = $message_option;
	}

	// Request password and give access if correct
	if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) && ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
	    $give_access = false;
	} else {
		$username = get_option( 'ss_podcasting_protection_username' );
		$password = get_option( 'ss_podcasting_protection_password' );

		if ( $_SERVER['PHP_AUTH_USER'] == $username ) {
			if ( md5( $_SERVER['PHP_AUTH_PW'] ) == $password ) {
				$give_access = true;
			}
		}
	}

	// Send 401 status and display no access message
	if ( ! $give_access ) {

		$no_access_message = '<div style="text-align:center;font-family:sans-serif;border:1px solid red;background:pink;padding:20px 0;color:red;">' . $message . '</div>';

		header('WWW-Authenticate: Basic realm="Podcast Feed"');
	    header('HTTP/1.0 401 Unauthorized');

		die( $no_access_message );
	}
}

// If redirect is on, get new feed URL and redirect if setting was changed more than 48 hours ago
$redirect = get_option( 'ss_podcasting_redirect_feed' );
$new_feed_url = false;
if ( $redirect && $redirect == 'on' ) {

	$new_feed_url = get_option( 'ss_podcasting_new_feed_url' );
	$update_date = get_option( 'ss_podcasting_redirect_feed_date' );

	if ( $new_feed_url && $update_date ) {
		$redirect_date = strtotime( '+2 days' , $update_date );
		$current_date = time();

		// Redirect with 301 if it is more than 2 days since redirect was saved
		if ( $current_date > $redirect_date ) {
			header ( 'HTTP/1.1 301 Moved Permanently' );
			header ( 'Location: ' . $new_feed_url );
			exit;
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

$series_id = 0;
if ( $podcast_series ) {
	$series = get_term_by( 'slug', $podcast_series, 'series' );
	$series_id = $series->term_id;

	// Do we need to redirect a single feed?
	$redirect = get_option( 'ss_podcasting_redirect_feed_' . $series_id );
	$new_feed_url = false;
	if ( $redirect && $redirect == 'on' ) {
		$new_feed_url = get_option( 'ss_podcasting_new_feed_url_' . $series_id );
		if ( $new_feed_url ) {
			header ( 'HTTP/1.1 301 Moved Permanently' );
			header ( 'Location: ' . $new_feed_url );
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

// Podcast description
$description = get_option( 'ss_podcasting_data_description', get_bloginfo( 'description' ) );
if ( $podcast_series ) {
	$series_description = get_option( 'ss_podcasting_data_description_' . $series_id, '' );
	if ( $series_description ) {
		$description = $series_description;
	}
}
$podcast_description = mb_substr( strip_tags( $description ), 0, 3999 );

// Podcast language
$language = get_option( 'ss_podcasting_data_language', get_bloginfo( 'language' ) );
if ( $podcast_series ) {
	$series_language = get_option( 'ss_podcasting_data_language_' . $series_id, '' );
	if ( $series_language ) {
		$language = $series_language;
	}
}

// Podcast copyright string
$copyright = get_option( 'ss_podcasting_data_copyright', '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_copyright = get_option( 'ss_podcasting_data_copyright_' . $series_id, '' );
	if ( $series_copyright ) {
		$copyright = $series_copyright;
	}
}

// Podcast subtitle
$subtitle = get_option( 'ss_podcasting_data_subtitle', get_bloginfo( 'description' ) );
if ( $podcast_series ) {
	$series_subtitle = get_option( 'ss_podcasting_data_subtitle_' . $series_id, '' );
	if ( $series_subtitle ) {
		$subtitle = $series_subtitle;
	}
}

// Podcast author
$author = get_option( 'ss_podcasting_data_author', get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_author = get_option( 'ss_podcasting_data_author_' . $series_id, '' );
	if ( $series_author ) {
		$author = $series_author;
	}
}

// Podcast owner name
$owner_name = get_option( 'ss_podcasting_data_owner_name', get_bloginfo( 'name' ) );
if ( $podcast_series ) {
	$series_owner_name = get_option( 'ss_podcasting_data_owner_name_' . $series_id, '' );
	if ( $series_owner_name ) {
		$owner_name = $series_owner_name;
	}
}

// Podcast owner email address
$owner_email = get_option( 'ss_podcasting_data_owner_email', get_bloginfo( 'admin_email' ) );
if ( $podcast_series ) {
	$series_owner_email = get_option( 'ss_podcasting_data_owner_email_' . $series_id, '' );
	if ( $series_owner_email ) {
		$owner_email = $series_owner_email;
	}
}

// Podcast explicit setting
$explicit_option = get_option( 'ss_podcasting_explicit', '' );
if ( $podcast_series ) {
	$series_explicit_option = get_option( 'ss_podcasting_explicit_' . $series_id, '' );
	$explicit_option = $series_explicit_option;
}
if ( $explicit_option && 'on' == $explicit_option ) {
	$itunes_explicit = 'yes';
	$googleplay_explicit = 'Yes';
} else {
	$itunes_explicit = 'clean';
	$googleplay_explicit = 'No';
}

// Podcast complete setting
$complete_option = get_option( 'ss_podcasting_complete', '' );
if ( $podcast_series ) {
	$series_complete_option = get_option( 'ss_podcasting_complete_' . $series_id, '' );
	$complete_option = $series_complete_option;
}
if ( $complete_option && 'on' == $complete_option ) {
	$complete = 'yes';
} else {
	$complete = '';
}

// Podcast cover image
$image = get_option( 'ss_podcasting_data_image', '' );
if ( $podcast_series ) {
	$series_image = get_option( 'ss_podcasting_data_image_' . $series_id, 'no-image' );
	if ( 'no-image' != $series_image ) {
		$image = $series_image;
	}
}

// Podcast category and subcategory (all levels)
$category1 = ssp_get_feed_category_output( 1, $series_id );
$category2 = ssp_get_feed_category_output( 2, $series_id );
$category3 = ssp_get_feed_category_output( 3, $series_id );

// Get stylehseet URL (filterable to allow custom RSS stylesheets)
$stylehseet_url = apply_filters( 'ssp_rss_stylesheet', $ss_podcasting->template_url . 'feed-stylesheet.xsl' );

// Set RSS header
header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );

// Use `echo` for first line to prevent any extra characters at start of document
echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?>' . "\n";

// Include RSS stylesheet
if( $stylehseet_url ) {
	echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( $stylehseet_url ) . '"?>';
} ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
	xmlns:googleplay="http://www.google.com/schemas/play-podcasts/1.0"
	<?php do_action( 'rss2_ns' ); ?>
>

<channel>
	<title><?php echo esc_html( $title ); ?></title>
	<atom:link href="<?php esc_url( self_link() ); ?>" rel="self" type="application/rss+xml" />
	<link><?php echo esc_url( apply_filters( 'ssp_feed_channel_link_tag', $ss_podcasting->home_url, $podcast_series ) ) ?></link>
	<description><?php echo esc_html( $description ); ?></description>
	<lastBuildDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ); ?></lastBuildDate>
	<language><?php echo esc_html( $language ); ?></language>
	<copyright><?php echo esc_html( $copyright ); ?></copyright>
	<itunes:subtitle><?php echo esc_html( $subtitle ); ?></itunes:subtitle>
	<itunes:author><?php echo esc_html( $author ); ?></itunes:author>
	<googleplay:author><?php echo esc_html( $author ); ?></googleplay:author>
	<googleplay:email><?php echo esc_html( $owner_email ); ?></googleplay:email>
	<itunes:summary><?php echo esc_html( $podcast_description ); ?></itunes:summary>
	<googleplay:description><?php echo esc_html( $podcast_description ); ?></googleplay:description>
	<itunes:owner>
		<itunes:name><?php echo esc_html( $owner_name ); ?></itunes:name>
		<itunes:email><?php echo esc_html( $owner_email ); ?></itunes:email>
	</itunes:owner>
	<itunes:explicit><?php echo esc_html( $itunes_explicit ); ?></itunes:explicit>
	<googleplay:explicit><?php echo esc_html( $googleplay_explicit ); ?></googleplay:explicit>
	<?php if( $complete ) { ?><itunes:complete><?php echo esc_html( $complete ); ?></itunes:complete><?php }
	if ( $image ) {
	?><itunes:image href="<?php echo esc_url( $image ); ?>"></itunes:image>
	<googleplay:image href="<?php echo esc_url( $image ); ?>"></googleplay:image>
	<image>
		<url><?php echo esc_url( $image ); ?></url>
		<title><?php echo esc_html( $title ); ?></title>
		<link><?php echo esc_url( apply_filters( 'ssp_feed_channel_link_tag', $ss_podcasting->home_url, $podcast_series ) ) ?></link>
	</image>
	<?php } ?>
	<?php if ( isset( $category1['category'] ) && $category1['category'] ) { ?>
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
	<?php }

	// Prevent WP core from outputting an <image> element
	remove_action( 'rss2_head', 'rss2_site_icon' );

	// Add RSS2 headers
	do_action( 'rss2_head' );

	// Get post IDs of all podcast episodes
	$num_posts = intval( apply_filters( 'ssp_feed_number_of_posts', get_option( 'posts_per_rss', 10 ) ) );

	$args = ssp_episodes( $num_posts, $podcast_series, true, 'feed' );

	$qry = new WP_Query( $args );

	if ( $qry->have_posts() ) {
		while ( $qry->have_posts()) {
			$qry->the_post();

			// Get the episode type (audio or video)
			$episode_type = $ss_podcasting->get_episode_type( get_the_ID() );

			// Audio file
			$audio_file = $ss_podcasting->get_enclosure( get_the_ID() );
			if ( get_option( 'permalink_structure' ) ) {
				$enclosure = $ss_podcasting->get_episode_download_link( get_the_ID() );
			} else {
				$enclosure = $audio_file;
			}

			// If there is no enclosure then go no further
			if ( ! isset( $enclosure ) || ! $enclosure ) {
				continue;
			}

			// Get episode image from post featured image
			$episode_image = '';
			$image_id = get_post_thumbnail_id( get_the_ID() );
			if ( $image_id ) {
				$image_att = wp_get_attachment_image_src( $image_id, 'full' );
				if ( $image_att ) {
					$episode_image = $image_att[0];
				}
			}

			// Episode duration (default to 0:00 to ensure there is always a value for this)
			$duration = get_post_meta( get_the_ID(), 'duration', true );
			if ( ! $duration ) {
				$duration = '0:00';
			}

			// File size
			$size = get_post_meta( get_the_ID(), 'filesize_raw', true );
			if ( ! $size ) {
				$size = 1;
			}

			// File MIME type (default to MP3/MP4 to ensure there is always a value for this)
			$mime_type = $ss_podcasting->get_attachment_mimetype( $audio_file );
			if ( ! $mime_type ) {
				switch( $episode_type ) {
					case 'audio': $mime_type = 'audio/mpeg'; break;
					case 'video': $mime_type = 'video/mp4'; break;
				}
			}

			// Episode explicit flag
			$ep_explicit = get_post_meta( get_the_ID(), 'explicit', true );
			if ( $ep_explicit && $ep_explicit == 'on' ) {
				$itunes_explicit_flag = 'yes';
				$googleplay_explicit_flag = 'Yes';
			} else {
				$itunes_explicit_flag = 'clean';
				$googleplay_explicit_flag = 'No';
			}

			// Episode block flag
			$ep_block = get_post_meta( get_the_ID(), 'block', true );
			if ( $ep_block && $ep_block == 'on' ) {
				$block_flag = 'yes';
			} else {
				$block_flag = 'no';
			}

			// Episode author
			$author = esc_html( get_the_author() );

			// Episode content (with iframes removed)
			$content = get_the_content_feed( 'rss2' );
			$content = preg_replace( '/<\/?iframe(.|\s)*?>/', '', $content );
			$content = apply_filters( 'ssp_feed_item_content', $content, get_the_ID() );

			// iTunes summary is the full episode content, but must be shorter than 4000 characters
			$itunes_summary = mb_substr( $content, 0, 3999 );
			$itunes_summary = apply_filters( 'ssp_feed_item_itunes_summary', $itunes_summary, get_the_ID() );
			$gp_description = apply_filters( 'ssp_feed_item_gp_description', $itunes_summary, get_the_ID() );

			// Episode description
			ob_start();
			the_excerpt_rss();
			$description = ob_get_clean();
			$description = apply_filters( 'ssp_feed_item_description', $description, get_the_ID() );

			// iTunes subtitle does not allow any HTML and must be shorter than 255 characters
			$itunes_subtitle = strip_tags( strip_shortcodes( $description ) );
			$itunes_subtitle = str_replace( array( '>', '<', '\'', '"', '`', '[andhellip;]', '[&hellip;]', '[&#8230;]' ), array( '', '', '', '', '', '', '', '' ), $itunes_subtitle );
			$itunes_subtitle = mb_substr( $itunes_subtitle, 0, 254 );
			$itunes_subtitle = apply_filters( 'ssp_feed_item_itunes_subtitle', $itunes_subtitle, get_the_ID() );

	?>
	<item>
		<title><?php esc_html( the_title_rss() ); ?></title>
		<link><?php esc_url( the_permalink_rss() ); ?></link>
		<pubDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ) ); ?></pubDate>
		<dc:creator><?php echo $author; ?></dc:creator>
		<guid isPermaLink="false"><?php esc_html( the_guid() ); ?></guid>
		<description><![CDATA[<?php echo $description; ?>]]></description>
		<itunes:subtitle><![CDATA[<?php echo $itunes_subtitle; ?>]]></itunes:subtitle>
		<content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded>
		<itunes:summary><![CDATA[<?php echo $itunes_summary; ?>]]></itunes:summary>
		<googleplay:description><![CDATA[<?php echo $gp_description; ?>]]></googleplay:description><?php if ( $episode_image ) { ?>
		<itunes:image href="<?php echo esc_url( $episode_image ); ?>"></itunes:image>
		<googleplay:image href="<?php echo esc_url( $episode_image ); ?>"></googleplay:image>
		<image href="<?php echo esc_url( $episode_image ); ?>"></image><?php } ?>
		<enclosure url="<?php echo esc_url( $enclosure ); ?>" length="<?php echo esc_attr( $size ); ?>" type="<?php echo esc_attr( $mime_type ); ?>"></enclosure>
		<itunes:explicit><?php echo esc_html( $itunes_explicit_flag ); ?></itunes:explicit>
		<googleplay:explicit><?php echo esc_html( $googleplay_explicit_flag ); ?></googleplay:explicit>
		<itunes:block><?php echo esc_html( $block_flag ); ?></itunes:block>
		<googleplay:block><?php echo esc_html( $block_flag ); ?></googleplay:block>
		<itunes:duration><?php echo esc_html( $duration ); ?></itunes:duration>
		<itunes:author><?php echo $author; ?></itunes:author>
	</item><?php }
	} ?>
</channel>
</rss><?php exit; ?>
