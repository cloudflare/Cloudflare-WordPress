<?php

require_once __DIR__.'/vendor/autoload.php';

use CloudFlare\IpRewrite;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Rewrites Cloudflare IP
$ipRewrite = new IpRewrite();

$is_cf = $ipRewrite->isCloudFlare();
if ($is_cf) {
    // Fixes Flexible SSL
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $_SERVER['HTTPS'] = 'on';
    }
}

// Initiliaze Hooks class which contains WordPress hook functions
$cloudflareHooks = new \CF\WordPress\Hooks();

// Enable HTTP2 Server Push
if (defined('CLOUDFLARE_HTTP2_SERVER_PUSH_ACTIVE') && CLOUDFLARE_HTTP2_SERVER_PUSH_ACTIVE) {
    add_action('init', array($cloudflareHooks, 'http2ServerPushInit'));
}

if (is_admin()) {
    //Register proxy AJAX endpoint
    add_action('wp_ajax_cloudflare_proxy', array($cloudflareHooks, 'initProxy'));

    //Add CloudFlare Plugin homepage to admin settings menu
    add_action('admin_menu', array($cloudflareHooks, 'cloudflareConfigPage'));

    //Add CloudFlare Plugin homepage to admin settings menu
    add_action('plugin_action_links_cloudflare/cloudflare.php', array($cloudflareHooks, 'pluginActionLinks'));

    // Load Activation Script
    register_activation_hook(CLOUDFLARE_PLUGIN_DIR.'cloudflare.php', array($cloudflareHooks, 'activate'));

    // Load Deactivation Script
    register_deactivation_hook(CLOUDFLARE_PLUGIN_DIR.'cloudflare.php', array($cloudflareHooks, 'deactivate'));
}

// Load Automatic Cache Purge
add_action('switch_theme', array($cloudflareHooks, 'purgeCacheEverything'));
add_action('customize_save_after', array($cloudflareHooks, 'purgeCacheEverything'));

$cloudflarePurgeActions = array(
    'autoptimize_action_cachepurged',   // Compat with https://wordpress.org/plugins/autoptimize
    'deleted_post',                     // Delete a post
    'edit_post',                        // Edit a post - includes leaving comments
    'delete_attachment',                // Delete an attachment - includes re-uploading
);

foreach ($cloudflarePurgeActions as $action) {
    add_action($action, array($cloudflareHooks, 'purgeCacheByRevelantURLs'), 10, 2);
}
