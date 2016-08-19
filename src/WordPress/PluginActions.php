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
    private $defaultIntegration;
    private $wordPressClientAPI;

    public function __construct(DefaultIntegration $defaultIntegration, APIInterface $api, Request $request)
    {
        parent::__construct($defaultIntegration, $api, $request);
        $this->defaultIntegration = $defaultIntegration;
    }

    public function createWordPressClientAPI(DefaultIntegration $defaultIntegration)
    {
        return new WordPressClientAPI($defaultIntegration);
    }

    /*
     * PATCH /plugin/:id/settings/default_settings               
     *
     * Requests are syncronized
     */
    public function applyDefaultSettings()
    {
        $this->wordPressClientAPI = $this->createWordPressClientAPI($this->defaultIntegration);

        $path_array = explode('/', $this->request->getUrl());
        $zoneId = $path_array[1];

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
                throw new ZoneSettingFailException();
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
}
