<?php

namespace CF\WordPress;

use \CloudFlare\IpRewrite;

class Hooks {

	protected $config;
	protected $dataStore;
	protected $integrationAPI;
	protected $logger;

	const CF_MIN_PHP_VERSION = '5.3';
	const CF_MIN_WP_VERSION = '3.4';

	/**
	 * @param \CF\Integration\IntegrationInterface $integrationContext
     */
	public function __construct(\CF\Integration\IntegrationInterface $integrationContext)
	{
		$this->config = $integrationContext->getConfig();
		$this->dataStore = $integrationContext->getDataStore();
		$this->integrationAPI = $integrationContext->getIntegrationAPI();
		$this->logger = $integrationContext->getLogger();
	}

	public function init() {
		$this->restoreOriginalIP();
	}

	public function restoreOriginalIP()
	{
		$ipRewrite = new IpRewrite();
		if ($ipRewrite->isCloudFlare()) {
			/*
			 * Fixes issues with Flexible-SSL
			 */
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

	public function checkVersionCompatibility()
	{
		$this->checkDependenciesExist();
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
}

