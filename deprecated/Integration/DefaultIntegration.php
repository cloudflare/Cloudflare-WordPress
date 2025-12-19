<?php
namespace CF\Integration;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\Integration\DefaultIntegration instead.
 */
class DefaultIntegration extends \Cloudflare\APO\Integration\DefaultIntegration {
    public function __construct(
        $config,
        $integrationAPI,
        $dataStore,
        $logger
    ) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\Integration\DefaultIntegration", 'CF\Integration namespace is deprecated, use Cloudflare\APO\Integration instead.' );
        parent::__construct($config, $integrationAPI, $dataStore, $logger);
    }
}
