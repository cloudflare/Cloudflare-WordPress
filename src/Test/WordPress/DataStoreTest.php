<?php

namespace CF\Test\WordPress {

    use CF\WordPress\DataStore;
    use CF\API\Plugin;
    use phpmock\phpunit\PHPMock;

    class DataStoreTest extends \PHPUnit_Framework_TestCase
    {
        use PHPMock;

        protected $dataStore;
        protected $mockDeleteOption;
        protected $mockGetOption;
        protected $mockLogger;
        protected $mockUpdateOption;

        public function setup()
        {
            $this->mockLogger = $this->getMockBuilder('CF\Integration\DefaultLogger')
                ->disableOriginalConstructor()
                ->getMock();
            $this->mockDeleteOption = $this->getFunctionMock('CF\WordPress', 'delete_option');
            $this->mockGetOption = $this->getFunctionMock('CF\WordPress', 'get_option');
            $this->mockUpdateOption = $this->getFunctionMock('CF\WordPress', 'update_option');
            $this->dataStore = new DataStore($this->mockLogger);
        }

        public function testCreateUserDataStoreSavesAPIKeyAndEmail()
        {
            $apiKey = 'apiKey';
            $email = 'email';

            $this->mockUpdateOption->expects($this->any())->willReturn(true);

            $this->assertTrue($this->dataStore->createUserDataStore($apiKey, $email, null, null));
        }

        public function testGetHostAPIUserUniqueIdReturnsNull()
        {
            $this->assertNull($this->dataStore->getHostAPIUserUniqueId());
        }

        public function testGetClientV4APIKeyReturnsCorrectValue()
        {
            $apiKey = 'apiKey';
            $this->mockGetOption->expects($this->once())->with(DataStore::API_KEY)->willReturn($apiKey);
            $this->assertEquals($apiKey, $this->dataStore->getClientV4APIKey());
        }

        public function testGetHostAPIUserKeyReturnsNull()
        {
            $this->assertNull($this->dataStore->getHostAPIUserKey());
        }

        public function testGetDomainNameCacheReturnsDomainIfItExistsInCache()
        {
            $cachedDomain = 'cachedDomain';
            $this->mockGetOption->expects($this->once())->with(DataStore::CACHED_DOMAIN_NAME)->willReturn($cachedDomain);
            $this->assertEquals($cachedDomain, $this->dataStore->getDomainNameCache());
        }

        public function testSetDomainNameCacheSetsDomain()
        {
            $domain = 'domain.com';
            $this->mockUpdateOption->expects($this->once())->willReturn(true);
            $this->assertTrue($this->dataStore->setDomainNameCache($domain));
        }

        public function testGetCloudFlareEmailReturnsCorrectValue()
        {
            $email = 'email';
            $this->mockGetOption->expects($this->once())->with(DataStore::EMAIL)->willReturn($email);
            $this->assertEquals($email, $this->dataStore->getCloudFlareEmail());
        }

        public function testGetPluginSettingCallsGetOption()
        {
            $this->mockGetOption->expects($this->once());
            $this->dataStore->getPluginSetting(Plugin::SETTING_DEFAULT_SETTINGS);
        }

        public function testGetCallsEscSqlAndGetOption()
        {
            $this->mockGetOption->expects($this->once());
            $this->dataStore->get('key');
        }

        public function testSetCallsEscSqlAndUpdateOption()
        {
            $this->mockUpdateOption->expects($this->once());
            $this->dataStore->set('key', 'value');
        }

        public function testClearCallsSqlAndDeleteToption()
        {
            $this->mockDeleteOption->expects($this->once());
            $this->dataStore->clear('key');
        }

        public function testClearDataStoreCallsExactNumberOfSqlCalls()
        {
            $pluginSettings = \CF\API\Plugin::getPluginSettingsKeys();
            $numberOfDataStoreKeys = 3;
            $totalNumberOfRowToClear = count($pluginSettings) + $numberOfDataStoreKeys;

            $this->mockDeleteOption->expects($this->exactly($totalNumberOfRowToClear));
            $this->dataStore->clearDataStore();
        }
    }
}
