<?php

namespace CF\Test\API;

use CF\API\Request;
use CF\Integration\DefaultIntegration;
use CF\API\Plugin;

class PluginTest extends \PHPUnit_Framework_TestCase
{
    private $mockPluginAPI;
    private $mockConfig;
    private $mockWordPressAPI;
    private $mockDataStore;
    private $mockLogger;
    private $mockDefaultIntegration;

    public function setup()
    {
        $this->mockPluginAPI = $this->getMockBuilder('CF\API\Plugin')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockConfig = $this->getMockBuilder('CF\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockWordPressAPI = $this->getMockBuilder('CF\WordPress\WordPressAPI')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('CF\WordPress\DataStore')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('CF\Integration\DefaultLogger')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDefaultIntegration = new DefaultIntegration($this->mockConfig, $this->mockWordPressAPI, $this->mockDataStore, $this->mockLogger);
    }

    public function testCreateAPISuccessResponse()
    {
        $resultString = 'result';
        $resultArray = array('email' => $resultString);

        $pluginAPI = new Plugin($this->mockDefaultIntegration);

        $firstResponse = $pluginAPI->createAPISuccessResponse($resultString);
        $secondResponse = $pluginAPI->createAPISuccessResponse($resultArray);

        $this->assertEquals('true', $firstResponse['success']);
        $this->assertEquals('true', $secondResponse['success']);
        $this->assertEquals($resultString, $firstResponse['result']);
        $this->assertEquals($resultArray, $secondResponse['result']);
    }

    public function testCallAPI()
    {
        $request = new Request(null, null, null, null);
        $pluginAPI = new Plugin($this->mockDefaultIntegration);

        $this->mockWordPressAPI->method('createAPIError')->willReturn($error);

        $response = $pluginAPI->callAPI($request);

        $this->assertEquals(false, $response['success']);
    }
}
