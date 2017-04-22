![torrentwatch-xa twxa logo](http://silverlakecorp.com/torrentwatch-xa/torrentwatch-xa-logo144.png)

torrentwatch-xa
===============

torrentwatch-xa is an actively-developed fork of Joris Vandalon's abandoned TorrentWatch-X automatic episodic torrent downloader with the _extra_ capability of handling anime fansub torrents that do not have season numbers, only episode numbers. It will continue to handle live-action TV episodes with nearly all season and episode numbering styles.

![torrentwatch-xa twxa ScreenShot 1](http://silverlakecorp.com/torrentwatch-xa/twxaScreenShot1.png)

I resurrected TorrentWatch-X because I could not make Sick Beard-Anime PVR handle anime episode numbering styles well enough for certain titles, and the TorrentWatch-X UI is far easier to use and understand for both automated and manual torrent downloads. When I forked TorrentWatch-X at version 0.8.9, it was a buggy mess, but over years of testing and development, torrentwatch-xa has proven to be the excellent set-it-and-forget-it PVR that TorrentWatch-X was always meant to be.

Without getting caught up in the feature race that other torrent downloaders seem to be stuck in, the goal is for torrentwatch-xa to do only what it's supposed to do and do it well. In the end, what we all really want is to come home to a folder full of automatically-downloaded live-action shows, anime, manga, and light novels, ready to be viewed immediately.

Status
===============

### Current Version

I've posted 0.3.1 with the changes listed in [CHANGELOG.md](CHANGELOG.md). This time I chose to finally address some small cosmetic or superficial UI bugs, including fully-Downloaded items not showing up in the Downloaded filter, progress bar and infoDiv misbehavior outside of the Transmission filter, and some of the item states that don't survive a browser refresh. Not all of the UI bugs have been fixed just yet; they will be fixed in small, controlled batches.

### Next Version

I hope to:

- continue cleaning up the item states so that they all properly survive browser refreshes
- improve the core matching process and improve performance by reducing number of calls to the parsing engine

Known bugs are tracked primarily in the [TODO.md](TODO.md) and [CHANGELOG.md](CHANGELOG.md) files. Tickets in GitHub Issues will remain separate for accountability reasons.

Tested Platforms
===============

torrentwatch-xa is developed and tested on Ubuntu 14.04.5 LTS with the prerequisite packages listed in the next section. For this testbed transmission-daemon is not installed locally--a separate NAS on the same LAN serves as the transmission server. The UI works on pretty much any modern web browser that has Javascript enabled, including smartphone and tablet browsers.

torrentwatch-xa should work without modifications on an out-of-the-box install of Debian 8.x x86_64 or Ubuntu 14.04.x, although I am only actively testing on Ubuntu 14.04.x with PHP 5.6.

Nearly all the debugging features are turned on and will remain so for the foreseeable future.

Be aware that I rarely test the GitHub copy of the code; I test using my local copy, and I rarely do wipe-and-reinstall torrentwatch-xa testing. So it is possible that permissions and file ownership differences may break the GitHub copy without my knowing it.

Prerequisites
===============

### Ubuntu 14.04 and Debian 8.x

From the official repos:

- transmission-daemon
- apache2 (currently Apache httpd 2.4.10)
- php5 (currently PHP 5.6)
- php5-json
- php5-curl

Installation
===============

See [INSTALL.md](INSTALL.md) for detailed installation steps.


Troubleshooting
===============

See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for detailed troubleshooting steps and explanations of design decisions and common issues.


Credits
===============

- Original TorrentWatch-X by Joris Vandalon https://code.google.com/p/torrentwatch-x/
- Original Torrentwatch by Erik Bernhardson https://code.google.com/p/torrentwatch/
- Credits for the PHP and Javascript libraries are inside of their respective files.