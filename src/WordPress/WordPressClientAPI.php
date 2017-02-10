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
     * @param $files
     *
     * @return bool
     */
    public function zonePurgeFiles($zoneId, $files)
    {
        $request = new Request('DELETE', 'zones/'.$zoneId.'/purge_cache', array(), array('files' => $files));
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
    public function createPageRule($zoneId, $body)
    {
        $request = new Request('POST', 'zones/'.$zoneId.'/pagerules/', array(), $body);
        $response = $this->callAPI($request);

        return $this->responseOk($response);
    }

    /**
     * @param Request $request
     *
     * @return array|mixed
     */
    public function callAPI(Request $request)
    {
        $request = $this->beforeSend($request);

        $url = add_query_arg($request->getParameters(), $this->getEndpoint().$request->getUrl());

        $requestParams = array(
            'timeout' => 30,
            'method' => $request->getMethod(),
            'headers' => $request->getHeaders(),
        );

        $isPaginatable = false;
        if ($requestParams['method'] === 'GET') {
            $isPaginatable = true;
        }

        $mergedResponse = null;

        $currentPage = 1;
        $totalPages = 1;

        while ($totalPages >= $currentPage) {
            // Enable pagination
            if ($isPaginatable) {
                $params['page'] = $currentPage;
            }

            if ($requestParams['method'] !== 'GET') {
                $requestParams['body'] = json_encode($request->getBody());
                $requestParams['headers']['Content-Type'] = 'application/json';
            }

            $requestResponse = wp_remote_request($url, $requestParams);

            // Check for connection error
            if (is_wp_error($requestResponse)) {
                $errorMessage = $requestResponse->get_error_message();

                $this->logAPICall($this->getAPIClientName(), array_merge(array('type' => 'request', 'path' => $url), $requestParams), true);
                $this->logAPICall($this->getAPIClientName(), array('type' => 'response', 'reason' => $requestResponse->get_error_message(), 'code' => $requestResponse->get_error_code(), 'body' => $errorMessage), true);

                return $this->createAPIError($errorMessage);
            }

            // Check for response error != 2XX
            if (wp_remote_retrieve_response_code($requestResponse) > 299) {
                $errorMessage = wp_remote_retrieve_response_message($requestResponse);

                $this->logAPICall($this->getAPIClientName(), array_merge(array('type' => 'request', 'path' => $url), $requestParams), true);
                $this->logAPICall($this->getAPIClientName(), array('type' => 'response', 'reason' => $errorMessage, 'code' => wp_remote_retrieve_response_code($requestResponse)), true);

                return $this->createAPIError($errorMessage);
            }

            $response = json_decode(wp_remote_retrieve_body($requestResponse), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errorMessage = 'Error decoding client API JSON';
                $this->logAPICall($errorMessage, array('error' => json_last_error()), true);

                return $this->createAPIError($errorMessage);
            }

            if (!$this->responseOk($response)) {
                $this->logAPICall($this->getAPIClientName(), array('type' => 'response', 'body' => $response), true);
            }

            if ($isPaginatable && isset($response['result_info'])) {
                $totalPages = $response['result_info']['total_pages'];

                if (!isset($mergedResponse)) {
                    $mergedResponse = $response;
                } else {
                    $mergedResponse['result'] = array_merge($mergedResponse['result'], $response['result']);

                    // Notify the frontend that pagination is taken care.
                    $mergedResponse['result_info']['notify'] = 'Backend has taken care of pagination. Output is merged in results.';
                    $mergedResponse['result_info']['page'] = -1;
                    $mergedResponse['result_info']['count'] = -1;
                }
            } else {
                $mergedResponse = $response;
            }

            $currentPage += 1;
        }

        return $mergedResponse;
    }
}
