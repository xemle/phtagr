
The players are located in the root flowplayer directory in the distribution package. 
The player flash components are: FlowPlayer.swf, FlowPlayerWhite.swf, FlowPlayerBlack.swf and FlowPlayerLP.swf
Sample HTML files + JavaScript for embedding the players are in the html directory. 

FlowPlayer Version history (latest release on top)
=================================================

2.0.1
	- Arranges the buttons to the left if the scrubber is not shown (when using showScrubber: false)
	- Does not show the video link in the Embed popup unless videoLink is specified in config
	Fixes:
	- Email sending works now by default, it sends the data to flowplayer.org's email sender
	- Menu bar is now always correctly opened above the control bar
	

2.0
	- Now uses the "native full screen" by default, no need to specify useNativeFullScreen: true to enable it
	- Shows the big play button overlay image by default, new config option 'usePlayOverlay' that can be
	  used to disable it.
	- New button to mute and unmute the sound volume
	- Possibility to show/hide every button and widget in the control bar
	- New overlay play button graphic
	- New rotating buffer animation
	- Added a new callback onFlowPlayerReady() to the JavaScript API.
    - New default skin 'air'
       * New controlsOverVideo config setting
       * new config setting controlBarBackgroundColor (controlAreaBorderColor setting was removed)
	- Removed the loop button (unnecessary)
	- The initial volume level is now 50%
	- The popup menu automatically hides after 10 seconds if not closed by the user.
	- Added new "onPlaybackTime" callback to the JavaScript API. Reports the current time
	  in the video timeline.
	- setting autoPlay: true now starts playing when the 'play' button overlay is used as the first clip
	  in the playList. Setting autoPlay: false will make the player pause on and show the splash.
	- Embed view shows a direct link to the current video file
      * added videoLink config option that can be used to override the default
        that is taken from the playList or the videoFile option
    - Email-a-link functionality:
      * added emailPostUrl config option
      * added emailVideoLink config option
      * added emailDefaultMessage config option (all occurrences of [video_link] tag in the message
        are replaced with the value of emailVideoLink)
	- AdServer functionality:
	  * added following clip specific configuration options: id, keywords
	  * added enableAds, publisherId, adServerUrl, adServerAuth, countryCode config options
	Fixes:
	- autoRewind now rewinds to start and stops if loop is false
	- The initialScale config option works correctly when the controls are hidden using hideControls: true
	- playList support with RTMP streaming servers was broken

1.21.1
	Fixes:
	- FlowPlayer prevented page elements from loading properly when the page was refreshed in IE. Now fixed!
	- setConfig() JavaScript API function is initialized as early as possible to
	  fix some timing issues on IE.
	- does not load the fullscreen.js from flowplayer.org when using the native full screen mode	
	- volume level was reset back to 100% when scrubbing


1.21
	- MP3 playback (following are not supported yet: Protection codes (a.k.a. anti-leeching))
	- Google Analytics support
	- Extensions to the JavaScript API
		- Added the possibility to play a clip via JavaScript
		- Added possibility to set programmatic cue points using JavaScript
		- Added possibility to play a clip having a RTMP url
	- Player initializes without specifying any config. After that you can use the JavaScript API
	  to configure it and play clips.
	- Embed code does not include child configs any more. Only the configuration set by
	  flashVars or injected is included in the embed code configuration.
	- Possibility to supply the embed code in config
	- Possibility to load a page for suggestions instead of just playing them in the same
	  player instance. Done with the linkURL and linkWindow clip specific fields.
	Fixes:
	- Seeking is more accurate so that it seeks exactly where the scrub bar is released
	- You can now seek to the beginning or the end of a video using the scrubber
	- Setting a new config using JavaScript via setConfig() now re-creates the UI so that it properly
	  reflects the new config
	- baseURL was not used if it was specified in a parent config and the playlist was specified in it's child config.
	- does not load the fullscreen.js from flowplayer.org when using the native full screen mode
	- doubleclickin on the "play" overlay at the beginning of a playlist caused the player to pause. Now doubleclicks
	  work the same as single clicks.
	- Fixes to suggestions:
	    - Does not unnecessarily load the suggestion URL several times.
	    - Suggestions loading did not work when using an external config file. Caused the player to initialize over and over.

1.20
	- Suggestions a.k.a. related videos
	- Loading of configuration using RTMP from a streaming server
	- The native full screen ads were removed and replaced by a flowplayer.org logo
	- Added a new clip type 'video' that ensures correct playback for h.264 videos supported
	  by the latest Flash player 3 update codenamed "Moviestar"
	Fixes:
	- RTMP recorded streams were not played to the end
	- Fixed the sample HTML files

1.19
	- Skinning kit
	- Added all dependencies to the source distribution package
	Fixes:
	- videoHeight does not have any effect in full screen mode any more, now resizes to fit the full area
	- configured progress bar, buffering bar etc. colors were ignored

1.18
	- Smoothing of FLV videos (antialiasing of scaled video)
	- Native Flash full screen mode added.
	- The Long Play version (FlowPlayerLP.swf) now resizes to full screen
	- Addes mouse over states to buttons.
	- Added 'overlay' to image clips. Can be used to overlay a play button image on top of another image clip.
	- Addes a built-in Big Play Button overlay.
	- Added new clip specific configuration variable 'liveStream' to specify that the clip is a live stream
	  played from a media server.
	- Changed the background of the video area to be black. Now the player does not show the video (shows the black
	  background instead) before the buffer is filled an the playback starts.
	- Added new clip specific setting 'showOnLoadBegin'. Turn this to false and the video will not be shown
	  before the buffer has been filled and the playback starts.
	- All methods of the JavaScript API are now available in LocalConnection API as well. You can now
	  control FlowPlayer from another Flash movie.
	- By default the loop button is no longer shown. You have to specify showLoopButton: true to make it visible.
	- New maximum play count for clips in the playlist. Controlled with a clip specific 'maxPlayCount' setting.
	Fixes:
	- Progress bar now better seeks to the end of videos.
	- The embed area is centered horizontally also in full screen mode.
	- The time display was corrupted with videos longer than 60 minutes. Now uses a smaller font so that
	the time values fit properly.
	- JS full screen works with external config file
	- Setting showFullScreenButton to False Shortens Progress Bar - now fixed
	- The Long Play version does not redraw the thumbs strip unnecessarily

Older releases in in version number order:

0.9     Initial public release. All basic features are in place.

0.9.1   Added 'autoPlay' variable that can be used to specify whether the
        playback should begin immediately when the player has been loaded into
        the Web browser.

0.9.2   Bug fixes.

0.9.3   Added new 'bufferLength' variable that can be used in the HTML page to
        specify the length of the buffer in seconds when streaming FLV. Fixed a
        bug that prevented playback after an FLV was completely played.

0.9.4   Added a 'baseURL' variable that can be used to specify the location of
        the video file to be loaded by Player.swf. See Player.html for an
        example.

        If the 'videoFile' variable's value contains a '.flv' or '.swf'
        extension, the standalone player (Player.swf) will NOT append this based
        on the detected flash version. If a prefix is not present, the player
        always appends either one of these prefixes.

1.0
	- Displays a "BUFFERING..." text when filling the video buffer.
	- Fixed playback of the start and the end of the video where the player
	  was errorneously cutting off some of the video.
	- Added a new start() function to the FlowPlayer class.
	- Fixed Sample.fla

1.1
    - Added ability to loop; Contains a new toggle button to control looping.
      Default looping state is defined in 'loop' parameter. Thanks Jeff Wagner
      for contributing the initial looping support.
    - Now resizes according to the size defined in the HTML page
    - Fixed some flaws in the graphics
    - The color of the progress bar is now gray by default (more neutral). The
      color can be customized by parameters.
    - Removed support to play videos in SWF format.

1.2
    - Added a 'autoBuffering' option and welcome image support.
    - Added a 'hideContols' option to hide all buttons and other widgets and
      leaving only the video display showing.
    - Added support for welcome images
    - Most of the UI is now built dynamically with ActionScript instead of using
      pre-drawn images. This results in 50% smaller download size.

1.2.b2
	- Fixed binary build that contained an old buggy FlowPlayer.swf

1.3
    - Fixed resizing problem that occurred with Internet Explorer: The video was
      not resized when the page was refreshed.

1.4
	- Removed the blue the background color of the player. The light blue color
	  became visible when using only the obect-tag to embed the player into a page.
	  By using only the object tag it's possible to author valid XHTML. The sample
	  FlowPlayer.html now shows this kind of markup.

1.5
	- Support for playlists
	- Extenal configuration file that enables configuring all the existing
	  settings. All settings defined in this configuration file can be
	  overridden using flashvars in the HTML object tag.
	- Basic skinning support: Images for all buttons (play, pause, looping
	  toggle, and dragger) can be loaded from external JPG files. Smaller
	  versions of the default buttons are provided as an example.
	  FlowPlayerLiht.swf is meant to be used with skinning: it does not contain
	  any button images in itself and therefore is slightly smaller in download
	  size.
	- 'hideBorder' option
	- visual improvement of control buttons
	- dragging can be now done by clicking anywhere in the progress bar area
	- clicking on the video area pauses and resumes playback
	- scaling the splash image is now optional. Alternatively it can be centered
	  into the video area.
	- removed the border surrounding the video area
	- plus some more minor changes
	Bug fixes:
	- Seeking using the dragger button is more accurate. Now it is possible to
	  seek to the very beginning of a clip.
	- Stops playing in the launching player when the full screen player is opened. This
	  way the full screen player can begin buffering the video immediately. Previously
	  the launching player was paused and continued buffering and it prevented the full
	  screen player from buffering.

1.6
	Bug fixes:
	- Does not buffer unnessessarily if looping through one clip again and again
	- Playback can be started normally again when the clip is done and looping
	is off. There was a bug that prevented this.
	- Clicking on the video area did not start playing if splash not used and
	when autoPlay=false
	- The seeker button aligned to the right from the mouse position when it was
	grabbed using the mouse button. Now it stays on the same position it was in
	when the mouse button was pressed down.
	- It was not possible to use characters 'y' and 'Y' in the names inside 
	the playList. Now following characters are available: 
	"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz?!/-:,. 1234567890"

1.7
	Fixes:
	- baseURLs are not appended to file name values that are complete URLs
	- minor visual enhancements
	New features:
	- Support for long videos: Initial support for streaming servers (tested with red5) +
	thumbnails corresponding to cue points.
	- Video duration and time indicator
	- Resizing of the video area through a menu

1.7.1
	- Now the original FLV video dimensions are detected from the FLV metadata. The different resizing options are based on those. Initially the size is maximized according to the 'videoHeight' setting and preserving the aspect ratio.
	Fixes:
	- progress bar now goes to the start position (left) even when autoPlay=false and autoBuffering=false [1574640]
	- resizing menu does not toggle pause/resume anymore [1581085]
	- fixed missing audio on some FLV files [1568612]
	- Flash Media Servers seems to send the FLV metadata again and again when seeking. This caused unnessessary rearranging of the thumbnails.
	- Thumnail list's scrollbar is now hidden if all thumbnails fit the available visible space.

1.8
	- Initial JavaScript API (Requires FlashPlayer 8 or above):
		* Possibility to configure the player using JavaScript and a configuration object
		similar to the external config file (using JavaScript Object Notation, JSON)
		* Possibility to move the playback to different clips in the playlist using JavaScript
	- Changed the format how thumbnails are specified in the config object
	- The numbers from the playlist's clips were removed
	- Unnamed clips are hidden from the playlist
	- Possibility to have images in the playlist. Now the splash image is internally handled
	as being the first clip in the playlist.
	- Adjacent clips are played as one stream when using a streaming server

1.9	- More complete JavaScript API
	- Hierarchical configuration
	- New config variable "initialScale" to control the initial scaling of the video
	- New resizing option "fill window" that will fill all available space (does not care about preserving the aspect ratios)
	- Changed the default buffer to 10 seconds
	- noVideoClip config setting that can be used to specify a video clip or an image to be played when
	  the primary clip is not found
	Fixes:
	- Fixed buffering indicator, did not show the buffer lenght correctly when not using a streaming server
	- It was not possible to pass an empty string in baseURL when using setConfig() in the JavaScript API
	- loop config setting was broken
	- Clip is now better recognized as completely played

1.10
	Changed to use the same configuration syntax with flashVars as with external configuration files. Now
	the same configuration style is used consistently everywhere.
	Fixes:
	- It was impossible to disable autoPlay and autoBuffering

1.10.1
	- Fix for the message on IE: "Object doesn't support this property or method' Line 48, Char 3". This
	  was caused by two method names in the new JavaScript API. As a result, these methods are now renamed
	  in the JavaScript API.
	- Inlcuded javascript.txt (documentation for the JavaScript API) in the distribution packages.

1.11
	- Finally added a volume control slider
	- Made all progress bar and volume slider colors customizable
	- Added a possibility to hide the playlist control buttons (next clip and previous clip buttons)
	- Fixed the sample html files to work in Internet Explorer. The pages now use SWFObject
	  (http://blog.deconcept.com/swfobject/) to embed the player.

1.11.1
	- Changed volume slider to change the volume while the slider is being moved. The previous version changed
	  it only after the mouse button was released.
	Fixes:
	- Now resets the play/pause button correctly when the clip ends and looping is not used
	- Looping does not go to the splash after the last clip in the playlist is finished. Instead the playback loops
	  to the first clip after the splash. This is valid also when only one video is configured to be played using
	  the 'videoFile' config option.

1.12
	- Protection scheme to prevent inline linking (http://en.wikipedia.org/wiki/Inline_linking) 
	  of video and image files.
	- Images in playlist are resized according to the menu options. Images also respect the 'initialScale'
	  config option.
	Fixes:
	- If loop is off the player stops on the last frame of the clip

1.13
	- New config options to hide the loop button and the size options menu.
	- Possibility to disable transport control buttons using a clip specific option.
	- Possibility to have hyperlinks for clips. Will open the linked URL into the browser when the clip is clicked.
	- Possibility to disable pause/resume behavior associated to clicking the video area. This is done
	  by specifying an empty hyperlink URL to a click.
	- New animation that plays on the progress bar area when the video is buffering
	- The setConfig() method in the JavaScript API can be used over and over again to replace the configuration

1.14
	- Two new skins included (black & white)
	- Initial support for lighttpd
	- Ability to include Flash movies (swf files) in playlist
	- Added a type property to playlist clips so that the URLs don't need to have an extension (swf, flv or jpg)
	  any more.
	Fixes:
	- playlist control buttons (next & prev) did not fade out when disabling them for a clip

1.14.1
	- dragger (scrubber) now causes immediate seeking when it is moved
	Fixes:
	- JavaScript API's setConfig() did not work correctly
	- allows seeking to unbuffered areas when streaming with lighttpd
	- removed unnecessary error logging
	- volume slider goes all the way to the right edge
	- fixed regressions in LP version: Thumbnail scrollbar was not shown, 
	duration labels did not have the grey background

1.15
	- Added several event callbacks to the JavaScript API. See javascript.txt for details.
	- Added a new "thermometer" skin, ends up in FlowPlayerThermo.swf (does not include the playlist
	  control and loop buttons yet)
	- Andrew Rice: Faster seeking with lighttpd by using a binary search to find the keyframes.
	               A fix to the Seek() method in the JavaScript API now works correctly with lighttpd.
				   
1.16
	- Added full screen support. Opens a new browser window that occupies all screen estate.
	- Added a new view that shows the HTML code for embedding the video in blogs etc.
	- Changed the looks of the menu. The menu is now shown when the user hovers over the area
	  where it is displayed. 
	- Added more event callbacks to the JavaScript API. See javascript.txt for details.
	- Changed the splash image to use the 'baseURL' variable that is used with all other types of clips.
	  'skinImagesBaseURL' is only used for external skin images
	- Removed the playlist view (the list that showed the playlist contents under the control buttons)
	Fixes:
	- Volume slider sometimes was errorneusly placed on top of the video area
	- a SWF in a playlist is not loaded on top of the control button area (new the control area
	border stays on top of it).
	- initialScale parameter did not work when the controls were hidden
	- pause/resume by clicking the video (or the linkUrl behavior) did not work in the upper left
	  corner of the video area
	- The player now dynamically resizes itself if the size is changed in the embedding HTML. This
	  is utilized by the fullscreen feature.

1.16.1
	Fixes a bug in the new full screen feature, it was not able to dynamically add the required 
	JavaScript to the opening page's DOM on Internet Explorer.

1.16.2
	Fixed the embedding feature so that the displayed code works in MySpace.

1.17
	- Added a button to open the full screen page.
	- Removed the menu auto-popup.
	- Added a 'autoRewind' option that is used to rewind to the first clip in the playlist. The old
	'loop' option keeps the playback looping without returning back to the first splash image
	(if there is a splash).
	- Added ability to include png files in playlists. Especially useful for creating transparent splash images
	that have a big play button image.
	- Added a per clip parameter 'allowResize' that can be used to override the scaling setting.
	- Changed the font used in FlowPlayer to be non italics
	- Added possibility to fix the control buttons area width. Now the controls do not fill the whole
	  width in the full screen mode.
	Fixes:
	- Embed provided hardcoded width and height values. Now it takes those from the parent player.

1.17.1
	Fixes:
	- Fixed user interface problems introduced in 1.17 
	(see: http://sourceforge.net/forum/forum.php?thread_id=1733937&forum_id=453550)


	  
How to use it
=============

Please see http://flowplayer.org/documentation

Support, comments, bug reports and feedback:
--------------------------------------------
Please post support requests and feedback to the forums at http://flowplayer.org 
You can also contact the author directly: api@iki.fi
