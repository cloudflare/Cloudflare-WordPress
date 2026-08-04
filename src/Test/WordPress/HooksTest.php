<?php

namespace Cloudflare\APO\Test\WordPress;

use Cloudflare\APO\WordPress\Hooks;
use Cloudflare\APO\API\Plugin;
use Cloudflare\APO\Integration\DefaultIntegration;
use phpmock\phpunit\PHPMock;

class HooksTest extends \PHPUnit\Framework\TestCase
{
    use PHPMock;

    protected $hooks;
    protected $mockConfig;
    protected $mockDataStore;
    protected $mockLogger;
    protected $mockWordPressAPI;
    protected $mockWordPressClientAPI;
    protected $mockDefaultIntegration;
    protected $mockProxy;

    public function setup(): void
    {
        $this->mockConfig = $this->getMockBuilder('Cloudflare\APO\Integration\DefaultConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataStore = $this->getMockBuilder('Cloudflare\APO\WordPress\DataStore')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockProxy = $this->getMockBuilder('Cloudflare\APO\WordPress\Proxy')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockWordPressAPI = $this->getMockBuilder('Cloudflare\APO\WordPress\WordPressAPI')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockWordPressClientAPI = $this->getMockBuilder('Cloudflare\APO\WordPress\WordPressClientAPI')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDefaultIntegration = new DefaultIntegration($this->mockConfig, $this->mockWordPressAPI, $this->mockDataStore, $this->mockLogger);

        $this->hooks = $this->getMockBuilder('\Cloudflare\APO\WordPress\Hooks')
            ->disableOriginalConstructor()
            ->setMethods(array('__construct')) // This is a hack to make the tests work
            ->getMock();

        $this->hooks->setAPI($this->mockWordPressClientAPI);
        $this->hooks->setConfig($this->mockConfig);
        $this->hooks->setDataStore($this->mockDataStore);
        $this->hooks->setLogger($this->mockLogger);
        $this->hooks->setIntegrationAPI($this->mockWordPressAPI);
        $this->hooks->setIntegrationContext($this->mockDefaultIntegration);
        $this->hooks->setProxy($this->mockProxy);
    }

    public function testCloudflareConfigPageCallsAddOptionsPageHookIfItExists()
    {
        $mockFunctionExists = $this->getFunctionMock('Cloudflare\APO\WordPress', 'function_exists');
        $mockFunctionExists->expects($this->once())->willReturn(true);
        $mock__ = $this->getFunctionMock('Cloudflare\APO\WordPress', '__');
        $mockAddOptionsPage = $this->getFunctionMock('Cloudflare\APO\WordPress', 'add_options_page');
        $mockAddOptionsPage->expects($this->once());
        $this->hooks->cloudflareConfigPage();
    }

    public function testPluginActionLinksGetAdminUrl()
    {
        $mockGetAdminUrl = $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_admin_url');
        $url = 'options-general.php?page=cloudflare';
        $link = '<a href="'.$url.'">Settings</a>';
        $mockGetAdminUrl->expects($this->once())->with(null, $url)->willReturn($url);
        $this->assertEquals(array($link), $this->hooks->pluginActionLinks(array()));
    }

    public function testInitProxyCallsProxyRun()
    {
        $this->mockWordPressAPI->method('isCurrentUserAdministrator')->willReturn(true);
        $this->mockProxy->expects($this->once())->method('run');
        $this->hooks->initProxy();
    }

    public function testActivateChecksWPVersionAndCurl()
    {
        define('CLOUDFLARE_MIN_WP_VERSION', '3.4');
        $GLOBALS['wp_version'] = '3.5';
        $this->assertTrue($this->hooks->activate());
    }

    public function testDeactivateCallsClearDataStore()
    {
        $this->mockDataStore->expects($this->once())->method('clearDataStore');
        $this->hooks->deactivate();
    }

    public function testPurgeCacheCallsZonePurgeCache()
    {
        $this->mockDataStore->method('getPluginSetting')->willReturn(array('value' => 'value'));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('domain.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->expects($this->once())->method('zonePurgeCache');
        $this->hooks->purgeCacheEverything();
    }

    /**
     * Call protected/private method of a class.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function pathIsNotForFeedsTrueProvider()
    {
        return array(
            'product page' => array('https://example.com/shop/product-1/'),
            'feedback path does not match feed' => array('/feedback'),
            'myfeed path does not match feed boundary' => array('/myfeed'),
            'feed prefix with extra segment' => array('/feed/something-else'),
            'plural feeds path' => array('/feeds/'),
            'root path only' => array('https://example.com/'),
        );
    }

    /**
     * @dataProvider pathIsNotForFeedsTrueProvider
     */
    public function testPathIsNotForFeedsReturnsTrueForNonFeedUrls($url)
    {
        $this->assertSame(true, $this->invokeMethod($this->hooks, 'pathIsNotForFeeds', array($url)));
    }

    public function pathIsNotForFeedsFalseProvider()
    {
        return array(
            '/feed' => array('/feed'),
            '/feed/' => array('/feed/'),
            '/feed/rdf/' => array('/feed/rdf/'),
            '/feed/rss/' => array('/feed/rss/'),
            '/feed/atom/' => array('/feed/atom/'),
            '/feed/rdf' => array('/feed/rdf'),
            '/author/foo/feed/' => array('/author/foo/feed/'),
            '/comments/feed/' => array('/comments/feed/'),
            '/shop/feed/' => array('/shop/feed/'),
            '/tag/something/feed/' => array('/tag/something/feed/'),
        );
    }

    /**
     * @dataProvider pathIsNotForFeedsFalseProvider
     */
    public function testPathIsNotForFeedsReturnsFalseForFeedUrls($url)
    {
        $this->assertSame(false, $this->invokeMethod($this->hooks, 'pathIsNotForFeeds', array($url)));
    }

    public function testPathIsNotForFeedsHandlesUrlWithoutPath()
    {
        // parse_url('https://example.com', PHP_URL_PATH) returns null
        $this->assertSame(true, $this->invokeMethod($this->hooks, 'pathIsNotForFeeds', array('https://example.com')));
    }

    public function testPathIsNotForFeedsHandlesMalformedUrl()
    {
        // parse_url('http:///') returns false on malformed URLs
        $this->assertSame(true, $this->invokeMethod($this->hooks, 'pathIsNotForFeeds', array('http:///')));
    }

    public function pathHasCachableFileExtensionTrueProvider()
    {
        return array(
            'jpg' => array('https://example.com/image.jpg'),
            'css' => array('https://example.com/styles/main.css'),
            'pdf' => array('https://example.com/docs/file.pdf'),
            'woff2' => array('https://example.com/fonts/font.woff2'),
            'zip' => array('https://example.com/downloads/archive.zip'),
        );
    }

    /**
     * @dataProvider pathHasCachableFileExtensionTrueProvider
     */
    public function testPathHasCachableFileExtensionReturnsTrueForCachableExtensions($url)
    {
        $this->assertSame(true, $this->invokeMethod($this->hooks, 'pathHasCachableFileExtension', array($url)));
    }

    public function pathHasCachableFileExtensionFalseProvider()
    {
        return array(
            'page path with trailing slash' => array('https://example.com/page/'),
            'root path' => array('https://example.com/'),
            'unknown extension' => array('https://example.com/file.unknown'),
            'no extension' => array('https://example.com/no-extension'),
            'no path' => array('https://example.com'),
        );
    }

    /**
     * @dataProvider pathHasCachableFileExtensionFalseProvider
     */
    public function testPathHasCachableFileExtensionReturnsFalseForNonCachable($url)
    {
        $this->assertSame(false, $this->invokeMethod($this->hooks, 'pathHasCachableFileExtension', array($url)));
    }

    public function urlIsHTTPSProvider()
    {
        return array(
            'https url' => array('https://example.com/page', true),
            'http url' => array('http://example.com/page', false),
            'malformed url' => array('http:///', false),
            'empty string' => array('', false),
        );
    }

    /**
     * @dataProvider urlIsHTTPSProvider
     */
    public function testUrlIsHTTPS($url, $expected)
    {
        $this->assertSame($expected, $this->invokeMethod($this->hooks, 'urlIsHTTPS', array($url)));
    }

    public function testPageRuleContainsReturnsFalseForNonArrayInput()
    {
        $this->assertSame(false, $this->invokeMethod($this->hooks, 'pageRuleContains', array(null, 'cache_level', 'cache_everything')));
        $this->assertSame(false, $this->invokeMethod($this->hooks, 'pageRuleContains', array('not-an-array', 'cache_level', 'cache_everything')));
    }

    public function testPageRuleContainsReturnsFalseForEmptyArray()
    {
        $this->assertSame(false, $this->invokeMethod($this->hooks, 'pageRuleContains', array(array(), 'cache_level', 'cache_everything')));
    }

    public function testPageRuleContainsMatchesAlwaysUseHttps()
    {
        $pageRules = array(
            array(
                'actions' => array(
                    array('id' => 'always_use_https'),
                ),
            ),
        );

        $this->assertSame(true, $this->invokeMethod($this->hooks, 'pageRuleContains', array($pageRules, 'always_use_https', null)));
    }

    public function testPageRuleContainsMatchesCacheLevelCacheEverything()
    {
        $pageRules = array(
            array(
                'actions' => array(
                    array('id' => 'cache_level', 'value' => 'cache_everything'),
                ),
            ),
        );

        $this->assertSame(true, $this->invokeMethod($this->hooks, 'pageRuleContains', array($pageRules, 'cache_level', 'cache_everything')));
    }

    public function testPageRuleContainsReturnsFalseWhenKeyMatchesButValueDiffers()
    {
        $pageRules = array(
            array(
                'actions' => array(
                    array('id' => 'cache_level', 'value' => 'bypass'),
                ),
            ),
        );

        $this->assertSame(false, $this->invokeMethod($this->hooks, 'pageRuleContains', array($pageRules, 'cache_level', 'cache_everything')));
    }

    public function testPageRuleContainsSkipsActionsWithoutValueKey()
    {
        $pageRules = array(
            array(
                'actions' => array(
                    array('id' => 'cache_level'),
                    array('id' => 'cache_level', 'value' => 'cache_everything'),
                ),
            ),
        );

        $this->assertSame(true, $this->invokeMethod($this->hooks, 'pageRuleContains', array($pageRules, 'cache_level', 'cache_everything')));
    }

    public function testZoneSettingAlwaysUseHTTPSEnabledReturnsTrueWhenOn()
    {
        $this->mockWordPressClientAPI->expects($this->once())
            ->method('getZoneSetting')
            ->with('zoneTag', 'always_use_https')
            ->willReturn(array('value' => 'on'));

        $this->assertSame(true, $this->invokeMethod($this->hooks, 'zoneSettingAlwaysUseHTTPSEnabled', array('zoneTag')));
    }

    public function testZoneSettingAlwaysUseHTTPSEnabledReturnsFalseWhenOff()
    {
        $this->mockWordPressClientAPI->expects($this->once())
            ->method('getZoneSetting')
            ->willReturn(array('value' => 'off'));

        $this->assertSame(false, $this->invokeMethod($this->hooks, 'zoneSettingAlwaysUseHTTPSEnabled', array('zoneTag')));
    }

    public function testZoneSettingAlwaysUseHTTPSEnabledReturnsFalseForEmptySettings()
    {
        $this->mockWordPressClientAPI->expects($this->once())
            ->method('getZoneSetting')
            ->willReturn(array());

        $this->assertSame(false, $this->invokeMethod($this->hooks, 'zoneSettingAlwaysUseHTTPSEnabled', array('zoneTag')));
    }

    public function testZoneSettingAlwaysUseHTTPSEnabledReturnsFalseWhenValueKeyMissing()
    {
        $this->mockWordPressClientAPI->expects($this->once())
            ->method('getZoneSetting')
            ->willReturn(array('id' => 'always_use_https'));

        $this->assertSame(false, $this->invokeMethod($this->hooks, 'zoneSettingAlwaysUseHTTPSEnabled', array('zoneTag')));
    }

    // -- purgeCacheByRelevantURLs ---------------------------------------------

    /**
     * Build a partial Hooks mock that stubs both __construct and
     * getPostRelatedLinks. Tests for purgeCacheByRelevantURLs need to bypass
     * getPostRelatedLinks so they don't have to mock ~20 WP functions.
     *
     * @param array $relatedLinksByPost Map of postId => array of related URLs.
     */
    private function makeHooksWithStubbedPostLinks(array $relatedLinksByPost = array())
    {
        $hooks = $this->getMockBuilder('\Cloudflare\APO\WordPress\Hooks')
            ->disableOriginalConstructor()
            ->setMethods(array('__construct', 'getPostRelatedLinks'))
            ->getMock();

        $hooks->setAPI($this->mockWordPressClientAPI);
        $hooks->setConfig($this->mockConfig);
        $hooks->setDataStore($this->mockDataStore);
        $hooks->setLogger($this->mockLogger);
        $hooks->setIntegrationAPI($this->mockWordPressAPI);
        $hooks->setIntegrationContext($this->mockDefaultIntegration);
        $hooks->setProxy($this->mockProxy);

        $hooks->method('getPostRelatedLinks')->willReturnCallback(
            function ($postId) use ($relatedLinksByPost) {
                return isset($relatedLinksByPost[$postId]) ? $relatedLinksByPost[$postId] : array();
            }
        );

        return $hooks;
    }

    /**
     * Stub getPluginSetting to return values for the Plugin::SETTING_* keys.
     * Keys not in $settings return false (matching the missing-setting branch).
     *
     * @param array $settings Map of setting key => value.
     */
    private function configurePluginSettings(array $settings)
    {
        $this->mockDataStore->method('getPluginSetting')->willReturnCallback(
            function ($key) use ($settings) {
                if (!array_key_exists($key, $settings)) {
                    return false;
                }
                return array(Plugin::SETTING_VALUE_KEY => $settings[$key]);
            }
        );
    }

    /**
     * Set up the WordPress function mocks used by the post-processing loop in
     * purgeCacheByRelevantURLs. All return happy-path values by default.
     */
    private function stubHappyPathPostFunctions()
    {
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_autosave')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_revision')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type')
            ->expects($this->any())->willReturn('post');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type_object')
            ->expects($this->any())->willReturn(new \stdClass());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_post_type_viewable')
            ->expects($this->any())->willReturn(true);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post')
            ->expects($this->any())->willReturn(new \WP_Post());
        // WP_Post is a core WordPress class not available in tests; mock the
        // is_a() check to satisfy the WP_Post type guard in Hooks.php.
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_a')
            ->expects($this->any())->willReturn(true);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'apply_filters')
            ->expects($this->any())->willReturnCallback(
                function ($name, $value) {
                    return $value;
                }
            );
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'do_action')
            ->expects($this->any());
    }

    public function testPurgeCacheByRelevantURLsNoOpWhenCacheAndAPODisabled()
    {
        $this->configurePluginSettings(array()); // both off
        $this->mockWordPressAPI->expects($this->never())->method('getDomainList');
        $this->mockWordPressClientAPI->expects($this->never())->method('zonePurgeFiles');

        $this->hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsNoOpWhenAPOSettingIsEmptyString()
    {
        $this->configurePluginSettings(array(Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION => ''));
        $this->mockWordPressAPI->expects($this->never())->method('getDomainList');
        $this->mockWordPressClientAPI->expects($this->never())->method('zonePurgeFiles');

        $this->hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsNoOpWhenDomainListEmpty()
    {
        $this->configurePluginSettings(array(Plugin::SETTING_PLUGIN_SPECIFIC_CACHE => 'on'));
        $this->mockWordPressAPI->expects($this->once())->method('getDomainList')->willReturn(array());
        $this->mockWordPressClientAPI->expects($this->never())->method('getZoneTag');
        $this->mockWordPressClientAPI->expects($this->never())->method('zonePurgeFiles');

        $this->hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsNoOpWhenZoneTagMissing()
    {
        $this->configurePluginSettings(array(Plugin::SETTING_PLUGIN_SPECIFIC_CACHE => 'on'));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->expects($this->once())->method('getZoneTag')->willReturn(null);
        $this->mockWordPressClientAPI->expects($this->never())->method('zonePurgeFiles');

        $this->hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsSkipsAutosavesAndRevisions()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(
            array(123 => array('https://example.com/post-1/'))
        );
        $this->configurePluginSettings(array(Plugin::SETTING_PLUGIN_SPECIFIC_CACHE => 'on'));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');

        // Mark the post as an autosave so the loop skips it before
        // getPostRelatedLinks is reached.
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_autosave')
            ->expects($this->once())->willReturn(true);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_revision')
            ->expects($this->any())->willReturn(false);

        $this->mockWordPressClientAPI->expects($this->never())->method('zonePurgeFiles');

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsSkipsNonViewablePostTypes()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(
            array(123 => array('https://example.com/post-1/'))
        );
        $this->configurePluginSettings(array(Plugin::SETTING_PLUGIN_SPECIFIC_CACHE => 'on'));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');

        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_autosave')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_revision')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type')
            ->expects($this->any())->willReturn('revision');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type_object')
            ->expects($this->any())->willReturn(new \stdClass());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_post_type_viewable')
            ->expects($this->once())->willReturn(false);

        $this->mockWordPressClientAPI->expects($this->never())->method('zonePurgeFiles');

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsSkipsWhenGetPostReturnsNonWPPost()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(
            array(123 => array('https://example.com/post-1/'))
        );
        $this->configurePluginSettings(array(Plugin::SETTING_PLUGIN_SPECIFIC_CACHE => 'on'));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');

        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_autosave')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_revision')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type')
            ->expects($this->any())->willReturn('post');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type_object')
            ->expects($this->any())->willReturn(new \stdClass());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_post_type_viewable')
            ->expects($this->any())->willReturn(true);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post')
            ->expects($this->once())->willReturn(null);

        $this->mockWordPressClientAPI->expects($this->never())->method('zonePurgeFiles');

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsFiltersOutOfZoneURLs()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(array(
            123 => array(
                'https://example.com/post-1/',
                'https://other.com/external/',
            ),
        ));
        // Enable APO to bypass the cacheable-extension filter so this test
        // isolates the host (in-zone) filter.
        $this->configurePluginSettings(array(
            Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION => 'on',
        ));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->method('getPageRules')->willReturn(array(
            array(
                'actions' => array(
                    array('id' => 'cache_level', 'value' => 'cache_everything'),
                ),
            ),
        ));
        $this->mockWordPressClientAPI->method('getZoneSetting')->willReturn(array('value' => 'off'));
        $this->stubHappyPathPostFunctions();

        $this->mockWordPressClientAPI->expects($this->once())
            ->method('zonePurgeFiles')
            ->with('zoneTag', $this->callback(function ($urls) {
                return $urls === array('https://example.com/post-1/');
            }))
            ->willReturn(true);

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsFiltersFeedsWhenNoCacheEverythingPageRule()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(array(
            123 => array(
                'https://example.com/post-1/',
                'https://example.com/feed/',
            ),
        ));
        $this->configurePluginSettings(array(
            Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION => 'on',
        ));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->method('getPageRules')->willReturn(array());
        $this->mockWordPressClientAPI->method('getZoneSetting')->willReturn(array('value' => 'off'));
        $this->stubHappyPathPostFunctions();

        $this->mockWordPressClientAPI->expects($this->once())
            ->method('zonePurgeFiles')
            ->with('zoneTag', $this->callback(function ($urls) {
                return $urls === array('https://example.com/post-1/');
            }))
            ->willReturn(true);

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsKeepsFeedsWhenCacheEverythingPageRulePresent()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(array(
            123 => array(
                'https://example.com/post-1/',
                'https://example.com/feed/',
            ),
        ));
        $this->configurePluginSettings(array(
            Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION => 'on',
        ));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->method('getPageRules')->willReturn(array(
            array(
                'actions' => array(
                    array('id' => 'cache_level', 'value' => 'cache_everything'),
                ),
            ),
        ));
        $this->mockWordPressClientAPI->method('getZoneSetting')->willReturn(array('value' => 'off'));
        $this->stubHappyPathPostFunctions();

        $this->mockWordPressClientAPI->expects($this->once())
            ->method('zonePurgeFiles')
            ->with('zoneTag', $this->callback(function ($urls) {
                sort($urls);
                return $urls === array(
                    'https://example.com/feed/',
                    'https://example.com/post-1/',
                );
            }))
            ->willReturn(true);

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsFiltersNonCacheableExtensionsWhenNoOverrideAndNoAPO()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(array(
            123 => array(
                'https://example.com/image.jpg',
                'https://example.com/page.html',
            ),
        ));
        $this->configurePluginSettings(array(Plugin::SETTING_PLUGIN_SPECIFIC_CACHE => 'on'));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->method('getPageRules')->willReturn(array());
        $this->mockWordPressClientAPI->method('getZoneSetting')->willReturn(array('value' => 'off'));
        $this->stubHappyPathPostFunctions();

        $this->mockWordPressClientAPI->expects($this->once())
            ->method('zonePurgeFiles')
            ->with('zoneTag', $this->callback(function ($urls) {
                return $urls === array('https://example.com/image.jpg');
            }))
            ->willReturn(true);

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsKeepsNonCacheableExtensionsWhenAPOEnabled()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(array(
            123 => array(
                'https://example.com/image.jpg',
                'https://example.com/page.html',
            ),
        ));
        $this->configurePluginSettings(array(
            Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION => 'on',
        ));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->method('getPageRules')->willReturn(array());
        $this->mockWordPressClientAPI->method('getZoneSetting')->willReturn(array('value' => 'off'));
        $this->stubHappyPathPostFunctions();

        $this->mockWordPressClientAPI->expects($this->once())
            ->method('zonePurgeFiles')
            ->with('zoneTag', $this->callback(function ($urls) {
                sort($urls);
                return $urls === array(
                    'https://example.com/image.jpg',
                    'https://example.com/page.html',
                );
            }))
            ->willReturn(true);

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsFiltersHTTPWhenAlwaysUseHTTPSEnabled()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(array(
            123 => array(
                'https://example.com/secure.jpg',
                'http://example.com/insecure.jpg',
            ),
        ));
        $this->configurePluginSettings(array(
            Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION => 'on',
        ));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->method('getPageRules')->willReturn(array());
        $this->mockWordPressClientAPI->method('getZoneSetting')->willReturn(array('value' => 'on'));
        $this->stubHappyPathPostFunctions();

        $this->mockWordPressClientAPI->expects($this->once())
            ->method('zonePurgeFiles')
            ->with('zoneTag', $this->callback(function ($urls) {
                return $urls === array('https://example.com/secure.jpg');
            }))
            ->willReturn(true);

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsChunksURLsInGroupsOf30()
    {
        $urls = array();
        for ($i = 1; $i <= 35; $i++) {
            $urls[] = 'https://example.com/page-' . $i . '.jpg';
        }
        $hooks = $this->makeHooksWithStubbedPostLinks(array(123 => $urls));
        $this->configurePluginSettings(array(
            Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION => 'on',
        ));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->method('getPageRules')->willReturn(array());
        $this->mockWordPressClientAPI->method('getZoneSetting')->willReturn(array('value' => 'off'));
        $this->stubHappyPathPostFunctions();

        $callCount = 0;
        $this->mockWordPressClientAPI->expects($this->exactly(2))
            ->method('zonePurgeFiles')
            ->willReturnCallback(function ($zone, $chunk) use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    \PHPUnit\Framework\Assert::assertCount(30, $chunk);
                } else {
                    \PHPUnit\Framework\Assert::assertCount(5, $chunk);
                }
                return true;
            });

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsTriggersMobilePurgeWhenCacheByDeviceTypeEnabled()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(array(
            123 => array('https://example.com/image.jpg'),
        ));
        $this->configurePluginSettings(array(
            Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION => 'on',
            Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION_CACHE_BY_DEVICE_TYPE => 'on',
        ));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->method('getPageRules')->willReturn(array());
        $this->mockWordPressClientAPI->method('getZoneSetting')->willReturn(array('value' => 'off'));
        $this->stubHappyPathPostFunctions();

        // Expect two calls: one with raw URL, one with mobile-shaped objects.
        $callCount = 0;
        $this->mockWordPressClientAPI->expects($this->exactly(2))
            ->method('zonePurgeFiles')
            ->willReturnCallback(function ($zone, $chunk) use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    \PHPUnit\Framework\Assert::assertSame(
                        array('https://example.com/image.jpg'),
                        $chunk
                    );
                } else {
                    \PHPUnit\Framework\Assert::assertCount(1, $chunk);
                    \PHPUnit\Framework\Assert::assertSame(
                        'https://example.com/image.jpg',
                        $chunk[0]->url
                    );
                    \PHPUnit\Framework\Assert::assertSame(
                        'mobile',
                        $chunk[0]->headers->{'CF-Device-Type'}
                    );
                }
                return true;
            });

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsHonoursCloudflarePurgeByUrlFilter()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(array(
            123 => array('https://example.com/original.jpg'),
        ));
        $this->configurePluginSettings(array(
            Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION => 'on',
        ));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->method('getPageRules')->willReturn(array());
        $this->mockWordPressClientAPI->method('getZoneSetting')->willReturn(array('value' => 'off'));

        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_autosave')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_revision')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type')
            ->expects($this->any())->willReturn('post');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type_object')
            ->expects($this->any())->willReturn(new \stdClass());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_post_type_viewable')
            ->expects($this->any())->willReturn(true);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post')
            ->expects($this->any())->willReturn(new \WP_Post());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'do_action')
            ->expects($this->any());

        // Filter rewrites URLs to a different in-zone path.
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'apply_filters')
            ->expects($this->once())
            ->with('cloudflare_purge_by_url', $this->anything(), 123)
            ->willReturn(array('https://example.com/replaced.jpg'));

        $this->mockWordPressClientAPI->expects($this->once())
            ->method('zonePurgeFiles')
            ->with('zoneTag', array('https://example.com/replaced.jpg'))
            ->willReturn(true);

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsFiresCloudflarePurgedUrlsAction()
    {
        $hooks = $this->makeHooksWithStubbedPostLinks(array(
            123 => array('https://example.com/image.jpg'),
        ));
        $this->configurePluginSettings(array(
            Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION => 'on',
        ));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->method('getPageRules')->willReturn(array());
        $this->mockWordPressClientAPI->method('getZoneSetting')->willReturn(array('value' => 'off'));
        $this->mockWordPressClientAPI->method('zonePurgeFiles')->willReturn(true);

        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_autosave')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_is_post_revision')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type')
            ->expects($this->any())->willReturn('post');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type_object')
            ->expects($this->any())->willReturn(new \stdClass());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_post_type_viewable')
            ->expects($this->any())->willReturn(true);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post')
            ->expects($this->any())->willReturn(new \WP_Post());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'apply_filters')
            ->expects($this->any())->willReturnCallback(
                function ($name, $value) {
                    return $value;
                }
            );

        $this->getFunctionMock('Cloudflare\APO\WordPress', 'do_action')
            ->expects($this->once())
            ->with('cloudflare_purged_urls', $this->isType('array'), array(123));

        $hooks->purgeCacheByRelevantURLs(123);
    }

    public function testPurgeCacheByRelevantURLsHandlesURLArrayShape()
    {
        // Some plugins push purge entries through the cloudflare_purge_by_url
        // filter as ['url' => ..., 'headers' => ...]. The host check on lines
        // 178-184 of Hooks.php unwraps this shape, but downstream filters
        // (pathIsNotForFeeds, pathHasCachableFileExtension, urlIsHTTPS) call
        // parse_url() directly on the value and crash on PHP 8+ when given an
        // array. We can therefore only assert this shape survives when the
        // cache_everything page rule is set AND APO is on AND always_use_https
        // is off, so all three downstream filters are bypassed.
        $hooks = $this->makeHooksWithStubbedPostLinks(array(
            123 => array(
                array('url' => 'https://example.com/special.jpg', 'headers' => array()),
            ),
        ));
        $this->configurePluginSettings(array(
            Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION => 'on',
        ));
        $this->mockWordPressAPI->method('getDomainList')->willReturn(array('example.com'));
        $this->mockWordPressClientAPI->method('getZoneTag')->willReturn('zoneTag');
        $this->mockWordPressClientAPI->method('getPageRules')->willReturn(array(
            array(
                'actions' => array(
                    array('id' => 'cache_level', 'value' => 'cache_everything'),
                ),
            ),
        ));
        $this->mockWordPressClientAPI->method('getZoneSetting')->willReturn(array('value' => 'off'));
        $this->stubHappyPathPostFunctions();

        $this->mockWordPressClientAPI->expects($this->once())
            ->method('zonePurgeFiles')
            ->with('zoneTag', $this->callback(function ($urls) {
                return count($urls) === 1
                    && is_array($urls[0])
                    && $urls[0]['url'] === 'https://example.com/special.jpg';
            }))
            ->willReturn(true);

        $hooks->purgeCacheByRelevantURLs(123);
    }

    // -- isPluginSpecificCacheEnabled / isAutomaticPlatformOptimizationEnabled
    // -- isAutomaticPlatformOptimizationCacheByDeviceTypeEnabled --------------

    public function pluginSettingProvider()
    {
        return array(
            'missing setting returns false' => array(false, false),
            'value off returns false' => array(array('value' => 'off'), false),
            'value false returns false' => array(array('value' => false), false),
            'value empty string returns false' => array(array('value' => ''), false),
            'value on returns true' => array(array('value' => 'on'), true),
            'value other truthy returns true' => array(array('value' => '1'), true),
        );
    }

    /**
     * @dataProvider pluginSettingProvider
     */
    public function testIsPluginSpecificCacheEnabled($settingObject, $expected)
    {
        $this->mockDataStore->expects($this->once())
            ->method('getPluginSetting')
            ->with(Plugin::SETTING_PLUGIN_SPECIFIC_CACHE)
            ->willReturn($settingObject);

        $this->assertSame($expected, $this->invokeMethod($this->hooks, 'isPluginSpecificCacheEnabled'));
    }

    /**
     * @dataProvider pluginSettingProvider
     */
    public function testIsAutomaticPlatformOptimizationEnabled($settingObject, $expected)
    {
        $this->mockDataStore->expects($this->once())
            ->method('getPluginSetting')
            ->with(Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION)
            ->willReturn($settingObject);

        $this->assertSame($expected, $this->invokeMethod($this->hooks, 'isAutomaticPlatformOptimizationEnabled'));
    }

    /**
     * @dataProvider pluginSettingProvider
     */
    public function testIsAutomaticPlatformOptimizationCacheByDeviceTypeEnabled($settingObject, $expected)
    {
        $this->mockDataStore->expects($this->once())
            ->method('getPluginSetting')
            ->with(Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION_CACHE_BY_DEVICE_TYPE)
            ->willReturn($settingObject);

        $this->assertSame($expected, $this->invokeMethod($this->hooks, 'isAutomaticPlatformOptimizationCacheByDeviceTypeEnabled'));
    }

    // -- getCloudflareRequestJSON --------------------------------------------

    public function testGetCloudflareRequestJSONStoresInputWhenActionMatches()
    {
        $_GET['action'] = Hooks::WP_AJAX_ACTION;
        unset($GLOBALS[Hooks::CLOUDFLARE_JSON]);

        $this->getFunctionMock('Cloudflare\APO\WordPress', 'file_get_contents')
            ->expects($this->once())
            ->with('php://input')
            ->willReturn('{"foo":"bar"}');

        $this->hooks->getCloudflareRequestJSON();

        $this->assertSame('{"foo":"bar"}', $GLOBALS[Hooks::CLOUDFLARE_JSON]);

        unset($_GET['action']);
        unset($GLOBALS[Hooks::CLOUDFLARE_JSON]);
    }

    public function testGetCloudflareRequestJSONNoOpWhenActionMissing()
    {
        unset($_GET['action']);
        unset($GLOBALS[Hooks::CLOUDFLARE_JSON]);

        $this->getFunctionMock('Cloudflare\APO\WordPress', 'file_get_contents')
            ->expects($this->never());

        $this->hooks->getCloudflareRequestJSON();

        $this->assertArrayNotHasKey(Hooks::CLOUDFLARE_JSON, $GLOBALS);
    }

    public function testGetCloudflareRequestJSONNoOpWhenActionDoesNotMatch()
    {
        $_GET['action'] = 'something_else';
        unset($GLOBALS[Hooks::CLOUDFLARE_JSON]);

        $this->getFunctionMock('Cloudflare\APO\WordPress', 'file_get_contents')
            ->expects($this->never());

        $this->hooks->getCloudflareRequestJSON();

        $this->assertArrayNotHasKey(Hooks::CLOUDFLARE_JSON, $GLOBALS);

        unset($_GET['action']);
    }

    // -- initAutomaticPlatformOptimization -----------------------------------

    public function testInitAutomaticPlatformOptimizationReturnsEarlyWhenHeadersSent()
    {
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'headers_sent')
            ->expects($this->once())->willReturn(true);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'apply_filters')
            ->expects($this->never());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'header')
            ->expects($this->never());

        $this->hooks->initAutomaticPlatformOptimization();
    }

    public function testInitAutomaticPlatformOptimizationSendsCacheHeaderWhenFilterAllows()
    {
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'headers_sent')
            ->expects($this->once())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_user_logged_in')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'apply_filters')
            ->expects($this->once())
            ->with('cloudflare_use_cache', true)
            ->willReturn(true);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'header')
            ->expects($this->once())
            ->with('cf-edge-cache: cache,platform=wordpress');

        $this->hooks->initAutomaticPlatformOptimization();
    }

    public function testInitAutomaticPlatformOptimizationSendsNoCacheHeaderWhenFilterBlocks()
    {
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'headers_sent')
            ->expects($this->once())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_user_logged_in')
            ->expects($this->any())->willReturn(true);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'apply_filters')
            ->expects($this->once())
            ->with('cloudflare_use_cache', false)
            ->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'header')
            ->expects($this->once())
            ->with('cf-edge-cache: no-cache');

        $this->hooks->initAutomaticPlatformOptimization();
    }

    // -- purgeCacheOnPostStatusChange ----------------------------------------

    /**
     * Build a partial Hooks mock that stubs purgeCacheByRelevantURLs so the
     * post/comment status change tests can verify whether the purge is invoked
     * without exercising the full cache-purge pipeline.
     */
    private function makeHooksWithStubbedPurge()
    {
        $hooks = $this->getMockBuilder('\Cloudflare\APO\WordPress\Hooks')
            ->disableOriginalConstructor()
            ->setMethods(array('__construct', 'purgeCacheByRelevantURLs'))
            ->getMock();

        $hooks->setAPI($this->mockWordPressClientAPI);
        $hooks->setConfig($this->mockConfig);
        $hooks->setDataStore($this->mockDataStore);
        $hooks->setLogger($this->mockLogger);
        $hooks->setIntegrationAPI($this->mockWordPressAPI);
        $hooks->setIntegrationContext($this->mockDefaultIntegration);
        $hooks->setProxy($this->mockProxy);

        return $hooks;
    }

    public function testPurgeCacheOnPostStatusChangePurgesWhenNewStatusIsPublish()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $post = (object) array('ID' => 42);

        $hooks->expects($this->once())->method('purgeCacheByRelevantURLs')->with(42);

        $hooks->purgeCacheOnPostStatusChange('publish', 'draft', $post);
    }

    public function testPurgeCacheOnPostStatusChangePurgesWhenOldStatusIsPublish()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $post = (object) array('ID' => 42);

        $hooks->expects($this->once())->method('purgeCacheByRelevantURLs')->with(42);

        $hooks->purgeCacheOnPostStatusChange('trash', 'publish', $post);
    }

    public function testPurgeCacheOnPostStatusChangeNoOpWhenNeitherIsPublish()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $post = (object) array('ID' => 42);

        $hooks->expects($this->never())->method('purgeCacheByRelevantURLs');

        $hooks->purgeCacheOnPostStatusChange('draft', 'pending', $post);
    }

    // -- purgeCacheOnCommentStatusChange -------------------------------------

    public function testPurgeCacheOnCommentStatusChangeNoOpWhenPostIDMissing()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $hooks->expects($this->never())->method('purgeCacheByRelevantURLs');

        $hooks->purgeCacheOnCommentStatusChange('approved', 'unapproved', (object) array());
    }

    public function testPurgeCacheOnCommentStatusChangeNoOpWhenPostIDEmpty()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $hooks->expects($this->never())->method('purgeCacheByRelevantURLs');

        $comment = (object) array('comment_post_ID' => 0);
        $hooks->purgeCacheOnCommentStatusChange('approved', 'unapproved', $comment);
    }

    public function testPurgeCacheOnCommentStatusChangePurgesWhenNewlyApproved()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $hooks->expects($this->once())->method('purgeCacheByRelevantURLs')->with(7);

        $comment = (object) array('comment_post_ID' => 7);
        $hooks->purgeCacheOnCommentStatusChange('approved', 'unapproved', $comment);
    }

    public function testPurgeCacheOnCommentStatusChangePurgesWhenUnapprovedFromApproved()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $hooks->expects($this->once())->method('purgeCacheByRelevantURLs')->with(7);

        $comment = (object) array('comment_post_ID' => 7);
        $hooks->purgeCacheOnCommentStatusChange('unapproved', 'approved', $comment);
    }

    public function testPurgeCacheOnCommentStatusChangeNoOpWhenStatusUnchanged()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $hooks->expects($this->never())->method('purgeCacheByRelevantURLs');

        $comment = (object) array('comment_post_ID' => 7);
        $hooks->purgeCacheOnCommentStatusChange('approved', 'approved', $comment);
    }

    public function testPurgeCacheOnCommentStatusChangeNoOpWhenNeitherSideIsApproved()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $hooks->expects($this->never())->method('purgeCacheByRelevantURLs');

        $comment = (object) array('comment_post_ID' => 7);
        $hooks->purgeCacheOnCommentStatusChange('spam', 'unapproved', $comment);
    }

    // -- purgeCacheOnNewComment ----------------------------------------------

    public function testPurgeCacheOnNewCommentNoOpWhenStatusNotApproved()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $hooks->expects($this->never())->method('purgeCacheByRelevantURLs');

        $hooks->purgeCacheOnNewComment(1, 0, array('comment_post_ID' => 7));
    }

    public function testPurgeCacheOnNewCommentNoOpWhenDataNotArray()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $hooks->expects($this->never())->method('purgeCacheByRelevantURLs');

        $hooks->purgeCacheOnNewComment(1, 1, null);
    }

    public function testPurgeCacheOnNewCommentNoOpWhenPostIDKeyMissing()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $hooks->expects($this->never())->method('purgeCacheByRelevantURLs');

        $hooks->purgeCacheOnNewComment(1, 1, array('foo' => 'bar'));
    }

    public function testPurgeCacheOnNewCommentPurgesOnApprovedNewComment()
    {
        $hooks = $this->makeHooksWithStubbedPurge();
        $hooks->expects($this->once())->method('purgeCacheByRelevantURLs')->with(7);

        $hooks->purgeCacheOnNewComment(1, 1, array('comment_post_ID' => 7));
    }

    // -- toPurgeCacheOnMobile ------------------------------------------------

    public function testToPurgeCacheOnMobileWrapsUrlWithMobileHeader()
    {
        $result = $this->invokeMethod($this->hooks, 'toPurgeCacheOnMobile', array('https://example.com/a'));

        $this->assertEquals('https://example.com/a', $result->url);
        $this->assertEquals('mobile', $result->headers->{'CF-Device-Type'});
    }

    // -- getPostRelatedLinks -------------------------------------------------

    /**
     * Configure the WordPress functions used by getPostRelatedLinks with
     * minimal happy-path values: a public 'post' with no taxonomies, no
     * archive, no trash, no attachments, and no SSL admin forcing.
     */
    private function stubGetPostRelatedLinksDefaults()
    {
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type')
            ->expects($this->any())->willReturn('post');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_object_taxonomies')
            ->expects($this->any())->willReturn(array());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_posts_url')
            ->expects($this->any())->willReturn('https://example.com/author/jane/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_feed_link')
            ->expects($this->any())->willReturn('https://example.com/author/jane/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_field')
            ->expects($this->any())->willReturn(1);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type_archive_link')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_permalink')
            ->expects($this->any())->willReturn('https://example.com/post/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_status')
            ->expects($this->any())->willReturn('publish');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_bloginfo_rss')
            ->expects($this->any())->willReturnCallback(function ($key) {
                return 'https://example.com/' . $key;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_comments_feed_link')
            ->expects($this->any())->willReturn('https://example.com/post/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'home_url')
            ->expects($this->any())->willReturnCallback(function ($path = '/') {
                return 'https://example.com' . $path;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_option')
            ->expects($this->any())->willReturnCallback(function ($key) {
                if ($key === 'posts_per_page') {
                    return 10;
                }
                if ($key === 'show_on_front') {
                    return 'posts';
                }
                if ($key === 'page_for_posts') {
                    return 0;
                }
                return false;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_count_posts')
            ->expects($this->any())->willReturn((object) array('publish' => 5));
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'function_exists')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_ssl')
            ->expects($this->any())->willReturn(true);
    }

    public function testGetPostRelatedLinksReturnsBaseURLs()
    {
        $this->stubGetPostRelatedLinksDefaults();

        $urls = $this->hooks->getPostRelatedLinks(123);

        $this->assertContains('https://example.com/post/', $urls);
        $this->assertContains('https://example.com/author/jane/', $urls);
        $this->assertContains('https://example.com/author/jane/feed/', $urls);
        $this->assertContains('https://example.com/', $urls);
        $this->assertContains('https://example.com/page/1/', $urls);
        // No archive URL when get_post_type_archive_link returns false.
        $this->assertNotContains(false, $urls);
    }

    public function testGetPostRelatedLinksAddsPostsPageWhenConfigured()
    {
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type')
            ->expects($this->any())->willReturn('post');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_object_taxonomies')
            ->expects($this->any())->willReturn(array());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_posts_url')
            ->expects($this->any())->willReturn('https://example.com/author/jane/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_feed_link')
            ->expects($this->any())->willReturn('https://example.com/author/jane/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_field')
            ->expects($this->any())->willReturn(1);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type_archive_link')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_permalink')
            ->expects($this->any())->willReturnCallback(function ($id) {
                return $id === 0 ? 'https://example.com/posts-page/' : 'https://example.com/post/';
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_status')
            ->expects($this->any())->willReturn('publish');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_bloginfo_rss')
            ->expects($this->any())->willReturn('https://example.com/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_comments_feed_link')
            ->expects($this->any())->willReturn('https://example.com/post/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'home_url')
            ->expects($this->any())->willReturnCallback(function ($path = '/') {
                return 'https://example.com' . $path;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_option')
            ->expects($this->any())->willReturnCallback(function ($key) {
                if ($key === 'posts_per_page') {
                    return 10;
                }
                if ($key === 'show_on_front') {
                    return 'page';
                }
                if ($key === 'page_for_posts') {
                    return 0;
                }
                return false;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_count_posts')
            ->expects($this->any())->willReturn((object) array('publish' => 5));
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'function_exists')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_ssl')
            ->expects($this->any())->willReturn(true);

        $urls = $this->hooks->getPostRelatedLinks(123);

        $this->assertContains('https://example.com/posts-page/', $urls);
    }

    public function testGetPostRelatedLinksAddsTrashedPostUrls()
    {
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type')
            ->expects($this->any())->willReturn('post');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_object_taxonomies')
            ->expects($this->any())->willReturn(array());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_posts_url')
            ->expects($this->any())->willReturn('https://example.com/author/jane/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_feed_link')
            ->expects($this->any())->willReturn('https://example.com/author/jane/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_field')
            ->expects($this->any())->willReturn(1);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type_archive_link')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_permalink')
            ->expects($this->any())->willReturn('https://example.com/post__trashed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_status')
            ->expects($this->any())->willReturn('trash');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_bloginfo_rss')
            ->expects($this->any())->willReturn('https://example.com/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_comments_feed_link')
            ->expects($this->any())->willReturn('https://example.com/post/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'home_url')
            ->expects($this->any())->willReturnCallback(function ($path = '/') {
                return 'https://example.com' . $path;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_option')
            ->expects($this->any())->willReturnCallback(function ($key) {
                return $key === 'posts_per_page' ? 10 : false;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_count_posts')
            ->expects($this->any())->willReturn((object) array('publish' => 5));
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'function_exists')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_ssl')
            ->expects($this->any())->willReturn(true);

        $urls = $this->hooks->getPostRelatedLinks(123);

        $this->assertContains('https://example.com/post/', $urls);
        $this->assertContains('https://example.com/post/feed/', $urls);
    }

    public function testGetPostRelatedLinksAddsAttachmentSizesForAttachmentType()
    {
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type')
            ->expects($this->any())->willReturn('attachment');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_object_taxonomies')
            ->expects($this->any())->willReturn(array());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_posts_url')
            ->expects($this->any())->willReturn('https://example.com/author/jane/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_feed_link')
            ->expects($this->any())->willReturn('https://example.com/author/jane/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_field')
            ->expects($this->any())->willReturn(1);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type_archive_link')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_permalink')
            ->expects($this->any())->willReturn('https://example.com/attachment/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_status')
            ->expects($this->any())->willReturn('publish');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_bloginfo_rss')
            ->expects($this->any())->willReturn('https://example.com/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_comments_feed_link')
            ->expects($this->any())->willReturn('https://example.com/attachment/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'home_url')
            ->expects($this->any())->willReturnCallback(function ($path = '/') {
                return 'https://example.com' . $path;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_option')
            ->expects($this->any())->willReturnCallback(function ($key) {
                return $key === 'posts_per_page' ? 10 : false;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_count_posts')
            ->expects($this->any())->willReturn((object) array('publish' => 5));
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_intermediate_image_sizes')
            ->expects($this->any())->willReturn(array('thumbnail', 'medium'));
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_get_attachment_image_src')
            ->expects($this->any())->willReturnCallback(function ($id, $size) {
                return array('https://example.com/img-' . $size . '.jpg', 100, 100);
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'function_exists')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_ssl')
            ->expects($this->any())->willReturn(true);

        $urls = $this->hooks->getPostRelatedLinks(123);

        $this->assertContains('https://example.com/img-thumbnail.jpg', $urls);
        $this->assertContains('https://example.com/img-medium.jpg', $urls);
    }

    public function testGetPostRelatedLinksAddsHttpVariantsWhenForceSslAdmin()
    {
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type')
            ->expects($this->any())->willReturn('post');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_object_taxonomies')
            ->expects($this->any())->willReturn(array());
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_posts_url')
            ->expects($this->any())->willReturn('https://example.com/author/jane/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_feed_link')
            ->expects($this->any())->willReturn('https://example.com/author/jane/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_field')
            ->expects($this->any())->willReturn(1);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type_archive_link')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_permalink')
            ->expects($this->any())->willReturn('https://example.com/post/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_status')
            ->expects($this->any())->willReturn('publish');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_bloginfo_rss')
            ->expects($this->any())->willReturn('https://example.com/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_comments_feed_link')
            ->expects($this->any())->willReturn('https://example.com/post/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'home_url')
            ->expects($this->any())->willReturnCallback(function ($path = '/') {
                return 'https://example.com' . $path;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_option')
            ->expects($this->any())->willReturnCallback(function ($key) {
                return $key === 'posts_per_page' ? 10 : false;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_count_posts')
            ->expects($this->any())->willReturn((object) array('publish' => 5));
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'function_exists')
            ->expects($this->any())->willReturnCallback(function ($fn) {
                return $fn === 'force_ssl_admin';
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'force_ssl_admin')
            ->expects($this->any())->willReturn(true);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_ssl')
            ->expects($this->any())->willReturn(true);

        $urls = $this->hooks->getPostRelatedLinks(123);

        $this->assertContains('https://example.com/post/', $urls);
        $this->assertContains('http://example.com/post/', $urls);
    }

    public function testGetPostRelatedLinksAddsTaxonomyTermAndFeedUrls()
    {
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type')
            ->expects($this->any())->willReturn('post');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_object_taxonomies')
            ->expects($this->any())->willReturn(array('category', 'private_tax'));
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_taxonomy')
            ->expects($this->any())->willReturnCallback(function ($tax) {
                $obj = new \WP_Taxonomy();
                $obj->public = ($tax === 'category');
                return $obj;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_the_terms')
            ->expects($this->any())->willReturnCallback(function ($postId, $tax) {
                if ($tax !== 'category') {
                    return array();
                }
                return array((object) array('term_id' => 11, 'taxonomy' => 'category'));
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_wp_error')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_term_link')
            ->expects($this->any())->willReturn('https://example.com/category/news/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_term_feed_link')
            ->expects($this->any())->willReturn('https://example.com/category/news/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_posts_url')
            ->expects($this->any())->willReturn('https://example.com/author/jane/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_author_feed_link')
            ->expects($this->any())->willReturn('https://example.com/author/jane/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_field')
            ->expects($this->any())->willReturn(1);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_type_archive_link')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_permalink')
            ->expects($this->any())->willReturn('https://example.com/post/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_status')
            ->expects($this->any())->willReturn('publish');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_bloginfo_rss')
            ->expects($this->any())->willReturn('https://example.com/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_post_comments_feed_link')
            ->expects($this->any())->willReturn('https://example.com/post/feed/');
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'home_url')
            ->expects($this->any())->willReturnCallback(function ($path = '/') {
                return 'https://example.com' . $path;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'get_option')
            ->expects($this->any())->willReturnCallback(function ($key) {
                return $key === 'posts_per_page' ? 10 : false;
            });
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'wp_count_posts')
            ->expects($this->any())->willReturn((object) array('publish' => 5));
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'function_exists')
            ->expects($this->any())->willReturn(false);
        $this->getFunctionMock('Cloudflare\APO\WordPress', 'is_ssl')
            ->expects($this->any())->willReturn(true);

        $urls = $this->hooks->getPostRelatedLinks(123);

        $this->assertContains('https://example.com/category/news/', $urls);
        $this->assertContains('https://example.com/category/news/feed/', $urls);
    }
}
