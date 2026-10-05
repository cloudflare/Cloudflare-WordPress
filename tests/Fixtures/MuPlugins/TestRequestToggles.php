<?php

/**
 * Plugin Name: Cloudflare integration test toggles
 * Description: Test environments only (mapped by .wp-env.test.json). Lets the
 * integration suite switch on features configured through wp-config.php
 * constants for a single request. Must-use plugins load before regular
 * plugins, so the constants exist when cloudflare.loader.php checks them.
 */

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test fixture; never shipped.
if (isset($_GET['cloudflare_test_http2_push']) && !defined('CLOUDFLARE_HTTP2_SERVER_PUSH_ACTIVE')) {
    define('CLOUDFLARE_HTTP2_SERVER_PUSH_ACTIVE', true);
}
