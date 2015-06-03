=== Seriously Simple Podcasting ===
Contributors: hlashbrooke
Donate link: http://www.hughlashbrooke.com/donate/
Tags: podcast, audio, rss, rss2, m4a, mp3, feed, itunes, media player, podcasting, radio, audio player, media, id3, stitcher, show, shows, series, simple, docs, documentation, wav
Requires at least: 4.0
Tested up to: 4.2.2
Stable tag: 1.10.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Podcasting the way it's meant to be. No mess, no fuss - just you and your content taking over the world.

== Description ==

[Seriously Simple Podcasting](http://www.seriouslysimplepodcasting.com/) is an easy-to-use podcasting solution for WordPress that is as powerful as it is simple. It uses a native WordPress interface and has minimal settings so as not to distract you from what you really need to do - publish your content.

**Primary Features**

- Simple settings so you can get your podcast up and running quickly
- Run multiple podcasts from the same site - each with their own, unique RSS feed
- New `podcast` post type and `series` taxonomy for managing your podcast episodes
- Use any post type for your podcast episodes
- Highly configurable and robust RSS feed designed for *all* podcast services and feed readers, including iTunes and Stitcher
- Shortcodes & widgets for displaying podcast episode lists as well as single episodes anywhere on your site
- Playable episodes using the built-in WordPress media player
- The freedom to host your audio files on the same site or any other server
- Complete user and developer [documentation](http://www.seriouslysimplepodcasting.com/)
- Full i18n support

**Some examples of the plugin in action**

- [Southern Cross Church](http://www.southerncrosschurch.org/sermons/)
- [WP Cape Town](http://www.wpcapetown.co.za/podcast/)

**Where to find help**

Seriously Simple Podcasting comes with complete user and developer [documentation](http://www.seriouslysimplepodcasting.com/). Please read this documentation thoroughly before posting on [the support forum](https://wordpress.org/support/plugin/seriously-simple-podcasting).

**How to contribute**

If you want to contribute to Seriously Simple Podcasting, you can [fork the GitHub repository](https://github.com/hlashbrooke/Seriously-Simple-Podcasting) - please read the [contributor guidelines](https://github.com/hlashbrooke/Seriously-Simple-Podcasting/blob/master/CONTRIBUTING.md) for more information on how you can do this.

== Usage ==

Simply upload the plugin and you're good to go. Go to "Podcast > Add New" to add new episodes and go to "Podcast > Settings" to customise your podcast.

Podcast audio files can be uploaded directly into WordPress or hosted on any other site - in the latter case all you'll need to supply is the URL to the file. *Please note that episode lengths and file sizes can only be automatically calculated for files that are hosted on the same server as the website - either way though, you can input them in manually.*

If you need help, you can find complete user and developer documentation [here](http://www.seriouslysimplepodcasting.com/).

== Installation ==

Installing "Seriously Simple Podcasting" can be done either by searching for "Seriously Simple Podcasting" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org
1. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Customise the plugin from the 'Podcast > Settings' page

== Frequently Asked Questions ==

= Where are the FAQs? =

You can find complete user and developer documentation (along with the FAQs) on [the Seriously Simple Podcasting documentation site](http://www.seriouslysimplepodcasting.com/).

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
10. Recent episodes widget
11. Single episode widget
12. Podcast series widget

== Changelog ==

= 1.10.2 =
* 2015-06-03
* [FIX] Further (final) fixing of SQL statement when fetching episode from audio file URL (props Stef Pause)

= 1.10.1 =
* 2015-06-03
* [FIX] Fixing SQL statement when fetching episode from audio file URL (props Stef Pause)

= 1.10 =
* 2015-06-02
* [NEW] Correctly sanitising all settings (props Jake Spurlock)
* [NEW] Preparing SQL statement correctly (props Jake Spurlock)
* [NEW] Adding option to display latest episode in Single Episode widget
* [NEW] Making settings update notices dismissable in WordPress 4.2+
* [NEW] Adding 'please review' and 'thank you' text to footer of plugin settings pages

= 1.9.9 =
* 2015-05-08
* [TWEAK] Correcting URL in `<link>` tag in podcast RSS feed
* [TWEAK] Making sure enclosure URLs always have a valid file extension

= 1.9.8 =
* 2015-05-05
* [NEW] Adding `ssp_feed_channel_link_tag` filter to allow dynamic editing of RSS feed `<link>` tag content
* [TWEAK] Using correct feed URL for `<link>` tag in podcast RSS feed
* [TWEAK] Updating 'Feed for a specific series' in 'Publishing' tab to show correct URL structure
* [TWEAK] Making `$version`, `$token` and `$home_url` class variables public for easier access
* [FIX] Making sure feed checks for audio file MIME type on correct file
* [FIX] Correctly handling multibyte characters inside episode details in feeds

= 1.9.7 =
* 2015-04-28
* [TWEAK] Making sure that audio player and episode data do not display on protected posts until password has been entered
* [TWEAK] Updating 'Documentation' URL in plugin links

= 1.9.6 =
* 2015-04-22
* [TWEAK] Making sure that widget & shortcode audio player use correct file URL
* [FIX] Removing `ref` parameter from audio player file URL to ensure reliable playback

= 1.9.5 =
* 2015-04-20
* [FIX] Properly escaping URLs to account for recent security exposure in WordPress core
* [FIX] Removing URL escaping when saving audio file to episode - prevents stripping of spaces from file names

= 1.9.4 =
* 2015-03-31
* [TWEAK] Removing dynamic file size calculation from feed template to improve feed load times
* [TWEAK] Removing `iframe` tags from all feed content
* [TWEAK] Adding `ssp_audio_player` filter to allow alternative media players to be used
* [FIX] Adjusting `itunes:subtitle` feed tag content to never exceed 225 characters

= 1.9.3 =
* 2015-03-18
* [FIX] Fixing 'Audio player position' option to make sure the selected positioning works in all cases

= 1.9.2 =
* 2015-03-17
* [FIX] Fixing streaming through audio player as well as remote services (iTunes, etc.) by checking link referrer
* [TWEAK] Passing audio file referrer to download function and action hook

= 1.9.1 =
* 2015-03-16
* [TWEAK] Making sure that the audio player always receives a direct file reference (requires pretty permalinks)

= 1.9 =
* 2015-03-15
* [NEW] Adding ability to supply different feed details for each series so you can run multiple podcasts from one site
* [NEW] Adding 'Series' widget for displaying a list of episodes from a selected series
* [NEW] Adding 'Single Episode' widget for displaying single podcast episode
* [NEW] Adding `podcast_episode` shortcode for displaying single podcast episode
* [NEW] Adding 'Date recorded' field to episode details
* [NEW] Adding `ssp_hide_episode_details` filter to allow dynamic hiding of episode details
* [NEW] Creating new and improved permalink structure for series feeds
* [NEW] Adding 'view feed' link to feed details settings page
* [TWEAK] Further improving episode download links
* [TWEAK] Improving episode custom fields display
* [TWEAK] Updating publishing URLs to be more accurate for different permalink structures
* [TWEAK] Numerous code improvements and performance optimisations across the board

= 1.8.11 =
* 2015-03-05
* [TWEAK] Improving episode download links
* [TWEAK] Minor code reformat and optimisation

= 1.8.10 =
* 2015-02-24
* [TWEAK] Adding Jetpack Publicize support to `podcast` post type
* [TWEAK] Adding `$type` argument to `ssp_show_generator_tag` filter
* [TWEAK] Moving `podcast` post type and feed registration earlier to prevent potential conflicts with other plugins
* [TWEAK] Adding RSS2 head tags to podcast RSS feed
* [FIX] Fixing episode lookup when downloading audio file
* [FIX] Ensuring that only the current episode audio file can be accessed when downloading audio files
* [FIX] Fixing explicit flag in podcast RSS feed

= 1.8.9 =
* 2015-02-17
* [FIX] Including WordPress media functions when fetching audio file data dynamically - prevents broken XML feeds

= 1.8.8 =
* 2015-02-16
* [FIX] Making sure that episode details meta box shows for all selected post types

= 1.8.7 =
* 2015-02-16
* [TWEAK] Removing getID3 class and switching to WordPress' built-in functions for retrieving ID3 data instead
* [TWEAK] Improving retrieval of audio track length and file size
* [TWEAK] Adding sanity checks when fetching podcast post types to ensure no errors are returned
* [TWEAK] Adding global function for retrieving podcast post types to standardise and sanitise data
* [TWEAK] Adding plugin generator tag to site header to indicate usage and current version
* [TWEAK] Adding filter to hide WordPress SEO RSS footer embed on podcast feed if [my patch](https://github.com/Yoast/wordpress-seo/pull/1990) is accepted

= 1.8.6 =
* 2015-02-12
* [TWEAK] Changing priority of meta box saving function - fixes conflict with The Events Calendar

= 1.8.5 =
* 2015-02-09
* [FIX] Fixing undefined index error when using `ss_podcast` shortcode (kudos sharky44)
* [DOCS] Creating complete user and developer docs: http://docs.hughlashbrooke.com/

= 1.8.4 =
* 2015-02-03
* [FIX] Ensuring that podcast episode details metabox displays for all selected post types
* [FIX] Switching to `audio_file` meta field for episode audio files to prevent automatic audio file deletion
* [FIX] Updating all existing `enclosure` fields to new `audio_file` field
* [TWEAK] Clarifying descriptions of some settings
* [TWEAK] Improving display of settings fields
* [TWEAK] Moving widget registration logic into frontend class
* [TWEAK] Renaming a few filters for consistency
* [TWEAK] Indicating feed data placeholders more effectively

= 1.8.3 =
* 2015-01-22
* [NEW] Adding post tag taxonomy to `podcast` post type (in lieu of keywords removal)
* [TWEAK] Moving meta box setup to correct hook
* [TWEAK] Adding further measures to prevent WordPress from stripping out audio file when saving episodes
* [FIX] Preventing unformatted episode meta data from showing on excerpt (kudos Robert Neu)
* [FIX] Fixing non-object error when no post types are selected in settings (kudos Robert Neu)

= 1.8.2 =
* 2015-01-19
* [NEW] Adding episode featured image as episode image in RSS feed using `itunes:image` tag
* [TWEAK] Udating all episode queries (archives, etc.) to fetch episodes from all relevant post types
* [TWEAK] Ensuring all `podcast` posts are fetched on the frontend, even if they don't have an enclosure
* [TWEAK] Updating feed category selection to use drop-down menus
* [TWEAK] Setting RSS feed to only show number of posts specified in WordPress settings
* [TWEAK] Adding `ssp_feed_number_of_posts` filter to allow dynamic filtering of number of posts shown in RSS feed
* [TWEAK] Clarifying descriptions of some options
* [TWEAK] Removing 'Keywords' taxonomy as it is no longer supported by iTunes
* [TWEAK] Removing unneeded frontend CSS file (obsolete since v1.8)
* [FIX] Reinstating `ss_podcast` shortcode that was removed in v1.8
* [FIX] Fixing default copyright text in feed

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
* [TWEAK] Minifying Javascript

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
* [FIX] Fixed built-in archive page template
* [TWEAK] Restructured & streamlined settings page
* [TWEAK] Updated FAQs to reflect recent support queries

= 1.4 =
* 2013-03-13
* [NEW] Added option to password protect podcast feed - sets a 'HTTP 401 Unauthorized' header and requests a username and password
* [NEW] Added 'do_feed_podcast' action so plugins/themes can intercept the feed or add their own processing - see templates/feed-podcast.php for usage caveats
* [FIX] Fixed series feed URLs (please take note of changes on podcast settings page)
* [FIX] Fixed a few typos on the settings page
* [TWEAK] Added series feed URL to series taxonomy table for quick reference
* [TWEAK] Moved feed template include to latest possible hook - this allows other plugins to load their templates first if necessary
* [TWEAK] Simplified field descriptions on settings page
* [TWEAK] Added validation to podcast description field
* [TWEAK] Updated localisation strings
* [TWEAK] Updated plugin FAQ
* [TWEAK] Added 'Upcoming Features' list

= 1.3.4 =
* 2013-03-11
* [FIX] Fixed issue where site subtitle was being displayed in author field in feed

= 1.3.3 =
* 2013-03-09
* [TWEAK] Added `author` and `custom fields` to podcast episode edit page

= 1.3.2 =
* 2013-03-08
* [TWEAK] Added media player to podcast meta data for display when built-in templates are not being used

= 1.3.1 =
* 2013-02-28
* [FIX] Removed HTML tags from feed description/summary
* [TWEAK] Added comments capability to podcast episodes
* [TWEAK] Improved MIME type recognition
* [TWEAK] Improved plugin FAQ

= 1.3 =
* 2013-02-16
* [NEW] Added option to syndicate your feed through Feedburner (or similar service)
* [NEW] Added RSS meta tags to site header
* [NEW] Added option to show podcast episodes in main query loop on home page along with blog posts
* [TWEAK] Unified feed templates, so only one feed is used for all podcasting services (ensured backward compatibility for existing feed URLs)
* [TWEAK] Changed podcast settings page URL (menu link is still in same place though)

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
* [TWEAK] Moved settings page to be a sub-page of the Podcast menu
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
* [NEW] Added `keywords` taxonomy to episodes
* [TWEAK] General performance enhancements
* [TWEAK] Enhanced localisation support

= 1.0.1 =
* 2013-01-06
* [FIX] Fixing bug that broke media uploader in WordPress 3.5

= 1.0.0 =
* 2012-12-13
* Initial release

== Upgrade Notice ==

= 1.10.2 =
* v1.10 contains a number of important security fixes as well as some helpful new features - all users are advised to update ASAP.