<?php
/*
Plugin Name: CloudFlare
Plugin URI: http://www.cloudflare.com/wiki/CloudFlareWordPressPlugin
Description: CloudFlare integrates your blog with the CloudFlare platform.
Version: 3.0.3
Author: John Wineman, Furkan Yilmaz, Junade Ali (CloudFlare Team)
License: BSD-3-Clause
*/

require_once 'vendor/autoload.php';

if (!defined('ABSPATH')) { // Exit if accessed directly
    exit;
}

define('CLOUDFLARE_MIN_WP_VERSION', '3.4');
define('CLOUDFLARE_MIN_PHP_VERSION', '5.3.10');
define('CLOUDFLARE_PLUGIN_DIR', plugin_dir_path(__FILE__));

// PHP version check must be either in register_activation_hook or
// admin_init. register_activation_hook doesn't handle upgrading 
// the plugin case but admin_init does.
add_action('admin_init', 'cloudflare_admin_init');

// Fixes Flexible SSL
$cloudflareHttpsServerOptions = array('HTTP_CF_VISITOR', 'HTTP_X_FORWARDED_PROTO');
foreach ($cloudflareHttpsServerOptions as $option) {
    if (isset($_SERVER[$option]) && $_SERVER[$option] == 'https') {
        $_SERVER['HTTPS'] = 'on';
        break;
    }
}

// ************************************************************** //

// Initialize Global Objects
$cloudflareConfig = new CF\Integration\DefaultConfig(file_get_contents('config.js', true));
$cloudflareLogger = new CF\Integration\DefaultLogger($cloudflareConfig->getValue('debug'));
$cloudflareDataStore = new CF\WordPress\DataStore($cloudflareLogger);
$cloudflareWordpressAPI = new CF\WordPress\WordPressAPI($cloudflareDataStore);
$cloudflareWordpressIntegration = new CF\Integration\DefaultIntegration($cloudflareConfig, $cloudflareWordpressAPI, $cloudflareDataStore, $cloudflareLogger);

// ************************************************************** //

// Initiliaze Hooks class which contains WordPress hook functions
$cloudflareHooks = new \CF\WordPress\Hooks($cloudflareWordpressIntegration);

//Register proxy AJAX endpoint
add_action('wp_ajax_cloudflare_proxy', array($cloudflareHooks, 'initProxy'));

//Add CloudFlare Plugin homepage to admin settings menu
add_action('admin_menu', array($cloudflareHooks, 'cloudflareConfigPage'));

//Add CloudFlare Plugin homepage to admin settings menu
add_action('plugin_action_links_cloudflare/cloudflare.php', array($cloudflareHooks, 'pluginActionLinks'));

// Load Activation Script
register_activation_hook(__FILE__, array($cloudflareHooks, 'activate'));

// Load Deactivation Script
register_deactivation_hook(__FILE__, array($cloudflareHooks, 'deactivate'));

// Load Uninstall Script
register_uninstall_hook(__FILE__, array('\CF\WordPress\Hooks', 'uninstall'));

// Load AutomaticCache
add_action('switch_theme', array($cloudflareHooks, 'purgeCache'));
add_action('customize_save_after', array($cloudflareHooks, 'purgeCache'));

// Enable HTTP2 Server Push
// add_action('init', array('\CF\Hooks\HTTP2ServerPush', 'init'));

function cloudflare_admin_init()
{
    // PHP version check has to go here because the below code uses namespaces
    if (version_compare(PHP_VERSION, CLOUDFLARE_MIN_PHP_VERSION, '<')) {
        deactivate_plugins(plugin_basename(__FILE__), true);
        wp_die('<p>The CloudFlare plugin requires a php version of at least '.CLOUDFLARE_MIN_PHP_VERSION.' you have '.PHP_VERSION.'.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));
    }
}
