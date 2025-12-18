<?php
namespace CF\API;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\API\AbstractPluginActions instead.
 */
abstract class AbstractPluginActions extends \Cloudflare\APO\API\AbstractPluginActions {
    public function __construct(
        \Cloudflare\APO\Integration\DefaultIntegration $defaultIntegration,
        \Cloudflare\APO\API\APIInterface $api,
        \Cloudflare\APO\API\Request $request
    ) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\API\AbstractPluginActions", 'CF\API namespace is deprecated, use Cloudflare\APO\API instead.' );
        parent::__construct($defaultIntegration, $api, $request);
    }
}
