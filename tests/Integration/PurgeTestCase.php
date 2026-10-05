<?php

namespace Cloudflare\APO\Tests\Integration;

use Cloudflare\APO\API\Plugin;
use Cloudflare\APO\Tests\Integration\Support\HttpRecorder;

/**
 * Base class for the automatic cache purge tests.
 *
 * Starts every test with Automatic Platform Optimization (APO) switched on and
 * the Cloudflare API calls a purge makes answered: the zone lookup, the active
 * page rules (none), the always_use_https setting (off) and the purge itself.
 */
abstract class PurgeTestCase extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->respondWithZone();
        $this->http->respondTo('GET', $this->zonePath('pagerules'), HttpRecorder::success(array()));
        $this->http->respondTo('GET', $this->zonePath('settings/always_use_https'), HttpRecorder::success(array(
            'id' => 'always_use_https',
            'value' => 'off',
        )));
        $this->http->respondTo('DELETE', $this->zonePath('purge_cache'), HttpRecorder::success(array('id' => self::ZONE_ID)));

        $this->setPluginSetting(Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION, 'on');
    }

    /**
     * URLs sent to Cloudflare in purge-by-URL requests.
     *
     * @return string[]
     */
    protected function purgedUrls()
    {
        $urls = array();
        foreach ($this->http->requestsTo('DELETE', $this->zonePath('purge_cache')) as $request) {
            if (!isset($request['body']['files'])) {
                continue;
            }
            foreach ($request['body']['files'] as $file) {
                $urls[] = is_array($file) ? $file['url'] : $file;
            }
        }

        return $urls;
    }

    /**
     * Number of purge-everything requests sent to Cloudflare.
     *
     * @return int
     */
    protected function purgeEverythingCount()
    {
        return count(array_filter(
            $this->http->requestsTo('DELETE', $this->zonePath('purge_cache')),
            function ($request) {
                return isset($request['body']['purge_everything']) && $request['body']['purge_everything'] === true;
            }
        ));
    }

    /**
     * @param string $path Path below zones/:id/.
     *
     * @return string
     */
    protected function zonePath($path)
    {
        return 'zones/' . self::ZONE_ID . '/' . $path;
    }
}
