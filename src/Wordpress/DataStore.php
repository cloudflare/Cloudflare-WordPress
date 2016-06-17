<?php

namespace CF\WordPress;

use CF\Integration\LoggerInterface;
use CF\Integration\DataStoreInterface;

class DataStore implements DataStoreInterface
{
    const API_KEY = 'cloudflare_api_key';
    const EMAIL = 'cloudflare_api_email';
    const IP_REWRITE = 'cloudflare_ip_rewrite';

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
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
     * @return (bool)
     */
    public function getIpRewrite()
    {
        return get_option(self::IP_REWRITE);
    }

    /**
     * @param $value
     *
     * @return bool
     */
    public function setIpRewrite($value)
    {
        // Clear option with temp value
        update_option(self::IP_REWRITE, 'cleared');

        // Set option
        return update_option(self::IP_REWRITE, $value);
    }
}
