<?php

namespace CF\Hooks;

class Activation
{
    public static function init()
    {
        self::checkVersionCompatibility();
        self::checkDependenciesExist();
    }

    public static function checkVersionCompatibility()
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

    public static function checkDependenciesExist()
    {
        // Guzzle3 depends on php5-curl. If dependency does not exist kill the plugin.
        if (!extension_loaded('curl')) {
            // Deactivate Plugin
            deactivate_plugins(basename(__FILE__));

            wp_die('<p><strong>Cloudflare</strong> plugin requires php5-curl to be installed.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));

            return;
        }
    }
}
