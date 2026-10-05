<?php

/**
 * PHPUnit bootstrap for the integration suite.
 *
 * Loads a real WordPress install with the plugin active, so tests can assert
 * against actual hooks, options and HTTP calls. Runs inside the wp-env
 * `cli` container, where WordPress lives at WP_ABSPATH (default /var/www/html)
 * and the plugin is mapped to wp-content/plugins/cloudflare.
 */

$cloudflareAbspath = getenv('WP_ABSPATH');
if (! is_string($cloudflareAbspath) || $cloudflareAbspath === '') {
    $cloudflareAbspath = '/var/www/html';
}
$cloudflareWpLoad = rtrim($cloudflareAbspath, '/') . '/wp-load.php';

if (! is_readable($cloudflareWpLoad)) {
    fwrite(
        STDERR,
        "Integration bootstrap could not find wp-load.php at {$cloudflareWpLoad}.\n"
        . "Run this suite inside wp-env: `npm run env:test:start` then `npm run test:integration`.\n"
    );
    exit(1);
}

// Load WordPress the way admin-ajax.php does, so the plugin registers the
// admin and AJAX hooks it only adds when is_admin() is true.
define('WP_ADMIN', true);

require_once $cloudflareWpLoad;

// admin-ajax.php and wp-admin pages also load the administration APIs, such as
// add_options_page() and get_plugin_data(). WordPress 6.8 and later load some
// of them while booting; older versions do not.
require_once ABSPATH . 'wp-admin/includes/admin.php';

// The environments set WP_DEBUG_DISPLAY to false, which would hide a fatal
// error in a test. Show PHP errors on stderr instead.
ini_set('display_errors', 'stderr');

if (! defined('CLOUDFLARE_PLUGIN_DIR')) {
    fwrite(
        STDERR,
        "The Cloudflare plugin is not active in this WordPress install.\n"
        . "If wp-content/plugins/cloudflare is empty inside the container, its mapped folder was deleted and\n"
        . "recreated after the environment started (for example by a branch switch). Restart the environment\n"
        . "with its stop and start scripts, for example `npm run env:test:stop` then `npm run env:test:start`.\n"
    );
    exit(1);
}

require_once dirname(__DIR__) . '/vendor/autoload.php';
