<?php

namespace Cloudflare\APO\Tests\Integration;

use Cloudflare\APO\API\Plugin;
use Cloudflare\APO\Tests\Integration\Support\HttpRecorder;

/**
 * Publishing, updating, unpublishing and removing content purges its URLs.
 */
class PurgeOnPostChangeTest extends PurgeTestCase
{
    public function testPublishingAPostPurgesItsUrls()
    {
        $postId = $this->createPost(array('post_status' => 'publish'));

        $purged = $this->purgedUrls();
        $this->assertContains(get_permalink($postId), $purged);
        $this->assertContains(home_url('/'), $purged);
    }

    public function testSavingADraftDoesNotPurge()
    {
        $this->createPost(array('post_status' => 'draft'));

        $this->assertSame(array(), $this->http->requests());
    }

    public function testUpdatingAPublishedPostPurgesItsUrls()
    {
        $postId = $this->createPost(array('post_status' => 'publish'));
        $this->http->clearRequests();

        wp_update_post(array('ID' => $postId, 'post_content' => 'Updated content'));

        $this->assertContains(get_permalink($postId), $this->purgedUrls());
    }

    public function testUnpublishingAPostPurgesItsUrls()
    {
        $postId = $this->createPost(array('post_status' => 'publish', 'post_name' => 'unpublish-me'));
        $publishedUrl = get_permalink($postId);
        $this->http->clearRequests();

        wp_update_post(array('ID' => $postId, 'post_status' => 'draft'));

        $this->assertContains(home_url('/'), $this->purgedUrls());
        $this->assertNotContains($publishedUrl, $this->purgedUrls(), 'Drafts have no public URL to purge.');
    }

    public function testTrashingAPublishedPostPurgesItsOriginalUrl()
    {
        $postId = $this->createPost(array('post_status' => 'publish', 'post_name' => 'trash-me'));
        $publishedUrl = get_permalink($postId);
        $this->http->clearRequests();

        wp_trash_post($postId);

        $this->assertContains($publishedUrl, $this->purgedUrls());
    }

    public function testTrashingASecondPostWithTheSameSlugPurgesItsOriginalUrl()
    {
        wp_trash_post($this->createPost(array('post_status' => 'publish', 'post_name' => 'same-slug')));
        $postId = $this->createPost(array('post_status' => 'publish', 'post_name' => 'same-slug'));
        $publishedUrl = get_permalink($postId);
        $this->http->clearRequests();

        wp_trash_post($postId);

        // WordPress renames it to same-slug__trashed-2, as same-slug__trashed is taken.
        $this->assertSame('same-slug__trashed-2', get_post_field('post_name', $postId));
        $this->assertContains($publishedUrl, $this->purgedUrls());
    }

    public function testPurgeByUrlFilterCanAddUrlsOnTheSiteDomainOnly()
    {
        $extraUrl = home_url('/landing-page/');
        $filter = function ($urls) use ($extraUrl) {
            $urls[] = $extraUrl;
            $urls[] = 'https://another-site.example/';

            return $urls;
        };
        add_filter('cloudflare_purge_by_url', $filter);

        try {
            $this->createPost(array('post_status' => 'publish'));
        } finally {
            remove_filter('cloudflare_purge_by_url', $filter);
        }

        $this->assertContains($extraUrl, $this->purgedUrls());
        $this->assertNotContains('https://another-site.example/', $this->purgedUrls());
    }

    public function testPluginSpecificCacheWithCacheEverythingPageRulePurgesPages()
    {
        delete_option(Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION);
        $this->setPluginSetting(Plugin::SETTING_PLUGIN_SPECIFIC_CACHE, 'on');
        $this->http->respondTo('GET', $this->zonePath('pagerules'), HttpRecorder::success(array(
            array('actions' => array(array('id' => 'cache_level', 'value' => 'cache_everything'))),
        )));

        $postId = $this->createPost(array('post_status' => 'publish'));

        $this->assertContains(get_permalink($postId), $this->purgedUrls());
    }

    public function testPluginSpecificCacheWithoutCacheEverythingPurgesOnlyStaticFiles()
    {
        delete_option(Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION);
        $this->setPluginSetting(Plugin::SETTING_PLUGIN_SPECIFIC_CACHE, 'on');
        $imageUrl = home_url('/wp-content/uploads/cover.jpg');
        $filter = function ($urls) use ($imageUrl) {
            $urls[] = $imageUrl;

            return $urls;
        };
        add_filter('cloudflare_purge_by_url', $filter);

        try {
            $postId = $this->createPost(array('post_status' => 'publish'));
        } finally {
            remove_filter('cloudflare_purge_by_url', $filter);
        }

        // Without APO or a Cache Everything page rule Cloudflare does not
        // cache HTML, so only the static file is purged.
        $this->assertSame(array($imageUrl), $this->purgedUrls());
        $this->assertNotContains(get_permalink($postId), $this->purgedUrls());
    }

    public function testNothingIsPurgedWhenApoAndPluginSpecificCacheAreOff()
    {
        $this->setPluginSetting(Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION, 'off');

        $this->createPost(array('post_status' => 'publish'));

        $this->assertSame(array(), $this->http->requests());
    }

    public function testPermanentlyDeletingAPublishedPostPurgesItsUrls()
    {
        $postId = $this->createPost(array('post_status' => 'publish', 'post_name' => 'delete-me'));
        $publishedUrl = get_permalink($postId);
        $this->http->clearRequests();

        wp_delete_post($postId, true);

        $this->assertContains($publishedUrl, $this->purgedUrls());
    }
}
