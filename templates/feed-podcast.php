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
$protection = get_option('ss_podcasting_protect_feed');

if( $protection && $protection == 'on' ) {

	$give_access = false;

	$message_option = get_option('ss_podcasting_protection_no_access_message');
	$message = __( 'You are not permitted to view this podcast feed.' , 'ss-podcasting' );
	if( $message_option && strlen( $message_option ) > 0 && $message_option != '' ) {
		$message = $message_option;
	}

	$no_access_message = '<div style="text-align:center;font-family:sans-serif;border:1px solid red;background:pink;padding:20px 0;color:red;">' . $message . '</div>';

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

	if( ! $give_access ) {
		header('WWW-Authenticate: Basic realm="Podcast Feed"');
	    header('HTTP/1.0 401 Unauthorized');
		die( $no_access_message );
	}
}

// Action hook for plugins/themes to intercept template
// Any add_action( 'do_feed_podcast' ) calls must be made before the 'template_redirect' hook
// If you are still going to load this template after using this hook then you must not output any data
do_action( 'do_feed_podcast' );

// If redirect is on, get new feed URL and redirect if setting was changed more than 48 hours ago
$redirect = get_option( 'ss_podcasting_redirect_feed' );
if( $redirect && $redirect == 'on' ) {

	$new_feed_url = get_option( 'ss_podcasting_new_feed_url' );
	$update_date = get_option( 'ss_podcasting_redirect_feed_date' );

	if( ( $update_date && strlen( $update_date ) > 0 && $update_date != '' ) && ( $new_feed_url && strlen( $new_feed_url ) > 0 && $new_feed_url != '' ) ) {
		$redirect_date = strtotime( '+2 days' , $update_date );
		$current_date = time();

		if( $current_date > $redirect_date ) {
			header ( 'HTTP/1.1 301 Moved Permanently' );
			header ( 'Location: ' . $new_feed_url );
			exit;
		}
	}
}

// Get podcast data
$title = get_option( 'ss_podcasting_data_title' );
if( ! $title || strlen( $title ) == 0 || $title == '' ) {
	$title = get_bloginfo( 'name' );
}

$description = get_option( 'ss_podcasting_data_description' );
if( ! $description || strlen( $description ) == 0 || $description == '' ) {
	$description = get_bloginfo( 'description' );
}
$itunes_description = strip_tags( $description );

$language = get_option( 'ss_podcasting_data_language' );
if( ! $language || strlen( $language ) == 0 || $language == '' ) {
	$language = get_bloginfo( 'language' );
}

$copyright = get_option( 'ss_podcasting_data_copyright' );
if( ! $copyright || strlen( $copyright ) == 0 || $copyright == '' ) {
	$copyright = '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' );
}

$subtitle = get_option( 'ss_podcasting_data_subtitle' );
if( ! $subtitle || strlen( $subtitle ) == 0 || $subtitle == '' ) {
	$subtitle = get_bloginfo( 'description' );
}

$author = get_option( 'ss_podcasting_data_author' );
if( ! $author || strlen( $author ) == 0 || $author == '' ) {
	$author = get_bloginfo( 'name' );
}

$owner_name = get_option( 'ss_podcasting_data_owner_name' );
if( ! $owner_name || strlen( $owner_name ) == 0 || $owner_name == '' ) {
	$owner_name = get_bloginfo( 'name' );
}

$owner_email = get_option( 'ss_podcasting_data_owner_email' );
if( ! $owner_email || strlen( $owner_email ) == 0 || $owner_email == '' ) {
	$owner_email = get_bloginfo( 'admin_email' );
}

$explicit = get_option( 'ss_podcasting_data_explicit' );
if( $explicit && $explicit == 'on' ) {
	$explicit = 'Yes';
} else {
	$explicit = 'No';
}

$image = get_option( 'ss_podcasting_data_image' );
if( ! $image || strlen( $image ) == 0 || $image == '' ) {
	$image = false;
}

$category = get_option( 'ss_podcasting_data_category' );
if( ! $category || strlen( $category ) == 0 || $category == '' ) {
	$category = false;
} else {
	$subcategory = get_option( 'ss_podcasting_data_subcategory' );
	if( ! $subcategory || strlen( $subcategory ) == 0 || $subcategory == '' ) {
		$subcategory = false;
	}
}

header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );

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
	<?php if( isset( $new_feed_url ) && strlen( $new_feed_url ) > 0 && $new_feed_url != '' ) { ?>
	<itunes:new-feed-url><?php echo esc_url( $new_feed_url ); ?></itunes:new-feed-url>
	<?php }

	// Fetch podcast episodes
	$args = array(
		'post_type' => 'podcast',
		'post_status' => 'publish',
		'posts_per_page' => -1
	);
	if( isset( $_GET['podcast_series'] ) && strlen( $_GET['podcast_series'] ) > 0 ) {
		$args['series'] = esc_attr( $_GET['podcast_series'] );
	}
	$qry = new WP_Query( $args );

	if( $qry->have_posts() ) :
		while( $qry->have_posts()) : $qry->the_post();

		// Enclosure (audio file)
		$enclosure = $ss_podcasting->get_enclosure( get_the_ID() );

		// Episode duration
		$duration = get_post_meta( get_the_ID() , 'duration' , true );
		if( ! $duration || strlen( $duration ) == 0 || $duration == '' ) {
			$duration = '0:00';
		}

		// File size
		$size = get_post_meta( get_the_ID() , 'filesize_raw' , true );
		if( ! $size || strlen( $size ) == 0 || $size == '' ) {
			$size = $ss_podcasting->get_file_size( $enclosure );
			$size = esc_html( $size['raw'] );
		}

		if( ! $size || strlen( $size ) == 0 || $size == '' ) {
			$size = 1;
		}

		// File MIME type (default to MP3 to ensure that there is always a value for this)
		$mime_type = $ss_podcasting->get_attachment_mimetype( $enclosure );
		if( ! $mime_type || strlen( $mime_type ) == 0 || $mime_type == '' ) {
			$mime_type = 'audio/mpeg';
		}

		// Episode explicit flag
		$ep_explicit = get_post_meta( get_the_ID() , 'explicit' , true );
		if( $ep_explicit && $ep_explicit == 'on' ) {
			$explicit_flag = 'Yes';
		} else {
			$explicit_flag = 'No';
		}

		// Episode block flag
		$ep_block = get_post_meta( get_the_ID() , 'block' , true );
		if( $ep_block && $ep_block == 'on' ) {
			$block_flag = 'Yes';
		} else {
			$block_flag = 'No';
		}

		// Episode series name
		$series_list = wp_get_post_terms( get_the_ID() , 'series' );
		$series = false;
		if( $series_list && count( $series_list ) > 0 ) {
			foreach( $series_list as $s ) {
				$series = esc_html( $s->name );
				break;
			}
		}

		// Episode keywords
		$keyword_list = wp_get_post_terms( get_the_ID() , 'keywords' );
		$keywords = false;
		if( $keyword_list && count( $keyword_list ) > 0 ) {
			$c = 0;
			foreach( $keyword_list as $k ) {
				if( $c == 0 ) {
					$keywords = esc_html( $k->name );
					++$c;
				} else {
					$keywords .= ', ' . esc_html( $k->name );
				}
			}
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
		$itunes_excerpt = substr( $itunes_excerpt, 0, 3950 );

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
		<itunes:summary><?php echo $itunes_summary; ?></itunes:summary>
		<enclosure url="<?php echo esc_url( $enclosure ); ?>" length="<?php echo esc_attr( $size ); ?>" type="<?php echo esc_attr( $mime_type ); ?>"></enclosure>
		<itunes:explicit><?php echo esc_html( $explicit_flag ); ?></itunes:explicit>
		<itunes:block><?php echo esc_html( $block_flag ); ?></itunes:block>
		<itunes:duration><?php echo esc_html( $duration ); ?></itunes:duration>
		<itunes:author><?php echo $author; ?></itunes:author><?php if( $keywords ) { ?>
		<itunes:keywords><?php echo esc_html( $keywords ); ?></itunes:keywords><?php } ?>
	</item><?php endwhile; endif; ?>
</channel>
</rss><?php exit; ?>