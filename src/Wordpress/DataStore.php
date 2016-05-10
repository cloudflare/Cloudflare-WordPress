<?php
namespace CF\Wordpress;

use CF\Integration\LoggerInterface;
use Symfony\Component\Yaml\Yaml as Yaml;
use CF\Integration\DataStoreInterface;

class DataStore implements DataStoreInterface
{
    const API_KEY = "cloudflare_api_key";
    const EMAIL = "cloudflare_api_email";

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
     * @return bool
     */
    public function createUserDataStore($client_api_key, $email, $unique_id, $user_key)
    {
        update_option(self::API_KEY, $client_api_key);
        update_option(self::EMAIL, $email);
    }

    /**
     * @return unique id for the current user for use in the host api
     */
    public function getHostAPIUserUniqueId()
    {
        return null;
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
        return null;
    }

    /**
     * @return cloudflare email
     */
    public function getCloudFlareEmail()
    {
        return get_option(self::EMAIL);
    }
}
