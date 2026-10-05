<?php

namespace Cloudflare\APO\Tests\Integration;

use Cloudflare\APO\API\Plugin;
use Cloudflare\APO\Tests\Integration\Support\HttpRecorder;
use Cloudflare\APO\Tests\Integration\Support\WpDieException;
use Cloudflare\APO\WordPress\DataStore;
use Cloudflare\APO\WordPress\Hooks;
use Cloudflare\APO\WordPress\WordPressAPI;

/**
 * The settings page talks to Cloudflare through the cloudflare_proxy AJAX
 * action. These tests drive that action the way admin-ajax.php does and check
 * who may use it and what reaches the Cloudflare API.
 */
class ProxyAjaxTest extends IntegrationTestCase
{
    private const SETTING = Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION;

    protected function tearDown(): void
    {
        unset($GLOBALS[Hooks::CLOUDFLARE_JSON], $_SERVER['REQUEST_METHOD']);
        $_GET = array();

        parent::tearDown();
    }

    public function testRequestFromNonAdministratorIsIgnored()
    {
        wp_set_current_user($this->createUser('editor'));

        $response = $this->patchSetting('on', wp_create_nonce(WordPressAPI::API_NONCE));

        $this->assertNull($response, 'The proxy must not answer non-administrators.');
        $this->assertFalse(get_option(self::SETTING));
    }

    public function testRequestWithoutCsrfTokenIsRejected()
    {
        wp_set_current_user($this->createUser('administrator'));

        $response = $this->patchSetting('on', null);

        $this->assertFalse($response['success']);
        $this->assertStringContainsString('CSRF Token not found', $response['errors'][0]['message']);
        $this->assertFalse(get_option(self::SETTING));
    }

    public function testRequestWithInvalidCsrfTokenIsRejected()
    {
        wp_set_current_user($this->createUser('administrator'));

        $response = $this->patchSetting('on', 'not-a-valid-token');

        $this->assertFalse($response['success']);
        $this->assertSame('CSRF Token not valid.', $response['errors'][0]['message']);
        $this->assertFalse(get_option(self::SETTING));
    }

    public function testCsrfTokenOfAnotherUserIsRejected()
    {
        wp_set_current_user($this->createUser('administrator'));
        $otherUsersToken = wp_create_nonce(WordPressAPI::API_NONCE);
        wp_set_current_user($this->createUser('administrator'));

        $response = $this->patchSetting('on', $otherUsersToken);

        $this->assertFalse($response['success']);
        $this->assertFalse(get_option(self::SETTING));
    }

    public function testValidRequestUpdatesThePluginSetting()
    {
        wp_set_current_user($this->createUser('administrator'));

        $response = $this->patchSetting('on', wp_create_nonce(WordPressAPI::API_NONCE));

        $this->assertTrue($response['success']);
        $this->assertSame('on', get_option(self::SETTING)[Plugin::SETTING_VALUE_KEY]);
        $this->assertSame(array(), $this->http->requests(), 'Plugin settings are stored locally.');
    }

    public function testGetRequestIsForwardedWithGlobalApiKeyCredentials()
    {
        wp_set_current_user($this->createUser('administrator'));
        $path = 'zones/' . self::ZONE_ID . '/settings/always_use_https';
        $this->http->respondTo('GET', $path, HttpRecorder::success(array('id' => 'always_use_https', 'value' => 'off')));

        $response = $this->dispatch('GET', array('proxyURLType' => 'CLIENT', 'proxyURL' => $path), null);

        $this->assertTrue($response['success']);
        $this->assertSame('off', $response['result']['value']);

        $requests = $this->http->requestsTo('GET', $path);
        $this->assertCount(1, $requests);
        $this->assertSame(self::EMAIL, $requests[0]['headers']['X-Auth-Email']);
        $this->assertSame(self::GLOBAL_API_KEY, $requests[0]['headers']['X-Auth-Key']);
        $this->assertArrayNotHasKey('Authorization', $requests[0]['headers']);
    }

    public function testGetRequestIsForwardedWithApiTokenAsBearer()
    {
        $apiToken = str_repeat('aB3', 13) . 'x';
        update_option(DataStore::API_KEY, $apiToken);
        wp_set_current_user($this->createUser('administrator'));
        $path = 'zones/' . self::ZONE_ID . '/settings/always_use_https';
        $this->http->respondTo('GET', $path, HttpRecorder::success(array('id' => 'always_use_https', 'value' => 'off')));

        $this->dispatch('GET', array('proxyURLType' => 'CLIENT', 'proxyURL' => $path), null);

        $requests = $this->http->requestsTo('GET', $path);
        $this->assertCount(1, $requests);
        $this->assertSame('Bearer ' . $apiToken, $requests[0]['headers']['Authorization']);
        $this->assertArrayNotHasKey('X-Auth-Key', $requests[0]['headers']);
    }

    /**
     * PATCH the APO plugin setting through the proxy, as the settings page does.
     *
     * @param string      $value
     * @param string|null $csrfToken Omitted from the body when null.
     *
     * @return array|null The decoded proxy response, or null when there was none.
     */
    private function patchSetting($value, $csrfToken)
    {
        $body = array(
            'proxyURL' => Plugin::ENDPOINT . 'plugin/' . self::ZONE_ID . '/settings/' . self::SETTING,
            'value' => $value,
        );
        if ($csrfToken !== null) {
            $body['cfCSRFToken'] = $csrfToken;
        }

        return $this->dispatch('PATCH', array(), $body);
    }

    /**
     * Run the proxy AJAX action for one request.
     *
     * @param string     $method HTTP method.
     * @param array      $query  Query string parameters.
     * @param array|null $body   JSON body.
     *
     * @return array|null The decoded proxy response, or null when the proxy did not answer.
     */
    private function dispatch($method, array $query, $body)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_GET = array('action' => Hooks::WP_AJAX_ACTION) + $query;
        $GLOBALS[Hooks::CLOUDFLARE_JSON] = $body === null ? '' : wp_json_encode($body);

        try {
            do_action('wp_ajax_' . Hooks::WP_AJAX_ACTION);
        } catch (WpDieException $e) {
            return json_decode($e->getMessage(), true);
        }

        return null;
    }
}
