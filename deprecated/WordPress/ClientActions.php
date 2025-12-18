<?php
namespace CF\WordPress;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\ClientActions instead.
 */
class ClientActions extends \Cloudflare\APO\WordPress\ClientActions {
    public function __construct(\Cloudflare\APO\Integration\IntegrationInterface $integrationContext, \Cloudflare\APO\API\APIInterface $api, \Cloudflare\APO\API\Request $request) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\ClientActions", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        parent::__construct($integrationContext, $api, $request);
    }
}
