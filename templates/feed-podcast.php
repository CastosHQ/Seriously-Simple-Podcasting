<?php
/**
 * Podcast RSS feed template
 *
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 *
 * Refactoring history:
 * Moved the data preparation to the feed controller
 * @see \SeriouslySimplePodcasting\Controllers\Feed_Controller::get_podcast_feed()
 *
 * @var $stylesheet_url
 * @var $title
 * @var $podcast_series
 * @var $description
 * @var $language
 * @var $copyright
 * @var $subtitle
 * @var $author
 * @var $itunes_type
 * @var $owner_name
 * @var $owner_email
 * @var $itunes_explicit
 * @var $complete
 * @var $image
 * @var $new_feed_url
 * @var $turbo
 * @var $googleplay_explicit
 * @var $exclude_series
 * @var string $locked Yes or no.
 *
 * @var array $funding {
 *      Array of link values.
 *
 * 	@type string $title Link title
 * 	@type string $url Link url
 * }
 *
 * @var array $podcast_value {
 *      Array of link values.
 *
 * 	@type string $recipient Recipient wallet address
 * }
 *
 * @var string $guid
 * @var int $series_id
 * @var string $pub_date_type
 * @var \WP_Query $qry
 * @var string $feed_link
 * @var Feed_Controller $feed_controller
 * @var bool $is_excerpt_mode
 * @var bool $home_url
 * @var string $media_prefix
 */

// Exit if accessed directly.
use SeriouslySimplePodcasting\Controllers\Feed_Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 xmlns:podcast="https://podcastindex.org/namespace/1.0"
	<?php do_action( 'rss2_ns' ); ?>>
	<?php do_action( 'ssp_feed_before_channel_tag' ) ?>
	<channel>
		<title><?php echo esc_html( $title ); ?></title>
		<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml"/>
		<link><?php echo esc_url( $feed_link ); ?></link>
		<description><?php echo esc_html( $description ); ?></description>
		<lastBuildDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ); ?></lastBuildDate>
		<language><?php echo esc_html( $language ); ?></language>
		<copyright><?php echo esc_html( $copyright ); ?></copyright>
		<itunes:subtitle><?php echo esc_html( $subtitle ); ?></itunes:subtitle>
		<itunes:author><?php echo esc_html( $author ); ?></itunes:author>
		<?php if ( $itunes_type ) :
		?><itunes:type><?php echo $itunes_type; ?></itunes:type>
		<?php endif
		?><itunes:summary><?php echo esc_html( $description ); ?></itunes:summary>
		<itunes:owner>
			<itunes:name><?php echo esc_html( $owner_name ); ?></itunes:name>
		<?php if ( $owner_email ) :
			?>	<itunes:email><?php echo esc_html( $owner_email ); ?></itunes:email>
		<?php endif; ?></itunes:owner>
		<itunes:explicit><?php echo esc_html( $itunes_explicit ); ?></itunes:explicit>
		<?php if ( $complete ) : ?>
			<itunes:complete><?php echo esc_html( $complete ); ?></itunes:complete>
		<?php endif;

		if ( $image ) :
			?><itunes:image href="<?php echo esc_url( $image ); ?>"></itunes:image>
			<image>
				<url><?php echo esc_url( $image ); ?></url>
				<title><?php echo esc_html( $title ); ?></title>
				<link><?php echo esc_url( apply_filters( 'ssp_feed_channel_link_tag', $home_url, $podcast_series ) ) ?></link>
			</image>
		<?php endif;

		if ( isset( $category1['category'] ) && $category1['category'] ) :
			?><itunes:category text="<?php echo esc_attr( $category1['category'] ); ?>"><?php
		if ( isset( $category1['subcategory'] ) && $category1['subcategory'] ) : ?>

			<itunes:category text="<?php echo esc_attr( $category1['subcategory'] ); ?>"></itunes:category><?php
		endif; ?>

		</itunes:category>
		<?php endif;

		if ( isset( $category2['category'] ) && $category2['category'] ) :
			?><itunes:category text="<?php echo esc_attr( $category2['category'] ); ?>">
				<?php if ( isset( $category2['subcategory'] ) && $category2['subcategory'] ) { ?>
					<itunes:category text="<?php echo esc_attr( $category2['subcategory'] ); ?>"></itunes:category>
				<?php } ?>
			</itunes:category>
		<?php endif;

		if ( isset( $category3['category'] ) && $category3['category'] ) :
			?><itunes:category text="<?php echo esc_attr( $category3['category'] ); ?>">
				<?php if ( isset( $category3['subcategory'] ) && $category3['subcategory'] ) { ?>
					<itunes:category text="<?php echo esc_attr( $category3['subcategory'] ); ?>"></itunes:category>
				<?php } ?>
			</itunes:category>
		<?php endif;

		if ( $new_feed_url ) :
			?><itunes:new-feed-url><?php echo esc_url( $new_feed_url ); ?></itunes:new-feed-url>
		<?php endif;

		if ( 'on' !== $turbo ) :
			?><googleplay:author><![CDATA[<?php echo esc_html( $author ); ?>]]></googleplay:author>
			<?php if ( $owner_email ) :
			?><googleplay:email><?php echo esc_html( $owner_email ); ?></googleplay:email><?php endif ?>
			<googleplay:description><?php echo esc_html( $description ); ?></googleplay:description>
			<googleplay:explicit><?php echo esc_html( $googleplay_explicit ); ?></googleplay:explicit>
			<?php if ( $image ) :
			?><googleplay:image href="<?php echo esc_url( $image ); ?>"></googleplay:image>
			<?php endif;
		endif;

		if ( 'yes' === $locked ) :
			?><podcast:locked<?php echo $owner_email ? ' owner="' . esc_html( $owner_email ) . '"' : '' ?>><?php
			echo esc_html( $locked ) ?></podcast:locked>
		<?php endif;

		if ( $funding && ! empty( $funding['url'] ) && ! empty( $funding['title'] ) ) :
			?><podcast:funding url="<?php echo esc_attr( $funding['url'] ) ?>"><?php echo esc_html( $funding['title'] ) ?></podcast:funding>
		<?php endif;

		if ( $podcast_value && ! empty( $podcast_value['recipient'] ) ) :
			$name = empty( $podcast_value['name'] ) ? 'podcaster' : $podcast_value['name'];
		?><podcast:value type="lightning" method="keysend" suggested="0.00000020000">
			<?php if ( empty( $podcast_value['custom_key'] ) || empty( $podcast_value['custom_value'] ) ) :
			?><podcast:valueRecipient name="<?php echo esc_attr( $name ) ?>" address="<?php echo esc_attr( $podcast_value['recipient'] ) ?>" split="100" type="node" />
			<?php else :
			?><podcast:valueRecipient name="<?php echo esc_attr( $name ) ?>" address="<?php echo esc_attr( $podcast_value['recipient'] ) ?>" split="100" type="node" customKey="<?php
			echo esc_attr( $podcast_value['custom_key'] ) ?>" customValue="<?php echo esc_attr( $podcast_value['custom_value'] ) ?>" />
		<?php    endif
		?></podcast:value>
		<?php endif;

		if ( $guid ) :
		?><podcast:guid><?php echo esc_attr( $guid ) ?></podcast:guid>
		<?php endif; ?>

		<!-- podcast_generator="SSP by Castos/<?php echo SSP_VERSION ?>" Seriously Simple Podcasting plugin for WordPress (https://wordpress.org/plugins/seriously-simple-podcasting/) -->
		<?php

		// Prevent WP core from outputting an <image> element
		remove_action( 'rss2_head', 'rss2_site_icon' );

		// Add RSS2 headers
		do_action( 'rss2_head' );

		if ( 'on' === $turbo ) {
			$turbo_post_count = 0;
		}

		if ( $qry->have_posts() ) {
			while ( $qry->have_posts() ) {
				$turbo_post_count = isset( $turbo_post_count ) ? $turbo_post_count + 1 : null;
				$args             = compact( 'author', 'is_excerpt_mode', 'pub_date_type', 'turbo_post_count', 'media_prefix' );
				echo $feed_controller->fetch_feed_item( $qry, $args );
			}
		} ?>
	</channel>
</rss>
