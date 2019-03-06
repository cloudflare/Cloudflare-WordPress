=== Cloudflare ===
Contributors: jwineman, furkan811, icyapril, manatarms, zproser
Tags: cloudflare, seo, ssl, ddos, speed, security, cdn, performance, free
Requires at least: 3.4
Tested up to: 4.9.1
Stable tag: 3.3.3
License: BSD-3-Clause

All of Cloudflare’s performance and security benefits in a simple one-click install of recommended settings specifically developed for WordPress.

== Description ==

= What this plugin can do for you =

**One-click WordPress-optimized settings**

The easiest way to setup Cloudflare for your WordPress site.

**Web application firewall (WAF) rulesets**

Available on all of Cloudflare’s paid plans, the WAF has built-in rulesets, including rules that mitigate WordPress specific threats and vulnerabilities. These security rules are always kept up-to-date, once the WAF is enabled, you can rest easy knowing your site is protected from even the latest threats.

**Automatic cache purge**

Occurs when you change the appearance of your website. This means that you can focus on your website, while we ensure that the latest content is always available to your visitors.
(Note: By default, Cloudflare does not cache HTML, and a cache purge is not required on updating HTML content such as publishing a new blog entry).

= Additional features =

* Header rewrite to prevent a redirect loop when Cloudflare’s Universal SSL is enabled

* Change Cloudflare settings from within the plugin itself without needing to navigate to the cloudflare.com dashboard. You can change settings for cache purge, security level, Always Online, and image optimization

* View analytics such as total visitors, bandwidth saved, and threats blocked

* Support for [HTTP2/Server Push](https://blog.cloudflare.com/announcing-support-for-http-2-server-push-2/)

== Installation ==

= Prerequisite =
Make sure your PHP version is 5.3.10 or higher.

= From your WordPress Dashboard =

1. Visit “Plugins” → Add New
2. Search for Cloudflare
3. Activate Cloudflare from your Plugins page.

= From WordPress.org =

1. Download Cloudflare
2. Upload the “cloudflare” directory to your “/wp-content/plugins/” directory, using ftp, sftp, scp etc.
3. Activate Cloudflare from your Plugins page.

= Once Activated =

1. Go to cloudflare.com
2. Login with your cloudflare account. (If you don’t have a Cloudflare account first sign up for Cloudflare)
3. Press your account name on top right corner and select “My Settings”
4. Scroll down to “API Key” → “Global API Key” → View API Key
5. Copy the API key
6. Return back to WordPress Cloudflare Plugin page
7. Enter your email address and paste your API key
8. Press Login.

== Frequently Asked Questions ==

= Do I need a Cloudflare account to use the plugin? =

Yes, on install and activation the plugin, first time users will be asked to enter their email address (used to sign-up for an account at cloudflare.com) and their user API key. This is needed to support all the features offered by the plugin.

= What settings are applied when I click "Apply Default Settings" in Cloudflare's WordPress plugin? =

 You can review the recommended settings that are applied [here](https://support.cloudflare.com/hc/en-us/articles/227342487).

= Does the plugin work if I have Varnish enabled? =

Yes, Cloudflare works with, and helps speed up your site even more, if you have Varnish enabled.

== Screenshots ==

1. Cloudflare Plugin

== Changelog ==

= 3.3.3 - 2019-3-6 =

*Fixed*

* Bug in Hooks.php causing errors in PHP 7+
* Bug preventing Autoptimize plugin's optimized asset urls from being used when present and when Cloudflare HTTP/2 Push was enabled

= 3.3.2 - 2017-12-12 =

*Fixed*

* Bug in cf-ip-rewrite

*Added*

* Added a new filter cloudflare_purge_by_url allowing users to have better control on automatically purged urls.

= 3.3.1 - 2017-6-29 =

*Fixed*

* Potential bug by using $_GET.

= 3.3.0 - 2017-6-29 =

*Added*

* Added a new Splash Screen
* Added userConfig.js file allowing custom configurations.
* Added logs in debug mode for Automatic Cache Purge.
* Added logs for oversized Server Push HTTP headers.

*Changed*

* Automatic Cache Purge now purges Autoptimize by everything rather than by URL.
* Updated IP Ranges

*Fixed*

* Bug where domains which had capital letters not working.
* Bug where Automatic Cache Purge couldn't purge front page.
* Bug related to work with IWP.
* Bug where if PHP is compiled with ipv6-disable flag, it crashed the site.

= 3.2.1 - 2017-3-14 =

*Fixed*

* Bug where accounts which had more than 20 zones would not show up correctly.

= 3.2.0 - 2017-3-1 =

*Added*

* Bypass Cache By Cookie functionality.
* HTTP/2 Server Push functionality (disabled by default).

*Changed*

* Lowered the plugin size.
* Automatic Cache Management feature includes purging taxonomies.
* Automatic Cache Management feature supports sites which use both HTTP and HTTPS.

*Fixed*

* Admin bar disappearing from the plugin.
* Bug where spinner was loading forever.
* Bug where the backend errors where not being shown in the frontend.
* Issues where IE11 was not working properly.

= 3.1.1 - 2016-11-17 =

*Changed*

* Moved Admin Bar behind Automatic Cache Purge toggle.

= 3.1.0 - 2016-11-17 =

*Added*

* Added ability to automatically purge cache when a post is published, edited or deleted. (Thanks to brandomeniconi and mike503)
* Added ability to work with WordPress MU Domain Mapping plugin. (Thanks to brandomeniconi)

*Changed*

* Changed the UI to look more like cloudflare.com dashboard.
* Changed plugin description.
* Disabled showing WordPress Admin Bar and Edit Post Link to avoid caching problems for users using HTML Caching.

*Fixed*

* Fixed bug where require vendor folders was not working.
* Fixed bug where static files were cached which caused issues updating the plugin.
* Fixed dependencies which caused issues with PHP Compatibility Checker plugin.

= 3.0.6 - 2016-10-6 =

*Added*

* Added ability to toggle Development Mode.

*Fixed*

* Fixed bug where active zone dropdown was not working properly.

*Changed*

* Compressed resources to lower plugin size.
* Updated Cloudflare logo.

= 3.0.5 - 2016-09-28 =

*Fixed*

* Fixed bug where refactored Flexible SSL fix was causing the settings page hook not to load.

= 3.0.4 - 2016-09-27 =

*Added*

* Ability for users to toggle Automatic HTTPS Rewrites (enabled by default, solves for most mixed content errors).

*Fixed*

* Fixed an issue where low PHP version where getting syntax error.
* Fixed issue where some users using Flexible SSL where not able to login to wp-admin .
* Fixed a bug where the active zone selector was not paginating through the whole zone list.
* Fixed an issue where the setting for Image Optimization was being displayed incorrectly.
* Fixed a bug in Analytics where the  Uniques Visitors data was not displaying accurately.

*Changed*

* Compressed assets to lower plugin size.
* Hooks loading logic refactored to make it more simple and readable.

= 3.0.3 - 2016-09-21 =

*Fixed*

* Fixed an issue where some domains were being incorrectly propagated to the domain selector dropdown
* Fixed an issue where the Web Application Firewall was accidentally triggering RFI Attack Rules
* Fixed an issue where image optimization was not being enabled for Pro and higher Cloudflare plans

= 3.0.2 - 2016-09-16 =

*Fixed*

* Disabled HTTP/2 Server Push which was leading to 520 and 502 errors for some websites.

= 3.0.1 - 2016-09-16 =

*Fixed*

* Fixed HTTP/2 Server Push exceeding the header limit Cloudflare has which caused 520 errors.
* Fixed warning message in HTTP/2 Server Push.

= 3.0.0 - 2016-09-15 =

*Added*

* Added one-click application oft WordPress specific recommended settings
* Added ability to purge the Cloudflare cache
* Integrated with WordPress cache management to automatically clear the Cloudflare cache on updating site appearance
* Added ability to change Cloudflare settings (Always Online mode, I’m Under Attack, Image Optimization, Security Level, Web Application Firewall)
* Added Analytics showing Cached Requests, bandwidth used, unique visitors, threats blocked
* Added Header rewrite to prevent a redirect loop when Cloudflare’s Universal SSL is enabled
* Added HTTP/2 Server Push support
* Added Support for PHP 5.3+

*Removed*

* Removed HTTPS Protocol Rewriting
* Removed submission of spam comments
* Removed ability to toggle Development Mode On/Off

*Changed*

* Updated user interface
* Started to support WordPress 3.4+ instead of 2.8+ because we depend on the  [WordPress Options API](https://codex.wordpress.org/Options_API)
