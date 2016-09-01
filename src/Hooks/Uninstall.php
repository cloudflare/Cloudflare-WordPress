<?php

namespace CF\Hooks;

use CF\API\Plugin;
use CF\WordPress\DataStore;

class Uninstall
{
    public static function init()
    {
        $pluginKeys = Plugin::getPluginSettingsKeys();

        // Delete Plugin Setting Options
        foreach ($pluginKeys as $optionName) {
            delete_option($optionName);
        }

        // Delete DataStore Options
        delete_option(DataStore::API_KEY);
        delete_option(DataStore::EMAIL);
        delete_option(DataStore::CACHED_DOMAIN_NAME);
    }
}
