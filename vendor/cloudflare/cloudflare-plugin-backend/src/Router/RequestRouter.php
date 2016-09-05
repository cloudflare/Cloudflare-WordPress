<?php

namespace CF\Router;

use CF\API\Request;
use CF\Integration\IntegrationInterface;

class RequestRouter
{
    protected $integrationContext;

    protected $routerList;

    /**
     * @param IntegrationInterface $integrationContext
     */
    public function __construct(IntegrationInterface $integrationContext)
    {
        $this->integrationContext = $integrationContext;
        $this->routerList = array();
    }

    /**
     * @param $clientClassName
     * @param $routes
     */
    public function addRouter($clientClassName, $routes)
    {
        $client = new $clientClassName($this->integrationContext);
        $router = new DefaultRestAPIRouter($this->integrationContext, $client, $routes);
        $this->routerList[$client->getAPIClientName()] = $router;
    }

    /**
     * @return array
     */
    public function getRouterList()
    {
        return $this->routerList;
    }

    /**
     * @param $routerList
     */
    public function setRouterList($routerList)
    {
        $this->routerList = $routerList;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function route(Request $request)
    {
        foreach ($this->getRouterList() as $router) {
            if ($router->getAPIClient()->shouldRouteRequest($request)) {
                return $router->route($request);
            }
        }

        return;
    }
}
