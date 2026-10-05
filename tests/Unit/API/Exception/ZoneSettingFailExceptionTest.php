<?php

namespace Cloudflare\APO\Tests\Unit\API\Exception;

use Cloudflare\APO\API\Exception\ZoneSettingFailException;

class ZoneSettingFailExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaultMessageIsPreservedForBackwardsCompatibility()
    {
        $exception = new ZoneSettingFailException();

        $this->assertSame(
            'Oops, something went wrong, please try again in a few minutes',
            $exception->getMessage()
        );
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCustomMessageOverridesDefault()
    {
        $exception = new ZoneSettingFailException('Custom failure message');

        $this->assertSame('Custom failure message', $exception->getMessage());
    }

    public function testCodeAndPreviousArePassedThroughToParent()
    {
        $previous = new \RuntimeException('underlying cause');
        $exception = new ZoneSettingFailException('boom', 42, $previous);

        $this->assertSame('boom', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testIsACloudFlareException()
    {
        $this->assertContains(
            'Cloudflare\APO\API\Exception\CloudFlareException',
            class_parents(ZoneSettingFailException::class)
        );
    }
}
