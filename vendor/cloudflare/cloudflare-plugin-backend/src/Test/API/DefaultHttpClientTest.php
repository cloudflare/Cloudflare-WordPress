<?php

namespace CF\API\Test;

use GuzzleHttp;
use CF\API\DefaultHttpClient;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use \CF\API\Request;

class DefaultHttpClientTest extends \PHPUnit_Framework_TestCase
{
    protected $mockClient;
    protected $mockGuzzleRequest;
    protected $mockGuzzleResponse;
    protected $mockRequest;

    public function setup()
    {
        $this->mockClient = $this->getMockBuilder(GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockGuzzleRequest = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockClient->method('createRequest')->willReturn($this->mockGuzzleRequest);

        $this->mockGuzzleResponse = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockClient->method('send')->willReturn($this->mockGuzzleResponse);

        $this->mockRequest = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->defaultHttpClient = new DefaultHttpClient("endpoint");
        $this->defaultHttpClient->setClient($this->mockClient);
    }

    public function testSendRequestCallsGuzzleSend()
    {
          $this->mockGuzzleResponse->method('json')->willReturn(true);
          $this->mockClient->expects($this->once())->method('send');

          $this->defaultHttpClient->send($this->mockRequest);
    }

    public function testCreateGuzzleRequestReturnsGuzzleRequest()
    {
        $this->assertInstanceOf(RequestInterface::class, $this->defaultHttpClient->createGuzzleRequest($this->mockRequest));
    }
}
