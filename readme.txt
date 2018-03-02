=== Seriously Simple Podcasting ===
Contributors: PodcastMotor, psykro, simondowdles, hlashbrooke, whyisjake
Tags: podcast, audio, video, vodcast, rss, mp3, mp4, feed, itunes, podcasting, media, stitcher, google play, playlist
Requires at least: 4.4
Tested up to: 4.9.1
Requires PHP: 5.3.3
Stable tag: 1.19.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Podcasting the way it's meant to be. No mess, no fuss - just you and your content taking over the world.

== Description ==

[Seriously Simple Podcasting](https://castos.com/seriously-simple-podcasting) is an easy-to-use podcasting solution for WordPress that is as powerful as it is simple. It uses a native WordPress interface and has minimal settings so as not to distract you from what you really need to do - publish your content.

**Primary Features**

- Simple settings so you can get your podcast up and running quickly
- Integrated podcast hosting platform, [Castos](https://castos.com/ssp), which allows you to host your podcast media files on a dedicated platform, without ever having to leave the WordPress dashboard.
- Newly redesigned, customizable media player that includes your podcast cover image
- Run multiple podcasts from the same site - each with their own, unique RSS feed
- Gather thorough stats on your listeners using the [free stats add-on](https://wordpress.org/plugins/seriously-simple-stats/)
- Supports both audio *and* video podcasting
- New `podcast` post type and `series` taxonomy for managing your podcast episodes
- Use any post type for your podcast episodes
- Highly configurable and robust RSS feed designed for *all* podcast services and feed readers - including iTunes, Google Play and Stitcher
- Shortcodes & widgets for displaying podcast episode lists, single episodes and podcast playlists anywhere on your site
- The freedom to host your media files wherever you like - on the same site, our integrated Castos hosting platform, or any other server
- Complete user and developer [documentation](http://support.castos.com/)
- [Full i18n support](https://translate.wordpress.org/projects/wp-plugins/seriously-simple-podcasting)

**Podcast Hosting Platform**

If you're looking for a podcast hosting platform that is as simple as it is powerful check out [Castos](https://castos.com/ssp).  Our integrated podcast hosting platform allows you to upload your podcast audio files directly to a dedicated media host, without ever having to leave the WordPress dashboard.

https://youtu.be/Se3H1IDAYtw

Give your website a performance boost by offloading all of your media files to a dedicated hosting provider, and your podcast listeners a terrifc listening experience at the same time.

**Beautifully Designed Media Player**

No need to look around for a premium podcast player for your website, we've built in an industry leading podcast player right into Seriously Simple Podcasting, for Free!

Choose from a compact or full size design, complete with your podcast cover image proudly displayed right alongside your player.

**Where to find help**

Seriously Simple Podcasting comes with complete user and developer [documentation](http://support.castos.com/). Please read this documentation thoroughly before posting on [the support forum](https://wordpress.org/support/plugin/seriously-simple-podcasting).

**Add-ons**

Seriously Simple Podcasting comes with a growing [library of add-ons](https://castos.com/seriously-simple-podcasting/add-ons/). Just like the core plugin itself, **all of the add-ons are 100% free to use and will always remain that way**.

**How to contribute**

If you want to contribute to Seriously Simple Podcasting, you can [fork the GitHub repository](https://github.com/thecraighewitt/Seriously-Simple-Podcasting) - please read the [contributor guidelines](https://github.com/thecraighewitt/Seriously-Simple-Podcasting/blob/master/CONTRIBUTING.md) for more information on how you can do this.

**Help translate this plugin**

If you would like to contribute translations to this plugin you can do so through [a simple web interface](https://translate.wordpress.org/projects/wp-plugins/seriously-simple-podcasting). Any and all translations (new languages or updates to existing ones) are always welcome.

== Usage ==

Simply upload the plugin and you're good to go. Go to "Podcast > Add New" to add new episodes and go to "Podcast > Settings" to customise your podcast.

Podcast media files can be uploaded directly into WordPress, hosted on the integrated [Castos](https://www.castos.com) platform,  or hosted on any other site - in the latter case all you'll need to supply is the URL to the file. *Please note that episode lengths and file sizes can only be automatically calculated for files that are hosted on the same server as the website - either way though, you can input them manually.*

If you need help, you can find complete user and developer documentation [here](http://support.castos.com/).

== Installation ==

Installing "Seriously Simple Podcasting" can be done either by searching for "Seriously Simple Podcasting" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org
1. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Customise the plugin from the 'Podcast > Settings' page

== Frequently Asked Questions ==

= Where are the FAQs? =

You can find complete user and developer documentation (along with the FAQs) on [the Seriously Simple Podcasting documentation site](http://support.castos.com/).

== Screenshots ==

1. Media player and episode details displayed on the frontend above post content.
2. The only info that you need to add to each podcast episode.
3. Set up your podcast.
4. Customise your podcast feed.
5. Secure your podcast feed.
6. Handle redirection for your feed.
7. Publish your podcast.
8. The podcast admin menu location.
9. Podcast episodes are shown in the At a Glance dashboard widget.
10. Recent episodes widget.
11. Single episode widget.
12. Podcast series widget.
13. Podcast playlist widget.
14. An example of the styled podcast RSS feed when viewed directly in the browser.

== Changelog ==

= 1.19.6 =
* 2018-03-02
* [FIX] Sanitize file name on upload to Castos service
* [NEW] Add ss_player shortcode to embed html player within content via a shortcode.
* [NEW] Added a Feature/series graphic (props @timnolte)
* [UPDATE] Update ssp-shortcode-podcast_playlist, adds parameter "tracklist" to show the list of episodes below the player. (props @robertogcalle)

= 1.19.5 =
* 2018-02-09
* [TWEAK] Only load the HTML5 media player track when the user clicks play

= 1.19.4 =
* 2017-12-15
* [FIX] Fixed a bug where the single player widget was loading the incorrect html 5 player
* [FIX] Fixed a bug where the subscribe links aren't shown on the compact player
* [TWEAK] Iterate over various album art options for the HTML5 player, and use the first most appropriate one that is of square aspect ratio.
* [TWEAK] Deferred loading of scripts and styles for new HTML5 player to the end of the <body> element, and only if at least one HTML5 player instance is present on the page, to avoid unnecessary loading of scripts.
* [TWEAK] Updated podcast_episode shortcode to allow the use of a "style" shortcode attribute, with a value of "mini" or "large" to either use the compact native WordPress player or the new larger HTML5 media player.
* [TWEAK] Updated podcast_playlist shortcode to force use of default WordPress media player instead of the new HTML5 player until some minor bugs are ironed out.
* [NEW] Added ssp_include_episode_meta_data, ssp_include_podcast_subscribe_links and ssp_include_player_meta filters to give greater programmatic control over player meta data and subscribe links.

= 1.19.3 =
* 2017-12-08
* [FIX] Namespaced CSS classes for icons to avoid conflicts with themes using font frameworks
* [FIX] Fixed a bug where the player meta wasn't displayed on the classic player
* [TWEAK] New Player Enhancements - added additional filters to the new HTML5 player to allow developers / site owners more flexibility.

= 1.19.2 =
* 2017-12-07
* [FIX] Fixed a bug breaking sites on PHP versions older than 5.6

= 1.19.1 =
* 2017-12-06
* [FIX] Fixed a bug where you can't dismiss the import message

= 1.19.0 =
* 2017-12-06
* [NEW] Added a 1.19.0 upgrade notice
* [FIX] Increased width and height of new player album art to avoid 1px line under player wrapper
* [FIX] Fixed bug where default feed image was not showing for the album art if not series image was set
* [NEW] Added new HTML5 Media Player
* [NEW] Added support for featured images
* [FIX] Removed unnecessary dependencies
* [FIX] Fixed bugs related to podcast meta and subscribe links
* [TWEAK] Added a setting to enable/disable iTunes fields at episode level

= 1.18.2 =
* 2017-11-06
* [NEW] Added podcast_tags taxonomy
* [NEW] Added [WP Date Picker Styling](https://github.com/stuttter/wp-datepicker-styling) (props [timnolte](https://github.com/timnolte))
* [TWEAK] Hide itunes fields of not enabled

= 1.18.1 =
* 2017-10-25
* [FIX] Fixed subscribe links for episodes in a series

= 1.18.0 =
* 2017-10-09
* [NEW] Added new and updated iTunes tags to podcast feed as announced at WWDC2017
* [NEW] Added new and updated iTunes tags as episode settings in current meta box
* [NEW] Added new and updated iTunes tags as series specific settings podcast settings area
* [NEW] Added Stitcher and Google Play subscription links
* [TWEAK] Removed duration from beneath the player
* [TWEAK] Fixed the wording of the explicit checkbox (props [Ken Andries](https://github.com/Douglasdc3))

= 1.17.3 =
* 2017-08-29
* [FIX] Fixed a bug with the importer process sending the incorrect data format

= 1.17.2 =
* 2017-08-28
* UPDATE SUMMARY:  Updates and improvements for [Seriously Simple Hosting](http://app.seriouslysimplepodcasting.com/) as well as general plugin bug fixes
* [IMPROVEMENT] Improved the Seriously Simple Hosting import procedure
* [FIX] Fixed a bug where the file size and duration was not being returned correctly
* [FIX] Fixed a bug where the stored file size of podcasts was not displaying correctly on the podcast feed
* [NEW] Updated the plugin readme to display the new PHP version for the WordPress.org plugin repository
* [NEW] Add option for pubDate to respect "Date recorded" field per episode (props [Magnus Sjögren](https://github.com/magnus-sjogren))

= 1.17.1 =
* 2017-08-07
* [FIX] Fixed a bug causing problems uploading podcasts on regular Posts

= 1.17.0 =
* 2017-07-31
* UPDATE SUMMARY:  Improved file uploading for [Seriously Simple Hosting](http://app.seriouslysimplepodcasting.com/)
* [NEW] File uploads to Seriously Simple Hosting display a percentage indicator
* [FIX] Fixed a bug where attempting to upload large files to Seriously Simple Hosting caused the upload to time out
* [FIX] Fixed a bug related to the minimum PHP version for the plugin
* [FIX] Fixed a bug where the new uploading system does not return file size or duration

= 1.16.4 =
* 2017-06-21
* [FIX] Improved file uploads for [Seriously Simple Hosting](http://app.seriouslysimplepodcasting.com/)
* [FIX] Fixed a bug causing problems with tracking stats [Seriously Simple Stats](https://en-za.wordpress.org/plugins/seriously-simple-stats/)

= 1.16.3 =
* 2017-05-29
* UPDATE SUMMARY:  Bugfix release for backward compatibility
* [FIX] Fixed a parse error causing the plugin not to be activated
* [FIX] Fixed an error with the plugin upgrade process

= 1.16.2 =
* 2017-05-22
* UPDATE SUMMARY:  Adding backwards compatibility to PHP 5.3.3 and improving Feed Details functionality when Series are being used.
* [FIX] Incorporated an updated AWS library to be compatible with other S3 plugins such as Offload to S3
* [FIX] Added PHP compatibility back to PHP 5.3.3
* [FIX] Resolved error with Feed Details for Series not saving correctly
* [TWEAK] Added Dismiss button to Podcast Welcome Page
* [TWEAK] Cleaned up some legacy code from the 1.16 release

= 1.16.1 =
* 2017-05-08
* [NEW] Updated the plugin to display PHP version requirements to the user or gracefully stop the plugin from loading

= 1.16 =
* 2017-05-08
* [NEW] Added Support for [Seriously Simple Hosting](http://app.seriouslysimplepodcasting.com/)
* [CHANGE] PHP version 5.5.0 or greater now required for Seriously Simple Hosting support

= 1.15.2 =
* 2017-04-19
* [NEW] Adding iTunes url to Feed Details
* [NEW] Adding filter for the key that holds the mp3 value (props [Brian Hogg](https://github.com/brianhogg))

= 1.15.1 =
* 2016-10-06
* [FIX] Making sure that playlist episodes are only loaded if they have a valid media file

= 1.15 =
* 2016-10-05
* UPDATE SUMMARY: Adding podcast playlists as well as much greater flexibility when protecting your podcast feed
* [NEW] Podcast playlists! You can display a playlist using the ["Podcast: Playlist" widget](https://www.seriouslysimplepodcasting.com/documentation/playlist-widget/) or [the `podcast_playlist` shortcode](https://www.seriouslysimplepodcasting.com/documentation/podcast_playlist/)
* [NEW] You can now add custom access rules for your podcast feeds using the new `ssp_feed_access` filter
* [NEW] You can now choose to hide the media player from logged out users with a simple plugin setting (props [Matt Sephton](https://github.com/gingerbeardman))
* [NEW] [The `podcast_episode` shortcode](https://www.seriouslysimplepodcasting.com/documentation/podcast_episode/) now uses the current episode by default if no episode is specified
* [TWEAK] Rearranging files so that different function types are more easily findable
* [TWEAK] Improving customisability of all shortcodes
* [TWEAK] Updating review link for new plugin directory structure

= 1.14.10 =
* 2016-08-30
* UPDATE SUMMARY: Improving feed customisability and employing improved WordPress core functions
* [TWEAK] Adding loads of filters to the feed template, so all tags can now be modified dynamically
* [TWEAK] Registering all meta keys with the enhanced `register_meta()` function included in WordPress 4.6+

= 1.14.9 =
* 2016-07-21
* UPDATE SUMMARY: Improving accessibility and allowing for more frontend customisation.
* [TWEAK] Adding `download` attribute to episode download links (props [Chris Christoff](https://github.com/chriscct7))
* [TWEAK] Adding HTML tags and classes to episode meta details
* [TWEAK] Improving layout of episode details fields on edit screen for design and accessibility

= 1.14.8 =
* 2016-06-06
* UPDATE SUMMARY: Improving loading of episode details in dashboard to allow for more extensibility, as well as making sure that translations are applied correctly.
* [TWEAK] Improving generation of episode details fields on episode edit screen
* [FIX] Ensuring full localisation support works correctly

= 1.14.7 =
* 2016-06-02
* UPDATE SUMMARY: Improving file loading to prevent lag/timeouts in some podcast players as well as making sure that file names with spaces work correctly.
* [FIX] Ensuring that spaces in file names don't lead to broken links or unplayable files by correctly encoding them
* [FIX] Moving file content headers to prevent slow loading (and possible timeouts) in some podcast players (props [Mattias Geniar](https://github.com/mattiasgeniar))
* [FIX] Ensuring that audio file is always obtained correctly, even for legacy WordPress enclosures (props [Jake Spurlock](https://github.com/whyisjake))
* [TWEAK] Adding `ssp_feed_image` filter to RSS feed to allow dynamic filtering of main feed image (props [Rhys Wynne](https://github.com/rhyswynne))

= 1.14.6 =
* 2016-04-19
* UPDATE SUMMARY: Fixing feed validation errors and making sure that feeds can be viewed instead always being forced to be downloaded.
* [FIX] Removing unused tags from RSS feed items
* [FIX] Correcting RSS feed MIME type
* [TWEAK] Improving RSS feed XML formatting

= 1.14.5 =
* 2016-04-14
* [FIX] Fixing scope of `get_attachment_id_from_url` function reference - this was breaking file downloads and file size calculations
* [FIX] Fixing podcast cover image display on feed page

= 1.14.4 =
* 2016-04-06
* [TWEAK] Adding `customize_selective_refresh` flag to all widget options for WordPress 4.5+ compatibility
* [FIX] Updating file location function to properly allow media files to live outside of the WordPress directory (props [stoney](https://github.com/stoney))
* [FIX] Correctly finding media file size and setting content headers accordingly (props [Jake Spurlock](https://github.com/whyisjake) and [sharky44](https://github.com/sharky44))
* [FIX] Fixing `image` tag output in feeds - this was causing many feeds to be invalidated (props [Travis Northcutt](https://github.com/tnorthcutt))

= 1.14.3 =
* 2016-02-18
* [TWEAK] Making sure that the episode recorded date is internationalised correctly
* [TWEAK] Adding filters to feed item content, description and subtitle fields (see the [filter reference](https://www.seriouslysimplepodcasting.com/documentation/filter-reference/) for details)
* [FIX] Fixing dislay of ampersands in episode content in feeds

= 1.14.2 =
* 2016-02-17
* [TWEAK] Updating and improving admin field names, descriptions and labels
* [FIX] Making sure that the custom feed template file is taken from child theme if one exists (props [Justin Fletcher](https://github.com/justinticktock))
* [FIX] Making sure the file durations can be calculated for sites with WordPress installed in a different folder

= 1.14.1 =
* 2016-01-19
* [TWEAK] Updating available podcast categories to use the latest specified by iTunes
* [FIX] Removing erroneous `subtitle` tag from RSS feed
* [FIX] Adding Google Play namespace definition to RSS feed header

= 1.14 =
* 2016-01-19
* [NEW] Adding full support for video podcasting
* [NEW] Adding full support for the Google Play Podcast specification
* [NEW] Adding a stylesheet for RSS feeds to make them readable in the browser and more easily shareable
* [TWEAK] Correctly escaping HTML output in the dashboard (props [Danny van Kooten](https://profiles.wordpress.org/dvankooten/))
* [TWEAK] Optimising code for clarity and performance (props [Danny van Kooten](https://profiles.wordpress.org/dvankooten/))
* [TWEAK] Ensuring that the feed URL is always supplied correctly (props [Danny van Kooten](https://profiles.wordpress.org/dvankooten/))
* [TWEAK] Improving inline comments all round

= 1.13.3 =
* 2015-11-24
* [FIX] Preventing preloading of audio in player

= 1.13.2 =
* 2015-11-16
* [FIX] Making sure the 'Date Recorded' field is able to be populated and cleared correctly

= 1.13.1 =
* 2015-11-12
* [TWEAK] Adjusting episode recorded date display to be more human readable on the episode edit screen
* [TWEAK] Adding new URL structure specifically for the audio player file URLs (not publicly visible)
* [TWEAK] Updating episode cache to cache series queries separately
* [TWEAK] Updating plugin links in the plugin list table

= 1.13 =
* 2015-11-03
* [NEW] Adding option to mark feeds as complete using `itunes:complete` feed tag if no more episodes will ever be added to the feed
* [NEW] Adding `ssp_episode_meta_details` filter to allow episode meta data to be easily modified on the fly - [read the filter docs here](http://www.seriouslysimplepodcasting.com/documentation/filter-reference/#ssp-episode-meta-details)
* [NEW] Adding `ssp_show_audio_player` filter to allow dynamic control of audio player visibility - [read the filter docs here](http://www.seriouslysimplepodcasting.com/documentation/filter-reference/#ssp-show-audio-player)
* [NEW] Adding customisable episode embed code field to episode edit screen for easy copying (available in WordPress 4.4+)
* [TWEAK] Adding notice for FastCGI servers on Security settings page
* [TWEAK] Ensuring the episode update nonce never returns an 'undefined variable' error
* [TWEAK] Updating `itunes:explicit` feed tag with correct values as per new iTunes specification
* [TWEAK] Updating admin screen markup for WordPress 4.4+
* [TWEAK] Updating post type and taxonomy registration arguments for WordPress 4.4+
* [FIX] Making sure audio player does not show up in `ss_podcast` shortcode (issue introduced in v1.12.1)
* [FIX] Fixing iTunes episode descriptions to be the correct length and include HTML correctly

= 1.12.1 =
* 2015-10-27
* [TWEAK] Ensuring that episode meta data display is the same in all locations

= 1.12 =
* 2015-10-23
* [NEW] Added `manage_podcast` capability to allow editing of podcast settings (adds to Editors & Administrators by default)
* [NEW] Added podcast player and meta data to oEmbed excerpt in WordPress 4.4+
* [NEW] Added the ability to select up to three category/sub-category pairs for each feed
* [TWEAK] Removing localisation files as translations are now handled on translate.wordpress.org
* [TWEAK] Improving MIME type detection for audio files and adding caching for faster queries
* [TWEAK] Improving feed performance by removing duplicate actions performed on summary and excerpt text

= 1.11.3 =
* 2015-10-06
* [NEW] Adding series-specific feed tags to HTML head as it should be
* [NEW] Adding `ssp_show_global_feed_tag` and `ssp_show_series_feed_tag` filters to hide feed tags from HTML head
* [NEW] Adding German translation (props signor-rossi)
* [FIX] Making sure shortcodes do not appear in iTunes excerpt (props Jake Spurlock)

= 1.11.2 =
* 2015-10-01
* [NEW] Adding Russian translation (props SMXRanger)
* [FIX] Changing text domain to match plugin slug: `seriously-simple-podcasting`

= 1.11.1 =
* 2015-09-21
* [FIX] Fixing image upload on feed settings page

= 1.11 =
* 2015-09-16
* [NEW] Adding feed redirection option for individual series (props Jake Spurlock)
* [NEW] Adding 'play in new window' link to episode meta
* [NEW] Improving episode meta generation to make dynamic filtering much easier using the `ssp_episode_meta_details` filter
* [TWEAK] Adding caching to episode retrieval functions (props Jake Spurlock)
* [TWEAK] Updating settings sanitisation functions
* [TWEAK] Only return episode IDs when loading episode count for At a Glance widget (props Jake Spurlock)
* [TWEAK] Only run `wp_enqueue_media()` on post pages (props Jake Spurlock)
* [TWEAK] General syntax and coding standards updates
* [FIX] Properly exiting after retrieving audio files (props Jake Spurlock)

= 1.10.3 =
* 2015-07-13
* [NEW] Further sanitisation and escaping (props Jake Spurlock)

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

= 1.15.1 =
* v1.15 includes support for podcast playlists as well as much greater flexibility for protecting your podcast feed.
