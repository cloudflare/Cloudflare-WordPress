<?php

namespace CF\WordPress;

use CF\Integration\DefaultLogger;
use CF\Integration\DataStoreInterface;
use CF\API\Plugin;

class DataStore implements DataStoreInterface
{
    const API_KEY = 'cloudflare_api_key';
    const EMAIL = 'cloudflare_api_email';

    private $pluginSettings = array();

    /**
     * @param DefaultLogger $logger
     */
    public function __construct(DefaultLogger $logger)
    {
        $this->logger = $logger;

		$this->pluginSettings[Plugin::SETTING_IP_REWRITE] = get_option(Plugin::SETTING_IP_REWRITE);
		$this->pluginSettings[Plugin::SETTING_PROTOCOL_REWRITE] = get_option(Plugin::SETTING_PROTOCOL_REWRITE);
		$this->pluginSettings[Plugin::SETTING_DEFAULT_SETTINGS] = get_option(Plugin::SETTING_DEFAULT_SETTINGS);
    }

    /**
     * @param $client_api_key
     * @param $email
     * @param $unique_id
     * @param $user_key
     *
     * @return bool
     */
    public function createUserDataStore($client_api_key, $email, $unique_id, $user_key)
    {
        // Clear options
        update_option(self::API_KEY, '');
        update_option(self::EMAIL, '');

        // Fill options
        $isUpdated1 = update_option(self::API_KEY, $client_api_key);
        $isUpdated2 = update_option(self::EMAIL, $email);

        return $isUpdated1 && $isUpdated2;
    }

    /**
     * @return unique id for the current user for use in the host api
     */
    public function getHostAPIUserUniqueId()
    {
        return;
    }

    /**
     * @return client v4 api key for current user
     */
    public function getClientV4APIKey()
    {
        return get_option(self::API_KEY);
    }

    /**
     * @return mixed
     */
    public function getHostAPIUserKey()
    {
        return;
    }

    /**
     * @return cloudflare email
     */
    public function getCloudFlareEmail()
    {
        return get_option(self::EMAIL);
    }

	/**
	 * @param  $settingId DataStore::[PluginSettingName]
	 * @return bool (bool)
	 */
    public static function getPluginSetting($settingId)
    {
        $settingName = self::getPluginSettingName($settingId);
        if (!$settingName) {
            return false;
        }

        return get_option($settingName);
    }

	/**
	 * @return array (bool)
	 */
    public function getPluginSettings()
    {
        return $this->pluginSettings;
    }

    /**
     * @param $value
     *
     * @return bool
     */
    public function setPluginSetting($settingId, $value)
    {
        $settingName = self::getPluginSettingName($settingId);
        if (!$settingName) {
            return false;
        }

        return update_option($settingName, $value);
    }

    private function getPluginSettingName($settingId)
    {
        return array_key_exists($settingId, $this->pluginSettings) ? $settingId : false;
    }
}
