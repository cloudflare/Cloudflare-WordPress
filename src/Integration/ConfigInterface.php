<?php

namespace Cloudflare\APO\Integration;

interface ConfigInterface
{
    /**
     * @param $key
     *
     * @return mixed
     */
    public function getValue($key);
}
