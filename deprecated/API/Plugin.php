<?php
namespace CF\API;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\API\Plugin instead.
 */
class Plugin extends \Cloudflare\APO\API\Plugin {
    public function __construct(\Cloudflare\APO\Integration\IntegrationInterface $integration) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\API\Plugin", 'CF\API namespace is deprecated, use Cloudflare\APO\API instead.' );
        parent::__construct($integration);
    }

    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\API\Plugin::getPluginSettingsKeys() instead.
     */
    public static function getPluginSettingsKeys() {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\API\Plugin::getPluginSettingsKeys", 'CF\API namespace is deprecated, use Cloudflare\APO\API instead.' );
        return parent::getPluginSettingsKeys();
    }
}
