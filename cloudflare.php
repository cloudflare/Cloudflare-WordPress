<?php
/*
Plugin Name: CloudFlare
Plugin URI: http://www.cloudflare.com/wiki/CloudFlareWordPressPlugin
Description: CloudFlare integrates your blog with the CloudFlare platform.
Version: 1.3.24
Author: Ian Pye, Jerome Chen, James Greene, Simon Moore, David Fritsch, John Wineman (CloudFlare Team)
License: GPLv2
*/

/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Plugin adapted from the Akismet WP plugin.

*/

require_once 'vendor/autoload.php';

define('CLOUDFLARE_VERSION', '1.3.24');
define('CLOUDFLARE_API_URL', 'https://www.cloudflare.com/api_json.html');
define('CLOUDFLARE_SPAM_URL', 'https://www.cloudflare.com/ajax/external-event.html');

use \CloudFlare\IpRewrite;

$cfPostKeys = array('cloudflare_zone_name', 'cf_key', 'cf_email', 'dev_mode', 'protocol_rewrite');

const MIN_PHP_VERSION = '5.3';
const MIN_WP_VERSION = '3.4';

foreach ($_POST as $key => $value) {
    if (in_array($key, $cfPostKeys)) {
        $_POST[$key] = cloudflare_filter_xss($_POST[$key]);
    }
}

// Call when the Plugin is activated in server.
function cloudflare_activate()
{
    global $wp_version;

    if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
        $flag = 'PHP';
        $version = MIN_PHP_VERSION;
    }

    if (version_compare($wp_version, MIN_WP_VERSION, '<')) {
        $flag = 'WordPress';
        $version = MIN_WP_VERSION;
    }

    if (isset($flag) || isset($version)) {
        // Deactivate Plugin
        deactivate_plugins(basename(__FILE__));

        // Kill Execution
        wp_die('<p><strong>Cloudflare</strong> plugin requires '.$flag.'  version '.$version.' or greater.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));

        return;
    }

    set_default_keys();
}
register_activation_hook(__FILE__, 'cloudflare_activate');

function set_default_keys()
{
    set_protocol_rewrite();
}

function cloudflare_filter_xss($input)
{
    return htmlentities($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

function cloudflare_init()
{
    $ipRewrite = new IpRewrite();
    $is_cf = $ipRewrite->isCloudFlare();
    if ($is_cf) {
        // The HTTP Request is from Cloudflare. Ip is rewritten successfully.
        // For more info: github.com/cloudflare/cf-ip-rewrite
        sslRewrite();
    }

    add_action('admin_menu', 'cloudflare_config_page');
}
add_action('init', 'cloudflare_init', 1);

function cloudflare_admin_init()
{
}
add_action('admin_init', 'cloudflare_admin_init');

function cloudflare_plugin_action_links($links)
{
    $links[] = '<a href="'.get_admin_url(null, 'options-general.php?page=cloudflare').'">Settings</a>';

    return $links;
}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'cloudflare_plugin_action_links');

function cloudflare_config_page()
{
    if (function_exists('add_options_page')) {
        add_options_page(__('CloudFlare Configuration'), __('CloudFlare'), 'manage_options', 'cloudflare', 'cloudflare_conf2');
    }
}

function set_protocol_rewrite()
{
    update_option(CF\API\Plugin::SETTING_PROTOCOL_REWRITE, true);
}

function load_protocol_rewrite()
{
    //TODO refactor so we're only initing this stuff once.
    //also used in purgeCache()
    $config = new CF\Integration\DefaultConfig('[]');
    $logger = new CF\Integration\DefaultLogger($config->getValue('debug'));
    $dataStore = new CF\WordPress\DataStore($logger);

    return $dataStore->getPluginSetting(CF\API\Plugin::SETTING_PROTOCOL_REWRITE);
}

function load_ip_rewrite()
{
    //TODO refactor so we're only initing this stuff once.
    $config = new CF\Integration\DefaultConfig('[]');
    $logger = new CF\Integration\DefaultLogger($config->getValue('debug'));
    $dataStore = new CF\WordPress\DataStore($logger);

    return $dataStore->getPluginSetting(CF\API\Plugin::SETTING_IP_REWRITE);
}

function load_plugin_specific_cache()
{
    //TODO refactor so we're only initing this stuff once.
    $config = new CF\Integration\DefaultConfig('[]');
    $logger = new CF\Integration\DefaultLogger($config->getValue('debug'));
    $dataStore = new CF\WordPress\DataStore($logger);

    return $dataStore->getPluginSetting(CF\API\Plugin::PLUGIN_SPECIFIC_CACHE);
}

function cloudflare_conf2()
{
    include 'cloudflare2.php';
}

// Now actually allow CF to see when a comment is approved/not-approved.
function cloudflare_set_comment_status($id, $status)
{
    if ($status == 'spam') {
        global $cloudflare_api_key, $cloudflare_api_email;

        load_cloudflare_keys();

        if (!$cloudflare_api_key || !$cloudflare_api_email) {
            return;
        }

        $comment = get_comment($id);

        // make sure we have a comment
        if (!is_null($comment)) {
            $payload = array(
                'a' => $comment->comment_author,
                'am' => $comment->comment_author_email,
                'ip' => $comment->comment_author_IP,
                'con' => substr($comment->comment_content, 0, 100),
            );

            $payload = urlencode(json_encode($payload));

            $args = array(
                'method' => 'GET',
                'timeout' => 20,
                'sslverify' => true,
                'user-agent' => 'CloudFlare/WordPress/'.CLOUDFLARE_VERSION,
            );

            $url = sprintf('%s?evnt_v=%s&u=%s&tkn=%s&evnt_t=%s', CLOUDFLARE_SPAM_URL, $payload, $cloudflare_api_email, $cloudflare_api_key, 'WP_SPAM');

            // fire and forget here, for better or worse
            wp_remote_get($url, $args);
        }

        // ajax/external-event.html?email=ian@cloudflare.com&t=94606855d7e42adf3b9e2fd004c7660b941b8e55aa42d&evnt_v={%22dd%22:%22d%22}&evnt_t=WP_SPAM
    }
}

add_action('wp_set_comment_status', 'cloudflare_set_comment_status', 1, 2);

function get_dev_mode_status($token, $email, $zone)
{
    $fields = array(
        'a' => 'zone_load',
        'tkn' => $token,
        'email' => $email,
        'z' => $zone,
    );

    $result = cloudflare_curl(CLOUDFLARE_API_URL, $fields, true);

    if (is_wp_error($result)) {
        trigger_error($result->get_error_message(), E_USER_WARNING);

        return $result;
    }

    if ($result->response->zone->obj->zone_status_class == 'status-dev-mode') {
        return 'on';
    }

    return 'off';
}

function set_dev_mode($token, $email, $zone, $value)
{
    $fields = array(
        'a' => 'devmode',
        'tkn' => $token,
        'email' => $email,
        'z' => $zone,
        'v' => $value,
    );

    $result = cloudflare_curl(CLOUDFLARE_API_URL, $fields, true);

    if (is_wp_error($result)) {
        trigger_error($result->get_error_message(), E_USER_WARNING);

        return $result;
    }

    return $result;
}

function get_domain($token, $email, $raw_domain)
{
    $fields = array(
        'a' => 'zone_load_multi',
        'tkn' => $token,
        'email' => $email,
    );

    $result = cloudflare_curl(CLOUDFLARE_API_URL, $fields, true);

    if (is_wp_error($result)) {
        trigger_error($result->get_error_message(), E_USER_WARNING);

        return $result;
    }

    $zone_count = $result->response->zones->count;
    $zone_names = array();

    if ($zone_count < 1) {
        return new WP_Error('match_domain', 'API did not return any domains');
    } else {
        for ($i = 0; $i < $zone_count; ++$i) {
            $zone_names[] = $result->response->zones->objs[$i]->zone_name;
        }

        $match = match_domain_to_zone($raw_domain, $zone_names);

        if (is_null($match)) {
            return new WP_Error('match_domain', 'Unable to automatically find your domain (no match)');
        } else {
            return $match;
        }
    }
}

/**
 * @param $domain        string      the domain portion of the WP URL
 * @param $zone_names    array       an array of zone_names to compare against
 *
 * @returns null|string null in the case of a failure, string in the case of a match
 */
function match_domain_to_zone($domain, $zones)
{
    $splitDomain = explode('.', $domain);
    $totalParts = count($splitDomain);

    // minimum parts for a complete zone match will be 2, e.g. blah.com
    for ($i = 0; $i <= ($totalParts - 2); ++$i) {
        $copy = $splitDomain;
        $currentDomain = implode('.', array_splice($copy, $i));
        foreach ($zones as $zone_name) {
            if (strtolower($currentDomain) == strtolower($zone_name)) {
                return $zone_name;
            }
        }
    }

    return;
}

/**
 * @param $url       string      the URL to curl
 * @param $fields    array       an associative array of arguments for POSTing
 * @param $json      boolean     attempt to decode response as JSON
 *
 * @returns WP_ERROR|string|object in the case of an error, otherwise a $result string or JSON object
 */
function cloudflare_curl($url, $fields = array(), $json = true)
{
    $args = array(
        'method' => 'GET',
        'timeout' => 20,
        'sslverify' => true,
        'user-agent' => 'CloudFlare/WordPress/'.CLOUDFLARE_VERSION,
    );

    if (!empty($fields)) {
        $args['method'] = 'POST';
        $args['body'] = $fields;
    }

    $response = wp_remote_request($url, $args);

    // if we have an array, we have a HTTP Response
    if (is_array($response)) {
        // Always expect a HTTP 200 from the API

        // HERE BE DRAGONS
        // WP_HTTP does not return conistent types - cURL seems to return an int for the reponse code, streams returns a string.
        if (intval($response['response']['code']) !== 200) {
            // Invalid response code
            return new WP_Error('cloudflare', sprintf('CloudFlare API returned a HTTP Error: %s - %s', $response['response']['code'], $response['response']['message']));
        } else {
            if ($json == true) {
                $result = json_decode($response['body']);
                // not a perfect test, but better than nothing perhaps
                if ($result == null) {
                    return new WP_Error('json_decode', sprintf('Unable to decode JSON response'), $result);
                }

                // check for the CloudFlare API failure response
                if (property_exists($result, 'result') && $result->result !== 'success') {
                    $msg = 'Unknown Error';
                    if (property_exists($result, 'msg') && !empty($result->msg)) {
                        $msg = $result->msg;
                    }

                    return new WP_Error('cloudflare', $msg);
                }

                return $result;
            } else {
                return $response['body'];
            }
        }
    } elseif (is_wp_error($response)) {
        return $response;
    }

    // Should never happen!
    return new WP_Error('unknown_wp_http_error', sprintf('Unknown response from wp_remote_request - unable to contact CloudFlare API'));
}

function cloudflare_buffer_wrapup($buffer)
{
    $cloudflare_protocol_rewrite = load_protocol_rewrite();
    if ($cloudflare_protocol_rewrite) {
        // Check for a Content-Type header. Currently only apply rewriting to "text/html" or undefined
        $headers = headers_list();
        $content_type = null;

        foreach ($headers as $header) {
            if (strpos(strtolower($header), 'content-type:') === 0) {
                $pieces = explode(':', strtolower($header));
                $content_type = trim($pieces[1]);
                break;
            }
        }

        if (is_null($content_type) || substr($content_type, 0, 9) === 'text/html') {
            // replace href or src attributes within script, link, base, and img tags with just "//" for protocol
            $re = "/(<(script|link|base|img|form)([^>]*)(href|src|action)=[\"'])https?:\\/\\//i";
            $subst = '$1//';
            $return = preg_replace($re, $subst, $buffer);

            // on regex error, skip overwriting buffer
            if ($return) {
                $buffer = $return;
            }
        }
    }

    return $buffer;
}

function cloudflare_buffer_init()
{
    ob_start('cloudflare_buffer_wrapup');
}
// Protocol Rewrite Hook # 1
// ob_start (buffer) is also being used with HTTP2ServerPush. There can
// be conflicts between the buffers when protocol rewrite is reactivated.
// add_action('plugins_loaded', 'cloudflare_buffer_init');

// wordpress 4.4 srcset ssl fix
// Shoutout to @bhubbard: https://wordpress.org/support/topic/44-https-rewritte-aint-working-with-images?replies=12
function cloudflare_ssl_srcset($sources)
{
    $cloudflare_protocol_rewrite = load_protocol_rewrite();

    if ($cloudflare_protocol_rewrite) {
        foreach ($sources as &$source) {
            $re = '/https?:\\/\\//i';
            $subst = '//';
            $return = preg_replace($re, $subst, $source['url']);

            if ($return) {
                $source['url'] = $return;
            }
        }

        return $sources;
    }

    return $sources;
}
// Protocol Rewrite Hook # 2
// add_filter('wp_calculate_image_srcset', 'cloudflare_ssl_srcset');

function purgeCache()
{
    if (load_plugin_specific_cache()) {
        // Init in a hacky way
        // $parse_uri is [plugin_path]/wp-admin/admin-ajax.php
        // get plugin path
        $parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
        $path = explode('wp-admin', $parse_uri[0]);
        require_once $path[0].'wp-load.php';

        $config = new CF\Integration\DefaultConfig('[]');
        $logger = new CF\Integration\DefaultLogger($config->getValue('debug'));
        $dataStore = new CF\WordPress\DataStore($logger);
        $wordpressAPI = new CF\WordPress\WordPressAPI($dataStore);
        $wordpressIntegration = new CF\Integration\DefaultIntegration($config, $wordpressAPI, $dataStore, $logger);
        $clientAPIClient = new CF\WordPress\WordPressClientAPI($wordpressIntegration);

        $wp_domain = $wordpressAPI->getDomainList()[0];
        if (count($wp_domain) > 0) {
            $zoneTag = $clientAPIClient->getZoneTag($wp_domain);
            if (isset($zoneTag)) {
                // Do not care of the return value
                $clientAPIClient->zonePurgeCache($zoneTag);
            }
        }
    }
}

function sslRewrite()
{
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $_SERVER['HTTPS'] = 'on';

        return true;
    }

    return false;
}

// "Save and Activate" pressed
function switch_wp_theme()
{
    // Purge cache when theme is switched.
    purgeCache();
}
add_action('switch_theme', 'switch_wp_theme');

// "Save and Publish" pressed
function theme_save_pressed()
{
    purgeCache();
}
add_action('customize_save_after', 'theme_save_pressed');

// Enable HTTP2 Server Push
require_once plugin_dir_path(__FILE__).'src/HTTP2ServerPush.php';
add_action('init', array('HTTP2ServerPush', 'init'));
