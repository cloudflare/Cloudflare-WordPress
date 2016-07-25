<?php

namespace CF\Test\WordPress;

use CF\WordPress\WordPressAPI;

class WordPressAPITest extends \PHPUnit_Framework_TestCase
{
    private $mockConfig;
    private $mockDataStore;
    private $mockLogger;

    public function setup()
    {
        $this->mockConfig = $this->getMockBuilder('CF\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('CF\WordPress\DataStore')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('CF\Integration\DefaultLogger')
            ->disableOriginalConstructor()
            ->getMock();
        $this->wordpressAPI = new WordPressAPI($this->mockDataStore);
    }

    public function testGetDomainList()
    {
        $_SERVER['SERVER_NAME'] = 'domainName.com';
        $domain_list = $this->wordpressAPI->getDomainList();

        $this->assertEquals(1, count($domain_list));
    }
}
