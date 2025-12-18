<?php
namespace CF;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\Utils instead.
 */
class Utils extends \Cloudflare\APO\Utils {
    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\Utils::getCurrentDate() instead.
     */
    public static function getCurrentDate() {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\Utils::getCurrentDate", 'CF namespace is deprecated, use Cloudflare\APO instead.' );
        return parent::getCurrentDate();
    }
}
