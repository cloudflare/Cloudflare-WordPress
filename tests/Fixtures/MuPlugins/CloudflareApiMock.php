<?php

/**
 * Plugin Name: Cloudflare API mock (test fixture)
 * Description: Test environments only (mapped as mu-plugins by the wp-env test,
 * build and compatibility configs). Answers the Cloudflare API calls the
 * plugin makes during web requests, for example from the settings page in the
 * browser tests, with prepared responses from CloudflareApiMock/, so no test
 * ever reaches the real API. Every call is logged to
 * wp-content/cloudflare-api-mock.log. PHPUnit and WP-CLI run in the CLI and
 * are left alone: the integration tests use HttpRecorder instead.
 */

namespace Cloudflare\APO\Tests\Fixtures;

if (PHP_SAPI === 'cli') {
    return;
}

final class CloudflareApiMock
{
    public const ENDPOINT = 'https://api.cloudflare.com/client/v4/';

    /** The documented Cloudflare API example zone id, as in the PHPUnit tests. */
    public const ZONE_ID = '023e105f4ecef8ad9ca31a8372d0c353';

    /**
     * The only credentials the mock accepts: the documented Cloudflare API
     * example key, shortened to the Global API Key format, and a matching email.
     */
    public const API_KEY = 'c2547eb745079dac9320b638f5e225cf483cc';

    public const EMAIL = 'integration@example.com';

    public const LOG = 'cloudflare-api-mock.log';

    /**
     * Option with response delays, as milliseconds by path suffix, for example
     * {"entitlements": 1500}. Lets a browser test change the order in which
     * the settings application receives its responses.
     */
    public const DELAYS_OPTION = 'cloudflare_api_mock_delays';

    /**
     * pre_http_request filter callback.
     *
     * @param false|array|\WP_Error $preempt
     * @param array                 $args
     * @param string                $url
     *
     * @return false|array|\WP_Error
     */
    public static function intercept($preempt, $args, $url)
    {
        if (strpos($url, self::ENDPOINT) !== 0) {
            return $preempt;
        }

        $parts = wp_parse_url(substr($url, strlen(self::ENDPOINT)));
        $path = trim(isset($parts['path']) ? $parts['path'] : '', '/');
        $query = array();
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $method = strtoupper(isset($args['method']) ? $args['method'] : 'GET');
        $body = isset($args['body']) && is_string($args['body']) ? json_decode($args['body'], true) : null;

        if (!self::authenticated(isset($args['headers']) ? (array) $args['headers'] : array())) {
            self::log($method, $path, $query, $body, true);

            // What Cloudflare answers for an unknown key or email.
            return self::reply(403, self::error('Unknown X-Auth-Key or X-Auth-Email', 9103));
        }

        self::delay($path);
        $response = self::respond($method, $path, is_array($body) ? $body : array());
        self::log($method, $path, $query, $body, $response !== null);

        if ($response === null) {
            return self::reply(404, self::error('No mocked response for ' . $method . ' ' . $path));
        }

        return self::reply(200, $response);
    }

    /**
     * Wait before answering when DELAYS_OPTION has a delay for this path.
     *
     * @param string $path
     */
    private static function delay($path)
    {
        foreach ((array) get_option(self::DELAYS_OPTION, array()) as $suffix => $milliseconds) {
            $suffix = (string) $suffix;
            if ($suffix !== '' && substr($path, -strlen($suffix)) === $suffix) {
                usleep((int) $milliseconds * 1000);
            }
        }
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $body
     *
     * @return array|null The response body, or null when the call is not mocked.
     */
    private static function respond($method, $path, array $body)
    {
        $zone = 'zones/' . self::ZONE_ID;

        if ($method === 'GET' && $path === 'zones') {
            return self::success(array(self::fixture('Zone')));
        }
        if ($method === 'GET' && $path === $zone) {
            return self::success(self::fixture('Zone'));
        }
        if ($method === 'GET' && $path === $zone . '/settings') {
            return self::success(self::fixture('ZoneSettings'));
        }
        if (preg_match('#^' . $zone . '/settings/([a-z0-9_]+)$#', $path, $matches)) {
            $setting = self::zoneSetting($matches[1]);
            if ($method === 'PATCH') {
                $setting = array_merge($setting, array('id' => $matches[1], 'value' => isset($body['value']) ? $body['value'] : null));
            }

            return $setting ? self::success($setting) : null;
        }
        if ($method === 'GET' && $path === $zone . '/pagerules') {
            return self::success(array());
        }
        if ($method === 'GET' && $path === $zone . '/dns_records') {
            return self::success(self::fixture('DnsRecords'));
        }
        if ($method === 'GET' && $path === $zone . '/entitlements') {
            return self::success(self::fixture('Entitlements'));
        }
        if (in_array($method, array('POST', 'DELETE'), true) && $path === $zone . '/purge_cache') {
            return self::success(array('id' => self::ZONE_ID));
        }

        return null;
    }

    /**
     * @param array $headers Request headers.
     *
     * @return bool
     */
    private static function authenticated(array $headers)
    {
        return isset($headers['X-Auth-Key'], $headers['X-Auth-Email'])
            && $headers['X-Auth-Key'] === self::API_KEY
            && $headers['X-Auth-Email'] === self::EMAIL;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    private static function zoneSetting($id)
    {
        foreach (self::fixture('ZoneSettings') as $setting) {
            if ($setting['id'] === $id) {
                return $setting;
            }
        }

        return array();
    }

    /**
     * Load CloudflareApiMock/<name>.json with the site's domain and the zone id
     * filled in.
     *
     * @param string $name
     *
     * @return array
     */
    private static function fixture($name)
    {
        $json = (string) file_get_contents(__DIR__ . '/CloudflareApiMock/' . $name . '.json');
        $json = str_replace(
            array('{{zone_id}}', '{{zone_name}}'),
            array(self::ZONE_ID, (string) wp_parse_url(home_url(), PHP_URL_HOST)),
            $json
        );

        return json_decode($json, true);
    }

    private static function success($result)
    {
        return array('success' => true, 'errors' => array(), 'messages' => array(), 'result' => $result);
    }

    private static function error($message, $code = 0)
    {
        return array('success' => false, 'errors' => array(array('code' => $code, 'message' => $message)), 'messages' => array(), 'result' => null);
    }

    private static function reply($status, array $body)
    {
        return array(
            'headers' => array('content-type' => 'application/json'),
            'body' => wp_json_encode($body),
            'response' => array('code' => $status, 'message' => get_status_header_desc($status)),
            'cookies' => array(),
            'filename' => null,
        );
    }

    private static function log($method, $path, array $query, $body, $mocked)
    {
        file_put_contents(
            WP_CONTENT_DIR . '/' . self::LOG,
            wp_json_encode(array(
                'method' => $method,
                'path' => $path,
                'query' => $query,
                'body' => $body,
                'mocked' => $mocked,
            )) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}

add_filter('pre_http_request', array(CloudflareApiMock::class, 'intercept'), 10, 3);
