<?php

/**
 * PHPUnit bootstrap for the unit suite, run against the source tree.
 *
 * Loads the source vendor autoloader and registers minimal stubs for
 * WordPress core classes used by the plugin (see tests/Fixtures/).
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/Fixtures/WordPressClassStubs.php';
