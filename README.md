![torrentwatch-xa TWXA logo](http://silverlakecorp.com/torrentwatch-xa/torrentwatch-xa-logo144.png)

torrentwatch-xa
===============

torrentwatch-xa is a fork of Joris Vandalon's TorrentWatch-X automatic episodic torrent downloader with the _extra_ capability of handling anime fansub torrents that do not have season numbers, only episode numbers. It will continue to handle live-action TV episodes with nearly all season + episode notations.

To restrict the development and testing scopes in order to improve quality assurance, I am focusing on Debian 7.x LINUX as the only OS and on Transmission as the only torrent client.

In the process of customizing torrentwatch-xa to fit my needs and workflow, I'll:

- fix some bugs
- refactor some code
- add some features, mostly UI and workflow improvements
- let some features languish or remove them outright, especially buggy/unreliable portions of the code
 
The end goal is for torrentwatch-xa to do only what it's supposed to do and do it well. Over time, this will mean that broken or aging features will probably be removed rather than repaired. While such features still work, they will remain.

Status and Announcements
===============

CURRENT VERSION: I've posted 0.1.1 with the changes listed in CHANGELOG. This version has a brand new season and episode detection engine that first counts the number of numbers in the title and uses that to improve pattern matching. This should improve accuracy and performance. One design decision this allows me to make is to give up on titles containing large numbers of numbers because they are too confusing to the parser. This behavior is preferable to getting a false-positive match.

I am sure the new detection engine will introduce its own share of bugs, but it has largely worked well over the past several months in testing.

NEXT VERSION: 0.2.0 in progress, with focus on minor changes in features/functionality such as automatic deletion of completely downloaded and seeded torrents. Will require re-prioritization of TODOs because there are so many.

Known bugs are tracked primarily in the TODO and CHANGELOG files. Tickets in GitHub Issues will remain separate for accountability reasons and will also be referenced in the TODO and CHANGELOG.

Design Decisions
===============

"One man's bug is another man's feature."

It's become obvious that there are situations for which a mutually-exclusive design decision cannot be avoided. For example, the title "Holly Stage for 50 - 3" is meant to be interpreted as title = "Holly Stage for 50" and episode number 3, with season 1 implied.
(Fans know that "Holly Stage for 50 - 3" really should be read as title = "Holly Stage for 49", season 2, episode 3, to further complicate matters.)
But the engine currently reads it as title = "Holly Stage for" and season 50, episode 3. Why? Because it was determined that the ## - ## pattern much more often means SS - EE.

Sadly, because the engine was forced to make the choice, fans of "Holly Stage for 50" must "hack" the Favorite to get it to download properly. There is no way to solve this problem without referring to some centralized database of anime titles or relying on some sort of AI, neither of which are going to happen in torrentwatch-xa any time soon.

Tested Platforms
===============

torrentwatch-xa is developed and tested on an out-of-the-box install of Debian 7.8 x86_64 with its out-of-the-box transmission-daemon, Apache2, and PHP5.4 packages. I have tested it using the local transmission-daemon as well as a remote transmission-daemon running on a separate NAS on the same LAN.

I do not plan on testing on Debian 8.x yet. It will probably work fine without any changes to torrentwatch-xa.

Nearly all the debugging features are turned on and will remain so for the foreseeable future.

Be aware that I rarely test the GitHub copy of the code; I test using my local copy, and I rarely do wipe-and-reinstall torrentwatch-xa testing. So it is possible that permissions and file ownership differences may break the GitHub copy without my knowing it.

The last wipe-and-reinstall test of the GitHub copy occurred with torrentwatch-xa 0.1.1 on Debian 7.8 x86_64 on 2015-06-01 and was a success.

Prerequisites
===============

The following packages are provided by the official Debian 7.x wheezy repos:

- transmission-daemon
- apache2
- php5 (currently PHP 5.4)

Installation
===============

Installation is fairly straightforward.

- Start with a Debian 7.x installation. (It can run with none of the tasksel bundles selected, but I typically choose only "SSH Server" and "Standard System Utilities".)
- `sudo apt-get install apache2 php5 transmission-daemon`
- Set up the transmission-daemon (instructions not included here) and test it so that you know it works and know what the username and password are. You may alternately use a Transmission instance on another server like a NAS.
- Use git to obtain torrentwatch-xa (or download and unzip the zip file instead)
  - `sudo apt-get install git`
  - `git clone https://github.com/dchang0/torrentwatch-xa.git`
- Copy/move the folders and their contents to their intended locations:
  - `mv ./torrentwatch-xa/var/www/torrentwatch-xa /var/www`
  - `mv ./torrentwatch-xa/var/lib/torrentwatch-xa /var/lib`
- Allow apache2 to write to the three cache folders.
  - `chown -R www-data:www-data /var/lib/torrentwatch-xa/*_cache`
- Set up the cron job by copying the cron job script torrentwatch-xa-cron to /etc/cron.d with proper permissions for it to run.
  - `sudo cp ./torrentwatch-xa/etc/cron.d/torrentwatch-xa-cron /etc/cron.d`
  - (optional) `chmod 755 /etc/cron.d/torrentwatch-xa-cron`
- Restart apache2
  - `sudo service apache2 restart`
- Open a web browser and visit `http://[hostname or IP of your Debian instance]/torrentwatch-xa`
- You may see error messages if apache2 is unable to write to the three cache folders. Correct any such errors.
- Use the Configure panel to set up the Transmission connection.
  - It may be necessary to restart Transmission to get torrentwatch-xa to connect.
    - `sudo service transmission-daemon restart`
  - It may also be necessary to reconfigure Transmission (not described here) to get it to work.
- You should already see some items from the default RSS feeds. Use the Configure panel to set up the RSS or Atom torrent feeds to your liking.
- Use the Favorites panel to set up your automatic downloads.
  - Be aware that your favorites may appear to not work if they are configured to be too stringent a match.
  - For instance, when using the "heart" button in the button bar to add a favorite, it currently (as of 0.1.1) copies over all the video qualities and the season + episode number, making it fail to match the very item used to create the favorite! Edit the favorite to cast a wider net:
    - Change the Qualities field to `All`
    - Remove the season and episode number from the title in the Filter field.
    - Remove the Last Downloaded Episode values if present.
    - Click the Update button to save the changes to the favorite.
    - Then, empty all caches and refresh the browser to trigger the match and start the download.
- Wait for some downloads to happen automatically or start some manually.
- Enjoy your downloaded torrents!

Credits
===============

The credits may change as features and assets are removed.

- Original TorrentWatch-X by Joris Vandalon
- Original Torrentwatch by Erik Bernhardson
- Original Torrentwatch CSS styling, images and general html tweaking by Keith Solomon http://reciprocity.be/
- Some of the icons were made by David Vignoni and are from Nuvola-1.0 available under the LGPL http://icon-king.com/
- Backgrounds and CSS Layout are borrowed from Clutch http://www.clutchbt.com/
- I have stumbled upon some credits embedded in various files that were put there by prior coders and that will not be re-listed here.