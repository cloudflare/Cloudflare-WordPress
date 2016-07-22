<?php

namespace CF\Test\API;

use CF\API\Request;
use CF\Integration\DefaultIntegration;
use CF\API\Plugin;

class PluginTest extends \PHPUnit_Framework_TestCase
{
    private $mockConfig;
    private $mockWordPressAPI;
    private $mockDataStore;
    private $mockLogger;
    private $mockDefaultIntegration;
    private $pluginAPIClient;

    public function setup()
    {
        $this->mockConfig = $this->getMockBuilder('CF\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockWordPressAPI = $this->getMockBuilder('CF\Integration\IntegrationAPIInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('CF\Integration\DataStoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('CF\Integration\DefaultLogger')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDefaultIntegration = new DefaultIntegration($this->mockConfig, $this->mockWordPressAPI, $this->mockDataStore, $this->mockLogger);
        $this->pluginAPIClient = new Plugin($this->mockDefaultIntegration);
    }

    public function testCreateAPISuccessResponse()
    {
        $resultString = 'result';
        $resultArray = array('email' => $resultString);

        $firstResponse = $this->pluginAPIClient->createAPISuccessResponse($resultString);
        $secondResponse = $this->pluginAPIClient->createAPISuccessResponse($resultArray);

        $this->assertTrue($firstResponse['success']);
        $this->assertTrue($secondResponse['success']);
        $this->assertEquals($resultString, $firstResponse['result']);
        $this->assertEquals($resultArray, $secondResponse['result']);
    }

    public function testCreateAPIErrorReturnsError()
    {
        $response = $this->pluginAPIClient->createAPIError('error Message');

        $this->assertFalse($response['success']);
    }

    public function testCallAPIReturnsError()
    {
        $request = new Request(null, null, null, null);

        $response = $this->pluginAPIClient->callAPI($request);

        $this->assertFalse($response['success']);
    }
}
