<?php

namespace CF\Integration\Test;

use CF\Integration\DefaultLogger;

class DefaultLoggerTest extends \PHPUnit\Framework\TestCase
{
    public function testDebugLogOnlyLogsIfDebugIsEnabled()
    {
        $logger = new DefaultLogger(true);
        $returnValue = $logger->debug('');
        $this->assertNull($returnValue);

        $logger = new DefaultLogger(false);
        $returnValue = $logger->debug('');
        $this->assertNull($returnValue);
    }
}
