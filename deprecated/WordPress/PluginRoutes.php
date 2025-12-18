<?php
namespace CF\WordPress;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\PluginRoutes instead.
 */
class PluginRoutes extends \Cloudflare\APO\WordPress\PluginRoutes {
    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\PluginRoutes::getRoutes() instead.
     */
    public static function getRoutes($routeList) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\PluginRoutes::getRoutes", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        return parent::getRoutes($routeList);
    }
}
