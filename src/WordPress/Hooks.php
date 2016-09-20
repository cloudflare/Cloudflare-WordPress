<?php

namespace CF\WordPress;

use \CloudFlare\IpRewrite;

class Hooks {

	protected $config;
	protected $dataStore;
	protected $integrationAPI;
	protected $logger;

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
}

