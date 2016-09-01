<?php

namespace CF\Wordpress {
	/*
	 * https://codex.wordpress.org/Function_Reference/update_option
	 */
    function update_option($key, $value)
    {
        return true;
    }

	/*
	 * https://developer.wordpress.org/reference/functions/get_option/
	 */
    function get_option($key)
    {
        return $key;
    }

	/*
	 * https://codex.wordpress.org/Function_Reference/esc_sql
	 */
	function esc_sql($string) {
		return $string;
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

        public function testGetPluginSettingReturnsSettingName()
        {
            $setting = Plugin::SETTING_IP_REWRITE;
            $result = $this->dataStore->getPluginSetting($setting);

            $this->assertEquals($setting, $result);
        }

        public function testGetPluginSettingReturnsFalseForBadSetting()
        {
            $setting = 'Bad Setting';
            $result = $this->dataStore->getPluginSetting($setting);

            $this->assertFalse($result);
        }

        public function testSetPluginSettingReturnsTrueForCorrectSettingName()
        {
            $setting = Plugin::SETTING_IP_REWRITE;
            $value = 'justAValue';
            $result = $this->dataStore->setPluginSetting($setting, $value);

            $this->assertTrue($result);
        }

        public function testSetPluginSettingReturnsFalseForBadSettingName()
        {
            $setting = 'justASetting';
            $value = 'justAValue';
            $result = $this->dataStore->setPluginSetting($setting, $value);

            $this->assertFalse($result);
        }

        public function testGetCallsGetOption()
        {
            $option = 'option';
            $response = $this->dataStore->get($option);
            $this->assertEquals($option, $response);
        }

        public function testSetCallsUpdateOption()
        {
            $this->assertTrue($this->dataStore->set('key', 'value'));
        }
    }
}
