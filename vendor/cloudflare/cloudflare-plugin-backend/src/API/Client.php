<?php
namespace CF\API;

use GuzzleHttp;

class Client extends AbstractAPIClient
{
    const CLIENT_API_NAME = "CLIENT API";
    const ENDPOINT = "https://api.cloudflare.com/client/v4/";
    const X_AUTH_KEY = "X-Auth-Key";
    const X_AUTH_EMAIL = "X-Auth-Email";


    /**
     * @param Request $request
     * @return Request
     */
    public function beforeSend(Request $request)
    {
        $headers = array(
            self::X_AUTH_KEY => $this->data_store->getClientV4APIKey(),
            self::X_AUTH_EMAIL => $this->data_store->getCloudFlareEmail(),
            self::CONTENT_TYPE_KEY => self::APPLICATION_JSON_KEY
        );
        $request->setHeaders($headers);

        return $request;
    }

    /**
     * @param $message
     * @return array
     */
    public function createAPIError($message)
    {
        $this->logger->error($message);
        return array(
            'result' => null,
            'success' => false,
            'errors' => array(
                array(
                    'code' => '',
                    'message' => $message,
                )
            ),
            'messages' => array()
        );
    }

    /**
     * @param $response
     * @return bool
     */
    public function responseOk($response)
    {
        return ($response["success"] === true);
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
        return self::CLIENT_API_NAME;
    }

    /**
     * GET /zones/:id
     * @param $zone_tag
     * @return string
     */
    public function zoneGetDetails($zone_tag)
    {
        $request = new Request("GET", "zones/" . $zone_tag, array(), array());
        return $this->callAPI($request);
    }

    /**
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

    public function zonePurgeCache($zone_tag)
    {
        $request = new Request('DELETE', 'zones/'.$zone_tag.'/purge_cache', array(), array('purge_everything' => true));
        $response = self::callAPI($request);

        error_log('PURGE');
        error_log(json_encode($response));

        return self::responseOk($response);
    }
}
