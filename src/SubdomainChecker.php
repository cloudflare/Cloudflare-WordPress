<?php

class SubdomainChecker
{
    private static $initiated = false;
    private static $isSubdomain = false;
    private static $originalDomain = '';
    private static $subDomain = '';

    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }

        // Init in a hacky way
        // $parse_uri is [plugin_path]/wp-admin/admin-ajax.php
        // get plugin path
        $parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
        $path = explode('wp-admin', $parse_uri[0]);
        require_once $path[0].'wp-load.php';

        $config = new CF\Integration\DefaultConfig('[]');
        $logger = new CF\Integration\DefaultLogger($config->getValue('debug'));
        $dataStore = new CF\WordPress\DataStore($logger);
        $wordpressAPI = new CF\WordPress\WordPressAPI($dataStore);
        $wordpressIntegration = new CF\Integration\DefaultIntegration($config, $wordpressAPI, $dataStore, $logger);
        $clientAPIClient = new CF\WordPress\WordPressClientAPI($wordpressIntegration);

        $wp_domain = $wordpressAPI->getOriginalDomain();
        self::$isSubdomain = $clientAPIClient->isSubdomain($wp_domain);

        if (self::$isSubdomain) {
            // Remove characters up to the first "." character.
            // For example:
            // blog.domain.com -> domain.com
            $newDomain = preg_replace('/^[^.]*.\s*/', '', $wp_domain);

            // Set global variables to be able to show error message.
            self::$originalDomain = $newDomain;
            self::$subDomain = $wp_domain;

            $wordpressAPI->setDomainName($newDomain);
        } else {
            $wordpressAPI->setDomainName($wp_domain);
        }
    }

    public static function init_hooks()
    {
        self::$initiated = true;

        add_action('admin_notices', array('SubdomainChecker', 'enableSubdomainNotice'));
    }

    public static function enableSubdomainNotice()
    {
        if (self::$isSubdomain) {
            $subDomain = self::$subDomain;
            $originalDomain = self::$originalDomain;

            $class = 'notice notice-error';
            $message = __("You are using subdomain < $subDomain >. Any changes made will effect the original domain < $originalDomain > as well.", '');
            printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
        }
    }
}
