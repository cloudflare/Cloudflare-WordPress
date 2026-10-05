<?php

namespace Cloudflare\APO\Tests\Integration\Support;

/**
 * Requests the WordPress site under test over HTTP.
 *
 * PHPUnit runs in the wp-env "cli" container, the site is served by the
 * "wordpress" container of the same environment. Requests carry the site's
 * Host header, so WordPress answers as it would for a visitor.
 */
final class SiteClient
{
    /**
     * @param string   $path    Path and query string, starting with "/".
     * @param string[] $headers Extra request header lines.
     *
     * @return array{status: int, headers: string[], body: string}
     */
    public function get($path, array $headers = array())
    {
        $home = wp_parse_url(home_url());
        $host = $home['host'] . (isset($home['port']) ? ':' . $home['port'] : '');
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => implode("\r\n", array_merge(array('Host: ' . $host), $headers)),
                'ignore_errors' => true,
                'follow_location' => 0,
                'timeout' => 30,
            ),
        ));

        $body = file_get_contents('http://wordpress' . $path, false, $context);
        if ($body === false || !isset($http_response_header[0])) {
            throw new \RuntimeException('The wp-env wordpress container did not answer ' . $path . '.');
        }
        preg_match('#^HTTP/\S+ (\d{3})#', $http_response_header[0], $status);

        return array(
            'status' => (int) $status[1],
            'headers' => $http_response_header,
            'body' => $body,
        );
    }

    /**
     * Cookie header that logs a user in, for the front end and wp-admin.
     *
     * @param int $userId
     *
     * @return string
     */
    public static function loginCookies($userId)
    {
        $expiration = time() + HOUR_IN_SECONDS;

        return 'Cookie: '
            . LOGGED_IN_COOKIE . '=' . rawurlencode(wp_generate_auth_cookie($userId, $expiration, 'logged_in')) . '; '
            . AUTH_COOKIE . '=' . rawurlencode(wp_generate_auth_cookie($userId, $expiration, 'auth'));
    }
}
