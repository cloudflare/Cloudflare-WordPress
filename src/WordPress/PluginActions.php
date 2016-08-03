<?php

namespace CF\WordPress;

use CF\API\APIInterface;
use CF\API\Request;
use CF\API\Plugin;
use CF\Integration\DefaultIntegration;
use CF\WordPress\Constants\Exceptions\PageRuleLimitException;
use CF\WordPress\Constants\Exceptions\ZoneSettingFailException;
use CF\WordPress\Constants\Plans;

class PluginActions
{
    private $api;
    private $config;
    private $wordpressAPI;
    private $dataStore;
    private $logger;
    private $request;
    private $wordPressClientAPI;

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

        $this->wordPressClientAPI = new WordPressClientAPI($defaultIntegration);
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
        $settings = $this->dataStore->getPluginSettings();

        $formattedSettings = array();
        foreach ($settings as $key => $value) {
            array_push($formattedSettings, $this->api->createPluginResult($key, $value, true, ''));
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
    protected function makeAPICallsForDefaultSettings($zoneId)
    {
        $result = true;
        $details = $this->wordPressClientAPI->zoneGetDetails($zoneId);

        if (!$this->wordPressClientAPI->responseOk($details)) {
            // Technically zoneGetDetails does not try to set Zone Settings
            // Can create a new exception but make things simple right?
            throw new ZoneSettingFailException();
        }

        $currentPlan = $details['result']['plan']['legacy_id'];

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'security_level', array('value' => 'medium'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'cache_level', array('value' => 'aggressive'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'minify', array('value' => array('css' => 'on', 'html' => 'on', 'js' => 'on')));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'browser_cache_ttl', array('value' => 14400));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'always_online', array('value' => 'on'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'development_mode', array('value' => 'off'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'ipv6', array('value' => 'off'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'websockets', array('value' => 'on'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'ip_geolocation', array('value' => 'on'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'email_obfuscation', array('value' => 'on'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'server_side_exclude', array('value' => 'on'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'hotlink_protection', array('value' => 'off'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'rocket_loader', array('value' => 'off'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        // If plan supports  Mirage and Polish try to set them off
        if (!Plans::PlanNeedsUpgrade($currentPlan, Plans::BIZ_PLAN)) {
            $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'mirage', array('value' => 'off'));
            if (!$result) {
                return false;
            }

            $result &= $this->wordPressClientAPI->changeZoneSettings($zoneId, 'polish', array('value' => 'off'));
            if (!$result) {
                throw new ZoneSettingFailException();
            }
        }

        // Set Page Rules
        $loginUrlPattern = wp_login_url();
        $adminUrlPattern = get_admin_url().'*';

        $result &= $this->wordPressClientAPI->createPageRule($zoneId, $loginUrlPattern);
        if (!$result) {
            throw new PageRuleLimitException();
        }

        $result &= $this->wordPressClientAPI->createPageRule($zoneId, $adminUrlPattern);
        if (!$result) {
            throw new PageRuleLimitException();
        }
    }

    /**
     * For PATCH /plugin/:zonedId/settings/:settingId where :settingId is Plugin::SETTING_DEFAULT_SETTINGS.
     *
     * @return mixed
     */
    public function patchPluginDefaultSettings()
    {
        $path_array = explode('/', $this->request->getUrl());
        $zoneId = $path_array[1];
        $settingId = $path_array[3];

        $value = $this->request->getBody()['value'];
        $options = $this->dataStore->setPluginSetting($settingId, true);

        if (!isset($options)) {
            return $this->api->createAPIError('Unable to set default settings');
        }

        try {
            $this->makeAPICallsForDefaultSettings($zoneId);
        } catch (PageRuleLimitException $e) {
            return $this->api->createAPIError($e->getMessage());
        } catch (ZoneSettingFailException $e) {
            return $this->api->createAPIError($e->getMessage());
        }

        $response = $this->api->createAPISuccessResponse(
            array(
                $this->api->createPluginResult($settingId, $value, true, ''),
            )
        );

        return $response;
    }
}
