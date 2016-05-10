<?php
require_once("vendor/autoload.php");
header('Content-Type: application/json');

$config = new CF\Integration\DefaultConfig(file_get_contents("config.js"));
$logger = new CF\Integration\DefaultLogger($config->getValue("debug"));
$dataStore = new CF\Wordpress\DataStore($logger);
$wordpressIntegration = ""; //TODO
$clientAPIClient = new CF\API\Client($wordpressIntegration);

$method = $_SERVER['REQUEST_METHOD'];
$parameters = $_GET;
$body = json_decode(file_get_contents('php://input'),true);
$path = (strtoupper($method === "GET") ? $_GET['proxyURL'] : $body['proxyURL']);

unset($parameters['proxyURL']);
unset($body['proxyURL']);
$request = new \CF\API\Request($method, $path, $parameters, $body);

//only check CSRF if its not a GET request
$isCSRFTokenValid = (($request->getMethod() === "GET") ? true : \CF\SecurityUtil::csrfTokenValidate($cpanelAPI->getHostAPIKey(), $cpanelAPI->getUserId(), $request->getBody()['cfCSRFToken']));
unset($body['cfCSRFToken']);
$apiResponse = "";

if($isCSRFTokenValid) {
    $apiResponse = $apiRouter->route($request);
} else {
    $apiResponse = $apiRouter->getAPIClient()->createAPIError("CSRF Token not valid.");
}

echo json_encode($apiResponse);
