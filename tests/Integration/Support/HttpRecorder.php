<?php

namespace Cloudflare\APO\Tests\Integration\Support;

/**
 * Intercepts outgoing HTTP requests made through the WordPress HTTP API.
 *
 * Requests to the Cloudflare API are recorded and answered from the responses
 * registered with respondTo(); anything without a registered response gets a
 * 404 in the Cloudflare API error format. Requests to any other host are
 * blocked, so the integration suite never talks to the network.
 */
final class HttpRecorder
{
    public const API_ENDPOINT = 'https://api.cloudflare.com/client/v4/';

    /**
     * @var array<int, array{method: string, path: string, query: array, headers: array, body: mixed}>
     */
    private $requests = array();

    /**
     * @var array<string, array{0: int, 1: array}>
     */
    private $responses = array();

    public function start()
    {
        add_filter('pre_http_request', array($this, 'intercept'), 10, 3);
    }

    public function stop()
    {
        remove_filter('pre_http_request', array($this, 'intercept'), 10);
    }

    /**
     * Register the response for a Cloudflare API call.
     *
     * @param string $method HTTP method.
     * @param string $path   Path after the API endpoint, without query string, e.g. "zones".
     * @param array  $body   Decoded response body.
     * @param int    $status HTTP status code.
     */
    public function respondTo($method, $path, array $body, $status = 200)
    {
        $this->responses[$this->key($method, $path)] = array($status, $body);
    }

    /**
     * pre_http_request filter callback.
     *
     * @param false|array|\WP_Error $preempt Response to short-circuit with.
     * @param array                 $args    Request arguments.
     * @param string                $url     Request URL.
     *
     * @return array|\WP_Error
     */
    public function intercept($preempt, $args, $url)
    {
        if (strpos($url, self::API_ENDPOINT) !== 0) {
            return new \WP_Error('http_request_blocked', 'Blocked by the integration suite: ' . $url);
        }

        $parts = wp_parse_url(substr($url, strlen(self::API_ENDPOINT)));
        $path = trim(isset($parts['path']) ? $parts['path'] : '', '/');
        $query = array();
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $method = strtoupper(isset($args['method']) ? $args['method'] : 'GET');

        $this->requests[] = array(
            'method' => $method,
            'path' => $path,
            'query' => $query,
            'headers' => isset($args['headers']) ? $args['headers'] : array(),
            'body' => isset($args['body']) && is_string($args['body']) ? json_decode($args['body'], true) : null,
        );

        $key = $this->key($method, $path);
        list($status, $body) = isset($this->responses[$key])
            ? $this->responses[$key]
            : array(404, self::error('No integration test response registered for ' . $key));

        return array(
            'headers' => array(),
            'body' => wp_json_encode($body),
            'response' => array(
                'code' => $status,
                'message' => get_status_header_desc($status),
            ),
            'cookies' => array(),
            'filename' => null,
        );
    }

    /**
     * Forget the requests recorded so far, for example the ones made while
     * setting up fixtures. Registered responses are kept.
     */
    public function clearRequests()
    {
        $this->requests = array();
    }

    /**
     * @return array<int, array{method: string, path: string, query: array, headers: array, body: mixed}>
     */
    public function requests()
    {
        return $this->requests;
    }

    /**
     * Recorded requests for one method and path.
     *
     * @param string $method HTTP method.
     * @param string $path   Path after the API endpoint, without query string.
     *
     * @return array<int, array{method: string, path: string, query: array, headers: array, body: mixed}>
     */
    public function requestsTo($method, $path)
    {
        $key = $this->key($method, $path);

        return array_values(array_filter($this->requests, function ($request) use ($key) {
            return $this->key($request['method'], $request['path']) === $key;
        }));
    }

    /**
     * A successful Cloudflare API response body.
     *
     * @param mixed $result
     *
     * @return array
     */
    public static function success($result)
    {
        return array(
            'success' => true,
            'errors' => array(),
            'messages' => array(),
            'result' => $result,
        );
    }

    /**
     * A failed Cloudflare API response body.
     *
     * @param string $message
     *
     * @return array
     */
    public static function error($message)
    {
        return array(
            'success' => false,
            'errors' => array(array('code' => 0, 'message' => $message)),
            'messages' => array(),
            'result' => null,
        );
    }

    private function key($method, $path)
    {
        return strtoupper($method) . ' ' . trim($path, '/');
    }
}
