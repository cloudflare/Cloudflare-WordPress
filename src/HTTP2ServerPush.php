<?php

// TODO: Get rid of $GLOBALS and use static variables

class HTTP2ServerPush
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }

        ob_start();
    }

    public static function init_hooks()
    {
        self::$initiated = true;

        add_action('wp_head', array('HTTP2ServerPush', 'http2_resource_hints'), 99, 1);
        add_filter('script_loader_src', array('HTTP2ServerPush', 'http2_link_preload_header'), 99, 1);
        add_filter('style_loader_src', array('HTTP2ServerPush', 'http2_link_preload_header'), 99, 1);
    }

    public static function http2_link_preload_header($src)
    {
        if (strpos($src, home_url()) !== false) {
            $preload_src = apply_filters('http2_link_preload_src', $src);

            if (!empty($preload_src)) {
                header(
                    sprintf(
                        'Link: <%s>; rel=preload; as=%s',
                        esc_url(self::http2_link_url_to_relative_path($preload_src)),
                        sanitize_html_class(self::http2_link_resource_hint_as(current_filter()))
                    ),
                    false
                );

                $GLOBALS['http2_'.self::http2_link_resource_hint_as(current_filter()).'_srcs'][] = self::http2_link_url_to_relative_path($preload_src);
            }
        }

        return $src;
    }

    /**
     * Render "resource hints" in the <head> section of the page. These encourage preload/prefetch behavior
     * when HTTP/2 support is lacking.
     */
    public static function http2_resource_hints()
    {
        $resource_types = array('script', 'style');
        array_walk($resource_types, function ($resource_type) {
            array_walk($GLOBALS["http2_{$resource_type}_srcs"], function ($src) use ($resource_type) {
                printf('<link rel="preload"  href="%s" as="%s">', esc_url($src), esc_html($resource_type));
            });
        });
    }

    /**
     * Convert an URL with authority to a relative path.
     *
     * @param string $src URL
     *
     * @return string mixed relative path
     */
    public static function http2_link_url_to_relative_path($src)
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
    public static function http2_link_resource_hint_as($current_hook)
    {
        return 'style_loader_src' === $current_hook ? 'style' : 'script';
    }
}
