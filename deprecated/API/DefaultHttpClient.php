<?php
namespace CF\API;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\API\DefaultHttpClient instead.
 */
class DefaultHttpClient extends \Cloudflare\APO\API\DefaultHttpClient {
    public function __construct($endpoint) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\API\DefaultHttpClient", 'CF\API namespace is deprecated, use Cloudflare\APO\API instead.' );
        parent::__construct($endpoint);
    }
}
