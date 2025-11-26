<?php

namespace Cloudflare\APO\API;

interface HttpClientInterface
{
    /**
     * @param  Request $request
     * @return Array   $response
     */
    public function send(Request $request);
}
