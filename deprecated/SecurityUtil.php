<?php
namespace CF;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\SecurityUtil instead.
 */
class SecurityUtil extends \Cloudflare\APO\SecurityUtil {
    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\SecurityUtil::generate16bytesOfSecureRandomData() instead.
     */
    public static function generate16bytesOfSecureRandomData() {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\SecurityUtil::generate16bytesOfSecureRandomData", 'CF namespace is deprecated, use Cloudflare\APO instead.' );
        return parent::generate16bytesOfSecureRandomData();
    }

    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\SecurityUtil::csrfTokenGenerate() instead.
     */
    public static function csrfTokenGenerate($secret, $user, $timeValidUntil = null) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\SecurityUtil::csrfTokenGenerate", 'CF namespace is deprecated, use Cloudflare\APO instead.' );
        return parent::csrfTokenGenerate($secret, $user, $timeValidUntil);
    }

    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\SecurityUtil::csrfTokenValidate() instead.
     */
    public static function csrfTokenValidate($secret, $user, $token) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\SecurityUtil::csrfTokenValidate", 'CF namespace is deprecated, use Cloudflare\APO instead.' );
        return parent::csrfTokenValidate($secret, $user, $token);
    }
}
