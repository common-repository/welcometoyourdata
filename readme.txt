=== Plugin Name ===
Contributors: Adam Wulf
Developer Links: 
	http://welcome.totheinter.net/welcome-to-your-data/
Tags: wtyd, peerindex, google_reader, stats, statistics, data, feedburner
Requires at least: 3.1
Tested up to: 3.1
Stable tag: 0.1.9

WelcomeToYourData tracks your statistics from your blog, Google Analytics, Google Reader, Feedburner, and PeerIndex accounts. Easily track and correlate data between these
data sources.

== Description ==

WelcomeToYourData tracks your statistics from your blog, Google Analytics, Google Reader, Feedburner, and PeerIndex accounts. Easily track and correlate data between these
data sources.

The major features of WelcomeToYourData are:

    * Import # of visitors, time on site, bounce rate, % new visitors, and events from Google Analytics
    * Import your PeerIndex score, authority, audience, and activity
    * Import your Feedburner feed hits and subscribers
    * Import your starred items from Google Reader
    * Track # of blog posts, average post length, # of comments
    * Graph all of the above data with easy to read line graphs
    * Correlate all of the above data with easy to use scatter plots

Requirements:

	* Wordpress version 3.1 or higher.
	* PHP 5 or higher

== Installation ==

Copy the wp-wtyd folder into the plugins directory. Activate the plugin.

Create a directory wp-content/cache_wtyd and chmod it to 777

Create a cron job to regularly load in data into WTYD. Example command for cron: curl -L -s http://example.com?updateWTYD

On the settings page, enter all of your credentials for the various data sources, then import your data's history.


== Screenshots ==

For an example graph with live data, visit:

http://welcome.totheinter.net/about/


== More Information ==

Find out more about WTYD at http://welcome.totheinter.net/welcome-to-your-data/

Send all bug reports and feature requests to adam.wulf@gmail.com .
