<?php
namespace CF\API;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\API\AbstractAPIClient instead.
 */
abstract class AbstractAPIClient extends \Cloudflare\APO\API\AbstractAPIClient {
    public function __construct(\Cloudflare\APO\Integration\IntegrationInterface $integration) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\API\AbstractAPIClient", 'CF\API namespace is deprecated, use Cloudflare\APO\API instead.' );
        parent::__construct($integration);
    }
}
