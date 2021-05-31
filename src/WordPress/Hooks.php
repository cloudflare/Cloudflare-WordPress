<?php

namespace CF\WordPress;

use CF\API\APIInterface;
use CF\Integration;
use Psr\Log\LoggerInterface;
use WP_Taxonomy;

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
        $this->config = new Integration\DefaultConfig(file_get_contents(CLOUDFLARE_PLUGIN_DIR.'config.json', true));
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
        if ($this->isPluginSpecificCacheEnabled() || $this->isAutomaticPlatformOptimizationEnabled()) {
            $wpDomainList = $this->integrationAPI->getDomainList();
            if (count($wpDomainList) > 0) {
                $wpDomain = $wpDomainList[0];

                $zoneTag = $this->api->getZoneTag($wpDomain);

                if (isset($zoneTag)) {
                    $isOK = $this->api->zonePurgeCache($zoneTag);

                    $isOK = ($isOK) ? 'succeeded' : 'failed';
                    $this->logger->debug("purgeCacheEverything " . $isOK);
                }
            }
        }
    }

    public function purgeCacheByRelevantURLs($postId)
    {
        if ($this->isPluginSpecificCacheEnabled() || $this->isAutomaticPlatformOptimizationEnabled()) {
            $wpDomainList = $this->integrationAPI->getDomainList();
            if (!count($wpDomainList)) {
                return;
            }
            $wpDomain = $wpDomainList[0];

            // Do not purge for autosaves or updates to post revisions.
            if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
                return;
            }

            $savedPost = get_post($postId);
            if (!is_a($savedPost, 'WP_Post')) {
                return;
            }

            $urls = $this->getPostRelatedLinks($postId);
            $urls = apply_filters('cloudflare_purge_by_url', $urls, $postId);

            $zoneTag = $this->api->getZoneTag($wpDomain);

            if (isset($zoneTag) && !empty($urls)) {
                $chunks = array_chunk($urls, 30);

                foreach ($chunks as $chunk) {
                    $isOK = $this->api->zonePurgeFiles($zoneTag, $chunk);

                    $isOK = ($isOK) ? 'succeeded' : 'failed';
                    $this->logger->debug("List of URLs purged are: " . print_r($chunk, true));
                    $this->logger->debug("purgeCacheByRelevantURLs " . $isOK);
                }
            }
        }
    }

    public function getPostRelatedLinks($postId)
    {
        $listofurls = array();
        $postType = get_post_type($postId);

        //Purge taxonomies terms and feeds URLs
        $postTypeTaxonomies = get_object_taxonomies($postType);

        foreach ($postTypeTaxonomies as $taxonomy) {
            // Only if taxonomy is public
            $taxonomy_data = get_taxonomy($taxonomy);
            if ($taxonomy_data instanceof WP_Taxonomy && false === $taxonomy_data->public) {
                continue;
            }

            $terms = get_the_terms($postId, $taxonomy);

            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                $termLink = get_term_link($term);
                $termFeedLink = get_term_feed_link($term->term_id, $term->taxonomy);
                if (!is_wp_error($termLink) && !is_wp_error($termFeedLink)) {
                    array_push($listofurls, $termLink);
                    array_push($listofurls, $termFeedLink);
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

        // Refresh pagination
        $total_posts_count = wp_count_posts()->publish;
        $posts_per_page = get_option('posts_per_page');
        // Limit to up to 3 pages
        $page_number_max = min(3, ceil($total_posts_count / $posts_per_page));

        $this->logger->debug("total_posts_count $total_posts_count");
        $this->logger->debug("posts_per_page  $posts_per_page");
        $this->logger->debug("page_number_max $page_number_max");

        foreach (range(1, $page_number_max) as $page_number) {
            array_push($listofurls, home_url(sprintf('/page/%s/', $page_number)));
        }

        // Attachments
        if ('attachment' == $postType) {
            $attachmentUrls = array();
            foreach (get_intermediate_image_sizes() as $size) {
                $attachmentSrc = wp_get_attachment_image_src($postId, $size);
                if (is_array($attachmentSrc) && !empty($attachmentSrc)) {
                    $attachmentUrls[] = $attachmentSrc[0];
                }
            }
            $listofurls = array_merge(
                $listofurls,
                array_unique(array_filter($attachmentUrls))
            );
        }

        // Purge https and http URLs
        if (function_exists('force_ssl_admin') && force_ssl_admin()) {
            $listofurls = array_merge($listofurls, str_replace('https://', 'http://', $listofurls));
        } elseif (!is_ssl() && function_exists('force_ssl_content') && force_ssl_content()) {
            $listofurls = array_merge($listofurls, str_replace('http://', 'https://', $listofurls));
        }

        // Clean array if row empty
        $listofurls = array_filter($listofurls);

        return $listofurls;
    }

    protected function isPluginSpecificCacheEnabled()
    {
        $cacheSettingObject = $this->dataStore->getPluginSetting(\CF\API\Plugin::SETTING_PLUGIN_SPECIFIC_CACHE);

        if (! $cacheSettingObject) {
            return false;
        }

        $cacheSettingValue = $cacheSettingObject[\CF\API\Plugin::SETTING_VALUE_KEY];

        return $cacheSettingValue !== false
            && $cacheSettingValue !== 'off';
    }

    protected function isAutomaticPlatformOptimizationEnabled()
    {
        $cacheSettingObject = $this->dataStore->getPluginSetting(\CF\API\Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION);

        if (! $cacheSettingObject) {
            return false;
        }

        $cacheSettingValue = $cacheSettingObject[\CF\API\Plugin::SETTING_VALUE_KEY];

        return $cacheSettingValue !== false
            && $cacheSettingValue !== 'off';
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

    public function initAutomaticPlatformOptimization()
    {
      // it could be too late to set the headers,
      // return early without triggering a warning in logs
        if (headers_sent()) {
            return;
        }

      // add header unconditionally so we can detect plugin is activated
        if (!is_user_logged_in()) {
            header('cf-edge-cache: cache,platform=wordpress');
        } else {
            header('cf-edge-cache: no-cache');
        }
    }

    public function purgeCacheOnPostStatusChange($new_status, $old_status, $post)
    {
        if ('publish' === $new_status || 'publish' === $old_status) {
            $this->purgeCacheByRelevantURLs($post->ID);
        }
    }

    public function purgeCacheOnCommentStatusChange($new_status, $old_status, $comment)
    {
        if (!isset($comment->comment_post_ID) || empty($comment->comment_post_ID)) {
            return; // nothing to do
        }

      // in case the comment status changed, and either old or new status is "approved", we need to purge cache for the corresponding post
        if (($old_status != $new_status) && (($old_status === 'approved') || ($new_status === 'approved'))) {
            $this->purgeCacheByRelevantURLs($comment->comment_post_ID);
            return;
        }
    }

    public function purgeCacheOnNewComment($comment_id, $comment_status, $comment_data)
    {
        if ($comment_status != 1) {
            return; // if comment is not approved, stop
        }
        if (!is_array($comment_data)) {
            return; // nothing to do
        }
        if (!array_key_exists('comment_post_ID', $comment_data)) {
            return; // nothing to do
        }

      // all clear, we ne need to purge cache related to this post id
        $this->purgeCacheByRelevantURLs($comment_data['comment_post_ID']);
    }
}
