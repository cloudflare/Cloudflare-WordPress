<?php

namespace CF\WordPress;

use CF\API\APIInterface;
use CF\API\Request;
use CF\Integration\DefaultIntegration;

class ClientActions
{
    private $api;
    private $config;
    private $wordpressAPI;
    private $dataStore;
    private $logger;
    private $request;

    /**
     * @param DefaultIntegration $defaultIntegration
     * @param APIInterface       $api
     * @param Request            $request
     */
    public function __construct(DefaultIntegration $defaultIntegration, APIInterface $api, Request $request)
    {
        $this->api = $api;
        $this->config = $defaultIntegration->getConfig();
        $this->wordpressAPI = $defaultIntegration->getIntegrationAPI();
        $this->dataStore = $defaultIntegration->getDataStore();
        $this->logger = $defaultIntegration->getLogger();
        $this->request = $request;
    }

    /**
     * GET /zones.
     *
     * @return mixed
     */
    public function returnWordPressDomain()
    {
        $cf_zones_list = $this->api->callAPI($this->request);
        $wordpress_domain_list = $this->wordpressAPI->getDomainList();
        $wordpress_domain = $wordpress_domain_list[0];

        $domain_list = array();
        if ($this->api->responseOk($cf_zones_list)) {
            $found = false;
            foreach ($cf_zones_list['result'] as $cf_zone) {
                if ($cf_zone['name'] === $wordpress_domain) {
                    $found = true;
                    array_push($domain_list, $cf_zone);
                }
            }

            if ($found === false) {
                array_push($domain_list, array(
                    'name' => $wordpress_domain,
                    'plan' => array('name' => ''),
                    'type' => '',
                    'status' => 'inactive',
                ));
            }
        }
        $cf_zones_list['result'] = $domain_list;

        return $cf_zones_list;
    }
}
