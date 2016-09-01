<?php
/*
Plugin Name: CloudFlare
Plugin URI: http://www.cloudflare.com/wiki/CloudFlareWordPressPlugin
Description: CloudFlare integrates your blog with the CloudFlare platform.
Version: 2.1.0-beta
Author: Ian Pye, Jerome Chen, James Greene, Simon Moore, David Fritsch, John Wineman (CloudFlare Team)
License: GPLv2
*/

require_once 'vendor/autoload.php';

use \CloudFlare\IpRewrite;

const MIN_PHP_VERSION = '5.3';
const MIN_WP_VERSION = '3.4';

if (!defined('ABSPATH')) { // Exit if accessed directly
    exit;
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

    return $dataStore->getPluginSetting(CF\API\Plugin::SETTING_PLUGIN_SPECIFIC_CACHE)[\CF\Integration\DataStoreInterface::VALUE_KEY];
}

function cloudflare_conf2()
{
    include 'cloudflare2.php';
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

function purgeCache()
{
    if (load_plugin_specific_cache()) {
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
add_action('init', array('\CF\Hooks\HTTP2ServerPush', 'init'));

// Load Uninstall Script
register_uninstall_hook(__FILE__, array('\CF\Hooks\Uninstall', 'init'));
