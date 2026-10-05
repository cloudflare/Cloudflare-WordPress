<?php

namespace Cloudflare\APO\Tests\Unit\API;

use Cloudflare\APO\API\Plugin;

class AbstractPluginActionsTest extends \PHPUnit\Framework\TestCase
{
    protected $mockAbstractPluginActions;
    protected $mockAPIClient;
    protected $mockClientAPI;
    protected $mockDataStore;
    protected $mockLogger;
    protected $mockDefaultIntegration;
    protected $mockRequest;
    protected $pluginActions;

    public function setUp(): void
    {
        $this->mockAPIClient = $this->getMockBuilder('\Cloudflare\APO\API\Plugin')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockClientAPI = $this->getMockBuilder('\Cloudflare\APO\API\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('\Cloudflare\APO\Integration\DataStoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRequest = $this->getMockBuilder('\Cloudflare\APO\API\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockAbstractPluginActions = $this->getMockBuilder('Cloudflare\APO\API\AbstractPluginActions')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockDefaultIntegration = $this->getMockBuilder('\Cloudflare\APO\Integration\DefaultIntegration')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockAbstractPluginActions->setAPI($this->mockAPIClient);
        $this->mockAbstractPluginActions->setClientAPI($this->mockClientAPI);
        $this->mockAbstractPluginActions->setDataStore($this->mockDataStore);
        $this->mockAbstractPluginActions->setLogger($this->mockLogger);
        $this->mockAbstractPluginActions->setRequest($this->mockRequest);
    }

    public function testPostAccountSaveAPICredentialsReturnsErrorIfMissingApiKey()
    {
        $this->mockRequest->method('getBody')->willReturn(array(
            'email' => 'email',
        ));
        $this->mockAPIClient->method('createAPIError')->willReturn(array('success' => false));

        $response = $this->mockAbstractPluginActions->login();

        $this->assertFalse($response['success']);
    }

    public function testPostAccountSaveAPICredentialsReturnsErrorIfMissingEmail()
    {
        $this->mockRequest->method('getBody')->willReturn(array(
            'apiKey' => 'apiKey',
        ));
        $this->mockAPIClient->method('createAPIError')->willReturn(array('success' => false));

        $response = $this->mockAbstractPluginActions->login();

        $this->assertFalse($response['success']);
    }

    public function testGetPluginSettingsReturnsArray()
    {
        $this->mockDataStore->method('get')->willReturn(array());
        $this->mockAPIClient
            ->expects($this->once())
            ->method('createAPISuccessResponse')
            ->will($this->returnCallback(function ($input) {
                $this->assertTrue(is_array($input));
            }));
        $this->mockAbstractPluginActions->getPluginSettings();
    }

    public function testPatchPluginSettingsReturnsErrorForBadSetting()
    {
        $this->mockRequest->method('getUrl')->willReturn('plugin/:id/settings/nonExistentSetting');
        $this->mockAPIClient->expects($this->once())->method('createAPIError');
        $this->mockAbstractPluginActions->patchPluginSettings();
    }

    public function testGetPluginSettingsHandlesSuccess()
    {
        /*
         * This assertion should fail as we add new settings and should be updated to reflect
         * count(Plugin::getPluginSettingsKeys())
         */
        $this->mockDataStore->method('get')->willReturn(array());
        $this->mockDataStore->expects($this->exactly(7))->method('get');
        $this->mockAPIClient->expects($this->once())->method('createAPISuccessResponse');
        $this->mockAbstractPluginActions->getPluginSettings();
    }

    public function testPatchPluginSettingsUpdatesSetting()
    {
        $value = 'value';
        $settingId = 'settingId';
        $this->mockRequest->method('getUrl')->willReturn('plugin/:zonedId/settings/' . $settingId);
        $this->mockRequest->method('getBody')->willReturn(array($value => $value));
        $this->mockDataStore->method('set')->willReturn(true);
        $this->mockDataStore->expects($this->once())->method('set');
        $this->mockAPIClient->expects($this->once())->method('createAPISuccessResponse');
        $this->mockAbstractPluginActions->patchPluginSettings();
    }

    public function testPatchPluginSettingsReturnsErrorIfSettingUpdateFails()
    {
        $value = 'value';
        $settingId = 'settingId';
        $this->mockRequest->method('getUrl')->willReturn('plugin/:zonedId/settings/' . $settingId);
        $this->mockRequest->method('getBody')->willReturn(array($value => $value));
        $this->mockDataStore->method('set')->willReturn(null);
        $this->mockDataStore->expects($this->once())->method('set');
        $this->mockAPIClient->expects($this->once())->method('createAPIError');
        $this->mockAbstractPluginActions->patchPluginSettings();
    }

    public function testLoginReturnsErrorIfAPIKeyOrEmailAreInvalid()
    {
        $apiKey = 'apiKey';
        $email = 'email';
        $this->mockRequest->method('getBody')->willReturn(array(
            $apiKey => $apiKey,
            $email => $email,
        ));
        $this->mockDataStore->method('createUserDataStore')->willReturn(true);
        $this->mockClientAPI->method('responseOk')->willReturn(false);

        $this->mockAPIClient->expects($this->once())->method('createAPIError');
        $this->mockAbstractPluginActions->login();
    }

    public function testLoginTrimsWhitespaceBeforeStoringCredentials()
    {
        // Simulate a credential pasted from the dashboard with surrounding
        // whitespace/newlines. The login flow must store the trimmed value
        // so that Client::isGlobalApiKey() routes auth correctly.
        // nosemgrep: generic.secrets.security.detected-generic-api-key.detected-generic-api-key
        $rawApiKey = "  cfk_" . str_repeat('a', 40) . "X1y2\n";
        $rawEmail = "  test@email.com\n";
        $trimmedApiKey = trim($rawApiKey);
        $trimmedEmail = trim($rawEmail);

        $this->mockRequest->method('getBody')->willReturn(array(
            'apiKey' => $rawApiKey,
            'email' => $rawEmail,
        ));
        $this->mockClientAPI->method('responseOk')->willReturn(true);

        $this->mockDataStore
            ->expects($this->once())
            ->method('createUserDataStore')
            ->with(
                $this->identicalTo($trimmedApiKey),
                $this->identicalTo($trimmedEmail),
                $this->isNull(),
                $this->isNull()
            )
            ->willReturn(true);

        $this->mockAPIClient
            ->expects($this->once())
            ->method('createAPISuccessResponse')
            ->with($this->identicalTo(array('email' => $trimmedEmail)));

        $this->mockAbstractPluginActions->login();
    }

    public function testGetPluginSettingsCallsCreatePluginSettingObjectIfDataStoreGetIsNull()
    {
        $this->mockDataStore->method('get')->willReturn(null);
        $this->mockAPIClient->expects($this->atLeastOnce())->method('createPluginSettingObject');
        $this->mockAbstractPluginActions->getPluginSettings();
    }

    public function testPatchPluginSettingsCallsApplyDefaultSettingsIfSettingIsDefaultSettings()
    {
        $settingId = 'default_settings';
        $this->mockRequest->method('getUrl')->willReturn('plugin/:zonedId/settings/' . $settingId);
        $this->mockDataStore->method('set')->willReturn(true);
        $this->mockAbstractPluginActions->expects($this->once())->method('applyDefaultSettings');
        $this->mockAbstractPluginActions->patchPluginSettings();
    }

    public function testGetUserConfigReturnsEmptyJson()
    {
        $this->mockAPIClient->expects($this->once())->method('createAPISuccessResponse')->with([]);
        $this->mockAbstractPluginActions->getConfig();
    }
}
