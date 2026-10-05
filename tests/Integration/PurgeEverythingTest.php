<?php

namespace Cloudflare\APO\Tests\Integration;

use Cloudflare\APO\API\Plugin;

/**
 * Site-wide changes purge the whole zone.
 */
class PurgeEverythingTest extends PurgeTestCase
{
    public function testSwitchingThemePurgesEverything()
    {
        $originalTheme = get_stylesheet();
        $otherTheme = $this->anotherInstalledTheme($originalTheme);

        try {
            switch_theme($otherTheme);
        } finally {
            switch_theme($originalTheme);
        }

        $this->assertGreaterThanOrEqual(1, $this->purgeEverythingCount());
        $this->assertSame(array(), $this->purgedUrls());
    }

    public function testAutoptimizeCachePurgePurgesEverything()
    {
        do_action('autoptimize_action_cachepurged');

        $this->assertSame(1, $this->purgeEverythingCount());
    }

    public function testNothingIsPurgedWhenApoAndPluginSpecificCacheAreOff()
    {
        $this->setPluginSetting(Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION, 'off');

        do_action('autoptimize_action_cachepurged');

        $this->assertSame(array(), $this->http->requests());
    }

    /**
     * @param string $exclude Stylesheet to skip.
     *
     * @return string
     */
    private function anotherInstalledTheme($exclude)
    {
        foreach (array_keys(wp_get_themes()) as $stylesheet) {
            if ($stylesheet !== $exclude) {
                return $stylesheet;
            }
        }

        $this->markTestSkipped('Switching themes needs a second installed theme.');
    }
}
