<?php
/**
 * Podcast RSS feed template
 *
 * @package WordPress
 */

global $ss_podcasting;

error_reporting(0);

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

header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
	<?php do_action('rss2_ns'); ?>
>

<channel>
	<title><?php echo htmlspecialchars( $title ); ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php echo htmlspecialchars( $description ); ?></description>
	<lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
	<language><?php echo htmlspecialchars( $language ); ?></language>
	<copyright><?php echo htmlspecialchars( $copyright ); ?></copyright>
	<itunes:subtitle><?php echo htmlspecialchars( $subtitle ); ?></itunes:subtitle>
	<itunes:author><?php echo htmlspecialchars( $author ); ?></itunes:author>
	<itunes:summary><?php echo htmlspecialchars( $description ); ?></itunes:summary>
	<itunes:owner>
		<itunes:name><?php echo htmlspecialchars( $owner_name ); ?></itunes:name>
		<itunes:email><?php echo htmlspecialchars( $owner_email ); ?></itunes:email>
	</itunes:owner>
	<itunes:explicit><?php echo htmlspecialchars( $explicit ); ?></itunes:explicit>
	<?php if( $image ) { ?>
	<itunes:image href="<?php echo $image; ?>"></itunes:image>
	<?php } ?>
	<?php if( $category ) { ?>
	<itunes:category text="<?php echo htmlspecialchars( $category ); ?>">
		<?php if( $subcategory ) { ?>
		<itunes:category text="<?php echo htmlspecialchars( $subcategory ); ?>"></itunes:category>
		<?php } ?>
	</itunes:category>
	<?php } ?>
	<?php if( isset( $new_feed_url ) && strlen( $new_feed_url ) > 0 && $new_feed_url != '' ) { ?>
	<itunes:new-feed-url><?php echo $new_feed_url; ?></itunes:new-feed-url>
	<?php }

	// Fetch podcast episodes
	$args = array(
		'post_type' => 'podcast',
		'post_status' => 'publish'
	);
	if( isset( $_GET['series'] ) && strlen( $_GET['series'] ) > 0 ) {
		$args['series'] = $_GET['series'];
	}
	$qry = new WP_Query( $args );
	
	if( $qry->have_posts() ) :
		while( $qry->have_posts()) : $qry->the_post();

		// Enclosure (audio file)
		$enclosure = get_post_meta( get_the_ID() , 'enclosure' , true );
		
		// Episode duration
		$duration = get_post_meta( get_the_ID() , 'duration' , true );
		
		// File size
		$size = get_post_meta( get_the_ID() , 'filesize_raw' , true );
		if( ! $size || strlen( $size ) == 0 || $size == '' ) {
			$size = $ss_podcasting->get_file_size( $enclosure );
			$size = $size['raw'];
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

		// Episode series name
		$series_list = wp_get_post_terms( get_the_ID() , 'series' );
		$series = false;
		if( $series_list && count( $series_list ) > 0 ) {
			foreach( $series_list as $s ) {
				$series = $s->name;
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
					$keywords = $k->name;
					++$c;
				} else {
					$keywords .= ', ' . $k->name;
				}
			}
		}

		// Episode author
		$author = htmlspecialchars( get_the_author() );

		// Episode content
		$content = strip_tags( get_the_content_feed( 'rss2' ) );

	?>
	<item>
		<title><?php the_title_rss(); ?></title>
		<link><?php the_permalink_rss(); ?></link>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
		<dc:creator><?php echo $author; ?></dc:creator>
		<guid isPermaLink="false"><?php the_guid(); ?></guid>
		<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
		<?php if ( strlen( $content ) > 0 ) : ?>
		<content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded>
		<itunes:summary><![CDATA[<?php echo $content; ?>]]></itunes:summary><?php else : ?>
		<content:encoded><![CDATA[<?php the_excerpt_rss(); ?>]]></content:encoded>
		<itunes:summary><![CDATA[<?php the_excerpt_rss(); ?>]]></itunes:summary><?php endif; ?>
		<enclosure url="<?php echo $enclosure; ?>" length="<?php echo htmlspecialchars( $size ); ?>" type="<?php echo htmlspecialchars( $mime_type ); ?>"></enclosure>
		<itunes:explicit><?php echo htmlspecialchars( $explicit_flag ); ?></itunes:explicit>
		<itunes:duration><?php echo htmlspecialchars( $duration ); ?></itunes:duration>
		<itunes:author><?php echo $author; ?></itunes:author><?php if( $keywords ) { ?>
		<itunes:keywords><?php echo htmlspecialchars( $keywords ); ?></itunes:keywords><?php } ?>
	</item><?php endwhile; endif; ?>
</channel>
</rss>