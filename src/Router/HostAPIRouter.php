<?php

namespace CF\Router;

use CF\API\Request;

class HostAPIRouter extends DefaultRestAPIRouter implements RouterInterface
{

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPath(Request $request)
    {
        return $request->getBody()['act'];
    }
}
