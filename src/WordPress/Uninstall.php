<?php

namespace CF\WordPress;

// Uninstall must have it's own class since WordPress doesn't accept
// non-static class function for register_uninstall_hook
class Uninstall
{
    public static function init()
    {
        // Create temp objects because to call clearDataStore
        $config = new \CF\Integration\DefaultConfig('[]');
        $logger = new \CF\Integration\DefaultLogger($config->getValue('debug'));
        $dataStore = new \CF\WordPress\DataStore($logger);

        $dataStore->clearDataStore();
    }
}
