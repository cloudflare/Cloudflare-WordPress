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

        $isCreated = $this->dataStore->createUserDataStore($apiKey, $email, null, null);

        if (!$isCreated) {
            $this->logger->error('Creating user data to store failed');

            return $this->api->createAPIError('Unable to save user credentials');
        }

        $response = $this->api->createAPISuccessResponse(array('email' => $email));

        return $response;
    }

    /**
     * GET /plugin/:zonedId/settings.
     *
     * @return mixed
     */
    public function getPluginSettings()
    {
        // Always return true
        $value = true;//$this->dataStore->getIpRewrite();

        $response = $this->api->createAPISuccessResponse(
            array(
                array(
                'id' => 'ip_rewrite',
                'value' => $value,
                'editable' => true,
                'modified_on' => '',
                ),
            )
        );

        $response['errors'] = []; // TODO: This doesn't seem a nice way
        $response['messages'] = [];

        return $response;
    }

    /**
     * PATCH /plugin/:zonedId/settings/:settingId.
     *
     * @return mixed
     */
    public function patchPluginSettings()
    {
        $path_array = explode('/', $this->request->getUrl());
        $settingId = $path_array[3];

        $value = $this->request->getBody()['value'];
        $isOK = $this->dataStore->setIpRewrite($value);

        if (!$isOK) {
            return $this->api->createAPIError('Unable to update plugin settings');
        }

        $response = $this->api->createAPISuccessResponse(
            array(
                array(
                    'id' => $settingId,
                    'value' => $value,
                    'editable' => true,
                    'modified_on' => '',
                ),
            )
        );

        $response['errors'] = []; // TODO: This doesn't seem a nice way
        $response['messages'] = [];

        return $response;
    }
}
