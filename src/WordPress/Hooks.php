<?php

namespace CF\WordPress;

use \CF\API\APIInterface;
use \CF\Integration;
use CloudFlare\IpRewrite;
use Psr\Log\LoggerInterface;

class Hooks
{
    protected $api;
    protected $config;
    protected $dataStore;
    protected $integrationAPI;
    protected $ipRewrite;
    protected $logger;

    public function __construct()
    {
		$this->config = new Integration\DefaultConfig('[]');
		$this->logger = new Integration\DefaultLogger(false);
		$this->dataStore = new DataStore($this->logger);
		$this->integrationAPI = new WordPressAPI($this->dataStore);
        $this->api = new WordPressClientAPI(new Integration\DefaultIntegration($this->config, $this->integrationAPI, $this->dataStore, $this->logger));
    }

    /**
     * @param \CF\API\APIInterface $api
     */
    public function setAPI(APIInterface $api)
    {
        $this->api = $api;
    }

	public function setConfig(Integration\ConfigInterface $config) {
		$this->config = $config;
	}

	public function setDataStore(Integration\DataStoreInterface $dataStore) {
		$this->dataStore = $dataStore;
	}

	public function setIntegrationAPI(Integration\IntegrationAPIInterface $integrationAPI) {
		$this->integrationAPI = $integrationAPI;
	}

	public function setLogger(LoggerInterface $logger) {
		$this->logger = $logger;
	}

    public function setIPRewrite(IpRewrite $ipRewrite)
    {
        $this->ipRewrite = $ipRewrite;
    }

    public function cloudflareConfigPage()
    {
        if (function_exists('add_options_page')) {
            add_options_page(__('CloudFlare Configuration'), __('CloudFlare'), 'manage_options', 'cloudflare', array($this, 'cloudflareIndexPage'));
        }
    }

    public function cloudflareIndexPage()
    {
        include WP_PLUGIN_DIR.'/cloudflare/index.php';
    }

    public function pluginActionLinks($links)
    {
        $links[] = '<a href="'.get_admin_url(null, 'options-general.php?page=cloudflare').'">Settings</a>';

        return $links;
    }

    public function initProxy()
    {
        include WP_PLUGIN_DIR.'/cloudflare/proxy.php';
    }

    public function activate()
    {
        if (version_compare($GLOBALS['wp_version'], CLOUDFLARE_MIN_WP_VERSION, '<')) {
            deactivate_plugins(basename(__FILE__));
            wp_die('<p><strong>Cloudflare</strong> plugin requires WordPress version '.CLOUDFLARE_MIN_WP_VERSION.' or greater.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));
        }

        // Guzzle3 depends on php5-curl. If dependency does not exist kill the plugin.
        if (!extension_loaded('curl')) {
            deactivate_plugins(basename(__FILE__));
            wp_die('<p><strong>Cloudflare</strong> plugin requires php5-curl to be installed.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));
        }

        return;
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

    public function isPluginSpecificCacheEnabled()
    {
        $cacheSettingObject = $this->dataStore->getPluginSetting(\CF\API\Plugin::SETTING_PLUGIN_SPECIFIC_CACHE);
        $cacheSettingValue = $cacheSettingObject[\CF\API\Plugin::SETTING_VALUE_KEY];

        return $cacheSettingValue;
    }
}
