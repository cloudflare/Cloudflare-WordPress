<?php

namespace Cloudflare\APO\Router;

use Cloudflare\APO\API\Request;

interface RouterInterface
{
    public function route(Request $request);
    public function getAPIClient();
}
