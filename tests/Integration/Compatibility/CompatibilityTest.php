<?php

namespace Cloudflare\APO\Tests\Integration\Compatibility;

use Cloudflare\APO\Tests\Integration\IntegrationTestCase;
use Cloudflare\APO\Tests\Integration\Support\SiteClient;
use Cloudflare\APO\WordPress\Hooks;

/**
 * Checks that only make sense with a third-party plugin active next to
 * Cloudflare. scripts/compatibility-tests.sh activates one plugin from
 * Plugins.json at a time, stores its slug in the
 * cloudflare_test_compatibility_plugin option and runs the integration suite,
 * which includes these tests.
 */
class CompatibilityTest extends IntegrationTestCase
{
    private const MOBILE_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    /**
     * @var SiteClient
     */
    private $site;

    /**
     * @var string
     */
    private $debugLog;

    /**
     * @var int
     */
    private $debugLogSize;

    protected function setUp(): void
    {
        if (!defined('CLOUDFLARE_TEST_COMPATIBILITY') || !get_option('cloudflare_test_compatibility_plugin')) {
            $this->markTestSkipped('Runs in the compatibility environment only: npm run test:compatibility.');
        }

        parent::setUp();

        $this->site = new SiteClient();
        $this->debugLog = WP_CONTENT_DIR . '/debug.log';
        clearstatcache();
        $this->debugLogSize = is_file($this->debugLog) ? (int) filesize($this->debugLog) : 0;
    }

    protected function tearDown(): void
    {
        $cloudflareErrors = $this->site !== null ? $this->cloudflareErrorsLogged() : array();

        parent::tearDown();

        $this->assertEmpty($cloudflareErrors, "PHP errors in the Cloudflare plugin:\n" . implode("\n", $cloudflareErrors));
    }

    public function testPluginUnderTestIsActive()
    {
        $slug = get_option('cloudflare_test_compatibility_plugin');

        $active = array_filter((array) get_option('active_plugins'), function ($plugin) use ($slug) {
            return strpos($plugin, $slug . '/') === 0;
        });

        $this->assertNotEmpty($active, $slug . ' should be active next to Cloudflare.');
        $this->assertTrue(is_plugin_active('cloudflare/cloudflare.php'));
    }

    public function testFrontPageRendersCompletelyAndIsCacheable()
    {
        $response = $this->site->get('/');

        $this->assertFrontPageIsComplete($response);
        $this->assertNotEmpty(preg_grep('/^cf-edge-cache: cache,platform=wordpress$/i', $response['headers']));
    }

    public function testMobileVisitorsGetCacheableResponses()
    {
        $response = $this->site->get('/', array('User-Agent: ' . self::MOBILE_USER_AGENT));

        $this->assertFrontPageIsComplete($response);
        $this->assertNotEmpty(preg_grep('/^cf-edge-cache: cache,platform=wordpress$/i', $response['headers']));
    }

    public function testSettingsPageLoadsForAdministrators()
    {
        $cookies = SiteClient::loginCookies($this->createUser('administrator'));

        $response = $this->site->get('/wp-admin/options-general.php?page=cloudflare', array($cookies));

        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString('id="root"', $response['body']);
        $this->assertStringContainsString('window.RestProxyCallback', $response['body']);
    }

    public function testSettingsProxyAnswersThroughAdminAjax()
    {
        $cookies = SiteClient::loginCookies($this->createUser('administrator'));
        $query = http_build_query(array(
            'action' => Hooks::WP_AJAX_ACTION,
            'proxyURLType' => 'PLUGIN',
            'proxyURL' => 'plugin/' . self::ZONE_ID . '/settings',
        ));

        $response = $this->site->get('/wp-admin/admin-ajax.php?' . $query, array($cookies));
        $json = json_decode($response['body'], true);

        $this->assertSame(200, $response['status']);
        $this->assertIsArray($json, 'The proxy answered with something other than JSON: ' . substr($response['body'], 0, 200));
        $this->assertTrue($json['success']);
    }

    /**
     * A fatal error after the page started rendering still answers 200, so
     * also check that the page was rendered to the end.
     *
     * @param array{status: int, headers: string[], body: string} $response
     */
    private function assertFrontPageIsComplete(array $response)
    {
        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString('</html>', $response['body'], 'The front page stopped rendering after ' . strlen($response['body']) . ' bytes.');
    }

    /**
     * PHP errors from the Cloudflare plugin written to debug.log during the test.
     *
     * @return string[]
     */
    private function cloudflareErrorsLogged()
    {
        clearstatcache();
        if (!is_file($this->debugLog) || filesize($this->debugLog) <= $this->debugLogSize) {
            return array();
        }

        $newLines = (string) file_get_contents($this->debugLog, false, null, $this->debugLogSize);

        return array_values(preg_grep('#/plugins/cloudflare/#', explode("\n", $newLines)));
    }
}
