<?php

namespace Cloudflare\APO\Tests\Integration;

/**
 * Verifies the plugin boots inside WordPress and registers its hooks.
 */
class PluginBootTest extends IntegrationTestCase
{
    public function testPluginIsActiveFromTheCloudflareFolder()
    {
        $this->assertTrue(is_plugin_active('cloudflare/cloudflare.php'));
    }

    public function testPluginConstantsAreDefined()
    {
        $this->assertTrue(defined('CLOUDFLARE_PLUGIN_DIR'));
        $this->assertSame(WP_PLUGIN_DIR . '/cloudflare/', CLOUDFLARE_PLUGIN_DIR);
    }

    public function testRequestHooksAreRegistered()
    {
        $this->assertSame(10, $this->hookPriority('plugins_loaded', 'getCloudflareRequestJSON'));
        $this->assertSame(10, $this->hookPriority('init', 'initAutomaticPlatformOptimization'));
    }

    public function testAdminHooksAreRegistered()
    {
        $this->assertSame(10, $this->hookPriority('wp_ajax_cloudflare_proxy', 'initProxy'));
        $this->assertSame(10, $this->hookPriority('admin_menu', 'cloudflareConfigPage'));
        $this->assertSame(10, $this->hookPriority('plugin_action_links_cloudflare/cloudflare.php', 'pluginActionLinks'));
        $this->assertSame(10, $this->hookPriority('activate_cloudflare/cloudflare.php', 'activate'));
        $this->assertSame(10, $this->hookPriority('deactivate_cloudflare/cloudflare.php', 'deactivate'));
    }

    /**
     * @dataProvider purgeHooks
     *
     * @param string $hookName
     * @param string $method
     */
    public function testPurgeHooksRunLast($hookName, $method)
    {
        $this->assertSame(PHP_INT_MAX, $this->hookPriority($hookName, $method));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function purgeHooks()
    {
        return array(
            'Autoptimize cache purge' => array('autoptimize_action_cachepurged', 'purgeCacheEverything'),
            'theme switch' => array('switch_theme', 'purgeCacheEverything'),
            'customizer save' => array('customize_save_after', 'purgeCacheEverything'),
            'post deleted' => array('deleted_post', 'purgeCacheByRelevantURLs'),
            'attachment deleted' => array('delete_attachment', 'purgeCacheByRelevantURLs'),
            'post status change' => array('transition_post_status', 'purgeCacheOnPostStatusChange'),
            'comment status change' => array('transition_comment_status', 'purgeCacheOnCommentStatusChange'),
            'new comment' => array('comment_post', 'purgeCacheOnNewComment'),
        );
    }

    public function testHttp2ServerPushIsOffByDefault()
    {
        $this->assertFalse($this->hookPriority('init', 'http2ServerPushInit'));
    }
}
