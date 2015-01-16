=== Seriously Simple Podcasting ===
Contributors: hlashbrooke
Donate link: http://www.hughlashbrooke.com/donate/
Tags: podcast, audio, rss, feed, itunes, media player
Requires at least: 4.0
Tested up to: 4.1
Stable tag: 1.8.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Podcasting the way it's meant to be.

== Description ==

"Seriously Simple Podcasting" is a simple and easy-to-use podcasting solution for WordPress. It uses a native WordPress interface and has minimal settings so as not to distract you from what you really need to do - publish your content.

**Primary Features**

- New `podcast` post type and `series` taxonomy for managing your podcast episodes
- Ability to use any post type for podcast episodes
- Highly configurable RSS feed designed for all podcast services and feed readers, including iTunes
- Widget for displaying recent podcast episodes anywhere on your site
- Playable episodes using the built-in WordPress media player
- Simple settings so you can get your podcast up and running quickly
- Allows audio files to be hosted on the same site or any other server
- Full i18n support

**Some examples of the plugin in action**

- [Southern Cross Church](http://www.southerncrosschurch.org/sermons/)
- [WP Cape Town](http://www.wpcapetown.co.za/podcast/)

Want to contribute? [Fork the GitHub repository](https://github.com/hlashbrooke/Seriously-Simple-Podcasting).

== Usage ==

Simply upload the plugin and you're good to go. Go to "Podcast > Add New" to add new episodes and go to "Podcast > Settings" to customise your podcast.

Podcast audio files can be uploaded directly into WordPress or hosted on any other site - in the latter case all you'll need to supply is the URL to the file. Please note that episode lengths and file sizes can only be automatically calculated for files that are hosted on the same server as the website - either way though, you can input them in manually.

== Installation ==

Installing "Seriously Simple Podcasting" can be done either by searching for "Seriously Simple Podcasting" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org
1. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Customise the plugin from the 'Podcast > Settings' page

== Frequently Asked Questions ==

= I've just updated to v1.8 and things are all messed up - what have you done?!? =

Please read [this post](http://www.hughlashbrooke.com/2015/01/important-upgrade-information-seriously-simple-podcasting-v1-8/) regarding the updates that come with v1.8 of Seriously Simple Podcasting. There have been changes to the template system, audio player display and various other important areas.

= The plugin doesn't work like it should - what's the problem? =

There could be a number of reasons for this, but the most common one is that you have another podcasting plugin activated on your site. Make sure you deactivate any other podcasting plugins (e.g. BluBrry PowerPress, PodPress, etc.). If you have done that and the plugin is still not working as it should then have a read of the rest of the FAQs as well as the support forum - if you don't find what you need there, then create a new topic.

= I've installed the plugin and added episodes, but where do I find all of it on my website? =

By default, your podcast episodes will be available at this URL on your site: http://www.example.com/podcast and your feed will be available here: http://www.example.com/feed/podcast. You can also set your podcast episodes to appear in your home page blog feed in the general settings.

= How do I change the URLs for my podcast episodes and RSS feed? =

The URLs mentioned in the previous question can be changed by using the following snippets.

For the podcast episode archive:

`
add_filter( 'ssp_archive_slug', 'ssp_modify_podcast_archive_slug' );
function ssp_modify_podcast_archive_slug ( $slug ) {
  return 'new-slug';
}
`

For the podcast RSS feed:

`
add_filter( 'ssp_feed_slug', 'ssp_modify_podcast_feed_slug' );
function ssp_modify_podcast_feed_slug ( $slug ) {
  return 'new-slug';
}
`

Just add those to your theme (or a plugin) and then re-save your site's permalinks on the Settings > Permalinks page and you'll be good to go.

= How can I edit the actual content of the RSS feed? =

You shouldn't ever need to do this, but if you would like to override the the RSS feed content then copy the 'feed-template.php' file from the plugin's 'templates' directory and paste it into your theme's root folder - from there you can edit it to display the feed content however you wish.

= Why can't I upload an audio file that is larger than 2/8/16/32/XX MB? =

This issue actually has nothing to do with this plugin. Your server and your WordPress installation will each have a maximum file upload size that you are allowed to make use of and there is no way for this plugin to override that. There are a few ways in which you can increase your server's maximum file upload size, but those instructions are outside the scope of this plugin's FAQ.

= Why don't I see the audio player when I view the individual episode pages? =

There could be a few reasons for this:

1. You have not set the audio player to appear for the full content of an episode - you can change this in the podcast settings
2. Your episode audio file is a reference to a folder and not a specific file
3. The audio file is password protected
4. Your site is running on an IP address and not a domain name
5. You have a plugin installed that conflicts with the WordPress media player

If any of these are true for you then that will most likely be the reason that the audio player is not showing on your podcast episodes.

= Why does my podcast image not save properly? =

For your podcast image to be valid on iTunes, it must be at least 1400x1400 px. This means that when you upload your image you must make sure to select a size option that at least has those dimensions. The image will display smaller on the plugin's settings screen, but as long as your uploaded image is the correct dimensions and you have selected the right size when inserting it then it will work.

= My feed password does not seem to be saving - what gives? =

Once you have saved a password for your podcast feed you will not see the password on screen again - this is because it is encoded and stored securely. You can enter a different password at any time, but if you save the settings page and you do not want to change the password then simply leave the password field blank and it will not be updated.

= Where can I find documentation/support for this plugin? =

There is currently no documentation for this plugin, but you can post questions on the support forum [here](http://wordpress.org/support/plugin/seriously-simple-podcasting) - I will respond to topics as I have time to do so, but I can make no guarantees of my availability. Before posting anything on the support forum, please update to the latest version of the plugin.

= I have an idea for this plugin - how can I make it known? =

If you have an idea for the plugin, feel free to post on the support forum [here](http://wordpress.org/support/plugin/seriously-simple-podcasting).
If you would like to contribute to the code then you can fork the GitHub repo [here](https://github.com/hlashbrooke/Seriously-Simple-Podcasting) - your pull requests will be reviewed and merged if they fit into the goals for this plugin. All contributors will be given credit where it is due.

== Screenshots ==

1. Audio player and episode details displayed on the frontend above post content.
2. The only info that you need to add to each podcast episode.
3. Set up your podcast.
4. Customise your podcast feed.
5. Secure your podcast feed.
6. Handle redirection for your feed.
7. Publish your podcast.
8. The podcast admin menu location.
9. Podcast episodes are shown in the At a Glance dashboard widget.

== Changelog ==

= 1.8.1 =
* 2015-01-16
* [NEW] Adding option to display audio player above or below post content
* [TWEAK] Adding podcast taxonomies to all podcast post types
* [TWEAK] Adding additional labels to taxonomies
* [TWEAK] Adding 'ssp_feed_url' filter to feed URL in RSS meta tag
* [FIX] Fixing feed URL in RSS meta tag
* [FIX] Localising RSS meta tag `title` attribute

= 1.8 =
* 2015-01-13
* [NEW] Allowing any post type to be used for podcast episodes
* [NEW] Changing options for displaying audio player and episode details
* [NEW] Removing built-in templates
* [NEW] Removing and replacing widget with new 'Recent Podcast Episodes' widget
* [NEW] Complete restructuring and renaming of classes and files
* [NEW] New settings class - settings page uses proper tabs, loads much faster and can now be easily extended
* [NEW] Removing MediaElement library from plugin
* [NEW] Generating feed correctly using add_feed() and ensuring backwards compatibility for previous versions
* [NEW] Commenting all the things
* [NEW] Adding brand new actions and filters everywhere
* [TWEAK] Changing dashboard menu icon to be more relevant
* [TWEAK] Improving file size calculations and saving (kudos danielpunkass & kallewangstedt)
* [TWEAK] Optimising feed template

= 1.7.5 =
* 2013-12-22
* [TWEAK] Fixing content display on single podcast template (kudos jeherve)
* [TWEAK] Updating admin icon for WordPress 3.8
* [TWEAK] Small CSS tweak on single podcast template
* [FIX] Fixing site URLs to work with proper home URL field (kudos firejdl)

= 1.7.4 =
* 2013-11-26
* [FIX] Fixing site URL functions

= 1.7.3 =
* 2013-10-25
* [FIX] Fixing WordPress version comparison for audio player
* [FIX] Fixing fatal error if ID3 class already exists

= 1.7.2 =
* 2013-09-25
* [FIX] Fixing Javascript error that prevented media uploading from working in Firefox

= 1.7.1 =
* 2013-09-23
* [TWEAK] Adding error notice for versions of WordPress prior to v3.5
* [FIX] Removing PHP warning when using widget
* [FIX] Fixing fatal error when using WordPress versions older than v3.5

= 1.7 =
* 2013-09-07
* [NEW] Adding ability to block individual episodes from appearing in iTunes
* [NEW] Adding integrated sharing options so you can share your podcast URL straight from your WordPress dashboard
* [NEW] Adding function to overwrite feed template (see the FAQ for more info)
* [TWEAK] Switching to using the new WordPress media uploader for all media uploading in plugin
* [TWEAK] General code clean up
* [TWEAK] Updating plugin FAQ
* [FIX] Fixing bug that prevented images being uploaded to episode post content

= 1.6.1 =
* 2013-08-23
* [FIX] Switching download link to use home_url() instead of site_url()
* [TWEAK] Updating plugin FAQ

= 1.6 =
* 2013-08-15
* [NEW] Adding option to hide audio player and episode data from the top of episode content
* [NEW] Adding episode descriptions for individual episodes in iTunes
* [TWEAK] Using built-in audio player functions for WordPress 3.6+
* [TWEAK] Improving content display for built-in plugin templates

= 1.5.3 =
* 2013-05-11
* [FIX] Fixing episode file URL in feed

= 1.5.2 =
* 2013-05-10
* [TWEAK] Slight tweak to HTML encoding in feed fields
* [TWEAK] Improving episode download functionality
* [TWEAK] Adding hooks for admin pages
* [FIX] Fixing backwards compatiblity for old iTunes feed link

= 1.5.1 =
* 2013-05-03
* [TWEAK] Improving (and fully fixing) HTML encoding in feed fields
* [TWEAK] Setting rewrite rules to flush on plugin activation
* [TWEAK] Adding to the plugin FAQ
* [FIX] Fixing typo in back-end widget display

= 1.5 =
* 2013-04-28
* [NEW] Forcing episode download when download link is clicked
* [TWEAK] Improving UI for podcast image uploads
* [TWEAK] Removing episode meta data from excerpt text
* [TWEAK] Improving built-in single episode template
* [TWEAK] Adding action to settings screen allowing additional settings fields to be added
* [FIX] Showing all episodes in podcast feed

= 1.4.6 =
* 2013-04-26
* [TWEAK] Adding settings info about new iTunes image dimensions
* [TWEAK] Adding admin notice for plugin survey

= 1.4.5 =
* 2013-04-25
* [TWEAK] Adding further checks for removing HTML from feed content
* [TWEAK] Trimming episode summary/description in feed

= 1.4.4 =
* 2013-04-19
* [TWEAK] Adding file size fallback to feed template to avoid potential validation errors

= 1.4.3 =
* 2013-04-07
* [TWEAK] Adding new plugin branding to WordPress admin - no new features are included in this update
* [TWEAK] Updated the plugin FAQ

= 1.4.2 =
* 2013-03-30
* [TWEAK] Added work around for WordPress bug that causes 404 error on feed when site has no posts
* [TWEAK] Removed episode meta from feed description/summary
* [TWEAK] Added global function to check if podcast feed is loading
* [TWEAK] Core functions are now loaded earlier in the plugin so they are more widely available

= 1.4.1 =
* 2013-03-18
* [TWEAK] Restructured & streamlined settings page
* [FIX] Fixed built-in archive page template
* [TWEAK] Updated FAQs to reflect recent support queries

= 1.4 =
* 2013-03-13
* [NEW] Added option to password protect podcast feed - sets a 'HTTP 401 Unauthorized' header and requests a username and password
* [NEW] Added 'do_feed_podcast' action so plugins/themes can intercept the feed or add their own processing - see templates/feed-podcast.php for usage caveats
* [USABILITY] Added series feed URL to series taxonomy table for quick reference
* [USABILITY] Moved feed template include to latest possible hook - this allows other plugins to load their templates first if necessary
* [USABILITY] Simplified field descriptions on settings page
* [FIX] Fixed series feed URLs (please take note of changes on podcast settings page)
* [FIX] Fixed a few typos on the settings page
* [TWEAK] Added validation to podcast description field
* [TWEAK] Updated localisation strings
* [TWEAK] Updated plugin FAQ
* [TWEAK] Added 'Upcoming Features' list

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
* [TWEAK] Improved MIME type recognition
* [TWEAK] Improved plugin FAQ

= 1.3 =
* 2013-02-16
* [NEW] Added option to syndicate your feed through Feedburner (or similar service)
* [NEW] Added RSS meta tags to site header
* [NEW] Added option to show podcast episodes in main query loop on home page along with blog posts
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
* [NEW] Added setting for redirecting podcast feed to new URL
* [NEW] Added episode meta data to start of episode excerpt
* [FIX] Fixed file size info & episode descriptions in feeds
* [USABILITY] Moved settings page to be a sub-page of the Podcast menu
* [TWEAK] Improved enclosure file size detection
* [TWEAK] Improved code commenting to make some features more clear
* [TWEAK] Improved script loading in dashboard to improve performance on all admin pages
* [TWEAK] Improved FAQ list

= 1.1.4 =
* 2013-02-07
* [TWEAK] Switching to using WordPress' built-in MIME type detection
* [TWEAK] Improving feed tag layout

= 1.1.3 =
* 2013-01-23
* [FIX] Fixing some feed validaton errors and warnings

= 1.1.2 =
* 2013-01-21
* [FIX] Removing PHP errors
* [FIX] Fixing XML encoding of category names

= 1.1.1 =
* 2013-01-18
* [TWEAK] Adding file MIME type to feed RSS

= 1.1 =
* 2013-01-17
* [NEW] Added loads of settings for the podcast feed details
* [NEW] Massive improvements to both iTunes & standard RSS feeds (including new feed URLs)
* [NEW] Audio duration is now calculated automatically
* [NEW] Added 'keywords' taxonomy to episodes
* [TWEAK] General performance enhancements
* [TWEAK] Enhanced localisation support

= 1.0.1 =
* 2013-01-06
* [FIX] Fixing bug that broke media uploader in WordPress 3.5

= 1.0.0 =
* 2012-12-13
* Initial release

== Upgrade Notice ==

= 1.8.1 =
* v1.8 is a major update that affects how your podcast is displayed on your site. READ THIS BEFORE UPDATING: http://www.hughlashbrooke.com/2015/01/important-upgrade-information-seriously-simple-podcasting-v1-8/.