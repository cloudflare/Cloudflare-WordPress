<?php

namespace Cloudflare\APO\Tests\Integration;

use Cloudflare\APO\Integration\DefaultLogger;

/**
 * The PHP-Scoper build keeps working when another plugin has already loaded an
 * incompatible, unprefixed psr/log. Only meaningful in the build environment,
 * where tests/Fixtures/MuPlugins/ConflictingPsrLog.php is switched on.
 */
class ScopedDependenciesTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        if (!defined('CLOUDFLARE_TEST_CONFLICTING_PSR_LOG')) {
            $this->markTestSkipped('Runs in the build environment only: npm run test:integration:build.');
        }

        parent::setUp();
    }

    public function testAnotherPluginsPsrLogIsLoaded()
    {
        $interfaceFile = (new \ReflectionClass('Psr\Log\LoggerInterface'))->getFileName();

        $this->assertStringEndsWith('ConflictingPsrLog.php', (string) $interfaceFile);
    }

    public function testPluginLoggerUsesThePrefixedPsrLog()
    {
        // class_implements() rather than instanceof: static analysis sees the
        // unprefixed source tree, where the logger does implement Psr\Log.
        $interfaces = class_implements(new DefaultLogger());

        $this->assertContains('Cloudflare\APO\Vendor\Psr\Log\LoggerInterface', $interfaces);
        $this->assertNotContains('Psr\Log\LoggerInterface', $interfaces);
    }
}
