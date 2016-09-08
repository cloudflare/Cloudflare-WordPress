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

function cloudflare_conf2()
{
    include 'cloudflare2.php';
}

function sslRewrite()
{
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $_SERVER['HTTPS'] = 'on';

        return true;
    }

    return false;
}

// Load Activation Script
register_activation_hook(__FILE__, array('\CF\Hooks\Activation', 'init'));

// Load Deactivation Script
register_deactivation_hook(__FILE__, array('\CF\Hooks\Deactivation', 'init'));

// Load Uninstall Script
register_uninstall_hook(__FILE__, array('\CF\Hooks\Uninstall', 'init'));

// Load AutomaticCache
add_action('init', array('\CF\Hooks\AutomaticCache', 'init'));

// Enable HTTP2 Server Push
add_action('init', array('\CF\Hooks\HTTP2ServerPush', 'init'));
