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
        $response = $this->callAPI($request);

        $zone_tag = null;
        if ($this->responseOk($response)) {
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
        $response = $this->callAPI($request);

        return $this->responseOk($response);
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
        $response = $this->callAPI($request);

        return $this->responseOk($response);
    }

    /**
     * @param $urlPattern
     *
     * @return array
     */
    public function createPageRule($zoneId, $urlPattern)
    {
        $body = $this->createPageRuleDisablePerformanceCacheBypassJsonBody($urlPattern);
        $request = new Request('POST', 'zones/'.$zoneId.'/pagerules/', array(), $body);
        $response = $this->callAPI($request);

        return $this->responseOk($response);
    }

    /**
     * @param $urlPattern
     *
     * @return array
     */
    public function createPageRuleDisablePerformanceCacheBypassJsonBody($urlPattern)
    {
        return array(
            'targets' => array(
                array(
                    'target' => 'url',
                    'constraint' => array(
                        'operator' => 'matches',
                        'value' => $urlPattern,
                    ),
                ),
            ),
            'actions' => array(
                array(
                    'id' => 'disable_performance',
                ),
                array(
                    'id' => 'cache_level',
                    'value' => 'bypass',
                ),
            ),
            'status' => 'active',
        );
    }
}
