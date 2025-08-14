<?php
/**
 * @see \SeriouslySimplePodcasting\Controllers\Feed_Controller::fetch_feed_item()
 *
 * @var string $title
 * @var string $pub_date
 * @var string $author
 * @var string $description
 * @var string $itunes_subtitle
 * @var string $itunes_episode_type
 * @var string $itunes_title
 * @var string $itunes_episode_number
 * @var string $itunes_season_number
 * @var string $enclosure
 * @var string $size
 * @var string $mime_type
 * @var string $turbo_post_count
 * @var string $itunes_summary
 * @var string $episode_image
 * @var string $itunes_explicit_flag
 * @var string $block_flag
 * @var string $duration
 * @var string $gp_description
 * @var string $googleplay_explicit_flag
 * @var string $transcript_url Added with SSTranscript plugin
 * @var string $transcript_mime Added with SSTranscript plugin
 */
?>

<item>
	<title><?php echo $title; ?></title>
	<link><?php the_permalink_rss(); ?></link>
	<pubDate><?php echo $pub_date; ?></pubDate>
	<dc:creator><![CDATA[<?php echo $author; ?>]]></dc:creator>
	<guid isPermaLink="false"><?php the_guid(); ?></guid>
	<description><![CDATA[<?php echo $description; ?>]]></description>
	<itunes:subtitle><![CDATA[<?php echo $itunes_subtitle; ?>]]></itunes:subtitle>
<?php

if ( $itunes_episode_type ) : ?>
	<itunes:episodeType><?php echo $itunes_episode_type; ?></itunes:episodeType>
<?php endif;

if ( $itunes_title ): ?>
	<itunes:title><![CDATA[<?php echo $itunes_title; ?>]]></itunes:title>
<?php endif;

if ( $itunes_episode_number ): ?>
	<itunes:episode><?php echo $itunes_episode_number; ?></itunes:episode>
<?php endif;

if ( $itunes_season_number ): ?>
	<itunes:season><?php echo $itunes_season_number; ?></itunes:season>
<?php endif;

if ( ! isset( $turbo_post_count ) || $turbo_post_count <= 10 ) : ?>
	<content:encoded><![CDATA[<?php echo $description; ?>]]></content:encoded>
<?php endif;

?>
	<enclosure url="<?php echo esc_url( $enclosure ); ?>" length="<?php echo esc_attr( $size ); ?>" type="<?php echo esc_attr( $mime_type ); ?>"></enclosure>
<?php

if ( ! isset( $turbo_post_count ) || $turbo_post_count <= 10 ) : ?>
	<itunes:summary><![CDATA[<?php echo $itunes_summary; ?>]]></itunes:summary>
<?php endif;

if ( $episode_image ) : ?>
	<itunes:image href="<?php echo esc_url( $episode_image ); ?>"></itunes:image>
	<image>
		<url><?php echo esc_url( $episode_image ); ?></url>
		<title><?php echo esc_attr( $title ); ?></title>
	</image>
<?php endif;

?>
	<itunes:explicit><?php echo esc_html( $itunes_explicit_flag ); ?></itunes:explicit>
	<itunes:block><?php echo esc_html( $block_flag ); ?></itunes:block>
	<itunes:duration><?php echo esc_html( $duration ); ?></itunes:duration>
	<itunes:author><![CDATA[<?php echo $author; ?>]]></itunes:author><?php

if ( ! $turbo_post_count ) : ?>
	<googleplay:description><![CDATA[<?php echo $gp_description; ?>]]></googleplay:description>
<?php endif;

if ( $episode_image ) : ?>
	<googleplay:image href="<?php echo esc_url( $episode_image ); ?>"></googleplay:image>
<?php endif;

?>
	<googleplay:explicit><?php echo esc_html( $googleplay_explicit_flag ); ?></googleplay:explicit>
	<googleplay:block><?php echo esc_html( $block_flag ); ?></googleplay:block>
<?php

if ( $transcript_url && $transcript_mime ) : ?>
	<podcast:transcript url="<?php echo esc_url( $transcript_url ) ?>" type="<?php echo esc_attr( $transcript_mime ) ?>"/>
<?php endif;

?>
</item>
