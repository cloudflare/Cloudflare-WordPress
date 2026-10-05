<?php

namespace Cloudflare\APO\Tests\Integration;

use Cloudflare\APO\Tests\Integration\Support\SiteClient;

/**
 * Headers the plugin adds to front-end responses. PHPUnit cannot read response
 * headers of code it runs itself, so these tests request the site over HTTP.
 */
class ResponseHeadersTest extends IntegrationTestCase
{
    public function testLoggedOutVisitorsGetCacheableResponses()
    {
        $headers = $this->frontPageHeaders();

        $this->assertNotEmpty(preg_grep('/^cf-edge-cache: cache,platform=wordpress$/i', $headers));
    }

    public function testLoggedInUsersGetUncachedResponses()
    {
        $headers = $this->frontPageHeaders(array(SiteClient::loginCookies($this->createUser('subscriber'))));

        $this->assertNotEmpty(preg_grep('/^cf-edge-cache: no-cache$/i', $headers));
    }

    public function testHttp2ServerPushSendsPreloadLinksWhenEnabled()
    {
        $headers = $this->frontPageHeaders(array(), '?cloudflare_test_http2_push=1');

        $this->assertNotEmpty(preg_grep('/^Link: <[^>]+>; rel=preload; as=(script|style)$/i', $headers));
    }

    public function testHttp2ServerPushIsOffByDefault()
    {
        $headers = $this->frontPageHeaders();

        $this->assertEmpty(preg_grep('/rel=preload/i', $headers));
    }

    /**
     * Request the front page and return the response header lines.
     *
     * @param string[] $requestHeaders Extra request header lines.
     * @param string   $query          Query string, including the leading "?".
     *
     * @return string[]
     */
    private function frontPageHeaders(array $requestHeaders = array(), $query = '')
    {
        $response = (new SiteClient())->get('/' . $query, $requestHeaders);

        $this->assertSame(200, $response['status']);

        return $response['headers'];
    }
}
