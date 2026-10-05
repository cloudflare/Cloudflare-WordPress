<?php

namespace Cloudflare\APO\Tests\Integration;

use Cloudflare\APO\Tests\Integration\Support\HttpRecorder;

/**
 * Guards the harness itself: no test may reach the network, and Cloudflare
 * API calls only get the responses a test registered.
 */
class NetworkIsolationTest extends IntegrationTestCase
{
    public function testRequestsToOtherHostsAreBlocked()
    {
        $response = wp_remote_get('https://example.com/');

        $this->assertWPError($response);
        $this->assertSame('http_request_blocked', $response->get_error_code());
    }

    public function testUnregisteredCloudflareApiCallsGetNotFound()
    {
        $response = wp_remote_get(HttpRecorder::API_ENDPOINT . 'user/tokens/verify');

        $this->assertSame(404, wp_remote_retrieve_response_code($response));
        $this->assertCount(1, $this->http->requestsTo('GET', 'user/tokens/verify'));
    }

    public function testRegisteredCloudflareApiCallsGetTheirResponse()
    {
        $this->respondWithZone();

        $response = wp_remote_get(HttpRecorder::API_ENDPOINT . 'zones?name=' . $this->siteDomain());
        $body = json_decode(wp_remote_retrieve_body($response), true);

        $this->assertSame(self::ZONE_ID, $body['result'][0]['id']);
        $this->assertSame(array('name' => $this->siteDomain()), $this->http->requests()[0]['query']);
    }

    /**
     * @param mixed $actual
     */
    private function assertWPError($actual)
    {
        $this->assertInstanceOf(\WP_Error::class, $actual);
    }
}
