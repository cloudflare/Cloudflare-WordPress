<?php

namespace CF\WordPress;

use CF\Integration\DefaultLogger;
use CF\Integration\DataStoreInterface;
use CF\API\Plugin;

class DataStore implements DataStoreInterface
{
    const API_KEY = 'cloudflare_api_key';
    const EMAIL = 'cloudflare_api_email';
    const CACHED_DOMAIN_NAME = 'cloudflare_cached_domain_name';

    /**
     * @param DefaultLogger $logger
     */
    public function __construct(DefaultLogger $logger)
    {
        $this->logger = $logger;
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
        $this->set(self::API_KEY, '');
        $this->set(self::EMAIL, '');

        // Fill options
        $isUpdated1 = $this->set(self::API_KEY, $client_api_key);
        $isUpdated2 = $this->set(self::EMAIL, $email);

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
        return $this->get(self::API_KEY);
    }

    /**
     * @return mixed
     */
    public function getHostAPIUserKey()
    {
        return;
    }

    /**
     * @return mixed
     */
    public function getDomainNameCache()
    {
        $cachedDomainName = $this->get(self::CACHED_DOMAIN_NAME);
        if (empty($cachedDomainName)) {
            return;
        }

        return $cachedDomainName;
    }

    /**
     * @return mixed
     */
    public function setDomainNameCache($domainName)
    {
        return $this->set(self::CACHED_DOMAIN_NAME, $domainName);
    }

    /**
     * @return cloudflare email
     */
    public function getCloudFlareEmail()
    {
        return $this->get(self::EMAIL);
    }

    /**
     * @param  $settingId Plugin::[PluginSettingName]
     *
     * @return mixed
     */
    public function getPluginSetting($settingId)
    {
        $settingName = $this->getPluginSettingName($settingId);
        if (!$settingName) {
            return false;
        }

        return $this->get($settingName);
    }

    private function getPluginSettingName($settingId)
    {
        return in_array($settingId, Plugin::getPluginSettingsKeys()) ? $settingId : false;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $result = get_option($key, null);

        return $result;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function set($key, $value)
    {
        return update_option($key, $value);
    }

    /**
     * @param $key
     */
    public function clear($key)
    {
        delete_option($key);
    }
}
