<?php

namespace CF\API;

use CF\API\Request;
use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;

class DefaultHttpClient implements HttpClientInterface
{
    const CONTENT_TYPE_KEY = 'Content-Type';
    const APPLICATION_JSON_KEY = 'application/json';

    protected $client;

    /**
     * @param String $endpoint
     */
    public function __construct($endpoint)
    {
        $this->client = new GuzzleHttp\Client(['base_url' => $endpoint]);
    }

    /**
     * @param  Request $request
     * @throws RequestException
     * @return Array $response
     */
    public function send(Request $request)
    {
        $apiRequest = $this->createGuzzleRequest($request);

        $response = $this->client->send($apiRequest)->json();

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RequestException('Error decoding client API JSON', $response);
        }

        return $response;
    }

    /**
     * @param  Request $request
     * @return GuzzleHttp\Message\RequestInterface $request
     */
    public function createGuzzleRequest(Request $request)
    {
        $bodyType = 'body';
        if (isset($request->getHeaders()[self::CONTENT_TYPE_KEY]) && $request->getHeaders()[self::CONTENT_TYPE_KEY] === self::APPLICATION_JSON_KEY) {
            $bodyType = 'json';
        }

        $requestOptions = array(
            'headers' => $request->getHeaders(),
            'query' => $request->getParameters(),
            $bodyType => $request->getBody(),
        );

        return $this->client->createRequest($request->getMethod(), $request->getUrl(), $requestOptions);
    }

    /**
     * @param GuzzleHttpClient $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }
}
