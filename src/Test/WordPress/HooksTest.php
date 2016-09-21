<?php

namespace CF\Test\WordPress;

use \CF\WordPress\Hooks;
use \CF\Integration\DefaultIntegration;
use phpmock\phpunit\PHPMock;

class HooksTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    protected $hooks;
    protected $mockConfig;
    protected $mockDataStore;
    protected $mockLogger;
    protected $mockIPRewrite;
    protected $mockWordPressAPI;
    protected $mockDefaultIntegration;

    public function setup() {
        $this->mockConfig = $this->getMockBuilder('CF\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('CF\WordPress\DataStore')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockIPRewrite = $this->getMockBuilder('\CloudFlare\IpRewrite')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockWordPressAPI = $this->getMockBuilder('CF\WordPress\WordPressAPI')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDefaultIntegration = new DefaultIntegration($this->mockConfig, $this->mockWordPressAPI, $this->mockDataStore, $this->mockLogger);
        $this->hooks = new Hooks($this->mockDefaultIntegration);
        $this->hooks->setIPRewrite($this->mockIPRewrite);
    }

    function testInitExecutesFlexibleSSLFix() {
        $this->mockIPRewrite->method('isCloudFlare')->willReturn(true);
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = true;
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->hooks->init();
        $this->assertEquals('on', $_SERVER['HTTPS']);
    }

    function testCloudflareConfigPageCallsAddOptionsPageHookIfItExsits() {
        $mockFunctionExists = $this->getFunctionMock('CF\WordPress', 'function_exists');
        $mockFunctionExists->expects($this->once())->willReturn(true);
        $mock__ = $this->getFunctionMock('CF\WordPress', '__');
        $mockAddOptionsPage = $this->getFunctionMock('CF\WordPress', 'add_options_page');
        $mockAddOptionsPage->expects($this->once());
        $this->hooks->cloudflareConfigPage();
    }

    function testPluginActionLinksGetAdminUrl() {
        $mockGetAdminUrl = $this->getFunctionMock('CF\WordPress', 'get_admin_url');
        $url = 'options-general.php?page=cloudflare';
        $link = '<a href="'. $url . '">Settings</a>';
        $mockGetAdminUrl->expects($this->once())->with(null, $url)->willReturn($url);
        $this->assertEquals(array($link), $this->hooks->pluginActionLinks(array()));
    }

}
