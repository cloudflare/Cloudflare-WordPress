<?php

/**
 * PHPUnit bootstrap for running the unit suite against the build artifact.
 *
 * Loads two autoloaders:
 *   1. Source vendor: provides PHPUnit, php-mock, the test classes and other
 *      dev dependencies.
 *   2. Build vendor: provides the scoped plugin classes (Cloudflare\APO\*) and
 *      prefixed vendor dependencies (Cloudflare\APO\Vendor\*).
 *
 * Load order matters: the build autoloader is loaded second so its optimized
 * classmap takes priority over the source PSR-4 rules for Cloudflare\APO\* classes.
 */

$sourceAutoloader = dirname(__DIR__) . '/vendor/autoload.php';
$buildAutoloader  = dirname(__DIR__) . '/build/cloudflare/vendor/autoload.php';

if (! file_exists($sourceAutoloader)) {
    fwrite(STDERR, "Source vendor/autoload.php not found. Run: composer install\n");
    exit(1);
}

if (! file_exists($buildAutoloader)) {
    fwrite(STDERR, "Build vendor/autoload.php not found. Run: composer build\n");
    exit(1);
}

// Dev tools: PHPUnit, php-mock, test classes, etc.
require_once $sourceAutoloader;

// Scoped plugin code: classmap overrides source PSR-4 for Cloudflare\APO\* classes.
require_once $buildAutoloader;

// Stubs for WordPress core classes referenced by the plugin (e.g. WP_Post).
require_once __DIR__ . '/Fixtures/WordPressClassStubs.php';
