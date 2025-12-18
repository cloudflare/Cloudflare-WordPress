<?php
namespace CF\Router;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\Router\RequestRouter instead.
 */
class RequestRouter extends \Cloudflare\APO\Router\RequestRouter {
    public function __construct(\Cloudflare\APO\Integration\IntegrationInterface $integrationContext) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\Router\RequestRouter", 'CF\Router namespace is deprecated, use Cloudflare\APO\Router instead.' );
        parent::__construct($integrationContext);
    }
}
