<?php

namespace CF\Router\Test;

use CF\API\Client;
use CF\Integration\DefaultIntegration;
use CF\Router\RequestRouter;

class RequestRouterTest extends \PHPUnit_Framework_TestCase
{
    protected $mockConfig;
    protected $mockAPI;
    protected $mockIntegration;
    protected $mockDataStore;
    protected $mockLogger;
    protected $mockRequest;
    protected $requestRouter;

    public function setup()
    {
        $this->mockConfig = $this->getMockBuilder('CF\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockAPI = $this->getMockBuilder('CF\Integration\IntegrationAPIInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('CF\Integration\DataStoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('CF\Integration\DefaultLogger')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockIntegration = new DefaultIntegration($this->mockConfig, $this->mockAPI, $this->mockDataStore, $this->mockLogger);

        $this->mockRequest = $this->getMockBuilder('CF\API\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestRouter = new RequestRouter($this->mockIntegration);
    }

    public function testAddRouterAddsRouter()
    {
        $this->requestRouter->addRouter('CF\API\Client', null);
        $this->assertEquals('CF\Router\DefaultRestAPIRouter', get_class($this->requestRouter->getRouterList()[Client::CLIENT_API_NAME]));
    }

    public function testRoutePassesValidRequestToDefaultRestAPIRouter()
    {
        $mockDefaultRestAPIRouter = $this->getMockBuilder('CF\Router\DefaultRestAPIRouter')
            ->disableOriginalConstructor()
            ->getMock();
        $mockAPIClient = $this->getMockBuilder('CF\API\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $mockAPIClient->method('shouldRouteRequest')->willReturn(true);
        $mockDefaultRestAPIRouter->method('getAPIClient')->willReturn($mockAPIClient);
        $mockDefaultRestAPIRouter->expects($this->once())->method('route');

        $this->requestRouter->setRouterList(array($mockDefaultRestAPIRouter));

        $this->requestRouter->route($this->mockRequest);
    }
}
