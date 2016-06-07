<?php

namespace CF\API;

class Plugin extends Client
{
    const PLUGIN_API_NAME = 'PLUGIN API';
    const ENDPOINT = 'https://partners.cloudflare/plugins/';

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return self::ENDPOINT;
    }

    /**
     * @return string
     */
    public function getAPIClientName()
    {
        return self::PLUGIN_API_NAME;
    }
}
