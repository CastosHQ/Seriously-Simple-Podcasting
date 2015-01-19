<?php
/**
 * Podcast RSS feed template
 *
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 */

global $ss_podcasting;

// Hide all errors
error_reporting( 0 );

// Check if feed is password protected
$protection = get_option( 'ss_podcasting_protect_feed' );

if( $protection && $protection == 'on' ) {

	$give_access = false;

	$message_option = get_option('ss_podcasting_protection_no_access_message');
	$message = __( 'You are not permitted to view this podcast feed.' , 'ss-podcasting' );
	if( $message_option && strlen( $message_option ) > 0 && $message_option != '' ) {
		$message = $message_option;
	}

	// Request password and give access if correct
	if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) && ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
	    $give_access = false;
	} else {
		$username = get_option('ss_podcasting_protection_username');
		$password = get_option('ss_podcasting_protection_password');

		if( $_SERVER['PHP_AUTH_USER'] == $username ) {
			if( md5( $_SERVER['PHP_AUTH_PW'] ) == $password ) {
				$give_access = true;
			}
		}
	}

	// Send 401 status and display no access message
	if( ! $give_access ) {

		$no_access_message = '<div style="text-align:center;font-family:sans-serif;border:1px solid red;background:pink;padding:20px 0;color:red;">' . $message . '</div>';

		header('WWW-Authenticate: Basic realm="Podcast Feed"');
	    header('HTTP/1.0 401 Unauthorized');

		die( $no_access_message );
	}
}

// If redirect is on, get new feed URL and redirect if setting was changed more than 48 hours ago
$redirect = get_option( 'ss_podcasting_redirect_feed' );
$new_feed_url = false;
if( $redirect && $redirect == 'on' ) {

	$new_feed_url = get_option( 'ss_podcasting_new_feed_url' );
	$update_date = get_option( 'ss_podcasting_redirect_feed_date' );

	if( $new_feed_url && $update_date ) {
		$redirect_date = strtotime( '+2 days' , $update_date );
		$current_date = time();

		// Redirect with 301 if it is more than 2 days since redirect was saved
		if( $current_date > $redirect_date ) {
			header ( 'HTTP/1.1 301 Moved Permanently' );
			header ( 'Location: ' . $new_feed_url );
			exit;
		}
	}
}

// Get podcast data
$title = get_option( 'ss_podcasting_data_title' );
if( ! $title ) {
	$title = get_bloginfo( 'name' );
}

$description = get_option( 'ss_podcasting_data_description' );
if( ! $description ) {
	$description = get_bloginfo( 'description' );
}
$itunes_description = strip_tags( $description );

$language = get_option( 'ss_podcasting_data_language' );
if( ! $language ) {
	$language = get_bloginfo( 'language' );
}

$copyright = get_option( 'ss_podcasting_data_copyright' );
if( ! $copyright ) {
	$copyright = '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' );
}

$subtitle = get_option( 'ss_podcasting_data_subtitle' );
if( ! $subtitle ) {
	$subtitle = get_bloginfo( 'description' );
}

$author = get_option( 'ss_podcasting_data_author' );
if( ! $author ) {
	$author = get_bloginfo( 'name' );
}

$owner_name = get_option( 'ss_podcasting_data_owner_name' );
if( ! $owner_name ) {
	$owner_name = get_bloginfo( 'name' );
}

$owner_email = get_option( 'ss_podcasting_data_owner_email' );
if( ! $owner_email ) {
	$owner_email = get_bloginfo( 'admin_email' );
}

$explicit = get_option( 'ss_podcasting_data_explicit' );
if( $explicit && $explicit == 'on' ) {
	$explicit = 'Yes';
} else {
	$explicit = 'No';
}

$image = get_option( 'ss_podcasting_data_image' );
if( ! $image ) {
	$image = false;
}

$category = get_option( 'ss_podcasting_data_category' );
if( ! $category ) {
	$category = false;
	$subcategory = false;
} else {
	$subcategory = get_option( 'ss_podcasting_data_subcategory' );
	if( ! $subcategory ) {
		$subcategory = false;
	}
}

// Set RSS header
header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );

// Echo first line to prevent any extra characters at start of document
echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?'.'>'; ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
	<?php do_action( 'rss2_ns' ); ?>
>

<channel>
	<title><?php echo esc_html( $title ); ?></title>
	<atom:link href="<?php esc_url( self_link() ); ?>" rel="self" type="application/rss+xml" />
	<link><?php esc_url( bloginfo_rss('url') ) ?></link>
	<description><?php echo esc_html( $description ); ?></description>
	<lastBuildDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ); ?></lastBuildDate>
	<language><?php echo esc_html( $language ); ?></language>
	<copyright><?php echo esc_html( $copyright ); ?></copyright>
	<itunes:subtitle><?php echo esc_html( $subtitle ); ?></itunes:subtitle>
	<itunes:author><?php echo esc_html( $author ); ?></itunes:author>
	<itunes:summary><?php echo esc_html( $itunes_description ); ?></itunes:summary>
	<itunes:owner>
		<itunes:name><?php echo esc_html( $owner_name ); ?></itunes:name>
		<itunes:email><?php echo esc_html( $owner_email ); ?></itunes:email>
	</itunes:owner>
	<itunes:explicit><?php echo esc_html( $explicit ); ?></itunes:explicit>
	<?php if( $image ) { ?>
	<itunes:image href="<?php echo esc_url( $image ); ?>"></itunes:image>
	<?php } ?>
	<?php if( $category ) { ?>
	<itunes:category text="<?php echo esc_attr( $category ); ?>">
		<?php if( $subcategory ) { ?>
		<itunes:category text="<?php echo esc_attr( $subcategory ); ?>"></itunes:category>
		<?php } ?>
	</itunes:category>
	<?php } ?>
	<?php if( $new_feed_url ) { ?>
	<itunes:new-feed-url><?php echo esc_url( $new_feed_url ); ?></itunes:new-feed-url>
	<?php }

	// Get post IDs of all podcast episodes
	$num_posts = intval( apply_filters( 'ssp_feed_number_of_posts', get_option( 'posts_per_rss', 10 ) ) );

	$podcast_series = '';
	if( isset( $_GET['podcast_series'] ) && strlen( $_GET['podcast_series'] ) > 0 ) {
		$podcast_series = $_GET['podcast_series'];
	}

	$args = ssp_episodes( $num_posts, $podcast_series, true, 'feed' );

	$qry = new WP_Query( $args );

	if( $qry->have_posts() ) {
		while( $qry->have_posts()) {
			$qry->the_post();

			// Audio file
			$enclosure = $ss_podcasting->get_enclosure( get_the_ID() );

			// If there is no enclosure then go no further
			if( ! isset( $enclosure ) || ! $enclosure ) {
				continue;
			}

			// Get episode image from post featured image
			$episode_image = '';
			$image_id = get_post_thumbnail_id( get_the_ID() );
			if( $image_id ) {
				$image_att = wp_get_attachment_image_src( $image_id, 'full' );
				if( $image_att ) {
					$episode_image = $image_att[0];
				}
			}

			// Episode duration
			$duration = get_post_meta( get_the_ID() , 'duration' , true );
			if( ! $duration ) {
				$duration = '0:00';
			}

			// File size
			$size = get_post_meta( get_the_ID() , 'filesize_raw' , true );
			if( ! $size ) {
				$size = $ss_podcasting->get_file_size( $enclosure );
				$size = esc_html( $size['raw'] );

				update_post_meta( get_the_ID(), 'filesize', $size['formatted'] );
	 			update_post_meta( get_the_ID(), 'filesize_raw', $size['raw'] );
			}

			// Set default size to prevent invalid feed
			if( ! $size ) {
				$size = 1;
			}

			// File MIME type (default to MP3 to ensure that there is always a value for this)
			$mime_type = $ss_podcasting->get_attachment_mimetype( $enclosure );
			if( ! $mime_type ) {
				$mime_type = 'audio/mpeg';
			}

			// Episode explicit flag
			$ep_explicit = get_post_meta( get_the_ID(), 'explicit', true );
			if( $ep_explicit && $ep_explicit == 'on' ) {
				$explicit_flag = 'Yes';
			} else {
				$explicit_flag = 'No';
			}

			// Episode block flag
			$ep_block = get_post_meta( get_the_ID(), 'block', true );
			if( $ep_block && $ep_block == 'on' ) {
				$block_flag = 'Yes';
			} else {
				$block_flag = 'No';
			}

			// Episode author
			$author = esc_html( get_the_author() );

			// Episode content
			$content = get_the_content_feed( 'rss2' );

			// iTunes summary does not allow any HTML and must be shorter than 4000 characters
			$itunes_summary = strip_tags( get_the_content() );
			$itunes_summary = str_replace( array( '&', '>', '<', '\'', '"', '`' ), array( 'and', '', '', '', '', '' ), $itunes_summary );
			$itunes_summary = substr( $itunes_summary, 0, 3950 );

			// iTunes short description does not allow any HTML and must be shorter than 4000 characters
			$itunes_excerpt = strip_tags( get_the_excerpt() );
			$itunes_excerpt = str_replace( array( '&', '>', '<', '\'', '"', '`', '[andhellip;]', '[&hellip;]' ), array( 'and', '', '', '', '', '', '', '' ), $itunes_excerpt );
			$itunes_excerpt = substr( $itunes_excerpt, 0, 3800 );

	?>
	<item>
		<title><?php esc_html( the_title_rss() ); ?></title>
		<link><?php esc_url( the_permalink_rss() ); ?></link>
		<pubDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ) ); ?></pubDate>
		<dc:creator><?php echo $author; ?></dc:creator>
		<guid isPermaLink="false"><?php esc_html( the_guid() ); ?></guid>
		<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
		<itunes:subtitle><?php echo $itunes_excerpt; ?></itunes:subtitle>
		<content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded>
		<itunes:summary><?php echo $itunes_summary; ?></itunes:summary><?php if( $episode_image ) { ?>
		<itunes:image href="<?php echo esc_url( $episode_image ); ?>"></itunes:image><?php } ?>
		<enclosure url="<?php echo esc_url( $enclosure ); ?>" length="<?php echo esc_attr( $size ); ?>" type="<?php echo esc_attr( $mime_type ); ?>"></enclosure>
		<itunes:explicit><?php echo esc_html( $explicit_flag ); ?></itunes:explicit>
		<itunes:block><?php echo esc_html( $block_flag ); ?></itunes:block>
		<itunes:duration><?php echo esc_html( $duration ); ?></itunes:duration>
		<itunes:author><?php echo $author; ?></itunes:author>
	</item><?php }
	} ?>
</channel>
</rss><?php exit; ?>