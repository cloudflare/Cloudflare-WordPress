<?php

namespace Cloudflare\APO\WordPress;

class ClientRoutes
{
    public static $routes = array(
        'zones' => array(
            'class' => 'Cloudflare\APO\WordPress\ClientActions',
            'methods' => array(
                'GET' => array(
                    'function' => 'returnWordPressDomain',
                ),
            ),
        ),
    );
}
