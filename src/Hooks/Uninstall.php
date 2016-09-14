<?php

namespace CF\Hooks;

class Uninstall
{
    public static function init()
    {
        // Create temp objects because we can't trust for them to be initialized
        $config = new \CF\Integration\DefaultConfig('[]');
        $logger = new \CF\Integration\DefaultLogger($config->getValue('debug'));
        $dataStore = new \CF\WordPress\DataStore($logger);
        $wordpressAPI = new \CF\WordPress\WordPressAPI($dataStore);

        $wordpressAPI->clearDataStore();
    }
}
