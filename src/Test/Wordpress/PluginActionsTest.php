<?php

namespace CF\Test\WordPress;

use CF\API\Request;
use CF\API\Plugin;
use CF\WordPress\PluginActions;
use CF\Integration\DefaultIntegration;

class PluginActionsTest extends \PHPUnit_Framework_TestCase
{
    private $pluginAPI;
    private $mockConfig;
    private $mockWordPressAPI;
    private $mockDataStore;
    private $mockLogger;
    private $mockDefaultIntegration;

    public function setup()
    {
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

        $this->pluginAPI = new Plugin($this->mockDefaultIntegration);
    }

    public function testLoginWordPressSuccess()
    {
        $email = 'email@example.com';
        $apiKey = 'apiKey';
        $request = new Request(null, null, null, array('apiKey' => $apiKey, 'email' => $email));

        $this->mockDataStore->method('createUserDataStore')->willReturn(true);

        $pluginActions = new PluginActions($this->mockDefaultIntegration, $this->pluginAPI, $request);
        $response = $pluginActions->loginWordPress();

        $this->assertEquals($email, $response['result']['email']);
        $this->assertEquals(true, $response['success']);
    }

    public function testLoginWordPressFail()
    {
        $request = new Request(null, null, null, null);

        $this->mockDataStore->method('createUserDataStore')->willReturn(false);
        $pluginActions = new PluginActions($this->mockDefaultIntegration, $this->pluginAPI, $request);
        $response = $pluginActions->loginWordPress();

        $this->assertEquals(false, $response['success']);
    }

    public function testGetPluginSettings()
    {
        $settingId = 'ip_rewrite';
        $value = true;

        $request = new Request(null, 'plugin/:zonedId/settings', null, null);

        $this->mockDataStore->method('getPluginSettings')->willReturn(
            array($settingId => $value)
        );

        $pluginActions = new PluginActions($this->mockDefaultIntegration, $this->pluginAPI, $request);
        $response = $pluginActions->getPluginSettings();

        $this->assertEquals(true, $response['success']);
        $this->assertEquals($settingId, $response['result'][0]['id']);
        $this->assertEquals($value, $response['result'][0]['value']);
    }

    public function testPatchPluginSettingsRouterRoutesGeneralSettings()
    {
        $settingId = 'someSettingId';

        $request = new Request(null, "plugin/:zonedId/settings/$settingId", null, array());

        $pluginActions = $this->getMockBuilder('CF\WordPress\PluginActions')
        ->setMethods(array('__construct'))
        ->setConstructorArgs(array($this->mockDefaultIntegration, $this->pluginAPI, $request))
        ->setMethods(array('patchPluginSettings'))
        ->getMock();

        $pluginActions->expects($this->once())
                    ->method('patchPluginSettings');

        $pluginActions->patchPluginSettingsRouter();
    }

    public function testPatchPluginSettingsRouterRoutesDefaultDefaultSettings()
    {
        $settingId = Plugin::SETTING_DEFAULT_SETTINGS;

        $request = new Request(null, "plugin/:zonedId/settings/$settingId", null, array());

        $pluginActions = $this->getMockBuilder('CF\WordPress\PluginActions')
        ->setMethods(array('__construct'))
        ->setConstructorArgs(array($this->mockDefaultIntegration, $this->pluginAPI, $request))
        ->setMethods(array('patchPluginDefaultSettings'))
        ->getMock();

        $pluginActions->expects($this->once())
                    ->method('patchPluginDefaultSettings');

        $pluginActions->patchPluginSettingsRouter();
    }

    public function testPatchPluginSettings()
    {
        $settingId = 'testId';
        $value = 'testValue';

        $request = new Request(null, "plugin/:zonedId/settings/$settingId", null, array('value' => $value));

        $this->mockDataStore->method('setPluginSetting')->willReturn(true);

        $pluginActions = new PluginActions($this->mockDefaultIntegration, $this->pluginAPI, $request);
        $response = $pluginActions->patchPluginSettings();

        $this->assertEquals(true, $response['success']);
        $this->assertEquals($settingId, $response['result'][0]['id']);
        $this->assertEquals($value, $response['result'][0]['value']);
    }

    public function testPatchPluginSettingsDefaultSettings()
    {
        $settingId = 'default_settings';
        $value = 'testValue';

        $request = new Request(null, 'plugin/:zonedId/settings/default_settings', null, array('value' => $value));

        $pluginActions = $this->getMockBuilder('CF\WordPress\PluginActions')
        ->setMethods(array('__construct'))
        ->setConstructorArgs(array($this->mockDefaultIntegration, $this->pluginAPI, $request))
        ->setMethods(array('makeAPICallsForDefaultSettings'))
        ->getMock();

        $this->mockDataStore->method('setPluginSetting')->willReturn(true);

        $response = $pluginActions->patchPluginSettingsRouter();

        $this->assertEquals(true, $response['success']);
        $this->assertEquals($settingId, $response['result'][0]['id']);
        $this->assertEquals($value, $response['result'][0]['value']);
    }
}
