<?php
namespace CF\WordPress;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\DataStore instead.
 */
class DataStore extends \Cloudflare\APO\WordPress\DataStore {
    public function __construct(\CF\Integration\DefaultLogger $logger) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\DataStore", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        parent::__construct($logger);
    }
}
