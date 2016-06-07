<?php

namespace CF\WordPress;

use CF\API\APIInterface;
use CF\API\Request;
use CF\Integration\DefaultIntegration;

class PluginActions
{
    private $api;
    private $config;
    private $wordpressAPI;
    private $dataStore;
    private $logger;
    private $request;

    /**
     * @param DefaultIntegration $defaultIntegration
     * @param APIInterface       $api
     * @param Request            $request
     */
    public function __construct(DefaultIntegration $defaultIntegration, APIInterface $api, Request $request)
    {
        $this->api = $api;
        $this->config = $defaultIntegration->getConfig();
        $this->wordpressAPI = $defaultIntegration->getIntegrationAPI();
        $this->dataStore = $defaultIntegration->getDataStore();
        $this->logger = $defaultIntegration->getLogger();
        $this->request = $request;
    }

    /**
     * GET /zones.
     *
     * @return mixed
     */
    public function loginWordPress()
    {
        $apiKey = $this->request->getBody()['apiKey'];
        $email = $this->request->getBody()['email'];

        // TODO: what should the exact response be?
        $response = array(
            'success' => 'true',
            'result' => array(
                    'email' => $email,
                ),
            );

        $this->dataStore->createUserDataStore($apiKey, $email, null, null);

        return $response;
    }
}
