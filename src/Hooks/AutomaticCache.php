<?php

namespace CF\Hooks;

class AutomaticCache
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::initHooks();
        }
    }

    public static function initHooks()
    {
        self::$initiated = true;

        add_action('switch_theme', array('\CF\Hooks\AutomaticCache', 'switchWPTheme'));
        add_action('customize_save_after', array('\CF\Hooks\AutomaticCache', 'themeSaveButtonPressed'));
    }

    // "Save and Activate" pressed
    public static function switchWPTheme()
    {
        // Purge cache when theme is switched.
        self::purgeCache();
    }

    // "Save and Publish" pressed
    public static function themeSaveButtonPressed()
    {
        self::purgeCache();
    }

    // Purges everything
    public static function purgeCache()
    {
        if (self::isPluginSpecificCacheEnabled()) {
            $config = new \CF\Integration\DefaultConfig('[]');
            $logger = new \CF\Integration\DefaultLogger($config->getValue('debug'));
            $dataStore = new \CF\WordPress\DataStore($logger);
            $wordpressAPI = new \CF\WordPress\WordPressAPI($dataStore);
            $wordpressIntegration = new \CF\Integration\DefaultIntegration($config, $wordpressAPI, $dataStore, $logger);
            $clientAPIClient = new \CF\WordPress\WordPressClientAPI($wordpressIntegration);

            $wp_domain_list = $wordpressAPI->getDomainList();
            $wp_domain = $wp_domain_list[0];
            if (count($wp_domain) > 0) {
                $zoneTag = $clientAPIClient->getZoneTag($wp_domain);

                if (isset($zoneTag)) {
                    // Do not care of the return value
                    $clientAPIClient->zonePurgeCache($zoneTag);
                }
            }
        }
    }

    public static function isPluginSpecificCacheEnabled()
    {
        // TODO: refactor so we're only initing this stuff once.
        $config = new \CF\Integration\DefaultConfig('[]');
        $logger = new \CF\Integration\DefaultLogger($config->getValue('debug'));
        $dataStore = new \CF\WordPress\DataStore($logger);

        $cacheSettingObject = $dataStore->getPluginSetting(\CF\API\Plugin::SETTING_PLUGIN_SPECIFIC_CACHE);
        $cacheSettingValue = $cacheSettingObject[\CF\API\Plugin::SETTING_VALUE_KEY];

        return $cacheSettingValue;
    }
}
