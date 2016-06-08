<?php

namespace CF\Router\Test;

use CF\API\Request;
use CF\Integration\DefaultIntegration;
use CF\Router\HostAPIRouter;

class HostAPIRouterTest extends \PHPUnit_Framework_TestCase
{

    private $hostAPIRouter;
    private $mockConfig;
    private $mockClientAPI;
    private $mockAPI;
    private $mockIntegration;
    private $mockDataStore;
    private $mockLogger;
    private $mockRoutes = array();

    public function setup()
    {
        $this->mockConfig = $this->getMockBuilder('CF\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockClientAPI = $this->getMockBuilder('CF\API\Host')
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
        $this->hostAPIRouter = new HostAPIRouter($this->mockIntegration, $this->mockClientAPI, $this->mockRoutes);
    }

    public function testGetPathReturnsHostAPIActParameter()
    {
        $request = new Request(null, null, null, array('act' => 'testAction'));
        $path = $this->hostAPIRouter->getPath($request);
        $this->assertEquals("testAction", $path);
    }
}
