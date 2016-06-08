<?php

namespace CF\Integration;

interface LoggerInterface extends \Psr\Log\LoggerInterface
{
    /**
     * @param $api
     * @param $message
     * @param $isDebug
     * @return mixed
     */
    public function logAPICall($api, $message, $isDebug);
}
