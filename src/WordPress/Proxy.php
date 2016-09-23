<?php

namespace CF\WordPress;

use CF\API;
use CF\Integration\IntegrationInterface;
use CF\Router\RequestRouter;

class Proxy
{
    protected $config;
    protected $dataStore;
    protected $logger;
    protected $wordpressAPI;
    protected $wordpressClientAPI;
    protected $wordpressIntegration;
    protected $requestRouter;

    /**
     * @param IntegrationInterface $integration
     */
    public function __construct(IntegrationInterface $integration)
    {
        $this->config = $integration->getConfig();
        $this->dataStore = $integration->getDataStore();
        $this->logger = $integration->getLogger();
        $this->wordpressAPI = $integration->getIntegrationAPI();
        $this->wordpressIntegration = $integration;
        $this->wordpressClientAPI = new WordPressClientAPI($this->wordpressIntegration);

        $this->requestRouter = new RequestRouter($this->wordpressIntegration);
        $this->requestRouter->addRouter('\CF\WordPress\WordPressClientAPI', ClientRoutes::$routes);
        $this->requestRouter->addRouter('\CF\API\Plugin', PluginRoutes::getRoutes(PluginRoutes::$routes));
    }

    /**
     * @param API\APIInterface $wordpressClientAPI
     */
    public function setWordpressClientAPI(API\APIInterface $wordpressClientAPI)
    {
        $this->wordpressClientAPI = $wordpressClientAPI;
    }

    /**
     * @param RequestRouter $requestRouter
     */
    public function setRequestRouter(RequestRouter $requestRouter)
    {
        $this->requestRouter = $requestRouter;
    }

    public function run()
    {
        header('Content-Type: application/json');

        $this->cacheDomainName();

        $request = $this->createRequest();

        $response = null;
        $body = $request->getBody();
        $csrfToken = $body['cfCSRFToken'];
        if ($this->isCloudFlareCSRFTokenValid($request->getMethod(), $csrfToken)) {
            $response = $this->requestRouter->route($request);
        } else {
            if ($csrfToken === null) {
                $response = $this->wordpressClientAPI->createAPIError('CSRF Token not found.  Its possible another plugin is altering requests sent by the CloudFlare plugin.');
            } else {
                $response = $this->wordpressClientAPI->createAPIError('CSRF Token not valid.');
            }
        }

        //die is how wordpress ajax keeps the rest of the app from loading during an ajax request
        wp_die(json_encode($response));
    }

    public function cacheDomainName()
    {
        // Check if domain name needs to cached
        $wpDomain = $this->wordpressAPI->getOriginalDomain();
        $cachedDomainList = $this->wordpressAPI->getDomainList();
        $cachedDomain = $cachedDomainList[0];

        if (Utils::getRegistrableDomain($wpDomain) !== $cachedDomain) {
            // Since we may not be logged in yet we need to check the credentials being set
            if ($this->wordpressClientAPI->isCrendetialsSet()) {
                // If it's not a subdomain cache the current domain
                $domainName = $wpDomain;

                // Get cloudflare zones to find if the current domain is a subdomain
                // of any cloudflare zones registered
                $response = $this->wordpressClientAPI->getZones();
                $validDomainName = $this->wordpressAPI->checkIfValidCloudflareSubdomain($response, $wpDomain);

                // Check if it's a subdomain, if it is cache the zone instead of the
                // subdomain
                if ($this->wordpressClientAPI->responseOK($response) && $validDomainName) {
                    $domainName = Utils::getRegistrableDomain($wpDomain);
                }

                $this->wordpressAPI->setDomainNameCache($domainName);
            }
        }
    }

    public function createRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $parameters = $_GET;
        $body = json_decode(file_get_contents('php://input'), true);
        $path = null;

        if (strtoupper($method === 'GET')) {
            if ($_GET['proxyURLType'] === 'CLIENT') {
                $path = API\Client::ENDPOINT.$_GET['proxyURL'];
            } elseif ($_GET['proxyURLType'] === 'PLUGIN') {
                $path = API\Plugin::ENDPOINT.$_GET['proxyURL'];
            }
        } else {
            $path = $body['proxyURL'];
        }

        unset($parameters['proxyURLType']);
        unset($parameters['proxyURL']);
        unset($body['proxyURL']);

        return new API\Request($method, $path, $parameters, $body);
    }

    /**
     * https://codex.wordpress.org/Function_Reference/wp_verify_nonce.
     *
     * Boolean false if the nonce is invalid. Otherwise, returns an integer with the value of:
     * 1 – if the nonce has been generated in the past 12 hours or less.
     * 2 – if the nonce was generated between 12 and 24 hours ago.
     *
     * @param $csrfToken
     *
     * @return bool
     */
    public function isCloudFlareCSRFTokenValid($method, $csrfToken)
    {
        if ($method === 'GET') {
            return true;
        }

        return wp_verify_nonce($csrfToken, WordPressAPI::API_NONCE) !== false;
    }
}
