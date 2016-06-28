<?php

namespace CF\API;

use CF\API\Client;
use CF\API\Request;

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

    /**
     * @param Request $request
     *
     * @return array|mixed
     */
    public function callAPI(Request $request)
    {
        $this->logger->error('CF\\Wordpress\\API\\Plugin\\callAPI should never be called');

        return $this->createAPIError('The url: '.$request->getUrl().' is not a valid path.');
    }

    public function createAPISuccessResponse($result)
    {
        return array(
            'success' => true,
            'result' => $result,
            'messages' => [],
            'errors' => [],
        );
    }

    public function createPluginResult($id, $value, $editable, $modified_on)
    {
        return array(
            'id' => $id,
            'value' => $value,
            'editable' => $editable,
            'modified_on' => $modified_on,
        );
    }
}
