0.1.0
Functional changes
- cloned from TorrentWatch-X 0.8.9 (https://code.google.com/p/torrentwatch-x/) to torrentwatch-xa 0.1.0 (https://github.com/dchang0/torrentwatch-xa/)
- changed footer to show gratitude for support from the community
- replaced Paypal donation account and fixed button so that it displays properly according to config setting
- replaced all references to torrentwatch and TorrentWatch-X with torrentwatch-xa
- split basedir into baseDir and webDir in order to put files in traditional Debian 7 paths, making it easier for future .deb packaging
    This is a BIG change; the split will probably break a lot of things that will be fixed over time.
    Splitting it up also required hardcoding paths in several places due to the poor use of libraries. These will be fixed soon for sure.
- renamed configDir to configCacheDir because the config held there is dynamic
- added default Transmission login to default config
- temporarily added season and episode labels for items that are detected as episodes or full seasons
- rewrote method of sanitizing titles to reduce errors
- added new default anime feeds: nyaa.se and tokyotosho.info (anime only)
- removed default feed ezRSS.it because they serve gzipped torrent files, for which there is currently no support
- improved error alert for "Error: No torrent file found on..." to clue user to possibility of gzipping
- removed default feed ThePirateBay HiRes Shows to reduce clutter
- changed favicon to new logo
- added logo at far left edge of top navbar and in footer (will revamp graphic design layout in future version)
- modified version check to use new URLs
- disabled Bug Report button
- added Bug Report link to footer
- added anime-style episode guessing, treating all anime episodes as part of season 1
    - fixed failure to match TV-style episodes introduced by anime-style episode guessing
    - added ability to detect specific resolutions (like 1280x720p, 720i, 1920x1080p, 480i) as certain qualities
    - rewrote resolution and quality detection
    - rewrote Season and Episode and Date detection
    - temporarily removed REPACK, PROPER, RERIP detection because of anime-style 01v2 and 03v3 repacks

Code changes
- replaced some quickie php tags like <?php echo key; ?> with proper full <?php print([HTML code]) ?> in global_config.tpl, favorites.tpl, and favorites_info.tpl
- changed Filter input field from type="text" to type="search" to gain Esc key functionality and removed old magnifying glass icon via CSS
- cleaned up typos discovered by IDE in several files
- typo cleanup inadvertently fixed torrent buttons in clientButtons
- add alt attributes to img tags
- removed ob_end_clean(); from several functions in torrentwatch-xa.php to fix "unreachable statement" IDE warnings, but this is a temporary cleanup
- fixed unquoted CURL-related constants in config_lib.php:get_curl_defaults()
- replaced existing variable name prefix of tw_ with twxa_ (will expand use of twxa_ prefix in future)
- used IDE source reformatting tools to clean up some files
- started new collection of "member" functions in twxa_parse.php
- removed get_item_filter(); started transition to allow user-defined Sanitize Characters and Separator Characters strings in config
- renamed some variables and functions according to Zend naming convention
- commented out old guess_match() and replaced with new, partially-completed detectMatch()
- completed commenting out $epiDiv (part of tvDB feature that was already commented out) to fix PHP notices

0.1.1
Functional changes
- renamed var/www/torrentwatch-xa-web to torrentwatch-xa, which means the URL changes
- fixed Delete Torrent button; it used to trash the downloaded file
- moved torrent list container down 6px in phone.css so that the filter bar no longer partially obscures it
- totally revamped detectResolution() to detect ###x### ####x### or ####x#### and check it for aspect ratios
- added Enhanced Definition TV resolution 576i and 576p
- temporarily removed font size setting because it only changes the font size of the Configuration UI
- added removal of audio codecs before episode detection (AC3 is being seen as an episode number)
- improved removal of all-numeric 8-digit checksums
- revamped Season and Episode detection to improve performance by focusing on "low-hanging fruit"
  - detection engine now counts occurrences of numbers in title and divides actions into groups by frequency of numbers
    - benefit is that we can go after standalone anime-style episodes sooner with fewer mistakes
    - also improves match debug output by grouping them together
  - added many new pattern detections
    - NOTE: Roman numeral Season I, II, or III - Arabic numeral Episode causes slightly counter-intuitive behavior in Favorites filters
  - improved detection of non-delimited dates such as YYYYMMDD, YYMMDD and so on
  - added conversion of fullwidth numerals to halfwidth for Japanese
  - added episode version (includes PROPER, REPACK, RERIP as version 2)
  - added detection of abbreviated years like '96
  - added $numberSequence to handle parallel numbering sequences like Movie 1, Movie 2, Movie 3 alongside Episode 1, Episode 2, Episode 3
  - added $detectedMediaType as groundwork for handling other media types than video
- removed The Pirate Bay from default feeds as they no longer offer RSS
- removed BT-Chat from default feeds as they were shut down

Code changes
- fixed undefined $showEpisodeNumber in feed_item.tpl
- removed undefined and unused $eta in feed_item.tpl
- fixed undefined $status
- renamed $torStop to $torPause
- renamed .torStop to .torPause
- renamed div.delete to div.torDelete
- renamed div.trash to div.torTrash
- added twxa_test_parser.php wrapper to make it easier to diagnose mismatches
- renamed _debug.php() to twxa_debug()
- added strtolower() for resolution matches so that 1080P becomes 1080p and so on
- moved <div> tag out of $footer to match its closing </div>
- single-quoted array keys in feeds.php:443
- renamed detectMatch()=>'key' to detectMatch()=>'title'
- renamed detectMatch()=>'data' to detectMatch()=>'qualities'
- added detectMatch()=>'isVideo'
- moved normalizing of codecs into normalizeCodecs()
- added debug logging of Season and Episode detection into /tmp/twlog
- changed Full Season $detectedEpisodeBatchEnd = 0 to $detectedEpisodeBatchEnd = '' for Preview episode 0 numbering
- refactored detectSeasonAndEpisode() to detectItem()
- added a simple pseudo-unit-tester for the parsing engine called twxa_test_parser.php
- refactored (renamed) $title to $ti throughout
- refactored (renamed) $separators to $seps throughout
- refactored (abbreviated) $detectedSeason... to $detSeas... throughout
- refactored (abbreviated) $detectedEpisode... to $detEpis... throughout
- refactored (abbreviated) $detected... to $det... throughout
- fixed missing mediaType in detectItem() return
- fixed (changed) $matches[1][] to $matches[0][] in twxa_parse.php

0.2.0

Functional changes
- fixed bug where "Download and seed ratio met" items return to the Downloading filter if browser is refreshed (torrentwatch-xa.js:588)
- replaced incorrect Start Torrent tor_start_10x10.png icon (had resume icon instead of start)
- moved Donate button from Paypal's website to local file
- designed new Move File tor_move_20x20.png and tor_move_10x10.png buttons (seen only in the Transmission filter's button bar)
- added Auto-Delete Seeded Torrents to automatically delete completely downloaded and seeded torrents from Transmission, leaving behind just the torrent's contents
- updated Transmission icon with latest official design
- fixed but temporarily removed Episodes Only toggle from config panel (entire concept of Episodes needs to be reworked now that print media can be faved)
- removed all NMT and Mac OS X support (only supported OS is currently Debian 7.x)
- fixed feed section header and filter button match counts so that they obey the selected filter
- added horizontal scrolling capability to History panel for long titles

Code changes
- cleaned up variable declarations and isset checks in feed_item.tpl
- fixed missing semicolons and curly braces throughout torrentwatch-xa.js (appears to have fixed browser crashes)
- commented out Javascript function and its call for changing font size in config panels
- added missing curly braces in various PHP files
- cleaned up typos in various places
- removed redundant logic in Update/Delete button Javascript and prepared for future "Update button pins panel" functionality
- commented out $oldStatus in torrentwatch-xa.js as it is not used

0.2.1

Functional changes
- stripped season and episode data from title when using Add to Favorites button in toolbar
- added AVI to video qualities list

Code changes
- restructured most of huge if...else if...else control structure into switch...case in detectItem(), breaking out code into separate functions
- added missing braces to if blocks in cache.php
- removed $platform, $config_values where unused in its scope from config_lib.php
- removed unused $exec variable and related lines from torrentwatch-xa.php
- removed $html_out where unused in its scope from torrentwatch-xa.php
- fixed Undefined offset: 1 on line 24 of torrentwatch-xa.php by changing append to assign
- suppressed error message Undefined offset: 1 on line 106 of feeds.php
- fixed Undefined variable: any on line 223 of feeds.php and line 318 of tor_client.php by adding !isset($any) || logic
- fixed No such file or directory on line 269 of config_lib.php with file_exists() check
- fixed Undefined variable: response on line 345 of config_lib.php with isset() check

0.2.2

Code changes
- moved /var/www/torrentwatch-xa to /var/www/html/torrentwatch-xa to match change from Debian 7.x to Debian 8.x
  - corrected default get_webDir() corresponding to above move

0.2.3

Functional changes
- consolidated and improved color coding in feed list, Transmission list, and Legend
- improved Legend verbiage
- added back Transmission label to Web UI button
- added hide/show UI elements depending on browser window width (does not apply to when phone.css and tablet.css are used)
- shortened text in footer for narrow screens
- lightened tints of alternating list rows
- removed cutesy icons except Transmission's from main button bar and squared off each button's corners

Code changes
- removed all code related to the unused "Report Bug" feature that was cloned from TorrentWatch-X
- fixed typo in e.stopImmediatePropagation() at torrentwatch-xa.js:1242

0.2.4

Functional changes
- made Default Seed Ratio global setting be the default seed ratio for the blank New Favorite form
- minor edits to Favorite Info template's help text
- added ability to use SSxEE or YYYYMMDD notation to Last Downloaded Episode on the New Favorite form
- added FeedBurner aggregator of other large anime torrent RSS feeds to default config

Code changes
- cleaned up CSS warnings caused by filter:progid: entries (for IE8--IE8 support should be removed in the future)
- removed overlooked CSS background tags referring to missing favorites.png
- removed switch-case for torrent client, since Transmission is the only supported client
- fixed Undefined variable: magnet in tor_client.php on line 217
- completely removed mostly-useless torInfo() in tools.php, leaving the infoDiv updates to Javascript
- added some twxa_debug() logging to feeds.php
- fixed minutes declarations and calculations in torrentwatch-xa.js
- improved logic for creating or recreating Download Dir and made it only attempt to do either if Transmission Host is 127.0.0.1 or localhost
- removed $func_timer because it does nothing
- moved /tmp/twlog to /tmp/twxalog

0.2.5

Functional changes
- added leading zero to hour in History list timestamps so that it matches the Feed list timestamps and titles line up

Code changes
- fixed recap episode decimal numbering #.5 as in "HorribleSubs 3-gatsu no Lion - 11.5 480p.mkv", which becomes 11x5 under debug 3_25-1 due to \-? regex
- fixed bug where Title - 1x01 480p.mkv leaves - and . in Favorites title when added using Add Favorite.
- fixed regex bug where Filter field ending in an exclamation point never matches, as with New Game!
- removed unused $argv in rss_dl.php
- added missing braces to some conditional blocks in several files
- fixed PHP Notice:  Undefined index: Feed in /var/lib/torrentwatch-xa/lib/feeds.php on line 183
- fixed PHP Notice:  Undefined index: Filter in /var/lib/torrentwatch-xa/lib/feeds.php on line 207

0.2.6

No functional changes this release

Code changes
- completely cleaned up update_hidelist.php and moved its contents into update_hidelist() in torrentwatch-xa.php
- completely cleaned up twxa_test_parser.php
- deleted useless $platform global variable and platform_initialize()
- merged platform.php into config_lib.php 
- refactored platform_getConfigFile(), platform_getConfigCache(), and platform_get_configCacheDir(), removing platform_ prefix from each
- deleted unused and obsolete cleanup.sh
- refactored setup_rss_list_html() into start_feed_list()
- refactored show_feed_html() into show_feed_list()
- refactored close_feed_html() into close_feed_list()
- refactored show_down_feed() into show_feed_down_header()
- refactored show_torrent_html() into show_feed_item()
- converted global $html_out in html.php to parameters
- renamed html.php to twxa_html.php
- removed $normalize toggle from detectMatch
- call detectMatch() only once between show_feed_item() and its call to templates/feed_item.tpl to improve performance
- merged guess.php into twxa_parse.php
- fixed: after clearing all caches, PHP Warning:  preg_match(): No ending delimiter '^' found in /var/lib/torrentwatch-xa/lib/tor_client.php on line 376 (377)
- upgraded jquery to latest 1.12.4, following 1.9 upgrade guide http://jquery.com/upgrade-guide/1.9/#live-removed and using JQuery Migrate 1.4.1 plugin
- upgraded jquery.form.js from 2.43 to 4.2.1 minified per https://github.com/jquery-form/form
- fixed: PHP Notice:  Undefined offset: 1 in /var/lib/torrentwatch-xa/lib/twxa_parse.php on line 387
- fixed: PHP Notice:  Undefined variable: idx in /var/lib/torrentwatch-xa/lib/config_lib.php on line 551
- refactored guess_feedtype() to guess_feed_type() and cleaned it up
- set default return value of guess_feed_type() back to "Unknown" for add_feed() to properly handle bad feeds, bypassing the following errors:
    - PHP Notice:  Undefined index: http://eztv.ag/ in /var/lib/torrentwatch-xa/lib/config_lib.php on line 538
    - PHP Notice:  Undefined index: http://eztv.ag/ in /var/lib/torrentwatch-xa/lib/feeds.php on line 500
- cleaned up add_feed() and fixed: PHP Notice: Only variables should be passed by reference in /var/lib/torrentwatch-xa/lib/config_lib.php on line 532
- fixed: PHP Notice: Only variables should be passed by reference in /var/lib/torrentwatch-xa/lib/config_lib.php on line 421
- refactored update_feedData() to update_feed_data()
- renamed NMT-mailscript.sh to example-mailscript.sh and improved its comments,
- hid the Configure > Other > Script field since it is read-only
- upgraded PHPMailer from 5.2 to 5.2.23, but not using any of the SMTP auth features yet
- cleaned up all the undefined variable PHP notices that occur when Configuration is saved:
  - PHP Notice:  Undefined index: combinefeeds in /var/lib/torrentwatch-xa/lib/config_lib.php on line 330
  - PHP Notice:  Undefined index: epionly in /var/lib/torrentwatch-xa/lib/config_lib.php on line 330
  - PHP Notice:  Undefined index: require_epi_info in /var/lib/torrentwatch-xa/lib/config_lib.php on line 330
  - PHP Notice:  Undefined index: dishidelist in /var/lib/torrentwatch-xa/lib/config_lib.php on line 330
  - PHP Notice:  Undefined index: hidedonate in /var/lib/torrentwatch-xa/lib/config_lib.php on line 330
  - PHP Notice:  Undefined index: savetorrents in /var/lib/torrentwatch-xa/lib/config_lib.php on line 330
- fixed fatal typo bugs in MailNotify()
- fixed: when deleting a downloaded torrent from the Transmission list, PHP Notice:  Undefined index: trash in /var/www/html/torrentwatch-xa/torrentwatch-xa.php on line 67
- renamed TODO to TODO.md since it was already in Markdown syntax
- refactored mailonhit to emailnotify
- changed 'TimeZone' to 'Time Zone' and 'TZ' to 'tz' in $config_values['Settings']
- moved Time Zone: field from Configure > Other to Configure > Interface
- changed 'Email Address' label to 'To: Email Address' in Configure > Other
- fixed: after clearing all caches and refreshing, PHP Notice:  Undefined index: torrent-added in /var/lib/torrentwatch-xa/lib/tor_client.php on line 173
- fixed: PHP Notice:  Undefined property: lastRSS::$rsscp in /var/lib/torrentwatch-xa/lib/lastRSS.php on line 115 by adding private member $rsscp to lastRSS class
- cleaned up lastRSS.php a bit
- deleted curl.php because it is a buggy replacement for PHP's built-in curl support (php5-curl package on Ubuntu)
- changed CURLOPT_ constants in get_curl_defaults() from strings to integers to conform to spec

0.3.0

Functional changes
- added check for mb_convert_kana()
- renamed Configure > Other to Configure > Trigger
- added SMTP authentication options to Configure > Trigger for email notifications
- added Script option to Configure > Trigger for shell scripts triggered by downloads or errors
- commented out soft-hyphen insertions

Code changes

- cleaned up logic in transmission_add_torrent() to match current Transmission RPC spec
- improved twxa_debug() logic
- add ERR:, INF:, and DBG: keywords to every twxa_debug() message for easier grep
- add verbosity setting to all twxa_debug() calls (but twxa_debug() doesn't yet hide messages by verbosity--it is DBG all the time)
- removed timer_init() and replaced it with timer_get_time(0)
- minor cleanup in client_add_torrent()
- fixed: PHP Notice:  Undefined index: ... in /var/lib/torrentwatch-xa/lib/feeds.php on line 498
- fixed annoying bug where browser thinks Cmd is still depressed after switching to browser using Cmd-Tab on Mac OS X, EXCEPT in the rare case of rotating all the way through the running apps list back to the browser (without switching focus away from the browser)
- slightly improved default seed ratio limit logic
- switched back to global $html_out--CPU util is somewhat improved with this change
- attempted to improve CPU util by switching preg_ functions to strreplace() in sanitizeTitle()
- converted some preg_ functions to str functions

0.3.1

Functional changes

- Downloaded filter now includes match_old_download (Cached Download, dark grey) items
- Downloaded filter total count includes match_old_download items
- temporarily added back item classes to the Legend for diagnostic purposes
- added infoDiv to downloading or downloaded items in the filters other than Transmission
- removed infoDiv from completed items in the filters other than Transmission
- deleting via context menus removes infoDiv from and changes state of removed torrent items immediately
- removed $test_run and match_favReady functionality (in the browser, favorites start downloading immediately on page load, rather than going into Ready state)
- removed Verify Episode option, since we don't want to re-download anything already in the download cache

Code changes

- cleaned up all but two functions in rss_dl_utils.php
- renamed rss_dl_utils.php to twxa_rss_dl_tools.php
- made a few more preg_ to str_ conversions to improve performance
- cleaned up default settings in config_lib.php
- changed match_season to match_favBatch in preparation for capability of downloading batches automatically
- changed match_test to match_favReady for clarity
- changed match_match to match_favStarted for clarity
- changed match_to_check to match_waitTorCheck for clarity
- renamed clear_cache_real() to clear_cache_by_feed_type()
- renamed clear_cache() to clear_cache_by_cache_type()
- renamed cache_setup() to setup_cache()
- renamed cache.php to twxa_cache.php
- cleaned up check_cache() and check_cache_episode() logic a bit
- renamed perform_feeds_matching() to process_all_feeds()
- renamed load_feeds() to load_all_feeds()
- renamed rss_perform_matching() to process_rss_feed()
- renamed atom_perform_matching() to process_atom_feed()

Next Version

Functional changes

- temporarily hid Configure > Favorites > Download PROPER/REPACK setting in preparation for itemVersion numbering system


IN PROGRESS

- started switchover from detectMatch() to detectItem() season and episode output
- why do some items that are in the download cache not get marked properly within PHP upon browser refresh?
- 'Only Newer' checks the episode number and compares with the Favorite record--why would we want to download anything but the newest?
- partially added ability to auto-download batches as long as one episode in a batch is newer than the Favorite's last downloaded episode

Code changes

- switched from detectMatch() to detectQualities() in add_hidden() and rss_dl.php
- switched from detectMatch() to detectItem() in check_for_torrent(), get_deep_dir()

IN PROGRESS

- fix Quality filtering in check_for_torrent() before checking the download cache

- context menu on items in the Transmission filter should not close automatically after a few seconds have passed

- minor changes to detectMatch() batch detection and batch notation in preparation for matching batches and full seasons

- fix boolean use of detectMatch() now that it returns an array