<?php

namespace CF\Test\WordPress;

use CF\WordPress\Proxy;
use CF\Integration\DefaultIntegration;
use phpmock\phpunit\PHPMock;

class ProxyTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;

    protected $mockConfig;
    protected $mockDataStore;
    protected $mockLogger;
    protected $mockWordPressAPI;
    protected $mockWordPressClientAPI;
    protected $mockDefaultIntegration;
    protected $mockRequestRouter;
    protected $proxy;

    public function setup()
    {
        $this->mockConfig = $this->getMockBuilder('CF\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('CF\WordPress\DataStore')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockWordPressAPI = $this->getMockBuilder('CF\WordPress\WordPressAPI')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockWordPressClientAPI = $this->getMockBuilder('CF\WordPress\WordPressClientAPI')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRequestRouter = $this->getMockBuilder('\CF\Router\RequestRouter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDefaultIntegration = new DefaultIntegration($this->mockConfig, $this->mockWordPressAPI, $this->mockDataStore, $this->mockLogger);
        $this->proxy = new Proxy($this->mockDefaultIntegration);
        $this->proxy->setWordpressClientAPI($this->mockWordPressClientAPI);
        $this->proxy->setRequestRouter($this->mockRequestRouter);

        $mockHeader = $this->getFunctionMock('CF\WordPress', 'header');
    }

    public function testRunHandlesGet()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['proxyURL'] = 'proxyUrl';
        $_GET['proxyURLType'] = 'proxyUrlType';
        $this->mockRequestRouter->expects($this->once())->method('route');
        $mockWPDie = $this->getFunctionMock('CF\WordPress', 'wp_die');
        $this->proxy->run();
    }

    public function testRunHandlesPost()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $jsonBody = json_encode(array(
            'proxyURL' => 'proxyURL',
            'proxyURLType' => 'proxyURLType',
            'cfCSRFToken' => 'cfCSRFToken',
        ));
        $mockFileGetContents = $this->getFunctionMock('CF\WordPress', 'file_get_contents');
        $mockFileGetContents->expects($this->any())->willReturn($jsonBody);
        $mockWPVerifyNonce = $this->getFunctionMock('CF\WordPress', 'wp_verify_nonce');
        $mockWPVerifyNonce->expects($this->once())->willReturn(true);
        $this->mockRequestRouter->expects($this->once())->method('route');
        $mockWPDie = $this->getFunctionMock('CF\WordPress', 'wp_die');
        $this->proxy->run();
    }

    public function testIsCloudFlareCSRFTokenValidReturnsTrueForGet()
    {
        $this->assertTrue($this->proxy->isCloudFlareCSRFTokenValid('GET', null));
    }

    public function testCreateRequestGETProxyClient()
    {
        $url = 'testproxyurl.com';
        $proxyURL = 'proxyurl.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['proxyURL'] = $url;
        $_GET['proxyURLType'] = 'CLIENT';
        $jsonBody = json_encode(array(
            'proxyURL' => $proxyURL,
        ));

        $mockFileGetContents = $this->getFunctionMock('CF\WordPress', 'file_get_contents');
        $mockFileGetContents->expects($this->any())->willReturn($jsonBody);

        $request = $this->proxy->createRequest();

        $this->assertFalse(isset($request->getParameters()['proxyURL']));
        $this->assertFalse(isset($request->getParameters()['proxyURLType']));
        $this->assertFalse(isset($request->getBody()['proxyURL']));
        $this->assertEquals(\CF\API\Client::ENDPOINT.$url, $request->getUrl());
    }

    public function testCreateRequestGETProxyPlugin()
    {
        $url = 'testproxyurl.com';
        $proxyURL = 'proxyurl.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['proxyURL'] = $url;
        $_GET['proxyURLType'] = 'PLUGIN';
        $jsonBody = json_encode(array(
            'proxyURL' => $proxyURL,
        ));

        $mockFileGetContents = $this->getFunctionMock('CF\WordPress', 'file_get_contents');
        $mockFileGetContents->expects($this->any())->willReturn($jsonBody);

        $request = $this->proxy->createRequest();

        $this->assertFalse(isset($request->getParameters()['proxyURL']));
        $this->assertFalse(isset($request->getParameters()['proxyURLType']));
        $this->assertFalse(isset($request->getBody()['proxyURL']));
        $this->assertEquals(\CF\API\Plugin::ENDPOINT.$url, $request->getUrl());
    }

    public function testCreateRequestNonGET()
    {
        $proxyURL = 'proxyurl.com';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $jsonBody = json_encode(array(
            'proxyURL' => $proxyURL,
        ));

        $mockFileGetContents = $this->getFunctionMock('CF\WordPress', 'file_get_contents');
        $mockFileGetContents->expects($this->any())->willReturn($jsonBody);

        $request = $this->proxy->createRequest();

        $this->assertFalse(isset($request->getParameters()['proxyURL']));
        $this->assertFalse(isset($request->getParameters()['proxyURLType']));
        $this->assertFalse(isset($request->getBody()['proxyURL']));
        // $this->assertEquals($proxyURL, $request->getUrl());
    }
}
