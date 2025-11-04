<?php
/**
 * Podcast RSS feed template
 *
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 *
 * @see \SeriouslySimplePodcasting\Controllers\Feed_Controller::get_podcast_feed()
 *
 * @var $stylesheet_url
 * @var $title
 * @var $description
 */

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
	 xmlns:podcast="https://podcastindex.org/namespace/1.0">
	<channel>
		<title><?php echo esc_html( $title ); ?></title>
		<description><?php echo esc_html( $description ); ?></description>
		<link><?php echo esc_url( trailingslashit( home_url() ) ); ?></link>
	</channel>
</rss>
