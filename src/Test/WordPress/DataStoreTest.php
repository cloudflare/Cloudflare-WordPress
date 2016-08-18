<?php

namespace CF\Test\WordPress;

use CF\WordPress\DataStore;

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

    public function testGetHostAPIUserKeyReturnsNull()
    {
        $this->assertNull($this->dataStore->getHostAPIUserKey());
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
