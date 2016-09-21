<?php

if (!defined('ABSPATH')) { // Exit if accessed directly
    exit;
}

header('Content-Type: application/json');

$config = new CF\Integration\DefaultConfig(file_get_contents('config.js', true));
$logger = new CF\Integration\DefaultLogger($config->getValue('debug'));
$dataStore = new CF\WordPress\DataStore($logger);
$wordpressAPI = new CF\WordPress\WordPressAPI($dataStore);
$wordpressIntegration = new CF\Integration\DefaultIntegration($config, $wordpressAPI, $dataStore, $logger);

$requestRouter = new \CF\Router\RequestRouter($wordpressIntegration);
$requestRouter->addRouter('\CF\WordPress\WordPressClientAPI', \CF\WordPress\ClientRoutes::$routes);
$requestRouter->addRouter('\CF\API\Plugin', \CF\WordPress\PluginRoutes::getRoutes(\CF\API\PluginRoutes::$routes));

// Check if domain name needs to cached
$wpDomain = $wordpressAPI->getOriginalDomain();
$cachedDomainList = $wordpressAPI->getDomainList();
$cachedDomain = $cachedDomainList[0];
if (CF\WordPress\Utils::getRegistrableDomain($wpDomain) !== $cachedDomain) {
    $wordPressClientAPI = new \CF\WordPress\WordPressClientAPI($wordpressIntegration);

    // Since we may not be logged in yet we need to check the credentials being set
    if ($wordPressClientAPI->isCrendetialsSet()) {
        // If it's not a subdomain cache the current domain
        $domainName = $wpDomain;

        // Get cloudflare zones to find if the current domain is a subdomain
        // of any cloudflare zones registered
        $response = $wordPressClientAPI->getZones();
        $validDomainName = $wordpressAPI->checkIfValidCloudflareSubdomain($response, $wpDomain);

        // Check if it's a subdomain, if it is cache the zone instead of the
        // subdomain
        if ($wordPressClientAPI->responseOK($response) && $validDomainName) {
            $domainName = CF\WordPress\Utils::getRegistrableDomain($wpDomain);
        }

        $wordpressAPI->setDomainNameCache($domainName);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$parameters = $_GET;
$body = json_decode(file_get_contents('php://input'), true);
$path = null;
if (strtoupper($method === 'GET')) {
    if ($_GET['proxyURLType'] === 'CLIENT') {
        $path = \CF\API\Client::ENDPOINT.$_GET['proxyURL'];
    } elseif ($_GET['proxyURLType'] === 'PLUGIN') {
        $path = \CF\API\Plugin::ENDPOINT.$_GET['proxyURL'];
    }
} else {
    $path = $body['proxyURL'];
}

unset($parameters['proxyURLType']);
unset($parameters['proxyURL']);
unset($body['proxyURL']);
$request = new CF\API\Request($method, $path, $parameters, $body);

// Only check CSRF if its not a GET request
if ($request->getMethod() === 'GET') {
    $isCSRFTokenValid = true;
} else {
    $body = $request->getBody();
    $nonce = $body['cfCSRFToken'];
    $isCSRFTokenValid = wp_verify_nonce($nonce, CF\WordPress\WordPressAPI::API_NONCE);
    unset($body['cfCSRFToken']);
}

if ($isCSRFTokenValid) {
    $response = $requestRouter->route($request);
} else {
    $message = 'CSRF Token not valid.';
    $response = array(
        'result' => null,
        'success' => false,
        'errors' => array(
            array(
                'code' => '',
                'message' => $message,
            ),
        ),
        'messages' => array(),
    );
}

//die is how wordpress ajax keeps the rest of the app from loading during an ajax request
die(json_encode($response));
