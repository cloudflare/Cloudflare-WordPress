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
    private $integration;
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
        $this->integration = $defaultIntegration;
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
        $settings = $this->dataStore->getPluginSettings($this->api);

        $response = $this->api->createAPISuccessResponse(
            $settings
        );

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

        $response = null;
        if ($settingId === DataStore::DEFAULT_SETTINGS) {
            $response = $this->patchPluginDefaultSettings();
        } else {
            $response = $this->patchPluginSettings2();
        }

        return $response;
    }

    /**
     * For PATCH /plugin/:zonedId/settings/:settingId where :settingId is not predefined.
     *
     * @return mixed
     */
    private function patchPluginSettings2()
    {
        $path_array = explode('/', $this->request->getUrl());
        $settingId = $path_array[3];

        $value = $this->request->getBody()['value'];
        $options = $this->dataStore->setPluginSetting($settingId, $value);

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
     * Every API call is synchronized.
     *
     * @param zoneId
     *
     * @return bool Check every setting and return true or false.
     */
    private function makeAPICallsForDefaultSettings($zonedId)
    {
        $wordPressClientAPI = new WordPressClientAPI($this->integration);

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'security_level', array('value' => 'medium'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'cache_level', array('value' => 'basic'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'ssl', array('value' => 'flexible'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'minify', array('value' => array('css' => 'on', 'html' => 'on', 'js' => 'on')));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'browser_cache_ttl', array('value' => 14400));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'always_online', array('value' => 'off'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'development_mode', array('value' => 'off'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'development_mode', array('value' => 'off'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'ipv6', array('value' => 'off'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'websockets', array('value' => 'on'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'ip_geolocation', array('value' => 'on'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'email_obfuscation', array('value' => 'on'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'server_side_exclude', array('value' => 'on'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'hotlink_protection', array('value' => 'off'));
        if (!$result) {
            return false;
        }

        $result = $wordPressClientAPI->changeZoneSettings($zonedId, 'rocket_loader', array('value' => 'off'));
        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     * For PATCH /plugin/:zonedId/settings/:settingId where :settingId is DataStore:DEFAULT_SETTINGS.
     *
     * @return mixed
     */
    private function patchPluginDefaultSettings()
    {
        $path_array = explode('/', $this->request->getUrl());
        $zoneId = $path_array[1];
        $settingId = $path_array[3];

        $value = $this->request->getBody()['value'];
        $options = $this->dataStore->setPluginSetting($settingId, true);

        if (!isset($options)) {
            return $this->api->createAPIError('Unable to set default settings');
        }

        $result = $this->makeAPICallsForDefaultSettings($zonedId);
        if (!$result) {
            $this->api->createAPIError('Unable to set default settings');
        }

        $response = $this->api->createAPISuccessResponse(
            array(
                $this->api->createPluginResult($settingId, $value, true, ''),
            )
        );

        return $response;
    }
}
