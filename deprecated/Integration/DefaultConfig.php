<?php
namespace CF\Integration;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\Integration\DefaultConfig instead.
 */
class DefaultConfig extends \Cloudflare\APO\Integration\DefaultConfig {
    public function __construct($config = "[]") {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\Integration\DefaultConfig", 'CF\Integration namespace is deprecated, use Cloudflare\APO\Integration instead.' );
        parent::__construct($config);
    }
}
