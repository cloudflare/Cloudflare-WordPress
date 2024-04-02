<?php

namespace CF\Integration;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Stringable;

class DefaultLogger extends AbstractLogger implements LoggerInterface
{
    private $debug;

    const PREFIX = '[Cloudflare]';

    /**
     * @param bool|false $debug
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param Stringable|string $message
     * @param array  $context
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        error_log(self::PREFIX.' '.strtoupper($level).': '.$message.' '.
            (!empty($context) ? print_r($context, true) : ''));
    }

    /**
     * Detailed debug information.
     *
     * @param Stringable|string $message
     * @param array  $context
     */
    public function debug(Stringable|string $message, array $context = []): void
    {
        if ($this->debug) {
            $this->log(LogLevel::DEBUG, $message, $context);
        }
    }
}
