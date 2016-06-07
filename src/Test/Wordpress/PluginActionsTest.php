<?php

namespace CF\Test\WordPress;

use CF\API\Request;
use CF\WordPress\PluginActions;
use CF\Integration\DefaultIntegration;

class PluginActionsTest extends \PHPUnit_Framework_TestCase
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

    public function testLoginWordPressSuccess()
    {
        $email = 'email@example.com';
        $request = new Request(null, null, null, null);

        $this->mockPluginAPI->method('createAPISuccessResponse')->willReturn(
            array(
            'success' => 'true',
            'result' => array('email' => $email),
            )
        );
        $this->mockDataStore->method('createUserDataStore')->willReturn(true);

        $pluginActions = new PluginActions($this->mockDefaultIntegration, $this->mockPluginAPI, $request);
        $response = $pluginActions->loginWordPress();

        $this->assertEquals($email, $response['result']['email']);
        $this->assertEquals('true', $response['success']);
    }

    public function testLoginWordPressFail()
    {
        $email = 'email@example.com';
        $error = 'error';

        $request = new Request(null, null, null, null);
        $this->mockPluginAPI->method('createAPISuccessResponse')->willReturn(
            array(
            'success' => 'true',
            'result' => array('email' => $email),
            )
        );
        $this->mockDataStore->method('createUserDataStore')->willReturn(false);
        $this->mockPluginAPI->method('createAPIError')->willReturn($error);
        $pluginActions = new PluginActions($this->mockDefaultIntegration, $this->mockPluginAPI, $request);
        $response = $pluginActions->loginWordPress();

        $this->assertEquals($error, $response);
    }
}
