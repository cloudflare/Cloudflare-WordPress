<?php

namespace Cloudflare\APO\Tests\Unit\API;

use Cloudflare\APO\API\DefaultHttpClient;
use Cloudflare\APO\API\Request;

class DefaultHttpClientTest extends \PHPUnit\Framework\TestCase
{
    protected $defaultHttpClient;
    protected $mockRequest;

    public function setUp(): void
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
