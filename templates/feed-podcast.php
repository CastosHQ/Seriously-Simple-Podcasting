<?php
/**
 * Podcast RSS feed template
 *
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 *
 * Refactoring history:
 * Moved the data preparation to the feed controller
 * @see \SeriouslySimplePodcasting\Controllers\Feed_Controller::load_feed_template()
 *
 * @var $stylesheet_url
 * @var $title
 * @var \SeriouslySimplePodcasting\Controllers\Frontend_Controller $ss_podcasting
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
 */

// Exit if accessed directly.
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
	<?php do_action( 'rss2_ns' ); ?>>
	<channel>
		<title><?php echo esc_html( $title ); ?></title>
		<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml"/>
		<link><?php echo esc_url( apply_filters( 'ssp_feed_channel_link_tag', $ss_podcasting->home_url, $podcast_series ) ) ?></link>
		<description><?php echo esc_html( $description ); ?></description>
		<lastBuildDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ); ?></lastBuildDate>
		<language><?php echo esc_html( $language ); ?></language>
		<copyright><?php echo esc_html( $copyright ); ?></copyright>
		<itunes:subtitle><?php echo esc_html( $subtitle ); ?></itunes:subtitle>
		<itunes:author><![CDATA[<?php echo esc_html( $author ); ?>]]></itunes:author>
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
			<googleplay:author><![CDATA[<?php echo esc_html( $author ); ?>]]></googleplay:author>
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

				$feed_item_path = apply_filters('ssp_feed_item_path', __DIR__ . '/feed/feed-item.php');

				include $feed_item_path;
			}
		} ?>
	</channel>
</rss>
