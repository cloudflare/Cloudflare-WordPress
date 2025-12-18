<?php
namespace CF\Integration;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\Integration\DefaultLogger instead.
 */
class DefaultLogger extends \Cloudflare\APO\Integration\DefaultLogger {
    public function __construct($debug = false) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\Integration\DefaultLogger", 'CF\Integration namespace is deprecated, use Cloudflare\APO\Integration instead.' );
        parent::__construct($debug);
    }
}
