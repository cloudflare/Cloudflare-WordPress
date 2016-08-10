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

    /**
     * Source: https://jtreminio.com/2013/03/unit-testing-tutorial-part-3-testing-protected-private-methods-coverage-reports-and-crap/.
     * 
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function testGetDomainListReturnValue()
    {
        $domainName = 'domainName.com';
        $_SERVER['SERVER_NAME'] = $domainName;
        $domainList = $this->wordpressAPI->getDomainList();

        $this->assertEquals(1, count($domainList));
        $this->assertEquals($domainName, $domainList[0]);
    }

    public function testFormatDomainAllCases()
    {
        $domaincom = 'normal.com';
        $domaincouk = 'normal.co.uk';
        $subdomain = 'sub.normal.com';

        $comDomains = array(
            'normal.com/some/stuff',
            'www.normal.com/some/stuff',
            'http://normal.com/some/stuff',
            'http://www.normal.com/some/stuff',
            'https://normal.com/some/stuff',
            'https://www.normal.com/some/stuff',
            'user:pass@normal.com/some/stuff',
            'http://user:pass@normal.com/some/stuff',
            'https://user:pass@normal.com/some/stuff',
            'normal.com/some/stuff?id=http:://thisshoudlnthappen.com',
        );

        $coukDomains = array(
            'https://normal.co.uk/some/stuff',
            'https://www.normal.co.uk/some/stuff',
            'http://user:pass@normal.co.uk/some/stuff',
            'https://user:pass@normal.co.uk/some/stuff',
        );

        $subDomains = array(
            'sub.normal.com/some/stuff',
            'www.sub.normal.com/some/stuff',
            'http://sub.normal.com/some/stuff',
            'https://sub.normal.com/some/stuff',
            'http://www.sub.normal.com/some/stuff',
        );

        foreach ($comDomains as $domain) {
            $this->assertEquals($domaincom, $this->invokeMethod($this->wordpressAPI, 'formatDomain', array($domain)));
        }

        foreach ($coukDomains as $domain) {
            $this->assertEquals($domaincouk, $this->invokeMethod($this->wordpressAPI, 'formatDomain', array($domain)));
        }

        foreach ($subDomains as $domain) {
            $this->assertEquals($subdomain, $this->invokeMethod($this->wordpressAPI, 'formatDomain', array($domain)));
        }
    }
}
