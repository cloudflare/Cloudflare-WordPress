<?php

namespace CF\Hooks;

use CloudFlare\IpRewrite;

class Init
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::initHooks();
        }

        self::cloudflareInit();
    }

    public static function initHooks()
    {
        self::$initiated = true;

        add_action('admin_menu', array('\CF\Hooks\Init', 'cloudflareConfigPage'));
        add_action('admin_init', array('\CF\Hooks\Init', 'cloudflareAdminInit'));
        add_action('plugin_action_links_cloudflare/cloudflare.php', array('\CF\Hooks\Init', 'pluginActionLinks'));
    }

    public static function cloudflareInit()
    {
        $ipRewrite = new IpRewrite();
        $is_cf = $ipRewrite->isCloudFlare();
        if ($is_cf) {
            // The HTTP Request is from Cloudflare. Ip is rewritten successfully.
            // For more info: github.com/cloudflare/cf-ip-rewrite
            self::sslRewrite();
        }
    }

    public static function cloudflareConfigPage()
    {
        if (function_exists('add_options_page')) {
            add_options_page(__('CloudFlare Configuration'), __('CloudFlare'), 'manage_options', 'cloudflare', array('\CF\Hooks\Init', 'cloudflareIndexPage'));
        }
    }

    public static function cloudflareIndexPage()
    {
        include WP_PLUGIN_DIR.'/cloudflare/index.php';
    }

    public static function sslRewrite()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $_SERVER['HTTPS'] = 'on';

            return true;
        }

        return false;
    }

    public static function pluginActionLinks($links)
    {
        $links[] = '<a href="'.get_admin_url(null, 'options-general.php?page=cloudflare').'">Settings</a>';

        return $links;
    }

    public static function cloudflareAdminInit()
    {
        // NARNIA!!
    }

    public static function initProxy()
    {
        include WP_PLUGIN_DIR.'/cloudflare/proxy.php';
    }
}
