<?php

namespace Cloudflare\APO\API\Test;

use Cloudflare\APO\API\DefaultHttpClient;
use \Cloudflare\APO\API\Request;

class DefaultHttpClientTest extends \PHPUnit\Framework\TestCase
{
    protected $mockRequest;

    public function setup(): void
    {
        $this->mockRequest = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->defaultHttpClient = new DefaultHttpClient("endpoint");
    }

    public function testCreateRequestOptionsReturnsArray()
    {
        $this->assertIsArray($this->defaultHttpClient->createRequestOptions($this->mockRequest));
    }
}
