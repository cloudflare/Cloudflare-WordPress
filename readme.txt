=== CloudFlare ===
Contributors: i3149, jchen329, jamescf, simon-says, dfritsch
Tags: cloudflare, comments, spam, cdn, free, website, performance, speed
Requires at least: 2.8
Tested up to: 4.1
Stable tag: 1.3.24
License: GPLv2

The CloudFlare WordPress Plugin ensures your WordPress blog is running optimally on the CloudFlare platform.

== Description ==

CloudFlare has developed a plugin for WordPress. By using the CloudFlare WordPress Plugin, you receive: 

* Correct IP Address information for comments posted to your site

* Better protection as spammers from your WordPress blog get reported to CloudFlare

THINGS YOU NEED TO KNOW:

* The main purpose of this plugin is to ensure you have no change to your originating IPs when using CloudFlare. Since CloudFlare acts a reverse proxy, connecting IPs now come from CloudFlare's range. This plugin will ensure you can continue to see the originating IP. 

* Every time you click the 'spam' button on your blog, this threat information is sent to CloudFlare to ensure you are constantly getting the best site protection.

* We recommend any WordPress and CloudFlare user use this plugin. For more best practices around using WordPress and CloudFlare, see: https://support.cloudflare.com/hc/en-us/articles/201717894-Using-CloudFlare-and-WordPress-Five-Easy-First-Steps

MORE INFORMATION ON CLOUDFLARE:

CloudFlare is a service that makes websites load faster and protects sites from online spammers and hackers. Any website with a root domain (ie www.mydomain.com) can use CloudFlare. On average, it takes less than 5 minutes to sign up. You can learn more here: [CloudFlare.com](https://www.cloudflare.com/overview.html).

== Installation ==

Upload the CloudFlare plugin to your blog, Activate it, and you're done!

You will also want to sign up your blog with CloudFlare.com

[Read more](http://blog.cloudflare.com/introducing-the-cloudflare-wordpress-plugin) on why we created this plugin.

== Changelog ==

= 1.3.23 =

Fixed bug that was preventing spam comments from being sent to CloudFlare

= 1.3.22 =

* Fixing bug which prevented a user from activating/deactivating the plugin

= 1.3.21 = 

* Added input sanitization.

= 1.3.20 =

* Updated the method to restore visitor IPs
* Updated the URL rewrite to be compatible with WordPress 4.4

= 1.3.18 =

* Bug: Clean up headers debugging message that can be displayed in some cases

= 1.3.17 =

* Limit http protocol rewriting to text/html Content-Type

= 1.3.16 =

* Update regex to not alter the canonical url

= 1.3.15 =

* Plugin settings are now found under Settings -> CloudFlare
* Plugin is now using the WordPress HTTP_API  - this will give better support to those in hosting environments without cURL or an up to date CA cert bundle
* Fixes to squash some PHP Warnings. Relocated error logging to only happen in WP_DEBUG mode
* Added Protocol Rewriting option to support Flexible SSL

= 1.3.14 =

* Improved logic to detect the customer domain, with added option for a manual override
* Standardised error display
* Updated CloudFlare IP Ranges

= 1.3.13 =

* Clarified error messaging in the plugin further
* Added cURL error detection to explain issues with server installed cert bundles

= 1.3.12 =

* Removed use of php short-code in a couple of places
* Added some cURL / json_decode error handling to output to the screen any failures
* Reformatted error / notice display slightly

= 1.3.11 =

* Adjusted a line syntax to account for differing PHP configurations.

= 1.3.10 = 

* Added IP ranges.

= 1.3.9 =
* Made adjustment to syntax surrounding cURL detection for PHP installations that do not have short_open_tag enabled.

= 1.3.8 =
* Fixed issue with invalid header.
* Updated IP ranges
* Fixed support link

= 1.3.7 =
* Remove Database Optimizer related text.

= 1.3.6 =
* Remove Database Optimizer.

= 1.3.5 =
* Disable Development Mode option if cURL not installed.  Will Use JSONP in future release to allow domains without cURL to use Development Mode.

= 1.3.4 =
* Add in IPV6 support and Development Mode option to wordpress plugin settings page.  Remove cached IP range text file.

= 1.3.3 =
* Bump stable version number.

= 1.3.2.Beta =  
* BETA RELEASE: IPv6 support - Pull the IPv6 range from https://www.cloudflare.com/ips-v6.  Added Development Mode option to wordpress plugin settings page.

= 1.2.4 =  
* Pull the IP range from https://www.cloudflare.com/ips-v4.  Modified to keep all files within cloudflare plugin directory.

= 1.2.3 =  
* Updated with new IP range

= 1.2.2 =
* Restricted database optimization to administrators

= 1.2.1 =
* Increased load priority to avoid conflicts with other plugins

= 1.2.0 =

* WP 3.3 compatibility.

= 1.1.9 =

* Includes latest CloudFlare IP allocation -- 108.162.192.0/18.

= 1.1.8 =

* WP 3.2 compatibility.

= 1.1.7 =

* Implements several security updates.

= 1.1.6 =

* Includes latest CloudFlare IP allocation -- 141.101.64.0/18.

= 1.1.5 =

* Includes latest CloudFlare IP allocation -- 103.22.200.0/22.

= 1.1.4 =

* Updated messaging.

= 1.1.3 =

* Better permission checking for DB optimizer.
* Added CloudFlare's latest /20 to the list of CloudFlare IP ranges.

= 1.1.2 =

* Fixed several broken help links.
* Fixed confusing error message.

= 1.1.1 =

* Fix for Admin menus which are breaking when page variable contains '-'.

= 1.1.0 =

* Added a box to input CloudFlare API credentials.
* Added a call to CloudFlare's report spam API when a comment is marked as spam.

= 1.0.1 =

* Fix to check that it is OK to add a header before adding one.

= 1.0.0 =

* Initial feature set
* Set RemoteIP header correctly.
* On comment spam, send the offending IP to CloudFlare.
* Clean up DB on load.
