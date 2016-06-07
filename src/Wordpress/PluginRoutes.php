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
    );
}
