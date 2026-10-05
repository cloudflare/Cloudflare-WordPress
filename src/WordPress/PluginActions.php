<?php

namespace Cloudflare\APO\WordPress;

use Cloudflare\APO\API\APIInterface;
use Cloudflare\APO\API\AbstractPluginActions;
use Cloudflare\APO\API\Exception\ZoneSettingFailException;
use Cloudflare\APO\API\Plugin;
use Cloudflare\APO\API\Request;
use Cloudflare\APO\Integration\DefaultIntegration;
use Cloudflare\APO\WordPress\Constants\Plans;

class PluginActions extends AbstractPluginActions
{
    protected $api;
    protected $clientAPI;
    protected $composer;
    protected $request;
    protected $userConfig;

    public const CONFIG = [
        "debug" => false,
        "featureManagerIsFullZoneProvisioningEnabled" => false,
        "isDNSPageEnabled" => false,
        "isSubdomainCheckEnabled" => true,
        "useHostAPILogin" => false,
        "homePageCards" => [
            "ApplyDefaultSettingsCard",
            "AutomaticPlatformOptimizationCard",
            "PurgeCacheCard"
        ],
        "moreSettingsCards" => [
            "container.moresettings.speed" => [
                "AlwaysOnlineCard",
                "ImageOptimizationCard",
                "PluginSpecificCacheCard",
                "DevelopmentModeCard"
            ],
            "container.moresettings.security" => [
                "SecurityLevelCard",
                "WAFCard",
                "AdvanceDDoSCard",
                "AutomaticHTTPSRewritesCard"
            ]
        ],
        "locale" => "en",
        "integrationName" => "wordpress"
    ];

    public const BANNED_KEYS = [
        'isDNSPageEnabled',
        'useHostAPILogin',
        'integrationName',
    ];

    public const USER_CONFIG_PATH = '/../../config.json';
    public const COMPOSER_CONFIG_PATH = '/../../composer.json';

    public function __construct(DefaultIntegration $defaultIntegration, APIInterface $api, Request $request)
    {
        parent::__construct($defaultIntegration, $api, $request);
        $this->clientAPI = new WordPressClientAPI($defaultIntegration);
    }

    public function setClientAPI(APIInterface $clientAPI)
    {
        // Inherited from AbstractPluginActions
        $this->clientAPI = $clientAPI;
    }

    /*
     * PATCH /plugin/:id/settings/default_settings
     *
     * Requests are synchronized
     *
     * @throws ZoneSettingFailException When zone details cannot be fetched, or
     *         when one or more individual zone settings fail to update. In the
     *         latter case the exception message lists the failed setting names
     *         and confirms the remaining settings were applied successfully.
     */
    public function applyDefaultSettings()
    {
        $path_array = explode('/', $this->request->getUrl());
        $zoneId = $path_array[1];

        $details = $this->clientAPI->zoneGetDetails($zoneId);

        if (!$this->clientAPI->responseOk($details)) {
            throw new ZoneSettingFailException();
        }

        $currentPlan = $details['result']['plan']['legacy_id'] ?? Plans::FREE_PLAN;

        // Define the recommended settings to apply
        $settings = array(
            array('name' => 'security_level', 'params' => array('value' => 'medium')),
            array('name' => 'cache_level', 'params' => array('value' => 'aggressive')),
            array('name' => 'browser_cache_ttl', 'params' => array('value' => 14400)),
            array('name' => 'always_online', 'params' => array('value' => 'on')),
            array('name' => 'development_mode', 'params' => array('value' => 'off')),
            array('name' => 'ipv6', 'params' => array('value' => 'on')),
            array('name' => 'websockets', 'params' => array('value' => 'on')),
            array('name' => 'ip_geolocation', 'params' => array('value' => 'on')),
            array('name' => 'email_obfuscation', 'params' => array('value' => 'on')),
            array('name' => 'server_side_exclude', 'params' => array('value' => 'on')),
            array('name' => 'hotlink_protection', 'params' => array('value' => 'off')),
            array('name' => 'rocket_loader', 'params' => array('value' => 'off')),
            array('name' => 'automatic_https_rewrites', 'params' => array('value' => 'on')),
        );

        // If the plan supports Mirage and Polish try to set them on
        if (!Plans::planNeedsUpgrade($currentPlan, Plans::BIZ_PLAN)) {
            $settings[] = array('name' => 'mirage', 'params' => array('value' => 'on'));
            $settings[] = array('name' => 'polish', 'params' => array('value' => 'lossless'));
        }

        // Apply all settings, collecting failures rather than failing fast.
        // Some API endpoints may return errors for certain token types (e.g.
        // Account Owned Tokens) even when the token has the correct permissions.
        // Skipping individual failures ensures remaining settings still apply.
        $failedSettings = array();
        foreach ($settings as $setting) {
            $result = $this->clientAPI->changeZoneSettings($zoneId, $setting['name'], $setting['params']);
            if (!$result) {
                $failedSettings[] = $setting['name'];
            }
        }

        if (!empty($failedSettings)) {
            $message = 'Failed to update the following settings: ' . implode(', ', $failedSettings)
                . '. The remaining settings were applied successfully.'
                . ' If you are using an Account Owned Token, some zone settings'
                . ' endpoints may not yet support this token type.';
            throw new ZoneSettingFailException($message);
        }
    }

    public function getConfig()
    {
        $this->getUserConfig();
        $this->getComposerJson();

        //Clone the config to manipulate
        $config = array_merge(array(), self::CONFIG);

        //Add version from composer.json to the config
        $config['version'] = $this->composer['version'];

        //This removes all the banned keys from the userConfig so we don't over write them
        $this->userConfig = array_diff_key($this->userConfig, array_flip(self::BANNED_KEYS));

        //Merge and intersect userConfig with default config and return response
        $response = array_intersect_key($this->userConfig + $config, $config);

        return $this->api->createAPISuccessResponse($response);
    }

    public function getUserConfig()
    {
        if ($this->userConfig === null) {
            if (file_exists(dirname(__FILE__) . self::USER_CONFIG_PATH)) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a file bundled with the plugin, not a remote URL.
                $userConfigContent = file_get_contents(dirname(__FILE__) . self::USER_CONFIG_PATH);
            }

            // Need to set an empty array for merge into config so it doesnt throw a type error
            $this->userConfig = [];
            if (!empty($userConfigContent)) {
                $this->userConfig = json_decode($userConfigContent, true);
            }
        }
    }

    public function setUserConfig($userConfig)
    {
        $this->userConfig = $userConfig;
    }

    public function getComposerJson()
    {
        if ($this->composer === null && file_exists(dirname(__FILE__) . self::COMPOSER_CONFIG_PATH)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a file bundled with the plugin, not a remote URL.
            $this->composer = json_decode(file_get_contents(dirname(__FILE__) . self::COMPOSER_CONFIG_PATH), true);
        }
    }

    public function setComposerJson($composer)
    {
        $this->composer = $composer;
    }
}
