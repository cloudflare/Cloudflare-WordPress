<?php

namespace Cloudflare\APO\Tests\Unit\WordPress;

use Cloudflare\APO\Integration\DefaultIntegration;
use Cloudflare\APO\WordPress\Constants\Plans;
use Cloudflare\APO\WordPress\PluginActions;
use phpmock\phpunit\PHPMock;

class PluginActionsTest extends \PHPUnit\Framework\TestCase
{
    use PHPMock;

    private $mockConfig;
    private $mockDataStore;
    private $mockDefaultIntegration;
    private $mockLogger;
    private $mockPluginAPIClient;
    private $mockWordPressAPI;
    private $mockWordPressClientAPI;
    private $mockRequest;
    private $pluginActions;

    public function setUp(): void
    {
        $this->mockConfig = $this->getMockBuilder('Cloudflare\APO\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('Cloudflare\APO\WordPress\DataStore')
            ->disableOriginalConstructor()
            ->getMock();
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_admin_url');
        $this->mockLogger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPluginAPIClient = $this->getMockBuilder('Cloudflare\APO\API\Plugin')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockWordPressAPI = $this->getMockBuilder('Cloudflare\APO\WordPress\WordPressAPI')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockWordPressClientAPI = $this->getMockBuilder('Cloudflare\APO\WordPress\WordPressClientAPI')
            ->disableOriginalConstructor()
            ->getMock();
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_login_url');
        $this->mockRequest = $this->getMockBuilder('Cloudflare\APO\API\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockDefaultIntegration = new DefaultIntegration($this->mockConfig, $this->mockWordPressAPI, $this->mockDataStore, $this->mockLogger);
        $this->pluginActions = new PluginActions($this->mockDefaultIntegration, $this->mockPluginAPIClient, $this->mockRequest);
        $this->pluginActions->setClientAPI($this->mockWordPressClientAPI);
    }

    public function testReturnApplyDefaultSettingsWithZoneWithPlanBIZ()
    {
        $this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

        $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(
            array(
                'result' => array(
                    'plan' => array(
                        'legacy_id' => Plans::BIZ_PLAN,
                    ),
                ),
            )
        );
        $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
        $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturn(true);
        $this->mockWordPressClientAPI->expects($this->exactly(15))->method('changeZoneSettings');

        $this->pluginActions->applyDefaultSettings();
    }

    public function testReturnApplyDefaultSettingsWithZoneWithFreePlan()
    {
        $this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

        $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(
            array(
                'result' => array(
                    'plan' => array(
                        'legacy_id' => Plans::FREE_PLAN,
                    ),
                ),
            )
        );
        $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
        $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturn(true);
        $this->mockWordPressClientAPI->expects($this->exactly(13))->method('changeZoneSettings');

        $this->pluginActions->applyDefaultSettings();
    }

    public function testReturnApplyDefaultSettingsZoneDetailsThrowsZoneSettingFailException()
    {
        $this->expectException('\Cloudflare\APO\API\Exception\ZoneSettingFailException');

        $this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

        $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(false);
        // Explicitly drive the early-throw branch via responseOk() returning false,
        // since the production code branches on responseOk(), not on the raw value.
        $this->mockWordPressClientAPI->method('responseOk')->willReturn(false);
        // changeZoneSettings must never be reached when zone details fail.
        $this->mockWordPressClientAPI->expects($this->never())->method('changeZoneSettings');

        $this->pluginActions->applyDefaultSettings();
    }

    public function testReturnApplyDefaultSettingsChangeZoneSettingsThrowsWithFailedSettingNames()
    {
        $this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

        $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(
            array(
                'result' => array(
                    'plan' => array(
                        'legacy_id' => Plans::FREE_PLAN,
                    ),
                ),
            )
        );
        $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
        // All settings fail
        $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturn(false);
        // All 13 settings (Free plan) should still be attempted despite failures
        $this->mockWordPressClientAPI->expects($this->exactly(13))->method('changeZoneSettings');

        try {
            $this->pluginActions->applyDefaultSettings();
            $this->fail('Expected ZoneSettingFailException was not thrown.');
        } catch (\Cloudflare\APO\API\Exception\ZoneSettingFailException $e) {
            $message = $e->getMessage();
            $this->assertMatchesRegularExpression('/Failed to update the following settings/', $message);
            // Every Free-plan setting name should appear in the failure list.
            $expectedSettings = array(
                'security_level',
                'cache_level',
                'browser_cache_ttl',
                'always_online',
                'development_mode',
                'ipv6',
                'websockets',
                'ip_geolocation',
                'email_obfuscation',
                'server_side_exclude',
                'hotlink_protection',
                'rocket_loader',
                'automatic_https_rewrites',
            );
            foreach ($expectedSettings as $name) {
                $this->assertStringContainsString($name, $message);
            }
            // The list should be comma-separated.
            $this->assertStringContainsString('security_level, cache_level', $message);
            // The Account Owned Token hint should be included so users know why
            // a permitted token may still see endpoint-level failures.
            $this->assertStringContainsString('Account Owned Token', $message);
        }
    }

    public function testApplyDefaultSettingsContinuesAfterPartialFailure()
    {
        $this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

        $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(
            array(
                'result' => array(
                    'plan' => array(
                        'legacy_id' => Plans::FREE_PLAN,
                    ),
                ),
            )
        );
        $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
        // Only hotlink_protection (11th call, index 10) fails
        $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturnOnConsecutiveCalls(
            ...$this->settingResults(13, array(10 => false)) // hotlink_protection
        );
        // All 13 settings should be attempted
        $this->mockWordPressClientAPI->expects($this->exactly(13))->method('changeZoneSettings');

        try {
            $this->pluginActions->applyDefaultSettings();
            $this->fail('Expected ZoneSettingFailException was not thrown.');
        } catch (\Cloudflare\APO\API\Exception\ZoneSettingFailException $e) {
            $message = $e->getMessage();
            // Combined assertions, since expectExceptionMessageMatches() is a setter
            // and a second call would silently overwrite the first.
            $this->assertStringContainsString('hotlink_protection', $message);
            $this->assertMatchesRegularExpression('/remaining settings were applied successfully/', $message);
        }
    }

    public function testApplyDefaultSettingsContinuesWhenFirstAndLastFreeSettingsFail()
    {
        // Regression test for the fail-fast bug: a failure on the very first
        // setting must not abort the loop, and a later failure must still be
        // collected. Failing the first and last Free-plan settings exercises
        // both ends of the loop in a single test.
        $this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

        $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(
            array(
                'result' => array(
                    'plan' => array(
                        'legacy_id' => Plans::FREE_PLAN,
                    ),
                ),
            )
        );
        $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
        $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturnOnConsecutiveCalls(
            // security_level (first) and automatic_https_rewrites (last)
            ...$this->settingResults(13, array(0 => false, 12 => false))
        );
        $this->mockWordPressClientAPI->expects($this->exactly(13))->method('changeZoneSettings');

        try {
            $this->pluginActions->applyDefaultSettings();
            $this->fail('Expected ZoneSettingFailException was not thrown.');
        } catch (\Cloudflare\APO\API\Exception\ZoneSettingFailException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('security_level', $message);
            $this->assertStringContainsString('automatic_https_rewrites', $message);
            // Settings that succeeded must not appear in the failure list.
            $this->assertStringNotContainsString('cache_level', $message);
            $this->assertStringNotContainsString('hotlink_protection', $message);
        }
    }

    public function testApplyDefaultSettingsBusinessPlanContinuesAfterPolishFailure()
    {
        // Business plan adds `mirage` and `polish` (15 total). Verify the loop
        // still attempts every setting and only the failing one is reported,
        // exercising the conditional plan-tier extension of the settings list.
        $this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

        $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(
            array(
                'result' => array(
                    'plan' => array(
                        'legacy_id' => Plans::BIZ_PLAN,
                    ),
                ),
            )
        );
        $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
        // Only `polish` (15th call, index 14) fails.
        $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturnOnConsecutiveCalls(
            ...$this->settingResults(15, array(14 => false)) // polish
        );
        $this->mockWordPressClientAPI->expects($this->exactly(15))->method('changeZoneSettings');

        try {
            $this->pluginActions->applyDefaultSettings();
            $this->fail('Expected ZoneSettingFailException was not thrown.');
        } catch (\Cloudflare\APO\API\Exception\ZoneSettingFailException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('polish', $message);
            $this->assertStringNotContainsString('mirage', $message);
        }
    }

    public function testApplyDefaultSettingsSucceedsWhenAllSettingsPass()
    {
        $this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

        $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(
            array(
                'result' => array(
                    'plan' => array(
                        'legacy_id' => Plans::FREE_PLAN,
                    ),
                ),
            )
        );
        $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
        $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturn(true);

        // Should not throw any exception
        $this->pluginActions->applyDefaultSettings();
        $this->addToAssertionCount(1);
    }

    /**
     * Build the per-call results for changeZoneSettings(): every call
     * succeeds except the zero-based call indexes listed in $failures.
     *
     * @param int               $calls    Number of zone setting calls.
     * @param array<int, false> $failures Results to override, keyed by call index.
     *
     * @return bool[]
     */
    private function settingResults($calls, array $failures)
    {
        return array_replace(array_fill(0, $calls, true), $failures);
    }
}
