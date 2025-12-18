<?php
namespace CF\WordPress;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\HTTP2ServerPush instead.
 */
class HTTP2ServerPush extends \Cloudflare\APO\WordPress\HTTP2ServerPush {
    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\HTTP2ServerPush::init() instead.
     */
    public static function init() {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\HTTP2ServerPush::init", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        return parent::init();
    }

    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\HTTP2ServerPush::initHooks() instead.
     */
    public static function initHooks() {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\HTTP2ServerPush::initHooks", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        return parent::initHooks();
    }

    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\HTTP2ServerPush::http2LinkPreloadHeader() instead.
     */
    public static function http2LinkPreloadHeader($src) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\HTTP2ServerPush::http2LinkPreloadHeader", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        return parent::http2LinkPreloadHeader($src);
    }

    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\HTTP2ServerPush::http2ResourceHints() instead.
     */
    public static function http2ResourceHints() {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\HTTP2ServerPush::http2ResourceHints", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        return parent::http2ResourceHints();
    }

    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\HTTP2ServerPush::http2LinkUrlToRelativePath() instead.
     */
    public static function http2LinkUrlToRelativePath($src) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\HTTP2ServerPush::http2LinkUrlToRelativePath", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        return parent::http2LinkUrlToRelativePath($src);
    }

    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\HTTP2ServerPush::http2LinkResourceHintAs() instead.
     */
    public static function http2LinkResourceHintAs($current_hook, $src) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\HTTP2ServerPush::http2LinkResourceHintAs", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        return parent::http2LinkResourceHintAs($current_hook, $src);
    }
}
