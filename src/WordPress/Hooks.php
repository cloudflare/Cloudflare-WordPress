<?php

namespace CF\WordPress;

use CloudFlare\IpRewrite;

class Hooks
{
    protected $api;
    protected $config;
    protected $dataStore;
    protected $integrationAPI;
	protected $ipRewrite;
    protected $logger;

    const CF_MIN_PHP_VERSION = '5.3';
    const CF_MIN_WP_VERSION = '3.4';

    /**
     * @param \CF\Integration\IntegrationInterface $integrationContext
     */
    public function __construct(\CF\Integration\IntegrationInterface $integrationContext)
    {
        $this->api = new \CF\WordPress\WordPressClientAPI($integrationContext);
        $this->config = $integrationContext->getConfig();
        $this->dataStore = $integrationContext->getDataStore();
        $this->integrationAPI = $integrationContext->getIntegrationAPI();
		$this->ipRewrite = new IpRewrite();
        $this->logger = $integrationContext->getLogger();
    }

    /**
     * @param \CF\API\APIInterface $api
     */
    public function setAPI(\CF\API\APIInterface $api)
    {
        $this->api = $api;
    }

	public function setIPRewrite(\CloudFlare\IpRewrite $ipRewrite) {
		$this->ipRewrite = $ipRewrite;
	}

    public function init()
    {
		if ($this->ipRewrite->isCloudFlare()) {
			// Fixes issues with Flexible-SSL
			if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
				$_SERVER['HTTPS'] = 'on';
			}
		}
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

    public function cloudflareAdminInit()
    {
        // NARNIA!!
    }

    public function initProxy()
    {
        include WP_PLUGIN_DIR.'/cloudflare/proxy.php';
    }

    public function activate()
    {
        $this->checkVersionCompatibility();
        $this->checkDependenciesExist();
    }

    public function checkVersionCompatibility()
    {
        //wordpress global
        global $wp_version;

        if (version_compare(PHP_VERSION, self::CF_MIN_PHP_VERSION, '<')) {
            $flag = 'PHP';
            $version = self::CF_MIN_PHP_VERSION;
        }

        if (version_compare($wp_version, self::CF_MIN_WP_VERSION, '<')) {
            $flag = 'WordPress';
            $version = self::CF_MIN_WP_VERSION;
        }

        if (isset($flag) || isset($version)) {
            // Deactivate Plugin
            deactivate_plugins(basename(__FILE__));

            // Kill Execution
            wp_die('<p><strong>Cloudflare</strong> plugin requires '.$flag.'  version '.$version.' or greater.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));

            return;
        }
    }

    public function checkDependenciesExist()
    {
        // Guzzle3 depends on php5-curl. If dependency does not exist kill the plugin.
        if (!extension_loaded('curl')) {
            // Deactivate Plugin
            deactivate_plugins(basename(__FILE__));

            wp_die('<p><strong>Cloudflare</strong> plugin requires php5-curl to be installed.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));

            return;
        }
    }

    public function deactivate()
    {
        $this->dataStore->clearDataStore();
    }

	public static function uninstall() {
		$config = new \CF\Integration\DefaultConfig('[]');
		$logger = new \CF\Integration\DefaultLogger($config->getValue('debug'));
		$dataStore = new \CF\WordPress\DataStore($logger);
		$dataStore->clearDataStore();
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
