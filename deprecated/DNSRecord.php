<?php
namespace CF;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\DNSRecord instead.
 */
class DNSRecord extends \Cloudflare\APO\DNSRecord {
    public function __construct() {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\DNSRecord", 'CF namespace is deprecated, use Cloudflare\APO instead.' );
    }
}
