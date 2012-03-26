=== wordTube ===
Contributors: Alex Rabe
Donate link: http://alexrabe.de/donation/
Tags: flash, swf, swfobject, video, youtube, media, mp3, images, admin, ads
Requires at least: 2.8
Tested up to: 3.1

wordTube is your Media Center plugin for WordPress.

== Description ==

This plugin manages the JW FLV MEDIA PLAYER 5.1 and makes it easy for 
you to put music, videos or flash movies onto your WordPress posts and pages. 
Various skins for the JW PLAYER are available via www.longtailvideo.com 

wordTube supports the streaming video format (Format .flv or .swf), 
sound files as MP3 and JPG, GIF or PNG grafic files. 
With wordTube you can simply insert it into your blog with the 
tag [media id="media id"] or as playlist [playlist id="playlist id"].

Since Version 2.0 it supports LongTail's AdSolution which allows you 
to run pre-roll, overlay mid-roll, and post-roll advertisements in your media player.
[Click here to sign up for LongTail](http://www.longtailvideo.com/referral.aspx?page=landingpage&ref=grwihxmxxldugmq "LongTail Adsolution")

You don't need to change the WYSIWYG mode or special HTML knowledge 
to insert such a tag. You can show a simple media file (i.e. a flv Format) 
or a playlist of all your media files..

== Credits ==

Copyright 2006-2011 Alex Rabe & Alakhnor

The wordTube button is taken from the Silk set of FamFamFam. See more at 
http://www.famfamfam.com/lab/icons/silk/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

== Installation ==

1. 	Upload the files to wp-content/plugins/wordtube

2.  Go to the web page http://www.longtailvideo.com and download the JW FLV Media Player 5.0 or higher and unpack the content

3.	Upload the file player.swf and yt.swf to the WordPress upload folder

4. 	Activate the plugin

5.	Upload a .flv video file or an mp3 sound file to the WordPress upload folder or use the Upload form	at Manage -> wordTube

6.	Optional you can upload a image to show a preview of the video/mp3 file

7. 	Go to your post and enter tag [media id="media id"] or [playlist id="playlist id"].

That's it ... Have fun

== Screenshots ==

1. Screenshot of the Media Center Area
2. Standard Playlist output
3. Single Media output
4. Playlist on the rigth side
5. Controls off (with Mouse over effect)

== Usage == 

1.	Configure wordTube: go to admin panel -> Options -> wordtube.

2.	Add media: Go to admin panel -> Manage -> Media Center. Click on 'Insert new media file'. Fill in the informations then submit with the submit button.

3.	Add playlist: Go to admin panel -> Manage -> Media Center. Click on 'Edit' (next to 'Playlist Preview'). Fill in the informations then submit with the submit button.

4.	Insert media or playlist using editor button: on the edit post screen, click on the wordTube button then select the element to insert.

5.	Manual insert of media: type in [media id="media id"] wherever you want to display the media. Example: [media id=1]

6.	Manual insert of playlist: type in [playlist id="playlist id"] wherever you want to display the playlist. Example: [playlist id=1]

7. 	Playlist order: to gain control over playlist order, go to admin panel -> Manage -> Media Center. Select the playlist you want to order in the dropdown menu of the filter part of the screen. Click on order field for each media to choose order, enter to save.

== Changelog == 

= V2.4.0 / 09.01.2011 = 
* Added sort ordering of playlist media items (THX to Lance Mitchell)
* Adding wpdb->prepare (THX to Peter Lowe)

= V2.3.1 / 05.04.2010 =
* Bugfix for settings , smoothing not saved
* Correct error message when player not found
* Adding correct excerpt filters

= V2.3.0 / 04.04.2010 =
* Added JW Media Player 5.1
* Updated settings for V5.0
* Move player file outside the plugin folder
* Updated Longtail Ads Implementation
* Remove direct callback for playlist and statistic
* Added new filter
* Minor UI updates
* Rework Widget for new WP2.8 class structure
* Bugfix for register_taxonomy under WP3.0
* Remove various PHP notices messages

= V2.2.2 / 19.07.2009 =
* Bugfix : Counter not added to database (THX to Karsten)
* Bugfix : AdsCode not overwritten during upgrade

= V2.2.1 / 04.06.2009 =
* Added JW Media Player 4.4
* Ready for WordPress 2.8
* Bugfix for jQuery UI 1.7
* Bugfix for validation

= V2.2.0 / 15.03.2009 =
* Added JW Media Player 4.3
* Added dashboard widget (THX to Frederic de Ranter)
* Added plugin support (THX to H.A.B.A for the idea)
* Added custom flashvars
* Added custom field support

= V2.1.0 / 11.12.2008 =
* Admin UI updates for Wordpress 2.7
* Added better excerpt feature (THX to Skwerl)
* Added new repeat option (THX to Skwerl)
* Added Counter statistic (THX to Frederic de Ranter)

= V2.0.1 / 19.11.2008 =
* Added JW Media Player 4.2
* Added attribute name to the object
* Added description flashvar (For LongTail)
* Added title flashvar (For LongTail)
* Bugfix for streaming server (like wowza)
* Bugfix : Div Wrapper around the flash object

= V2.0.0 / 27.09.2008 =
* Added JW Media Player 4.1 support
* Added WordPress Shortcode support. 
  Shortcodes changed from [MEDIA=X]/[MYPLAYLIST=X] to [media id=x]/[playlist id=x]
* Added LongTail Ads support
* Updated to swfobject 2.1
* Rework of all classes
* Remove all pre 4.0 Flash vars and conditions
* Remove all pre WP 2.6 dependencies
* Remove media player content in the_excerp()
* Remove MP3 layout, statistic, color settings. This will be return after 4.1 as new plugin later on. Stay tuned.

= V1.61 (not released) =
* This version has two major enhancements: tag management and youtube support. 
* Tags can be added to each media. They use the standard WP term table. 
* A tag list edit screen is available to ease input. 
* Related media can be displayed. They can be related to a post or another media. 
* An option has been added to display related media in the end screen of each media (requires mediaplayer v3.15 or above). 
* Youtube support has been added. From version 3.16, mediaplayer supports Youtube links. 
  If Youtube checkbox is checked when adding a new media in the media center, informations (title, author, image and tags) 
  will be retrieved from Youtube.com. 
* Insert button is now in the new "Add media" toolbar for WordPress 2.5. 
* A display mask can be set in option. upload directory is now manage like WordPress? (with year/month). 
* Fix excerpt. some options didn't work in some cases. 
* Add wordtube.jpg (default image) to prevent some hidden 404 message (didn't have any other impact). 

= V1.60 / 16.03.2008 =
* complete rewrite into a class
* playlist on right side
* start icon off/on
* control bar off/on
* function to display media with width/height
* fix excerpt
* sortable playlist
* choice for mp3 layout
* WP formatted filters on manage screen (media list screen)
* Media selection can be 'last' or 'random' ex: [MEDIA=last]
* Note: new texts will require translation update
* Remove count complete
* Added button for WP2.5

= V1.53 / 02.10.2007 =
* Bugfix for feeds (Thx to Tom)

= V1.52 / 23.09.2007 =
* Bugfix for swfobject integration in admin panel

= V1.51 / 12.09.2007 =
* Bugfix for statistic (THX to Alakhnor)
* Include update class
* Load swfobjects via WP script-loader

= V1.50 / 21.07.2007 =
* Bugfix for sort order
* Bugfix for statistic
* New TinyMCE dialog
* Change access level to editor role
* Added large controls option
* Added Wordpress 2.3 support

= V1.44 / 29.04.2007 =
* Fix path problem in button

= V1.43 / 14.04.2007 =
* Add Display width option
* Control bar can now disabled (Playlist size = 0)
* Change load language file for Gengo compability

= V1.42 / 30.03.2007 =
* Bugfix for language support during installation
* Bugfix for Update form of media files

= V1.41 / 29.03.2007 =
* Insert page navigation for more than 10 media files
* Remove <P> tag , when CENTER tag is not used (more XHTML valid)
* Change back minimum required Flash Player Version 7
* Updated to actual SWFobjects V1.50

= V1.40 / 15.02.2007 =
* Add link URL for playlist
* Updated to Media Player Version 3.5
* Attach media file in RSS feed (start of Podcast integration)
* Show thumbnail in RSS feed
* Change database types for URL's (larger than 200 chars now possible)
* Disable "wmode=transparent" for fullscreen mode
* Change from block level &lt;P&gt; to &lt;DIV&gt; tag

= V1.35 / 01.02.2007 =
* Add XHTML option
* Add RSS Feed message
* Add CSS class="wordtube"
* Bugfix for different sql_mode settings
* Fixed width in Button popup

= V1.34 / 27.01.2007 =
* Add autoscroll option
* Bugfix for Overstrech option

= V1.33 / 26.01.2007 =
* Sort order for playlist
* Limit size of Playlist in admin section

= V1.32 / 24.01.2007 =
* Updated Tag for CDATA to pass XHTML validation ( Thanks to Jiku Lee ) 

= V1.31 / 21.01.2007 =
* Bugfix for ID’s larger than 10
* IE Bugfix for Dropdown list

= V1.30 / 21.01.2007 =
* Multiple playlist support
* Moved autostart to media file option
* Widget support
* Integrate AJAX DBX box
* Updated to Media Player Version 3.3
* New watermark option

= V1.21 / 09.01.2007 =
* Force Flash width/heigth for IE
* Bugfix for exclude option in playlist 

= V1.20 / 06.01.2007 =
* Exclude option for playlist
* Set file permission after upload
* Viewer statistic / Counter
* Integrated MP3 Player Version
* New Equalizer option

= V1.11 / 01.01.2007 =
* Changes from UFO to SWFobjects to fix Firefox problem
* Added shuffle mode
* Updated german translation

= V1.10 / not released =
* Integrated support for Media Player
* Added Author field
* Added Playlist support
* Button support for Wordpress 2.1
* Changed form names from "Video" to "Media"
* Fix bug in wordtube-Button.php when files are deleted

= V1.03 / 29.12.2006 =
* Updated Button Path for testpages (Xampp/ EasyPHP) under Windows

= V1.02 / 27.12.2006 =
* Updated to FLV Player Version 3.2
* Integrated new Overstrech option

= V1.01 / 21.12.2006 =
* Bugfix for Permalink structure

= V1.00 / 20.12.2006 =
* First version
* Integrated UFO script
* German translation
* TinyMCE Button to select Video