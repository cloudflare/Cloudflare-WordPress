<?php

namespace Cloudflare\APO\WordPress;

use Cloudflare\APO\API;
use Cloudflare\APO\API\Plugin;
use Cloudflare\APO\Integration\IntegrationInterface;
use Cloudflare\APO\Router\RequestRouter;

class Proxy
{
    protected $config;
    protected $dataStore;
    protected $logger;
    protected $wordpressAPI;
    protected $wordpressClientAPI;
    protected $wordpressIntegration;
    protected $requestRouter;

    public $pluginAPI;

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
        $this->pluginAPI = new Plugin($this->wordpressIntegration);

        $this->requestRouter = new RequestRouter($this->wordpressIntegration);
        $this->requestRouter->addRouter($this->wordpressClientAPI, ClientRoutes::$routes);
        $this->requestRouter->addRouter($this->pluginAPI, PluginRoutes::getRoutes(PluginRoutes::$routes));
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
        if (!$this->wordpressAPI->isCurrentUserAdministrator()) {
            return;
        }

        header('Content-Type: application/json');

        $request = $this->createRequest();

        $response = null;
        $body = $request->getBody();
        $csrfToken = isset($body['cfCSRFToken']) ? $body['cfCSRFToken'] : null;
        if ($this->isCloudFlareCSRFTokenValid($request->getMethod(), $csrfToken)) {
            $response = $this->requestRouter->route($request);
        } else {
            if ($csrfToken === null) {
                $response = $this->wordpressClientAPI->createAPIError('CSRF Token not found. It\'s possible another plugin is altering requests sent by the Cloudflare plugin.');
            } else {
                $response = $this->wordpressClientAPI->createAPIError('CSRF Token not valid.');
            }
        }

        //die is how WordPress ajax keeps the rest of the app from loading during an ajax request
        wp_die(wp_json_encode($response));
    }

    public function createRequest()
    {
        // run() only calls this for administrators, and checks the CSRF token
        // for every method except GET, which is read-only.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
        $parameters = $_GET;
        $jsonInput = $this->getJSONBody();
        $body = json_decode((string) $jsonInput, true);
        $path = null;

        if (strtoupper($method) === 'GET') {
            $proxyURLType = isset($_GET['proxyURLType']) ? sanitize_text_field(wp_unslash($_GET['proxyURLType'])) : '';
            $endpoint = null;

            if ($proxyURLType === 'CLIENT') {
                $endpoint = API\Client::ENDPOINT;
            } elseif ($proxyURLType === 'PLUGIN') {
                $endpoint = API\Plugin::ENDPOINT;
            }

            // proxyURL is the API path after the endpoint. esc_url_raw() keeps
            // percent-encoding and query strings intact, unlike
            // sanitize_text_field().
            if ($endpoint !== null) {
                $path = isset($_GET['proxyURL']) ? esc_url_raw($endpoint . wp_unslash($_GET['proxyURL'])) : $endpoint;
            }
        } else {
            $path = $body['proxyURL'] ?? '';
        }

        unset($parameters['proxyURLType']);
        unset($parameters['proxyURL']);
        unset($body['proxyURL']);
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        return new API\Request($method, $path, $parameters, $body);
    }

    /**
     * Wrapped in a function so it can be
     * mocked during testing
     *
     * @return string|null The raw request body stored by Hooks::getCloudflareRequestJSON().
     */
    public function getJSONBody()
    {
        return $GLOBALS[Hooks::CLOUDFLARE_JSON] ?? null;
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
