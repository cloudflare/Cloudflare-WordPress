<?php

namespace Cloudflare\APO\Router\Test;

use Cloudflare\APO\API\Request;
use Cloudflare\APO\Integration\DefaultIntegration;
use Cloudflare\APO\Router\DefaultRestAPIRouter;

class DefaultRestAPIRouterTest extends \PHPUnit\Framework\TestCase
{
    private $clientV4APIRouter;
    private $mockConfig;
    private $mockClientAPI;
    private $mockAPI;
    private $mockIntegration;
    private $mockDataStore;
    private $mockLogger;
    private $mockRoutes = array();

    public function setup(): void
    {
        $this->mockConfig = $this->getMockBuilder('Cloudflare\APO\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockClientAPI = $this->getMockBuilder('Cloudflare\APO\API\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockAPI = $this->getMockBuilder('Cloudflare\APO\Integration\IntegrationAPIInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('Cloudflare\APO\Integration\DataStoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('Cloudflare\APO\Integration\DefaultLogger')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockIntegration = new DefaultIntegration($this->mockConfig, $this->mockAPI, $this->mockDataStore, $this->mockLogger);
        $this->clientV4APIRouter = new DefaultRestAPIRouter($this->mockIntegration, $this->mockClientAPI, $this->mockRoutes);
    }

    public function testGetRouteReturnsClassFunctionForValidRoute()
    {
        $routes = array(
            'zones' => array(
                'class' => 'testClass',
                'methods' => array(
                    'GET' => array(
                        'function' => 'testFunction',
                    ),
                ),
            ),
        );
        $this->clientV4APIRouter->setRoutes($routes);

        $request = new Request('GET', 'zones', array(), array());

        $response = $this->clientV4APIRouter->getRoute($request);

        $this->assertEquals(array(
            'class' => 'testClass',
            'function' => 'testFunction',
        ), $response);
    }

    public function testGetRouteReturnsFalseForNoRouteFound()
    {
        $request = new Request('GET', 'zones', array(), array());
        $response = $this->clientV4APIRouter->getRoute($request);
        $this->assertFalse($response);
    }
}
