<?php

namespace CF\API;

use CF\Integration\DataStoreInterface;

class Plugin extends Client
{
    const PLUGIN_API_NAME = 'PLUGIN API';
    const ENDPOINT = 'https://partners.cloudflare/plugins/';

    //plugin/:id/settings/:human_readable_id setting names
    const SETTING_DEFAULT_SETTINGS = 'default_settings';
    const SETTING_IP_REWRITE = 'ip_rewrite';
    const SETTING_PROTOCOL_REWRITE = 'protocol_rewrite';
    const SETTING_PLUGIN_SPECIFIC_CACHE = 'plugin_specific_cache';
    const SETTING_PLUGIN_SPECIFIC_CACHE_TAG = 'plugin_specific_cache_tag';

    public static function getPluginSettingsKeys()
    {
        return array(
            self::SETTING_DEFAULT_SETTINGS,
            self::SETTING_IP_REWRITE,
            self::SETTING_PROTOCOL_REWRITE,
            self::SETTING_PLUGIN_SPECIFIC_CACHE,
            self::SETTING_PLUGIN_SPECIFIC_CACHE_TAG,
        );
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return self::ENDPOINT;
    }

    /**
     * @return string
     */
    public function getAPIClientName()
    {
        return self::PLUGIN_API_NAME;
    }

    /**
     * @param Request $request
     *
     * @return array|mixed
     */
    public function callAPI(Request $request)
    {
        $this->logger->error('CF\\Wordpress\\API\\Plugin\\callAPI should never be called');

        return $this->createAPIError('The url: '.$request->getUrl().' is not a valid path.');
    }

    public function createAPISuccessResponse($result)
    {
        return array(
            'success' => true,
            'result' => $result,
            'messages' => [],
            'errors' => [],
        );
    }

    public static function createPluginSettingObject($pluginSettingKey, $value, $editable, $modified_on)
    {
        return array(
            DataStoreInterface::ID_KEY => $pluginSettingKey,
            DataStoreInterface::VALUE_KEY => $value,
            DataStoreInterface::EDITABLE_KEY => $editable,
            DataStoreInterface::MODIFIED_DATE_KEY => $modified_on,
        );
    }
}
