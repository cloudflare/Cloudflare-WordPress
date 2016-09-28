<?php

require_once 'vendor/autoload.php';

use CloudFlare\IpRewrite;

// Rewrites Cloudflare IP
$ipRewrite = new IpRewrite();

$is_cf = $ipRewrite->isCloudFlare();
if ($is_cf) {
    // Fixes Flexible SSL
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $_SERVER['HTTPS'] = 'on';
    }
}

// Enable HTTP2 Server Push
// add_action('init', array('\CF\Hooks\HTTP2ServerPush', 'init'));

if (is_admin()) {
    // Initiliaze Hooks class which contains WordPress hook functions
    $cloudflareHooks = new \CF\WordPress\Hooks();

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

    // Load Automatic Cache Purge
    add_action('switch_theme', array($cloudflareHooks, 'purgeCache'));
    add_action('customize_save_after', array($cloudflareHooks, 'purgeCache'));
}
