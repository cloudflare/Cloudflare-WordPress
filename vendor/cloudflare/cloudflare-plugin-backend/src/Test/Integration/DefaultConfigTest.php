<?php

namespace CF\Integration\Test;

use CF\Integration\DefaultConfig;

class DefaultConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testGetValueReturnsCorrectValue()
    {
        $key = 'key';
        $value = 'value';
        $config = new DefaultConfig(json_encode(array($key => $value)));
        $this->assertEquals($value, $config->getValue($key));
    }
}
