<?php
namespace CF\Integration;

use \Psr\Log\AbstractLogger;
use \Psr\Log\LogLevel;

class DefaultLogger extends AbstractLogger implements LoggerInterface
{
    private $debug;

    const PREFIX = "[CloudFlare]";

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
     *
     * @return null
     */
    public function log($level, $message, array $context = array()) {
        return error_log(self::PREFIX . " " . strtoupper($level) . ": " . $message . " " .
            (!empty($context) ? print_r($context,true) : ""));
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function debug($message, array $context = array())
    {
        if($this->debug) {
           return $this->log(LogLevel::DEBUG, $message, $context);
        }
    }

    public function logAPICall($api, $message, $is_debug)
    {

        $log_level = "error";
        if ($is_debug) {
            $log_level = "debug";
        }

        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $this->$log_level("[" . $api . "] " . $message);
    }
}
