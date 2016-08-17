<?php

namespace CF\API;

use CF\Integration\IntegrationInterface;
use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;

abstract class AbstractAPIClient implements APIInterface
{
    const CONTENT_TYPE_KEY = 'Content-Type';
    const APPLICATION_JSON_KEY = 'application/json';

    protected $config;
    protected $data_store;
    protected $logger;
    protected $integrationAPI;

    /**
     * @param IntegrationInterface $integration
     */
    public function __construct(IntegrationInterface $integration)
    {
        $this->config = $integration->getConfig();
        $this->data_store = $integration->getDataStore();
        $this->logger = $integration->getLogger();
        $this->integrationAPI = $integration->getIntegrationAPI();
    }

    /**
     * @param Request $request
     *
     * @return array|mixed
     */
    public function callAPI(Request $request)
    {
        try {
            $client = new GuzzleHttp\Client(['base_url' => $this->getEndpoint()]);

            $request = $this->beforeSend($request);

            $bodyType = (($request->getHeaders()[self::CONTENT_TYPE_KEY] === self::APPLICATION_JSON_KEY) ? 'json' : 'body');

            $requestOptions = array(
                'headers' => $request->getHeaders(),
                'query' => $request->getParameters(),
                $bodyType => $request->getBody(),
            );

            if ($this->config->getValue('debug')) {
                $requestOptions['debug'] = fopen('php://stderr', 'w');
            }

            $apiRequest = $client->createRequest($request->getMethod(), $request->getUrl(), $requestOptions);

            $response = $client->send($apiRequest)->json();

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RequestException('Error decoding client API JSON', $response);
            }

            if (!$this->responseOk($response)) {
                $this->logAPICall($this->getAPIClientName(), array('type' => 'response', 'body' => $response), true);
            }

            return $response;
        } catch (RequestException $e) {
            $errorMessage = $this->getErrorMessage($e);

            $this->logAPICall($this->getAPIClientName(), array(
                'type' => 'request',
                'method' => $request->getMethod(),
                'path' => $request->getUrl(),
                'headers' => $request->getHeaders(),
                'params' => $request->getParameters(),
                'body' => $request->getBody(), ), true);
            $this->logAPICall($this->getAPIClientName(), array('type' => 'response', 'code' => $e->getCode(), 'body' => $errorMessage, 'stacktrace' => $e->getTraceAsString()), true);

            return $this->createAPIError($errorMessage);
        }
    }

    /**
     * @param RequestException $object
     *
     * @return string
     */
    public function getErrorMessage(RequestException $error)
    {
        return $error->getMessage();
    }

    /**
     * @param string $apiName
     * @param array  $message
     * @param bool   $isError
     */
    public function logAPICall($apiName, $message, $isError)
    {
        $logLevel = 'error';
        if ($isError === false) {
            $logLevel = 'debug';
        }

        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $this->logger->$logLevel('['.$apiName.'] '.$message);
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getPath(Request $request)
    {
        //substring of everything after the endpoint is the path
        return substr($request->getUrl(), strpos($request->getUrl(), $this->getEndpoint()) + strlen($this->getEndpoint()));
    }

    function shouldRouteRequest(Request $request) {
        return (strpos($request->getUrl(), $this->getEndpoint()) !== false);
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    abstract public function beforeSend(Request $request);

    /**
     * @return mixed
     */
    abstract public function getAPIClientName();

    /**
     * @param $message
     *
     * @return array
     */
    abstract public function createAPIError($message);

    /**
     * @return mixed
     */
    abstract public function getEndpoint();
}
