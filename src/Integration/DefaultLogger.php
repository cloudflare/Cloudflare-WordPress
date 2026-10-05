<?php

namespace Cloudflare\APO\Integration;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

class DefaultLogger extends AbstractLogger implements LoggerInterface
{
    private $debug;

    public const PREFIX = '[Cloudflare]';

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
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = array())
    {
        error_log(self::PREFIX . ' ' . strtoupper($level) . ': ' . $message . ' ' .
            (!empty($context) ? print_r($context, true) : ''));
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     */
    public function debug($message, array $context = array())
    {
        if ($this->debug) {
            $this->log(LogLevel::DEBUG, $message, $context);
        }
    }
}
