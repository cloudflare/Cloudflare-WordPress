<?php

namespace PHPUnit\Framework;

/**
 * Compatibility class to work with PHPUnit 7
 *
 * @internal
 */
abstract class BaseTestListener implements TestListener
{
    use TestListenerDefaultImplementation;
}
