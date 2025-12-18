<?php
namespace CF\WordPress;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\PluginActions instead.
 */
class PluginActions extends \Cloudflare\APO\WordPress\PluginActions {
    public function __construct(\Cloudflare\APO\Integration\IntegrationInterface $integrationContext, \Cloudflare\APO\API\APIInterface $api, \Cloudflare\APO\API\Request $request) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\PluginActions", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        parent::__construct($integrationContext, $api, $request);
    }
}
