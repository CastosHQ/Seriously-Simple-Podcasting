Feature: Login
	In order to test the SSP plugin
	As an admin user
	I need to be able to login

	Background:
		Given I login as admin
		And I create episodes
			| title     | content			|
			| Episode1  | Episode1 content	|
			| Episode2  | Episode2 content	|
			| Episode3  | Episode3 content	|

	Scenario: Check the main feed
		When I click "Podcast" submenu "Settings"
		And I click tab "Feed details"
		Then I can see "Feed details: Automated test show"
		Then I can see "Edit Podcast Settings"
		And I click "View feed" link
		Then I can see that current url is "/feed/podcast/automated-test-show"


		# Check XML elements
		And I can see in source "<?xml version"
		And I can see in source "<?xml-stylesheet type=\"text/xsl\" href=\"{{base_url}}/wp-content/plugins/seriously-simple-podcasting/templates/feed-stylesheet.xsl\"?>"
		And I can see in source "<rss version=\"2.0\""
		And I can see in source "xmlns:content=\"http://purl.org/rss/1.0/modules/content/\""
		And I can see in source "xmlns:wfw=\"http://wellformedweb.org/CommentAPI/\""
		And I can see in source "xmlns:dc=\"http://purl.org/dc/elements/1.1/\""
		And I can see in source "xmlns:atom=\"http://www.w3.org/2005/Atom\""
		And I can see in source "xmlns:sy=\"http://purl.org/rss/1.0/modules/syndication/\""
		And I can see in source "xmlns:slash=\"http://purl.org/rss/1.0/modules/slash/\""
		And I can see in source "xmlns:itunes=\"http://www.itunes.com/dtds/podcast-1.0.dtd\""
		And I can see in source "xmlns:googleplay=\"http://www.google.com/schemas/play-podcasts/1.0\""
		And I can see in source "xmlns:podcast=\"https://podcastindex.org/namespace/1.0\""
		And I can see in source "<channel>"
		And I can see in source "<title>Automated test show</title>"
		And I can see in source "<atom:link href=\"{{base_url}}/feed/podcast/automated-test-show/\" rel=\"self\" type=\"application/rss+xml\"/>"
		And I can see in source "<link>{{base_url}}/podcasts/automated-test-show/</link>"
		And I can see in source "<description>This show is to test some SSP functionality</description>"
		And I can see in source "<lastBuildDate>"
		And I can see in source "<language>en-GB</language>"
		And I can see in source "<copyright>&#xA9; 2025 My WordPress</copyright>"
		And I can see in source "<itunes:subtitle>Just another WordPress site</itunes:subtitle>"
		And I can see in source "<itunes:author>My WordPress</itunes:author>"
		And I can see in source "<itunes:summary>This show is to test some SSP functionality</itunes:summary>"
		And I can see in source "<itunes:owner>"
		And I can see in source "<itunes:name>My WordPress</itunes:name>"
		And I can see in source "</itunes:owner>"
		And I can see in source "<itunes:explicit>false</itunes:explicit>"
		And I can see in source "<itunes:category text=\"Business\">"
		And I can see in source "<itunes:category text=\"Management\"></itunes:category>"
		And I can see in source "<googleplay:author><![CDATA[My WordPress]]></googleplay:author>"
		And I can see in source "<googleplay:description>This show is to test some SSP functionality</googleplay:description>"
		And I can see in source "<googleplay:explicit>No</googleplay:explicit>"
		And I can see in source "<podcast:locked>yes</podcast:locked>"
		And I can see in source "<podcast:guid>"

		# Check items
		And I can see in source "<title>Episode3</title>"
		And I can see in source "<dc:creator><![CDATA[My WordPress]]></dc:creator>"
		And I can see in source "<guid isPermaLink=\"false\">{{base_url}}"
		And I can see in source "<description><![CDATA[Episode3 content]]></description>"
		And I can see in source "<itunes:subtitle><![CDATA[Episode3 content]]></itunes:subtitle>"
		And I can see in source "<content:encoded><![CDATA[Episode3 content]]></content:encoded>"
		And I can see in source "<enclosure url=\"https://episodes.castos.com/podcasthacker/d21a1b7a-531f-48f1-b4c0-8b8add2bccfe-file-example.mp3\" length=\"1087849\" type=\"audio/mpeg\"></enclosure>"
		And I can see in source "type=\"audio/mpeg\"></enclosure>"
		And I can see in source "<itunes:summary><![CDATA[Episode3 content]]></itunes:summary>"
		And I can see in source "<itunes:explicit>false</itunes:explicit>"
		And I can see in source "<itunes:block>no</itunes:block>"
		And I can see in source "<itunes:duration>0:00</itunes:duration>"
		And I can see in source "<itunes:author><![CDATA[My WordPress]]></itunes:author>"
		And I can see in source "<googleplay:description><![CDATA[Episode3 content]]></googleplay:description>"
		And I can see in source "<googleplay:explicit>No</googleplay:explicit>"
		And I can see in source "<googleplay:block>no</googleplay:block>"

		And I can see in source "<title>Episode2</title>"
		And I can see in source "<description><![CDATA[Episode2 content]]></description>"

		And I can see in source "<title>Episode1</title>"
		And I can see in source "<description><![CDATA[Episode1 content]]></description>"
		And I can see in source "<!-- podcast_generator"
