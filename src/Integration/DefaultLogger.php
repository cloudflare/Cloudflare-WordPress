<?php

namespace CF\Integration;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

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
     * @param string|\Stringable $message
     * @param array  $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        error_log(self::PREFIX.' '.strtoupper($level).': '.$message.' '.
            (!empty($context) ? print_r($context, true) : ''));
    }

    /**
     * Detailed debug information.
     *
     * @param string|\Stringable $message
     * @param array  $context
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        if ($this->debug) {
            $this->log(LogLevel::DEBUG, $message, $context);
        }
    }
}
