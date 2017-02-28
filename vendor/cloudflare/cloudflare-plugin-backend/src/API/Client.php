<?php

namespace CF\API;

use Guzzle\Http\Exception\BadResponseException;

class Client extends AbstractAPIClient
{
    const CLIENT_API_NAME = 'CLIENT API';
    const ENDPOINT = 'https://api.cloudflare.com/client/v4/';
    const X_AUTH_KEY = 'X-Auth-Key';
    const X_AUTH_EMAIL = 'X-Auth-Email';

    /**
     * @param Request $request
     *
     * @return Request
     */
    public function beforeSend(Request $request)
    {
        $headers = array(
            self::X_AUTH_KEY => $this->data_store->getClientV4APIKey(),
            self::X_AUTH_EMAIL => $this->data_store->getCloudFlareEmail(),
            self::CONTENT_TYPE_KEY => self::APPLICATION_JSON_KEY,
        );
        $request->setHeaders($headers);

        // Remove cfCSRFToken (a custom header) to save bandwidth
        $body = $request->getBody();
        $body['cfCSRFToken'] = null;
        $request->setBody($body);

        return $request;
    }

    /**
     * @param $message
     *
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
                ),
            ),
            'messages' => array(),
        );
    }

    /**
     * @param error
     *
     * @return string
     */
    public function getErrorMessage($error)
    {
        $jsonResponse = json_decode($error->getResponse()->getBody(), true);
        $errorMessage = $error->getMessage();

        if (count($jsonResponse['errors']) > 0) {
            $errorMessage = $jsonResponse['errors'][0]['message'];
        }

        return $errorMessage;
    }

    /**
     * @param $response
     *
     * @return bool
     */
    public function responseOk($response)
    {
        return $response['success'] === true;
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
     * GET /zones/:id.
     *
     * @param $zone_tag
     *
     * @return string
     */
    public function zoneGetDetails($zone_tag)
    {
        $request = new Request('GET', 'zones/'.$zone_tag, array(), array());

        return $this->callAPI($request);
    }
}
