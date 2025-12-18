<?php
namespace CF\WordPress;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\Utils instead.
 */
class Utils extends \Cloudflare\APO\WordPress\Utils {
    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\Utils::strEndsWith() instead.
     */
    public static function strEndsWith($haystack, $needle) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\Utils::strEndsWith", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        return parent::strEndsWith($haystack, $needle);
    }

    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\Utils::isSubdomainOf() instead.
     */
    public static function isSubdomainOf($subDomainName, $domainName) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\Utils::isSubdomainOf", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        return parent::isSubdomainOf($subDomainName, $domainName);
    }

    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\Utils::getRegistrableDomain() instead.
     */
    public static function getRegistrableDomain($domainName) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\Utils::getRegistrableDomain", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        return parent::getRegistrableDomain($domainName);
    }

    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\Utils::getComposerJson() instead.
     */
    public static function getComposerJson(): array {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\Utils::getComposerJson", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        return parent::getComposerJson();
    }
}
