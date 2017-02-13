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
        // Call GET /zones
        $response = $this->api->callAPI($this->request);

        // Cache the domain for subdomains
        $this->cacheDomainName($response);

        // Get zone information
        $cf_zones_list = $this->filterZones($response);

        return $cf_zones_list;
    }

    private function filterZones($response)
    {
        $cf_zones_list = $response;
        $wpDomainList = $this->wordpressAPI->getDomainList();
        $wpDomain = $wpDomainList[0];

        $domain_list = array();
        if ($this->api->responseOk($cf_zones_list)) {
            $found = false;
            foreach ($cf_zones_list['result'] as $cf_zone) {
                if ($cf_zone['name'] === $wpDomain) {
                    $found = true;
                    array_push($domain_list, $cf_zone);
                }
            }

            if ($found === false) {
                array_push($domain_list, array(
                    'name' => $wpDomain,
                    'plan' => array('name' => ''),
                    'type' => '',
                    'status' => 'inactive',
                ));
            }
        }
        $cf_zones_list['result'] = $domain_list;

        return $cf_zones_list;
    }

    public function cacheDomainName($response)
    {
        // Check if domain name needs to cached
        $wpDomain = $this->wordpressAPI->getOriginalDomain();
        $cachedDomainList = $this->wordpressAPI->getDomainList();
        $cachedDomain = $cachedDomainList[0];

        if (Utils::getRegistrableDomain($wpDomain) !== $cachedDomain) {
            // If it's not a subdomain cache the current domain
            $domainName = $wpDomain;

            // Get cloudflare zones to find if the current domain is a
            // subdomain of any cloudflare zones registered
            $validDomainName = $this->wordpressAPI->checkIfValidCloudflareSubdomain($response, $wpDomain);

            // Check if it's a subdomain, if it is cache the zone instead of the
            // subdomain
            if ($this->api->responseOK($response) && $validDomainName) {
                $domainName = Utils::getRegistrableDomain($wpDomain);
            }

            $this->wordpressAPI->setDomainNameCache($domainName);

            // Log for debugging
            $this->logger->debug("Current domain -> $wpDomain");
            $this->logger->debug("Valid domain -> $validDomainName");
            $this->logger->debug("Cached domain -> $domainName");

            return $domainName;
        }

        return $cachedDomain;
    }
}
