<?php

namespace CF\WordPress;

class PluginRoutes
{
    public static $routes = array(
        'account' => array(
            'class' => 'CF\WordPress\PluginActions',
            'methods' => array(
                'POST' => array(
                    'function' => 'loginWordPress',
                ),
            ),
        ),

        'zones/:zoneId/settings' => array(
            'class' => 'CF\WordPress\PluginActions',
            'methods' => array(
                'GET' => array(
                    'function' => 'getPluginSettings',
                ),
            ),
        ),

        'zones/:zoneId/settings/:settingId' => array(
            'class' => 'CF\WordPress\PluginActions',
            'methods' => array(
                'PATCH' => array(
                    'function' => 'patchPluginSettings',
                ),
            ),
        ),
    );
}
