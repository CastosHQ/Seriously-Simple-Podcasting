=== Seriously Simple Podcasting ===
Contributors: hlashbrooke
Donate link: http://www.hughlashbrooke.com/donate/
Tags: podcast, audio, rss, feed, itunes, media player
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 1.4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Podcasting done right.

== Description ==

"Seriously Simple Podcasting" is a simple and easy-to-use podcasting solution for WordPress. It uses a native WordPress interface and has minimal settings so as not to distract you from what you really need to do - publish your content. Quite simply, it is podcasting done right.

It comes with built-in templates, widget and shortcode so you can display your podcast however you like. It also has easy subscribe links for iTunes listeners and standard RSS subscribers.

Podcast episodes are playable directly on your site using the MediaElement.js audio player that works in all browsers: http://mediaelementjs.com/.

CONTRIBUTE ON GITHUB: https://github.com/hlashbrooke/Seriously-Simple-Podcasting

UPGRADE NOTICE: Users upgrading to v1.4.x please take note of the changed feed URLs for individual series.

== Usage ==

Simply upload the plugin and you're good to go. Go to "Podcast > Add New" to add new episodes and go to "Podcast > Settings" to customise, describe, protect, redirect & share your podcast.

Podcast audio files can be uploaded directly into WordPress or hosted on any other site - in the latter case all you'll need to supply is the URL to the file. Please note that episode lengths can only be calculated for files that are hosted on the same server as the website.

== Installation ==

Installing "Seriously Simple Podcasting" can be done either by searching for "Seriously Simple Podcasting" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org
1. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Customise the plugin from the 'Podcast > Settings' page

== Frequently Asked Questions ==

= I've installed the plugin and added episodes, but where do I find all of it on my website? =

Your podcast page will always be available from this URL on your site: http://www.example.com/?post_type=podcast, but if you go to the plugin's settings page (Podcast > Settings) you can specify a URL slug that will make your podcast URLs more attractive - once you have saved your slug, the podcast archive page URL will be formatted like this: http://www.example.com/your-slug.

= Why do the podcast episode and archive pages just look like my default blog pages? =

On the plugin's settings page (Podcast > Settings) you can opt to use the plugin's built-in templates - they will ensure that your podcast is displayed correctly. They are not guaranteed to fit in with every theme, but you can simply create your own page templates to override them. To do this, disable the use of the built-in templates and copy the 'archive-podcast.php' and 'single-podcast.php' files from the plugin's 'templates' directory and paste them in your theme's root folder - from there you can edit them to display the content however you wish.

= Why don't I see the media player when I view the individual episode pages? =

There could be a few reasons for this:

1. Your episode audio file is reference to folder and not a specific file
1. The audio file is password protected
1. Your site is running on an IP address and not a domain name.

If any of these are true for you then that will most likely be the reason that the media player is not showing on your podcast episodes.

= My feed password does not seem to be saving - what gives? =

Once you have saved a password for your podcast feed you will not see the password on screen again - this is because it is encoded and stored securely. You can enter a different password at any time, but if you save the settings page and you do not want to change the password then simply leave the password field blank and it will not be updated.

= Where can I find documentation/support for this plugin? =

There is currently no documentation for this plugin, but you can post questions on the support forum here: http://wordpress.org/support/plugin/seriously-simple-podcasting - I will respond to topics as I have time to do so, but I can make no guarantees of my availability. Before posting anything on the support forum, please update to the latest version of the plugin.

= I have an idea for this plugin - how can I make it known? =

If you have an idea for the plugin, feel free to post on the support forum here: http://wordpress.org/support/plugin/seriously-simple-podcasting.
If you would like to contribute to the code then you can fork the GitHub repo here: https://github.com/hlashbrooke/Seriously-Simple-Podcasting - your pull requests will be reviewed and merged if they fit into the goals for this plugin. All contributors will be given credit where it is due.

== Screenshots ==

1. The plugin settings screen within the WordPress admin (it has been slightly modified from this since v1.3).
2. The info that you need to add to each podcast episode.

== Changelog ==

= 1.4.2 =
* 2013-03-30
* [UPDATE] Added work around for WordPress bug that causes 404 error on feed when site has no posts
* [UPDATE] Removed episode meta from feed description/summary
* [UPDATE] Added global function to check if podcast feed is loading
* [UPDATE] Core functions are now loaded earlier in the plugin so they are more widely available

= 1.4.1 =
* 2013-03-18
* [UPDATE] Restructured & streamlined settings page
* [FIX] Fixed built-in archive page template
* [UPDATE] Updated FAQs to reflect recent support queries

= 1.4 =
* 2013-03-13
* [FEATURE] Added option to password protect podcast feed - sets a 'HTTP 401 Unauthorized' header and requests a username and password
* [FEATURE] Added 'do_feed_podcast' action so plugins/themes can intercept the feed or add their own processing - see templates/feed-podcast.php for usage caveats
* [USABILITY] Added series feed URL to series taxonomy table for quick reference
* [USABILITY] Moved feed template include to latest possible hook - this allows other plugins to load their templates first if necessary
* [USABILITY] Simplified field descriptions on settings page
* [FIX] Fixed series feed URLs (please take note of changes on podcast settings page)
* [FIX] Fixed a few typos on the settings page
* [UPDATE] Added validation to podcast description field
* [UPDATE] Updated localisation strings
* [UPDATE] Updated plugin FAQ
* [UPDATE] Added 'Upcoming Features' list

= 1.3.4 =
* 2013-03-11
* [FIX] Fixed issue where site subtitle was being displayed in author field in feed

= 1.3.3 =
* 2013-03-09
* [USABILITY] Added 'author' and 'custom fields' to podcast episode edit page

= 1.3.2 =
* 2013-03-08
* [USABILITY] Added media player to podcast meta data for display when built-in templates are not being used

= 1.3.1 =
* 2013-02-28
* [USABILITY] Added comments capability to podcast episodes
* [FIX] Removed HTML tags from feed description/summary
* [UPDATE] Improved MIME type recognition
* [UPDATE] Improved plugin FAQ

= 1.3 =
* 2013-02-16
* [FEATURE] Added option to syndicate your feed through Feedburner (or similar service)
* [FEATURE] Added RSS meta tags to site header
* [FEATURE] Added option to show podcast episodes in main query loop on home page along with blog posts
* [USABILITY] Unified feed templates, so only one feed is used for all podcasting services (ensured backward compatibility for existing feed URLs)
* [USABILITY] Changed podcast settings page URL (menu link is still in same place though)

= 1.2.2 =
* 2013-02-14
* [FIX] Removed conflicts with other plugins that prevented some admin pages from loading

= 1.2.1 =
* 2013-02-13
* [FIX] Fixed critical bug that was preventing episode data from being added

= 1.2 =
* 2013-02-12
* [FEATURE] Added setting for redirecting podcast feed to new URL
* [FEATURE] Added episode meta data to start of episode excerpt
* [FIX] Fixed file size info & episode descriptions in feeds
* [USABILITY] Moved settings page to be a sub-page of the Podcast menu
* [UPDATE] Improved enclosure file size detection
* [UPDATE] Improved code commenting to make some features more clear
* [UPDATE] Improved script loading in dashboard to improve performance on all admin pages
* [UPDATE] Improved FAQ list

= 1.1.4 =
* 2013-02-07
* [UPDATE] Switching to using WordPress' built-in MIME type detection
* [UPDATE] Improving feed tag layout

= 1.1.3 =
* 2013-01-23
* [FIX] Fixing some feed validaton errors and warnings

= 1.1.2 =
* 2013-01-21
* [FIX] Removing PHP errors
* [FIX] Fixing XML encoding of category names

= 1.1.1 =
* 2013-01-18
* [UPDATE] Adding file MIME type to feed RSS

= 1.1 =
* 2013-01-17
* [FEATURE] Added loads of settings for the podcast feed details
* [FEATURE] Massive improvements to both iTunes & standard RSS feeds (including new feed URLs)
* [FEATURE] Audio duration is now calculated automatically
* [FEATURE] Added 'keywords' taxonomy to episodes
* [UPDATE] General performance enhancements
* [UPDATE] Enhanced localisation support

= 1.0.1 =
* 2013-01-06
* [FIX] Fixing bug that broke media uploader in WordPress 3.5

= 1.0.0 =
* 2012-12-13
* Initial release

== Upgrade Notice ==

= 1.4.2 =
* [UPDATE] Added work around for WordPress bug that causes 404 error on feed when site has no posts
* [UPDATE] Removed episode meta from feed description/summary
* [UPDATE] Added global function to check if podcast feed is loading
* [UPDATE] Core functions are now loaded earlier in the plugin so they are more widely available

= 1.4.1 =
* [UPDATE] Restructured & streamlined settings page
* [FIX] Fixed built-in archive page template
* [UPDATE] Updated FAQs to reflect recent support queries

= 1.4 =
* [FEATURE] Added option to password protect podcast feed - sets a 'HTTP 401 Unauthorized' header and requests a username and password
* [FEATURE] Added 'do_feed_podcast' action so plugins/themes can intercept the feed or add their own processing - see templates/feed-podcast.php for usage caveats
* [USABILITY] Added series feed URL to series taxonomy table for quick reference
* [USABILITY] Moved feed template include to latest possible hook - this allows other plugins to load their templates first if necessary
* [USABILITY] Simplified field descriptions on settings page
* [FIX] Fixed series feed URLs (please take note of changes on podcast settings page)
* [FIX] Fixed a few typos on the settings page
* [UPDATE] Added validation to podcast description field
* [UPDATE] Updated localisation strings
* [UPDATE] Updated plugin FAQ
* [UPDATE] Added 'Upcoming Features' list

= 1.3.4 =
* [FIX] Fixed issue where site subtitle was being displayed in author field in feed

= 1.3.3 =
* [USABILITY] Added 'author' and 'custom fields' to podcast episode edit page

= 1.3.2 =
* [USABILITY] Added media player to podcast meta data for display when built-in templates are not being used

= 1.3.1 =
* [USABILITY] Added comments capability to podcast episodes
* [FIX] Removed HTML tags from feed description/summary
* [UPDATE] Improved MIME type recognition
* [UPDATE] Improved plugin FAQ

= 1.3 =
* [FEATURE] Added option to syndicate your feed through Feedburner (or similar service)
* [FEATURE] Added RSS meta tags to site header
* [FEATURE] Added option to show podcast episodes in main query loop on home page along with blog posts
* [USABILITY] Unified feed templates, so only one feed is used for all podcasting services (ensured backward compatibility for existing feed URLs)
* [USABILITY] Changed podcast settings page URL (menu link is still in same place though)

= 1.2.2 =
* [FIX] Removed conflicts with other plugins that prevented some admin pages from loading

= 1.2.1 =
* [FIX] Fixed critical bug that was preventing episode data from being added

= 1.2 =
* [FEATURE] Added setting for redirecting podcast feed to new URL
* [FEATURE] Added episode meta data to start of episode excerpt
* [FIX] Fixed file size info & episode descriptions in feeds
* [USABILITY] Moved settings page to be a sub-page of the Podcast menu
* [UPDATE] Improved enclosure file size detection
* [UPDATE] Improved code commenting to make some features more clear
* [UPDATE] Improved script loading in dashboard to improve performance on all admin pages
* [UPDATE] Improved FAQ list

= 1.1.4 =
* [UPDATE] Switching to using WordPress' built-in MIME type detection
* [UPDATE] Improving feed tag layout

= 1.1.3 =
* [FIX] Fixing some feed validaton errors and warnings

= 1.1.2 =
* [FIX] Removing PHP errors
* [FIX] Fixing XML encoding of category names

= 1.1.1 =
* [UPDATE] Adding file MIME type to feed RSS

= 1.1 =
* [FEATURE] Added loads of settings for the podcast feed details
* [FEATURE] Massive improvements to both iTunes & standard RSS feeds (including new feed URLs)
* [FEATURE] Audio duration is now calculated automatically
* [FEATURE] Added 'keywords' taxonomy to episodes
* [UPDATE] General performance enhancements
* [UPDATE] Enhanced localisation support

= 1.0.1 =
* [FIX] Fixing bug that broke media uploader in WordPress 3.5

= 1.0.0 =
* Initial release