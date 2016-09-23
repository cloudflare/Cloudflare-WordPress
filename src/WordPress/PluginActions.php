<?php

namespace CF\WordPress;

use CF\API\APIInterface;
use CF\API\Request;
use CF\API\Plugin;
use CF\Integration\DefaultIntegration;
use CF\API\Exception\PageRuleLimitException;
use CF\API\Exception\ZoneSettingFailException;
use CF\WordPress\Constants\Plans;
use CF\API\AbstractPluginActions;

class PluginActions extends AbstractPluginActions
{
    protected $api;
    protected $clientAPI;
    protected $request;

    public function __construct(DefaultIntegration $defaultIntegration, APIInterface $api, Request $request)
    {
        parent::__construct($defaultIntegration, $api, $request);
        $this->clientAPI = new WordPressClientAPI($defaultIntegration);
    }

    public function setClientAPI(APIInterface $clientAPI)
    {
        // Inheirited from AbstractPluginActions
        $this->clientAPI = $clientAPI;
    }

    /*
     * PATCH /plugin/:id/settings/default_settings
     *
     * Requests are synchronized
     */
    public function applyDefaultSettings()
    {
        $path_array = explode('/', $this->request->getUrl());
        $zoneId = $path_array[1];

        $result = true;
        $details = $this->clientAPI->zoneGetDetails($zoneId);

        if (!$this->clientAPI->responseOk($details)) {
            // Technically zoneGetDetails does not try to set Zone Settings
            // Can create a new exception but make things simple right?
            throw new ZoneSettingFailException();
        }

        $currentPlan = $details['result']['plan']['legacy_id'];

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'security_level', array('value' => 'medium'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'cache_level', array('value' => 'aggressive'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'minify', array('value' => array('css' => 'on', 'html' => 'on', 'js' => 'on')));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'browser_cache_ttl', array('value' => 14400));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'always_online', array('value' => 'on'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'development_mode', array('value' => 'off'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'ipv6', array('value' => 'off'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'websockets', array('value' => 'on'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'ip_geolocation', array('value' => 'on'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'email_obfuscation', array('value' => 'on'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'server_side_exclude', array('value' => 'on'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'hotlink_protection', array('value' => 'off'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'rocket_loader', array('value' => 'off'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        $result &= $this->clientAPI->changeZoneSettings($zoneId, 'automatic_https_rewrites', array('value' => 'on'));
        if (!$result) {
            throw new ZoneSettingFailException();
        }

        // If plan supports  Mirage and Polish try to set them on
        if (!Plans::PlanNeedsUpgrade($currentPlan, Plans::BIZ_PLAN)) {
            $result &= $this->clientAPI->changeZoneSettings($zoneId, 'mirage', array('value' => 'on'));
            if (!$result) {
                throw new ZoneSettingFailException();
            }

            $result &= $this->clientAPI->changeZoneSettings($zoneId, 'polish', array('value' => 'lossless'));
            if (!$result) {
                throw new ZoneSettingFailException();
            }
        }

        // Set Page Rules
        $adminUrlPattern = get_admin_url().'*';

        $result &= $this->clientAPI->createPageRule($zoneId, $adminUrlPattern);
        if (!$result) {
            throw new PageRuleLimitException();
        }
    }
}
