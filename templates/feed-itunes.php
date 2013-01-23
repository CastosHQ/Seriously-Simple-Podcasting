<?php
/**
 * Podcast iTunes feed template
 *
 * @package WordPress
 */

global $ss_podcasting;

error_reporting(0);

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

$author = get_option( 'ss_podcasting_data_subtitle' );
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
	<itunes:explicit><?php echo htmlspecialchars( $explicit ); ?></itunes:explicit><?php if( $image ) { ?>
	<itunes:image href="<?php echo $image; ?>" /><?php } ?><?php if( $category ) { ?>
	<itunes:category text="<?php echo htmlspecialchars( $category ); ?>"><?php if( $subcategory ) { ?>
		<itunes:category text="<?php echo htmlspecialchars( $subcategory ); ?>" /><?php } ?>
	</itunes:category><?php }

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

		//Enclosure
		$enclosure = get_post_meta( get_the_ID() , 'enclosure' , true );
		
		// Episode duration
		$duration = get_post_meta( get_the_ID() , 'duration' , true );
		$length = $ss_podcasting->format_duration( $duration );

		//File MIME type
		$mime_type = $ss_podcasting->get_file_mimetype( $enclosure );

		// Explicit flag
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
	?>
	<item>
		<title><?php the_title_rss(); ?></title>
		<link><?php the_permalink_rss(); ?></link>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
		<guid isPermaLink="false"><?php the_guid(); ?></guid>
		<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description><?php $content = get_the_content_feed('rss2');
		if ( strlen( $content ) > 0 ) : ?>
		<content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded><?php else : ?>
		<content:encoded><![CDATA[<?php the_excerpt_rss(); ?>]]></content:encoded><?php endif; ?>
		<enclosure url="<?php echo $enclosure; ?>" length="<?php echo htmlspecialchars( $length ); ?>" type="<?php echo htmlspecialchars( $mime_type ); ?>"/>
		<itunes:explicit><?php echo htmlspecialchars( $explicit_flag ); ?></itunes:explicit>
		<itunes:duration><?php echo htmlspecialchars( $duration ); ?></itunes:duration>
		<itunes:author><?php htmlspecialchars( the_author() ); ?></itunes:author>
		<itunes:summary><![CDATA[<?php the_excerpt_rss(); ?>]]></itunes:summary><?php if( $keywords ) { ?>
		<itunes:keywords><?php echo htmlspecialchars( $keywords ); ?></itunes:keywords><?php } ?>
	</item><?php endwhile; endif; ?>
</channel>
</rss>