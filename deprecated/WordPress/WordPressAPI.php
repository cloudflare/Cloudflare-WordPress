<?php
namespace CF\WordPress;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\WordPressAPI instead.
 */
class WordPressAPI extends \Cloudflare\APO\WordPress\WordPressAPI {
    public function __construct(\Cloudflare\APO\Integration\DataStoreInterface $dataStore) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\WordPressAPI", 'CF\WordPress namespace is deprecated, use Cloudflare\APO\WordPress instead.' );
        parent::__construct($dataStore);
    }
}
