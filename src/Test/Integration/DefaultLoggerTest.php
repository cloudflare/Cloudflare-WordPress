<?php

namespace CF\Integration\Test;

use CF\Integration\DefaultLogger;

class DefaultLoggerTest extends \PHPUnit\Framework\TestCase
{
    public function testDebugLogOnlyLogsIfDebugIsEnabled()
    {
        // Capture error log output
        $errorLogBackup = ini_get('log_errors');
        $errorLogFileBackup = ini_get('error_log');

        // Enable error logging to a temporary file
        ini_set('log_errors', 1);
        $tempFile = tempnam(sys_get_temp_dir(), 'test_log');
        ini_set('error_log', $tempFile);

        try {
            // Test with debug enabled
            $logger = new DefaultLogger(true);
            $logger->debug('test message');

            $logContent = file_get_contents($tempFile);
            $this->assertStringContainsString('[Cloudflare] DEBUG: test message', $logContent);

            // Clear the log file
            file_put_contents($tempFile, '');

            // Test with debug disabled
            $logger = new DefaultLogger(false);
            $logger->debug('test message 2');

            $logContent = file_get_contents($tempFile);
            $this->assertEmpty($logContent);
        } finally {
            // Restore original settings
            ini_set('log_errors', $errorLogBackup);
            ini_set('error_log', $errorLogFileBackup);
            unlink($tempFile);
        }
    }
}
