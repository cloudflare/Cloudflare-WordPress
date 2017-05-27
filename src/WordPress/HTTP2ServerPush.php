<?php

// TODO: 
// Get rid of $GLOBALS and use static variables
// Make class functions non-static
// Add debug logs. Need dependency injection of logger to this class
namespace CF\WordPress;

class HTTP2ServerPush
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::initHooks();
        }

        ob_start();
    }

    public static function initHooks()
    {
        self::$initiated = true;

        add_action('wp_head', array('\CF\WordPress\HTTP2ServerPush', 'http2ResourceHints'), 99, 1);
        add_filter('script_loader_src', array('\CF\WordPress\HTTP2ServerPush', 'http2LinkPreloadHeader'), 99, 1);
        add_filter('style_loader_src', array('\CF\WordPress\HTTP2ServerPush', 'http2LinkPreloadHeader'), 99, 1);
    }

    public static function http2LinkPreloadHeader($src)
    {
        if (strpos($src, home_url()) !== false) {
            $preload_src = apply_filters('http2_link_preload_src', $src);

            if (!empty($preload_src)) {
                $newHeader = sprintf(
                    'Link: <%s>; rel=preload; as=%s',
                    esc_url(self::http2LinkUrlToRelativePath($preload_src)),
                    sanitize_html_class(self::http2LinkResourceHintAs(current_filter()))
                );

                // If the current header size is larger than 3KB (3072 bytes)
                // ignore following resources which can be pushed
                // This is a workaround for Cloudflare's 8KB header limit
                // and fastcgi default 4KB header limit
                $headerAsString = implode('  ', headers_list());

                // +2 comes from the last CRLF since it's two bytes
                $headerSize = strlen($headerAsString) + strlen($newHeader) + 2;
                if ($headerSize > 3072) {
                    error_log('Cannot Server Push (header size over 3072 Bytes).');
                    return $src;
                }

                header($newHeader, false);

                $GLOBALS['http2_'.self::http2LinkResourceHintAs(current_filter()).'_srcs'][] = self::http2LinkUrlToRelativePath($preload_src);
            }
        }

        return $src;
    }

    /**
     * Render "resource hints" in the <head> section of the page. These encourage preload/prefetch behavior
     * when HTTP/2 support is lacking.
     */
    public static function http2ResourceHints()
    {
        $resource_types = array('script', 'style');
        array_walk($resource_types, function ($resource_type) {
            if (is_array($GLOBALS["http2_{$resource_type}_srcs"])) {
                array_walk($GLOBALS["http2_{$resource_type}_srcs"], function ($src) use ($resource_type) {
                    printf('<link rel="preload" href="%s" as="%s">', esc_url($src), esc_html($resource_type));
                });
            }
        });
    }

    /**
     * Convert an URL with authority to a relative path.
     *
     * @param string $src URL
     *
     * @return string mixed relative path
     */
    public static function http2LinkUrlToRelativePath($src)
    {
        return '//' === substr($src, 0, 2) ? preg_replace('/^\/\/([^\/]*)\//', '/', $src) : preg_replace('/^http(s)?:\/\/[^\/]*/', '', $src);
    }

    /**
     * Maps a WordPress hook to an "as" parameter in a resource hint.
     *
     * @param string $current_hook pass current_filter()
     *
     * @return string 'style' or 'script'
     */
    public static function http2LinkResourceHintAs($current_hook)
    {
        return 'style_loader_src' === $current_hook ? 'style' : 'script';
    }
}
