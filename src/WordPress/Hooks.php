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
            add_options_page(__('CloudFlare Configuration'), __('CloudFlare'), 'manage_options', 'cloudflare', array($this, 'cloudflareIndexPage'));
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

    public function purgeCache()
    {
        if ($this->isPluginSpecificCacheEnabled()) {
            $wp_domain_list = $this->integrationAPI->getDomainList();
            $wp_domain = $wp_domain_list[0];
            if (count($wp_domain) > 0) {
                $zoneTag = $this->api->getZoneTag($wp_domain);

                if (isset($zoneTag)) {
                    // Do not care of the return value
                    $this->api->zonePurgeCache($zoneTag);
                }
            }
        }
    }

    public function purgeOnPostTransition($new_status, $old_status, $post) {
        // purge only on unpublished -> published || published -> unpublished
        if( ( $new_status != $old_status ) && ( 'publish' == $new_status || 'publish' == $old_status )){
            $this->purgeSinglePost($post);
        }
    }

    public function purgeOnPostUpdate($post_id, $post=null) {
        if( !wp_is_post_autosave( $post ) &&  !wp_is_post_revision( $post ) && ( 'publish' == get_post_status( $post ) ) ) {
          $this->purgeSinglePost($post);
        }
    }

    public function purgeSinglePost($target_post)
    {
        if ($this->isPluginSpecificCacheEnabled()) {
            $wp_domain_list = $this->integrationAPI->getDomainList();
            $wp_domain = $wp_domain_list[0];
            if (count($wp_domain) > 0) {
                $zoneTag = $this->api->getZoneTag($wp_domain);

            		if ( is_numeric($target_post) ) {
            			$target_post = get_post( (int)$target_post );
            		}

                if ( ! is_a( $target_post, 'WP_Post' ) ) {
                   return false;
                }

            		$post_url = get_permalink( $target_post );

            		$urls = array();

                //purge post url and home
            		array_push( $urls, $post_url, home_url() );

                //purge all taxonomies terms pages
            		$post_type = get_post_type( $target_post );

            		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

            		foreach ( $taxonomies as $taxonomy_slug => $taxonomy ){

            			$terms = get_the_terms( $target_post, $taxonomy_slug );

            			if ( !empty( $terms ) && ! is_wp_error( $terms ) ) {
            				foreach ( $terms as $term) {

            					$term_link = get_term_link( $term );

            					if ( ! is_wp_error( $term_link ) ) {
            						array_push( $urls, $term_link );
            					}
            				}
            			}
            		}

               //purge author pages
               $post_author = get_post_field('post_author', $target_post->ID);
                array_push($urls,
                  get_author_posts_url($post_author),
                  get_author_feed_link($post_author)
                );

                //purge post-type archive pages
                if (get_post_type_archive_link($post_type) == true) {
                  array_push($urls,
                    get_post_type_archive_link($post_type),
                    get_post_type_archive_feed_link($post_type)
                  );
                }

                //purge feeds
                array_push($urls,
                  get_bloginfo_rss('rdf_url') ,
                  get_bloginfo_rss('rss_url') ,
                  get_bloginfo_rss('rss2_url'),
                  get_bloginfo_rss('atom_url'),
                  get_bloginfo_rss('comments_rss2_url'),
                  get_post_comments_feed_link($target_post->ID)
                );

            		if ( ('post' == $post_type) && ( 'page' == get_option('show_on_front') ) && get_option( 'page_for_posts' ) ) {
            			array_push( $urls, get_permalink( get_option( 'page_for_posts' ) ) );
            		}

                if( is_ssl() ){
                    $urls = array_merge($urls, array_map( function($url){ return str_replace('https://', 'http://', $url); }, $urls) );
                }

                apply_filters('cloudflare_purge_post_urls', $urls, $target_post);

                if (isset($zoneTag)) {
                    $this->api->zonePurgeFiles($zoneTag, $urls);
                }
            }
        }
    }

    protected function isPluginSpecificCacheEnabled()
    {
        $cacheSettingObject = $this->dataStore->getPluginSetting(\CF\API\Plugin::SETTING_PLUGIN_SPECIFIC_CACHE);
        $cacheSettingValue = $cacheSettingObject[\CF\API\Plugin::SETTING_VALUE_KEY];

        return $cacheSettingValue;
    }
}
