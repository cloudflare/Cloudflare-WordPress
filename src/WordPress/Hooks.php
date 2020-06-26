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

    const CLOUDFLARE_JSON = 'CLOUDFLARE_JSON';
    const WP_AJAX_ACTION = 'cloudflare_proxy';

    public function __construct()
    {
        $this->config = new Integration\DefaultConfig(file_get_contents(CLOUDFLARE_PLUGIN_DIR.'config.js', true));
        $this->logger = new Integration\DefaultLogger($this->config->getValue('debug'));
        $this->dataStore = new DataStore($this->logger);
        $this->integrationAPI = new WordPressAPI($this->dataStore);
        $this->integrationContext = new Integration\DefaultIntegration($this->config, $this->integrationAPI, $this->dataStore, $this->logger);
        $this->api = new WordPressClientAPI($this->integrationContext);
        $this->proxy = new Proxy($this->integrationContext);
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

        return true;
    }

    public function deactivate()
    {
        $this->dataStore->clearDataStore();
    }

    public function purgeCacheEverything()
    {
        if ($this->isPluginSpecificCacheEnabled()) {
            $wpDomainList = $this->integrationAPI->getDomainList();
            $wpDomain = $wpDomainList[0];
            if (count($wpDomain) > 0) {
                $zoneTag = $this->api->getZoneTag($wpDomain);

                if (isset($zoneTag)) {
                    $isOK = $this->api->zonePurgeCache($zoneTag);

                    $isOK = ($isOK) ? 'succeeded' : 'failed';
                    $this->logger->debug("purgeCacheEverything " . $isOK);
                }
            }
        }
    }

    public function purgeCacheByRevelantURLs($postId)
    {
        if ($this->isPluginSpecificCacheEnabled()) {
            $wpDomainList = $this->integrationAPI->getDomainList();
            $wpDomain = $wpDomainList[0];
            if (count($wpDomain) <= 0) {
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

            $savedPost = get_post($postId);
            if (is_a($savedPost, 'WP_Post') == false) {
                return;
            }

            $urls = $this->getPostRelatedLinks($postId);
            $urls = apply_filters('cloudflare_purge_by_url', $urls, $postId);

            $zoneTag = $this->api->getZoneTag($wpDomain);

            if (isset($zoneTag) && !empty($urls)) {
                $isOK = $this->api->zonePurgeFiles($zoneTag, $urls);

                $isOK = ($isOK) ? 'succeeded' : 'failed';
                $this->logger->debug("List of URLs purged are: " . print_r($urls, true));
                $this->logger->debug("purgeCacheByRevelantURLs " . $isOK);
            }
        }
    }

    public function getPostRelatedLinks($postId)
    {
        $listofurls = array();
        $postType = get_post_type($postId);

        //Purge taxonomies terms URLs
        $postTypeTaxonomies = get_object_taxonomies($postType);

        foreach ($postTypeTaxonomies as $taxonomy) {
            $terms = get_the_terms($postId, $taxonomy);

            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                $termLink = get_term_link($term);
                if (!is_wp_error($termLink)) {
                    array_push($listofurls, $termLink);
                }
            }
        }

        // Author URL
        array_push(
            $listofurls,
            get_author_posts_url(get_post_field('post_author', $postId)),
            get_author_feed_link(get_post_field('post_author', $postId))
        );

        // Archives and their feeds
        if (get_post_type_archive_link($postType) == true) {
            array_push(
                $listofurls,
                get_post_type_archive_link($postType),
                get_post_type_archive_feed_link($postType)
            );
        }

        // Post URL
        array_push($listofurls, get_permalink($postId));

        // Also clean URL for trashed post.
        if (get_post_status($postId) == 'trash') {
            $trashPost = get_permalink($postId);
            $trashPost = str_replace('__trashed', '', $trashPost);
            array_push($listofurls, $trashPost, $trashPost.'feed/');
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
        $pageLink = get_permalink(get_option('page_for_posts'));
        if (is_string($pageLink) && !empty($pageLink) && get_option('show_on_front') == 'page') {
            array_push($listofurls, $pageLink);
        }

        // Purge https and http URLs
        if (function_exists('force_ssl_admin') && force_ssl_admin()) {
            $listofurls = array_merge($listofurls, str_replace('https://', 'http://', $listofurls));
        } elseif (!is_ssl() && function_exists('force_ssl_content') && force_ssl_content()) {
            $listofurls = array_merge($listofurls, str_replace('http://', 'https://', $listofurls));
        }

        return $listofurls;
    }

    protected function isPluginSpecificCacheEnabled()
    {
        $cacheSettingObject = $this->dataStore->getPluginSetting(\CF\API\Plugin::SETTING_PLUGIN_SPECIFIC_CACHE);
        $cacheSettingValue = $cacheSettingObject[\CF\API\Plugin::SETTING_VALUE_KEY];

        return isset($cacheSettingValue) && $cacheSettingValue !== false && $cacheSettingValue !== 'off';
    }

    public function http2ServerPushInit()
    {
        HTTP2ServerPush::init();
    }

    /*
     * php://input can only be read once before PHP 5.6, try to grab it ONLY if the request
     * is coming from the cloudflare proxy.  We store it in a global so \CF\WordPress\Proxy
     * can act on the request body later on in the script execution.
     */
    public function getCloudflareRequestJSON()
    {
        if (isset($_GET['action']) && $_GET['action'] === self::WP_AJAX_ACTION) {
            $GLOBALS[self::CLOUDFLARE_JSON] = file_get_contents('php://input');
        }
    }
}
