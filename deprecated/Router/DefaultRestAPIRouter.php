<?php
namespace CF\Router;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\Router\DefaultRestAPIRouter instead.
 */
class DefaultRestAPIRouter extends \Cloudflare\APO\Router\DefaultRestAPIRouter {
    public function __construct(\Cloudflare\APO\Integration\IntegrationInterface $integration, \Cloudflare\APO\API\APIInterface $api, $routes) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\Router\DefaultRestAPIRouter", 'CF\Router namespace is deprecated, use Cloudflare\APO\Router instead.' );
        parent::__construct($integration, $api, $routes);
    }
}
