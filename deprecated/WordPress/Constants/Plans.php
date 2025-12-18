<?php
namespace CF\WordPress\Constants;

/**
 * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\Constants\Plans instead.
 */
class Plans extends \Cloudflare\APO\WordPress\Constants\Plans {
    /**
     * @deprecated 4.13.0 Use \Cloudflare\APO\WordPress\Constants\Plans::planNeedsUpgrade() instead.
     */
    public static function planNeedsUpgrade($currentPlan, $minimumPlan) {
        _deprecated_file( __FILE__, '4.13.0', "Cloudflare\APO\WordPress\Constants\Plans::planNeedsUpgrade", 'CF\WordPress\Constants namespace is deprecated, use Cloudflare\APO\WordPress\Constants instead.' );
        return parent::planNeedsUpgrade($currentPlan, $minimumPlan);
    }
}
