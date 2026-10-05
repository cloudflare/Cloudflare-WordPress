<?php

namespace Cloudflare\APO\Tests\Unit\API;

use Cloudflare\APO\API\Client;
use Cloudflare\APO\Integration\DefaultIntegration;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    private $mockConfig;
    private $mockClientAPI;
    private $mockAPI;
    private $mockDataStore;
    private $mockLogger;
    private $mockCpanelIntegration;

    public function setUp(): void
    {
        $this->mockConfig = $this->getMockBuilder('Cloudflare\APO\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockAPI = $this->getMockBuilder('Cloudflare\APO\Integration\IntegrationAPIInterface')
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('Cloudflare\APO\Integration\DataStoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('Cloudflare\APO\Integration\DefaultLogger')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockCpanelIntegration = new DefaultIntegration($this->mockConfig, $this->mockAPI, $this->mockDataStore, $this->mockLogger);

        $this->mockClientAPI = new Client($this->mockCpanelIntegration);
    }

    public function testBeforeSendAddsRequestHeaders()
    {
        // nosemgrep: generic.secrets.security.detected-generic-api-key.detected-generic-api-key
        $apiKey = '41db178adf2ef1c82c84db6ca455457646d33';
        $email = 'test@email.com';

        $this->mockDataStore->method('getClientV4APIKey')->willReturn($apiKey);
        $this->mockDataStore->method('getCloudFlareEmail')->willReturn($email);

        $request = new \Cloudflare\APO\API\Request(null, null, null, null);
        $beforeSendRequest = $this->mockClientAPI->beforeSend($request);

        $actualRequestHeaders = $beforeSendRequest->getHeaders();
        $expectedRequestHeaders = array(
            Client::X_AUTH_KEY => $apiKey,
            Client::X_AUTH_EMAIL => $email,
            Client::CONTENT_TYPE_KEY => Client::APPLICATION_JSON_KEY,
        );

        $this->assertEquals($expectedRequestHeaders[Client::X_AUTH_KEY], $actualRequestHeaders[Client::X_AUTH_KEY]);
        $this->assertEquals($expectedRequestHeaders[Client::X_AUTH_EMAIL], $actualRequestHeaders[Client::X_AUTH_EMAIL]);
        $this->assertEquals($expectedRequestHeaders[Client::CONTENT_TYPE_KEY], $actualRequestHeaders[Client::CONTENT_TYPE_KEY]);
    }

    public function testBeforeSendUsesGlobalKeyHeadersForCfkPrefix()
    {
        // New-format Global API Key: "cfk_" + 40 chars + checksum.
        // nosemgrep: generic.secrets.security.detected-generic-api-key.detected-generic-api-key
        $apiKey = 'cfk_' . str_repeat('a', 40) . 'X1y2';
        $email = 'test@email.com';

        $this->mockDataStore->method('getClientV4APIKey')->willReturn($apiKey);
        $this->mockDataStore->method('getCloudFlareEmail')->willReturn($email);

        $request = new \Cloudflare\APO\API\Request(null, null, null, null);
        $beforeSendRequest = $this->mockClientAPI->beforeSend($request);

        $headers = $beforeSendRequest->getHeaders();

        $this->assertSame($apiKey, $headers[Client::X_AUTH_KEY]);
        $this->assertSame($email, $headers[Client::X_AUTH_EMAIL]);
        $this->assertArrayNotHasKey(Client::AUTHORIZATION, $headers);
    }

    public function testBeforeSendUsesBearerAuthForApiToken()
    {
        // Pre-2026 API Token format: 40-char alphanumeric (mixed case).
        // nosemgrep: generic.secrets.security.detected-generic-api-key.detected-generic-api-key
        $apiKey = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMN';
        $email = 'test@email.com';

        $this->mockDataStore->method('getClientV4APIKey')->willReturn($apiKey);
        $this->mockDataStore->method('getCloudFlareEmail')->willReturn($email);

        $request = new \Cloudflare\APO\API\Request(null, null, null, null);
        $beforeSendRequest = $this->mockClientAPI->beforeSend($request);

        $headers = $beforeSendRequest->getHeaders();

        $this->assertSame("Bearer {$apiKey}", $headers[Client::AUTHORIZATION]);
        $this->assertArrayNotHasKey(Client::X_AUTH_KEY, $headers);
        $this->assertArrayNotHasKey(Client::X_AUTH_EMAIL, $headers);
    }

    public function testBeforeSendUsesBearerAuthForCfutPrefix()
    {
        // New-format User API Token: "cfut_" + 40 chars + checksum.
        // nosemgrep: generic.secrets.security.detected-generic-api-key.detected-generic-api-key
        $apiKey = 'cfut_' . str_repeat('a', 40) . 'X1y2';
        $email = 'test@email.com';

        $this->mockDataStore->method('getClientV4APIKey')->willReturn($apiKey);
        $this->mockDataStore->method('getCloudFlareEmail')->willReturn($email);

        $request = new \Cloudflare\APO\API\Request(null, null, null, null);
        $beforeSendRequest = $this->mockClientAPI->beforeSend($request);

        $headers = $beforeSendRequest->getHeaders();

        $this->assertSame("Bearer {$apiKey}", $headers[Client::AUTHORIZATION]);
        $this->assertArrayNotHasKey(Client::X_AUTH_KEY, $headers);
        $this->assertArrayNotHasKey(Client::X_AUTH_EMAIL, $headers);
    }

    public function testBeforeSendUsesBearerAuthForCfatPrefix()
    {
        // New-format Account API Token: "cfat_" + 40 chars + checksum.
        // nosemgrep: generic.secrets.security.detected-generic-api-key.detected-generic-api-key
        $apiKey = 'cfat_' . str_repeat('b', 40) . 'X1y2';
        $email = 'test@email.com';

        $this->mockDataStore->method('getClientV4APIKey')->willReturn($apiKey);
        $this->mockDataStore->method('getCloudFlareEmail')->willReturn($email);

        $request = new \Cloudflare\APO\API\Request(null, null, null, null);
        $beforeSendRequest = $this->mockClientAPI->beforeSend($request);

        $headers = $beforeSendRequest->getHeaders();

        $this->assertSame("Bearer {$apiKey}", $headers[Client::AUTHORIZATION]);
        $this->assertArrayNotHasKey(Client::X_AUTH_KEY, $headers);
        $this->assertArrayNotHasKey(Client::X_AUTH_EMAIL, $headers);
    }

    public function testBeforeSendUsesGlobalKeyHeadersForLegacy45CharHex()
    {
        // Regression test for the corrected 37-45 hex range: a 45-char
        // lowercase hex Global API Key must route to X-Auth-Email + X-Auth-Key.
        // nosemgrep: generic.secrets.security.detected-generic-api-key.detected-generic-api-key
        $apiKey = str_repeat('a', 45);
        $email = 'test@email.com';

        $this->mockDataStore->method('getClientV4APIKey')->willReturn($apiKey);
        $this->mockDataStore->method('getCloudFlareEmail')->willReturn($email);

        $request = new \Cloudflare\APO\API\Request(null, null, null, null);
        $beforeSendRequest = $this->mockClientAPI->beforeSend($request);

        $headers = $beforeSendRequest->getHeaders();

        $this->assertSame($apiKey, $headers[Client::X_AUTH_KEY]);
        $this->assertSame($email, $headers[Client::X_AUTH_EMAIL]);
        $this->assertArrayNotHasKey(Client::AUTHORIZATION, $headers);
    }

    /**
     * @dataProvider providerIsGlobalApiKey
     */
    public function testIsGlobalApiKey($key, $expected, $description)
    {
        $this->assertSame(
            $expected,
            Client::isGlobalApiKey($key),
            $description
        );
    }

    public function providerIsGlobalApiKey()
    {
        return array(
            // New scannable Global API Key format.
            'new cfk_ Global API Key' => array(
                'cfk_' . str_repeat('a', 40) . 'X1y2',
                true,
                'cfk_-prefixed Global API Key should be detected as Global API Key',
            ),
            // New scannable API Token formats.
            'new cfut_ User API Token' => array(
                'cfut_' . str_repeat('a', 40) . 'X1y2',
                false,
                'cfut_-prefixed User API Token should be sent as Bearer',
            ),
            'new cfat_ Account API Token' => array(
                'cfat_' . str_repeat('b', 40) . 'X1y2',
                false,
                'cfat_-prefixed Account API Token should be sent as Bearer',
            ),
            // Pre-2026 Global API Key format: 37-45 lowercase hex characters.
            'pre-2026 Global API Key (37 chars hex)' => array(
                str_repeat('a', 37),
                true,
                '37-char lowercase hex matches legacy Global API Key format',
            ),
            'pre-2026 Global API Key (40 chars hex)' => array(
                str_repeat('a', 40),
                true,
                '40-char lowercase hex matches legacy Global API Key format',
            ),
            'pre-2026 Global API Key (45 chars hex)' => array(
                str_repeat('a', 45),
                true,
                '45-char lowercase hex matches legacy Global API Key format',
            ),
            // Boundary coverage for the 37-45 lowercase hex range.
            'pre-2026 Global API Key (38 chars hex)' => array(
                str_repeat('a', 38),
                true,
                '38-char lowercase hex is inside the legacy range',
            ),
            'pre-2026 Global API Key (41 chars hex)' => array(
                str_repeat('a', 41),
                true,
                '41-char lowercase hex is inside the legacy range',
            ),
            'pre-2026 Global API Key (42 chars hex)' => array(
                str_repeat('a', 42),
                true,
                '42-char lowercase hex is inside the legacy range',
            ),
            'pre-2026 Global API Key (43 chars hex)' => array(
                str_repeat('a', 43),
                true,
                '43-char lowercase hex is inside the legacy range',
            ),
            'pre-2026 Global API Key (44 chars hex)' => array(
                str_repeat('a', 44),
                true,
                '44-char lowercase hex is inside the legacy range',
            ),
            'lowercase hex just below range (36 chars)' => array(
                str_repeat('a', 36),
                false,
                '36-char lowercase hex is below the legacy range',
            ),
            'lowercase hex just above range (46 chars)' => array(
                str_repeat('a', 46),
                false,
                '46-char lowercase hex is above the legacy range',
            ),
            'lowercase hex outside 37-45 range' => array(
                str_repeat('a', 48),
                false,
                'Hex strings outside the 37-45 range are not Global API Keys',
            ),
            // Negative cases inside the legacy length window.
            'mixed-case hex inside legacy length window' => array(
                'A' . str_repeat('a', 39),
                false,
                'Legacy Global API Key is lowercase only; uppercase should not match',
            ),
            'non-hex characters inside legacy length window' => array(
                str_repeat('g', 40),
                false,
                'Non-hex characters in the legacy length window are not Global API Keys',
            ),
            // Pre-2026 API Token format: 40-char alphanumeric, mixed case.
            'pre-2026 API Token (40 char mixed case)' => array(
                'abcdefghijklmnopqrstuvwxyz0123456789ABCD',
                false,
                '40-char alphanumeric (mixed case) is an API Token',
            ),
            // Defensive cases.
            'empty string' => array(
                '',
                false,
                'Empty credential should not be treated as Global API Key',
            ),
            'null input' => array(
                null,
                false,
                'Null credential must be rejected (is_string guard)',
            ),
        );
    }

    public function testClientApiErrorReturnsValidStructure()
    {
        $expectedErrorResponse = array(
            'result' => null,
            'success' => false,
            'errors' => array(
                array(
                    'code' => '',
                    'message' => 'Test Message',
                ),
            ),
            'messages' => array(),
        );
        $errorResponse = $this->mockClientAPI->createAPIError('Test Message');
        $this->assertEquals($errorResponse, $expectedErrorResponse);
    }

    public function testResponseOkReturnsTrueForValidResponse()
    {
        $v4APIResponse = array(
            'success' => true,
        );

        $this->assertTrue($this->mockClientAPI->responseOk($v4APIResponse));
    }

    public function testGetErrorMessageSuccess()
    {
        $errorMessage = 'I am an error message';

        $errorJSON = json_encode(
            array(
                'success' => false,
                'errors' => array(
                    array(
                        'message' => $errorMessage,
                    ),
                ),
            )
        );

        // getErrorMessage() only needs an error exposing getMessage() and
        // getResponse()->getBody(), so a small double is enough.
        $error = new class ($errorJSON) {
            private $body;

            public function __construct($body)
            {
                $this->body = $body;
            }

            public function getMessage()
            {
                return 'Not this message';
            }

            public function getResponse()
            {
                return $this;
            }

            public function getBody()
            {
                return $this->body;
            }
        };

        $this->assertEquals($errorMessage, $this->mockClientAPI->getErrorMessage($error));
    }
}
