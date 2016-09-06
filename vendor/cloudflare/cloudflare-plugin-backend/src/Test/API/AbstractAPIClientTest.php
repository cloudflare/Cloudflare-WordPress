<?php

namespace CF\API\Test;

class AbstractAPIClientTest extends \PHPUnit_Framework_TestCase
{
    protected $mockAbstractAPIClient;
    protected $mockRequest;

    public function setup()
    {
        $this->mockRequest = $this->getMockBuilder('CF\API\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockAbstractAPIClient = $this->getMockBuilder('CF\API\AbstractAPIClient')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function testGetPathReturnsPath()
    {
        $endpoint = 'http://api.cloudflare.com/client/v4';
        $path = '/zones';
        $this->mockRequest->method('getUrl')->willReturn($endpoint.$path);
        $this->mockAbstractAPIClient->method('getEndpoint')->willReturn($endpoint);
        $this->assertEquals($path, $this->mockAbstractAPIClient->getPath($this->mockRequest));
    }

    public function testShouldRouteRequestReturnsTrueForValidRequest()
    {
        $endpoint = 'http://api.cloudflare.com/client/v4';
        $url = $endpoint.'/zones';
        $this->mockRequest->method('getUrl')->willReturn($url);
        $this->mockAbstractAPIClient->method('getEndpoint')->willReturn($endpoint);
        $this->assertTrue($this->mockAbstractAPIClient->shouldRouteRequest($this->mockRequest));
    }

    public function testShouldRouteRequestReturnsFalseForInvalidRequest()
    {
        $this->mockRequest->method('getUrl')->willReturn('http://api.cloudflare.com/client/v4/zones');
        $this->mockAbstractAPIClient->method('getEndpoint')->willReturn('https://api.cloudflare.com/host-gw.html');
        $this->assertFalse($this->mockAbstractAPIClient->shouldRouteRequest($this->mockRequest));
    }
}
