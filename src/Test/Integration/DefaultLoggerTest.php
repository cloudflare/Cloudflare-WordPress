<?php

namespace Cloudflare\APO\Integration\Test;

use Cloudflare\APO\Integration\DefaultLogger;

class DefaultLoggerTest extends \PHPUnit\Framework\TestCase
{
    public function testDebugLogOnlyLogsIfDebugIsEnabled()
    {
        $logger = new DefaultLogger(true);
        $returnValue = $logger->debug('');
        $this->assertTrue($returnValue);

        $logger = new DefaultLogger(false);
        $returnValue = $logger->debug('');
        $this->assertNull($returnValue);
    }
}
