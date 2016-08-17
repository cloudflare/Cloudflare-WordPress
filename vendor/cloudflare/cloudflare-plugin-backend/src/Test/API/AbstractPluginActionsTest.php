<?php

namespace CF\API\Test;

use CF\API\Plugin;

class AbstractPluginActionsTest extends \PHPUnit_Framework_TestCase
{

    protected $mockAbstractPluginActions;
    protected $mockAPIClient;
    protected $mockClientAPI;
    protected $mockDataStore;
    protected $mockLogger;
    protected $mockRequest;
    protected $pluginActions;

    public function setup()
    {
        $this->mockAPIClient = $this->getMockBuilder('\CF\API\Plugin')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockClientAPI = $this->getMockBuilder('\CF\API\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('\CF\Integration\DataStoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRequest = $this->getMockBuilder('\CF\API\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockAbstractPluginActions = $this->getMockBuilder('CF\API\AbstractPluginActions')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockAbstractPluginActions->setRequest($this->mockRequest);
        $this->mockAbstractPluginActions->setAPI($this->mockAPIClient);
        $this->mockAbstractPluginActions->setClientAPI($this->mockClientAPI);
        $this->mockAbstractPluginActions->setDataStore($this->mockDataStore);
        $this->mockAbstractPluginActions->setLogger($this->mockLogger);

    }

    public function testPostAccountSaveAPICredentialsReturnsErrorIfMissingApiKey() {
        $this->mockRequest->method('getBody')->willReturn(array(
            'email' => 'email'
        ));
        $this->mockAPIClient->method('createAPIError')->willReturn(array('success' => false));

        $response = $this->mockAbstractPluginActions->login();

        $this->assertFalse($response['success']);
    }

    public function testPostAccountSaveAPICredentialsReturnsErrorIfMissingEmail() {
        $this->mockRequest->method('getBody')->willReturn(array(
            'apiKey' => 'apiKey'
        ));
        $this->mockAPIClient->method('createAPIError')->willReturn(array('success' => false));

        $response = $this->mockAbstractPluginActions->login();

        $this->assertFalse($response['success']);
    }

    public function testGetPluginSettingsReturnsArray() {
        $this->mockAPIClient
            ->expects($this->once())
            ->method('createAPISuccessResponse')
            ->will($this->returnCallback(function($input) {
                $this->assertTrue(is_array($input));
            }));
        $this->mockAbstractPluginActions->getPluginSettings();
    }

    public function testPatchPluginSettingsReturnsErrorForBadSetting() {
        $this->mockRequest->method('getUrl')->willReturn('plugin/:id/settings/nonExistentSetting');
        $this->mockAPIClient->expects($this->once())->method('createAPIError');
        $this->mockAbstractPluginActions->patchPluginSettings();
    }

    public function testGetPluginSettingsHandlesSuccess() {
        /*
         * This assertion should fail as we add new settings and should be updated to reflect
         * count(Plugin::getPluginSettingsKeys())
         */
        $this->mockDataStore->expects($this->exactly(5))->method('get');
        $this->mockAPIClient->expects($this->once())->method('createAPISuccessResponse');
        $this->mockAbstractPluginActions->getPluginSettings();
    }

    public function testPatchPluginSettingsRouterRoutesGeneralSettings()
    {
        //mock patchPluginSettings so we can tell if its being called.
        $this->mockAbstractPluginActions = $this->getMockBuilder('CF\API\AbstractPluginActions')
            ->disableOriginalConstructor()
            ->setMethods(array('patchPluginSettings'))
            ->getMockForAbstractClass();
        $this->mockAbstractPluginActions->setRequest($this->mockRequest);

        $settingId = 'someSettingId';
        $this->mockRequest->method('getUrl')->willReturn('plugin/:zonedId/settings/'.$settingId);
        $this->mockAbstractPluginActions->expects($this->once())->method('patchPluginSettings');
        $this->mockAbstractPluginActions->patchPluginSettingsRouter();

    }

    public function testPatchPluginSettingsRouterRoutesDefaultSettings()
    {
        //mock patchPluginDefaultSettings so we can tell if its being called.
        $this->mockAbstractPluginActions = $this->getMockBuilder('CF\API\AbstractPluginActions')
            ->disableOriginalConstructor()
            ->setMethods(array('patchPluginDefaultSettings'))
            ->getMockForAbstractClass();
        $this->mockAbstractPluginActions->setRequest($this->mockRequest);

        $this->mockRequest->method('getUrl')->willReturn('plugin/:zonedId/settings/'.Plugin::SETTING_DEFAULT_SETTINGS);
        $this->mockAbstractPluginActions->expects($this->once())->method('patchPluginDefaultSettings');
        $this->mockAbstractPluginActions->patchPluginSettingsRouter();
    }

    public function testPatchPluginSettingsUpdatesSetting() {
        $value = "value";
        $settingId = "settingId";
        $this->mockRequest->method('getUrl')->willReturn('plugin/:zonedId/settings/'.$settingId);
        $this->mockRequest->method('getBody')->willReturn(array($value => $value));
        $this->mockDataStore->method('set')->willReturn(true);
        $this->mockDataStore->expects($this->once())->method('set')->with($settingId, $value);
        $this->mockAPIClient->expects($this->once())->method('createAPISuccessResponse');
        $this->mockAbstractPluginActions->patchPluginSettings();
    }

    public function testPatchPluginSettingsReturnsErrorIfSettingUpdateFails() {
        $value = "value";
        $settingId = "settingId";
        $this->mockRequest->method('getUrl')->willReturn('plugin/:zonedId/settings/'.$settingId);
        $this->mockRequest->method('getBody')->willReturn(array($value => $value));
        $this->mockDataStore->method('set')->willReturn(null);
        $this->mockDataStore->expects($this->once())->method('set')->with($settingId, $value);
        $this->mockAPIClient->expects($this->once())->method('createAPIError');
        $this->mockAbstractPluginActions->patchPluginSettings();
    }

    public function testPatchPluginDefaultSettingsUpdatesDefaultSettings() {
        $value = true;
        $settingId = Plugin::SETTING_DEFAULT_SETTINGS;
        $this->mockRequest->method('getUrl')->willReturn('plugin/:zonedId/settings/'.$settingId);
        $this->mockRequest->method('getBody')->willReturn(array("value" => $value));
        $this->mockDataStore->expects($this->once())->method('set')->with($settingId, $value);
        $this->mockAbstractPluginActions->expects($this->once())->method('applyDefaultSettings');
        $this->mockAPIClient->expects($this->once())->method('createAPISuccessResponse');
        $this->mockAbstractPluginActions->patchPluginDefaultSettings();
    }

    public function testPatchPluginDefaultSettingsHandlesCloudFlareException() {
        $this->mockAbstractPluginActions->method('applyDefaultSettings')->will($this->throwException(new \CF\API\Exception\ZoneSettingFailException()));
        $this->mockAPIClient->expects($this->once())->method('createAPIError');
        $this->mockAbstractPluginActions->patchPluginDefaultSettings();
    }
}

