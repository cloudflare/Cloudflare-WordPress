<?php

namespace Cloudflare\APO\Test\WordPress;

use Cloudflare\APO\WordPress\Hooks;
use Cloudflare\APO\Integration\DefaultIntegration;
use phpmock\phpunit\PHPMock;

class HooksTest extends \PHPUnit\Framework\TestCase
{
    use PHPMock;

    protected $hooks;
    protected $mockConfig;
    protected $mockDataStore;
    protected $mockLogger;
    protected $mockWordPressAPI;
    protected $mockWordPressClientAPI;
    protected $mockDefaultIntegration;
    protected $mockProxy;

    public function setup(): void
    {
        $this->mockConfig = $this->getMockBuilder('Cloudflare\APO\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('Cloudflare\APO\WordPress\DataStore')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockProxy = $this->getMockBuilder('Cloudflare\APO\WordPress\Proxy')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockWordPressAPI = $this->getMockBuilder('Cloudflare\APO\WordPress\WordPressAPI')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockWordPressClientAPI = $this->getMockBuilder('Cloudflare\APO\WordPress\WordPressClientAPI')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDefaultIntegration = new DefaultIntegration($this->mockConfig, $this->mockWordPressAPI, $this->mockDataStore, $this->mockLogger);

        $this->hooks = $this->getMockBuilder('\Cloudflare\APO\WordPress\Hooks')
            ->disableOriginalConstructor()
            ->setMethods(array('__construct')) // This is a hack to make the tests work
            ->getMock();

        $this->hooks->setAPI($this->mockWordPressClientAPI);
        $this->hooks->setConfig($this->mockConfig);
        $this->hooks->setDataStore($this->mockDataStore);
        $this->hooks->setLogger($this->mockLogger);
        $this->hooks->setIntegrationAPI($this->mockWordPressAPI);
        $this->hooks->setIntegrationContext($this->mockDefaultIntegration);
        $this->hooks->setProxy($this->mockProxy);
    }

    public function testCloudflareConfigPageCallsAddOptionsPageHookIfItExists()
    {
        $mockFunctionExists = $this->getFunctionMock('Cloudflare\APO\WordPress', 'function_exists');
        $mockFunctionExists->expects($this->once())->willReturn(true);
        $mock__ = $this->getFunctionMock('Cloudflare\APO\WordPress', '__');
        $mockAddOptionsPage = $this->getFunctionMock('Cloudflare\APO\WordPress', 'add_options_page');
        $mockAddOptionsPage->expects($this->once());
        $this->hooks->cloudflareConfigPage();
    }

    public function testPluginActionLinksGetAdminUrl()
    {
        $mockGetAdminUrl = $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_admin_url');
        $url = 'options-general.php?page=cloudflare';
        $link = '<a href="'.$url.'">Settings</a>';
        $mockGetAdminUrl->expects($this->once())->with(null, $url)->willReturn($url);
        $this->assertEquals(array($link), $this->hooks->pluginActionLinks(array()));
    }

    public function testInitProxyCallsProxyRun()
    {
        $this->mockWordPressAPI->method('isCurrentUserAdministrator')->willReturn(true);
        $this->mockProxy->expects($this->once())->method('run');
        $this->hooks->initProxy();
    }

    public function testActivateChecksWPVersionAndCurl()
    {
        define('CLOUDFLARE_MIN_WP_VERSION', '3.4');
        $GLOBALS['wp_version'] = '3.5';
        $this->assertTrue($this->hooks->activate());
    }

    public function testDeactivateCallsClearDataStore()
    {
        $this->mockDataStore->expects($this->once())->method('clearDataStore');
        $this->hooks->deactivate();
    }

    public function testPurgeCacheCallsZonePurgeCache()
    {
        $this->mockDataStore->method('getPluginSetting')->willReturn(array('value' => 'value'));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('domain.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->expects($this->once())->method('zonePurgeCache');
        $this->hooks->purgeCacheEverything();
    }
}
