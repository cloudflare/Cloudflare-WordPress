<?php

namespace CF\Wordpress {
    function update_option($key, $value)
    {
        return true;
    }

    function get_option($key)
    {
        return $key;
    }
}

namespace CF\Test\WordPress {

    use CF\WordPress\DataStore;
    use CF\API\Plugin;

    class DataStoreTest extends \PHPUnit_Framework_TestCase
    {
        public static $keyValueStore = array();
        protected $dataStore;
        protected $mockLogger;

        public function setup()
        {
            $this->mockLogger = $this->getMockBuilder('CF\Integration\DefaultLogger')
                ->disableOriginalConstructor()
                ->getMock();
            $this->dataStore = new DataStore($this->mockLogger);
        }

        public function testConstructorPopulatesSettings()
        {
            /*
             * mocked get_option($key) defined above returns key
             */
            $pluginSettings = array(
                Plugin::SETTING_IP_REWRITE => Plugin::SETTING_IP_REWRITE,
                Plugin::SETTING_PROTOCOL_REWRITE => Plugin::SETTING_PROTOCOL_REWRITE,
                Plugin::SETTING_DEFAULT_SETTINGS => Plugin::SETTING_DEFAULT_SETTINGS,
                Plugin::PLUGIN_SPECIFIC_CACHE => Plugin::PLUGIN_SPECIFIC_CACHE,
            );
            $this->assertEquals($pluginSettings, $this->dataStore->getPluginSettings());
        }

        public function testGetHostAPIUserUniqueIdReturnsNull()
        {
            $this->assertNull($this->dataStore->getHostAPIUserUniqueId());
        }

        public function testGetClientV4APIKeyReturnsAPIKey()
        {
            $this->assertEquals(DataStore::API_KEY, $this->dataStore->getClientV4APIKey());
        }

        public function testGetHostAPIUserKeyReturnsNull()
        {
            $this->assertNull($this->dataStore->getHostAPIUserKey());
        }

        public function testGetCloudFlareEmailReturnsEmail()
        {
            $this->assertEquals(DataStore::EMAIL, $this->dataStore->getCloudFlareEmail());
        }

        public function testGetPluginSettingReturnsSetting()
        {
            $this->assertEquals(Plugin::SETTING_IP_REWRITE, $this->dataStore->getPluginSetting(Plugin::SETTING_IP_REWRITE));
        }

        public function testGetPluginSettingReturnsFalseForBadSetting()
        {
            $this->assertFalse($this->dataStore->getPluginSetting('nonExistentSetting'));
        }

        public function testSetPluginSettingReturnsFalseForBadSettingName()
        {
            $this->assertFalse($this->dataStore->setPluginSetting('nonExistentSetting', 'value'));
        }
    }
}
