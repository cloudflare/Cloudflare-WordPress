<?php
namespace CF\WordPress;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\WordPressClientAPI instead.
 */
class WordPressClientAPI extends \Cloudflare\APO\WordPress\WordPressClientAPI {
    public function __construct(\Cloudflare\APO\Integration\IntegrationInterface $integrationContext) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\WordPressClientAPI", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        parent::__construct($integrationContext);
    }
}
