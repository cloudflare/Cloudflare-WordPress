<?php

namespace Cloudflare\APO\WordPress;

class PluginRoutes extends \Cloudflare\APO\API\PluginRoutes
{
    /**
     * @param $routeList
     *
     * @return mixed
     */
    public static function getRoutes($routeList)
    {
        foreach ($routeList as $routePath => $route) {
            $route['class'] = '\Cloudflare\APO\WordPress\PluginActions';
            $routeList[$routePath] = $route;
        }

        return $routeList;
    }
}
