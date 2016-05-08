<?php
namespace CF\Integration;

class DefaultLogger implements LoggerInterface
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
     * @param $logLevel
     * @param $message
     * @return bool
     */
    public function log($logLevel, $message)
    {
        return error_log(self::PREFIX . " " . strtoupper($logLevel) . " " . $message);
    }

    /**
     * @param $message
     */
    public function info($message)
    {
        $this->log("INFO", $message);
    }

    /**
     * @param $message
     */
    public function debug($message)
    {
        if ($this->debug) {
            $this->log("DEBUG", $message);
        }
    }

    /**
     * @param $message
     */
    public function warn($message)
    {
        $this->log("WARN", $message);
    }

    /**
     * @param $message
     */
    public function error($message)
    {
        $this->log("ERROR", $message);
    }

    public function logAPICall($api, $message, $is_debug)
    {

        $log_level = "ERROR";
        if ($is_debug) {
            $log_level = "DEBUG";
        }

        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $this->$log_level("[" . $api . "] " . $message);
    }
}
