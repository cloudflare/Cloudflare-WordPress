<?php

namespace CF\WordPress;

use CF\API\APIInterface;
use CF\Integration;
use Psr\Log\LoggerInterface;

class Hooks
{
    protected $api;
    protected $config;
    protected $dataStore;
    protected $integrationContext;
    protected $integrationAPI;
    protected $logger;
    protected $proxy;

    public function __construct()
    {
        $this->config = new Integration\DefaultConfig('[]');
        $this->logger = new Integration\DefaultLogger(false);
        $this->dataStore = new DataStore($this->logger);
        $this->integrationAPI = new WordPressAPI($this->dataStore);
        $this->integrationContext = new Integration\DefaultIntegration($this->config, $this->integrationAPI, $this->dataStore, $this->logger);
        $this->api = new WordPressClientAPI($this->integrationContext);
        $this->proxy = new Proxy($this->integrationContext);

        // Don't allow "logged in" options to display to anonymous users
        if ($this->isPluginSpecificCacheEnabled()) {
            add_filter('show_admin_bar', '__return_false');
            add_filter('edit_post_link', '__return_null');
        }
    }

    /**
     * @param \CF\API\APIInterface $api
     */
    public function setAPI(APIInterface $api)
    {
        $this->api = $api;
    }

    public function setConfig(Integration\ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function setDataStore(Integration\DataStoreInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    public function setIntegrationContext(Integration\IntegrationInterface $integrationContext)
    {
        $this->integrationContext = $integrationContext;
    }

    public function setIntegrationAPI(Integration\IntegrationAPIInterface $integrationAPI)
    {
        $this->integrationAPI = $integrationAPI;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setProxy(Proxy $proxy)
    {
        $this->proxy = $proxy;
    }

    public function cloudflareConfigPage()
    {
        if (function_exists('add_options_page')) {
            add_options_page(__('Cloudflare Configuration'), __('Cloudflare'), 'manage_options', 'cloudflare', array($this, 'cloudflareIndexPage'));
        }
    }

    public function cloudflareIndexPage()
    {
        include CLOUDFLARE_PLUGIN_DIR.'index.php';
    }

    public function pluginActionLinks($links)
    {
        $links[] = '<a href="'.get_admin_url(null, 'options-general.php?page=cloudflare').'">Settings</a>';

        return $links;
    }

    public function initProxy()
    {
        $this->proxy->run();
    }

    public function activate()
    {
        if (version_compare($GLOBALS['wp_version'], CLOUDFLARE_MIN_WP_VERSION, '<')) {
            deactivate_plugins(basename(CLOUDFLARE_PLUGIN_DIR));
            wp_die('<p><strong>Cloudflare</strong> plugin requires WordPress version '.CLOUDFLARE_MIN_WP_VERSION.' or greater.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));
        }

        // Guzzle3 depends on php5-curl. If dependency does not exist kill the plugin.
        if (!extension_loaded('curl')) {
            deactivate_plugins(basename(CLOUDFLARE_PLUGIN_DIR));
            wp_die('<p><strong>Cloudflare</strong> plugin requires php5-curl to be installed.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));
        }

        return true;
    }

    public function deactivate()
    {
        $this->dataStore->clearDataStore();
    }

    public function purgeCacheEverything()
    {
        if ($this->isPluginSpecificCacheEnabled()) {
            $wp_domain_list = $this->integrationAPI->getDomainList();
            $wp_domain = $wp_domain_list[0];
            if (count($wp_domain) > 0) {
                $zoneTag = $this->api->getZoneTag($wp_domain);

                if (isset($zoneTag)) {
                    $this->api->zonePurgeCache($zoneTag);
                }
            }
        }
    }

    public function purgeCacheByRevelantURLs($postId)
    {
        if ($this->isPluginSpecificCacheEnabled()) {
            $wp_domain_list = $this->integrationAPI->getDomainList();
            $wp_domain = $wp_domain_list[0];
            if (count($wp_domain) <= 0) {
                return;
            }

            $validPostStatus = array('publish', 'trash');
            $thisPostStatus = get_post_status($postId);

            if (get_permalink($postId) != true || !in_array($thisPostStatus, $validPostStatus)) {
                return;
            }

            if (is_int(wp_is_post_autosave($postId)) ||  is_int(wp_is_post_revision($postId))) {
                return;
            }

            $saved_post = get_post($postId);
            if (is_a($saved_post, 'WP_Post') == false) {
                return;
            }

            $urls = $this->getPostRelatedLinks($postId);

            $zoneTag = $this->api->getZoneTag($wp_domain);

            if (isset($zoneTag) && !empty($urls)) {
                $this->api->zonePurgeFiles($zoneTag, $urls);
            }
        }
    }

    public function getPostRelatedLinks($postId)
    {
        $listofurls = array();

        // Category purge
        $categories = get_the_category($postId);
        if ($categories) {
            foreach ($categories as $cat) {
                array_push($listofurls, get_category_link($cat->term_id));
            }
        }

        // Tag purge
        $tags = get_the_tags($postId);
        if ($tags) {
            foreach ($tags as $tag) {
                array_push($listofurls, get_tag_link($tag->term_id));
            }
        }

        // Author URL
        array_push(
            $listofurls,
            get_author_posts_url(get_post_field('post_author', $postId)),
            get_author_feed_link(get_post_field('post_author', $postId))
        );

        // Archives and their feeds
        if (get_post_type_archive_link(get_post_type($postId)) == true) {
            array_push(
                $listofurls,
                get_post_type_archive_link(get_post_type($postId)),
                get_post_type_archive_feed_link(get_post_type($postId))
            );
        }

        // Post URL
        array_push($listofurls, get_permalink($postId));

        // Also clean URL for trashed post.
        if (get_post_status($postId) == 'trash') {
            $trashpost = get_permalink($postId);
            $trashpost = str_replace('__trashed', '', $trashpost);
            array_push($listofurls, $trashpost, $trashpost.'feed/');
        }

        // Feeds
        array_push(
            $listofurls,
            get_bloginfo_rss('rdf_url'),
            get_bloginfo_rss('rss_url'),
            get_bloginfo_rss('rss2_url'),
            get_bloginfo_rss('atom_url'),
            get_bloginfo_rss('comments_rss2_url'),
            get_post_comments_feed_link($postId)
        );

        // Home Page and (if used) posts page
        array_push($listofurls, home_url('/'));
        if (get_option('show_on_front') == 'page') {
            array_push($listofurls, get_permalink(get_option('page_for_posts')));
        }

        return $listofurls;
    }

    protected function isPluginSpecificCacheEnabled()
    {
        $cacheSettingObject = $this->dataStore->getPluginSetting(\CF\API\Plugin::SETTING_PLUGIN_SPECIFIC_CACHE);
        $cacheSettingValue = $cacheSettingObject[\CF\API\Plugin::SETTING_VALUE_KEY];

        return isset($cacheSettingValue) && $cacheSettingValue !== false && $cacheSettingValue !== 'off';
    }
}
