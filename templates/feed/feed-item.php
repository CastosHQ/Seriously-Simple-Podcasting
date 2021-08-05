<?php
/**
 * Refactoring history:
 * 1st stage - created this file to move some code from the feed-podcast.php
 * @todo: 2nd stage: move the data preparation somewhere else, leave only xml part here
 *
 * @var $stylesheet_url
 * @var $title
 * @var $ss_podcasting
 * @var $podcast_series
 * @var $description
 * @var $language
 * @var $copyright
 * @var $subtitle
 * @var $author
 * @var $itunes_type
 * @var $podcast_description
 * @var $owner_name
 * @var $owner_email
 * @var $itunes_explicit
 * @var $complete
 * @var $image
 * @var $new_feed_url
 * @var $turbo
 * @var $googleplay_explicit
 * @var $exclude_series
 * @var $episode_description_uses_excerpt
 * @var WP_Query $qry
 */

use SeriouslySimplePodcasting\Controllers\Frontend_Controller;

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
	return;
}

// Get episode image from post featured image
/** @var Frontend_Controller  $ss_podcasting */
global $ss_podcasting;
$episode_image = $ss_podcasting->get_episode_image_url( get_the_ID() );
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
$author = apply_filters( 'ssp_feed_item_author', $author, get_the_ID() );

// Cache the post in case it changes
$post_id = get_the_ID();


// Description is set based on feed setting
if ( $episode_description_uses_excerpt ) {
	ob_start();
	the_excerpt_rss();
	$description = ob_get_clean();
} else {
	$description = ssp_get_the_feed_item_content();
	if ( isset( $turbo_post_count ) && $turbo_post_count > 10 ) {
		// If turbo is on, limit the full html description to 4000 chars
		$description = mb_substr( $description, 0, 3999 );
	}
}

$description = apply_filters( 'ssp_feed_item_description', $description, get_the_ID() );

// Clean up after shortcodes in content and excerpts
if ( $post_id !== get_the_ID() ) {
	$qry->reset_postdata();
}

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

$title = esc_html( get_the_title_rss() );
?>
<item>
	<title><?php echo $title; ?></title>
	<link><?php the_permalink_rss(); ?></link>
	<pubDate><?php echo $pub_date; ?></pubDate>
	<dc:creator><![CDATA[<?php echo $author; ?>]]></dc:creator>
	<guid isPermaLink="false"><?php the_guid(); ?></guid>
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
		<content:encoded><![CDATA[<?php echo $description; ?>]]></content:encoded>
	<?php } ?>
	<enclosure url="<?php echo esc_url( $enclosure ); ?>" length="<?php echo esc_attr( $size ); ?>"
			   type="<?php echo esc_attr( $mime_type ); ?>"></enclosure>
	<?php if ( ! isset( $turbo_post_count ) || $turbo_post_count <= 10 ) { ?>
		<itunes:summary><![CDATA[<?php echo $itunes_summary; ?>]]></itunes:summary>
	<?php } ?>
	<?php if ( $episode_image ) { ?>
		<itunes:image href="<?php echo esc_url( $episode_image ); ?>"></itunes:image>
		<image>
			<url><?php echo esc_url( $episode_image ); ?></url>
			<title><?php echo esc_attr( $title ); ?></title>
		</image>
	<?php } ?>
	<itunes:explicit><?php echo esc_html( $itunes_explicit_flag ); ?></itunes:explicit>
	<itunes:block><?php echo esc_html( $block_flag ); ?></itunes:block>
	<itunes:duration><?php echo esc_html( $duration ); ?></itunes:duration>
	<itunes:author><![CDATA[<?php echo $author; ?>]]></itunes:author>
	<?php if ( 'off' === $turbo ) { ?>
		<googleplay:description><![CDATA[<?php echo $gp_description; ?>]]></googleplay:description>
		<?php if ( $episode_image ) { ?>
			<googleplay:image href="<?php echo esc_url( $episode_image ); ?>"></googleplay:image>
		<?php } ?>
		<googleplay:explicit><?php echo esc_html( $googleplay_explicit_flag ); ?></googleplay:explicit>
		<googleplay:block><?php echo esc_html( $block_flag ); ?></googleplay:block>
	<?php } ?>
</item>
