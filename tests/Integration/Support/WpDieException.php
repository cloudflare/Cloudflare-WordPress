<?php

namespace Cloudflare\APO\Tests\Integration\Support;

/**
 * Thrown instead of exiting when code under test calls wp_die(). The message
 * holds what wp_die() received, for example the proxy's JSON response.
 */
final class WpDieException extends \RuntimeException
{
}
