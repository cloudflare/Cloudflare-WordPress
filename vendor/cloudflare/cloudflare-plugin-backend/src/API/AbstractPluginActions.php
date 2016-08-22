<?php

namespace CF\API;

use CF\Integration\DataStoreInterface;
use CF\Integration\DefaultIntegration;
use CF\WordPress\Constants\Exceptions\PageRuleLimitException;
use CF\WordPress\Constants\Exceptions\ZoneSettingFailException;
use CF\WordPress\Constants\Plans;

abstract class AbstractPluginActions
{
    protected $api;
    protected $config;
    protected $integrationAPI;
    protected $dataStore;
    protected $logger;
    protected $request;
    protected $clientAPI;

    /**
     * @param DefaultIntegration $defaultIntegration
     * @param APIInterface       $api
     * @param Request            $request
     */
    public function __construct(DefaultIntegration $defaultIntegration, APIInterface $api, Request $request)
    {
        $this->api = $api;
        $this->config = $defaultIntegration->getConfig();
        $this->integrationAPI = $defaultIntegration->getIntegrationAPI();
        $this->dataStore = $defaultIntegration->getDataStore();
        $this->logger = $defaultIntegration->getLogger();
        $this->request = $request;

        $this->clientAPI = new Client($defaultIntegration);
    }

    /**
     * @param APIInterface $api
     */
    public function setAPI(APIInterface $api) {
        $this->api = $api;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request) {
        $this->request = $request;
    }

    /**
     * @param APIInterface $clientAPI
     */
    public function setClientAPI(APIInterface $clientAPI) {
        $this->clientAPI = $clientAPI;
    }

    /**
     * @param DataStoreInterface $dataStore
     */
    public function setDataStore(DataStoreInterface $dataStore) {
        $this->dataStore = $dataStore;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * POST /account.
     *
     * @return mixed
     */
    public function login()
    {
        $requestBody = $this->request->getBody();
        if(empty($requestBody["apiKey"])) {
            return $this->api->createAPIError("Missing required parameter: 'apiKey'.");
        }
        if(empty($requestBody["email"])) {
            return $this->api->createAPIError("Missing required parameter: 'email'.");
        }

        $isCreated = $this->dataStore->createUserDataStore($requestBody["apiKey"], $requestBody["email"], null, null);

        if (!$isCreated) {
            $this->logger->error('Creating user data to store failed');

            return $this->api->createAPIError('Unable to save user credentials');
        }

        $response = $this->api->createAPISuccessResponse(array('email' => $requestBody["email"]));

        return $response;
    }

    /**
     * GET /plugin/:zonedId/settings.
     *
     * @return mixed
     */
    public function getPluginSettings()
    {
        $settingsList = Plugin::getPluginSettingsKeys();

        $formattedSettings = array();
        foreach ($settingsList as $setting) {
            $value = $this->dataStore->get($setting);
            array_push($formattedSettings, $this->api->createPluginResult($setting, $value, true, ''));
        }

        $response = $this->api->createAPISuccessResponse(
            $formattedSettings
        );

        return $response;
    }

    /**
     * PATCH /plugin/:zonedId/settings/:settingId.
     *
     * Routes custom settingIds and default settingsIds
     * to different functions
     *
     * @return mixed
     */
    public function patchPluginSettingsRouter()
    {
        $path_array = explode('/', $this->request->getUrl());
        $settingId = $path_array[3];

        $response = null;
        if ($settingId === Plugin::SETTING_DEFAULT_SETTINGS) {
            $response = $this->patchPluginDefaultSettings();
        } else {
            $response = $this->patchPluginSettings();
        }

        return $response;
    }

    /**
     * For PATCH /plugin/:zonedId/settings/:settingId where :settingId is not predefined.
     *
     * @return mixed
     */
    public function patchPluginSettings()
    {
        $path_array = explode('/', $this->request->getUrl());
        $settingId = $path_array[3];

        $value = $this->request->getBody()['value'];
        $options = $this->dataStore->set($settingId, $value);

        if (!isset($options)) {
            return $this->api->createAPIError('Unable to update plugin settings');
        }

        $response = $this->api->createAPISuccessResponse(
            array(
                $this->api->createPluginResult($settingId, $value, true, ''),
            )
        );

        return $response;
    }

    /**
     * For PATCH /plugin/:zonedId/settings/:settingId where :settingId is Plugin::SETTING_DEFAULT_SETTINGS.
     * @return mixed
     * @throws \Exception
     */
    public function patchPluginDefaultSettings()
    {
        $this->dataStore->set(Plugin::SETTING_DEFAULT_SETTINGS, true);

        try {
            $this->applyDefaultSettings();
        } catch(\Exception $e) {
            if($e instanceof Exception\CloudFlareException) {
                return $this->api->createAPIError($e->getMessage());
            } else {
                throw $e;
            }
        }


        return $this->api->createAPISuccessResponse(
            array(
                $this->api->createPluginResult(Plugin::SETTING_DEFAULT_SETTINGS, "on", true, ''),
            )
        );
    }

    /**
     * Children should implement this method to apply the plugin specific default settings
     *
     * @return mixed
     */
    public abstract function applyDefaultSettings();
}
