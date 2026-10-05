<?php

/**
 * PHPStan bootstrap.
 *
 * Defines the constants that cloudflare.php sets at runtime so static analysis
 * can resolve CLOUDFLARE_* references in src/. Loaded by PHPStan only; it has
 * no effect at runtime. The types are pinned under `dynamicConstantNames` in
 * phpstan.neon.dist, so the values below are never treated as fixed.
 */

if (! defined('CLOUDFLARE_MIN_PHP_VERSION')) {
    define('CLOUDFLARE_MIN_PHP_VERSION', '7.4');
}
if (! defined('CLOUDFLARE_MIN_WP_VERSION')) {
    define('CLOUDFLARE_MIN_WP_VERSION', '6.7');
}
if (! defined('CLOUDFLARE_PLUGIN_DIR')) {
    define('CLOUDFLARE_PLUGIN_DIR', dirname(__DIR__) . '/');
}

// WordPress defines its cookie names at runtime in wp_cookie_constants().
if (! defined('LOGGED_IN_COOKIE')) {
    define('LOGGED_IN_COOKIE', 'wordpress_logged_in_');
}
if (! defined('AUTH_COOKIE')) {
    define('AUTH_COOKIE', 'wordpress_');
}
