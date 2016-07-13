<?php

namespace CF\WordPress;

use CF\API\Client;
use CF\API\Request;

class WordPressClientAPI extends Client
{
    /**
     * @param $zone_name
     *
     * @return mixed
     */
    public function getZoneTag($zone_name)
    {
        $request = new Request('GET', 'zones/', array('name' => $zone_name), array());
        $response = self::callAPI($request);

        $zone_tag = null;
        if (self::responseOk($response)) {
            foreach ($response['result'] as $zone) {
                if ($zone['name'] === $zone_name) {
                    $zone_tag = $zone['id'];
                    break;
                }
            }
        }

        return $zone_tag;
    }

    /**
     * @param $zoneId
     *
     * @return bool
     */
    public function zonePurgeCache($zoneId)
    {
        $request = new Request('DELETE', 'zones/'.$zoneId.'/purge_cache', array(), array('purge_everything' => true));
        $response = self::callAPI($request);

        return self::responseOk($response);
    }

    /**
     * @param $zoneId
     * @param $settingName
     * @param $params
     *
     * @return bool
     */
    public function changeZoneSettings($zoneId, $settingName, $params)
    {
        $request = new Request('PATCH', 'zones/'.$zoneId.'/settings/'.$settingName, array(), $params);
        $response = self::callAPI($request);

        return self::responseOk($response);
    }
     * @return bool
     */
    public function zonePurgeCache($zone_tag)
    {
        $request = new Request('DELETE', 'zones/'.$zone_tag.'/purge_cache', array(), array('purge_everything' => true));
        $response = self::callAPI($request);

        return self::responseOk($response);
    }
}
