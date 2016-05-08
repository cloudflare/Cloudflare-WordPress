<?php

namespace CF\Integration;

interface LoggerInterface
{

    /**
     * @param $logLevel
     * @param $message
     * @return mixed
     */
    public function log($logLevel, $message);

    /**
     * @param $api
     * @param $message
     * @param $isDebug
     * @return mixed
     */
    public function logAPICall($api, $message, $isDebug);
}
