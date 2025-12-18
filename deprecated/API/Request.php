<?php
namespace CF\API;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\API\Request instead.
 */
class Request extends \Cloudflare\APO\API\Request {
    public function __construct($method, $url, $parameters, $body) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\API\Request", 'CF\API namespace is deprecated, use Cloudflare\APO\API instead.' );
        parent::__construct($method, $url, $parameters, $body);
    }
}
