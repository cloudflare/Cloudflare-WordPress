<?php

namespace CF\Hooks;

class Activation
{
    public static function init()
    {
        self::checkVersionCompatiblity();
    }

    public static function checkVersionCompatiblity()
    {
        global $wp_version;

        if (version_compare(PHP_VERSION, CF_MIN_PHP_VERSION, '<')) {
            $flag = 'PHP';
            $version = CF_MIN_PHP_VERSION;
        }

        if (version_compare($wp_version, CF_MIN_WP_VERSION, '<')) {
            $flag = 'WordPress';
            $version = CF_MIN_WP_VERSION;
        }

        if (isset($flag) || isset($version)) {
            // Deactivate Plugin
            deactivate_plugins(basename(__FILE__));

            // Kill Execution
            wp_die('<p><strong>Cloudflare</strong> plugin requires '.$flag.'  version '.$version.' or greater.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));

            return;
        }
    }
}
