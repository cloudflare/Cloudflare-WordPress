<?php
namespace CF\WordPress;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\Proxy instead.
 */
class Proxy extends \Cloudflare\APO\WordPress\Proxy {
    public function __construct(\Cloudflare\APO\Integration\IntegrationInterface $integrationContext) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\Proxy", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        parent::__construct($integrationContext);
    }
}
