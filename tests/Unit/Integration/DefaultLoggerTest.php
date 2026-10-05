<?php

namespace Cloudflare\APO\Tests\Unit\Integration;

use Cloudflare\APO\Integration\DefaultLogger;

class DefaultLoggerTest extends \PHPUnit\Framework\TestCase
{
    private $logFile;
    private $previousErrorLog;

    protected function setUp(): void
    {
        // DefaultLogger writes through error_log(); send that to a temp file.
        $this->logFile = tempnam(sys_get_temp_dir(), 'cloudflare-log');
        $this->previousErrorLog = ini_set('error_log', $this->logFile);
    }

    protected function tearDown(): void
    {
        ini_set('error_log', (string) $this->previousErrorLog);
        unlink($this->logFile);
    }

    public function testDebugLogOnlyLogsIfDebugIsEnabled()
    {
        (new DefaultLogger(false))->debug('hidden message');
        (new DefaultLogger(true))->debug('visible message');

        $log = (string) file_get_contents($this->logFile);
        $this->assertStringContainsString('[Cloudflare] DEBUG: visible message', $log);
        $this->assertStringNotContainsString('hidden message', $log);
    }
}
