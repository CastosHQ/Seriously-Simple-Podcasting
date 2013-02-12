<?php
/**
 * Podcast standard RSS feed template
 *
 * @package WordPress
 */

global $ss_podcasting;

error_reporting(0);

// If redirect is on, get new feed URL and redirect
$redirect = get_option( 'ss_podcasting_redirect_feed' );
if( $redirect && $redirect == 'on' ) {
	$new_feed_url = get_option( 'ss_podcasting_new_feed_url' );
	if( $new_feed_url && strlen( $new_feed_url ) > 0 && $new_feed_url != '' ) {
		header ( 'HTTP/1.1 301 Moved Permanently' );
		header ( 'Location: ' . $new_feed_url );
		exit;
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

$category = get_option( 'ss_podcasting_data_category' );
if( ! $category || strlen( $category ) == 0 || $category == '' ) {
	$category = false;
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
	<?php do_action('rss2_ns'); ?>
>

<channel>
	<title><?php echo $title; ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php echo htmlspecialchars( $description ); ?></description>
	<lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
	<language><?php echo htmlspecialchars( $language ); ?></language>
	<copyright><?php echo htmlspecialchars( $copyright ); ?></copyright>
	<?php
	// Fetch podcast episodes
	$args = array(
		'post_type' => 'podcast',
		'post_status' => 'publish'
	);
	if( isset( $_GET['series'] ) && strlen( $_GET['series'] ) > 0 ) {
		$args['series'] = $_GET['series'];
	}
	$qry = new WP_Query( $args );
	?>
	<?php if( $qry->have_posts() ) : while( $qry->have_posts() ) : $qry->the_post();

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

		// File MIME type (default to MP3 to ensure that the is always a value for this)
		$mime_type = $ss_podcasting->get_attachment_mimetype( $enclosure );
		if( ! $mime_type || strlen( $mime_type ) == 0 || $mime_type == '' ) {
			$mime_type = 'audio/mpeg';
		}

		// Episode author
		$author = htmlspecialchars( get_the_author() );

		// Episode content
		$content = get_the_content_feed( 'rss2' );

	?>
	<item>
		<title><?php the_title_rss() ?></title>
		<link><?php the_permalink_rss() ?></link>
		<?php if( $category ) { ?><category><?php echo htmlspecialchars( $category ); ?></category><?php } ?>
		<pubDate><?php echo mysql2date( 'D, d M Y H:i:s +0000' , get_post_time( 'Y-m-d H:i:s' , true ) , false ); ?></pubDate>
		<dc:creator><?php echo $author; ?></dc:creator>
		<guid isPermaLink="false"><?php the_guid(); ?></guid>
		<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
		<?php if ( strlen( $content ) > 0 ) : ?>
		<content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded><?php else : ?>
		<content:encoded><![CDATA[<?php the_excerpt_rss(); ?>]]></content:encoded><?php endif; ?>
		<enclosure url="<?php echo $enclosure; ?>" length="<?php echo htmlspecialchars( $size ); ?>" type="<?php echo htmlspecialchars( $mime_type ); ?>"></enclosure>
	</item><?php endwhile; endif; ?>
</channel>
</rss>