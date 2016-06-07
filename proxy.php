<?php

require_once 'vendor/autoload.php';

// include wp-load.php, directs logs to debug.log
$parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
require_once $parse_uri[0].'wp-load.php';

header('Content-Type: application/json');

$config = new CF\Integration\DefaultConfig(file_get_contents('config.js'));
$logger = new CF\Integration\DefaultLogger($config->getValue('debug'));
$dataStore = new CF\WordPress\DataStore($logger);
$wordpressAPI = new CF\WordPress\WordPressAPI($dataStore);
$wordpressIntegration = new CF\Integration\DefaultIntegration($config, $wordpressAPI, $dataStore, $logger);
$clientAPIClient = new CF\API\Client($wordpressIntegration);
$clientAPIClientRoutes = CF\WordPress\ClientRoutes::$routes;
$pluginAPIClient = new CF\API\Plugin($wordpressIntegration);
$pluginAPIPluginRoutes = CF\WordPress\PluginRoutes::$routes;

$method = $_SERVER['REQUEST_METHOD'];
$parameters = $_GET;
$body = json_decode(file_get_contents('php://input'), true);
$path = (strtoupper($method === 'GET') ? $_GET['proxyURL'] : $body['proxyURL']);

unset($parameters['proxyURL']);
unset($body['proxyURL']);
$request = new CF\API\Request($method, $path, $parameters, $body);

//only check CSRF if its not a GET request
// TODO: change $wordpressAPI->getHostAPIKey() to something appropriate
// since it's null
$isCSRFTokenValid = false;
$isCSRFTokenValid = ($request->getMethod() === 'GET') ? true : CF\SecurityUtil::csrfTokenValidate($wordpressAPI->getHostAPIKey(), $wordpressAPI->getUserId(), $request->getBody()['cfCSRFToken']);
unset($body['cfCSRFToken']);
$apiResponse = '';
$apiRouter = null;

if (isClientAPI($request->getUrl())) {
    $apiRouter = new CF\Router\DefaultRestAPIRouter($wordpressIntegration, $clientAPIClient, $clientAPIClientRoutes);
} else {
    $apiRouter = new CF\Router\DefaultRestAPIRouter($wordpressIntegration, $pluginAPIClient, $pluginAPIPluginRoutes);
}

if ($isCSRFTokenValid) {
    $apiResponse = $apiRouter->route($request);
} else {
    $apiResponse = $apiRouter->getAPIClient()->createAPIError('CSRF Token not valid.');
}

echo json_encode($apiResponse);

/**
 * @param $path
 *
 * @return bool
 */
function isClientAPI($path)
{
    return strpos($path, CF\API\Client::ENDPOINT) !== false;
}
