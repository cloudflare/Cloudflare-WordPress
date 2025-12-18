<?php
namespace CF\API;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\API\Client instead.
 */
class Client extends \Cloudflare\APO\API\Client {
    public function __construct(\Cloudflare\APO\Integration\IntegrationInterface $integration) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\API\Client", 'CF\API namespace is deprecated, use Cloudflare\APO\API instead.' );
        parent::__construct($integration);
    }
}
