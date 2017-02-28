<?php

namespace CF\API;

use CF\Integration\IntegrationInterface;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;

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
            $client = new Client($this->getEndpoint());

            $request = $this->beforeSend($request);

            $method = $request->getMethod();
            $params = $request->getParameters();

            $mergedResponse = null;

            $currentPage = 1;
            $totalPages = 1;

            while ($totalPages >= $currentPage) {
                $apiRequest = $client->createRequest($method, $request->getUrl(), $request->getHeaders(), $request->getBody(), array());

                // Enable pagination
                if ($method === 'GET') {
                    $params['page'] = $currentPage;
                }

                // Assign parameters
                $query = $apiRequest->getQuery();
                foreach ($params as $key => $value) {
                    $query->set($key, $value);
                }

                // Since Guzzle automatically overwrites a new header when the request
                // is POST / PATCH / DELETE / PUT, we need to overwrite the Content-Type header
                // with setBody() function.
                if ($method !== 'GET') {
                    $apiRequest->setBody(json_encode($request->getBody()), 'application/json');
                }

                $response = $client->send($apiRequest)->json();

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new BadResponseException('Error decoding client API JSON', $response);
                }

                if (!$this->responseOk($response)) {
                    $this->logAPICall($this->getAPIClientName(), array('type' => 'response', 'body' => $response), true);
                }

                if (isset($response['result_info'])) {
                    $totalPages = $response['result_info']['total_pages'];
                    $mergedResponse = $this->mergeResponses($mergedResponse, $response);
                } else {
                    $mergedResponse = $response;
                }

                $currentPage += 1;
            }

            return $mergedResponse;
        } catch (BadResponseException $e) {
            $errorMessage = $this->getErrorMessage($e);

            $this->logAPICall($this->getAPIClientName(), array(
                'type' => 'request',
                'method' => $request->getMethod(),
                'path' => $request->getUrl(),
                'headers' => $request->getHeaders(),
                'params' => $request->getParameters(),
                'body' => $request->getBody(), ), true);
            $this->logAPICall($this->getAPIClientName(), array('type' => 'response', 'reason' => $e->getResponse()->getReasonPhrase(), 'code' => $e->getResponse()->getStatusCode(), 'body' => $errorMessage, 'stacktrace' => $e->getTraceAsString()), true);

            return $this->createAPIError($errorMessage);
        }
    }

    public function mergeResponses($mergedResponse, $response)
    {
        if (!isset($mergedResponse)) {
            $mergedResponse = $response;
        } else {
            $mergedResponse['result'] = array_merge($mergedResponse['result'], $response['result']);

            // Notify the frontend that pagination is taken care.
            $mergedResponse['result_info']['notify'] = 'Backend has taken care of pagination. Ouput is merged in results.';
            $mergedResponse['result_info']['page'] = -1;
            $mergedResponse['result_info']['count'] = -1;
        }

        return $mergedResponse;
    }

    /**
     * @param $error
     *
     * @return string
     */
    public function getErrorMessage($error)
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
     *
     * @return string
     */
    public function getPath(Request $request)
    {
        //substring of everything after the endpoint is the path
        return substr($request->getUrl(), strpos($request->getUrl(), $this->getEndpoint()) + strlen($this->getEndpoint()));
    }

    public function shouldRouteRequest(Request $request)
    {
        return strpos($request->getUrl(), $this->getEndpoint()) !== false;
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
