<?php
namespace CF\WordPress;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\Hooks instead.
 */
class Hooks extends \Cloudflare\APO\WordPress\Hooks {
    public function __construct() {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\Hooks", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        parent::__construct();
    }
}
