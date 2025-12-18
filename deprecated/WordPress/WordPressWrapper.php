<?php
namespace CF\WordPress;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\WordPressWrapper instead.
 */
class WordPressWrapper extends \Cloudflare\APO\WordPress\WordPressWrapper {
    public function __construct() {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\WordPressWrapper", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
    }
}
